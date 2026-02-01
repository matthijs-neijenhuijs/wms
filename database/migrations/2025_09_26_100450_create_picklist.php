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
        Schema::create('picklists', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('order_id')->constrained('orders')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->boolean('completed')->default(false);
            $table->boolean('back_order')->default(false);
            $table->text('comments')->nullable();
            $table->integer('generated_year_picklist_id');
            $table->index('generated_year_picklist_id');
            $table->string('generated_custom_picklist_id')->nullable()->unique();
            $table->index('generated_custom_picklist_id');
            $table->timestamps();
        });

        Schema::create('picklists_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('picklist_id')->unsigned();
            $table->foreign('picklist_id')->references('id')->on('picklists')->onDelete('cascade');
            $table->boolean('show_for_supplier')->default(false);
            $table->string('ean_code');
            $table->string('reference_code')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('product_title');
            $table->boolean('scanned')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picklist');
    }
};
