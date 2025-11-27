<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Category extends Model
{
    use HasFactory;

    protected $appends = ['image'];

    public function getImageAttribute(): ?string
    {
        return $this->formatImageUrl($this->attributes['image_url'] ?? null);
    }

    public function getImageUrlAttribute($value): ?string
    {
        return $this->formatImageUrl($value);
    }

    private function formatImageUrl(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return URL::to($value);
    }
}
