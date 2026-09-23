<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Orders\Models\OrderStatus;
use Modules\Users\Models\User;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Warehouse extends Model
{
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subdomain_id',
        'name',
        'currency',
        'order_statuses_id_completed_picklist',
    ];

    /**
     * @return BelongsTo<Subdomain, $this>
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class, 'subdomain_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_warehouses');
    }

    /**
     * @return BelongsTo<OrderStatus, $this>
     */
    public function completedPicklistOrderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'order_statuses_id_completed_picklist');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('warehouse')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Warehouse {$eventName}");
    }

    public function getActivityLogTitle(): string
    {
        return (string) ($this->name ?: "Warehouse #{$this->getKey()}");
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
