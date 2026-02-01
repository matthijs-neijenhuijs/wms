<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'reference_code',
        'ean',
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
}
