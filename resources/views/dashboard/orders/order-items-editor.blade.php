<div wire:key="order-items-editor-{{ $orderId }}">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">{{ __('dashboard.edit-items') }}</h4>

            @if ($this->isEditable && ! $showAddForm)
                <button type="button" class="btn btn-sm btn-primary" wire:click="openAddForm">
                    <i class="fa-solid fa-plus"></i> {{ __('dashboard.add-item') }}
                </button>
            @endif
        </div>

        <div class="card-body">
            @unless ($this->isEditable)
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-lock"></i> {{ __('dashboard.order-items-locked-notice') }}
                </p>
            @endunless

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('dashboard.product') }}</th>
                            <th>{{ __('dashboard.qty') }}</th>
                            <th>{{ __('dashboard.unit-price') }}</th>
                            <th>{{ __('dashboard.line-total') }}</th>
                            @if ($this->isEditable)
                                <th>{{ __('dashboard.actions') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->order->items as $item)
                            <tr wire:key="edit-item-{{ $item->id }}">
                                <td>
                                    <div class="fw-semibold">{{ $item->product_name }}</div>
                                    @if ($item->sku_label)
                                        <div class="small text-muted">{{ $item->sku_label }}</div>
                                    @endif
                                </td>
                                <td style="min-width: 130px">
                                    @if ($this->isEditable)
                                        <div class="input-group input-group-sm" style="max-width: 130px">
                                            <button type="button" class="btn btn-outline-secondary"
                                                wire:click="updateQuantity({{ $item->id }}, {{ max(1, $item->quantity - 1) }})">-</button>
                                            <input type="text" class="form-control text-center" readonly
                                                value="{{ $item->quantity }}">
                                            <button type="button" class="btn btn-outline-secondary"
                                                wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})">+</button>
                                        </div>
                                    @else
                                        {{ $item->quantity }}
                                    @endif
                                </td>
                                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td>{{ number_format((float) $item->line_total, 2) }}</td>
                                @if ($this->isEditable)
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                title="{{ __('dashboard.replace-item') }}"
                                                wire:click="openReplaceForm({{ $item->id }})">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                title="{{ __('dashboard.delete') }}"
                                                wire:click="deleteItem({{ $item->id }})"
                                                wire:confirm="{{ __('dashboard.are-you-sure') }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($showAddForm)
                <div class="border rounded p-1 mt-1">
                    <h6 class="mb-1">
                        {{ $replacingItemId ? __('dashboard.replace-item') : __('dashboard.add-item') }}
                    </h6>

                    <div class="row g-1">
                        <div class="col-md-5">
                            <input type="text" class="form-control" wire:model.live.debounce.400ms="productSearch"
                                placeholder="{{ __('dashboard.select-product') }}">

                            @if ($this->productOptions->isNotEmpty())
                                <div class="list-group mt-50" style="max-height: 200px; overflow-y: auto;">
                                    @foreach ($this->productOptions as $product)
                                        <button type="button"
                                            class="list-group-item list-group-item-action {{ $selectedProductId === $product->id ? 'active' : '' }}"
                                            wire:click="$set('selectedProductId', {{ $product->id }})">
                                            {{ $product->name }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('selectedProductId')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            @if ($this->selectedProduct?->has_variants)
                                <select class="form-select" wire:model="selectedSkuId">
                                    <option value="">{{ __('dashboard.select-variant') }}</option>
                                    @foreach ($this->selectedProduct->activeSkus as $sku)
                                        <option value="{{ $sku->id }}">
                                            {{ $sku->label() }} — {{ number_format((float) $sku->priceAfterDiscount(), 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('selectedSkuId')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div class="col-md-2">
                            <input type="number" min="1" class="form-control" wire:model="quantity"
                                placeholder="{{ __('dashboard.qty') }}">
                        </div>

                        <div class="col-md-1 d-flex gap-50">
                            <button type="button" class="btn btn-success btn-sm" wire:click="submitForm">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cancelForm">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@script
<script>
    $wire.on('orderItemsShouldRefresh', () => {
        window.location.reload();
    });
</script>
@endscript
