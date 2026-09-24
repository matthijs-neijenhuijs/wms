<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Orders\Models\PurchaseOrderStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->string('status')->default(PurchaseOrderStatus::Concept->value)->after('processed');
            $table->date('received_date')->nullable()->after('expected_delivery_date');
        });

        // Concept is a true no-commitment stage that affects nothing else in
        // the system, so a purchase order must be allowed to exist before a
        // delivery date is chosen.
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->date('expected_delivery_date')->nullable()->change();
        });

        // Forward-only backfill from the old completed/processed booleans.
        // Every pre-existing row's `status` column currently holds the
        // 'concept' default applied above; narrow it down in three passes.

        // 1) Anything already flagged completed or processed had its stock
        //    already applied under the old model - 'processed' is the only
        //    status consistent with that already-mutated on_stock_quantity.
        DB::table('purchase_orders')
            ->where('completed', true)
            ->orWhere('processed', true)
            ->update(['status' => PurchaseOrderStatus::Processed->value]);

        // 2) Rows with neither flag true, but every line item scanned (and
        //    at least one line item exists), map to 'scanned' - consistent
        //    with "automatic on full scan", without re-applying stock.
        $fullyScannedIds = DB::table('purchase_orders')
            ->where('completed', false)
            ->where('processed', false)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('purchase_orders_products')
                    ->whereColumn('purchase_orders_products.purchase_order_id', 'purchase_orders.id');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('purchase_orders_products')
                    ->whereColumn('purchase_orders_products.purchase_order_id', 'purchase_orders.id')
                    ->where('scanned', false);
            })
            ->pluck('id');

        DB::table('purchase_orders')
            ->whereIn('id', $fullyScannedIds)
            ->update(['status' => PurchaseOrderStatus::Scanned->value]);

        // 3) Everything else (still 'concept') becomes 'purchased', since
        //    expected_delivery_date was NOT NULL for every pre-existing row.
        DB::table('purchase_orders')
            ->where('status', PurchaseOrderStatus::Concept->value)
            ->update(['status' => PurchaseOrderStatus::Purchased->value]);

        // Drop the superseded flags now that every call site has moved to
        // the status column (single atomic change, no external consumers).
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropColumn(['completed', 'processed']);
        });
    }
};
