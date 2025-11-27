<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    use HasFactory;

    /**
     * Mapping ke tabel referensi wilayah bawaan projek.
     */
    protected $table = 'wilayah_level_1_2';

    public $timestamps = false;
}
