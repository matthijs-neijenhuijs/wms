@php
    /** @var \Illuminate\Support\Collection<int, \Modules\Orders\Models\PurchaseOrderIncomingBatch> $batches */
@endphp

<div class="fi-in-incoming-stock">
    @if ($batches->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('No incoming stock expected.') }}
        </p>
    @else
        <ul class="space-y-1">
            @foreach ($batches as $batch)
                <li class="text-sm">
                    {{ __(':quantity units expected on :date', [
                        'quantity' => $batch->quantity,
                        'date' => \Illuminate\Support\Carbon::parse($batch->expected_delivery_date)->toFormattedDateString(),
                    ]) }}
                </li>
            @endforeach
        </ul>
    @endif
</div>
