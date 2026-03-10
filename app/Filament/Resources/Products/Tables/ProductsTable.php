<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('stockProduct'))
            ->columns([
                ToggleColumn::make('active'),
                TextColumn::make('reference_code')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->url(fn ($record): ?string => Filament::getTenant()
                        ? route('products.barcode.download', [
                            'tenant' => Filament::getTenant()->name,
                            'product' => $record,
                        ])
                        : null)
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('stock_summary')
                    ->label('Stock')
                    ->badge()
                    ->html()
                    ->state(function ($record): string {
                        $stock = $record->stockProduct;
                        $onStock = $stock?->on_stock_quantity ?? 0;
                        $reserved = $stock?->reserved_quantity ?? 0;
                        $reservedOnPicklists = $stock?->reserved_on_picklists ?? 0;
                        $free = $stock?->free_on_stock_quantity ?? 0;

                        return "<span class=\"leading-tight\">OnStock: {$onStock}<br>Reserved: {$reserved}<br>Picked: {$reservedOnPicklists}<br>Free: {$free}</span>";
                    }),

                TextColumn::make('brand.name')
                    ->numeric()
                    ->sortable(),

                TagsColumn::make('attributes')
                    ->getStateUsing(function ($record) {
                        return $record->attributes->map(function ($attribute) {
                            return "{$attribute->attributeGroup->name}: {$attribute->name}";
                        });
                    })
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
