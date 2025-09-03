<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'active',
        'email',
        'bill_client_address_id',
        'delivery_client_address_id'
    ];

    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }
    public function addresses(){
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


    



}
