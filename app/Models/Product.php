<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends Model
{
    use BelongsToWarehouse;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'active',
        'reference_code',
        'price',
        'product_code',
        'stock_unlimited',
        'barcode',
        'name',
        'weight',
        'height',
        'length',
        'hs_code',
        'country_of_origin',
        'description',
        'vat_rate_id',
        'brand_id',
        'product_category_id',
        'image',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('product')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Product {$eventName}");
    }

    public function getActivityLogTitle(): string
    {
        return (string) ($this->name ?: $this->reference_code ?: "Product #{$this->getKey()}");
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<VatRate, $this>
     */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * @return HasMany<ProductAttribute, $this>
     */
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * @return BelongsToMany<Attribute, $this, Pivot, 'pivot'>
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')->withTimestamps();
    }

    /**
     * @return HasOne<StockProduct, $this>
     */
    public function stockProduct(): HasOne
    {
        return $this->hasOne(StockProduct::class);
    }
}
