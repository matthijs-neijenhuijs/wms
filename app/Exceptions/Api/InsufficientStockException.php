<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InsufficientStockException extends Exception
{
    /**
     * @param  array<int, array{product_id: int, requested: int, available: int}>  $items
     */
    public function __construct(private readonly array $items)
    {
        parent::__construct('Insufficient stock for one or more items.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'items' => $this->items,
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
