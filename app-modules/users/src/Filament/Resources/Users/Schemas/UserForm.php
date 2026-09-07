<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Schemas;

use App\Models\Subdomain;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subdomain_id')
                    ->label('Subdomain')
                    ->options(Subdomain::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Hash::make($state) : null),

                Select::make('warehouses')
                    ->relationship(
                        name: 'warehouses',
                        titleAttribute: 'name',
                        modifyQueryUsing: function ($query) {
                            if (app()->has('current_subdomain')) {
                                return $query->where('subdomain_id', app('current_subdomain')->id);
                            }

                            return $query;
                        }
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Warehouses'),
            ]);
    }
}
