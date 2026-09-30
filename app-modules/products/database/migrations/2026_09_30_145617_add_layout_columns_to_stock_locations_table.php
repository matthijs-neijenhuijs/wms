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
        Schema::table('stock_locations', function (Blueprint $table) {
            $table->decimal('x', 8, 2)->nullable()->after('rank');
            $table->decimal('y', 8, 2)->nullable()->after('x');
            $table->decimal('width', 8, 2)->nullable()->after('y');
            $table->decimal('height', 8, 2)->nullable()->after('width');
        });
    }
};
