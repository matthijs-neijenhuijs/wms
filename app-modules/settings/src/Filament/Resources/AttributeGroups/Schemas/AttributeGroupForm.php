<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\AttributeGroups\Schemas;

use Filament\Schemas\Schema;
use Modules\Settings\Filament\Resources\AttributeGroups\Inputs\NameInput;

class AttributeGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                NameInput::make(),
                //
            ]);
    }
}
