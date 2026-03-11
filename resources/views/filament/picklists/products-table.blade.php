@php
    $products = $record->products ?? [];
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-700">
        <thead>
            <tr class="bg-gray-100 dark:bg-gray-800">
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left text-sm font-semibold">Reference Code</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left text-sm font-semibold">Name</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left text-sm font-semibold">Barcode</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left text-sm font-semibold">Updated</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-center text-sm font-semibold">Scanned</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr class="border-t border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                    <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm">
                        {{ $product->reference_code }}
                    </td>
                    <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm">
                        <div>
                            @if($product->product && $product->product->brand)
                                <strong>{{ $product->product->brand->name }} - {{ $product->product_title }}</strong>
                            @else
                                <strong>{{ $product->product_title }}</strong>
                            @endif
                        </div>
                        @if($product->product && $product->product->attributes->count())
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach($product->product->attributes as $attribute)
                                    <span class="fi-color fi-color-primary fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm" style="margin-right: 0.5rem; margin-bottom: 0.5rem;">
                                        {{ $attribute->attributeGroup->name }}: {{ $attribute->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm">
                        {{ $product->product->barcode ?? '-' }}
                    </td>
                    <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm">
                        {{ $product->updated_at?->format('M d, Y') ?? '-' }}
                    </td>
                    <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-center">
                        @if($product->scanned)
                            <svg class="inline-block h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        @else
                            <svg class="inline-block h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="border-t border-gray-300 dark:border-gray-700">
                    <td colspan="5" class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm text-center text-gray-500">No products found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
