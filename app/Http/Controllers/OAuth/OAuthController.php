<?php

namespace App\Http\Controllers\OAuth;

use App\Contracts\PlatformConnector;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ConnectedSocialAccount;
use App\Models\AuditLog;
use App\Services\Platforms\FacebookConnector;
use App\Services\Platforms\LinkedInConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    private function getConnector(string $platform): PlatformConnector
    {
        return match ($platform) {
            'facebook' => new FacebookConnector(),
            'linkedin' => new LinkedInConnector(),
            default => throw new \InvalidArgumentException("Unsupported platform: {$platform}"),
        };
    }

    public function redirect(Request $request, string $platform)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
        ]);

        $this->authorize('create', ConnectedSocialAccount::class);

        $brand = Brand::findOrFail($request->input('brand_id'));

        $connector = $this->getConnector($platform);
        $redirectUri = route('oauth.callback', ['platform' => $platform]);

        $state = [
            'brand_id' => $brand->id,
            'user_id' => $request->user()->id,
        ];

        $authUrl = $connector->getAuthUrl($redirectUri, $state);

        return redirect($authUrl);
    }

    public function callback(Request $request, string $platform)
    {
        if ($request->has('error')) {
            Log::warning('OAuth error', [
                'platform' => $platform,
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'OAuth authorization failed: ' . $request->input('error_description', 'Unknown error'));
        }

        $code = $request->input('code');
        $stateEncoded = $request->input('state');

        if (!$code || !$stateEncoded) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid OAuth callback parameters');
        }

        try {
            $state = json_decode(base64_decode($stateEncoded), true);
            $brandId = $state['brand_id'] ?? null;
            $stateUserId = $state['user_id'] ?? null;

            if (!$brandId) {
                throw new \Exception('Missing brand_id in state');
            }

            // Verify the user_id in state matches the current user
            if ($stateUserId && $request->user()->id !== $stateUserId) {
                throw new \Exception('User ID mismatch - potential authorization bypass attempt');
            }

            $this->authorize('create', ConnectedSocialAccount::class);

            $brand = Brand::findOrFail($brandId);
            $connector = $this->getConnector($platform);

            // Exchange code for token
            $token = $connector->handleCallback($code, $state);

            // Get publish targets (pages/orgs)
            $targets = $connector->listPublishTargets($token);

            $accountsCreated = 0;
            foreach ($targets as $target) {
                // For Facebook, we need to store the page access token
                if ($platform === 'facebook' && isset($target['meta']['page_access_token'])) {
                    // Create a separate token for each page
                    $pageToken = \App\Models\OAuthToken::create([
                        'platform' => 'facebook',
                        'access_token' => $target['meta']['page_access_token'],
                        'refresh_token' => null,
                        'expires_at' => null, // Page tokens don't expire
                        'scopes' => [
                            'pages_read_engagement',
                            'pages_manage_posts',
                            'pages_manage_metadata',
                            'pages_read_user_content',
                        ],
                        'meta' => [
                            'page_id' => $target['external_id'],
                            'page_name' => $target['display_name'],
                            'tasks' => $target['meta']['tasks'] ?? [],
                        ],
                    ]);

                    $tokenId = $pageToken->id;
                    $accountMeta = [
                        'page_access_token' => $target['meta']['page_access_token'],
                        'tasks' => $target['meta']['tasks'] ?? [],
                    ];
                } else {
                    $tokenId = $token->id;
                    $accountMeta = $target['meta'] ?? [];
                }

                // Create or update connected account
                $connectedAccount = ConnectedSocialAccount::updateOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'platform' => $platform,
                        'external_account_id' => $target['external_id'],
                    ],
                    [
                        'display_name' => $target['display_name'],
                        'token_id' => $tokenId,
                        'status' => 'connected',
                        'meta' => $accountMeta,
                    ]
                );

                // Audit log
                AuditLog::log('account_connected', $connectedAccount, [
                    'platform' => $platform,
                    'account_name' => $target['display_name'],
                ]);

                $accountsCreated++;
            }

            Log::info('OAuth connection successful', [
                'platform' => $platform,
                'brand_id' => $brand->id,
                'accounts_created' => $accountsCreated,
            ]);

            return redirect()->route('dashboard')
                ->with('success', "Successfully connected {$accountsCreated} {$platform} account(s) for {$brand->name}");

        } catch (\Exception $e) {
            Log::error('OAuth callback failed', [
                'platform' => $platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Failed to connect account. Please try again.');
        }
    }

    public function disconnect(Request $request, ConnectedSocialAccount $account)
    {
        $this->authorize('update', $account);

        try {
            $account->update(['status' => 'revoked']);

            // Audit log
            AuditLog::log('account_disconnected', $account, [
                'platform' => $account->platform,
                'account_name' => $account->display_name,
            ]);

            Log::info('Account disconnected', [
                'account_id' => $account->id,
                'platform' => $account->platform,
            ]);

            return back()->with('success', 'Account disconnected successfully');
        } catch (\Exception $e) {
            Log::error('Failed to disconnect account', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to disconnect account');
        }
    }

    public function reconnect(Request $request, ConnectedSocialAccount $account)
    {
        $this->authorize('update', $account);

        $redirectRequest = $request->duplicate();
        $redirectRequest->merge(['brand_id' => $account->brand_id]);

        return $this->redirect($redirectRequest, $account->platform);
    }
}
