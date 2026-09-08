<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\AttributeGroups\Inputs;

use Filament\Forms\Components\TextInput;

class AttributeNameInput
{
    public static function make(): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->maxLength(255);
    }
}
