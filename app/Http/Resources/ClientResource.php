<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use App\Models\ClientAddresses;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Client */
class ClientResource extends JsonResource
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
            'email' => $this->email,
            'company' => $this->company,
            'vat_number' => $this->vat_number,
            'coc_number' => $this->coc_number,
            'debtor_number' => $this->debtor_number,
            'iban_number' => $this->iban_number,
            'comments' => $this->comments,
            'delivery_address' => $this->whenLoaded('clientDeliveryAddress', function (): ?array {
                $deliveryAddress = $this->resource->clientDeliveryAddress;

                if (! $deliveryAddress instanceof ClientAddresses) {
                    return null;
                }

                return [
                    'id' => $deliveryAddress->id,
                    'name' => $deliveryAddress->name,
                    'company' => $deliveryAddress->company,
                    'address' => $deliveryAddress->address,
                    'zipcode' => $deliveryAddress->zipcode,
                    'city' => $deliveryAddress->city,
                    'region' => $deliveryAddress->region,
                    'country' => $deliveryAddress->country,
                    'telephone_number' => $deliveryAddress->telephone_number,
                ];
            }),
            'bill_address' => $this->whenLoaded('clientBillAddress', function (): ?array {
                $billAddress = $this->resource->clientBillAddress;

                if (! $billAddress instanceof ClientAddresses) {
                    return null;
                }

                return [
                    'id' => $billAddress->id,
                    'name' => $billAddress->name,
                    'company' => $billAddress->company,
                    'address' => $billAddress->address,
                    'zipcode' => $billAddress->zipcode,
                    'city' => $billAddress->city,
                    'region' => $billAddress->region,
                    'country' => $billAddress->country,
                    'telephone_number' => $billAddress->telephone_number,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
