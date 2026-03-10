<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DynamicAiCharts\DynamicAiChartResource;
use App\Filament\Widgets\AiChartChatWidget;
use App\Filament\Widgets\DynamicAiChartWidget;
use App\Models\DynamicAiChart;
use App\Models\User;
use App\Services\AiCharts\DynamicChartGenerator;
use DomainException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use OpenWms\FilamentDynamicAiCharts\Exceptions\AiChartClarificationException;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dynamic Dashboard';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('askAiForChart')
                ->label('Ask AI for chart')
                ->icon('heroicon-o-sparkles')
                ->schema([
                    Textarea::make('question')
                        ->label('Question')
                        ->rows(4)
                        ->required()
                        ->maxLength(2000),
                ])
                ->action(function (array $data, DynamicChartGenerator $generator): void {
                    $user = auth()->user();

                    if (! $user instanceof User) {
                        Notification::make()
                            ->title('You must be logged in to generate charts.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $warehouse = Filament::getTenant();

                    if (! $warehouse) {
                        Notification::make()
                            ->title('No active warehouse found.')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $chart = $generator->createFromQuestion(
                            question: (string) $data['question'],
                            user: $user,
                            warehouse: $warehouse,
                        );

                        Notification::make()
                            ->title('Chart created: '.$chart->title)
                            ->success()
                            ->send();
                    } catch (AiChartClarificationException $exception) {
                        Notification::make()
                            ->title('Need more information')
                            ->body($exception->clarificationQuestion)
                            ->warning()
                            ->persistent()
                            ->send();
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('manageAiCharts')
                ->label('Manage AI charts')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => DynamicAiChartResource::getUrl()),
        ];
    }

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

        $widgets = [
            AiChartChatWidget::class,
            ...$parentWidgets,
        ];

        $charts = DynamicAiChart::query()
            ->orderByDesc('score')
            ->latest()
            ->limit(12)
            ->get();

        foreach ($charts as $chart) {
            $widgets[] = DynamicAiChartWidget::make([
                'chartId' => $chart->id,
            ]);
        }

        return $widgets;
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
