<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel pivot untuk items dalam 1 order (1 order = banyak produk).
     * Menyimpan snapshot nama/harga/gambar produk saat order dibuat,
     * sehingga perubahan produk di kemudian hari tidak mempengaruhi histori order.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name', 255);
            $table->string('variant_name', 255)->nullable();
            $table->integer('quantity');
            $table->bigInteger('price');
            $table->string('image', 500)->nullable();
            $table->timestamps();

            // Foreign key ke orders (hapus order -> hapus items juga)
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');

            // Index untuk performa
            $table->index('order_id', 'idx_order_items_order_id');
            $table->index('product_id', 'idx_order_items_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
