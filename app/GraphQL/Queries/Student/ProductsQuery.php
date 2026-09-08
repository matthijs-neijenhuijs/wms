<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Student;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Modules\Products\Models\Product;
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
     * @param  array<string, mixed>  $args
     * @return Collection<int, Product>
     */
    public function resolve(mixed $root, array $args): Collection
    {
        return Product::query()->get();
    }
}
