<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Picklists\Models\Picklist;

/** @mixin Picklist */
class PicklistResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'order_id' => $this->order_id,
            'completed' => $this->completed,
            'back_order' => $this->back_order,
            'comments' => $this->comments,
            'generated_custom_picklist_id' => $this->generated_custom_picklist_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
