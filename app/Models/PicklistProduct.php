<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PicklistProduct extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'name',
        'amount',
        'reference_code',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
