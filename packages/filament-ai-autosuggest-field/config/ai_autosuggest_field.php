<?php

declare(strict_types=1);

return [
    'provider' => env('AI_AUTOSUGGEST_PROVIDER', env('AI_PROVIDER', 'openai')),

    'model' => env('AI_AUTOSUGGEST_MODEL', null),

    'default_max_suggestions' => 5,

    'default_context_limit' => 5,

    'default_min_characters' => 3,
];
