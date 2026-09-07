<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Subdomain;
use App\Models\Warehouse;
use App\Services\PurchaseOrderImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

it('imports purchase order products from a csv file with barcode and quantity data', function () {
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'purchase-order-import',
        'name' => 'Purchase Order Import',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Import Warehouse',
        'currency' => 'EUR',
    ]);

    Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'REF-PO-001',
        'product_code' => 'PO-001',
        'barcode' => '1234567890123',
        'name' => 'Imported Product',
        'description' => 'Import description',
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'expected_delivery_date' => '2026-04-10',
        'comments' => 'Supplier import',
    ]);

    $file = UploadedFile::fake()->createWithContent('purchase-order.csv', implode(PHP_EOL, [
        'barcode,quantity',
        '1234567890123,2',
        '9999999999999,1',
    ]));

    app(PurchaseOrderImportService::class)->import($purchaseOrder, $file);

    $purchaseOrder->refresh();

    expect($purchaseOrder->processed)->toBeFalse();
    expect($purchaseOrder->completed)->toBeFalse();
    expect($purchaseOrder->products()->count())->toBe(3);

    $this->assertDatabaseHas('purchase_orders_products', [
        'purchase_order_id' => $purchaseOrder->id,
        'barcode' => '1234567890123',
        'reference_code' => 'REF-PO-001',
        'product_title' => 'Imported Product',
    ]);

    $this->assertDatabaseHas('purchase_orders_products', [
        'purchase_order_id' => $purchaseOrder->id,
        'barcode' => '9999999999999',
        'product_title' => 'Unknown product',
    ]);
});
