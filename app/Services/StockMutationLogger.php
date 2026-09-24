<?php

declare(strict_types=1);

namespace App\Services;

use Modules\Products\Models\StockProduct;
use Modules\Users\Models\User;
use Spatie\Activitylog\Contracts\Activity;

class StockMutationLogger
{
    /**
     * Writes one rich, order-attributed `activity_log` entry for a physical
     * `StockProduct::on_stock_quantity` mutation. Callers must suppress the
     * automatic dirty-diff entry themselves immediately before calling this,
     * via `activity()->withoutLogging(fn () => $stockProduct->save())`.
     *
     * @param  array<string, mixed>  $properties  Must include either
     *                                            `order_id`/`order_reference` (sales order reduced stock) or
     *                                            `purchase_order_id`/`purchase_order_reference` (purchase order
     *                                            increased stock).
     */
    public static function log(
        StockProduct $stockProduct,
        int $quantityBefore,
        ?int $causerId,
        string $description,
        array $properties,
    ): ?Activity {
        $quantityAfter = $stockProduct->on_stock_quantity;
        $delta = $quantityAfter - $quantityBefore;

        if ($delta === 0) {
            return null;
        }

        // Resolve to a Model (or null) rather than passing the raw id straight
        // through to causedBy(): Spatie throws CouldNotLogActivity if an int
        // id doesn't resolve to an existing user (e.g. deleted between the
        // request and this queued job running), which would otherwise abort
        // the whole stock mutation.
        $causer = $causerId ? User::find($causerId) : null;

        return activity('stock_product')
            ->performedOn($stockProduct)
            ->causedBy($causer)
            ->event('updated')
            ->withProperties([
                ...$properties,
                'quantity_delta' => $delta,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'direction' => $delta > 0 ? 'increase' : 'decrease',
            ])
            ->log($description);
    }
}
