<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('currency')
                    ->options([
                        'EUR' => 'Euro (€)',
                        'USD' => 'US Dollar ($)',
                        'GBP' => 'British Pound (£)',
                        'JPY' => 'Japanese Yen (¥)',
                        'CHF' => 'Swiss Franc (CHF)',
                        'CAD' => 'Canadian Dollar (C$)',
                        'AUD' => 'Australian Dollar (A$)',
                        'CNY' => 'Chinese Yuan (¥)',
                    ])
                    ->required()
                    ->default('EUR')
                    ->searchable(),

                Select::make('order_statuses_id_completed_picklist')
                    ->label('Completed picklist status')
                    ->relationship(
                        name: 'completedPicklistOrderStatus',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query, ?Warehouse $record): Builder {
                            if (! $record) {
                                return $query->whereNull('id');
                            }

                            return $query
                                ->where('warehouse_id', $record->id)
                                ->orderBy('name');
                        },
                    )
                    ->nullable()
                    ->placeholder('None')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
