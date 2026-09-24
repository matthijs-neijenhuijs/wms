<?php

declare(strict_types=1);

namespace Modules\Orders\Models;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasColor, HasLabel
{
    case Concept = 'concept';
    case Purchased = 'purchased';
    case Received = 'received';
    case Scanned = 'scanned';
    case Processed = 'processed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Concept => 'Concept',
            self::Purchased => 'Purchased',
            self::Received => 'Received',
            self::Scanned => 'Scanned',
            self::Processed => 'Processed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Concept => 'gray',
            self::Purchased => 'warning',
            self::Received => 'info',
            self::Scanned => 'primary',
            self::Processed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Whether purchase orders in this status count as incoming stock for the
     * deferral algorithm (PurchaseOrder::incomingBatchesForProduct()) and the
     * product/stock "incoming stock" UI. Processed is excluded because that
     * stock has already become real on_stock_quantity; Concept and Cancelled
     * never count.
     */
    public function isDeferralEligible(): bool
    {
        return in_array($this, [self::Purchased, self::Received, self::Scanned], true);
    }

    /**
     * Closed transition table for the purchase order lifecycle:
     * concept -> purchased -> received -> scanned -> processed, with
     * cancellation only offered from concept/purchased. Purchased -> Scanned
     * is also allowed directly, since scanning can complete before anyone
     * clicks "Mark Received".
     *
     * @return list<self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Concept => [self::Purchased, self::Cancelled],
            self::Purchased => [self::Received, self::Scanned, self::Cancelled],
            self::Received => [self::Scanned],
            self::Scanned => [self::Processed],
            self::Processed, self::Cancelled => [],
        };
    }
}
