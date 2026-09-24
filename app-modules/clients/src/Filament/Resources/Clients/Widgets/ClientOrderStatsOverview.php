<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;

class ClientOrderStatsOverview extends StatsOverviewWidget
{
    public ?Client $record = null;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $totals = Order::query()
            ->where('client_id', $this->record?->getKey())
            ->toBase()
            ->leftJoin('order_products', 'order_products.order_id', '=', 'orders.id')
            ->selectRaw('count(distinct orders.id) as order_count, coalesce(sum(order_products.quantity * order_products.price), 0) as total_revenue')
            ->first();

        $orderCount = (int) ($totals->order_count ?? 0);
        $averageOrderAmount = $orderCount > 0
            ? ((float) $totals->total_revenue) / $orderCount
            : 0.0;

        $currency = $this->record?->warehouse->currency ?? 'EUR';

        return [
            Stat::make(__('Total Orders'), (string) $orderCount),
            Stat::make(__('Average Order Amount'), Number::currency($averageOrderAmount, in: $currency)),
        ];
    }
}
