<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Columns\BarcodeColumn;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Columns\NameColumn;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Columns\PriceColumn;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Columns\QuantityColumn;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Columns\ReferenceCodeColumn;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Columns\VatRateColumn;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs\BarcodeInput;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs\NameInput;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs\PriceInput;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs\ProductSelect;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs\QuantityInput;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs\ReferenceCodeInput;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs\VatRateInput;

class OrderProductRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ProductSelect::make(),
                QuantityInput::make(),
                NameInput::make(),
                ReferenceCodeInput::make(),
                BarcodeInput::make(),
                PriceInput::make($this),
                VatRateInput::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                NameColumn::make(),
                BarcodeColumn::make(),
                QuantityColumn::make(),
                PriceColumn::make($this),
                VatRateColumn::make(),
                ReferenceCodeColumn::make(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->orderStatus?->canModifyProducts() ?? true),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->orderStatus?->canModifyProducts() ?? true),
                DeleteAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->orderStatus?->canModifyProducts() ?? true),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
