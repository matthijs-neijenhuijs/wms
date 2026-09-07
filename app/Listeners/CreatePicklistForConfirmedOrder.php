<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\OrderStatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Orders\Events\OrderConfirmed;

class CreatePicklistForConfirmedOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private OrderStatusTransitionService $transitionService) {}

    public function handle(OrderConfirmed $event): void
    {
        $this->transitionService->generatePicklist($event->order);
    }
}
