<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\VatRates\Schemas;

use Filament\Schemas\Schema;
use Modules\Settings\Filament\Resources\VatRates\Inputs\NameInput;
use Modules\Settings\Filament\Resources\VatRates\Inputs\RateInput;

class VatRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                NameInput::make(),
                RateInput::make(),

            ]);
    }
}
