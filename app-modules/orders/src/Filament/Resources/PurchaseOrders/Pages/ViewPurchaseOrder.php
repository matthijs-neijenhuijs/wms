<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Pages;

use App\Services\PurchaseOrderProcessingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;
use Modules\Orders\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderFailedProduct;
use Modules\Orders\Models\PurchaseOrderProduct;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    #[On('purchase-order-barcode-scanned')]
    public function handleScannedBarcode(string $barcode): void
    {
        $this->scanProductBarcode($barcode);
    }

    public function scanProductBarcode(string $barcode): void
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->getRecord();

        $barcode = trim($barcode);

        $product = PurchaseOrderProduct::query()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->where('barcode', $barcode)
            ->orderBy('scanned')
            ->first();

        if (! $product || $product->scanned) {
            $failedProduct = PurchaseOrderFailedProduct::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->where('barcode', $barcode)
                ->first();

            if ($failedProduct) {
                $failedProduct->increment('total_quantity_scanned');
            } else {
                PurchaseOrderFailedProduct::query()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'barcode' => $barcode,
                    'reference_code' => $product?->reference_code,
                    'color' => $product?->color,
                    'size' => $product?->size,
                    'product_title' => $product?->product_title,
                    'total_quantity_scanned' => 1,
                ]);
            }

            Notification::make()
                ->title($product ? __('Product already scanned') : __('Unknown barcode'))
                ->danger()
                ->send();

            return;
        }

        $product->scanned = true;
        $product->save();

        $this->dispatch('purchase-order-product-scan-success', productId: $product->id);

        Notification::make()
            ->title(__('Product scanned'))
            ->success()
            ->send();

        /** @var PurchaseOrder $freshPurchaseOrder */
        $freshPurchaseOrder = $purchaseOrder->fresh();

        if ($freshPurchaseOrder->isFullyScanned()) {
            app(PurchaseOrderProcessingService::class)->processScanCompletion($freshPurchaseOrder);

            Notification::make()
                ->title(__('Purchase order fully scanned — stock updated'))
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->getRecord();

        return [
            Action::make('markReceived')
                ->label(__('Mark Received'))
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->visible(fn (): bool => $purchaseOrder->canMarkReceived())
                ->requiresConfirmation()
                ->modalDescription(__('Are you sure you want to mark this purchase order as received? This does not change stock.'))
                ->action(function () use ($purchaseOrder): void {
                    app(PurchaseOrderProcessingService::class)->markReceived($purchaseOrder);
                    $purchaseOrder->refresh();

                    Notification::make()
                        ->title(__('Purchase order marked as received'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
