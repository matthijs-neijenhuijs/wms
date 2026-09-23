# Platform & Cross-Cutting Findings

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Platform isn't a warehouse domain on its own — it's the supporting
infrastructure (queues, imports/exports, activity logging, AI conversation
persistence) and cross-cutting findings that the other modules rely on or
that don't fit neatly into one of them.

Miscellaneous findings from the exploration pass behind the Purchase Order and
E-commerce Order API work (see [`orders.md`](orders.md)) that don't belong to
any single domain module. Included for completeness of the system map; none of
this is new work.

- `agent_conversations`/`agent_conversation_messages` come from the **third-party
  `laravel/ai` package** (`vendor/laravel/ai`), not an app module. No Eloquent
  model exists for them (accessed via `DB::table()` in
  `Laravel\Ai\Storage\DatabaseConversationStore`); no Filament resource exists.
- `dynamic_ai_charts`, mentioned in `.github/instructions/warehouse-ai-solutions.instructions.md`
  as already existing, **does not exist anywhere in the codebase** (no migration,
  table, or model). That instructions file is factually wrong on this point — flag
  it, do not build against it.
- `app-modules/platform` has no models/resources of its own; it only owns
  infrastructure migrations (`cache`, `jobs`, `imports`, `exports`,
  `failed_import_rows`, `activity_log`).
- `Relation::enforceMorphMap()` — required by this project's custom rules for every
  Filament provider — **is not called anywhere in the codebase**. Out of scope for
  the Purchase Order plan (not related to it), but worth the user's attention
  separately.
- `app/Filament/NavigationGroups.php` is an empty, dead file (declare + nothing).
