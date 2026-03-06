<?php

namespace OpenWms\FilamentDynamicAiCharts\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class DynamicChartPlannerAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;
    use RemembersConversations;

    public function __construct(private readonly array $catalog = []) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return implode("\n", [
            'You are a warehouse analytics expert that designs Filament chart widgets.',
            'You may only use the provided catalog of allowed models and columns.',
            'If the request is ambiguous or cannot be answered with confidence, set possible=false.',
            'When possible=false, set clarification_question with exactly one concrete follow-up question for the user.',
            'When possible=true, choose a chart type and a safe query plan.',
            'Use mode grouped_aggregate for grouped charts or column_totals for totals over multiple numeric columns.',
            'Prefer concise, human-readable titles for charts.',
            'Catalog: '.json_encode($this->catalog, JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'possible' => $schema->boolean()->required(),
            'title' => $schema->string()->required(),
            'reason' => $schema->string()->required(),
            'clarification_question' => $schema->string()->required(),
            'model_key' => $schema->string()->required(),
            'chart_type' => $schema->string()->required(),
            'mode' => $schema->string()->required(),
            'aggregate' => $schema->string()->required(),
            'group_by_column' => $schema->string()->required(),
            'value_column' => $schema->string()->required(),
            'value_columns_json' => $schema->string()->required(),
            'filters_json' => $schema->string()->required(),
            'limit' => $schema->integer()->required(),
        ];
    }
}
