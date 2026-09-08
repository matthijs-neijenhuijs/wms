<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dynamic Dashboard';

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        $parentWidgets = array_values(array_filter(
            parent::getWidgets(),
            function (string|WidgetConfiguration $widget): bool {
                $widgetClass = $widget instanceof WidgetConfiguration
                    ? $widget->widget
                    : $widget;

                return ! in_array($widgetClass, [
                    AccountWidget::class,
                    FilamentInfoWidget::class,
                ], true);
            },
        ));

        return [
            ...$parentWidgets,
        ];
    }

    /**
     * @return int | array<string, int | null>
     */
    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
