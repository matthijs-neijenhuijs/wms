<?php

namespace App\Filament\Resources\ApiKeys\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ApiKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->inline(false)
                    ->default(true)
                    ->required(),
                DatePicker::make('expires_at')
                    ->label('Expires at'),
            ]);
    }
}
