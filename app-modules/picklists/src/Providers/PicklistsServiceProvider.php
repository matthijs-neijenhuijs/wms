<?php

declare(strict_types=1);

namespace Modules\Picklists\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Picklists\Models\Picklist;
use Modules\Picklists\Observers\PicklistObserver;

class PicklistsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'picklists');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Picklist::observe(PicklistObserver::class);
    }
}
