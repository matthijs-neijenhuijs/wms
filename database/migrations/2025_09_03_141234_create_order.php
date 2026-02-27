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

        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->string('name');
            $table->string('color');
            $table->boolean('generate_picklist')->default(false);
            $table->boolean('reserve_stock')->default(false);
            $table->boolean('concepted')->default(false);
            $table->boolean('completed')->default(false);
            $table->boolean('paused')->default(false);
            $table->boolean('delivered')->default(false);
            $table->boolean('cancelled')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreignId('order_statuses_id')->nullable()->constrained('order_statuses')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->integer('generated_year_order_id');
            $table->index('generated_year_order_id');
            $table->string('generated_custom_order_id')->nullable()->unique();
            $table->index('generated_custom_order_id');
            $table->decimal('discount', 12, 4)->nullable();
            $table->string('custom_order_id')->nullable();
            $table->string('invoice_name')->nullable();
            $table->string('invoice_address')->nullable();
            $table->string('invoice_zipcode')->nullable();
            $table->string('invoice_region')->nullable();
            $table->string('invoice_city')->nullable();
            $table->string('invoice_country')->nullable();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('delivery_zipcode')->nullable();
            $table->string('delivery_region')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_country')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('comments')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'custom_order_id']);
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
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
