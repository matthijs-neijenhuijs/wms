<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Pages;

use App\Services\OrderStatusTransitionService;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Modules\Orders\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Modules\Orders\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderEditForm;
use Modules\Orders\Models\PurchaseOrder;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function form(Schema $schema): Schema
    {
        return PurchaseOrderEditForm::configure($schema);
    }

    protected function afterSave(): void
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->getRecord();

        if (! $purchaseOrder->wasChanged('expected_delivery_date')) {
            return;
        }

        if (! $purchaseOrder->status->isDeferralEligible()) {
            return;
        }

        $productIds = $purchaseOrder->products()
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->all();

        if ($productIds !== []) {
            app(OrderStatusTransitionService::class)->refreshReservedStockForProductIds($productIds);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
