<?php

namespace OpenWms\FilamentAiAutosuggestField;

use Filament\Contracts\Plugin;
use Filament\Panel;

class AiAutosuggestFieldPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'ai-autosuggest-field';
    }

    public function register(Panel $panel): void
    {
        // Standalone form field plugin.
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
