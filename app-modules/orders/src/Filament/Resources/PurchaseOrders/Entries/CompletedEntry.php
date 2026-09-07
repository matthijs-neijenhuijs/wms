<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;

class CompletedEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('completed')
            ->formatStateUsing(fn (bool|int|null $state): string => $state ? __('Yes') : __('No'));
    }
}
