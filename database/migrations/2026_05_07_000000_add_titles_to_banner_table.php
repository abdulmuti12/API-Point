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
        if (!Schema::hasColumn('banner', 'title')) {
            Schema::table('banner', function (Blueprint $table) {
                $table->string('title', 100)->nullable()->after('status');
            });
        }
        if (!Schema::hasColumn('banner', 'title2')) {
            Schema::table('banner', function (Blueprint $table) {
                $table->string('title2', 100)->nullable()->after('title');
            });
        }
        if (!Schema::hasColumn('banner', 'title3')) {
            Schema::table('banner', function (Blueprint $table) {
                $table->string('title3', 100)->nullable()->after('title2');
            });
        }
        if (!Schema::hasColumn('banner', 'title4')) {
            Schema::table('banner', function (Blueprint $table) {
                $table->string('title4', 100)->nullable()->after('title3');
            });
        }
        if (!Schema::hasColumn('banner', 'title5')) {
            Schema::table('banner', function (Blueprint $table) {
                $table->string('title5', 100)->nullable()->after('title4');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('banner', 'title') ? 'title' : null,
            Schema::hasColumn('banner', 'title2') ? 'title2' : null,
            Schema::hasColumn('banner', 'title3') ? 'title3' : null,
            Schema::hasColumn('banner', 'title4') ? 'title4' : null,
            Schema::hasColumn('banner', 'title5') ? 'title5' : null,
        ]));

        if (!empty($columnsToDrop)) {
            Schema::table('banner', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
