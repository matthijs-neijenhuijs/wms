<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToWarehouse;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'order_statuses_id',
        'discount',
        'custom_order_id',
        'invoice_name',
        'invoice_address',
        'invoice_zipcode',
        'invoice_region',
        'invoice_city',
        'invoice_country',
        'delivery_name',
        'delivery_address',
        'delivery_zipcode',
        'delivery_region',
        'delivery_city',
        'delivery_country',
        'telephone',
        'email',
        'comments',
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

            if ($model->collection_id and $model->collection) {

                $model->expected_delivery_date = $model->collection->expected_delivery_date;

            }

        });

        parent::boot();
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'order_statuses_id');
    }

    public function products()
    {
        return $this->hasMany(OrderProduct::class);
    }
}
