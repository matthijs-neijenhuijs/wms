<?php

namespace App\Filament\Resources\ApiKeys\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
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
                Textarea::make('allowed_ips')
                    ->label('Allowed IPs')
                    ->rows(3)
                    ->helperText('One IP per line or comma-separated. Leave empty to allow all.')
                    ->formatStateUsing(function ($state): ?string {
                        if (blank($state)) {
                            return null;
                        }

                        if (is_array($state)) {
                            return implode("\n", $state);
                        }

                        return (string) $state;
                    })
                    ->dehydrateStateUsing(function ($state): ?array {
                        if (blank($state)) {
                            return null;
                        }

                        $parts = preg_split('/[\n,]+/', (string) $state);
                        $filtered = array_values(array_filter(array_map('trim', $parts), fn ($value) => $value !== ''));

                        return $filtered === [] ? null : $filtered;
                    }),
                DatePicker::make('expires_at')
                    ->label('Expires at'),
            ]);
    }
}
