<?php

declare(strict_types=1);

namespace Modules\Orders\Models;

final class PurchaseOrderIncomingBatch
{
    public function __construct(
        public readonly string $expected_delivery_date,
        public readonly int $quantity,
    ) {}
}
