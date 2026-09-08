<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Inputs;

use App\Models\Subdomain;
use Filament\Forms\Components\Select;

class SubdomainSelect
{
    public static function make(): Select
    {
        return Select::make('subdomain_id')
            ->label('Subdomain')
            ->options(Subdomain::query()->pluck('name', 'id'))
            ->searchable()
            ->required();
    }
}
