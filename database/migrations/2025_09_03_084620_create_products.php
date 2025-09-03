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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false);
            $table->string('reference_code');
            $table->string('ean')->unique();
            $table->string('name')->unique();
            $table->text('description');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
