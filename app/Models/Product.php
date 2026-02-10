<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use BelongsToWarehouse;

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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')->withTimestamps();
    }

    public function stockProduct(): HasOne
    {
        return $this->hasOne(StockProduct::class);
    }
}
