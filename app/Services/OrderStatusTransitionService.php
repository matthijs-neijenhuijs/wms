<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Picklist;
use App\Models\PicklistProduct;
use App\Models\StockProduct;
use Illuminate\Support\Facades\DB;
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

        if ($newStatus->generate_picklist && ! ($previousStatus?->generate_picklist ?? false)) {
            $this->generatePicklist($order);
        }

        if ($newStatus->reserve_stock && ! ($previousStatus?->reserve_stock ?? false)) {
            $this->reserveStock($order);
        }

        if ($newStatus->reduce_stock && ! ($previousStatus?->reduce_stock ?? false)) {
            $this->reduceStock($order);
        }

        $this->syncOrderFlags($order, $newStatus);

        if ($newStatus->cancelled && ! ($previousStatus?->cancelled ?? false)) {
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
                    'reference_code' => $orderProduct->reference_code,
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
            ->unique();

        foreach ($productIds as $productId) {
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
            'picked' => (bool) ($newStatus?->reduce_stock ?? false),
            'completed' => (bool) ($newStatus?->completed ?? false),
            'on_hold' => (bool) ($newStatus?->on_hold ?? false),
            'delivered' => (bool) ($newStatus?->delivered ?? false),
            'cancelled' => (bool) ($newStatus?->cancelled ?? false),
        ])->saveQuietly();
    }

    protected function calculateReservedQuantity(int $productId): int
    {
        return (int) DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_statuses_id')
            ->where('order_products.product_id', $productId)
            ->where('order_statuses.reserve_stock', true)
            ->where('orders.picked', false)
            ->where('orders.cancelled', false)
            ->sum('order_products.quantity');
    }

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
