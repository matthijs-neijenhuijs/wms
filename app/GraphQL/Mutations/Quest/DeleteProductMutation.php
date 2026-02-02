<?php

namespace App\GraphQL\Mutations\Quest;

use App\Models\Product;
use GraphQL\Type\Definition\Type;
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

    public function resolve($root, $args)
    {
        $student = Product::findOrFail($args['id']);

        return $student->delete() ? true : false;
    }
}
