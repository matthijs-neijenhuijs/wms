<?php

declare(strict_types=1);

namespace Modules\Picklists\Observers;

use App\Models\Picklist;
use App\Models\Warehouse;

class PicklistObserver
{
    /**
     * Handle the Picklist "creating" event.
     */
    public function creating(Picklist $picklist): void
    {
        if ($picklist->generated_year_picklist_id !== null && $picklist->generated_custom_picklist_id !== null) {
            return;
        }

        if (! $picklist->warehouse_id) {
            return;
        }

        $warehouse = $picklist->relationLoaded('warehouse')
            ? $picklist->warehouse
            : Warehouse::query()->find($picklist->warehouse_id);

        $prefix = strtoupper(substr((string) ($warehouse?->name ?? ''), 0, 4));
        $year = now()->format('y');

        $maxGeneratedYearPicklistId = Picklist::query()
            ->withoutGlobalScopes()
            ->where('warehouse_id', $picklist->warehouse_id)
            ->whereYear('created_at', now()->year)
            ->max('generated_year_picklist_id');

        $nextGeneratedYearPicklistId = ($maxGeneratedYearPicklistId ?? 0) + 1;

        $picklist->generated_year_picklist_id ??= $nextGeneratedYearPicklistId;
        $picklist->generated_custom_picklist_id ??= "PICKLIST{$prefix}{$year}{$picklist->generated_year_picklist_id}";
    }
}
