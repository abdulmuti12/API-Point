<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redeem_histories', function (Blueprint $table) {
            $table->bigInteger('transaction_amount')->nullable()->after('point_amount')->comment('Nominal transaksi pada event earn ini');
        });

        // Backfill from description text for existing rows
        DB::statement("
            UPDATE redeem_histories
            SET transaction_amount = CAST(REGEXP_REPLACE(SUBSTRING(description, 'total transaksi ([0-9]+)'), '[^0-9]', '') AS UNSIGNED)
            WHERE type = 'earn' AND transaction_amount IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('redeem_histories', function (Blueprint $table) {
            $table->dropColumn('transaction_amount');
        });
    }
};