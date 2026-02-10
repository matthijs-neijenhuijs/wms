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

        Schema::create('orders_status', function(Blueprint $table) {     
            $table->increments('id');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->string('title');
            $table->string('color');
            $table->boolean('is_validated')->default(false);
            $table->boolean('is_paid')->default(false);              
            $table->boolean('is_delivered')->default(false);  
            $table->integer('modified_by_user_id')->unsigned()->nullable();
            $table->foreign('modified_by_user_id')->references('id')->on('user')->onDelete('set null');
            $table->timestamps();
            $table->unique(array('title','warehouse_id'), 'unique_order_status_title'); 
        });



        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orders_status_id')->nullable()->constrained('orders_status')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->enum('status', ['concept', 'confirmed', 'shipped', 'delivered', 'cancelled'])->default('concept');
            $table->integer('generated_year_order_id');
            $table->index('generated_year_order_id');
            $table->string('generated_custom_order_id')->nullable()->unique();
            $table->index('generated_custom_order_id');
            $table->decimal('total_price', 12, 4)->nullable();
            $table->decimal('discount', 12, 4)->nullable();
            $table->string('custom_order_id')->nullable();
            $table->string('invoice_initials')->nullable();
            $table->string('invoice_name')->nullable();
            $table->string('invoice_street')->nullable();
            $table->bigInteger('invoice_housenumber')->nullable();
            $table->string('invoice_housenumber_suffix')->nullable();
            $table->string('invoice_zipcode')->nullable();
            $table->string('invoice_city')->nullable();
            $table->string('invoice_country')->nullable();
            $table->string('delivery_initials')->nullable();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_street')->nullable();
            $table->bigInteger('delivery_housenumber')->nullable();
            $table->string('delivery_housenumber_suffix')->nullable();
            $table->string('delivery_zipcode')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('customer_remarks')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'custom_order_id']);
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('order_id')->constrained('orders')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreignId('product_id')->nullable()->constrained('products')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreignId('vat_rate_id')->nullable()->constrained('vat_rates')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->string('name');
            $table->bigInteger('quantity')->nullable();

            $table->bigInteger('weight')->nullable();
            $table->decimal('price', 12, 4)->nullable();
            $table->decimal('vat_rate', 12, 4)->nullable();
            $table->string('barcode')->nullable();
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
