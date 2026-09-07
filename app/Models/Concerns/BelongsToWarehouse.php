<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\WarehouseScope;

trait BelongsToWarehouse
{
    protected static function bootBelongsToWarehouse(): void
    {
        static::addGlobalScope(new WarehouseScope);
    }
}
