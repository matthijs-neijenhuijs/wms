<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    private function resolveWarehouse(Request $request): Warehouse
    {
        $warehouse = $request->attributes->get('warehouse');

        if (! $warehouse instanceof Warehouse) {
            abort(401, 'Unauthorized.');
        }

        return $warehouse;
    }
}
