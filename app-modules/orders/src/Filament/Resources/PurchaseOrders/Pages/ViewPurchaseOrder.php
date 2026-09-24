<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Pages;

use App\Services\PurchaseOrderProcessingService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;
use Modules\Orders\Filament\Resources\PurchaseOrders\Inputs\ExpectedDeliveryDateInput;
use Modules\Orders\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderFailedProduct;
use Modules\Orders\Models\PurchaseOrderProduct;
use Modules\Orders\Models\PurchaseOrderStatus;

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
            app(PurchaseOrderProcessingService::class)->evaluateScanCompletion($freshPurchaseOrder);

            Notification::make()
                ->title(__('Purchase order fully scanned'))
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->getRecord();

        return [
            Action::make('markPurchased')
                ->label(__('Mark Purchased'))
                ->icon(Heroicon::Truck)
                ->color('warning')
                ->visible(fn (): bool => $purchaseOrder->canTransitionTo(PurchaseOrderStatus::Purchased))
                ->schema([
                    ExpectedDeliveryDateInput::make()
                        ->required()
                        ->default($purchaseOrder->expected_delivery_date),
                ])
                ->action(function (array $data) use ($purchaseOrder): void {
                    app(PurchaseOrderProcessingService::class)->markPurchased(
                        $purchaseOrder,
                        Carbon::parse($data['expected_delivery_date']),
                    );
                    $purchaseOrder->refresh();

                    Notification::make()
                        ->title(__('Purchase order marked as purchased'))
                        ->success()
                        ->send();
                }),

            Action::make('markReceived')
                ->label(__('Mark Received'))
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->visible(fn (): bool => $purchaseOrder->canTransitionTo(PurchaseOrderStatus::Received))
                ->requiresConfirmation()
                ->modalDescription(__('Are you sure you want to mark this purchase order as received? This does not change stock, and does not set the Received Date — set that on the Edit page if needed.'))
                ->action(function () use ($purchaseOrder): void {
                    app(PurchaseOrderProcessingService::class)->markReceived($purchaseOrder);
                    $purchaseOrder->refresh();

                    Notification::make()
                        ->title(__('Purchase order marked as received'))
                        ->success()
                        ->send();
                }),

            Action::make('markProcessed')
                ->label(__('Mark Processed'))
                ->icon(Heroicon::CheckBadge)
                ->color('success')
                ->visible(fn (): bool => $purchaseOrder->canTransitionTo(PurchaseOrderStatus::Processed))
                ->requiresConfirmation()
                ->modalDescription(__('Are you sure you want to mark this purchase order as processed? This will increase on-hand stock for every scanned line item.'))
                ->action(function () use ($purchaseOrder): void {
                    $causerId = auth()->id();

                    app(PurchaseOrderProcessingService::class)->markProcessed(
                        $purchaseOrder,
                        is_int($causerId) ? $causerId : null,
                    );
                    $purchaseOrder->refresh();

                    Notification::make()
                        ->title(__('Purchase order processed — stock updated'))
                        ->success()
                        ->send();
                }),

            Action::make('cancel')
                ->label(__('Cancel'))
                ->icon(Heroicon::XCircle)
                ->color('danger')
                ->visible(fn (): bool => $purchaseOrder->canTransitionTo(PurchaseOrderStatus::Cancelled))
                ->requiresConfirmation()
                ->modalDescription(__('Are you sure you want to cancel this purchase order? This cannot be undone.'))
                ->action(function () use ($purchaseOrder): void {
                    app(PurchaseOrderProcessingService::class)->cancel($purchaseOrder);
                    $purchaseOrder->refresh();

                    Notification::make()
                        ->title(__('Purchase order cancelled'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
