<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ApiKey extends Model
{
    use BelongsToWarehouse;

    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'name',
        'key_hash',
        'is_active',
        'last_used_at',
        'expires_at',
        'allowed_ips',
    ];

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('api_key')
            ->logOnly(['name', 'is_active', 'last_used_at', 'expires_at', 'allowed_ips'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "API key {$eventName}");
    }

    public function getActivityLogTitle(): string
    {
        return (string) ($this->name ?: "API key #{$this->getKey()}");
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'allowed_ips' => 'array',
        ];
    }
}
