<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeyResource extends JsonResource
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
            'is_active' => $this->is_active,
            'last_used_at' => $this->last_used_at,
            'expires_at' => $this->expires_at,
            'allowed_ips' => $this->allowed_ips,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
