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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'email_verified_at')) {
                $table->dateTime('email_verified_at')->nullable()->after('email');
            }

            if (!Schema::hasColumn('customers', 'provider')) {
                $table->string('provider', 50)->nullable()->after('email_verified_at');
            }

            if (!Schema::hasColumn('customers', 'provider_id')) {
                $table->string('provider_id', 255)->nullable()->after('provider');
            }

            if (!Schema::hasColumn('customers', 'avatar')) {
                $table->text('avatar')->nullable()->after('provider_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach (['email_verified_at', 'provider', 'provider_id', 'avatar'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
