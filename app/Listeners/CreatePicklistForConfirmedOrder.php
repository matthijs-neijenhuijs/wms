<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Models\Picklist;
use App\Models\PicklistProduct;
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
        $order = $event->order->loadMissing('products');

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

        foreach ($order->products as $orderProduct) {
            $quantity = max((int) ($orderProduct->quantity ?? 0), 0);

            for ($index = 0; $index < $quantity; $index++) {
                PicklistProduct::query()->create([
                    'picklist_id' => $picklist->id,
                    'ean_code' => (string) ($orderProduct->barcode ?? ''),
                    'reference_code' => $orderProduct->reference_code,
                    'product_title' => (string) $orderProduct->name,
                    'show_for_supplier' => false,
                    'scanned' => false,
                ]);
            }
        }
    }
}
