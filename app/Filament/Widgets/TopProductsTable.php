<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Modules\Products\Models\Product;

class TopProductsTable extends TableWidget
{
    public function table(Table $table): Table
    {
        $tenantId = Filament::getTenant()?->getKey();

        return $table
            ->heading(__('Top Products This Year'))
            ->query(
                Product::query()
                    ->select('products.*')
                    ->selectRaw('COALESCE(SUM(order_products.quantity), 0) as quantity_sold')
                    ->join('order_products', 'order_products.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'order_products.order_id')
                    ->where('orders.warehouse_id', $tenantId)
                    ->whereYear('orders.created_at', now()->year)
                    ->groupBy('products.id')
                    ->orderByDesc('quantity_sold')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('Product')),

                TextColumn::make('reference_code')
                    ->label(__('Reference')),

                TextColumn::make('quantity_sold')
                    ->label(__('Quantity Sold'))
                    ->numeric(),
            ])
            ->paginated(false);
    }
}
