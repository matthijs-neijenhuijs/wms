<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\AttributeGroups;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Settings\Filament\Resources\AttributeGroups\Pages\CreateAttributeGroup;
use Modules\Settings\Filament\Resources\AttributeGroups\Pages\EditAttributeGroup;
use Modules\Settings\Filament\Resources\AttributeGroups\Pages\ListAttributeGroups;
use Modules\Settings\Filament\Resources\AttributeGroups\Pages\ManageAttributeGroupActivities;
use Modules\Settings\Filament\Resources\AttributeGroups\Schemas\AttributeGroupForm;
use Modules\Settings\Filament\Resources\AttributeGroups\Tables\AttributeGroupsTable;
use Modules\Settings\Models\AttributeGroup;
use UnitEnum;

class AttributeGroupResource extends Resource
{
    protected static ?string $model = AttributeGroup::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditAttributeGroup::class,
            ManageAttributeGroupActivities::class,
        ]);
    }

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
            'history' => ManageAttributeGroupActivities::route('/{record}/history'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttributesRelationManager::class,
        ];
    }
}
