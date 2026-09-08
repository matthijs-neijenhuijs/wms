<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrderProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Orders\Models\PurchaseOrder;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class PurchaseOrderImportService
{
    public function import(PurchaseOrder $purchaseOrder, TemporaryUploadedFile|UploadedFile|string|null $file): int
    {
        if (! $file) {
            throw ValidationException::withMessages([
                'import_file' => 'Please upload a CSV or Excel file.',
            ]);
        }

        $rows = $this->readRows($file);
        $importedRows = 0;

        foreach ($rows as $row) {
            $importRow = $this->extractImportRow($row);

            if (! $importRow) {
                continue;
            }

            $product = Product::query()
                ->where('warehouse_id', $purchaseOrder->warehouse_id)
                ->where('barcode', $importRow['barcode'])
                ->first();

            for ($index = 0; $index < $importRow['quantity']; $index++) {
                PurchaseOrderProduct::query()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'barcode' => $importRow['barcode'],
                    'reference_code' => $product?->reference_code,
                    'product_title' => $product->name ?? 'Unknown product',
                    'show_for_supplier' => false,
                    'scanned' => false,
                ]);

                $importedRows++;
            }
        }

        if ($importedRows === 0) {
            throw ValidationException::withMessages([
                'import_file' => 'No valid barcode and quantity rows were found in the uploaded file.',
            ]);
        }

        return $importedRows;
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    protected function readRows(TemporaryUploadedFile|UploadedFile|string $file): array
    {
        $path = $file instanceof TemporaryUploadedFile || $file instanceof UploadedFile
            ? $file->getRealPath()
            : $file;

        if (! $path) {
            return [];
        }

        $extension = Str::lower($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile
            ? ($file->getClientOriginalExtension() ?: pathinfo($path, PATHINFO_EXTENSION))
            : pathinfo($path, PATHINFO_EXTENSION));

        return $extension === 'xlsx'
            ? $this->readXlsxRows($path)
            : $this->readCsvRows($path);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $delimiter = $this->detectDelimiter($path);
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null]) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function readXlsxRows(string $path): array
    {
        $reader = new XlsxReader;
        $reader->open($path);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    protected function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ',';
        }

        $firstLine = (string) fgets($handle);
        fclose($handle);

        $delimiters = [',', ';', "\t", '|'];
        $selectedDelimiter = ',';
        $highestCount = 0;

        foreach ($delimiters as $delimiter) {
            $count = substr_count($firstLine, $delimiter);

            if ($count > $highestCount) {
                $highestCount = $count;
                $selectedDelimiter = $delimiter;
            }
        }

        return $selectedDelimiter;
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @return array{barcode: string, quantity: int}|null
     */
    protected function extractImportRow(array $row): ?array
    {
        $values = array_values($row);
        $firstValue = trim((string) ($values[0] ?? ''));
        $secondValue = $values[1] ?? null;

        if ($this->isHeaderRow($firstValue, $secondValue)) {
            return null;
        }

        $barcode = trim((string) ($row['barcode'] ?? $row['ean'] ?? $row['ean_code'] ?? $row['product_barcode'] ?? $values[0] ?? ''));
        $quantityValue = $row['quantity'] ?? $row['qty'] ?? $row['amount'] ?? $values[1] ?? null;
        $quantity = (int) preg_replace('/[^0-9-]/', '', (string) $quantityValue);

        if ($barcode === '' || $quantity < 1) {
            return null;
        }

        return [
            'barcode' => $barcode,
            'quantity' => $quantity,
        ];
    }

    protected function isHeaderRow(string $firstValue, mixed $secondValue): bool
    {
        $normalizedFirst = Str::of($firstValue)->trim()->lower()->value();
        $normalizedSecond = Str::of((string) $secondValue)->trim()->lower()->value();

        return in_array($normalizedFirst, ['barcode', 'ean', 'ean_code', 'product_barcode'], true)
            && in_array($normalizedSecond, ['quantity', 'qty', 'amount'], true);
    }
}
