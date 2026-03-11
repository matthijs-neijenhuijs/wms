<div class="fi-ta">
    <div class="fi-ta-ctn fi-ta-ctn-with-header">
        <div class="fi-ta-main">
            <div class="fi-ta-content-ctn fi-fixed-positioning-context">
                <div class="fi-ta-content overflow-x-auto">
                    <table class="fi-ta-table">
                        <thead>
                            <tr>
                                <th class="fi-ta-header-cell !text-left" style="width: 15%; text-align: left;">Reference Code</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 35%; text-align: left;">Name</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 15%; text-align: left;">Barcode</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 20%; text-align: left;">Updated</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 15%; text-align: left;">Scanned</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($getState() as $product)
                                <tr class="fi-ta-row">
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            {{ $product->reference_code }}
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            {{ $product->product_title }}
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            {{ $product->ean_code }}
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            {{ $product->updated_at?->format('M d, Y') ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            @if($product->scanned)
                                                <x-filament::icon
                                                    icon="heroicon-m-check"
                                                    class="fi-icon fi-size-sm text-success-600"
                                                />
                                            @else
                                                <x-filament::icon
                                                    icon="heroicon-m-x-mark"
                                                    class="fi-icon fi-size-sm text-danger-600"
                                                />
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="fi-ta-cell text-center text-sm text-gray-500 dark:text-gray-400">
                                        <div class="fi-ta-col">
                                            No products found
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
