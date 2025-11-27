<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')
            ->whereNotNull('image_url')
            ->where('image_url', 'like', 'images/%')
            ->update([
                'image_url' => DB::raw("SUBSTRING(image_url, LENGTH('images/') + 1)"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')
            ->whereNotNull('image_url')
            ->where('image_url', 'not like', 'images/%')
            ->update([
                'image_url' => DB::raw("CONCAT('images/', image_url)"),
            ]);
    }
};
