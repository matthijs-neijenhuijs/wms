<?php

declare(strict_types=1);

namespace OpenWms\FilamentAiAutosuggestField\Services;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Ai\Responses\StructuredAgentResponse;
use OpenWms\FilamentAiAutosuggestField\Ai\Agents\AiAutosuggestAgent;
use Throwable;

class AiAutosuggestService
{
    public function suggest(
        string $fieldLabel,
        string $query,
        array $contextModelClasses,
        int $maxSuggestions = 5,
        int $contextLimit = 5,
        ?string $provider = null,
        ?string $model = null,
    ): array {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $context = $this->buildModelContext($contextModelClasses, $contextLimit);

        try {
            $agent = new AiAutosuggestAgent(
                fieldLabel: $fieldLabel,
                maxSuggestions: $maxSuggestions,
                modelContext: $context,
            );

            $response = $agent->prompt(
                prompt: implode("\n", [
                    'Current user input: '.$query,
                    'Provide suggestions for this value only.',
                    'Each suggestion should be distinct and useful.',
                    'Return suggestions_json as a JSON array of strings.',
                ]),
                provider: $provider ?: config('ai_autosuggest_field.provider', 'openai'),
                model: $model,
            );

            if (! $response instanceof StructuredAgentResponse) {
                return [];
            }

            $suggestions = json_decode((string) ($response['suggestions_json'] ?? '[]'), true);

            if (! is_array($suggestions)) {
                return [];
            }

            return collect($suggestions)
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(fn (string $value): string => trim($value))
                ->unique()
                ->take($maxSuggestions)
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function buildModelContext(array $modelClasses, int $contextLimit): array
    {
        $tenant = Filament::getTenant();

        return collect($modelClasses)
            ->filter(fn (mixed $class): bool => is_string($class) && class_exists($class))
            ->map(function (string $class) use ($contextLimit, $tenant): ?array {
                $instance = app($class);

                if (! $instance instanceof Model) {
                    return null;
                }

                $query = $class::query();
                $columns = $instance->getConnection()->getSchemaBuilder()->getColumnListing($instance->getTable());

                if ($tenant && in_array('warehouse_id', $columns, true)) {
                    $query->where('warehouse_id', $tenant->getKey());
                }

                $rows = $query
                    ->latest()
                    ->limit($contextLimit)
                    ->get()
                    ->map(function (Model $record) {
                        return Arr::only(
                            $record->attributesToArray(),
                            collect($record->attributesToArray())
                                ->filter(fn (mixed $value): bool => is_scalar($value) || $value === null)
                                ->keys()
                                ->take(12)
                                ->all(),
                        );
                    })
                    ->all();

                return [
                    'model' => $class,
                    'table' => $instance->getTable(),
                    'rows' => $rows,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
