<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_postal_code', function (Blueprint $table) {
            $table->integer('postal_id');
            $table->integer('subdis_id');
            $table->integer('dis_id');
            $table->integer('city_id');
            $table->integer('prov_id');
            $table->integer('postal_code');
            // $table->foreign('subdis_id')->references('subdis_id')->on('master_subdistrict')->onDelete('cascade');
            // $table->foreign('dis_id')->references('dis_id')->on('master_district')->onDelete('cascade');
            // $table->foreign('city_id')->references('city_id')->on('master_city')->onDelete('cascade');
            // $table->foreign('prov_id')->references('prov_id')->on('master_province')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_postal_code');
    }
};