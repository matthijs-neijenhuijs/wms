<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Brands\Filament\Resources\Brands\Pages\CreateBrand;
use Modules\Brands\Filament\Resources\Brands\Pages\EditBrand;
use Modules\Brands\Filament\Resources\Brands\Pages\ListBrands;
use Modules\Brands\Filament\Resources\Brands\Schemas\BrandForm;
use Modules\Brands\Filament\Resources\Brands\Tables\BrandsTable;
use Modules\Brands\Models\Brand;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
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
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
