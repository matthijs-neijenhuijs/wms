<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Products\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouse = $this->resolveWarehouse($request);

        $products = Product::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['stockProduct', 'brand', 'productCategory', 'vatRate'])
            ->paginate(50);

        return ProductResource::collection($products);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): ProductResource
    {
        $warehouse = $this->resolveWarehouse($request);

        $product = Product::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['stockProduct', 'brand', 'productCategory', 'vatRate'])
            ->findOrFail($id);

        return new ProductResource($product);
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
