<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectedSocialAccount extends Model
{
    protected $fillable = [
        'brand_id',
        'platform',
        'external_account_id',
        'display_name',
        'token_id',
        'status',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(OAuthToken::class, 'token_id');
    }

    public function postVariants(): HasMany
    {
        return $this->hasMany(PostVariant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'connected' && 
               $this->token && 
               !$this->token->isExpired();
    }
}
