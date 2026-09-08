<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;
use Modules\Picklists\Filament\Resources\Picklists\PicklistResource;
use Modules\Picklists\Models\Picklist;
use Modules\Picklists\Models\PicklistFailedProduct;
use Modules\Picklists\Models\PicklistProduct;

class ViewPicklist extends ViewRecord
{
    protected static string $resource = PicklistResource::class;

    #[On('picklist-barcode-scanned')]
    public function handleScannedBarcode(string $barcode): void
    {
        $this->scanProductBarcode($barcode);
    }

    public function scanProductBarcode(string $barcode): void
    {
        $record = $this->record;

        if (! $record instanceof Picklist) {
            return;
        }

        $barcode = trim($barcode);

        if ($barcode === '') {
            Notification::make()
                ->title('No barcode provided.')
                ->warning()
                ->send();

            return;
        }

        $picklistProduct = PicklistProduct::query()
            ->where('picklist_id', $record->getKey())
            ->where('barcode', $barcode)
            ->orderBy('scanned')
            ->first();

        if (! $picklistProduct) {
            $failedProduct = PicklistFailedProduct::query()
                ->where('picklist_id', $record->getKey())
                ->where('barcode', $barcode)
                ->first();

            if ($failedProduct) {
                $failedProduct->increment('total_quantity_scanned');
            } else {
                PicklistFailedProduct::create([
                    'picklist_id' => $record->getKey(),
                    'barcode' => $barcode,
                    'total_quantity_scanned' => 1,
                ]);
            }

            Notification::make()
                ->title("Barcode {$barcode} is not in this picklist.")
                ->danger()
                ->send();

            $record->unsetRelation('failedProducts');
            $record->load('failedProducts');

            return;
        }

        if ($picklistProduct->scanned) {
            $failedProduct = PicklistFailedProduct::query()
                ->where('picklist_id', $record->getKey())
                ->where('barcode', $barcode)
                ->first();

            if ($failedProduct) {
                $failedProduct->increment('total_quantity_scanned');
            } else {
                PicklistFailedProduct::create([
                    'picklist_id' => $record->getKey(),
                    'barcode' => $barcode,
                    'reference_code' => $picklistProduct->reference_code,
                    'color' => $picklistProduct->color,
                    'size' => $picklistProduct->size,
                    'product_title' => $picklistProduct->product_title,
                    'total_quantity_scanned' => 1,
                ]);
            }

            Notification::make()
                ->title("Barcode {$barcode} is already scanned.")
                ->warning()
                ->send();

            $record->unsetRelation('failedProducts');
            $record->load('failedProducts');

            return;
        }

        $picklistProduct->update([
            'scanned' => true,
        ]);

        if ($record->relationLoaded('products')) {
            $loadedProduct = $record->products->firstWhere('id', $picklistProduct->id);

            if ($loadedProduct) {
                $loadedProduct->scanned = true;
                $loadedProduct->updated_at = $picklistProduct->updated_at;
            }
        }

        Notification::make()
            ->title("Barcode {$barcode} scanned.")
            ->success()
            ->send();

        $this->dispatch('picklist-product-scan-success', productId: $picklistProduct->id);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
