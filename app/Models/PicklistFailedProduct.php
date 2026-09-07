<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PicklistFailedProduct extends Model
{
    protected $table = 'picklist_failed_products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'picklist_id',
        'barcode',
        'reference_code',
        'total_quantity_scanned',
        'color',
        'size',
        'product_title',
    ];
}
