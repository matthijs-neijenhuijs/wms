<?php

namespace App\Observers;

use App\Models\OrderProduct;

class OrderProductObserver
{
    /**
     * Handle the OrderProduct "created" event.
     */
    public function created(OrderProduct $orderProduct): void
    {
        $orderProduct->order?->updateTotalPrice();
    }

    /**
     * Handle the OrderProduct "updated" event.
     */
    public function updated(OrderProduct $orderProduct): void
    {
        $orderProduct->order?->updateTotalPrice();
    }

    /**
     * Handle the OrderProduct "deleted" event.
     */
    public function deleted(OrderProduct $orderProduct): void
    {
        $orderProduct->order?->updateTotalPrice();
    }

    /**
     * Handle the OrderProduct "restored" event.
     */
    public function restored(OrderProduct $orderProduct): void
    {
        $orderProduct->order?->updateTotalPrice();
    }

    /**
     * Handle the OrderProduct "force deleted" event.
     */
    public function forceDeleted(OrderProduct $orderProduct): void
    {
        $orderProduct->order?->updateTotalPrice();
    }
}
