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
        return $this->likes + $this->comments + $this->shares;
    }
}
