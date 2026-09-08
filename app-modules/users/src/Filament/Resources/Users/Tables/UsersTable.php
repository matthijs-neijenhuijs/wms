<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Modules\Users\Filament\Resources\Users\Columns\CreatedAtColumn;
use Modules\Users\Filament\Resources\Users\Columns\EmailColumn;
use Modules\Users\Filament\Resources\Users\Columns\NameColumn;
use Modules\Users\Filament\Resources\Users\Columns\SubdomainColumn;
use Modules\Users\Filament\Resources\Users\Columns\UpdatedAtColumn;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                NameColumn::make(),
                EmailColumn::make(),
                SubdomainColumn::make(),
                CreatedAtColumn::make(),
                UpdatedAtColumn::make(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
