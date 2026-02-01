<?php

namespace App\Filament\Resources\VatRates;

use App\Filament\Resources\VatRates\Pages\CreateVatRate;
use App\Filament\Resources\VatRates\Pages\EditVatRate;
use App\Filament\Resources\VatRates\Pages\ListVatRates;
use App\Filament\Resources\VatRates\Schemas\VatRateForm;
use App\Filament\Resources\VatRates\Tables\VatRatesTable;
use App\Models\VatRate;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class VatRateResource extends Resource
{
    protected static ?string $model = VatRate::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return VatRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VatRatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVatRates::route('/'),
            'create' => CreateVatRate::route('/create'),
            'edit' => EditVatRate::route('/{record}/edit'),
        ];
    }
}
