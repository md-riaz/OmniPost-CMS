<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $fillable = [
        'brand_id',
        'created_by',
        'status',
        'title',
        'base_text',
        'base_media',
        'target_url',
        'utm_template',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'base_media' => 'array',
        'approved_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(PostVariant::class);
    }
}
