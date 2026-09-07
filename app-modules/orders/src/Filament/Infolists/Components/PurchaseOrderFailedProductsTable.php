<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;

class PurchaseOrderFailedProductsTable extends Entry
{
    protected string $view = 'orders::filament.infolists.components.purchase-order-failed-products-table';
}
