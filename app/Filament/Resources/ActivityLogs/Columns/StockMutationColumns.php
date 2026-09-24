<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogs\Columns;

use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\PurchaseOrder;
use Spatie\Activitylog\Models\Activity;

/**
 * Columns/filter for the stock-mutation properties written by
 * `App\Services\StockMutationLogger`. Lives in `app/` (not the Products
 * module) so it can reference `Order`/`PurchaseOrder` (Orders module)
 * without the Products module depending on the Orders module.
 */
class StockMutationColumns
{
    public static function direction(): IconColumn
    {
        return IconColumn::make('stock_direction')
            ->label(__('Direction'))
            ->state(fn (Activity $record): ?int => self::delta($record))
            ->icon(fn (?int $state): ?Heroicon => match (true) {
                $state === null => null,
                $state > 0 => Heroicon::OutlinedArrowTrendingUp,
                $state < 0 => Heroicon::OutlinedArrowTrendingDown,
                default => Heroicon::OutlinedMinus,
            })
            ->color(fn (?int $state): string => match (true) {
                $state === null => 'gray',
                $state > 0 => 'success',
                $state < 0 => 'danger',
                default => 'gray',
            })
            ->tooltip(fn (?int $state): ?string => match (true) {
                $state === null => null,
                $state > 0 => self::stringLabel('Stock increased'),
                $state < 0 => self::stringLabel('Stock decreased'),
                default => null,
            });
    }

    public static function quantityDelta(): TextColumn
    {
        return TextColumn::make('stock_quantity_delta')
            ->label(__('Qty change'))
            ->state(fn (Activity $record): ?int => self::delta($record))
            ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : sprintf('%+d', $state))
            ->badge()
            ->color(fn (?int $state): string => match (true) {
                $state === null => 'gray',
                $state > 0 => 'success',
                $state < 0 => 'danger',
                default => 'gray',
            });
    }

    public static function orderReference(): TextColumn
    {
        return TextColumn::make('stock_order_reference')
            ->label(__('Order'))
            ->state(fn (Activity $record): ?string => $record->getProperty('order_reference')
                ?? $record->getProperty('purchase_order_reference'))
            ->url(function (Activity $record): ?string {
                if ($orderId = $record->getProperty('order_id')) {
                    $resource = Filament::getModelResource(Order::class);

                    if ($resource && $resource::hasPage('edit')) {
                        return $resource::getUrl('edit', ['record' => $orderId]);
                    }
                }

                if ($purchaseOrderId = $record->getProperty('purchase_order_id')) {
                    $resource = Filament::getModelResource(PurchaseOrder::class);

                    if ($resource && $resource::hasPage('view')) {
                        return $resource::getUrl('view', ['record' => $purchaseOrderId]);
                    }
                }

                return null;
            })
            ->placeholder('—');
    }

    public static function directionFilter(): SelectFilter
    {
        return SelectFilter::make('stock_direction')
            ->label(__('Stock direction'))
            ->options([
                'increase' => __('Increase'),
                'decrease' => __('Decrease'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query->when(
                filled($data['value'] ?? null),
                fn (Builder $query): Builder => $query->whereJsonContains('properties->direction', $data['value']),
            ));
    }

    private static function delta(Activity $record): ?int
    {
        $delta = $record->getProperty('quantity_delta');

        return $delta === null ? null : (int) $delta;
    }

    private static function stringLabel(string $key): string
    {
        $label = __($key);

        return is_string($label) ? $label : $key;
    }
}
