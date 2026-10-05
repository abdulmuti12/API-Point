<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->nullable()->after('phone');
            $table->string('province_name')->nullable()->after('province_id');
            $table->unsignedBigInteger('city_id')->nullable()->after('province_name');
            $table->string('city_name')->nullable()->after('city_id');
            $table->unsignedBigInteger('district_id')->nullable()->after('city_name');
            $table->string('district_name')->nullable()->after('district_id');
            $table->unsignedBigInteger('subdistrict_id')->nullable()->after('district_name');
            $table->string('subdistrict_name')->nullable()->after('subdistrict_id');
            $table->boolean('is_default')->default(false)->after('address_line');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'province_id',
                'province_name',
                'city_id',
                'city_name',
                'district_id',
                'district_name',
                'subdistrict_id',
                'subdistrict_name',
                'is_default',
            ]);
        });
    }
};