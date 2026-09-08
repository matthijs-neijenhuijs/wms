<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Columns\AddressColumn;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Columns\BillAddressColumn;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Columns\CityColumn;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Columns\DeliveryAddressColumn;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Columns\NameColumn;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\AddressInput;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\CityInput;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\CompanyInput;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\CountryInput;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\GenderSelect;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\InitialsInput;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\NameInput as AddressNameInput;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\RegionInput;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\TelephoneNumberInput;
use Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs\ZipcodeInput;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    #[On('refresh-relation-manager')]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact')
                    ->schema([
                        CompanyInput::make(),
                        GenderSelect::make(),
                        InitialsInput::make(),
                        AddressNameInput::make(),
                        TelephoneNumberInput::make(),
                    ])
                    ->columns(2),
                Section::make('Address')
                    ->schema([
                        AddressInput::make(),
                        ZipcodeInput::make(),
                        CityInput::make(),
                        RegionInput::make(),
                        CountryInput::make(),
                    ])
                    ->columns(2),

            ]);
    }

    public function table(Table $table): Table
    {

        return $table

            ->recordTitleAttribute('name')
            ->columns([
                BillAddressColumn::make(),
                DeliveryAddressColumn::make(),
                NameColumn::make(),
                AddressColumn::make(),
                CityColumn::make(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
