<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;

    protected $appends = [
        'badge_url',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'city',
        'image_url',
        'description',
        'reputation_points',
        'badge_level',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getBadgeUrlAttribute(): string
    {
        $map = [
            'Warga Baru' => 'bronze',
            'Pedagang' => 'silver',
            'Juragan' => 'gold',
            'Sultan' => 'diamond',
        ];

        $slug = $map[$this->badge_level] ?? 'bronze';

        return asset("badges/{$slug}.png");
    }

    public function addReputation(int $amount): void
    {
        $this->reputation_points = ($this->reputation_points ?? 0) + $amount;
        $this->badge_level = $this->determineBadgeLevel($this->reputation_points);
        $this->save();
    }

    private function determineBadgeLevel(int $points): string
    {
        if ($points >= 1000) {
            return 'Sultan';
        }

        if ($points >= 501) {
            return 'Juragan';
        }

        if ($points >= 101) {
            return 'Pedagang';
        }

        return 'Warga Baru';
    }
}
