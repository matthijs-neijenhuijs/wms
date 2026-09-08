<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Models\PurchaseOrder;

/** @mixin PurchaseOrder */
class PurchaseOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'completed' => $this->completed,
            'processed' => $this->processed,
            'comments' => $this->comments,
            'expected_delivery_date' => $this->expected_delivery_date,
            'generated_year_purchase_order_id' => $this->generated_year_purchase_order_id,
            'generated_custom_purchase_order_id' => $this->generated_custom_purchase_order_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
