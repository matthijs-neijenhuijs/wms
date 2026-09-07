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
        Schema::create('dynamic_ai_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('SET NULL')->onDelete('SET NULL');
            $table->string('title');
            $table->text('question');
            $table->string('chart_type', 50);
            $table->string('selected_model', 100);
            $table->string('metric_key', 100);
            $table->text('query_sql');
            $table->json('query_bindings')->nullable();
            $table->json('chart_payload')->nullable();
            $table->integer('score')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'score']);
            $table->index(['warehouse_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_ai_charts');
    }
};
