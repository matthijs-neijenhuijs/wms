<?php

namespace App\Providers;

use App\Auth\SubdomainUserProvider;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Observers\OrderObserver;
use App\Observers\OrderProductObserver;
use Illuminate\Support\Facades\Auth;
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

        Order::observe(OrderObserver::class);
        OrderProduct::observe(OrderProductObserver::class);
    }
}
