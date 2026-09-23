<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Inputs;

use Filament\Forms\Components\Textarea;

class CommentsInput
{
    public static function make(): Textarea
    {
        return Textarea::make('comments')
            ->rows(3);
    }
}
