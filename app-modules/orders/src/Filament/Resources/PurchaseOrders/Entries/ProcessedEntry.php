<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;

class ProcessedEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('processed')
            ->formatStateUsing(fn (bool|int|null $state): string => $state ? __('Yes') : __('No'));
    }
}
