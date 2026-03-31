<div class="fi-ta">
    <div class="fi-ta-ctn fi-ta-ctn-with-header">
        <div class="fi-ta-main">
            <div class="fi-ta-content-ctn fi-fixed-positioning-context">
                <div class="overflow-x-auto">
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
                                <tr wire:key="purchase-order-product-{{ $product->id }}-{{ (int) $product->scanned }}" @if($product->scanned) style="background-color: #bbf7d0;" @endif>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $product->reference_code }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $product->product_title }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $product->barcode }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $product->updated_at?->format('M d, Y') ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-icon">
                                                @if($product->scanned)
                                                    <x-filament::icon icon="heroicon-m-check" class="fi-icon fi-size-sm text-success-600" />
                                                @else
                                                    <x-filament::icon icon="heroicon-m-x-mark" class="fi-icon fi-size-sm text-danger-600" />
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="fi-ta-cell text-center text-sm text-gray-500 dark:text-gray-400">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">No products found</span>
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
