<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Brands\Models\Brand;
use Modules\Products\Models\Product;
use Modules\Products\Models\ProductCategory;
use Modules\Products\Models\StockProduct;
use Modules\Settings\Models\VatRate;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'active' => $this->active,
            'name' => $this->name,
            'product_code' => $this->product_code,
            'reference_code' => $this->reference_code,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'stock_unlimited' => $this->stock_unlimited,
            'weight' => $this->weight,
            'height' => $this->height,
            'length' => $this->length,
            'hs_code' => $this->hs_code,
            'country_of_origin' => $this->country_of_origin,
            'description' => $this->description,
            'brand' => $this->whenLoaded('brand', function (): ?array {
                $brand = $this->resource->brand;

                if (! $brand instanceof Brand) {
                    return null;
                }

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                ];
            }),
            'category' => $this->whenLoaded('productCategory', function (): ?array {
                $category = $this->resource->productCategory;

                if (! $category instanceof ProductCategory) {
                    return null;
                }

                return [
                    'id' => $category->id,
                    'name' => $category->getAttribute('name'),
                ];
            }),
            'vat_rate' => $this->whenLoaded('vatRate', function (): ?array {
                $vatRate = $this->resource->vatRate;

                if (! $vatRate instanceof VatRate) {
                    return null;
                }

                return [
                    'id' => $vatRate->id,
                    'name' => $vatRate->name,
                    'rate' => $vatRate->rate,
                ];
            }),
            'stock' => $this->whenLoaded('stockProduct', function (): ?array {
                $stock = $this->resource->stockProduct;

                if (! $stock instanceof StockProduct) {
                    return null;
                }

                return [
                    'quantity_on_stock' => $stock->on_stock_quantity,
                    'reserved_quantity' => $stock->reserved_quantity,
                    'reserved_on_picklists' => $stock->reserved_on_picklists,
                    'free_on_stock_quantity' => $stock->free_on_stock_quantity,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
