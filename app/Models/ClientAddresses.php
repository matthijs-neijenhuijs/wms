<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAddresses extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'company',
        'gender',
        'initials',
        'name',
        'address',
        'zipcode',
        'city',
        'region',
        'country',
        'telephone_number',
    ];

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function isBillingAddress(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id', 'bill_client_address_id');
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function isDeliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id', 'delivery_client_address_id');
    }
}
