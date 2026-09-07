<?php

declare(strict_types=1);

namespace OpenWms\FilamentDynamicAiCharts\Services\AiCharts;

use App\Models\DynamicAiChart;
use App\Models\User;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Responses\StructuredAgentResponse;
use OpenWms\FilamentDynamicAiCharts\Ai\Agents\DynamicChartPlannerAgent;
use OpenWms\FilamentDynamicAiCharts\Exceptions\AiChartClarificationException;
use Throwable;

class DynamicChartGenerator
{
    public function handleChatMessage(string $question, User $user, Warehouse $warehouse): array
    {
        $language = $this->detectQuestionLanguage($question);
        $catalog = $this->buildCatalogFromConfig($warehouse);

        if ($catalog === []) {
            return [
                'type' => 'error',
                'message' => $language === 'nl'
                    ? 'Er zijn geen AI-chartmodellen geconfigureerd.'
                    : 'No AI chart models are configured.',
                'chart' => null,
            ];
        }

        $countAnswer = $this->buildCountAnswerFromQuestion($question, $catalog, $warehouse);

        if ($countAnswer) {
            return [
                'type' => 'answer',
                'message' => $countAnswer,
                'chart' => null,
            ];
        }

        try {
            $chart = $this->createFromQuestion($question, $user, $warehouse);

            return [
                'type' => 'chart',
                'message' => $language === 'nl'
                    ? 'Ik heb grafiek "'.$chart->title.'" gegenereerd.'
                    : 'I generated chart "'.$chart->title.'".',
                'chart' => $chart,
            ];
        } catch (AiChartClarificationException $exception) {
            return [
                'type' => 'clarification',
                'message' => $exception->clarificationQuestion,
                'chart' => null,
            ];
        } catch (DomainException $exception) {
            return [
                'type' => 'error',
                'message' => $exception->getMessage(),
                'chart' => null,
            ];
        }
    }

