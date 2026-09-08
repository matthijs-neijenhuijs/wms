<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\RelationManagers;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderProductRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state) {
                            $product = Product::with('vatRate')->find($state);

                            /** @var Product|null $product */
                            if ($product) {
                                $set('vat_rate_id', $product->vat_rate_id);
                                $set('vat_rate', $product->vatRate?->rate);
                                $set('barcode', $product->barcode);
                                $set('price', $product->price);
                                $set('name', $product->name);
                                $set('reference_code', $product->reference_code);
                            }
                        }
                    }),

                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),

                TextInput::make('name')
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                TextInput::make('reference_code')
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('barcode')
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('price')
                    ->numeric()
                    ->prefix(fn () => match ($this->getOwnerRecord()->warehouse->currency ?? 'EUR') {
                        'USD' => '$',
                        'GBP' => '£',
                        'JPY' => '¥',
                        'CHF' => 'CHF',
                        'CAD' => 'C$',
                        'AUD' => 'A$',
                        'CNY' => '¥',
                        default => '€',
                    })
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('vat_rate')
                    ->numeric()
                    ->suffix('%')
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('quantity'),
                TextColumn::make('price')
                    ->money(fn () => $this->getOwnerRecord()->warehouse->currency ?? 'EUR'),
                TextColumn::make('vat_rate')
                    ->suffix('%'),
                TextColumn::make('reference_code'),
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
