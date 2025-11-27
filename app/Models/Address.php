<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'village_id',
        'street_name',
        'rt',
        'rw',
        'latitude',
        'longitude',
        'user_id',
        'label',
        'recipient_name',
        'phone_number',
        'address_line',
        'city',
        'province',
        'postal_code',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
