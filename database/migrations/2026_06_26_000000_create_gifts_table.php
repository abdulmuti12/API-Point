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
        Schema::create('gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50);
            $table->bigInteger('total_point');
            $table->text('image')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 60)->default('active');
            $table->timestamps();

            $table->index('status', 'idx_gifts_status');
            $table->index('name', 'idx_gifts_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
};