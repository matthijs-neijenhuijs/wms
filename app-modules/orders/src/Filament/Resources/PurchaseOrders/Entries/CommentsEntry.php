<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;

class CommentsEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('comments');
    }
}
