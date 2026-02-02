<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }
}
