<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Orders\Filament\Resources\OrderStatuses\Pages\CreateOrderStatus;
use Modules\Orders\Filament\Resources\OrderStatuses\Pages\EditOrderStatus;
use Modules\Orders\Filament\Resources\OrderStatuses\Pages\ListOrderStatuses;
use Modules\Orders\Filament\Resources\OrderStatuses\Schemas\OrderStatusForm;
use Modules\Orders\Filament\Resources\OrderStatuses\Tables\OrderStatusesTable;
use Modules\Orders\Models\OrderStatus;
use UnitEnum;

class OrderStatusResource extends Resource
{
    protected static ?string $model = OrderStatus::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Order Statuses');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Order Statuses');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderStatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderStatusesTable::configure($table);
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
            'index' => ListOrderStatuses::route('/'),
            'create' => CreateOrderStatus::route('/create'),
            'edit' => EditOrderStatus::route('/{record}/edit'),
        ];
    }
}
