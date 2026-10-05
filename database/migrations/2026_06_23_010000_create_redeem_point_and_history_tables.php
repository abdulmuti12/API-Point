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
        Schema::create('redeem_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->uuid('point_id');
            $table->bigInteger('total_transaction')->default(0)->comment('Total transaksi customer saat point dihitung');
            $table->integer('total_point_earned')->default(0)->comment('Total point yang pernah didapat');
            $table->integer('total_point_claimed')->default(0)->comment('Total point yang sudah di-claim');
            $table->integer('total_point_active')->default(0)->comment('Sisa point yang masih aktif');
            $table->integer('total_point_closed')->default(0)->comment('Sisa point yang sudah non-aktif');
            $table->timestamps();

            $table->index('customer_id', 'idx_redeem_points_customer_id');
            $table->index('point_id', 'idx_redeem_points_point_id');
            $table->index('customer_id', 'redeem_points_customer_id_point_id_unique');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        Schema::create('redeem_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('redeem_point_id');
            $table->unsignedBigInteger('customer_id');
            $table->uuid('point_id');
            $table->enum('type', ['earn', 'claim'])->comment('earn = penambahan point, claim = penarikan/claim point');
            $table->integer('point_amount')->comment('Jumlah point yang ditambah/dikurangi');
            $table->integer('point_balance')->comment('Saldo point setelah transaksi');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('customer_id', 'idx_redeem_histories_customer_id');
            $table->index('redeem_point_id', 'idx_redeem_histories_redeem_point_id');
            $table->index('status', 'idx_redeem_histories_status');
            $table->index('type', 'idx_redeem_histories_type');
            $table->foreign('redeem_point_id')->references('id')->on('redeem_points')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redeem_histories');
        Schema::dropIfExists('redeem_points');
    }
};