<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Picklists\Models\Picklist;
use Modules\Picklists\Models\PicklistProduct;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Spatie\Activitylog\Models\Activity;

class OrderStatusTransitionService
{
    public function process(Order $order, ?OrderStatus $previousStatus, ?OrderStatus $newStatus): void
    {
        if (! $newStatus) {
            $this->syncOrderFlags($order, null);

            return;
        }

        $this->logWorkflowStep(
            $order,
            'status_changed',
            "Order status changed to {$newStatus->name}",
            [
                'previous_status' => $previousStatus?->name,
                'new_status' => $newStatus->name,
                'status_id' => $newStatus->getKey(),
            ],
        );
        if ($newStatus->generate_picklist && ! ($previousStatus && $previousStatus->generate_picklist)) {
            $this->generatePicklist($order);
        }

        if ($newStatus->reserve_stock && ! ($previousStatus && $previousStatus->reserve_stock)) {
            $this->reserveStock($order);
        }

        if ($newStatus->reduce_stock && ! ($previousStatus && $previousStatus->reduce_stock)) {
            $this->reduceStock($order);
        }

        $this->syncOrderFlags($order, $newStatus);

        if ($newStatus->cancelled && ! ($previousStatus && $previousStatus->cancelled)) {
            $this->releaseReservedStock($order);
        }
    }

    public function generatePicklist(Order $order): void
    {
        $order->loadMissing('products');

        $existingPicklist = Picklist::query()
            ->where('order_id', $order->id)
            ->first();

        if ($existingPicklist) {
            return;
        }

        $picklist = Picklist::query()->create([
            'warehouse_id' => $order->warehouse_id,
            'order_id' => $order->id,
        ]);

        $this->logWorkflowStep($order, 'picklist_generated', 'Picklist generated for order', [
            'picklist_id' => $picklist->getKey(),
        ]);

        foreach ($order->products as $orderProduct) {
            $quantity = max((int) ($orderProduct->quantity ?? 0), 0);

            for ($index = 0; $index < $quantity; $index++) {
                PicklistProduct::query()->create([
                    'picklist_id' => $picklist->id,
                    'barcode' => (string) ($orderProduct->barcode ?? ''),
                    'reference_code' => (string) ($orderProduct->reference_code ?? ''),
                    'product_title' => (string) ($orderProduct->name ?? ''),
                    'show_for_supplier' => false,
                    'scanned' => false,
                ]);
            }
        }
    }

    public function reserveStock(Order $order): void
    {
        $this->refreshReservedStock($order);

        $this->logWorkflowStep($order, 'stock_reserved', 'Stock reserved for order');
    }

    public function reduceStock(Order $order): void
    {
        if ($order->picked) {
            return;
        }

        $this->adjustStockLevels($order, function (StockProduct $stockProduct, int $quantity): void {
            $stockProduct->on_stock_quantity = max(0, $stockProduct->on_stock_quantity - $quantity);
            $stockProduct->reserved_on_picklists = max(0, $stockProduct->reserved_on_picklists - $quantity);
            $this->recalculateFreeStock($stockProduct);
        });

        $order->forceFill([
            'picked' => true,
        ])->saveQuietly();

        $this->refreshReservedStock($order);

        $this->logWorkflowStep($order, 'stock_reduced', 'Stock reduced for order');
    }

    public function releaseReservedStock(Order $order): void
    {
        $this->refreshReservedStock($order);

        $this->logWorkflowStep($order, 'stock_released', 'Reserved stock released for cancelled order');
    }

    protected function adjustStockLevels(Order $order, callable $callback): void
    {
        $order->loadMissing('products.product.stockProduct');

        foreach ($order->products as $orderProduct) {
            /** @var OrderProduct $orderProduct */
            /** @var Product|null $product */
            $product = $orderProduct->product;

            if (! $product || ! $product->stockProduct) {
                continue;
            }

            $quantity = max((int) ($orderProduct->quantity ?? 0), 0);

            if ($quantity === 0) {
                continue;
            }

            DB::transaction(function () use ($product, $quantity, $callback): void {
                $stockProduct = StockProduct::query()
                    ->whereKey($product->stockProduct->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $stockProduct) {
                    return;
                }

                $callback($stockProduct, $quantity);

                $stockProduct->save();
            });
        }
    }

    protected function refreshReservedStock(Order $order): void
    {
        $order->loadMissing('products.product.stockProduct');

        $productIds = $order->products
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->all();

        $this->refreshReservedStockForProductIds($productIds);
    }

