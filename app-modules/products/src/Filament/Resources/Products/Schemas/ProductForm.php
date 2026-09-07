<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product details')
                    ->schema([
                        Toggle::make('active')
                            ->inline(false)
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('reference_code')
                            ->required(),
                        TextInput::make('barcode')
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->columnSpan(2),
                        Select::make('vat_rate_id')
                            ->relationship(name: 'vatRate', titleAttribute: 'name'),
                        Select::make('brand_id')
                            ->relationship(name: 'brand', titleAttribute: 'name'),
                        Select::make('attributes')
                            ->multiple()
                            ->relationship('attributes', titleAttribute: 'name')
                            ->preload()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record): string => sprintf('%s: %s', optional($record->attributeGroup)->name ?? 'Ungrouped', $record->name))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
