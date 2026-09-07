<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Number;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('product_code')
                ->rules(['required', 'max:255']),
            ImportColumn::make('barcode')
                ->label('EAN/Barcode')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('name')
                ->rules(['required', 'max:255']),
            ImportColumn::make('reference_code')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('price')
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('description')
                ->rules(['nullable', 'max:65535']),
            ImportColumn::make('active')
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('stock_unlimited')
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('weight')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('height')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('length')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('hs_code')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('country_of_origin')
                ->rules(['nullable', 'max:255']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Checkbox::make('updateExisting')
                ->label('Update existing products (match by product_code)'),
            Hidden::make('warehouse_id')
                ->default(fn (): ?int => Filament::getTenant()?->id)
                ->dehydrated(),
        ];
    }

    public function resolveRecord(): ?Product
    {
        $warehouseId = $this->options['warehouse_id'] ?? Filament::getTenant()?->id;

        if (! ($this->options['updateExisting'] ?? false)) {
            return new Product;
        }

        if (! $warehouseId) {
            return new Product;
        }

        return Product::query()
            ->withoutGlobalScopes()
            ->where('warehouse_id', $warehouseId)
            ->where('product_code', $this->data['product_code'] ?? null)
            ->firstOrNew();
    }

    protected function beforeSave(): void
    {
        $warehouseId = $this->options['warehouse_id'] ?? Filament::getTenant()?->id;

        if ($warehouseId) {
            $this->record->warehouse_id = $warehouseId;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
