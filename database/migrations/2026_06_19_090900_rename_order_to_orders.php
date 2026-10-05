<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            if (Schema::hasTable('order')) {
                Schema::rename('order', 'orders');
            } else {
                return;
            }
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'order_code') && !Schema::hasColumn('orders', 'order_number')) {
                $table->renameColumn('order_code', 'order_number');
            }
            // if (!Schema::hasColumn('orders', 'order_number')) {
            //     $table->string('order_number', 50)->after('id');
            // }

            if (!Schema::hasColumn('orders', 'recipient_name')) {
                $table->string('recipient_name', 255)->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('orders', 'phone')) {
                $table->string('phone', 20)->nullable()->after('recipient_name');
            }
            if (!Schema::hasColumn('orders', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('orders', 'province_id')) {
                $table->unsignedBigInteger('province_id')->nullable()->after('shipping_address');
            }
            if (!Schema::hasColumn('orders', 'province_name')) {
                $table->string('province_name', 255)->nullable()->after('province_id');
            }
            if (!Schema::hasColumn('orders', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('province_name');
            }
            if (!Schema::hasColumn('orders', 'city_name')) {
                $table->string('city_name', 255)->nullable()->after('city_id');
            }
            if (!Schema::hasColumn('orders', 'district_id')) {
                $table->unsignedBigInteger('district_id')->nullable()->after('city_name');
            }
            if (!Schema::hasColumn('orders', 'district_name')) {
                $table->string('district_name', 255)->nullable()->after('district_id');
            }
            if (!Schema::hasColumn('orders', 'subdistrict_id')) {
                $table->unsignedBigInteger('subdistrict_id')->nullable()->after('district_name');
            }
            if (!Schema::hasColumn('orders', 'subdistrict_name')) {
                $table->string('subdistrict_name', 255)->nullable()->after('subdistrict_id');
            }
            if (!Schema::hasColumn('orders', 'postal_code')) {
                $table->string('postal_code', 10)->nullable()->after('subdistrict_name');
            }
            if (!Schema::hasColumn('orders', 'subtotal')) {
                $table->bigInteger('subtotal')->default(0)->after('postal_code');
            }
            if (!Schema::hasColumn('orders', 'shipping_cost')) {
                $table->bigInteger('shipping_cost')->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('orders', 'grand_total')) {
                $table->bigInteger('grand_total')->default(0)->after('shipping_cost');
            }
            if (!Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }

            foreach (['id_product', 'qty'] as $legacyCol) {
                if (Schema::hasColumn('orders', $legacyCol)) {
                    $table->dropColumn($legacyCol);
                }
            }
        });

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasIndex('orders', 'idx_orders_customer_id')) {
                    $table->index('customer_id', 'idx_orders_customer_id');
                }
                if (Schema::hasColumn('orders', 'order_number') && !Schema::hasIndex('orders', 'idx_orders_order_number')) {
                    $table->index('order_number', 'idx_orders_order_number');
                }
                if (!Schema::hasIndex('orders', 'idx_orders_status')) {
                    $table->index('status', 'idx_orders_status');
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasIndex('orders', 'idx_orders_customer_id')) {
                $table->dropIndex('idx_orders_customer_id');
            }
            if (Schema::hasIndex('orders', 'idx_orders_order_number')) {
                $table->dropIndex('idx_orders_order_number');
            }
            if (Schema::hasIndex('orders', 'idx_orders_status')) {
                $table->dropIndex('idx_orders_status');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'order_number') && !Schema::hasColumn('orders', 'order_code')) {
                $table->renameColumn('order_number', 'order_code');
            } elseif (Schema::hasColumn('orders', 'order_number')) {
                $table->dropColumn('order_number');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach ([
                'recipient_name', 'phone', 'shipping_address',
                'province_id', 'province_name',
                'city_id', 'city_name',
                'district_id', 'district_name',
                'subdistrict_id', 'subdistrict_name',
                'postal_code', 'subtotal', 'shipping_cost',
                'grand_total', 'notes',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        if (Schema::hasTable('orders') && !Schema::hasTable('order')) {
            Schema::rename('orders', 'order');
        }
    }
};
