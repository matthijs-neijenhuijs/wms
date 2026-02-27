<?php

namespace App\Console\Commands;

use App\Events\OrderConfirmed;
use App\Listeners\CreatePicklistForConfirmedOrder;
use App\Models\Order;
use App\Models\Picklist;
use Illuminate\Console\Command;

class BackfillPicklistsForConfirmedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-picklists-for-confirmed-orders {--order-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing picklists for orders with a status that has generate_picklist enabled';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $listener = app(CreatePicklistForConfirmedOrder::class);
        $orderId = $this->option('order-id');

        $query = Order::query()
            ->whereHas('orderStatus', fn ($query) => $query->where('generate_picklist', true))
            ->whereNotIn('id', Picklist::query()->select('order_id'))
            ->with('products');

        if (filled($orderId)) {
            $query->where('id', (int) $orderId);
        }

        $orders = $query
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders found that require picklist backfill.');

            return self::SUCCESS;
        }

        $created = 0;

        foreach ($orders as $order) {
            if (! $order instanceof Order) {
                continue;
            }

            $listener->handle(new OrderConfirmed($order));
            $created++;
            $this->line("Processed order #{$order->id}");
        }

        $this->info("Backfill complete. Processed {$created} order(s).");

        return self::SUCCESS;
    }
}
