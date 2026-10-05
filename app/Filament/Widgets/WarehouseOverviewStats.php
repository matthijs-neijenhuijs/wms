<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderStatus;
use Modules\Picklists\Models\Picklist;
use Modules\Products\Models\StockProduct;

class WarehouseOverviewStats extends StatsOverviewWidget
{
    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $threshold = ($tenant instanceof Warehouse ? $tenant->low_stock_threshold : null) ?? 5;

        $openOrders = Order::query()
            ->where('completed', false)
            ->where('cancelled', false)
            ->where('delivered', false)
            ->count();

        $openPicklists = Picklist::query()
            ->where('completed', false)
            ->count();

        $lowStockProducts = StockProduct::query()
            ->whereHas('product', fn ($query) => $query->where('stock_unlimited', false))
            ->where('free_on_stock_quantity', '<=', $threshold)
            ->count();

        $pendingPurchaseOrders = PurchaseOrder::query()
            ->whereNotIn('status', [
                PurchaseOrderStatus::Received,
                PurchaseOrderStatus::Processed,
                PurchaseOrderStatus::Cancelled,
            ])
            ->count();

        return [
            Stat::make(__('Open Orders'), (string) $openOrders)
                ->icon(Heroicon::OutlinedShoppingCart)
                ->color('primary'),

            Stat::make(__('Open Picklists'), (string) $openPicklists)
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('info'),

            Stat::make(__('Low Stock Products'), (string) $lowStockProducts)
                ->description(__('Threshold: :threshold units', ['threshold' => $threshold]))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($lowStockProducts > 0 ? 'warning' : 'success'),

            Stat::make(__('Pending Purchase Orders'), (string) $pendingPurchaseOrders)
                ->icon(Heroicon::OutlinedTruck)
                ->color('info'),
        ];
    }
}
