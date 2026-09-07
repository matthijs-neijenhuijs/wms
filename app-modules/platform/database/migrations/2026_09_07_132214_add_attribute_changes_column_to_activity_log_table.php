<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection(config('activitylog.database_connection'))->hasColumn(config('activitylog.table_name'), 'attribute_changes')) {
            return;
        }

        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->json('attribute_changes')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::connection(config('activitylog.database_connection'))->hasColumn(config('activitylog.table_name'), 'attribute_changes')) {
            return;
        }

        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropColumn('attribute_changes');
        });
    }
};
