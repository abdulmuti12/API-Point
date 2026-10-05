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
            $table->text('note2')->nullable();
            $table->text('note3')->nullable();
            $table->text('note4')->nullable();
            $table->text('note5')->nullable();
            $table->string('status', 15)->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner', function (Blueprint $table) {
            $table->dropColumn(['note2', 'note3', 'note4', 'note5', 'status']);
        });
    }
};
