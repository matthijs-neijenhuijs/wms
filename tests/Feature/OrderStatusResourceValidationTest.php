<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Orders\Filament\Resources\OrderStatuses\Pages\EditOrderStatus;
use Modules\Orders\Models\OrderStatus;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

function createOrderStatusValidationContext(): OrderStatus
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "order-status-validation-{$counter}",
        'name' => "Order Status Validation {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "OSV{$counter} Warehouse",
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Warehouse Manager',
        'email' => "manager-{$counter}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    return OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Status '.$counter,
        'color' => '#22c55e',
    ]);
}

it('rejects generate_picklist without reserve_stock', function () {
    $status = createOrderStatusValidationContext();

    Livewire::test(EditOrderStatus::class, ['record' => $status->getKey()])
        ->fillForm([
            'generate_picklist' => true,
            'reserve_stock' => false,
        ])
        ->call('save')
        ->assertHasFormErrors(['generate_picklist'])
        ->assertNotNotified();
});

it('accepts generate_picklist when reserve_stock is also enabled', function () {
    $status = createOrderStatusValidationContext();

    Livewire::test(EditOrderStatus::class, ['record' => $status->getKey()])
        ->fillForm([
            'generate_picklist' => true,
            'reserve_stock' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($status->fresh()->generate_picklist)->toBeTrue();
});

it('rejects completed combined with generate_picklist, reserve_stock, or reduce_stock', function (string $conflictingFlag) {
    $status = createOrderStatusValidationContext();

    Livewire::test(EditOrderStatus::class, ['record' => $status->getKey()])
        ->fillForm([
            'completed' => true,
            $conflictingFlag => true,
        ])
        ->call('save')
        ->assertHasFormErrors(['completed'])
        ->assertNotNotified();
})->with([
    'generate_picklist' => ['generate_picklist'],
    'reserve_stock' => ['reserve_stock'],
    'reduce_stock' => ['reduce_stock'],
]);

it('accepts completed when the workflow flags are all disabled', function () {
    $status = createOrderStatusValidationContext();

    Livewire::test(EditOrderStatus::class, ['record' => $status->getKey()])
        ->fillForm([
            'completed' => true,
            'generate_picklist' => false,
            'reserve_stock' => false,
            'reduce_stock' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($status->fresh()->completed)->toBeTrue();
});
