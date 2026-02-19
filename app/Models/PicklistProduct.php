<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PicklistProduct extends Model
{
    protected $table = 'picklists_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'picklist_id',
        'show_for_supplier',
        'ean_code',
        'reference_code',
        'color',
        'size',
        'product_title',
        'scanned',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
