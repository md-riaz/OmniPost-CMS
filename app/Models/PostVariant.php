<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'platform',
        'connected_social_account_id',
        'text_override',
        'media_override',
        'scheduled_at',
        'status',
    ];

    protected $casts = [
        'media_override' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function connectedSocialAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedSocialAccount::class);
    }

    public function publicationAttempts(): HasMany
    {
        return $this->hasMany(PublicationAttempt::class);
    }

    public function metricsSnapshots(): HasMany
    {
        return $this->hasMany(MetricsSnapshot::class);
    }

    public function isDue(): bool
    {
        return $this->scheduled_at && 
               $this->scheduled_at->isPast() && 
               $this->status === 'scheduled';
    }
}
