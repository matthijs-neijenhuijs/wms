<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Models\OrderStatus;

/** @mixin OrderStatus */
class OrderStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'name' => $this->name,
            'color' => $this->color,
            'generate_picklist' => $this->generate_picklist,
            'reserve_stock' => $this->reserve_stock,
            'reduce_stock' => $this->reduce_stock,
            'concepted' => $this->concepted,
            'completed' => $this->completed,
            'on_hold' => $this->on_hold,
            'delivered' => $this->delivered,
            'cancelled' => $this->cancelled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
