<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];

    public function getImagePathAttribute(): ?string
    {
        return $this->attributes['image_url'] ?? null;
    }

    public function setImagePathAttribute(?string $value): void
    {
        $this->attributes['image_url'] = $value ? ltrim($value, '/\\') : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $base = rtrim(env('R2_CDN_URL'), '/');
        $path = ltrim($this->image_path ?? '', '/');

        return $path ? "{$base}/{$path}" : null;
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
