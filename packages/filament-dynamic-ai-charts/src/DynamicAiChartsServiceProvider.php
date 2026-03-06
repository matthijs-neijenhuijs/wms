<?php

namespace OpenWms\FilamentDynamicAiCharts;

use Illuminate\Support\ServiceProvider;

class DynamicAiChartsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai_charts.php',
            'ai_charts',
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-dynamic-ai-charts');

        $this->publishes([
            __DIR__.'/../config/ai_charts.php' => config_path('ai_charts.php'),
        ], 'dynamic-ai-charts-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'dynamic-ai-charts-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filament-dynamic-ai-charts'),
        ], 'dynamic-ai-charts-views');
    }
}
