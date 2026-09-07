<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use AgeekDev\Barcode\Facades\Barcode;
use App\Models\Product;
use App\Models\Warehouse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductBarcodeDownloadController extends Controller
{
    public function __invoke(string $tenant, Product $product): StreamedResponse
    {
        $warehouse = Warehouse::query()
            ->where('name', $tenant)
            ->firstOrFail();

        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->warehouses()->whereKey($warehouse->id)->exists(), 403);
        abort_unless((int) $product->warehouse_id === (int) $warehouse->id, 404);

        $barcodeValue = (string) ($product->barcode ?: $product->product_code ?: $product->id);
        $barcodePng = Barcode::imageType('png')->generate($barcodeValue);

        return response()->streamDownload(function () use ($barcodePng): void {
            echo $barcodePng;
        }, 'barcode-'.$barcodeValue.'.png', [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="barcode-'.$barcodeValue.'.png"',
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'public',
        ]);
    }
}
