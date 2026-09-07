<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Inputs;

use Filament\Forms\Components\Textarea;

class CommentsInput
{
    public static function make(): Textarea
    {
        return Textarea::make('comments')
            ->columnSpanFull();
    }
}
