<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricsSnapshot extends Model
{
    protected $fillable = [
        'post_variant_id',
        'captured_at',
        'likes',
        'comments',
        'shares',
        'impressions',
        'clicks',
        'raw_metrics',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'raw_metrics' => 'array',
    ];

    public function postVariant(): BelongsTo
    {
        return $this->belongsTo(PostVariant::class);
    }

    public function getTotalEngagement(): int
    {
        return ($this->likes ?? 0) + ($this->comments ?? 0) + ($this->shares ?? 0);
    }

    public function getEngagementRateAttribute(): ?float
    {
        if (!$this->impressions || $this->impressions == 0) {
            return null;
        }
        $engagement = $this->getTotalEngagement();
        return round(($engagement / $this->impressions) * 100, 2);
    }

    public function getClickThroughRateAttribute(): ?float
    {
        if (!$this->impressions || $this->impressions == 0) {
            return null;
        }
        return round((($this->clicks ?? 0) / $this->impressions) * 100, 2);
    }
}
