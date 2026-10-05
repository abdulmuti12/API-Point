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
        Schema::table('banner', function (Blueprint $table) {
            $table->text('file5')->nullable()->after('file4'); // ubah 'file4' sesuai kolom terakhir sebelumnya
            $table->text('file6')->nullable()->after('file5');
            $table->text('file7')->nullable()->after('file6');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner', function (Blueprint $table) {
            $table->dropColumn(['file5', 'file6', 'file7']);
        });
    }
};
