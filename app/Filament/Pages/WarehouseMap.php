<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Warehouse;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Products\Models\StockLocation;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\FloorHeightInput;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\FloorWidthInput;
use UnitEnum;

class WarehouseMap extends Page
{
    protected string $view = 'filament.pages.warehouse-map';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Warehouse Map';

    protected static ?string $title = 'Warehouse Map';

    public function editFloorSizeAction(): Action
    {
        return Action::make('editFloorSize')
            ->label(__('Edit Floor Size'))
            ->icon(Heroicon::OutlinedArrowsPointingOut)
            ->modalHeading(__('Edit Floor Size'))
            ->modalDescription(__('Locations that no longer fit are moved or shrunk to stay inside the floor.'))
            ->modalWidth('md')
            ->fillForm(fn (): array => [
                'floor_width' => $this->getWarehouse()->floor_width,
                'floor_height' => $this->getWarehouse()->floor_height,
            ])
            ->schema([
                FloorWidthInput::make()->required(),
                FloorHeightInput::make()->required(),
            ])
            ->action(function (array $data): void {
                $this->updateFloorSize((float) $data['floor_width'], (float) $data['floor_height']);

                Notification::make()
                    ->title(__('Floor Size Updated'))
                    ->success()
                    ->send();

                $this->redirect(static::getUrl(), navigate: true);
            });
    }

    public function updateFloorSize(float $floorWidth, float $floorHeight): void
    {
        $floorWidth = round(max(0.1, $floorWidth), 2);
        $floorHeight = round(max(0.1, $floorHeight), 2);

        $this->getWarehouse()->update([
            'floor_width' => $floorWidth,
            'floor_height' => $floorHeight,
        ]);

        StockLocation::query()
            ->whereNotNull(['x', 'y', 'width', 'height'])
            ->get()
            ->each(function (StockLocation $location) use ($floorWidth, $floorHeight): void {
                $width = min((float) $location->width, $floorWidth);
                $height = min((float) $location->height, $floorHeight);

                $location->update([
                    'x' => round(max(0.0, min((float) $location->x, $floorWidth - $width)), 2),
                    'y' => round(max(0.0, min((float) $location->y, $floorHeight - $height)), 2),
                    'width' => round($width, 2),
                    'height' => round($height, 2),
                ]);
            });
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->editFloorSizeAction(),
        ];
    }

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
        $this->skipRender();

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

    public function removeLocationFromMap(int $id): void
    {
        $this->skipRender();

        StockLocation::query()->findOrFail($id)->update([
            'x' => null,
            'y' => null,
            'width' => null,
            'height' => null,
        ]);
    }

    /**
     * Reassign ranks so the given locations are walked first, in the given
     * order, followed by all other locations in their current rank order.
     *
     * @param  array<int, int|string>  $locationIds
     * @return array<int, int>
     */
    public function saveRoute(array $locationIds): array
    {
        $this->skipRender();

        $locationIds = array_values(array_unique(array_map(intval(...), $locationIds)));

        $locations = StockLocation::query()->orderBy('rank')->get()->keyBy('id');

        $unknownIds = array_diff($locationIds, $locations->keys()->all());

        if ($unknownIds !== []) {
            throw (new ModelNotFoundException)->setModel(StockLocation::class, $unknownIds);
        }

        $orderedLocations = collect($locationIds)
            ->map(fn (int $id): ?StockLocation => $locations->get($id))
            ->filter()
            ->concat($locations->except($locationIds)->values())
            ->values();

        DB::transaction(function () use ($orderedLocations): void {
            foreach ($orderedLocations as $index => $location) {
                if ($location->rank !== $index + 1) {
                    StockLocation::query()->whereKey($location->id)->update(['rank' => -($index + 1)]);
                }
            }

            foreach ($orderedLocations as $index => $location) {
                $location->update(['rank' => $index + 1]);
            }
        });

        return $orderedLocations
            ->mapWithKeys(fn (StockLocation $location, int $index): array => [$location->id => $index + 1])
            ->all();
    }
}
