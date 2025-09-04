<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'price_with_tax',
        'price_without_tax',
        'total_discount',
        'client_id'

    ];



    public static function boot()
    {
        static::creating(function ($model) {

            if ($model->created_at) {
                $order = Order::where('warehouse_id', '=', $model->warehouse_id)->where('created_at', '>=', Carbon::createFromFormat('Y-m-d H:i:s', $model->created_at)->year)->orderBy('id', 'desc')->first();
                $warehouse = Warehouse::find($model->warehouse_id);
                $prefix = 'ORDER'.strtoupper(substr($warehouse->name, 0, 4));

                if ($order) {
                    $model->generated_year_order_id = $order->generated_year_order_id + 1;
                    $model->generated_custom_order_id = $order->generated_year_order_id + 1;
                    $model->generated_custom_order_id = $prefix.Carbon::createFromFormat('Y-m-d H:i:s', $model->created_at)->format('y').$model->generated_custom_order_id;
                } else {
                    $model->generated_year_order_id = 1;
                    $model->generated_custom_order_id = $prefix.Carbon::createFromFormat('Y-m-d H:i:s', $model->created_at)->format('y').'1';
                }
            } else {
                $order = Order::where('warehouse_id', '=', $model->warehouse_id)->where('created_at', '>=', Carbon::now()->year)->orderBy('id', 'desc')->first();
                $warehouse = Warehouse::find($model->warehouse_id);
                $prefix = 'ORDER'.strtoupper(substr($warehouse->name, 0, 4));

                if ($order) {
                    $model->generated_year_order_id = $order->generated_year_order_id + 1;
                    $model->generated_custom_order_id = $order->generated_year_order_id + 1;
                    $model->generated_custom_order_id = $prefix.Carbon::now()->format('y').$model->generated_custom_order_id;
                } else {
                    $model->generated_year_order_id = 1;
                    $model->generated_custom_order_id = $prefix.Carbon::now()->format('y').'1';
                }
            }


            if ($model->collection_id AND $model->collection) {

                $model->expected_delivery_date = $model->collection->expected_delivery_date;

            }


        });




        parent::boot();
    }


    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }



    public function client(){
        return $this->belongsTo(Client::class);
    }



}
