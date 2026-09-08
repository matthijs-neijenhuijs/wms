<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Modules\Products\Models\Product;

class ProductSelect
{
    public static function make(): Select
    {
        return Select::make('product_id')
            ->relationship('product', 'name')
            ->required()
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(function (Set $set, $state): void {
                if (! $state) {
                    return;
                }

                $product = Product::with('vatRate')->find($state);

                if (! $product instanceof Product) {
                    return;
                }

                $set('vat_rate_id', $product->vat_rate_id);
                $set('vat_rate', $product->vatRate?->rate);
                $set('barcode', $product->barcode);
                $set('price', $product->price);
                $set('name', $product->name);
                $set('reference_code', $product->reference_code);
            });
    }
}
