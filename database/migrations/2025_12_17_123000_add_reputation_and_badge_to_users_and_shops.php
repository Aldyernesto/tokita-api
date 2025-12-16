<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reputation_points')->default(0)->after('fcm_token');
            $table->string('badge_level')->default('Warga Baru')->after('reputation_points');
        });

        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->integer('reputation_points')->default(0)->after('description');
                $table->string('badge_level')->default('Warga Baru')->after('reputation_points');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reputation_points', 'badge_level']);
        });

        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropColumn(['reputation_points', 'badge_level']);
            });
        }
    }
};
