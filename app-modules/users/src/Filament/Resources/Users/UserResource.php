<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users;

use App\Models\Subdomain;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Users\Filament\Resources\Users\Pages\CreateUser;
use Modules\Users\Filament\Resources\Users\Pages\EditUser;
use Modules\Users\Filament\Resources\Users\Pages\ListUsers;
use Modules\Users\Filament\Resources\Users\Schemas\UserForm;
use Modules\Users\Filament\Resources\Users\Tables\UsersTable;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $pluralModelLabel = 'Users';

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $currentSubdomain = app()->has('current_subdomain')
            ? app('current_subdomain')
            : self::resolveSubdomainFromHost();

        if (! $currentSubdomain) {
            return $query;
        }

        return $query->where('subdomain_id', $currentSubdomain->id);
    }

    private static function resolveSubdomainFromHost(): ?Subdomain
    {
        $host = request()->getHost();
        $centralDomain = config('app.central_domain', 'wms.test');

        $subdomain = str_replace('.'.$centralDomain, '', $host);

        if ($subdomain === $centralDomain || $subdomain === '') {
            return null;
        }

        return Subdomain::query()->where('subdomain', $subdomain)->first();
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
