<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Columns;

use Filament\Tables\Columns\TextColumn;

class StockSummaryColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('stock_summary')
            ->label('Stock')
            ->html()
            ->state(function ($record): string {
                $stock = $record->stockProduct;
                $onStock = $stock !== null && $stock->on_stock_quantity !== null ? $stock->on_stock_quantity : 0;
                $reserved = $stock !== null && $stock->reserved_quantity !== null ? $stock->reserved_quantity : 0;
                $reservedOnPicklists = $stock !== null && $stock->reserved_on_picklists !== null ? $stock->reserved_on_picklists : 0;
                $free = $stock !== null && $stock->free_on_stock_quantity !== null ? $stock->free_on_stock_quantity : 0;

                return sprintf(
                    '<div class="flex flex-col items-start gap-1"><span class="fi-color fi-color-info fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap" style="color: #000;">Stock: %d</span><span class="fi-color fi-color-info fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap" style="color: #000;">Res: %d</span><span class="fi-color fi-color-info fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap" style="color: #000;">Pick: %d</span><span class="fi-color fi-color-info fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap" style="color: #000;">Free: %d</span></div>',
                    $onStock,
                    $reserved,
                    $reservedOnPicklists,
                    $free,
                );
            });
    }
}
