<div class="fi-ta">
    <div class="fi-ta-ctn fi-ta-ctn-with-header">
        <div class="fi-ta-main">
            <div class="fi-ta-content-ctn fi-fixed-positioning-context">
                <div class="overflow-x-auto">
                    <table class="fi-ta-table">
                        <thead>
                            <tr>
                                <th class="fi-ta-header-cell !text-left" style="width: 15%; text-align: left;">Reference Code</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 30%; text-align: left;">Name</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 15%; text-align: left;">Barcode</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 10%; text-align: left;">Color</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 10%; text-align: left;">Size</th>
                                <th class="fi-ta-header-cell !text-left" style="width: 20%; text-align: left;">Times Scanned</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($getState() as $failedProduct)
                                <tr class="fi-ta-row" wire:key="failed-product-{{ $failedProduct->id }}-{{ $failedProduct->total_quantity_scanned }}">
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $failedProduct->reference_code ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $failedProduct->product_title ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $failedProduct->barcode }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $failedProduct->color ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">{{ $failedProduct->size ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text font-semibold text-danger-600">{{ $failedProduct->total_quantity_scanned }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="fi-ta-cell text-center text-sm text-gray-500 dark:text-gray-400">
                                        <div class="fi-ta-col">
                                            <span class="fi-ta-text">No failed scans</span>
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