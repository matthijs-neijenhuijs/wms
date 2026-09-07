<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Schemas;

use Filament\Schemas\Schema;

class PicklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
