<?php

declare(strict_types=1);

namespace OpenWms\FilamentAiAutosuggestField\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class AiAutosuggestAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly string $fieldLabel,
        private readonly int $maxSuggestions,
        private readonly array $modelContext,
    ) {}

    public function instructions(): Stringable|string
    {
        return implode("\n", [
            'You are an assistant that creates concise autosuggest values for a form field.',
            'Return only high-confidence suggestions based on the user input and context data.',
            'Do not invent fake IDs, references, or sensitive values.',
            'Suggestions must be short plain text values appropriate for a single form input.',
            'Return exactly the JSON schema fields requested.',
            'Field label: '.$this->fieldLabel,
            'Maximum suggestions: '.$this->maxSuggestions,
            'Context models/data: '.json_encode($this->modelContext, JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'reason' => $schema->string()->required(),
            'suggestions_json' => $schema->string()->required(),
        ];
    }
}
