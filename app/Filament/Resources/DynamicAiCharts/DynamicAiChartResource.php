<?php

namespace App\Filament\Resources\DynamicAiCharts;

use App\Filament\Resources\DynamicAiCharts\Pages\ManageDynamicAiCharts;
use App\Models\DynamicAiChart;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DynamicAiChartResource extends Resource
{
    protected static ?string $model = DynamicAiChart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'AI Charts';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderByDesc('score')->latest())
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('chart_type')
                    ->badge(),
                TextColumn::make('selected_model')
                    ->label('Model')
                    ->badge(),
                TextColumn::make('metric_key')
                    ->label('Metric')
                    ->badge(),
                TextColumn::make('score')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDynamicAiCharts::route('/'),
        ];
    }
}
