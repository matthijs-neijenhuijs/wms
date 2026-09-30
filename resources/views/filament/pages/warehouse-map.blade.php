<x-filament-panels::page>
    @php
        $warehouse = $this->getWarehouse();
        $locations = $this->getLocations();
    @endphp

    @if ($warehouse->floor_width === null || $warehouse->floor_height === null)
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Set this warehouse's floor width and height before using the map.
            </p>
            <a
                href="{{ \Modules\Settings\Filament\Resources\Warehouses\WarehouseResource::getUrl('edit', ['record' => $warehouse]) }}"
                class="mt-2 inline-block text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
            >
                Edit warehouse floor dimensions
            </a>
        </div>
    @else
        <div class="flex flex-col gap-6 lg:flex-row">
            <div class="flex-1 overflow-x-auto">
                <canvas
                    id="warehouse-map-canvas"
                    width="960"
                    height="640"
                    style="border:1px solid rgb(209 213 219); max-width:100%; touch-action:none;"
                ></canvas>
            </div>
            <div class="w-full lg:w-64">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-200">Unplaced locations</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Drag a location onto the map to place it.
                </p>
                <ul id="warehouse-map-unplaced-list" class="mt-2 space-y-1"></ul>
            </div>
        </div>

        <script type="application/json" id="warehouse-map-data">
            {!! json_encode([
                'floorWidth' => (float) $warehouse->floor_width,
                'floorHeight' => (float) $warehouse->floor_height,
                'locations' => $locations->map(fn ($location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'rank' => $location->rank,
                    'x' => $location->x === null ? null : (float) $location->x,
                    'y' => $location->y === null ? null : (float) $location->y,
                    'width' => $location->width === null ? null : (float) $location->width,
                    'height' => $location->height === null ? null : (float) $location->height,
                ])->values(),
            ]) !!}
        </script>

        @vite('resources/js/warehouse-map.js')
    @endif
</x-filament-panels::page>
