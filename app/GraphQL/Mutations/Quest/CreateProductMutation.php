<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Quest;

use App\Models\Product;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class CreateProductMutation extends Mutation
{
    protected $attributes = [
        'name' => 'createProduct',
        'description' => 'Creates a Product',
    ];

    public function type(): Type
    {
        return GraphQL::type('Product');
    }

    public function args(): array
    {
        return [
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string, mixed> $args
     */
    public function resolve(mixed $root, array $args): Product
    {
        $product = new Product;
        $product->fill($args);
        $product->save();

        return $product;
    }
}
