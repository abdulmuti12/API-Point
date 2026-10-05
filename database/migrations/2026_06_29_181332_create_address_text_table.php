<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->text('address');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('customer_id', 'idx_address_text_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_text');
    }
};
