<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Tables;

use App\Models\Picklist;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Orders\Filament\Resources\Orders\OrderResource;

class PicklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('generated_custom_picklist_id')
                    ->label('id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.generated_custom_order_id')
                    ->label('order id')
                    ->url(fn (Picklist $record): ?string => $record->order
                        ? OrderResource::getUrl('edit', ['record' => $record->order])
                        : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('total products')
                    ->sortable(),
                TextColumn::make('scanned_products_count')
                    ->label('total scanned')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
