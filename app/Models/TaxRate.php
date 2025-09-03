<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'rate'
    ];


        public function warehouse(){
            return $this->belongsTo(Warehouse::class);
        }
        public function products(){
            return $this->hasMany(Product::class);
        }

}
