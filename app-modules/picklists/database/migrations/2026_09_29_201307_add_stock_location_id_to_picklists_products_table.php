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
        Schema::table('picklists_products', function (Blueprint $table) {
            $table->foreignId('stock_location_id')->nullable()->after('picklist_id')
                ->constrained('stock_locations')->onUpdate('cascade')->onDelete('set null');
        });
    }
};
