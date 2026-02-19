<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\Client;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('delivery_address')
                    ->label('Delivery address')
                    ->state(function (Client $record): string {
                        $address = $record->clientDeliveryAddress;

                        if (! $address) {
                            return 'Not set';
                        }

                        return $address->name ?? 'Not set';
                    })
                    ->badge()
                    ->description(function (Client $record): ?string {
                        $address = $record->clientDeliveryAddress;

                        if (! $address) {
                            return null;
                        }

                        return trim(sprintf('%s • %s', $address->address, $address->city), " \t\n\r\0\x0B•");
                    }),
                TextColumn::make('bill_address')
                    ->label('Bill address')
                    ->state(function (Client $record): string {
                        $address = $record->clientBillAddress;

                        if (! $address) {
                            return 'Not set';
                        }

                        return $address->name ?? 'Not set';
                    })
                    ->badge()
                    ->description(function (Client $record): ?string {
                        $address = $record->clientBillAddress;

                        if (! $address) {
                            return null;
                        }

                        return trim(sprintf('%s • %s', $address->address, $address->city), " \t\n\r\0\x0B•");
                    }),
                //
            ])
            ->filters([
                //
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