    /**
     * Recalculate reserved/free stock for the given products, e.g. after a
     * purchase order is imported or fully scanned (no Order context available
     * in that case, unlike refreshReservedStock() above).
     *
     * @param  array<int, int|string>  $productIds
     */
    public function refreshReservedStockForProductIds(array $productIds): void
    {
        foreach (array_unique(array_filter($productIds)) as $productId) {
            DB::transaction(function () use ($productId): void {
                $stockProduct = StockProduct::query()
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $stockProduct) {
                    return;
                }

                $stockProduct->reserved_quantity = $this->calculateReservedQuantity((int) $productId);
                $this->recalculateFreeStock($stockProduct);
                $stockProduct->save();
            });
        }
    }

    protected function syncOrderFlags(Order $order, ?OrderStatus $newStatus): void
    {
        $order->forceFill([
            'picked' => $newStatus !== null && (bool) $newStatus->reduce_stock,
            'completed' => $newStatus !== null && (bool) $newStatus->completed,
            'on_hold' => $newStatus !== null && (bool) $newStatus->on_hold,
            'delivered' => $newStatus !== null && (bool) $newStatus->delivered,
            'cancelled' => $newStatus !== null && (bool) $newStatus->cancelled,
        ])->saveQuietly();
    }

    /**
     * Reserved quantity for a product, deferring demand that is covered by an
     * incoming (not-yet-processed) purchase order due to arrive before the
     * order's own delivery date - per the domain rule that a client order's
     * stock does not need to be reserved until the purchase order it depends
     * on arrives. Orders are served earliest-delivery-date-first; demand not
     * covered by current stock nor a timely incoming batch is still reserved,
     * which keeps this a no-op versus a plain SUM() for products with no
     * purchase orders at all.
     */
    protected function calculateReservedQuantity(int $productId): int
    {
        $orderLines = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_statuses_id')
            ->where('order_products.product_id', $productId)
            ->where('order_statuses.reserve_stock', true)
            ->where('orders.picked', false)
            ->where('orders.cancelled', false)
            ->orderBy('orders.delivery_date')
            ->get(['order_products.quantity', 'orders.delivery_date']);

        $remainingStock = (int) (StockProduct::where('product_id', $productId)->value('on_stock_quantity') ?? 0);

        $incomingBatches = DB::table('purchase_orders_products')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_orders_products.purchase_order_id')
            ->where('purchase_orders_products.product_id', $productId)
            ->where('purchase_orders.processed', false)
            ->select('purchase_orders.expected_delivery_date')
            ->get()
            ->groupBy('expected_delivery_date')
            ->map(fn ($rows) => $rows->count())
            ->sortKeys()
            ->map(fn (int $qty, string $date): object => (object) [
                'expected_delivery_date' => $date,
                'remaining' => $qty,
            ])
            ->values();

        $reserved = 0;

        foreach ($orderLines as $line) {
            $quantity = (int) $line->quantity;

            $fromStock = min($quantity, $remainingStock);
            $remainingStock -= $fromStock;
            $reserved += $fromStock;

            $needed = $quantity - $fromStock;

            if ($needed > 0) {
                foreach ($incomingBatches as $batch) {
                    if ($needed <= 0) {
                        break;
                    }

                    if ($batch->remaining <= 0 || $batch->expected_delivery_date > $line->delivery_date) {
                        continue;
                    }

                    $take = min($needed, $batch->remaining);
                    $batch->remaining -= $take;
                    $needed -= $take;
                }
            }

            $reserved += $needed;
        }

        return $reserved;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected function logWorkflowStep(Order $order, string $event, string $description, array $properties = []): void
    {
        $alreadyLogged = Activity::query()
            ->where('log_name', 'order_workflow')
            ->where('event', $event)
            ->where('description', $description)
            ->where('subject_type', $order->getMorphClass())
            ->where('subject_id', $order->getKey())
            ->exists();

        if ($alreadyLogged) {
            return;
        }

        activity('order_workflow')
            ->performedOn($order)
            ->event($event)
            ->withProperties($properties)
            ->log($description);
    }

    protected function recalculateFreeStock(StockProduct $stockProduct): void
    {
        $stockProduct->free_on_stock_quantity = max(
            0,
            $stockProduct->on_stock_quantity
                - $stockProduct->reserved_quantity
                - $stockProduct->reserved_on_picklists,
        );
    }
}
