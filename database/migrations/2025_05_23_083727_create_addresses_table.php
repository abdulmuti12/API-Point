<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id(); // bigint primary key
            $table->unsignedBigInteger('user_id'); // foreign key ke users
            $table->string('phone');      
            $table->string('province');  
            $table->string('city');      
            $table->string('district');   
            $table->string('postal_code'); 
            $table->text('address_line');  
            $table->text('address_detail')->nullable(); 
            $table->timestamps(); // 

            // Foreign key constraint (opsional jika ingin strict)
            //$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
