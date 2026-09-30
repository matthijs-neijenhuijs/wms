<x-filament-panels::page>
    @php
        $warehouse = $this->getWarehouse();
        $locations = $this->getLocations();
        $smallIcon = \Filament\Support\Enums\IconSize::Small;
    @endphp

    <style>
        .wm-layout { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); align-items: start; }
        @media (min-width: 1024px) { .wm-layout { grid-template-columns: minmax(0, 1fr) 20rem; } }
        .wm-sidebar { display: flex; flex-direction: column; gap: 1.5rem; }
        .wm-hints { display: flex; flex-wrap: wrap; gap: 0.5rem 1.25rem; margin-bottom: 0.75rem; font-size: 0.75rem; color: var(--gray-500); }
        .wm-hint { display: inline-flex; align-items: center; gap: 0.375rem; }
        .wm-canvas-wrapper { overflow-x: auto; border-radius: 0.5rem; background: var(--gray-50); }
        .wm-canvas { display: block; width: 100%; outline: none; touch-action: none; }
        .wm-callout { display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 1rem; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; line-height: 1.25rem; background: color-mix(in oklab, var(--primary-500) 10%, transparent); color: var(--primary-700); }
        .wm-callout .fi-icon { flex-shrink: 0; margin-top: 0.125rem; }
        .wm-list { display: flex; flex-direction: column; gap: 0.5rem; margin: 0; padding: 0; list-style: none; }
        .wm-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-200); border-radius: 0.5rem; background: #fff; font-size: 0.875rem; cursor: grab; user-select: none; touch-action: none; box-shadow: 0 1px 2px rgb(0 0 0 / 0.05); transition: border-color 0.15s, opacity 0.15s; }
        .wm-item:hover { border-color: var(--primary-500); }
        .wm-item.wm-item-dragging { opacity: 0.5; cursor: grabbing; }
        .wm-item-handle { color: var(--gray-400); }
        .wm-item-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--gray-950); }
        .wm-empty { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1rem 0; text-align: center; font-size: 0.875rem; color: var(--gray-500); }
        .wm-empty .fi-icon { color: var(--success-500); }
        .wm-selection { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .wm-rank { display: inline-flex; align-items: center; justify-content: center; min-width: 1.5rem; height: 1.5rem; padding: 0 0.375rem; border-radius: 9999px; background: #92400e; color: #fff; font-size: 0.75rem; font-weight: 600; }
        .wm-selection-name { font-size: 0.875rem; font-weight: 500; color: var(--gray-950); }
        .wm-full-width { width: 100%; }
        .wm-notice { display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.875rem; color: var(--gray-600); }
        .wm-notice > .fi-icon { color: var(--warning-500); flex-shrink: 0; }
        .wm-notice p { margin: 0 0 0.5rem; }
        .wm-route-list { display: flex; flex-direction: column; gap: 0.375rem; margin: 0 0 1rem; padding: 0; list-style: none; font-size: 0.875rem; }
        .wm-route-item { display: flex; align-items: center; gap: 0.5rem; color: var(--gray-950); }
        .wm-route-item .wm-rank { flex-shrink: 0; }
        .wm-route-item.wm-route-pending { color: var(--gray-400); }
        .wm-route-item.wm-route-pending .wm-rank { background: var(--gray-300); }
        .wm-route-empty { margin: 0 0 1rem; font-size: 0.875rem; color: var(--gray-500); }
        .wm-button-row { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .dark .wm-route-item { color: #fff; }
        .dark .wm-route-item.wm-route-pending, .dark .wm-route-empty { color: var(--gray-400); }
        .dark .wm-route-item.wm-route-pending .wm-rank { background: var(--gray-600); }
        .dark .wm-hints, .dark .wm-empty { color: var(--gray-400); }
        .dark .wm-canvas-wrapper { background: rgb(255 255 255 / 0.05); }
        .dark .wm-callout { color: var(--primary-300); }
        .dark .wm-item { border-color: rgb(255 255 255 / 0.1); background: var(--gray-900); }
        .dark .wm-item:hover { border-color: var(--primary-400); }
        .dark .wm-item-name, .dark .wm-selection-name { color: #fff; }
        .dark .wm-notice { color: var(--gray-300); }
    </style>

    @if ($warehouse->floor_width === null || $warehouse->floor_height === null)
        <x-filament::section>
            <div class="wm-notice">
                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedExclamationTriangle" :size="\Filament\Support\Enums\IconSize::Large" />
                <div>
                    <p>{{ __("Set this warehouse's floor width and height before using the map.") }}</p>
                    {{ $this->editFloorSizeAction }}
                </div>
            </div>
        </x-filament::section>
    @else
        <div class="wm-layout" wire:ignore>
            <x-filament::section
                :heading="__('Floor Plan')"
                :description="__('Floor size: :width × :height', ['width' => (float) $warehouse->floor_width, 'height' => (float) $warehouse->floor_height])"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedMap"
            >
                <div class="wm-hints">
                    <span class="wm-hint">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedCursorArrowRays" :size="$smallIcon" />
                        {{ __('Click to select') }}
                    </span>
                    <span class="wm-hint">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowsPointingOut" :size="$smallIcon" />
                        {{ __('Drag to move') }}
                    </span>
                    <span class="wm-hint">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowsRightLeft" :size="$smallIcon" />
                        {{ __('Drag the bottom-right corner to resize') }}
                    </span>
                    <span class="wm-hint">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedTrash" :size="$smallIcon" />
                        {{ __('Click × or press Delete to remove') }}
                    </span>
                    <span class="wm-hint">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedFlag" :size="$smallIcon" />
                        {{ __('Use Edit Route to set the walking order') }}
                    </span>
                </div>

                <div class="wm-canvas-wrapper">
                    <canvas
                        id="warehouse-map-canvas"
                        class="wm-canvas"
                        width="960"
                        height="640"
                        tabindex="0"
                        data-route-hint="{{ __('Click locations in walking order') }}"
                    ></canvas>
                </div>
            </x-filament::section>

            <div class="wm-sidebar">
                <x-filament::section
                    :heading="__('Walking Route')"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedFlag"
                >
                    <div id="warehouse-map-route-editing" hidden>
                        <div class="wm-callout">
                            <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedCursorArrowRays" :size="$smallIcon" />
                            <span>{{ __("Click the locations on the floor plan in the order you want to walk them. Locations you don't click follow afterwards in their current order.") }}</span>
                        </div>
                    </div>

                    <ol id="warehouse-map-route-list" class="wm-route-list"></ol>
                    <p id="warehouse-map-route-empty" class="wm-route-empty" hidden>{{ __('Place locations on the floor plan to build a route.') }}</p>

                    <div id="warehouse-map-route-view-actions" class="wm-button-row">
                        <x-filament::button
                            id="warehouse-map-route-edit"
                            color="gray"
                            size="sm"
                            :icon="\Filament\Support\Icons\Heroicon::OutlinedPencilSquare"
                            class="wm-full-width"
                        >
                            {{ __('Edit Route') }}
                        </x-filament::button>
                    </div>

                    <div id="warehouse-map-route-edit-actions" class="wm-button-row" hidden>
                        <x-filament::button
                            id="warehouse-map-route-save"
                            size="sm"
                            :icon="\Filament\Support\Icons\Heroicon::OutlinedCheck"
                        >
                            {{ __('Save Route') }}
                        </x-filament::button>
                        <x-filament::button
                            id="warehouse-map-route-undo"
                            color="gray"
                            size="sm"
                            :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowUturnLeft"
                        >
                            {{ __('Undo') }}
                        </x-filament::button>
                        <x-filament::button
                            id="warehouse-map-route-cancel"
                            color="gray"
                            size="sm"
                            :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                        >
                            {{ __('Cancel') }}
                        </x-filament::button>
                    </div>
                </x-filament::section>

                <div id="warehouse-map-selection" hidden>
                    <x-filament::section
                        :heading="__('Selected Location')"
                        :icon="\Filament\Support\Icons\Heroicon::OutlinedMapPin"
                    >
                        <div class="wm-selection">
                            <span id="warehouse-map-selection-rank" class="wm-rank"></span>
                            <span id="warehouse-map-selection-name" class="wm-selection-name"></span>
                        </div>

                        <x-filament::button
                            id="warehouse-map-remove-button"
                            color="danger"
                            outlined
                            size="sm"
                            :icon="\Filament\Support\Icons\Heroicon::OutlinedTrash"
                            class="wm-full-width"
                        >
                            {{ __('Remove From Map') }}
                        </x-filament::button>
                    </x-filament::section>
                </div>

                <x-filament::section
                    :heading="__('Unplaced Locations')"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedQueueList"
                >
                    <div class="wm-callout">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedHandRaised" :size="$smallIcon" />
                        <span>{{ __('Drag a location from this list onto the floor plan to place it. Locations you remove from the map return here.') }}</span>
                    </div>

                    <ul id="warehouse-map-unplaced-list" class="wm-list">
                        @foreach ($locations as $location)
                            <li
                                class="wm-item"
                                data-location-id="{{ $location->id }}"
                                @if ($location->x !== null && $location->y !== null && $location->width !== null && $location->height !== null) hidden @endif
                            >
                                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::Bars2" :size="$smallIcon" class="wm-item-handle" />
                                <span class="wm-item-name">{{ $location->name }}</span>
                                <x-filament::badge color="gray" size="sm"><span data-rank-label>{{ $location->rank }}</span></x-filament::badge>
                            </li>
                        @endforeach
                    </ul>

                    <div id="warehouse-map-unplaced-empty" class="wm-empty" hidden>
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedCheckCircle" :size="\Filament\Support\Enums\IconSize::ExtraLarge" />
                        <span>{{ __('All locations are placed on the map.') }}</span>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <script type="application/json" id="warehouse-map-data">
            {!! json_encode([
                'floorWidth' => (float) $warehouse->floor_width,
                'floorHeight' => (float) $warehouse->floor_height,
                'locations' => $locations->map(fn ($location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'rank' => $location->rank,
                    'x' => $location->x === null ? null : (float) $location->x,
                    'y' => $location->y === null ? null : (float) $location->y,
                    'width' => $location->width === null ? null : (float) $location->width,
                    'height' => $location->height === null ? null : (float) $location->height,
                ])->values(),
            ]) !!}
        </script>

        @vite('resources/js/warehouse-map.js')
    @endif
</x-filament-panels::page>
