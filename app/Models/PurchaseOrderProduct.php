<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderProduct extends Model
{
    protected $table = 'purchase_orders_products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'show_for_supplier',
        'barcode',
        'reference_code',
        'color',
        'size',
        'product_title',
        'scanned',
    ];

    protected function casts(): array
    {
        return [
            'show_for_supplier' => 'boolean',
            'scanned' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
