<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Clients\Models\Client;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouse = $this->resolveWarehouse($request);

        $clients = Client::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['clientDeliveryAddress', 'clientBillAddress'])
            ->paginate(50);

        return ClientResource::collection($clients);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): ClientResource
    {
        $warehouse = $this->resolveWarehouse($request);

        $client = Client::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['clientDeliveryAddress', 'clientBillAddress'])
            ->findOrFail($id);

        return new ClientResource($client);
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
