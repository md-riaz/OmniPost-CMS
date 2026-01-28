<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationAttempt extends Model
{
    protected $fillable = [
        'post_variant_id',
        'attempt_no',
        'queued_at',
        'started_at',
        'finished_at',
        'result',
        'external_post_id',
        'idempotency_key',
        'error_code',
        'error_message',
        'raw_response',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function postVariant(): BelongsTo
    {
        return $this->belongsTo(PostVariant::class);
    }

    public function isSuccessful(): bool
    {
        return $this->result === 'success';
    }
}
