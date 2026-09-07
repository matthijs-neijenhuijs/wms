<?php

declare(strict_types=1);

namespace Modules\Brands\Providers;

use Illuminate\Support\ServiceProvider;

class BrandsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
