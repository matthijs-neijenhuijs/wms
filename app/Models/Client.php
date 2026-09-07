<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Client extends Model
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
        'email',
        'vat_number',
        'coc_number',
        'debtor_number',
        'iban_number',
        'comments',
        'company',
        'bill_client_address_id',
        'delivery_client_address_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('client')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Client {$eventName}");
    }

    public function getActivityLogTitle(): string
    {
        return (string) ($this->company ?: $this->email ?: "Client #{$this->getKey()}");
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function addresses()
    {
        return $this->hasMany(ClientAddresses::class);
    }

    public function clientDeliveryAddress()
    {
        return $this->belongsTo(ClientAddresses::class, 'delivery_client_address_id');
    }

    public function clientBillAddress()
    {
        return $this->belongsTo(ClientAddresses::class, 'bill_client_address_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
