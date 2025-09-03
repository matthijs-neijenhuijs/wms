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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('user_warehouses', function (Blueprint $table){
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('SET NULL')->onDelete('CASCADE');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onUpdate('SET NULL')->onDelete('CASCADE');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
