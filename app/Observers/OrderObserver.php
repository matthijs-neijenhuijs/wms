<?php

namespace App\Observers;

use App\Events\OrderConfirmed;
use App\Models\Order;

class OrderObserver
{
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
