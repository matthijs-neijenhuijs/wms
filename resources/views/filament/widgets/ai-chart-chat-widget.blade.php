<x-filament-widgets::widget class="fi-wi-ai-chart-chat">
    <x-filament::section
        heading="AI Chart Assistant"
        description="Ask about allowed models. I can answer directly, ask a clarification, or generate a chart."
    >
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="max-height: 20rem; overflow-y: auto; border: 1px solid rgb(229 231 235); border-radius: 0.75rem; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                @forelse ($messages as $entry)
                    <div style="display: flex; justify-content: {{ $entry['role'] === 'user' ? 'flex-end' : 'flex-start' }};">
                        <div style="max-width: 75%; white-space: normal;">
                            <x-filament::badge
                                :color="$entry['role'] === 'user' ? 'warning' : 'success'"
                                style="white-space: normal; line-height: 1.5; padding: 0.5rem 0.75rem;"
                            >
                                {{ $entry['content'] }}
                            </x-filament::badge>
                        </div>
                    </div>
                @empty
                    <p style="margin-bottom: 0.75rem; font-size: 0.875rem; color: rgb(107 114 128);">Start the conversation by asking a data question, for example: “How many products are in the database?”</p>
                @endforelse
            </div>

            <form wire:submit="send" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <x-filament::input.wrapper>
                    <x-filament::input
                        wire:model="message"
                        placeholder="Type your question..."
                    />
                </x-filament::input.wrapper>

                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                    <x-filament::button type="submit">
                        Send
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        color="gray"
                        wire:click="clearChat"
                    >
                        Clear
                    </x-filament::button>
                </div>
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
