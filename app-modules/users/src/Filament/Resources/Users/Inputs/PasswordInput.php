<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Inputs;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;

class PasswordInput
{
    public static function make(): TextInput
    {
        return TextInput::make('password')
            ->password()
            ->revealable()
            ->required(fn (string $context): bool => $context === 'create')
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Hash::make($state) : null);
    }
}
