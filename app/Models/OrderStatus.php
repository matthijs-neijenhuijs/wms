<?php

namespace App\Models;

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
        'order_is_expected',
        'order_is_concept',
        'order_is_confirmed',
        'order_is_shipped',
        'order_is_delivered',
        'order_is_cancelled',
    ];

    protected function casts(): array
    {
        return [
            'order_is_expected' => 'boolean',
            'order_is_concept' => 'boolean',
            'order_is_confirmed' => 'boolean',
            'order_is_shipped' => 'boolean',
            'order_is_delivered' => 'boolean',
            'order_is_cancelled' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Determine if orders with this status can be edited
     */
    public function canEditOrder(): bool
    {
        // Once shipped, delivered, or cancelled, orders cannot be edited
        return ! $this->order_is_shipped
            && ! $this->order_is_delivered
            && ! $this->order_is_cancelled;
    }

    /**
     * Determine if orders with this status can be deleted
     */
    public function canDeleteOrder(): bool
    {
        // Only concept and expected orders can be deleted
        return $this->order_is_concept || $this->order_is_expected;
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
        return ! $this->order_is_delivered && ! $this->order_is_cancelled;
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
        return $this->order_is_expected || $this->order_is_concept;
    }

    /**
     * Determine if the order is in a final state
     */
    public function isFinalState(): bool
    {
        return $this->order_is_delivered || $this->order_is_cancelled;
    }
}
