<?php

declare(strict_types=1);

namespace Modules\Orders\Observers;

use App\Services\OrderStatusTransitionService;
use Modules\Orders\Exceptions\OrderProductsLockedException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;

class OrderProductObserver
{
    public function __construct(private readonly OrderStatusTransitionService $transitionService) {}

    /**
     * Handle the OrderProduct "creating" event.
     */
    public function creating(OrderProduct $orderProduct): void
    {
        $this->guardCanModifyProducts($orderProduct);
    }

    /**
     * Handle the OrderProduct "created" event.
     */
    public function created(OrderProduct $orderProduct): void
    {
        $this->refreshIfReserved($orderProduct, $orderProduct->product_id);
    }

    /**
     * Handle the OrderProduct "updating" event.
     */
    public function updating(OrderProduct $orderProduct): void
    {
        $this->guardCanModifyProducts($orderProduct);
    }

    /**
     * Handle the OrderProduct "updated" event.
     */
    public function updated(OrderProduct $orderProduct): void
    {
        if (! $orderProduct->wasChanged(['product_id', 'quantity'])) {
            return;
        }

        $previousProductId = $orderProduct->getPrevious()['product_id'] ?? $orderProduct->product_id;

        $this->refreshIfReserved($orderProduct, $previousProductId);

        if ($orderProduct->product_id !== $previousProductId) {
            $this->refreshIfReserved($orderProduct, $orderProduct->product_id);
        }
    }

    /**
     * Handle the OrderProduct "deleting" event.
     */
    public function deleting(OrderProduct $orderProduct): void
    {
        $this->guardCanModifyProducts($orderProduct);
    }

    /**
     * Handle the OrderProduct "deleted" event.
     */
    public function deleted(OrderProduct $orderProduct): void
    {
        $this->refreshIfReserved($orderProduct, $orderProduct->product_id);
    }

    /**
     * Handle the OrderProduct "restored" event.
     */
    public function restored(OrderProduct $orderProduct): void
    {
        //
    }

    /**
     * Handle the OrderProduct "force deleted" event.
     */
    public function forceDeleted(OrderProduct $orderProduct): void
    {
        //
    }

    private function refreshIfReserved(OrderProduct $orderProduct, ?int $productId): void
    {
        if (! $productId || ! $orderProduct->order_id) {
            return;
        }

        $order = Order::query()->with('orderStatus')->find($orderProduct->order_id);

        if (! $order?->orderStatus?->reserve_stock) {
            return;
        }

        $this->transitionService->refreshReservedStockForProductIds([$productId]);
    }

    private function guardCanModifyProducts(OrderProduct $orderProduct): void
    {
        if (! $orderProduct->order_id) {
            return;
        }

        $order = Order::query()->with('orderStatus')->find($orderProduct->order_id);

        if (! $order instanceof Order) {
            return;
        }

        $orderStatus = $order->orderStatus;

        if ($orderStatus instanceof OrderStatus && ! $orderStatus->canModifyProducts()) {
            throw new OrderProductsLockedException(
                "Order #{$order->id}'s products cannot be modified: its current status ({$orderStatus->name}) is not a concept status."
            );
        }
    }
}
