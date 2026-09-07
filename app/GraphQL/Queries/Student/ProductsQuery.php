<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Student;

use App\Models\Product;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ProductsQuery extends Query
{
    protected $attributes = [
        'name' => 'products',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Product'));
    }

    /**
     * @param mixed $root
     * @param array<string, mixed> $args
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function resolve(mixed $root, array $args): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()->get();
    }
}
