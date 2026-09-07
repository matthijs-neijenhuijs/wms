<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;
        $client = $order->client;

        return [
            'id' => $order->getAttribute('id'),
            'warehouse_id' => $order->getAttribute('warehouse_id'),
            'client_id' => $order->getAttribute('client_id'),
            'order_status_id' => $order->getAttribute('order_statuses_id'),
            'generated_year_order_id' => $order->getAttribute('generated_year_order_id'),
            'generated_custom_order_id' => $order->getAttribute('generated_custom_order_id'),
            'custom_order_id' => $order->getAttribute('custom_order_id'),
            'discount' => $order->getAttribute('discount'),
            'invoice_name' => $order->getAttribute('invoice_name'),
            'invoice_address' => $order->getAttribute('invoice_address'),
            'invoice_zipcode' => $order->getAttribute('invoice_zipcode'),
            'invoice_region' => $order->getAttribute('invoice_region'),
            'invoice_city' => $order->getAttribute('invoice_city'),
            'invoice_country' => $order->getAttribute('invoice_country'),
            'delivery_name' => $order->getAttribute('delivery_name'),
            'delivery_address' => $order->getAttribute('delivery_address'),
            'delivery_zipcode' => $order->getAttribute('delivery_zipcode'),
            'delivery_region' => $order->getAttribute('delivery_region'),
            'delivery_city' => $order->getAttribute('delivery_city'),
            'delivery_country' => $order->getAttribute('delivery_country'),
            'telephone_number' => $order->getAttribute('telephone_number'),
            'email' => $order->getAttribute('email'),
            'comments' => $order->getAttribute('comments'),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $client?->getAttribute('id'),
                'email' => $client?->getAttribute('email'),
                'company' => $client?->getAttribute('company'),
            ]),
            'items' => OrderProductResource::collection($this->whenLoaded('products')),
            'created_at' => $order->getAttribute('created_at'),
            'updated_at' => $order->getAttribute('updated_at'),
        ];
    }
}
