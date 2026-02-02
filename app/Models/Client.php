<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
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
