<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Users\Filament\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
