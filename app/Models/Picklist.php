<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Illuminate\Database\Eloquent\Model;

class Picklist extends Model
{
    use BelongsToWarehouse;

    protected $fillable = ['id', 'order_id', 'warehouse_id', 'completed', 'comments', 'generated_custom_picklist_id', 'back_order'];

    public function productsQuantity()
    {
        return $this->hasMany('App\Models\PicklistProduct')->get()->sum('scanned');
    }

    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function products()
    {
        return $this->hasMany('App\Models\PicklistProduct')->orderBy('ean_code');
    }

    public function failedProducts()
    {
        return $this->hasMany('App\Models\PicklistFailedProduct');
    }

    public function productsCombined()
    {
        $products = $this->hasMany('App\Models\PicklistProduct')->orderBy('ean_code')->get();

        $array = [];
        foreach ($products as $product) {

            if (isset($array[$product->ean_code])) {
                $array[$product->ean_code]['quantity'] = 1 + $array[$product->ean_code]['quantity'];
                if ($product->scanned) {
                    $array[$product->ean_code]['total_quantity_scanned'] = $array[$product->ean_code]['total_quantity_scanned'] + 1;
                }
            } else {
                $array[$product->ean_code] = $product->toArray();
                $array[$product->ean_code]['quantity'] = 1;
                if ($product->scanned) {
                    $array[$product->ean_code]['total_quantity_scanned'] = 1;
                } else {
                    $array[$product->ean_code]['total_quantity_scanned'] = 0;
                }

            }

        }

        return $array;
    }
}
