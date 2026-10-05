<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: gift_claims
     * Tracking approval flow untuk customer claim hadiah:
     *   - waiting:     baru di-request, belum di-approve admin
     *   - approved:    disetujui admin, point sudah dipotong (status=closed)
     *   - rejected:    ditolak admin, point customer tetap aman
     *   - completed:   hadiah sudah diterima customer (optional, manual update admin)
     */
    public function up(): void
    {
        Schema::create('gift_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('gift_id');
            $table->uuid('redeem_point_id')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->bigInteger('required_point');           // snapshot harga gift saat request
            $table->bigInteger('customer_point_at_request')->nullable(); // snapshot point customer saat request

            $table->string('status', 20)->default('waiting'); // waiting | approved | rejected | completed
            $table->text('notes')->nullable();               // optional notes by customer (alamat pengiriman / catatan)
            $table->text('admin_note')->nullable();          // alasan approve/reject by admin
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('gift_id')->references('id')->on('gifts')->onDelete('restrict');
            $table->foreign('redeem_point_id')->references('id')->on('redeem_points')->onDelete('set null');

            $table->index('status', 'idx_gc_status');
            $table->index('customer_id', 'idx_gc_customer');
            $table->index('gift_id', 'idx_gc_gift');
            $table->index(['customer_id', 'status'], 'idx_gc_customer_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_claims');
    }
};
