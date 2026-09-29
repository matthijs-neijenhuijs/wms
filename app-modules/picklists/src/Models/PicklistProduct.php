<?php

declare(strict_types=1);

namespace Modules\Picklists\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Orders\Models\Order;
use Modules\Products\Models\StockLocation;

class PicklistProduct extends Model
{
    protected $table = 'picklists_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'picklist_id',
        'stock_location_id',
        'show_for_supplier',
        'barcode',
        'reference_code',
        'color',
        'size',
        'product_title',
        'scanned',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<StockLocation, $this>
     */
    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }
}
