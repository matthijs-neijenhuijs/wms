<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Products\Filament\Resources\Products\Inputs\ActiveToggle;
use Modules\Products\Filament\Resources\Products\Inputs\AttributesSelect;
use Modules\Products\Filament\Resources\Products\Inputs\BarcodeInput;
use Modules\Products\Filament\Resources\Products\Inputs\BrandSelect;
use Modules\Products\Filament\Resources\Products\Inputs\NameInput;
use Modules\Products\Filament\Resources\Products\Inputs\ReferenceCodeInput;
use Modules\Products\Filament\Resources\Products\Inputs\VatRateSelect;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product details')
                    ->schema([
                        ActiveToggle::make(),
                        ReferenceCodeInput::make(),
                        BarcodeInput::make(),
                        NameInput::make(),
                        VatRateSelect::make(),
                        BrandSelect::make(),
                        AttributesSelect::make(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
