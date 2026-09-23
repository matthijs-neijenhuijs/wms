<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Products\Models\StockProduct;

class PurchaseOrderProcessingService
{
    public function __construct(
        private readonly OrderStatusTransitionService $orderStatusTransitionService,
    ) {}

    public function processScanCompletion(PurchaseOrder $purchaseOrder): void
    {
        $productCounts = DB::table('purchase_orders_products')
            ->where('purchase_order_id', $purchaseOrder->id)
            ->where('scanned', true)
            ->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id');

        DB::transaction(function () use ($purchaseOrder, $productCounts): void {
            foreach ($productCounts as $productId => $quantity) {
                $stockProduct = StockProduct::query()
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $stockProduct) {
                    continue;
                }

                $stockProduct->on_stock_quantity += (int) $quantity;
                $stockProduct->save();
            }

            $purchaseOrder->processed = true;
            $purchaseOrder->save();
        });

        $this->orderStatusTransitionService->refreshReservedStockForProductIds(
            $productCounts->keys()->all()
        );
    }

    public function markReceived(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->completed = true;
        $purchaseOrder->save();
    }
}
