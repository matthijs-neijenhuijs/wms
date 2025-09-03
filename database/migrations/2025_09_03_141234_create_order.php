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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->decimal('price_with_tax', 12, 4)->nullable();
            $table->decimal('price_without_tax', 12, 4)->nullable();            
            $table->decimal('total_discount', 12, 4)->nullable();
            $table->integer('generated_year_order_id');
            $table->index('generated_year_order_id');
            $table->string('generated_custom_order_id')->nullable()->unique();
            $table->index('generated_custom_order_id');
            $table->timestamps();
        });

        Schema::create('order_product', function(Blueprint $table) {     
            $table->increments('id');
            $table->foreignId('order_id')->constrained('orders')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreignId('product_id')->nullable()->constrained('products')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->onUpdate('CASCADE')->onDelete('SET NULL');                           
            $table->string('name');
            $table->decimal('tax_rate', 12, 4)->nullable();
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('weight')->nullable(); 
            $table->decimal('price_with_tax', 12, 4)->nullable();  
            $table->decimal('price_without_tax', 12, 4)->nullable(); 
            $table->decimal('total_price_with_tax', 12, 4)->nullable();  
            $table->decimal('total_price_without_tax', 12, 4)->nullable(); 
            $table->string('reference_code')->nullable();
            $table->string('product_attribute_title')->nullable();
            $table->timestamps();  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};
