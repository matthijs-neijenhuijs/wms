<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subdomain_id' => $this->subdomain_id,
            'name' => $this->name,
            'currency' => $this->currency,
            'order_statuses_id_completed_picklist' => $this->order_statuses_id_completed_picklist,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
