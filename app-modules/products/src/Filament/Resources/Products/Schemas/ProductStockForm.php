<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Products\Filament\Resources\Products\Inputs\FreeOnStockQuantityInput;
use Modules\Products\Filament\Resources\Products\Inputs\OnStockQuantityInput;
use Modules\Products\Filament\Resources\Products\Inputs\ReservedOnPicklistsInput;
use Modules\Products\Filament\Resources\Products\Inputs\ReservedQuantityInput;
use Modules\Products\Models\Product;

class ProductStockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stock')
                    ->relationship('stockProduct')
                    ->schema([
                        OnStockQuantityInput::make(),
                        ReservedQuantityInput::make(),
                        ReservedOnPicklistsInput::make(),
                        FreeOnStockQuantityInput::make(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('Incoming Stock'))
                    ->columnSpanFull()
                    ->schema([
                        View::make('products::filament.stock.incoming-stock')
                            ->viewData(fn (?Product $record) => [
                                'batches' => $record ? PurchaseOrder::incomingBatchesForProduct($record->id) : collect(),
                            ]),
                    ]),
            ]);
    }
}
