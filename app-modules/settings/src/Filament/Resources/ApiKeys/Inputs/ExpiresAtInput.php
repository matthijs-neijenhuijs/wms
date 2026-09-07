<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\ApiKeys\Inputs;

use Filament\Forms\Components\DatePicker;

class ExpiresAtInput
{
    public static function make(): DatePicker
    {
        return DatePicker::make('expires_at')
            ->label(__('Expires At'));
    }
}
