<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'department',
        'category',
        'name',
        'objective',
        'budget',
        'kpi_target',
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
