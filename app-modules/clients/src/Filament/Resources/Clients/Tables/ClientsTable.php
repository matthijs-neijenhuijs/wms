<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Modules\Clients\Filament\Resources\Clients\Columns\BillAddressColumn;
use Modules\Clients\Filament\Resources\Clients\Columns\DeliveryAddressColumn;
use Modules\Clients\Filament\Resources\Clients\Columns\EmailColumn;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                EmailColumn::make(),
                DeliveryAddressColumn::make(),
                BillAddressColumn::make(),
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
