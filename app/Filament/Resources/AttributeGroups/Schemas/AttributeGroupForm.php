<?php

namespace App\Filament\Resources\AttributeGroups\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\TextInput;
class AttributeGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                //
            ]);
    }
}
