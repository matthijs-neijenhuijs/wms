<?php

namespace App\Filament\Resources\TaxRates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class TaxRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                       TextColumn::make('name'),
                                TextColumn::make(name: 'rate')
        
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
