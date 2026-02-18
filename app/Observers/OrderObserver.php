<?php

namespace App\Observers;

use App\Events\OrderConfirmed;
use App\Models\Order;
use App\Models\Warehouse;

class OrderObserver
{
    /**
     * Handle the Order "creating" event.
     */
    public function creating(Order $order): void
    {
        if ($order->generated_year_order_id !== null && $order->generated_custom_order_id !== null) {
            return;
        }

        if (! $order->warehouse_id) {
            return;
        }

        $warehouse = $order->relationLoaded('warehouse')
            ? $order->warehouse
            : Warehouse::query()->find($order->warehouse_id);

        $prefix = strtoupper(substr((string) ($warehouse?->name ?? ''), 0, 4));
        $year = now()->format('y');

        $maxGeneratedYearOrderId = Order::query()
            ->withoutGlobalScopes()
            ->where('warehouse_id', $order->warehouse_id)
            ->whereYear('created_at', now()->year)
            ->max('generated_year_order_id');

        $nextGeneratedYearOrderId = ($maxGeneratedYearOrderId ?? 0) + 1;

        $order->generated_year_order_id ??= $nextGeneratedYearOrderId;
        $order->generated_custom_order_id ??= "ORDER{$prefix}{$year}{$order->generated_year_order_id}";
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if order status changed to confirmed
        if ($order->isDirty('order_statuses_id')) {
            $newStatus = $order->orderStatus;

            // If status is now confirmed, dispatch event
            if ($newStatus && $newStatus->order_is_confirmed) {
                OrderConfirmed::dispatch($order);
            }
        }
    }
}
