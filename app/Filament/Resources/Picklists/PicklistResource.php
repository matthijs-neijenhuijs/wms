<?php

namespace App\Filament\Resources\Picklists;

use App\Filament\Resources\Picklists\Pages\CreatePicklist;
use App\Filament\Resources\Picklists\Pages\EditPicklist;
use App\Filament\Resources\Picklists\Pages\ListPicklists;
use App\Filament\Resources\Picklists\Schemas\PicklistForm;
use App\Filament\Resources\Picklists\Tables\PicklistsTable;
use App\Models\Picklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PicklistResource extends Resource
{
    protected static ?string $model = Picklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return PicklistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PicklistsTable::configure($table);
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
            'index' => ListPicklists::route('/'),
            'create' => CreatePicklist::route('/create'),
            'edit' => EditPicklist::route('/{record}/edit'),
        ];
    }
}
