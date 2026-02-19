<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use BelongsToWarehouse;

    /** @use HasFactory<\Database\Factories\ApiKeyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'name',
        'key_hash',
        'is_active',
        'last_used_at',
        'expires_at',
        'allowed_ips',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'allowed_ips' => 'array',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
