<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\OrderStatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Orders\Events\OrderStatusChanged;

class ProcessOrderStatusTransition implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;

    public function __construct(private OrderStatusTransitionService $transitionService) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order->fresh(['products.product.stockProduct']);

        if (! $order) {
            return;
        }

        if (($event->newStatus?->getKey() ?? null) !== $order->order_statuses_id) {
            return;
        }

        $this->transitionService->process($order, $event->previousStatus, $event->newStatus);
    }
}
