<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderProductResource extends JsonResource
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
            'product_id' => $this->product_id,
            'vat_rate_id' => $this->vat_rate_id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'vat_rate' => $this->vat_rate,
            'reference_code' => $this->reference_code,
            'barcode' => $this->barcode,
            'weight' => $this->weight,
        ];
    }
}
