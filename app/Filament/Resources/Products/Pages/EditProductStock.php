<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductStockForm;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

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
