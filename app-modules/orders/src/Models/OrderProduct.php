<?php

declare(strict_types=1);

namespace Modules\Orders\Models;

use App\Models\Product;
use App\Models\VatRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProduct extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'vat_rate_id',
        'vat_rate',
        'name',
        'quantity',
        'amount',
        'reference_code',
        'barcode',
        'price',
        'weight',
        'product_attribute_title',
    ];

    /**
     * @return array{filterableAttributes: list<string>, sortableAttributes: list<string>, searchableAttributes: list<string>}
     */
    public static function getSearchableSettings(): array
    {
        return [
            'filterableAttributes' => [
                'order_id',
                'product_id',
                'vat_rate_id',
            ],
            'sortableAttributes' => [
                'created_at',
                'updated_at',
            ],
            'searchableAttributes' => [
                'name',
                'reference_code',
                'barcode',
                'product_attribute_title',
            ],
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<VatRate, $this>
     */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'vat_rate_id' => $this->vat_rate_id,
            'name' => $this->name,
            'reference_code' => $this->reference_code,
            'barcode' => $this->barcode,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'weight' => 'integer',
            'vat_rate' => 'decimal:4',
            'price' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
