<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client details')
                    ->schema([
                        Toggle::make('active')
                            ->inline(false)
                            ->required(),
                        TextInput::make('email')
                            ->required()
                            ->email(),
                        TextInput::make('company'),
                        TextInput::make('vat_number')
                            ->label('VAT number'),
                        TextInput::make('coc_number')
                            ->label('CoC number'),
                        TextInput::make('debtor_number')
                            ->label('Debtor number'),
                        TextInput::make('iban_number')
                            ->label('IBAN number'),
                        Textarea::make('comments')
                            ->rows(3),
                    ])
                    ->columns(2),
                Section::make('Addresses')
                    ->schema([
                        Select::make('bill_client_address_id')
                            ->label('Bill address')
                            ->relationship(
                                name: 'clientBillAddress',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, ?\App\Models\Client $record): Builder {
                                    if (! $record) {
                                        return $query->whereNull('id');
                                    }

                                    return $query->where('client_id', $record->id);
                                },
                            ),
                        Select::make('delivery_client_address_id')
                            ->label('Delivery address')
                            ->relationship(
                                name: 'clientDeliveryAddress',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, ?\App\Models\Client $record): Builder {
                                    if (! $record) {
                                        return $query->whereNull('id');
                                    }

                                    return $query->where('client_id', $record->id);
                                },
                            ),
                    ])
                    ->columns(2),

            ]);
    }
}
