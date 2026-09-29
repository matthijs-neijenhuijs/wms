<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use App\Models\Concerns\BelongsToWarehouse;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class StockLocation extends Model
{
    use BelongsToWarehouse;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'parent_id',
        'name',
        'rank',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockLocation $location): void {
            if ($location->rank !== null) {
                return;
            }

            $location->rank = ((int) static::query()
                ->where('warehouse_id', $location->warehouse_id)
                ->max('rank')) + 1;
        });
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<StockLocation, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'parent_id');
    }

    /**
     * @return HasMany<StockLocation, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(StockLocation::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<Product, $this, Pivot, 'pivot'>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'stock_location_product')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('stock_location')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Stock location {$eventName}");
    }

    public function getActivityLogTitle(): string
    {
        return (string) ($this->name ?: "Stock location #{$this->getKey()}");
    }

    /**
     * @return array{filterableAttributes: list<string>, sortableAttributes: list<string>, searchableAttributes: list<string>}
     */
    public static function getSearchableSettings(): array
    {
        return [
            'filterableAttributes' => [
                'warehouse_id',
                'parent_id',
            ],
            'sortableAttributes' => [
                'rank',
                'created_at',
                'updated_at',
            ],
            'searchableAttributes' => [
                'name',
            ],
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'rank' => $this->rank,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
        ];
    }
}
