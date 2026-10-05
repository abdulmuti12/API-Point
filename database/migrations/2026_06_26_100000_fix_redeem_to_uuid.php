<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop existing redeem tables
        Schema::dropIfExists('redeem_histories');
        Schema::dropIfExists('redeem_points');

        // Create redeem_points with UUID
        Schema::create('redeem_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('point_id')->constrained('points')->onDelete('cascade');
            $table->bigInteger('total_transaction')->default(0);
            $table->integer('total_point_earned')->default(0);
            $table->integer('total_point_claimed')->default(0);
            $table->integer('total_point_active')->default(0);
            $table->integer('total_point_closed')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'point_id'], 'customer_point_idx');
        });

        // Create redeem_histories with UUID
        Schema::create('redeem_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('redeem_point_id')->constrained('redeem_points')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('point_id')->constrained('points')->onDelete('cascade');
            $table->enum('type', ['earn', 'claim']);
            $table->integer('point_amount');
            $table->integer('point_balance');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'redeem_point_id', 'point_id', 'status', 'type'], 'redeem_history_indexes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('redeem_histories');
        Schema::dropIfExists('redeem_points');

        Schema::create('redeem_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('point_id')->constrained('points')->onDelete('cascade');
            $table->bigInteger('total_transaction')->default(0);
            $table->integer('total_point_earned')->default(0);
            $table->integer('total_point_claimed')->default(0);
            $table->integer('total_point_active')->default(0);
            $table->integer('total_point_closed')->default(0);
            $table->timestamps();
        });

        Schema::create('redeem_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('redeem_point_id')->constrained('redeem_points')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('point_id')->constrained('points')->onDelete('cascade');
            $table->enum('type', ['earn', 'claim']);
            $table->integer('point_amount');
            $table->integer('point_balance');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
};
