<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'delivery_address' => $this->whenLoaded('clientDeliveryAddress', fn () => [
                'id' => $this->clientDeliveryAddress?->id,
                'name' => $this->clientDeliveryAddress?->name,
                'company' => $this->clientDeliveryAddress?->company,
                'address' => $this->clientDeliveryAddress?->address,
                'zipcode' => $this->clientDeliveryAddress?->zipcode,
                'city' => $this->clientDeliveryAddress?->city,
                'region' => $this->clientDeliveryAddress?->region,
                'country' => $this->clientDeliveryAddress?->country,
                'telephone_number' => $this->clientDeliveryAddress?->telephone_number,
            ]),
            'bill_address' => $this->whenLoaded('clientBillAddress', fn () => [
                'id' => $this->clientBillAddress?->id,
                'name' => $this->clientBillAddress?->name,
                'company' => $this->clientBillAddress?->company,
                'address' => $this->clientBillAddress?->address,
                'zipcode' => $this->clientBillAddress?->zipcode,
                'city' => $this->clientBillAddress?->city,
                'region' => $this->clientBillAddress?->region,
                'country' => $this->clientBillAddress?->country,
                'telephone_number' => $this->clientBillAddress?->telephone_number,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
