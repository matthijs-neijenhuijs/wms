<?php

declare(strict_types=1);

namespace OpenWms\FilamentAiAutosuggestField\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use OpenWms\FilamentAiAutosuggestField\Services\AiAutosuggestService;

class AiAutosuggestField extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament-ai-autosuggest-field::forms.components.ai-autosuggest-field';

    /**
     * @var array<class-string>
     */
    protected array|Closure $contextModels = [];

    protected int|Closure|null $contextLimit = null;

    protected int|Closure|null $maxSuggestions = null;

    protected int|Closure|null $minCharacters = null;

    protected string|Closure|null $provider = null;

    protected string|Closure|null $aiModel = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->live(debounce: 800);

        $this->afterStateUpdated(function (AiAutosuggestField $component, mixed $state): void {
            $component->meta('suggestions', []);

            $query = trim((string) $state);

            if (mb_strlen($query) < $component->getMinCharacters()) {
                return;
            }

            $suggestions = app(AiAutosuggestService::class)->suggest(
                fieldLabel: $component->getLabel() ?: (string) $component->getName(),
                query: $query,
                contextModelClasses: $component->getContextModels(),
                maxSuggestions: $component->getMaxSuggestions(),
                contextLimit: $component->getContextLimit(),
                provider: $component->getProvider(),
                model: $component->getAiModel(),
            );

            $component->meta('suggestions', $suggestions);
        });
    }

    /**
     * @param  array<class-string> | Closure  $models
     */
    public function contextModels(array|Closure $models): static
    {
        $this->contextModels = $models;

        return $this;
    }

    public function contextLimit(int|Closure $limit): static
    {
        $this->contextLimit = $limit;

        return $this;
    }

    public function maxSuggestions(int|Closure $maxSuggestions): static
    {
        $this->maxSuggestions = $maxSuggestions;

        return $this;
    }

    public function minCharacters(int|Closure $minCharacters): static
    {
        $this->minCharacters = $minCharacters;

        return $this;
    }

    public function provider(string|Closure|null $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function aiModel(string|Closure|null $model): static
    {
        $this->aiModel = $model;

        return $this;
    }

    /**
     * @return array<class-string>
     */
    public function getContextModels(): array
    {
        $models = $this->evaluate($this->contextModels);

        if (! is_array($models)) {
            return [];
        }

        return array_values(array_filter($models, fn (mixed $model): bool => is_string($model)));
    }

    public function getContextLimit(): int
    {
        return (int) ($this->evaluate($this->contextLimit) ?? config('ai_autosuggest_field.default_context_limit', 5));
    }

    public function getMaxSuggestions(): int
    {
        return (int) ($this->evaluate($this->maxSuggestions) ?? config('ai_autosuggest_field.default_max_suggestions', 5));
    }

    public function getMinCharacters(): int
    {
        return (int) ($this->evaluate($this->minCharacters) ?? config('ai_autosuggest_field.default_min_characters', 3));
    }

    public function getProvider(): ?string
    {
        return $this->evaluate($this->provider);
    }

    public function getAiModel(): ?string
    {
        return $this->evaluate($this->aiModel) ?: config('ai_autosuggest_field.model');
    }

    public function getSuggestions(): array
    {
        $suggestions = $this->getMeta('suggestions');

        return is_array($suggestions) ? $suggestions : [];
    }
}
