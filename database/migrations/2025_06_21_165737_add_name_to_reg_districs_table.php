<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reg_districs', function (Blueprint $table) {
            $table->string('name', 50)->after('regency_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reg_districs', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

};
