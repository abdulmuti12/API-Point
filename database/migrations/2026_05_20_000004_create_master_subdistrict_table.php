<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_subdistrict', function (Blueprint $table) {
            $table->integer('subdis_id');
            $table->string('subdis_name', 512);
            $table->integer('dis_id');
            // $table->foreign('dis_id')->references('dis_id')->on('master_district')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_subdistrict');
    }
};