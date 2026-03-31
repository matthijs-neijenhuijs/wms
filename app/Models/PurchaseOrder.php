<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
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
}
