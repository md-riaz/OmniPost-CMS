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
            'headers' => [
                'LinkedIn-Version' => '202401',
                'X-Restli-Protocol-Version' => '2.0.0',
            ],
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
            // Prepare payload for LinkedIn Posts API (new format)
            $payload = [
                'author' => $targetId,
                'commentary' => $text,
                'visibility' => 'PUBLIC',
                'distribution' => [
                    'feedDistribution' => 'MAIN_FEED',
                    'targetEntities' => [],
                    'thirdPartyDistributionChannels' => []
                ],
                'lifecycleState' => 'PUBLISHED',
                'isReshareDisabledByAuthor' => false
            ];

            // Add optional link
            if (!empty($options['link'])) {
                $payload['content'] = [
                    'article' => [
                        'source' => $options['link'],
                        'title' => '', // Can be extracted if provided
                        'description' => ''
                    ]
                ];
            }

            $response = $this->client->post('https://api.linkedin.com/rest/posts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
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

    public function fetchMetrics(string $shareUrn, string $accessToken): array
    {
        try {
            // Extract organization URN from share URN if possible, or just use the share URN directly
            // For REST API, we need the URN in the format urn:li:share:123 or urn:li:ugcPost:123
            
            // 1. Fetch Post details to verify existence and get basic info
            // URL encode the URN
            $encodedUrn = urlencode($shareUrn);
            
            $postResponse = $this->client->get(
                'https://api.linkedin.com/rest/posts/' . $encodedUrn,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                ]
            );

            $postData = json_decode($postResponse->getBody()->getContents(), true);
            $organizationUrn = $postData['author'] ?? null;

            if (!$organizationUrn) {
                // If we can't get the author, we can't reliably query statistics in the context of an org 
                // but let's try querying stats directly for the share first
                // Note: The author field is usually present in the post object
                Log::warning('Could not determine organization URN from post details', ['share_urn' => $shareUrn]);
            }

            // 2. Fetch Statistics using the organizationalEntityShareStatistics endpoint
            // This endpoint supports looking up stats by share URN(s)
            $statsResponse = $this->client->get(
                'https://api.linkedin.com/rest/organizationalEntityShareStatistics',
                [
                    'query' => [
                        'q' => 'organizationalEntity',
                        'organizationalEntity' => $organizationUrn, // Required by the endpoint
                        'shares' => 'List(' . urlencode($shareUrn) . ')',
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                ]
            );

            $statsData = json_decode($statsResponse->getBody()->getContents(), true);
            
            // Extract statistics for our share
            $stats = null;
            // The REST API returns 'elements' array
            foreach ($statsData['elements'] ?? [] as $element) {
                if (($element['share'] ?? '') === $shareUrn) {
                    $stats = $element['totalShareStatistics'] ?? [];
                    break;
                }
            }

            if (!$stats) {
                $stats = [
                    'likeCount' => 0,
                    'commentCount' => 0,
                    'shareCount' => 0,
                    'impressionCount' => 0,
                    'clickCount' => 0,
                ];
            }

            // Normalize metrics
            $normalized = [
                'likes' => $stats['likeCount'] ?? 0,
                'comments' => $stats['commentCount'] ?? 0,
                'shares' => $stats['shareCount'] ?? 0,
                'impressions' => $stats['impressionCount'] ?? 0,
                'clicks' => $stats['clickCount'] ?? 0,
            ];

            Log::info('Fetched LinkedIn metrics', [
                'share_urn' => $shareUrn,
                'metrics' => $normalized,
            ]);

            return [
                'success' => true,
                'metrics' => $normalized,
                'raw_data' => [
                    'post' => $postData,
                    'statistics' => $statsData,
                ],
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode();
            $responseBody = $e->getResponse()?->getBody()->getContents();
            $errorData = json_decode($responseBody, true);

            Log::error('Failed to fetch LinkedIn metrics', [
                'share_urn' => $shareUrn,
                'status_code' => $statusCode,
                'error' => $e->getMessage(),
                'response' => $responseBody,
            ]);

            return [
                'success' => false,
                'error' => $errorData['message'] ?? $e->getMessage(),
                'error_code' => $statusCode,
            ];
        }
    }
}
