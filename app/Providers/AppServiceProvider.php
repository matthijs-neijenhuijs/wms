<?php

namespace App\Providers;

use App\Auth\SubdomainUserProvider;
use App\Events\OrderConfirmed;
use App\Events\OrderStatusChanged;
use App\Listeners\CreatePicklistForConfirmedOrder;
use App\Listeners\ProcessOrderStatusTransition;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Picklist;
use App\Models\PurchaseOrder;
use App\Observers\OrderObserver;
use App\Observers\OrderProductObserver;
use App\Observers\PicklistObserver;
use App\Observers\PurchaseOrderObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('subdomain', function ($app, array $config) {
            return new SubdomainUserProvider($app['hash'], $config['model']);
        });

        Event::listen(OrderConfirmed::class, CreatePicklistForConfirmedOrder::class);
        Event::listen(OrderStatusChanged::class, ProcessOrderStatusTransition::class);

        Order::observe(OrderObserver::class);
        OrderProduct::observe(OrderProductObserver::class);
        Picklist::observe(PicklistObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
    }
}
