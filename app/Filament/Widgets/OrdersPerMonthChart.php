<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Orders\Models\Order;

class OrdersPerMonthChart extends ChartWidget
{
    protected ?string $heading = 'Orders per Month';

    protected string $color = 'primary';

    public function mount(): void
    {
        parent::mount();

        $this->filter ??= (string) now()->year;
    }

    /**
     * @return array<int, string>
     */
    protected function getFilters(): ?array
    {
        $years = Order::query()
            ->get(['created_at'])
            ->map(fn (Order $order): int => $order->created_at === null ? 0 : $order->created_at->year)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $options = [];

        foreach ($years as $year) {
            $options[(string) $year] = (string) $year;
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $year = (int) ($this->filter ?? now()->year);

        $countsByMonth = Order::query()
            ->whereYear('created_at', $year)
            ->get(['created_at'])
            ->groupBy(fn (Order $order): int => $order->created_at === null ? 0 : $order->created_at->month)
            ->map->count();

        $data = collect(range(1, 12))->map(fn (int $month) => (int) ($countsByMonth[$month] ?? 0));

        return [
            'datasets' => [
                [
                    'label' => __('Orders'),
                    'data' => $data->values()->all(),
                ],
            ],
            'labels' => collect(range(1, 12))->map(fn (int $month) => now()->setMonth($month)->format('M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
