<?php

namespace App\Filament\Resources\OrderStatuses\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OrderStatusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        ColorPicker::make('color')
                            ->required(),
                    ]),

                Fieldset::make('Status Flags')
                    ->columns(3)
                    ->components([
                        Toggle::make('order_is_expected')
                            ->label('Is Expected')
                            ->default(false),

                        Toggle::make('order_is_concept')
                            ->label('Is Concept')
                            ->default(false),

                        Toggle::make('order_is_confirmed')
                            ->label('Is Confirmed')
                            ->default(false),

                        Toggle::make('order_is_shipped')
                            ->label('Is Shipped')
                            ->default(false),

                        Toggle::make('order_is_delivered')
                            ->label('Is Delivered')
                            ->default(false),

                        Toggle::make('order_is_cancelled')
                            ->label('Is Cancelled')
                            ->default(false),
                    ]),
            ]);
    }
}
