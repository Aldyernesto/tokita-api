<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('village_id')->after('phone_number');
            $table->string('street_name')->after('village_id');
            $table->string('rt', 10)->after('street_name');
            $table->string('rw', 10)->after('rt');
            $table->decimal('latitude', 15, 12)->nullable()->after('rw');
            $table->decimal('longitude', 15, 12)->nullable()->after('latitude');

            $table->index('village_id', 'addresses_village_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex('addresses_village_id_index');

            $table->dropColumn([
                'village_id',
                'street_name',
                'rt',
                'rw',
                'latitude',
                'longitude',
            ]);
        });
    }
};
