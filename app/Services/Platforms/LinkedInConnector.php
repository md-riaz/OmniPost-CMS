<?php

namespace App\Services\Platforms;

use App\Contracts\PlatformConnector;
use App\Models\OAuthToken;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LinkedInConnector implements PlatformConnector
{
    private Client $client;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
        ]);
        $this->clientId = config('services.linkedin.client_id');
        $this->clientSecret = config('services.linkedin.client_secret');
    }

    public function getPlatform(): string
    {
        return 'linkedin';
    }

    public function getAuthUrl(string $redirectUri, array $state = []): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'state' => base64_encode(json_encode($state)),
            'scope' => implode(' ', [
                'r_organization_social',
                'w_organization_social',
                'rw_organization_admin',
            ]),
        ];

        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
    }

    public function handleCallback(string $code, array $state = []): OAuthToken
    {
        $response = $this->client->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => route('oauth.callback', ['platform' => 'linkedin']),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $token = OAuthToken::create([
            'platform' => 'linkedin',
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => isset($data['expires_in']) 
                ? now()->addSeconds($data['expires_in']) 
                : now()->addDays(60),
            'scopes' => explode(' ', $data['scope'] ?? 'r_organization_social w_organization_social'),
            'meta' => [
                'token_type' => $data['token_type'] ?? 'Bearer',
            ],
        ]);

        Log::info('LinkedIn OAuth token created', ['token_id' => $token->id]);

        return $token;
    }

    public function refreshToken(OAuthToken $token): OAuthToken
    {
        if (!$token->refresh_token) {
            throw new \Exception('No refresh token available for LinkedIn token');
        }

        try {
            $response = $this->client->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token->refresh_token,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $token->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $token->refresh_token,
                'expires_at' => isset($data['expires_in']) 
                    ? now()->addSeconds($data['expires_in']) 
                    : now()->addDays(60),
            ]);

            Log::info('LinkedIn token refreshed', ['token_id' => $token->id]);

            return $token->fresh();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Failed to refresh LinkedIn token', [
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
            // Get user profile to find organizations
            $profileResponse = $this->client->get('https://api.linkedin.com/v2/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token->access_token,
                    'LinkedIn-Version' => '202401',
                ],
            ]);

            // Get organizations the user can administer
            $orgsResponse = $this->client->get('https://api.linkedin.com/v2/organizationalEntityAcls', [
                'query' => [
                    'q' => 'roleAssignee',
                    'role' => 'ADMINISTRATOR',
                    'projection' => '(elements*(organizationalTarget~(localizedName,vanityName)))',
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token->access_token,
                    'LinkedIn-Version' => '202401',
                ],
            ]);

            $data = json_decode($orgsResponse->getBody()->getContents(), true);

            return collect($data['elements'] ?? [])->map(function ($element) {
                $org = $element['organizationalTarget~'] ?? [];
                $orgId = $element['organizationalTarget'] ?? '';

                return [
                    'external_id' => $orgId,
                    'display_name' => $org['localizedName'] ?? 'Unknown Organization',
                    'meta' => [
                        'vanity_name' => $org['vanityName'] ?? null,
                    ],
                ];
            });
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Failed to list LinkedIn organizations', [
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
            $payload = [
                'author' => $targetId,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $text,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];

            // Add optional link
            if (!empty($options['link'])) {
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'originalUrl' => $options['link'],
                    ],
                ];
            }

            $response = $this->client->post('https://api.linkedin.com/v2/ugcPosts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'LinkedIn-Version' => '202401',
                ],
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['id'])) {
                throw new \Exception('LinkedIn API did not return post ID');
            }

            Log::info('Successfully published to LinkedIn', [
                'org_id' => $targetId,
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

            Log::error('Failed to publish to LinkedIn', [
                'org_id' => $targetId,
                'status_code' => $statusCode,
                'error' => $e->getMessage(),
                'response' => $responseBody,
            ]);

            // Check for token expiry (401 = unauthorized/expired)
            if ($statusCode == 401) {
                throw new \Exception('Token expired: ' . ($errorData['message'] ?? 'Unauthorized'), 401);
            }

            // Check for rate limiting (429 = too many requests)
            if ($statusCode == 429) {
                throw new \Exception('Rate limited: ' . ($errorData['message'] ?? 'Too many requests'), 429);
            }

            throw new \Exception('Failed to publish to LinkedIn: ' . ($errorData['message'] ?? $e->getMessage()), $statusCode ?? 500);
        }
    }
}
