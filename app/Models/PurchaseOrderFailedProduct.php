<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderFailedProduct extends Model
{
    protected $table = 'purchase_order_failed_products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'barcode',
        'reference_code',
        'total_quantity_scanned',
        'color',
        'size',
        'product_title',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
