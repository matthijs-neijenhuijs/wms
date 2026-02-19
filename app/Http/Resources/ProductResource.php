<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
            ]),
            'category' => $this->whenLoaded('productCategory', fn () => [
                'id' => $this->productCategory?->id,
                'name' => $this->productCategory?->name,
            ]),
            'vat_rate' => $this->whenLoaded('vatRate', fn () => [
                'id' => $this->vatRate?->id,
                'name' => $this->vatRate?->name,
                'rate' => $this->vatRate?->rate,
            ]),
            'stock' => $this->whenLoaded('stockProduct', fn () => [
                'quantity_on_stock' => $this->stockProduct?->on_stock_quantity,
                'reserved_quantity' => $this->stockProduct?->reserved_quantity,
                'reserved_on_picklists' => $this->stockProduct?->reserved_on_picklists,
                'free_on_stock_quantity' => $this->stockProduct?->free_on_stock_quantity,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
