<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Warehouse;
use App\Services\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Orders\Models\Order;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouse = $this->resolveWarehouse($request);

        $orders = Order::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['products', 'client'])
            ->paginate(50);

        return OrderResource::collection($orders);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): OrderResource
    {
        $warehouse = $this->resolveWarehouse($request);

        $order = Order::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['products', 'client'])
            ->findOrFail($id);

        return new OrderResource($order);
    }

    /**
     * Store a newly created resource.
     */
    public function store(StoreOrderRequest $request, OrderCreationService $service): JsonResponse
    {
        $warehouse = $this->resolveWarehouse($request);

        $order = $service->create($warehouse, $request->validated());

        $order->load(['products', 'client']);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode($order->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    private function resolveWarehouse(Request $request): Warehouse
    {
        $warehouse = $request->attributes->get('warehouse');

        if (! $warehouse instanceof Warehouse) {
            abort(401, 'Unauthorized.');
        }

        return $warehouse;
    }
}
