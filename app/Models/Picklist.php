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
        return $this->hasMany('App\Models\PicklistProduct')->orderBy('barcode');
    }

    public function failedProducts()
    {
        return $this->hasMany('App\Models\PicklistFailedProduct');
    }

    public function productsCombined()
    {
        $products = $this->hasMany('App\Models\PicklistProduct')->orderBy('barcode')->get();

        $array = [];
        foreach ($products as $product) {

            if (isset($array[$product->barcode])) {
                $array[$product->barcode]['quantity'] = 1 + $array[$product->barcode]['quantity'];
                if ($product->scanned) {
                    $array[$product->barcode]['total_quantity_scanned'] = $array[$product->barcode]['total_quantity_scanned'] + 1;
                }
            } else {
                $array[$product->barcode] = $product->toArray();
                $array[$product->barcode]['quantity'] = 1;
                if ($product->scanned) {
                    $array[$product->barcode]['total_quantity_scanned'] = 1;
                } else {
                    $array[$product->barcode]['total_quantity_scanned'] = 0;
                }

            }

        }

        return $array;
    }
}
