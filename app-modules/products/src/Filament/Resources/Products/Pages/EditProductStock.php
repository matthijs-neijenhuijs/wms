<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Modules\Products\Filament\Resources\Products\ProductResource;
use Modules\Products\Filament\Resources\Products\Schemas\ProductStockForm;

class EditProductStock extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $navigationLabel = 'Stock';

    protected static ?string $title = 'Stock';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return null;
    }

    public function form(Schema $schema): Schema
    {
        return ProductStockForm::configure($schema);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
