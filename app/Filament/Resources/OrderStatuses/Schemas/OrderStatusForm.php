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
                        Toggle::make('generate_picklist')
                            ->label('Generate Picklist')
                            ->default(false),

                        Toggle::make('reserve_stock')
                            ->label('Reserve Stock')
                            ->default(false),

                        Toggle::make('concepted')
                            ->label('Is Concepted')
                            ->default(false),

                        Toggle::make('completed')
                            ->label('Is Completed')
                            ->default(false),

                        Toggle::make('paused')
                            ->label('Is Paused')
                            ->default(false),

                        Toggle::make('delivered')
                            ->label('Delivered')
                            ->default(false),

                        Toggle::make('cancelled')
                            ->label('Cancelled')
                            ->default(false),
                    ]),
            ]);
    }
}
