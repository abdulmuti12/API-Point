<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_province', function (Blueprint $table) {
            $table->integer('prov_id');
            $table->string('prov_name', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_province');
    }
};