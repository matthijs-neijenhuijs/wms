<?php

declare(strict_types=1);

namespace Modules\Orders\Models;

use App\Models\Concerns\BelongsToWarehouse;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToWarehouse;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'completed',
        'processed',
        'comments',
        'expected_delivery_date',
        'generated_year_purchase_order_id',
        'generated_custom_purchase_order_id',
    ];

    public static function getSearchableSettings(): array
    {
        return [
            'filterableAttributes' => [
                'warehouse_id',
                'completed',
                'processed',
            ],
            'sortableAttributes' => [
                'created_at',
                'updated_at',
                'expected_delivery_date',
            ],
            'searchableAttributes' => [
                'generated_custom_purchase_order_id',
                'comments',
            ],
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PurchaseOrderProduct::class)->orderBy('barcode');
    }

    public function failedProducts(): HasMany
    {
        return $this->hasMany(PurchaseOrderFailedProduct::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'generated_custom_purchase_order_id' => $this->generated_custom_purchase_order_id,
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'completed' => (bool) $this->completed,
            'processed' => (bool) $this->processed,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function casts(): array
    {
        return [
            'expected_delivery_date' => 'date',
            'completed' => 'boolean',
            'processed' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
