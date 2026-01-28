<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class OAuthToken extends Model
{
    protected $fillable = [
        'platform',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'scopes' => 'array',
        'meta' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // Encrypt/decrypt access token
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    // Encrypt/decrypt refresh token
    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function connectedSocialAccounts(): HasMany
    {
        return $this->hasMany(ConnectedSocialAccount::class, 'token_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
