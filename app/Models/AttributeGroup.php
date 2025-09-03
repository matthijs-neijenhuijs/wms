<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeGroup extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name'
    ];


        public function warehouse(){
            return $this->belongsTo(Warehouse::class);
        }
        public function attributes(){
            return $this->hasMany(Attribute::class);
        }

}
