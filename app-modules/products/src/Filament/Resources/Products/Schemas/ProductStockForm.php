<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Products\Filament\Resources\Products\Inputs\FreeOnStockQuantityInput;
use Modules\Products\Filament\Resources\Products\Inputs\OnStockQuantityInput;
use Modules\Products\Filament\Resources\Products\Inputs\ReservedOnPicklistsInput;
use Modules\Products\Filament\Resources\Products\Inputs\ReservedQuantityInput;

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
            ]);
    }
}
