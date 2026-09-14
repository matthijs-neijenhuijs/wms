<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Inputs;

use Filament\Forms\Components\Textarea;

class DescriptionInput
{
    public static function make(): Textarea
    {
        return Textarea::make('description')
            ->required()
            ->columnSpanFull();
    }
}
