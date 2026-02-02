<?php

namespace App\Filament\Resources\AttributeGroups;

use App\Filament\Resources\AttributeGroups\Pages\CreateAttributeGroup;
use App\Filament\Resources\AttributeGroups\Pages\EditAttributeGroup;
use App\Filament\Resources\AttributeGroups\Pages\ListAttributeGroups;
use App\Filament\Resources\AttributeGroups\Schemas\AttributeGroupForm;
use App\Filament\Resources\AttributeGroups\Tables\AttributeGroupsTable;
use App\Models\AttributeGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AttributeGroupResource extends Resource
{
    protected static ?string $model = AttributeGroup::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AttributeGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttributeGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributeGroups::route('/'),
            'create' => CreateAttributeGroup::route('/create'),
            'edit' => EditAttributeGroup::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttributesRelationManager::class,
        ];
    }
}
