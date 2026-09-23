<?php

declare(strict_types=1);

namespace Modules\Orders\Providers;

use App\Listeners\CreatePicklistForConfirmedOrder;
use App\Listeners\ProcessOrderStatusTransition;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Orders\Events\OrderConfirmed;
use Modules\Orders\Events\OrderStatusChanged;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Observers\OrderObserver;
use Modules\Orders\Observers\OrderProductObserver;
use Modules\Orders\Observers\PurchaseOrderObserver;

class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'orders');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Event::listen(OrderConfirmed::class, CreatePicklistForConfirmedOrder::class);
        Event::listen(OrderStatusChanged::class, ProcessOrderStatusTransition::class);

        Order::observe(OrderObserver::class);
        OrderProduct::observe(OrderProductObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
    }
}
