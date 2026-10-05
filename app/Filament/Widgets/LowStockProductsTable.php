<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Modules\Products\Models\Product;

class LowStockProductsTable extends TableWidget
{
    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        $threshold = ($tenant instanceof Warehouse ? $tenant->low_stock_threshold : null) ?? 5;

        return $table
            ->heading(__('Low Stock Products'))
            ->query(
                Product::query()
                    ->where('products.stock_unlimited', false)
                    ->join('stock_products', 'stock_products.product_id', '=', 'products.id')
                    ->where('stock_products.free_on_stock_quantity', '<=', $threshold)
                    ->orderBy('stock_products.free_on_stock_quantity')
                    ->select('products.*')
                    ->with('stockProduct')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('Product'))
                    ->searchable(),

                TextColumn::make('reference_code')
                    ->label(__('Reference')),

                TextColumn::make('stockProduct.on_stock_quantity')
                    ->label(__('On Stock'))
                    ->numeric(),

                TextColumn::make('stockProduct.reserved_quantity')
                    ->label(__('Reserved'))
                    ->numeric(),

                TextColumn::make('stockProduct.free_on_stock_quantity')
                    ->label(__('Free Stock'))
                    ->numeric()
                    ->badge()
                    ->color(fn (?int $state): string => ($state ?? 0) <= 0 ? 'danger' : 'warning'),
            ])
            ->paginated([5, 10, 25]);
    }
}
