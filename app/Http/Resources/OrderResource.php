<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'warehouse_id' => $this->warehouse_id,
            'client_id' => $this->client_id,
            'order_status_id' => $this->order_statuses_id,
            'generated_year_order_id' => $this->generated_year_order_id,
            'generated_custom_order_id' => $this->generated_custom_order_id,
            'custom_order_id' => $this->custom_order_id,
            'discount' => $this->discount,
            'invoice_name' => $this->invoice_name,
            'invoice_address' => $this->invoice_address,
            'invoice_zipcode' => $this->invoice_zipcode,
            'invoice_region' => $this->invoice_region,
            'invoice_city' => $this->invoice_city,
            'invoice_country' => $this->invoice_country,
            'delivery_name' => $this->delivery_name,
            'delivery_address' => $this->delivery_address,
            'delivery_zipcode' => $this->delivery_zipcode,
            'delivery_region' => $this->delivery_region,
            'delivery_city' => $this->delivery_city,
            'delivery_country' => $this->delivery_country,
            'telephone_number' => $this->telephone_number,
            'email' => $this->email,
            'comments' => $this->comments,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client?->id,
                'email' => $this->client?->email,
                'company' => $this->client?->company,
            ]),
            'items' => OrderProductResource::collection($this->whenLoaded('products')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
