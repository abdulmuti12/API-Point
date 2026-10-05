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
        Schema::table('reg_regencies', function (Blueprint $table) {
            $table->string('id', 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('reg_regencies', function (Blueprint $table) {
            $table->string('id', 4)->change(); // Ubah ini sesuai panjang sebelumnya
        });
    }

};
