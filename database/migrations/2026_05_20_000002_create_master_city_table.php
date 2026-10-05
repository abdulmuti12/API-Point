<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_city', function (Blueprint $table) {
            $table->integer('city_id');
            $table->string('city_name', 255);
            $table->integer('prov_id');
            // $table->foreign('prov_id')->references('prov_id')->on('master_province')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_city');
    }
};