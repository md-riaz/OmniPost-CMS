<?php

namespace App\Console\Commands;

use App\Models\ConnectedSocialAccount;
use App\Models\OAuthToken;
use App\Services\Platforms\FacebookConnector;
use App\Services\Platforms\LinkedInConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TokenExpiryWatcher extends Command
{
    protected $signature = 'oauth:watch-expiry {--refresh : Attempt to refresh expiring tokens}';

    protected $description = 'Check for expiring OAuth tokens and optionally refresh them';

    public function handle()
    {
        $this->info('Checking OAuth tokens for expiry...');

        $expiringTokens = OAuthToken::with('connectedSocialAccounts')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>', now())
            ->get();

        $expiredTokens = OAuthToken::with('connectedSocialAccounts')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $this->info("Found {$expiringTokens->count()} tokens expiring within 7 days");
        $this->info("Found {$expiredTokens->count()} expired tokens");

        // Mark connected accounts with expired tokens
        foreach ($expiredTokens as $token) {
            $accounts = $token->connectedSocialAccounts()->where('status', 'connected')->get();
            
            foreach ($accounts as $account) {
                $account->update(['status' => 'expired']);
                $this->warn("Marked account {$account->display_name} ({$account->platform}) as expired");
                
                Log::warning('OAuth token expired', [
                    'token_id' => $token->id,
                    'account_id' => $account->id,
                    'platform' => $account->platform,
                ]);
            }
        }

        // Send notifications for expiring tokens
        if ($expiringTokens->count() > 0) {
            $admins = \App\Models\User::whereHas('roles', function ($query) {
                $query->where('slug', 'admin');
            })->get();

            foreach ($expiringTokens as $token) {
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\TokenExpiringNotification($token));
                }
            }

            $this->info("Sent expiry notifications to {$admins->count()} admin(s)");
        }

        // Attempt to refresh tokens if --refresh flag is set
        if ($this->option('refresh')) {
            $this->info('Attempting to refresh expiring tokens...');
            
            foreach ($expiringTokens as $token) {
                try {
                    $connector = $this->getConnector($token->platform);
                    $oldExpiresAt = $token->expires_at?->toDateTimeString();
                    $refreshedToken = $connector->refreshTokenIfNeeded($token);
                    $newExpiresAt = $refreshedToken->expires_at?->toDateTimeString();
                    
                    if ($oldExpiresAt !== $newExpiresAt) {
                        $this->info("Refreshed token for {$token->platform} (ID: {$token->id})");
                        
                        Log::info('OAuth token refreshed', [
                            'token_id' => $token->id,
                            'platform' => $token->platform,
                            'old_expires_at' => $oldExpiresAt,
                            'new_expires_at' => $newExpiresAt,
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to refresh token {$token->id}: {$e->getMessage()}");
                    
                    Log::error('Failed to refresh OAuth token', [
                        'token_id' => $token->id,
                        'platform' => $token->platform,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Mark accounts as expired if refresh fails
                    $accounts = $token->connectedSocialAccounts()->where('status', 'connected')->get();
                    foreach ($accounts as $account) {
                        $account->update(['status' => 'expired']);
                    }
                }
            }
        }

        $this->info('Token expiry check completed');
        
        return Command::SUCCESS;
    }

    private function getConnector(string $platform)
    {
        return match ($platform) {
            'facebook' => new FacebookConnector(),
            'linkedin' => new LinkedInConnector(),
            default => throw new \InvalidArgumentException("Unsupported platform: {$platform}"),
        };
    }
}
