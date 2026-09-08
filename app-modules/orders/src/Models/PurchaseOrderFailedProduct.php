<?php

declare(strict_types=1);

namespace Modules\Orders\Models;

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

    /**
     * @return array{filterableAttributes: list<string>, sortableAttributes: list<string>, searchableAttributes: list<string>}
     */
    public static function getSearchableSettings(): array
    {
        return [
            'filterableAttributes' => [
                'purchase_order_id',
            ],
            'sortableAttributes' => [
                'created_at',
                'updated_at',
            ],
            'searchableAttributes' => [
                'barcode',
                'reference_code',
                'color',
                'size',
                'product_title',
            ],
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'barcode' => $this->barcode,
            'reference_code' => $this->reference_code,
            'total_quantity_scanned' => $this->total_quantity_scanned,
            'color' => $this->color,
            'size' => $this->size,
            'product_title' => $this->product_title,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function casts(): array
    {
        return [
            'total_quantity_scanned' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
