<?php

declare(strict_types=1);

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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('completed')->default(false);
            $table->boolean('processed')->default(false);
            $table->text('comments')->nullable();
            $table->date('expected_delivery_date');
            $table->integer('generated_year_purchase_order_id');
            $table->index('generated_year_purchase_order_id');
            $table->string('generated_custom_purchase_order_id')->nullable();
            $table->index('generated_custom_purchase_order_id');
            $table->timestamps();

            $table->unique(['warehouse_id', 'generated_custom_purchase_order_id'], 'po_wh_gcpoid_uq');
        });

        Schema::create('purchase_orders_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->boolean('show_for_supplier')->default(false);
            $table->string('barcode');
            $table->string('reference_code')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('product_title');
            $table->boolean('scanned')->default(false);
            $table->timestamps();
        });

        Schema::create('purchase_order_failed_products', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->string('barcode')->nullable();
            $table->string('reference_code')->nullable();
            $table->bigInteger('total_quantity_scanned')->default(0);
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('product_title')->nullable();
            $table->timestamps();
        });
    }
};
