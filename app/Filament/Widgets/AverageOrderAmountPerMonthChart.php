<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;
use Modules\Orders\Models\Order;

class AverageOrderAmountPerMonthChart extends ChartWidget
{
    protected ?string $heading = 'Average Order Amount per Month';

    protected string $color = 'success';

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

        $ordersByMonth = Order::query()
            ->whereYear('created_at', $year)
            ->with('products')
            ->get()
            ->groupBy(fn (Order $order): int => $order->created_at === null ? 0 : $order->created_at->month);

        $data = collect(range(1, 12))->map(function (int $month) use ($ordersByMonth) {
            /** @var Collection<int, Order> $monthOrders */
            $monthOrders = $ordersByMonth->get($month, collect());
            $orderCount = $monthOrders->count();

            if ($orderCount === 0) {
                return 0.0;
            }

            $totalRevenue = $monthOrders->sum(
                fn (Order $order) => $order->products->sum(fn ($product) => ($product->quantity ?? 0) * ($product->price ?? 0))
            );

            return round($totalRevenue / $orderCount, 2);
        });

        return [
            'datasets' => [
                [
                    'label' => __('Average Order Amount'),
                    'data' => $data->values()->all(),
                ],
            ],
            'labels' => collect(range(1, 12))->map(fn (int $month) => now()->setMonth($month)->format('M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
