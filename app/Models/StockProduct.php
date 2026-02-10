<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockProduct extends Model
{
    /** @use HasFactory<\Database\Factories\StockProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'on_stock_quantity',
        'reserved_quantity',
        'reserved_on_picklists',
        'free_on_stock_quantity',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'on_stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'reserved_on_picklists' => 'integer',
            'free_on_stock_quantity' => 'integer',
        ];
    }
}
