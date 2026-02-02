<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientAddresses extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'client_id',
        'lastname',
        'street',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function isBillingAddress()
    {
        return $this->belongsTo(Client::class, 'id', 'bill_client_address_id');
    }

    public function isDeliveryAddress()
    {
        return $this->belongsTo(Client::class, 'id', 'delivery_client_address_id');
    }
}
