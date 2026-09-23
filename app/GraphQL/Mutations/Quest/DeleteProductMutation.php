<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Quest;

use GraphQL\Type\Definition\Type;
use Modules\Products\Models\Product;
use Rebing\GraphQL\Support\Mutation;

class DeleteProductMutation extends Mutation
{
    protected $attributes = [
        'name' => 'deleteProduct',
        'description' => 'Deletes a Product',
    ];

    public function type(): Type
    {
        return Type::boolean();
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::int()),
                'rules' => ['exists:quests'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function resolve(mixed $root, array $args): bool
    {
        $product = Product::query()->findOrFail((int) $args['id']);

        return (bool) $product->delete();
    }
}
