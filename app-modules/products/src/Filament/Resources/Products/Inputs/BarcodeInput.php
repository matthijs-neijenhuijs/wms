<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\TextInput;

class BarcodeInput
{
    public static function make(): TextInput
    {
        return TextInput::make('barcode')
            ->required();
    }
}
