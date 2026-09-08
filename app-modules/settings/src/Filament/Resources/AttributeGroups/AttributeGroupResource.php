<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\AttributeGroups;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Settings\Filament\Resources\AttributeGroups\Pages\CreateAttributeGroup;
use Modules\Settings\Filament\Resources\AttributeGroups\Pages\EditAttributeGroup;
use Modules\Settings\Filament\Resources\AttributeGroups\Pages\ListAttributeGroups;
use Modules\Settings\Filament\Resources\AttributeGroups\Schemas\AttributeGroupForm;
use Modules\Settings\Filament\Resources\AttributeGroups\Tables\AttributeGroupsTable;
use Modules\Settings\Models\AttributeGroup;
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
