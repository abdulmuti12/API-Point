<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_district', function (Blueprint $table) {
            $table->integer('dis_id');
            $table->string('dis_name', 255);
            $table->integer('city_id');
            // $table->foreign('city_id')->references('city_id')->on('master_city')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_district');
    }
};