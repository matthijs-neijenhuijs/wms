<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductStockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stock')
                    ->relationship('stockProduct')
                    ->schema([
                        TextInput::make('on_stock_quantity')
                            ->label('Quantity on stock')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->live()
                            ->afterStateHydrated(fn (Set $set, Get $get) => self::updateFreeStock($set, $get))
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateFreeStock($set, $get)),
                        TextInput::make('reserved_quantity')
                            ->label('Reserved quantity')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateFreeStock($set, $get)),
                        TextInput::make('reserved_on_picklists')
                            ->label('Reserved on picklists')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateFreeStock($set, $get)),
                        TextInput::make('free_on_stock_quantity')
                            ->label('Free on stock quantity')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    private static function updateFreeStock(Set $set, Get $get): void
    {
        $freeOnStock = max(
            0,
            (int) $get('on_stock_quantity')
                - (int) $get('reserved_quantity')
                - (int) $get('reserved_on_picklists')
        );

        $set('free_on_stock_quantity', $freeOnStock);
    }
}
