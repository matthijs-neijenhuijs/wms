<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Rebing\GraphQL\GraphQLServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    GraphQLServiceProvider::class,
];
