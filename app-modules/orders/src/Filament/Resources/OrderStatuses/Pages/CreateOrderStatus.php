<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Orders\Filament\Resources\OrderStatuses\OrderStatusResource;

class CreateOrderStatus extends CreateRecord
{
    protected static string $resource = OrderStatusResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
