<?php

namespace App\Contracts;

use App\Models\OAuthToken;
use Illuminate\Support\Collection;

interface PlatformConnector
{
    /**
     * Get the OAuth authorization URL
     *
     * @param string $redirectUri The callback URL
     * @param array $state Additional state data (e.g., brand_id)
     * @return string
     */
    public function getAuthUrl(string $redirectUri, array $state = []): string;

    /**
     * Handle the OAuth callback and create/update the token
     *
     * @param string $code Authorization code from OAuth provider
     * @param array $state State data returned from OAuth provider
     * @return OAuthToken
     */
    public function handleCallback(string $code, array $state = []): OAuthToken;

    /**
     * Refresh an expired OAuth token
     *
     * @param OAuthToken $token
     * @return OAuthToken
     */
    public function refreshToken(OAuthToken $token): OAuthToken;

    /**
     * Check if token needs refreshing and refresh if needed
     *
     * @param OAuthToken $token
     * @return OAuthToken
     */
    public function refreshTokenIfNeeded(OAuthToken $token): OAuthToken;

    /**
     * List all publish targets (pages, organizations, etc.) for the authenticated token
     *
     * @param OAuthToken $token
     * @return Collection Collection of arrays with keys: external_id, display_name, meta
     */
    public function listPublishTargets(OAuthToken $token): Collection;

    /**
     * Get the platform identifier (e.g., 'facebook', 'linkedin')
     *
     * @return string
     */
    public function getPlatform(): string;

    /**
     * Publish content to the platform
     *
     * @param string $targetId The external ID of the publish target (page, org, etc.)
     * @param string $text The text content to publish
     * @param string $accessToken The access token (can be user or page token)
     * @param array $options Additional options (link, media, etc.)
     * @return array ['external_post_id' => string, 'raw_response' => array]
     * @throws \Exception on publish failure
     */
    public function publish(string $targetId, string $text, string $accessToken, array $options = []): array;
}
