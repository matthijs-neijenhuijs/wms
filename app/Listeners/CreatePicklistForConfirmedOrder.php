<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Models\Picklist;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreatePicklistForConfirmedOrder implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(OrderConfirmed $event): void
    {
        $order = $event->order;

        // Create picklist from confirmed order
        $picklist = Picklist::create([
            'warehouse_id' => $order->warehouse_id,
            'order_id' => $order->id,
            'name' => 'Picklist for Order '.$order->generated_custom_order_id,
        ]);

        // Add order products to picklist products
        foreach ($order->products as $product) {
            $picklist->products()->attach($product->id, [
                'quantity' => $product->quantity,
                'picked_quantity' => 0,
            ]);
        }
    }
}
