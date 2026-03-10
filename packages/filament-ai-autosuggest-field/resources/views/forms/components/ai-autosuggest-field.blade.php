@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $suggestions = $getSuggestions();
    $placeholder = $getPlaceholder();
    $id = $getId();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <x-filament::input.wrapper :disabled="$isDisabled" :valid="! $errors->has($statePath)">
        <x-filament::input
            :attributes="
                \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                    ->merge([
                        'id' => $id,
                        'disabled' => $isDisabled,
                        'placeholder' => filled($placeholder) ? e($placeholder) : null,
                        $applyStateBindingModifiers('wire:model') => $statePath,
                    ], escape: false)
                    ->class(['fi-input'])
            "
            type="text"
        />
    </x-filament::input.wrapper>

    @if (filled($suggestions))
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($suggestions as $suggestion)
                <x-filament::button
                    :wire:key="$id . '-suggestion-' . md5((string) $suggestion)"
                    size="xs"
                    color="gray"
                    type="button"
                    x-on:click="$wire.set({{ \Illuminate\Support\Js::from($statePath) }}, {{ \Illuminate\Support\Js::from($suggestion) }})"
                >
                    {{ $suggestion }}
                </x-filament::button>
            @endforeach
        </div>
    @endif
</x-dynamic-component>
