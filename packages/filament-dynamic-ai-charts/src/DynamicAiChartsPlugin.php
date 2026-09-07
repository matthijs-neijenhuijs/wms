<?php

declare(strict_types=1);

namespace OpenWms\FilamentDynamicAiCharts;

use Filament\Contracts\Plugin;
use Filament\Panel;

class DynamicAiChartsPlugin implements Plugin
{
    protected ?string $dashboardPageClass = 'App\\Filament\\Pages\\Dashboard';

    public static function make(): static
    {
        return app(static::class);
    }

    public function dashboardPage(?string $dashboardPageClass): static
    {
        $this->dashboardPageClass = $dashboardPageClass;

        return $this;
    }

    public function getId(): string
    {
        return 'dynamic-ai-charts';
    }

    public function register(Panel $panel): void
    {
        if (is_string($this->dashboardPageClass) && class_exists($this->dashboardPageClass)) {
            $panel->pages([$this->dashboardPageClass]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
