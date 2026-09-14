<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\ApiKeys\Inputs;

use Filament\Forms\Components\Textarea;

class AllowedIpsInput
{
    public static function make(): Textarea
    {
        return Textarea::make('allowed_ips')
            ->label(__('Allowed IPs'))
            ->rows(3)
            ->helperText(__('One IP per line or comma-separated. Leave empty to allow all.'))
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
                if ($parts === false) {
                    return null;
                }

                $filtered = array_values(array_filter(array_map('trim', $parts), fn ($value) => $value !== ''));

                return $filtered === [] ? null : $filtered;
            });
    }
}
