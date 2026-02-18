<?php

namespace App\Filament\Resources\Warehouses;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Warehouses';

    protected static ?string $pluralModelLabel = 'Warehouses';

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $currentSubdomain = app()->has('current_subdomain')
            ? app('current_subdomain')
            : self::resolveSubdomainFromHost();

        if (! $currentSubdomain) {
            return $query;
        }

        return $query->where('subdomain_id', $currentSubdomain->id);
    }

    private static function resolveSubdomainFromHost(): ?Subdomain
    {
        $host = request()->getHost();
        $centralDomain = config('app.central_domain', 'wms.test');

        $subdomain = str_replace('.'.$centralDomain, '', $host);

        if ($subdomain === $centralDomain || $subdomain === '') {
            return null;
        }

        return Subdomain::query()->where('subdomain', $subdomain)->first();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
