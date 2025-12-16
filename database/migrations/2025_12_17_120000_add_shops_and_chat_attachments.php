<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city')->nullable();
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique('user_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_id')->nullable()->after('seller_id');

            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->nullOnDelete();
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->enum('attachment_type', ['text', 'product', 'image'])
                ->default('text')
                ->after('payload');
            $table->unsignedBigInteger('attachment_id')->nullable()->after('attachment_type');

            $table->foreign('attachment_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['attachment_id']);
            $table->dropColumn(['attachment_type', 'attachment_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });

        Schema::dropIfExists('shops');
    }
};
