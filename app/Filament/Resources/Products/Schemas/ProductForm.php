<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use OpenWms\FilamentAiAutosuggestField\Forms\Components\AiAutosuggestField;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('active')->inline(false)
                    ->required()->columnSpan(2),
                TextInput::make('reference_code')
                    ->required(),
                TextInput::make('barcode')
                    ->required(),
                TextInput::make('name')
                    ->required()->columnSpan(2),
                Select::make('vat_rate_id')
                    ->relationship(name: 'vatRate', titleAttribute: 'name'),
                Select::make('brand_id')
                    ->relationship(name: 'brand', titleAttribute: 'name'),
                Select::make('attributes')
                    ->multiple()
                    ->relationship('attributes', titleAttribute: 'name')
                    ->preload()
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn ($record) => sprintf('%s: %s', $record->attributeGroup?->name ?? 'Ungrouped', $record->name))
                    ->columnSpanFull(),
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
