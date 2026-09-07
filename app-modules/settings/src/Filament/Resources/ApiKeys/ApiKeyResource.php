<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\ApiKeys;

use App\Models\ApiKey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Settings\Filament\Resources\ApiKeys\Pages\CreateApiKey;
use Modules\Settings\Filament\Resources\ApiKeys\Pages\EditApiKey;
use Modules\Settings\Filament\Resources\ApiKeys\Pages\ListApiKeys;
use Modules\Settings\Filament\Resources\ApiKeys\Schemas\ApiKeyForm;
use Modules\Settings\Filament\Resources\ApiKeys\Tables\ApiKeysTable;
use UnitEnum;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ApiKeyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiKeysTable::configure($table);
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
            'index' => ListApiKeys::route('/'),
            'create' => CreateApiKey::route('/create'),
            'edit' => EditApiKey::route('/{record}/edit'),
        ];
    }
}
