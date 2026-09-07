<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subdomain extends Model
{
    protected $fillable = [
        'subdomain',
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'subdomain_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'subdomain_id');
    }
}
