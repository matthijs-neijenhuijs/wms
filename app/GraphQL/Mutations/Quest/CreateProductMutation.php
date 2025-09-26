<?php


namespace App\GraphQL\Mutations\Quest;

use App\Models\Product;
use Rebing\GraphQL\Support\Mutation;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateProductMutation extends Mutation
{
    protected $attributes = [
        'name' => 'createProduct',
        'description' => 'Creates a Product'
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
                'type' =>  Type::nonNull(Type::string()),
            ]
        ];
    }

    public function resolve($root, $args)
    {
        $student = new Product();
        $student->fill($args);
        $student->save();

        return $student;
    }
}
