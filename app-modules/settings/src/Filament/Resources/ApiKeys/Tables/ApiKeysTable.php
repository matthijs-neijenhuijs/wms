<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\ApiKeys\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Modules\Settings\Filament\Resources\ApiKeys\Columns\CreatedAtColumn;
use Modules\Settings\Filament\Resources\ApiKeys\Columns\ExpiresAtColumn;
use Modules\Settings\Filament\Resources\ApiKeys\Columns\IsActiveColumn;
use Modules\Settings\Filament\Resources\ApiKeys\Columns\LastUsedAtColumn;
use Modules\Settings\Filament\Resources\ApiKeys\Columns\NameColumn;

class ApiKeysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                NameColumn::make(),
                IsActiveColumn::make(),
                LastUsedAtColumn::make(),
                ExpiresAtColumn::make(),
                CreatedAtColumn::make(),
            ])
            ->defaultSort('created_at', 'desc')
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
