<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Clients\Filament\Resources\Clients\Inputs\ActiveToggle;
use Modules\Clients\Filament\Resources\Clients\Inputs\BillAddressSelect;
use Modules\Clients\Filament\Resources\Clients\Inputs\CocNumberInput;
use Modules\Clients\Filament\Resources\Clients\Inputs\CommentsInput;
use Modules\Clients\Filament\Resources\Clients\Inputs\CompanyInput;
use Modules\Clients\Filament\Resources\Clients\Inputs\DebtorNumberInput;
use Modules\Clients\Filament\Resources\Clients\Inputs\DeliveryAddressSelect;
use Modules\Clients\Filament\Resources\Clients\Inputs\EmailInput;
use Modules\Clients\Filament\Resources\Clients\Inputs\IbanNumberInput;
use Modules\Clients\Filament\Resources\Clients\Inputs\VatNumberInput;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client details')
                    ->schema([
                        ActiveToggle::make(),
                        EmailInput::make(),
                        CompanyInput::make(),
                        VatNumberInput::make(),
                        CocNumberInput::make(),
                        DebtorNumberInput::make(),
                        IbanNumberInput::make(),
                        CommentsInput::make(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Addresses')
                    ->schema([
                        BillAddressSelect::make(),
                        DeliveryAddressSelect::make(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

            ]);
    }
}
