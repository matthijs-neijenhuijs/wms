<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subdomain_id',
        'name',
    ];

    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class, 'subdomain_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_warehouses');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Warehouse $warehouse) {
            if (! $warehouse->subdomain_id && app()->has('current_subdomain')) {
                $warehouse->subdomain_id = app('current_subdomain')->id;
            }
        });
    }
}
