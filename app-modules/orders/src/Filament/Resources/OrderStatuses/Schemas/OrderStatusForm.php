<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Schemas;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\CancelledToggle;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\ColorInput;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\CompletedToggle;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\ConceptedToggle;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\DeliveredToggle;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\GeneratePicklistToggle;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\NameInput;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\OnHoldToggle;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\ReduceStockToggle;
use Modules\Orders\Filament\Resources\OrderStatuses\Inputs\ReserveStockToggle;

class OrderStatusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        NameInput::make(),
                        ColorInput::make(),
                    ]),

                Fieldset::make(__('Status Flags'))
                    ->columns(3)
                    ->components([
                        GeneratePicklistToggle::make(),
                        ReserveStockToggle::make(),
                        ReduceStockToggle::make(),
                        ConceptedToggle::make(),
                        CompletedToggle::make(),
                        OnHoldToggle::make(),
                        DeliveredToggle::make(),
                        CancelledToggle::make(),
                    ]),
            ]);
    }
}
