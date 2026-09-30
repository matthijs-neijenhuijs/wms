<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Warehouse;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\Products\Models\StockLocation;

class WarehouseMap extends Page
{
    protected string $view = 'filament.pages.warehouse-map';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Warehouse Map';

    protected static ?string $title = 'Warehouse Map';

    public function getWarehouse(): Warehouse
    {
        /** @var Warehouse $tenant */
        $tenant = Filament::getTenant();

        return $tenant;
    }

    /**
     * @return Collection<int, StockLocation>
     */
    public function getLocations(): Collection
    {
        return StockLocation::query()
            ->orderBy('rank')
            ->get(['id', 'parent_id', 'name', 'rank', 'x', 'y', 'width', 'height']);
    }

    public function saveLocationLayout(int $id, float $x, float $y, float $width, float $height): void
    {
        $location = StockLocation::query()->findOrFail($id);
        $warehouse = $this->getWarehouse();

        $width = max(0.1, $width);
        $height = max(0.1, $height);

        $x = max(0.0, $x);
        $y = max(0.0, $y);

        if ($warehouse->floor_width !== null) {
            $x = min($x, (float) $warehouse->floor_width - $width);
        }

        if ($warehouse->floor_height !== null) {
            $y = min($y, (float) $warehouse->floor_height - $height);
        }

        $location->update([
            'x' => round(max(0.0, $x), 2),
            'y' => round(max(0.0, $y), 2),
            'width' => round($width, 2),
            'height' => round($height, 2),
        ]);
    }
}
