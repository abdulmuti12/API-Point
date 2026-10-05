<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // ============================================================
        // STEP 1: Backup existing customer data
        // ============================================================
        $customers = DB::table('customers')->get()->map(fn($c) => (array) $c)->toArray();

        // ============================================================
        // STEP 2: Drop dependent tables
        //   - redeem_histories (FK -> redeem_points, customers)
        //   - redeem_points (FK -> customers, points)
        //   - orders (FK -> customers, no constraint but column referenced)
        // ============================================================
        Schema::dropIfExists('redeem_histories');
        Schema::dropIfExists('redeem_points');
        Schema::dropIfExists('orders');

        // ============================================================
        // STEP 3: Convert customers.id BIGINT -> UUID (CHAR(36))
        // ============================================================

        // 3a. Drop the old bigint id column
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        // 3b. Add new uuid id column
        Schema::table('customers', function (Blueprint $table) {
            $table->uuid('id')->first();
        });

        // 3c. Populate UUID for each existing customer
        foreach ($customers as &$customer) {
            $customer['id'] = (string) \Illuminate\Support\Str::uuid();
        }
        unset($customer);

        // 3d. Re-insert the customers with new UUIDs
        if (!empty($customers)) {
            DB::table('customers')->truncate();
            // Insert in chunks
            foreach (array_chunk($customers, 100) as $chunk) {
                DB::table('customers')->insert($chunk);
            }
        }

        // 3e. Make id the PK
        Schema::table('customers', function (Blueprint $table) {
            $table->primary('id');
        });

        // ============================================================
        // STEP 4: Re-create orders with UUID customer_id
        // ============================================================
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50);
            $table->string('status', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('restrict');
            $table->string('recipient_name', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('shipping_address')->nullable();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->string('province_name', 255)->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('city_name', 255)->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->string('district_name', 255)->nullable();
            $table->unsignedBigInteger('subdistrict_id')->nullable();
            $table->string('subdistrict_name', 255)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('shipping_cost')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->timestamps();

            $table->index('order_number', 'idx_orders_order_number');
            $table->index('status', 'idx_orders_status');
        });

        // ============================================================
        // STEP 5: Re-create redeem tables with UUID customer_id
        // ============================================================
        Schema::create('redeem_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('point_id')->constrained('points')->onDelete('cascade');
            $table->bigInteger('total_transaction')->default(0);
            $table->integer('total_point_earned')->default(0);
            $table->integer('total_point_claimed')->default(0);
            $table->integer('total_point_active')->default(0);
            $table->integer('total_point_closed')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'point_id'], 'customer_point_idx');
        });

        Schema::create('redeem_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('redeem_point_id')->constrained('redeem_points')->onDelete('cascade');
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('point_id')->constrained('points')->onDelete('cascade');
            $table->enum('type', ['earn', 'claim']);
            $table->integer('point_amount');
            $table->integer('point_balance');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'redeem_point_id', 'point_id', 'status', 'type'], 'redeem_history_indexes');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('redeem_histories');
        Schema::dropIfExists('redeem_points');
        Schema::dropIfExists('orders');

        // Convert customers.id back to BIGINT
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->id()->first();
        });

        // Re-create orders with bigint customer_id
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50);
            $table->string('status', 255)->nullable();
            $table->text('notes')->nullable();
            $table->integer('customer_id');
            $table->string('recipient_name', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('shipping_address')->nullable();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->string('province_name', 255)->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('city_name', 255)->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->string('district_name', 255)->nullable();
            $table->unsignedBigInteger('subdistrict_id')->nullable();
            $table->string('subdistrict_name', 255)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('shipping_cost')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->timestamps();

            $table->index('customer_id', 'idx_orders_customer_id');
            $table->index('order_number', 'idx_orders_order_number');
            $table->index('status', 'idx_orders_status');
        });

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
        });

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
        });

        Schema::enableForeignKeyConstraints();
    }
};