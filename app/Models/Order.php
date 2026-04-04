<?php

namespace App\Models;

use AlizHarb\ActivityLog\Contracts\HasActivityLogTitle;
use App\Models\Concerns\BelongsToWarehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model implements HasActivityLogTitle
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
        'client_id',
        'order_statuses_id',
        'generated_year_order_id',
        'generated_custom_order_id',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('order')
            ->logOnly([
                'warehouse_id',
                'client_id',
                'order_statuses_id',
                'generated_year_order_id',
                'generated_custom_order_id',
                'discount',
                'custom_order_id',
                'completed',
                'picked',
                'cancelled',
                'delivered',
                'on_hold',
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
                'telephone_number',
                'email',
                'comments',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Order {$eventName}");
    }

    public function getActivityLogTitle(): string
    {
        return (string) ($this->generated_custom_order_id ?: $this->custom_order_id ?: "Order #{$this->getKey()}");
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'order_statuses_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
