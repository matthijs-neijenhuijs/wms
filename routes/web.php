<?php

use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // GraphQL routes
        Route::post('/graphql', '\Rebing\GraphQL\GraphQLController@query')->name('graphql');
        Route::get('/graphql', '\Rebing\GraphQL\GraphQLController@query')->name('graphql');
        
        // your actual routes
    });
}
