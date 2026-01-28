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

        $expiringTokens = OAuthToken::whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>', now())
            ->get();

        $expiredTokens = OAuthToken::whereNotNull('expires_at')
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

        // Attempt to refresh tokens if --refresh flag is set
        if ($this->option('refresh')) {
            $this->info('Attempting to refresh expiring tokens...');
            
            foreach ($expiringTokens as $token) {
                try {
                    $connector = $this->getConnector($token->platform);
                    $refreshedToken = $connector->refreshTokenIfNeeded($token);
                    
                    if ($refreshedToken->wasChanged()) {
                        $this->info("Refreshed token for {$token->platform} (ID: {$token->id})");
                        
                        Log::info('OAuth token refreshed', [
                            'token_id' => $token->id,
                            'platform' => $token->platform,
                            'new_expires_at' => $refreshedToken->expires_at,
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

