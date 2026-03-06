<?php

namespace OpenWms\FilamentDynamicAiCharts\Filament\Widgets;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use OpenWms\FilamentDynamicAiCharts\Services\AiCharts\DynamicChartGenerator;

class AiChartChatWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament-dynamic-ai-charts::widgets.ai-chart-chat-widget';

    protected int|string|array $columnSpan = 'full';

    public string $message = '';

    /**
     * @var array<int, array{role: string, content: string, chart_id?: int|null}>
     */
    public array $messages = [];

    public function mount(): void
    {
        $this->messages = session('ai_chart_chat_messages', []);
    }

    public function send(DynamicChartGenerator $generator): void
    {
        $question = trim($this->message);

        if ($question === '') {
            return;
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        $this->message = '';

        $user = auth()->user();

        if (! $user instanceof User) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'You must be logged in to use the chart assistant.',
            ];

            $this->persistMessages();

            return;
        }

        $warehouse = Filament::getTenant();

        if (! $warehouse) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'No active warehouse found.',
            ];

            $this->persistMessages();

            return;
        }

        $result = $generator->handleChatMessage($question, $user, $warehouse);

        if (($result['type'] ?? '') === 'chart' && ($result['chart'] ?? null)) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => (string) ($result['message'] ?? 'Chart generated.'),
                'chart_id' => $result['chart']->id,
            ];

            $this->persistMessages();

            $redirectUrl = request()->headers->get('referer') ?: url('/');

            $this->redirect($redirectUrl, navigate: true);

            return;
        }

        $this->messages[] = [
            'role' => 'assistant',
            'content' => (string) ($result['message'] ?? 'I could not process that request.'),
        ];

        $this->persistMessages();
    }

    public function clearChat(): void
    {
        $this->messages = [];
        $this->persistMessages();
    }

    private function persistMessages(): void
    {
        session(['ai_chart_chat_messages' => array_slice($this->messages, -50)]);
    }
}
