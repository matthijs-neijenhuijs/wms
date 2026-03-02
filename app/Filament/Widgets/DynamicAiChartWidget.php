<?php

namespace App\Filament\Widgets;

use App\Models\DynamicAiChart;
use App\Services\AiCharts\DynamicChartGenerator;
use Filament\Widgets\ChartWidget;

class DynamicAiChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;

    public ?int $chartId = null;

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '30s';

    protected ?DynamicAiChart $chart = null;

    public function mount(): void
    {
        $this->chart = $this->resolveChart();

        parent::mount();
    }

    public function getHeading(): string
    {
        $chart = $this->resolveChart();

        if (! $chart) {
            return 'Chart unavailable';
        }

        return $chart->title;
    }

    public function getDescription(): ?string
    {
        $chart = $this->resolveChart();

        if (! $chart) {
            return null;
        }

        if ((int) $chart->score > 0) {
            return 'Score: '.$chart->score.' • '.$chart->question;
        }

        return $chart->question;
    }

    protected function getData(): array
    {
        $chart = $this->resolveChart();

        if (! $chart) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Unavailable',
                        'data' => [],
                    ],
                ],
            ];
        }

        $payload = app(DynamicChartGenerator::class)->buildChartPayload($chart);

        $chart->update([
            'chart_payload' => $payload,
        ]);

        return $payload;
    }

    protected function getType(): string
    {
        return $this->resolveChart()?->chart_type ?? 'bar';
    }

    protected function resolveChart(): ?DynamicAiChart
    {
        if (! $this->chartId) {
            return null;
        }

        if ($this->chart?->id === $this->chartId) {
            return $this->chart;
        }

        $this->chart = DynamicAiChart::query()->find($this->chartId);

        return $this->chart;
    }
}
