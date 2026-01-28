<?php

namespace App\Services\Platforms;

use App\Contracts\PlatformConnector;
use App\Models\OAuthToken;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FacebookConnector implements PlatformConnector
{
    private Client $client;
    private string $appId;
    private string $appSecret;
    private string $graphApiVersion;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
        ]);
        $this->appId = config('services.facebook.client_id');
        $this->appSecret = config('services.facebook.client_secret');
        $this->graphApiVersion = config('services.facebook.graph_api_version', 'v18.0');
    }

    public function getPlatform(): string
    {
        return 'facebook';
    }

    public function getAuthUrl(string $redirectUri, array $state = []): string
    {
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'state' => base64_encode(json_encode($state)),
            'scope' => implode(',', [
                'pages_show_list',
                'pages_read_engagement',
                'pages_manage_posts',
            ]),
        ];

        return 'https://www.facebook.com/' . $this->graphApiVersion . '/dialog/oauth?' . http_build_query($params);
    }

    public function handleCallback(string $code, array $state = []): OAuthToken
    {
        $response = $this->client->get('https://graph.facebook.com/' . $this->graphApiVersion . '/oauth/access_token', [
            'query' => [
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'code' => $code,
                'redirect_uri' => route('oauth.callback', ['platform' => 'facebook']),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        // Get long-lived token
        $longLivedResponse = $this->client->get('https://graph.facebook.com/' . $this->graphApiVersion . '/oauth/access_token', [
            'query' => [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'fb_exchange_token' => $data['access_token'],
            ],
        ]);

        $longLivedData = json_decode($longLivedResponse->getBody()->getContents(), true);

        $token = OAuthToken::create([
            'platform' => 'facebook',
            'access_token' => $longLivedData['access_token'],
            'refresh_token' => null, // Facebook doesn't use refresh tokens
            'expires_at' => isset($longLivedData['expires_in']) 
                ? now()->addSeconds($longLivedData['expires_in']) 
                : now()->addDays(60), // Default 60 days for long-lived tokens
            'scopes' => ['pages_show_list', 'pages_read_engagement', 'pages_manage_posts'],
            'meta' => [
                'token_type' => $longLivedData['token_type'] ?? 'bearer',
            ],
        ]);

        Log::info('Facebook OAuth token created', ['token_id' => $token->id]);

        return $token;
    }

    public function refreshToken(OAuthToken $token): OAuthToken
    {
        // Facebook long-lived tokens don't have a traditional refresh mechanism
        // Instead, we exchange the old token for a new one
        try {
            $response = $this->client->get('https://graph.facebook.com/' . $this->graphApiVersion . '/oauth/access_token', [
                'query' => [
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $this->appId,
                    'client_secret' => $this->appSecret,
                    'fb_exchange_token' => $token->access_token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $token->update([
                'access_token' => $data['access_token'],
                'expires_at' => isset($data['expires_in']) 
                    ? now()->addSeconds($data['expires_in']) 
                    : now()->addDays(60),
            ]);

            Log::info('Facebook token refreshed', ['token_id' => $token->id]);

            return $token->fresh();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Failed to refresh Facebook token', [
                'token_id' => $token->id,
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse()?->getStatusCode(),
            ]);
            throw $e;
        }
    }

    public function refreshTokenIfNeeded(OAuthToken $token): OAuthToken
    {
        // Refresh if token expires in less than 7 days
        if ($token->expires_at && $token->expires_at->lt(now()->addDays(7))) {
            return $this->refreshToken($token);
        }

        return $token;
    }

    public function listPublishTargets(OAuthToken $token): Collection
    {
        try {
            $response = $this->client->get('https://graph.facebook.com/' . $this->graphApiVersion . '/me/accounts', [
                'query' => [
                    'access_token' => $token->access_token,
                    'fields' => 'id,name,access_token',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return collect($data['data'] ?? [])->map(function ($page) {
                return [
                    'external_id' => $page['id'],
                    'display_name' => $page['name'],
                    'meta' => [
                        'page_access_token' => $page['access_token'],
                    ],
                ];
            });
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Failed to list Facebook pages', [
                'token_id' => $token->id,
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse()?->getStatusCode(),
            ]);
            throw $e;
        }
    }

    public function publish(string $targetId, string $text, string $accessToken, array $options = []): array
    {
        try {
            $params = [
                'message' => $text,
                'access_token' => $accessToken,
            ];

            // Add optional link
            if (!empty($options['link'])) {
                $params['link'] = $options['link'];
            }

            $response = $this->client->post(
                'https://graph.facebook.com/' . $this->graphApiVersion . '/' . $targetId . '/feed',
                [
                    'form_params' => $params,
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['id'])) {
                throw new \Exception('Facebook API did not return post ID');
            }

            Log::info('Successfully published to Facebook', [
                'page_id' => $targetId,
                'post_id' => $data['id'],
            ]);

            return [
                'external_post_id' => $data['id'],
                'raw_response' => $data,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode();
            $responseBody = $e->getResponse()?->getBody()->getContents();
            $errorData = json_decode($responseBody, true);

            Log::error('Failed to publish to Facebook', [
                'page_id' => $targetId,
                'status_code' => $statusCode,
                'error' => $e->getMessage(),
                'response' => $responseBody,
            ]);

            // Check for token expiry (190 = expired token)
            if (isset($errorData['error']['code']) && $errorData['error']['code'] == 190) {
                throw new \Exception('Token expired: ' . ($errorData['error']['message'] ?? 'Unknown error'), 190);
            }

            // Check for rate limiting (613 = rate limit)
            if (isset($errorData['error']['code']) && $errorData['error']['code'] == 613) {
                throw new \Exception('Rate limited: ' . ($errorData['error']['message'] ?? 'Unknown error'), 613);
            }

            throw new \Exception('Failed to publish to Facebook: ' . ($errorData['error']['message'] ?? $e->getMessage()), $statusCode ?? 500);
        }
    }
}
