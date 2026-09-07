<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Product;
use App\Models\StockProduct;

return [

    'provider' => env('AI_CHARTS_PROVIDER', 'mistral'),

    'allowed_models' => [
        'products' => [
            'model' => Product::class,
            'description' => 'Products model.',
        ],
        'orders' => [
            'model' => Order::class,
            'description' => 'Orders model.',
        ],
        'stock_products' => [
            'model' => StockProduct::class,
            'description' => 'Stock values per product.',
        ],
    ],

];
