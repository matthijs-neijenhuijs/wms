<?php

declare(strict_types=1);

namespace OpenWms\FilamentAiAutosuggestField;

use Illuminate\Support\ServiceProvider;

class AiAutosuggestFieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai_autosuggest_field.php',
            'ai_autosuggest_field',
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-ai-autosuggest-field');

        $this->publishes([
            __DIR__.'/../config/ai_autosuggest_field.php' => config_path('ai_autosuggest_field.php'),
        ], 'ai-autosuggest-field-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filament-ai-autosuggest-field'),
        ], 'ai-autosuggest-field-views');
    }
}
