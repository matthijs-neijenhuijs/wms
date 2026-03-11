<?php

namespace App\Filament\Resources\Picklists\Pages;

use App\Filament\Resources\Picklists\PicklistResource;
use App\Models\PicklistFailedProduct;
use App\Models\PicklistProduct;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

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
        $barcode = trim($barcode);

        if ($barcode === '') {
            Notification::make()
                ->title('No barcode provided.')
                ->warning()
                ->send();

            return;
        }

        $picklistProduct = PicklistProduct::query()
            ->where('picklist_id', $this->record->getKey())
            ->where('barcode', $barcode)
            ->orderBy('scanned')
            ->first();

        if (! $picklistProduct) {
            $failedProduct = PicklistFailedProduct::query()
                ->where('picklist_id', $this->record->getKey())
                ->where('barcode', $barcode)
                ->first();

            if ($failedProduct) {
                $failedProduct->increment('total_quantity_scanned');
            } else {
                PicklistFailedProduct::create([
                    'picklist_id' => $this->record->getKey(),
                    'barcode' => $barcode,
                    'total_quantity_scanned' => 1,
                ]);
            }

            Notification::make()
                ->title("Barcode {$barcode} is not in this picklist.")
                ->danger()
                ->send();

            $this->record->unsetRelation('failedProducts');
            $this->record->load('failedProducts');

            return;
        }

        if ($picklistProduct->scanned) {
            $failedProduct = PicklistFailedProduct::query()
                ->where('picklist_id', $this->record->getKey())
                ->where('barcode', $barcode)
                ->first();

            if ($failedProduct) {
                $failedProduct->increment('total_quantity_scanned');
            } else {
                PicklistFailedProduct::create([
                    'picklist_id' => $this->record->getKey(),
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

            $this->record->unsetRelation('failedProducts');
            $this->record->load('failedProducts');

            return;
        }

        $picklistProduct->update([
            'scanned' => 1,
        ]);

        if ($this->record->relationLoaded('products')) {
            $loadedProduct = $this->record->products->firstWhere('id', $picklistProduct->id);

            if ($loadedProduct) {
                $loadedProduct->scanned = 1;
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
