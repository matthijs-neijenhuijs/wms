<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Services\OrderStatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreatePicklistForConfirmedOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private OrderStatusTransitionService $transitionService) {}

    public function handle(OrderConfirmed $event): void
    {
        $this->transitionService->generatePicklist($event->order);
    }
}
