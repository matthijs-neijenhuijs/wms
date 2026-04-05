<?php

use App\Filament\RelationManagers\HistoryRelationManager;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Clients\Pages\ManageClientActivities;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ManageOrderActivities;
use App\Filament\Resources\Products\Pages\ManageProductActivities;
use App\Filament\Resources\Products\ProductResource;
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

it('registers history pages for audited resources', function () {
    expect(ClientResource::getPages())->toHaveKey('history');
    expect(OrderResource::getPages())->toHaveKey('history');
    expect(ProductResource::getPages())->toHaveKey('history');

    expect(ManageClientActivities::getRelatedResource())->toBe(ActivityLogResource::class);
    expect(ManageOrderActivities::getRelatedResource())->toBe(ActivityLogResource::class);
    expect(ManageProductActivities::getRelatedResource())->toBe(ActivityLogResource::class);

    expect(ClientResource::getRelations())->not->toContain(HistoryRelationManager::class);
    expect(OrderResource::getRelations())->not->toContain(HistoryRelationManager::class);
    expect(ProductResource::getRelations())->not->toContain(HistoryRelationManager::class);
});
