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
        } catch (\Exception $e) {
            Log::error('Failed to refresh LinkedIn token', [
                'token_id' => $token->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function refreshTokenIfNeeded(OAuthToken $token): OAuthToken
    {
        // Refresh if token expires in less than 7 days
        if ($token->expires_at && $token->expires_at->subDays(7)->isPast()) {
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
        } catch (\Exception $e) {
            Log::error('Failed to list LinkedIn organizations', [
                'token_id' => $token->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
