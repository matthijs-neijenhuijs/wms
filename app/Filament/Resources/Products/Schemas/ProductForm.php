<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
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
                TextInput::make('ean')
                    ->required(),
                TextInput::make('name')
                    ->required()->columnSpan(2),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
Select::make('tax_rate_id')
    ->relationship(name: 'taxRate', titleAttribute: 'name'),

Select::make('brand_id')
    ->relationship(name: 'brand', titleAttribute: 'name'),

Select::make('product_category_id')
    ->relationship(name: 'productCategory', titleAttribute: 'name')


            ]);
    }
}
