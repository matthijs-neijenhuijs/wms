<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Pages;

use BackedEnum;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Table;
use Modules\Clients\Filament\Resources\Clients\ClientResource;
use Modules\Clients\Filament\Resources\Clients\Widgets\ClientOrderStatsOverview;
use Modules\Orders\Filament\Resources\Orders\OrderResource;

class ManageClientOrders extends ManageRelatedRecords
{
    protected static string $resource = ClientResource::class;

    protected static string $relationship = 'orders';

    protected static ?string $relatedResource = OrderResource::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('Orders');
    }

    public function getTitle(): string
    {
        return __('Orders');
    }

    public function getBreadcrumb(): string
    {
        return __('Orders');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ClientOrderStatsOverview::class,
        ];
    }

    protected function makeTable(): Table
    {
        return parent::makeTable()->toolbarActions([]);
    }
}
