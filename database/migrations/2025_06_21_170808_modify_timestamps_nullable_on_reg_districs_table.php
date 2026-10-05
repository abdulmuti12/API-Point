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
        Schema::table('reg_districs', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->default(null)->change();
            $table->timestamp('updated_at')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('reg_districs', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable(false)->default(DB::raw('CURRENT_TIMESTAMP'))->change();
            $table->timestamp('updated_at')->nullable(false)->default(DB::raw('CURRENT_TIMESTAMP'))->change();
        });
    }

};
