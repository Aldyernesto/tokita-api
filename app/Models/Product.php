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
        $path = $this->image_path ? ltrim($this->image_path, '/\\') : null;

        if (! $path) {
            return null;
        }

        $disk = config('filesystems.default') ?? env('FILESYSTEM_DISK');

        if ($disk === 'r2') {
            $base = rtrim((string) env('R2_CDN_URL'), '/');

            return $base ? "{$base}/{$path}" : null;
        }

        if ($disk === 'public') {
            return asset("storage/{$path}");
        }

        return null;
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
