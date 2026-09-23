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
        Schema::table('purchase_orders_products', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }
};
