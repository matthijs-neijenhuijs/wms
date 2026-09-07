<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Orders\Filament\Resources\Orders\Inputs\ClientSelect;
use Modules\Orders\Filament\Resources\Orders\Inputs\OrderStatusSelect;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Order Details'))
                    ->schema([
                        OrderStatusSelect::make(),
                        ClientSelect::make(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
