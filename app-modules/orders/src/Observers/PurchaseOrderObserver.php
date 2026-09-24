<?php

declare(strict_types=1);

namespace Modules\Orders\Observers;

use App\Models\Warehouse;
use Modules\Orders\Exceptions\PurchaseOrderStatusLockedException;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderStatus;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "creating" event.
     */
    public function creating(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->generated_year_purchase_order_id !== null && $purchaseOrder->generated_custom_purchase_order_id !== null) {
            return;
        }

        if (! $purchaseOrder->warehouse_id) {
            return;
        }

        $warehouse = $purchaseOrder->relationLoaded('warehouse')
            ? $purchaseOrder->warehouse
            : Warehouse::query()->find($purchaseOrder->warehouse_id);

        if (! $warehouse instanceof Warehouse) {
            return;
        }

        $prefix = strtoupper(substr((string) $warehouse->name, 0, 4));
        $year = now()->format('y');

        $maxGeneratedYearPurchaseOrderId = PurchaseOrder::query()
            ->withoutGlobalScopes()
            ->where('warehouse_id', $purchaseOrder->warehouse_id)
            ->whereYear('created_at', now()->year)
            ->max('generated_year_purchase_order_id');

        $nextGeneratedYearPurchaseOrderId = ($maxGeneratedYearPurchaseOrderId ?? 0) + 1;

        $purchaseOrder->generated_year_purchase_order_id ??= $nextGeneratedYearPurchaseOrderId;
        $purchaseOrder->generated_custom_purchase_order_id ??= "PURCHASEORDER{$prefix}{$year}{$purchaseOrder->generated_year_purchase_order_id}";
    }

    /**
     * Handle the PurchaseOrder "updating" event.
     */
    public function updating(PurchaseOrder $purchaseOrder): void
    {
        if (! $purchaseOrder->isDirty('status')) {
            return;
        }

        $originalValue = $purchaseOrder->getOriginal('status');
        $originalStatus = $originalValue instanceof PurchaseOrderStatus
            ? $originalValue
            : PurchaseOrderStatus::from((string) $originalValue);

        if (! in_array($purchaseOrder->status, $originalStatus->allowedNextStatuses(), true)) {
            throw new PurchaseOrderStatusLockedException(
                "Purchase order #{$purchaseOrder->id}'s status cannot move from {$originalStatus->value} to {$purchaseOrder->status->value}."
            );
        }
    }
}
