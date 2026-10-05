<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend order_items untuk support input transaksi manual:
     *   - `name`           : nama item yang diinput admin (free text)
     *   - `category_id`    : relasi ke tabel category
     *   - `brand_id`       : relasi ke tabel brands
     *
     * product_name tetap dipakai untuk backward compatibility dengan
     * order yang dibuat dari flow customer (checkout). Untuk order manual
     * kolom `name` yang akan digunakan.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->after('product_name');
            $table->unsignedBigInteger('category_id')->nullable()->after('name');
            $table->unsignedBigInteger('brand_id')->nullable()->after('category_id');

            $table->foreign('category_id')
                ->references('id')->on('category')
                ->onDelete('set null');

            $table->foreign('brand_id')
                ->references('id')->on('brands')
                ->onDelete('set null');

            $table->index('category_id', 'idx_order_items_category_id');
            $table->index('brand_id', 'idx_order_items_brand_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['brand_id']);
            $table->dropIndex('idx_order_items_category_id');
            $table->dropIndex('idx_order_items_brand_id');
            $table->dropColumn(['name', 'category_id', 'brand_id']);
        });
    }
};