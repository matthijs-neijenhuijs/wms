<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

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
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
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

            ]);
    }
}