    public function createFromQuestion(string $question, User $user, Warehouse $warehouse): DynamicAiChart
    {
        $catalog = $this->buildCatalogFromConfig($warehouse);
        $questionFallback = $this->buildQuestionFallbackPlan($question, $catalog, $warehouse);

        if ($catalog === []) {
            throw new DomainException('No AI chart models are configured.');
        }

        $agent = new DynamicChartPlannerAgent($catalog);

        $response = $agent
            ->forUser($user)
            ->continueLastConversation($user)
            ->prompt(
                prompt: $this->buildPrompt($question, $catalog),
                provider: config('ai_charts.provider', 'mistral'),
            );

        if (! $response instanceof StructuredAgentResponse) {
            throw new DomainException('The AI response did not contain structured chart data.');
        }

        $plan = $response->toArray();

        $possible = (bool) ($plan['possible'] ?? false);
        $clarificationQuestion = trim((string) ($plan['clarification_question'] ?? ''));

        $sql = null;
        $bindings = null;
        $metricKey = null;
        $chartType = $this->normalizeChartType((string) ($plan['chart_type'] ?? 'bar'));
        $resolvedModelKey = (string) ($plan['model_key'] ?? '');
        $fallbackReason = null;
        $title = trim((string) ($plan['title'] ?? 'AI Chart'));

        if (! $possible) {
            if (! $questionFallback) {
                $reason = (string) ($plan['reason'] ?? 'The question cannot be answered with the configured models.');

                if ($clarificationQuestion !== '') {
                    throw new AiChartClarificationException(
                        clarificationQuestion: $clarificationQuestion,
                        message: $reason,
                    );
                }

                throw new DomainException($reason);
            }

            $resolvedModelKey = $questionFallback['model_key'];
            $metricKey = $questionFallback['metric_key'];
            $sql = $questionFallback['sql'];
            $bindings = $questionFallback['bindings'];
            $chartType = $questionFallback['chart_type'];
            $title = $questionFallback['title'] ?? $title;
            $fallbackReason = 'AI marked question as impossible; fallback query planner was used.';
        }

        if (! $sql || ! is_array($bindings) || ! is_string($metricKey)) {
            if (! array_key_exists($resolvedModelKey, $catalog)) {
                if (! $questionFallback) {
                    throw new DomainException('The AI selected a model that is not allowed in ai_charts config.');
                }

                $resolvedModelKey = $questionFallback['model_key'];
                $metricKey = $questionFallback['metric_key'];
                $sql = $questionFallback['sql'];
                $bindings = $questionFallback['bindings'];
                $chartType = $questionFallback['chart_type'];
                $title = $questionFallback['title'] ?? $title;
                $fallbackReason = 'AI selected a non-allowed model; fallback query planner was used.';
            } else {
                try {
                    ['sql' => $sql, 'bindings' => $bindings, 'metric_key' => $metricKey] = $this->buildQueryFromPlan(
                        plan: $plan,
                        modelMeta: $catalog[$resolvedModelKey],
                        warehouse: $warehouse,
                    );
                } catch (DomainException $exception) {
                    if (! $questionFallback) {
                        if ($clarificationQuestion !== '') {
                            throw new AiChartClarificationException(
                                clarificationQuestion: $clarificationQuestion,
                                message: $exception->getMessage(),
                            );
                        }

                        throw $exception;
                    }

                    $resolvedModelKey = $questionFallback['model_key'];
                    $metricKey = $questionFallback['metric_key'];
                    $sql = $questionFallback['sql'];
                    $bindings = $questionFallback['bindings'];
                    $chartType = $questionFallback['chart_type'];
                    $title = $questionFallback['title'] ?? $title;
                    $fallbackReason = $exception->getMessage();
                }
            }
        }

        $chart = DynamicAiChart::query()->create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'title' => $title !== '' ? $title : 'AI Chart',
            'question' => $question,
            'chart_type' => $chartType,
            'selected_model' => $resolvedModelKey,
            'metric_key' => $metricKey,
            'query_sql' => $sql,
            'query_bindings' => $bindings,
            'meta' => [
                'agent_reason' => (string) ($plan['reason'] ?? ''),
                'fallback_reason' => $fallbackReason,
                'query_plan' => Arr::only($plan, [
                    'mode',
                    'aggregate',
                    'clarification_question',
                    'group_by_column',
                    'value_column',
                    'value_columns_json',
                    'filters_json',
                    'limit',
                ]),
            ],
        ]);

        $payload = $this->buildChartPayload($chart);

        $chart->update([
            'chart_payload' => $payload,
        ]);

        return $chart->fresh();
    }

    public function buildChartPayload(DynamicAiChart $chart): array
    {
        $rows = DB::select($chart->query_sql, $chart->query_bindings ?? []);

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = (string) ($row->label ?? 'Unknown');
            $values[] = (int) ($row->value ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $chart->title,
                    'data' => $values,
                ],
            ],
        ];
    }

    private function buildPrompt(string $question, array $catalog): string
    {
        return implode("\n", [
            'User question: '.$question,
            'Use only the catalog entries below.',
            'If unsupported, set possible=false.',
            'For grouped charts use mode=grouped_aggregate and set group_by_column + value_column.',
            'For totals over multiple numeric columns use mode=column_totals and set value_columns_json.',
            'filters_json must be valid JSON array: [{"column":"status","operator":"=","value":"confirmed"}]',
            'value_columns_json must be valid JSON array of column names.',
            'Catalog JSON: '.json_encode($catalog, JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function buildCatalogFromConfig(Warehouse $warehouse): array
    {
        $configuredModels = config('ai_charts.allowed_models', []);
        $catalog = [];

        foreach ($configuredModels as $key => $configEntry) {
            $modelClass = null;
            $description = '';

            if (is_string($configEntry)) {
                $modelClass = $configEntry;
            }

            if (is_array($configEntry)) {
                $modelClass = $configEntry['model'] ?? null;
                $description = (string) ($configEntry['description'] ?? '');
            }

            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                continue;
            }

            $model = app($modelClass);

            if (! $model instanceof Model) {
                continue;
            }

            $table = $model->getTable();
            $columnsMeta = Schema::getColumns($table);
            $columns = array_values(array_map(fn (array $column): string => $column['name'], $columnsMeta));
            $columnTypes = collect($columnsMeta)
                ->mapWithKeys(fn (array $column): array => [$column['name'] => (string) ($column['type_name'] ?? $column['type'] ?? '')])
                ->all();

            $numericColumns = collect($columnsMeta)
                ->filter(fn (array $column): bool => $this->isNumericColumn($column))
                ->map(fn (array $column): string => $column['name'])
                ->values()
                ->all();

            $catalog[(string) $key] = [
                'model' => $modelClass,
                'table' => $table,
                'description' => $description !== '' ? $description : class_basename($modelClass).' model',
                'columns' => $columns,
                'column_types' => $columnTypes,
                'numeric_columns' => $numericColumns,
                'has_warehouse_column' => in_array('warehouse_id', $columns, true),
                'row_count' => $this->estimateRowCount($table, $columns, $warehouse),
                'sample_values' => $this->extractSampleValues($table, $columnsMeta, $warehouse),
                'relation_hints' => $this->extractRelationHints($columns),
            ];
        }

        return $catalog;
    }

    private function buildQueryFromPlan(array $plan, array $modelMeta, Warehouse $warehouse): array
    {
        $table = (string) $modelMeta['table'];
        $columns = $modelMeta['columns'];
        $numericColumns = $modelMeta['numeric_columns'];
        $mode = (string) ($plan['mode'] ?? '');
        $aggregate = $this->normalizeAggregate((string) ($plan['aggregate'] ?? 'sum'));
        $filters = $this->parseFiltersJson((string) ($plan['filters_json'] ?? '[]'));
        $limit = max(0, (int) ($plan['limit'] ?? 10));

        ['sql' => $whereSql, 'bindings' => $whereBindings] = $this->buildWhereClause(
            modelMeta: $modelMeta,
            filters: $filters,
            warehouse: $warehouse,
        );

        if ($mode === 'grouped_aggregate') {
            $groupByColumn = (string) ($plan['group_by_column'] ?? '');
            $valueColumn = (string) ($plan['value_column'] ?? '');

            if (! in_array($groupByColumn, $columns, true)) {
                throw new DomainException('AI selected an invalid group_by_column for the chosen model.');
            }

            if ($aggregate !== 'count' && ! in_array($valueColumn, $numericColumns, true)) {
                throw new DomainException('AI selected a non-numeric value_column for the chosen aggregate.');
            }

            $aggregateSql = $aggregate === 'count'
                ? 'count(*)'
                : $aggregate.'(m.`'.$valueColumn.'`)';

            $sql = 'select cast(m.`'.$groupByColumn.'` as char) as label, coalesce('.$aggregateSql.', 0) as value from `'.$table.'` as m'.$whereSql.' group by m.`'.$groupByColumn.'` order by value desc';
            $bindings = $whereBindings;

            if ($limit > 0) {
                $sql .= ' limit ?';
                $bindings[] = $limit;
            }

            return [
                'sql' => $sql,
                'bindings' => $bindings,
                'metric_key' => 'grouped_aggregate:'.$aggregate,
            ];
        }

        if ($mode === 'column_totals') {
            $valueColumns = $this->parseValueColumnsJson((string) ($plan['value_columns_json'] ?? '[]'));

            if ($valueColumns === []) {
                throw new DomainException('AI did not provide value columns for a column_totals chart.');
            }

            $valueColumns = array_values(array_filter($valueColumns, fn (string $column): bool => in_array($column, $numericColumns, true)));

            if ($valueColumns === []) {
                if ($table === 'products' && Schema::hasTable('stock_products')) {
                    $requestedColumns = $this->parseValueColumnsJson((string) ($plan['value_columns_json'] ?? '[]'));
                    $stockColumns = [
                        'on_stock_quantity',
                        'reserved_quantity',
                        'reserved_on_picklists',
                        'free_on_stock_quantity',
                    ];

                    $stockValueColumns = array_values(array_filter($requestedColumns, fn (string $column): bool => in_array($column, $stockColumns, true)));

                    if ($stockValueColumns !== []) {
                        $stockColumnsMeta = Schema::getColumns('stock_products');
                        $stockColumnNames = array_values(array_map(fn (array $column): string => $column['name'], $stockColumnsMeta));
                        $stockNumericColumns = collect($stockColumnsMeta)
                            ->filter(fn (array $column): bool => $this->isNumericColumn($column))
                            ->map(fn (array $column): string => $column['name'])
                            ->values()
                            ->all();

                        return $this->buildQueryFromPlan(
                            plan: [
                                'mode' => 'column_totals',
                                'aggregate' => $aggregate,
                                'value_columns_json' => json_encode($stockValueColumns, JSON_UNESCAPED_SLASHES),
                                'filters_json' => json_encode($filters, JSON_UNESCAPED_SLASHES),
                                'limit' => $limit,
                            ],
                            modelMeta: [
                                'table' => 'stock_products',
                                'columns' => $stockColumnNames,
                                'numeric_columns' => $stockNumericColumns,
                                'has_warehouse_column' => in_array('warehouse_id', $stockColumnNames, true),
                            ],
                            warehouse: $warehouse,
                        );
                    }
                }

                throw new DomainException('AI selected only non-numeric columns for a totals chart.');
            }

            $queryParts = [];
            $bindings = [];

            foreach ($valueColumns as $column) {
                $aggregateSql = $aggregate === 'count'
                    ? 'count(m.`'.$column.'`)'
                    : $aggregate.'(m.`'.$column.'`)';

                $queryParts[] = 'select ? as label, coalesce('.$aggregateSql.', 0) as value from `'.$table.'` as m'.$whereSql;
                $bindings[] = str($column)->replace('_', ' ')->title()->toString();
                array_push($bindings, ...$whereBindings);
            }

            return [
                'sql' => implode(' union all ', $queryParts),
                'bindings' => $bindings,
                'metric_key' => 'column_totals:'.$aggregate,
            ];
        }

        throw new DomainException('AI selected an unsupported query mode.');
    }

    private function buildFallbackPlanFromQuestion(string $question, array $catalog, Warehouse $warehouse): ?array
    {
        $normalizedQuestion = str($question)->lower()->toString();

        $columnKeywordMap = [
            'on_stock_quantity' => ['on stock', 'onstock', 'in stock', 'instock', 'stock'],
            'reserved_quantity' => ['reserved', 'reserve'],
            'reserved_on_picklists' => ['picked', 'picklist', 'picklists'],
            'free_on_stock_quantity' => ['free', 'available'],
        ];

        $matchedColumns = [];

        foreach ($columnKeywordMap as $column => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedQuestion, $keyword)) {
                    $matchedColumns[] = $column;

                    break;
                }
            }
        }

        $matchedColumns = array_values(array_unique($matchedColumns));

        if ($matchedColumns === []) {
            return null;
        }

        $candidateModelKeys = collect($catalog)
            ->filter(function (array $modelMeta) use ($matchedColumns): bool {
                $numericColumns = $modelMeta['numeric_columns'] ?? [];

                return collect($matchedColumns)->contains(fn (string $column): bool => in_array($column, $numericColumns, true));
            })
            ->keys()
            ->values()
            ->all();

        if ($candidateModelKeys === []) {
            return null;
        }

        $modelKey = in_array('stock_products', $candidateModelKeys, true)
            ? 'stock_products'
            : $candidateModelKeys[0];

        $modelMeta = $catalog[$modelKey];
        $numericColumns = $modelMeta['numeric_columns'] ?? [];

        $valueColumns = array_values(array_filter($matchedColumns, fn (string $column): bool => in_array($column, $numericColumns, true)));

        if ($valueColumns === []) {
            return null;
        }

        ['sql' => $sql, 'bindings' => $bindings, 'metric_key' => $metricKey] = $this->buildQueryFromPlan(
            plan: [
                'mode' => 'column_totals',
                'aggregate' => 'sum',
                'value_columns_json' => json_encode($valueColumns, JSON_UNESCAPED_SLASHES),
                'filters_json' => '[]',
                'limit' => 0,
            ],
            modelMeta: $modelMeta,
            warehouse: $warehouse,
        );

        return [
            'model_key' => $modelKey,
            'metric_key' => $metricKey,
            'sql' => $sql,
            'bindings' => $bindings,
            'chart_type' => 'doughnut',
            'title' => 'Stock totals',
        ];
    }

    private function buildQuestionFallbackPlan(string $question, array $catalog, Warehouse $warehouse): ?array
    {
        return $this->buildFallbackPlanFromQuestion($question, $catalog, $warehouse)
            ?? $this->buildCountChartFallbackFromQuestion($question, $catalog, $warehouse);
    }

    private function buildCountChartFallbackFromQuestion(string $question, array $catalog, Warehouse $warehouse): ?array
    {
        $modelKey = $this->detectModelKeyFromQuestion($question, $catalog);

        if (! $modelKey) {
            return null;
        }

        if (! $this->isCountQuestion($question)) {
            return null;
        }

        $modelMeta = $catalog[$modelKey];

        ['sql' => $whereSql, 'bindings' => $whereBindings] = $this->buildWhereClause(
            modelMeta: $modelMeta,
            filters: [],
            warehouse: $warehouse,
        );

        $table = (string) $modelMeta['table'];
        $label = str($modelKey)->replace('_', ' ')->title()->toString();

        return [
            'model_key' => $modelKey,
            'metric_key' => 'total_count',
            'sql' => 'select ? as label, count(*) as value from `'.$table.'` as m'.$whereSql,
            'bindings' => [
                $label,
                ...$whereBindings,
            ],
            'chart_type' => 'bar',
            'title' => $label.' in database',
        ];
    }

    private function buildCountAnswerFromQuestion(string $question, array $catalog, Warehouse $warehouse): ?string
    {
        $language = $this->detectQuestionLanguage($question);
        $modelKey = $this->detectModelKeyFromQuestion($question, $catalog);

        if (! $modelKey) {
            return null;
        }

        if (! $this->isCountQuestion($question)) {
            return null;
        }

        $modelMeta = $catalog[$modelKey];
        $table = (string) $modelMeta['table'];
        $columns = $modelMeta['columns'];

        $query = DB::table($table);

        if (($modelMeta['has_warehouse_column'] ?? false) === true) {
            $query->where('warehouse_id', $warehouse->id);
        }

        if ($table === 'stock_products' && in_array('product_id', $columns, true)) {
            $query->whereExists(function ($subQuery) use ($warehouse): void {
                $subQuery->selectRaw('1')
                    ->from('products as tenant_products')
                    ->whereColumn('tenant_products.id', 'stock_products.product_id')
                    ->where('tenant_products.warehouse_id', $warehouse->id);
            });
        }

        $count = (int) $query->count();
        $label = $language === 'nl'
            ? $this->translateModelLabelToDutch($modelKey)
            : $this->translateModelLabelToEnglish($modelKey);

        if ($language === 'nl') {
            return 'Er '.($count === 1 ? 'is' : 'zijn').' '.$count.' '.$label.' in de database.';
        }

        return 'There '.($count === 1 ? 'is' : 'are').' '.$count.' '.$label.' in the database.';
    }

    private function detectModelKeyFromQuestion(string $question, array $catalog): ?string
    {
        $normalizedQuestion = ' '.str($question)->lower()->toString().' ';
        $questionTokens = collect(preg_split('/[^a-z0-9_]+/i', str($question)->lower()->toString()) ?: [])
            ->filter(fn (string $token): bool => $token !== '')
            ->values()
            ->all();

        foreach ($catalog as $modelKey => $modelMeta) {
            $table = str($modelMeta['table'])->lower()->toString();
            $baseModel = str(class_basename((string) $modelMeta['model']))->snake()->lower()->toString();

            $candidates = array_unique([
                str($modelKey)->lower()->toString(),
                str($modelKey)->lower()->singular()->toString(),
                $table,
                str($table)->singular()->toString(),
                $baseModel,
                str($baseModel)->singular()->toString(),
            ]);

            foreach ($candidates as $candidate) {
                $candidate = trim($candidate);

                if ($candidate === '') {
                    continue;
                }

                if (str_contains($normalizedQuestion, ' '.$candidate.' ')) {
                    return (string) $modelKey;
                }

                foreach ($questionTokens as $token) {
                    if ($token === $candidate) {
                        return (string) $modelKey;
                    }

                    if (str($token)->replaceEnd('en', '')->toString() === $candidate) {
                        return (string) $modelKey;
                    }

                    if (str($token)->replaceEnd('s', '')->toString() === $candidate) {
                        return (string) $modelKey;
                    }
                }
            }
        }

        return null;
    }

    private function isCountQuestion(string $question): bool
    {
        $normalizedQuestion = str($question)->lower()->toString();

        $countPhrases = [
            'how many',
            'how much',
            'hoeveel',
            'aantal',
        ];

        foreach ($countPhrases as $phrase) {
            if (str_contains($normalizedQuestion, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function translateModelLabelToDutch(string $modelKey): string
    {
        $translations = [
            'products' => 'producten',
            'orders' => 'orders',
            'stock_products' => 'voorraadproducten',
        ];

        return $translations[$modelKey] ?? str($modelKey)->replace('_', ' ')->toString();
    }

    private function translateModelLabelToEnglish(string $modelKey): string
    {
        $translations = [
            'products' => 'products',
            'orders' => 'orders',
            'stock_products' => 'stock products',
        ];

        return $translations[$modelKey] ?? str($modelKey)->replace('_', ' ')->toString();
    }

    private function detectQuestionLanguage(string $question): string
    {
        $normalized = ' '.str($question)->lower()->toString().' ';

        $dutchMarkers = [
            ' hoeveel ',
            ' aantal ',
            ' zijn er ',
            ' in de database ',
            ' kan je ',
            ' kun je ',
        ];

        foreach ($dutchMarkers as $marker) {
            if (str_contains($normalized, $marker)) {
                return 'nl';
            }
        }

        return 'en';
    }

    private function estimateRowCount(string $table, array $columns, Warehouse $warehouse): int
    {
        try {
            $query = DB::table($table);

            if (in_array('warehouse_id', $columns, true)) {
                $query->where('warehouse_id', $warehouse->id);
            }

            return (int) $query->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function extractSampleValues(string $table, array $columnsMeta, Warehouse $warehouse): array
    {
        $sampleValues = [];

        $candidateColumns = collect($columnsMeta)
            ->filter(fn (array $column): bool => in_array($column['type_name'], ['string', 'text', 'varchar', 'char'], true))
            ->take(4)
            ->values()
            ->all();

        foreach ($candidateColumns as $column) {
            $columnName = $column['name'];

            try {
                $query = DB::table($table)
                    ->select($columnName)
                    ->whereNotNull($columnName);

                if (Schema::hasColumn($table, 'warehouse_id')) {
                    $query->where('warehouse_id', $warehouse->id);
                }

                $values = $query
                    ->distinct()
                    ->limit(5)
                    ->pluck($columnName)
                    ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                    ->map(fn (string $value): string => mb_substr($value, 0, 40))
                    ->values()
                    ->all();

                if ($values !== []) {
                    $sampleValues[$columnName] = $values;
                }
            } catch (Throwable) {
            }
        }

        return $sampleValues;
    }

    private function extractRelationHints(array $columns): array
    {
        return collect($columns)
            ->filter(fn (string $column): bool => str_ends_with($column, '_id'))
            ->map(fn (string $column): string => str($column)->beforeLast('_id')->snake()->toString())
            ->values()
            ->all();
    }

    private function buildWhereClause(array $modelMeta, array $filters, Warehouse $warehouse): array
    {
        $clauses = [];
        $bindings = [];
        $columns = $modelMeta['columns'];

        if (($modelMeta['has_warehouse_column'] ?? false) === true) {
            $clauses[] = 'm.`warehouse_id` = ?';
            $bindings[] = $warehouse->id;
        }

        if (($modelMeta['table'] ?? null) === 'stock_products' && in_array('product_id', $columns, true)) {
            $clauses[] = 'exists (select 1 from `products` as tenant_products where tenant_products.`id` = m.`product_id` and tenant_products.`warehouse_id` = ?)';
            $bindings[] = $warehouse->id;
        }

        foreach ($filters as $filter) {
            $column = (string) ($filter['column'] ?? '');
            $operator = (string) ($filter['operator'] ?? '=');
            $value = $filter['value'] ?? null;

            if (! in_array($column, $columns, true)) {
                continue;
            }

            if (! in_array($operator, ['=', '!=', '>', '>=', '<', '<='], true)) {
                continue;
            }

            $clauses[] = 'm.`'.$column.'` '.$operator.' ?';
            $bindings[] = $value;
        }

        if ($clauses === []) {
            return ['sql' => '', 'bindings' => []];
        }

        return [
            'sql' => ' where '.implode(' and ', $clauses),
            'bindings' => $bindings,
        ];
    }

    private function parseValueColumnsJson(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(function (mixed $value): string {
            if (! is_string($value)) {
                return '';
            }

            return $this->normalizeColumnAlias($value);
        }, $decoded)));
    }

    private function normalizeColumnAlias(string $column): string
    {
        $normalized = str($column)
            ->lower()
            ->replaceMatches('/[^a-z0-9_]+/', '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();

        $normalized = str($normalized)
            ->replaceEnd('_products', '')
            ->replaceEnd('_product', '')
            ->toString();

        $aliases = [
            'onstock_quantity' => 'on_stock_quantity',
            'on_stock' => 'on_stock_quantity',
            'reserved' => 'reserved_quantity',
            'reserved_quantity_products' => 'reserved_quantity',
            'reserved_on_picklist' => 'reserved_on_picklists',
            'picked_quantity' => 'reserved_on_picklists',
            'free_onstock_quantity' => 'free_on_stock_quantity',
            'free_stock_quantity' => 'free_on_stock_quantity',
            'free_on_stock' => 'free_on_stock_quantity',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    private function isNumericColumn(array $column): bool
    {
        $typeName = str((string) ($column['type_name'] ?? ''))->lower()->toString();
        $type = str((string) ($column['type'] ?? ''))->lower()->toString();
        $candidate = $typeName !== '' ? $typeName : $type;

        if ($candidate === '') {
            return false;
        }

        return str($candidate)->contains(['int', 'decimal', 'double', 'float', 'real', 'numeric']);
    }

    private function parseFiltersJson(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn (mixed $filter): bool => is_array($filter)));
    }

    private function normalizeAggregate(string $aggregate): string
    {
        $allowed = ['count', 'sum', 'avg', 'min', 'max'];

        if (! in_array($aggregate, $allowed, true)) {
            return 'sum';
        }

        return $aggregate;
    }

    private function normalizeChartType(string $chartType): string
    {
        $allowed = ['bar', 'line', 'pie', 'doughnut', 'polarArea', 'radar'];

        if (! in_array($chartType, $allowed, true)) {
            return 'bar';
        }

        return $chartType;
    }
}
