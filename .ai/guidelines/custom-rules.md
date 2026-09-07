---
name: custom AI rules
description: Matthijs Neijenhuijs coding standards for building and maintaining Laravel applications; use for models, controllers, services, and other application code.
license: MIT
metadata:
  author: Matthijs Neijenhuijs
---

## Rule priority
- You MUST follow existing project conventions first.
- If no convention exists, you MUST follow the rules in this file.

## Mandatory checks after completing a task
- You MUST always run these commands:
```bash
# Search for translate strings
php artisan lang:extract

# Run analysis
compose analyse

# Run linter
compose lint

```
- You MUST run relevant tests for changed behavior (at least focused tests around the affected code).

## General rules
- You MUST follow Filament code quality tips: https://filamentphp.com/docs/5.x/resources/code-quality-tips
- You MUST NOT use `down()` methods in migrations. We do not roll back migrations in production. If schema changes are needed, you MUST create a new migration.
- You MUST use `start_on` and `end_on` for date fields.
- You MUST use `start_at` and `end_at` for datetime fields.

## Model rules
- Every model MUST define a `casts()` method for attributes that are not plain strings or integers.

The function should look like this:
```php
public function casts(): array
{
    return [
        'attribute_name' => 'cast_type',
        // Add more attributes and their corresponding cast types as needed
    ];
}
```

- You MUST place `casts()` as the last function in the model class.
- You MUST use `HasFactory` in all models that have a factory.

Example:
```php
/** @use HasFactory<AssetTypeFactory> */
use HasFactory;
```
- Every model MUST define `getSearchableSettings()` for Laravel Scout / Meilisearch.

Example:
```php
public static function getSearchableSettings(): array
{
    return [
        'filterableAttributes' => [],
        'sortableAttributes'   => ['created_at', 'updated_at'],
        'searchableAttributes' => [
            'av_key',
            'name',
        ],
    ];
}
```
- Every model MUST define `toSearchableArray()` for fields indexed by Laravel Scout.

Example:
```php
public function toSearchableArray(): array
{
    return [
        'id'         => $this->id,
        'av_key'     => $this->av_key,
        'name'       => $this->name,
        'created_at' => $this->created_at?->toIso8601String(),
        'updated_at' => $this->updated_at?->toIso8601String(),
    ];
}
```

## Config rules
- You MUST use Meilisearch with the Scout Meilisearch driver.
- Every time a new searchable model is added, you MUST add its index settings in `config/scout.php`.
- Model index settings MUST come from `ModelClass::getSearchableSettings()`.

Example:
```php
// config/scout.php
return [
    'meilisearch' => [
        'index-settings' => [
            'model_name' => ModelClass::getSearchableSettings(),
            // Add more model names and their corresponding searchable settings as needed
        ],
    ],
];
```

## Directory rules
- You MUST use `.gitkeep` files in empty directories so they are tracked by Git.
- You MUST remove `.gitkeep` when the directory is no longer empty.

## Building factory classes
- Factories MUST only contain definitions and fake data calls.
- You MUST NOT use logic in factories: no query logic, `if` statements, loops, or business logic.
- Factories are intentionally simple and "dumb" and MUST stay that way.

## Building seeder classes
- Seeders MAY use logic, but they MUST stay simple and maintainable.
- Seeders MAY use complex queries only when needed, and they MUST remain easy to understand.

## Translation rules
- You MUST translate Filament columns and input fields with `label()`.
- In translation files, you MUST capitalize every separate word.
- You MUST translate navigation labels in Filament clusters and resources with `__()`.

## HTTP resource rules
- Every resource MUST have a corresponding HTTP resource class.
- You MUST define the JSON response structure as an explicit array of attributes in the resource class.

## Filament provider rules
- Every Filament provider MUST define `Relation::enforceMorphMap()` in `boot()`.

Example:
```php
public function boot(): void
{
    Relation::enforceMorphMap([
        'model_name' => ModelClass::class,
        // Add more model names and their corresponding classes as needed
    ]);
}
```

- Every Filament provider MUST define `Feature::discover()` in `boot()`.

Example:
```php
public function boot(): void
{
    Feature::discover([
        FeatureClass::class,
        // Add more feature classes as needed
    ]);
}
```

