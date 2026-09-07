<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['brand', 'stockProduct', 'attributes.attributeGroup']))
            ->columns([
                ToggleColumn::make('active'),
                TextColumn::make('reference_code')
                    ->searchable(),
                TextColumn::make('name')
                    ->html()
                    ->formatStateUsing(function (string $state, $record): string {
                        $brandName = $record->brand?->name;
                        $nameWithBrand = $brandName ? "{$brandName} - {$state}" : $state;

                        $attributeBadges = $record->attributes
                            ->map(function ($attribute): string {
                                $groupName = optional($attribute->attributeGroup)->name ?? 'Ungrouped';
                                $label = e("{$groupName}: {$attribute->name}");

                                return '<span class="fi-color fi-color-primary fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap">'.$label.'</span>';
                            })
                            ->implode('');

                        if ($attributeBadges === '') {
                            return e($nameWithBrand);
                        }

                        return '<div>'.e($nameWithBrand).'</div><div class="mt-1 flex flex-col items-start gap-1">'.$attributeBadges.'</div>';
                    })
                    ->searchable(),
                TextColumn::make('stock_summary')
                    ->label('Stock')
                    ->html()
                    ->state(function ($record): string {
                        $stock = $record->stockProduct;
                        $onStock = $stock !== null && $stock->on_stock_quantity !== null ? $stock->on_stock_quantity : 0;
                        $reserved = $stock !== null && $stock->reserved_quantity !== null ? $stock->reserved_quantity : 0;
                        $reservedOnPicklists = $stock !== null && $stock->reserved_on_picklists !== null ? $stock->reserved_on_picklists : 0;
                        $free = $stock !== null && $stock->free_on_stock_quantity !== null ? $stock->free_on_stock_quantity : 0;

                        return '<div class="flex flex-col items-start gap-1">'
                            .'<span class="fi-color fi-color-info fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap" style="color: #000;">Stock: '.$onStock.'</span>'
                            .'<span class="fi-color fi-color-info fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap" style="color: #000;">Res: '.$reserved.'</span>'
                            .'<span class="fi-color fi-color-info fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap" style="color: #000;">Pick: '.$reservedOnPicklists.'</span>'
                            .'<span class="fi-color fi-color-info fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap" style="color: #000;">Free: '.$free.'</span>'
                            .'</div>';
                    }),

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
