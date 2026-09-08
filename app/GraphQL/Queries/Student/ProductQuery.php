<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Student;

use GraphQL\Type\Definition\Type;
use Modules\Products\Models\Product;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ProductQuery extends Query
{
    protected $attributes = [
        'name' => 'product',
    ];

    public function type(): Type
    {
        return GraphQL::type('Product');
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::int(),
                'rules' => ['required'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function resolve(mixed $root, array $args): Product
    {
        return Product::query()->findOrFail((int) $args['id']);
    }
}
