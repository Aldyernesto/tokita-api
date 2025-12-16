<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar_url',
        'google_id',
        'fcm_token',
        'reputation_points',
        'badge_level',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'badge_url',
    ];

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class);
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
