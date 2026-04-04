<?php

use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('can scope the activity log query for a warehouse tenant', function () {
    $subdomain = Subdomain::create([
        'subdomain' => 'test-tenant',
        'name' => 'Test Tenant',
    ]);

    $warehouse = Warehouse::create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Main Warehouse',
        'currency' => 'EUR',
    ]);

    $resourceClass = config('filament-activity-log.resource.class');

    expect($resourceClass::scopeEloquentQueryToTenant(Activity::query(), $warehouse))
        ->toBeInstanceOf(Builder::class);
});
