<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use AlizHarb\ActivityLog\Contracts\HasActivityLogTitle;
use Database\Factories\StockProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class StockProduct extends Model implements HasActivityLogTitle
{
    /** @use HasFactory<StockProductFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'on_stock_quantity',
        'reserved_quantity',
        'reserved_on_picklists',
        'free_on_stock_quantity',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('stock_product')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Stock {$eventName}");
    }

    public function getActivityLogTitle(): string
    {
        $product = $this->product;

        if ($product !== null) {
            $productName = $product->name ?? $product->reference_code;
        } else {
            $productName = null;
        }

        return (string) ($productName ? "Stock for {$productName}" : "Stock #{$this->getKey()}");
    }

    public function getActivityLogParent(): ?Product
    {
        return $this->product;
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'on_stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'reserved_on_picklists' => 'integer',
            'free_on_stock_quantity' => 'integer',
        ];
    }
}
