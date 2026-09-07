<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Orders\Filament\Resources\PurchaseOrders\Inputs\CommentsInput;
use Modules\Orders\Filament\Resources\PurchaseOrders\Inputs\ExpectedDeliveryDateInput;
use Modules\Orders\Filament\Resources\PurchaseOrders\Inputs\ImportFileInput;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Purchase Order Details'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        ExpectedDeliveryDateInput::make(),
                        ImportFileInput::make(),
                        CommentsInput::make(),
                    ]),
            ]);
    }
}
