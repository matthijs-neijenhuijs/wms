<?php

declare(strict_types=1);

namespace Modules\Orders\Models;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatus extends Model
{
    protected $table = 'order_statuses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'name',
        'color',
        'generate_picklist',
        'reserve_stock',
        'reduce_stock',
        'concepted',
        'completed',
        'on_hold',
        'delivered',
        'cancelled',
    ];

    public static function getSearchableSettings(): array
    {
        return [
            'filterableAttributes' => [
                'warehouse_id',
                'generate_picklist',
                'reserve_stock',
                'reduce_stock',
                'concepted',
                'completed',
                'on_hold',
                'delivered',
                'cancelled',
            ],
            'sortableAttributes' => [
                'created_at',
                'updated_at',
            ],
            'searchableAttributes' => [
                'name',
                'color',
            ],
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Determine if orders with this status can be edited
     */
    public function canEditOrder(): bool
    {
        // Once on hold, delivered, or cancelled, orders cannot be edited
        return ! $this->on_hold
            && ! $this->delivered
            && ! $this->cancelled;
    }

    /**
     * Determine if orders with this status can be deleted
     */
    public function canDeleteOrder(): bool
    {
        // Only concept and expected orders can be deleted
        return $this->concepted || $this->generate_picklist;
    }

    /**
     * Determine if products can be added/removed for orders with this status
     */
    public function canModifyProducts(): bool
    {
        // Same as edit logic - cannot modify products once shipped/delivered/cancelled
        return $this->canEditOrder();
    }

    /**
     * Determine if the order status can be changed
     */
    public function canChangeStatus(): bool
    {
        // Once delivered or cancelled, status cannot be changed
        return ! $this->delivered && ! $this->cancelled;
    }

    /**
     * Determine if the order is in a "locked" state
     */
    public function isLocked(): bool
    {
        return ! $this->canEditOrder();
    }

    /**
     * Determine if the order requires confirmation
     */
    public function requiresConfirmation(): bool
    {
        return $this->generate_picklist || $this->concepted;
    }

    /**
     * Determine if the order is in a final state
     */
    public function isFinalState(): bool
    {
        return $this->delivered || $this->cancelled;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'name' => $this->name,
            'color' => $this->color,
            'generate_picklist' => (bool) $this->generate_picklist,
            'reserve_stock' => (bool) $this->reserve_stock,
            'reduce_stock' => (bool) $this->reduce_stock,
            'concepted' => (bool) $this->concepted,
            'completed' => (bool) $this->completed,
            'on_hold' => (bool) $this->on_hold,
            'delivered' => (bool) $this->delivered,
            'cancelled' => (bool) $this->cancelled,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function casts(): array
    {
        return [
            'generate_picklist' => 'boolean',
            'reserve_stock' => 'boolean',
            'reduce_stock' => 'boolean',
            'concepted' => 'boolean',
            'completed' => 'boolean',
            'on_hold' => 'boolean',
            'delivered' => 'boolean',
            'cancelled' => 'boolean',
        ];
    }
}
