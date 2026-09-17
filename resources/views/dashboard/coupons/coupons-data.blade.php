<div>
    <div class="row mb-1">
        <div class="col-md-4">
            <input type="text" class="form-control" wire:model.live="search"
                placeholder="{{ __('dashboard.search') }} ({{ __('dashboard.code') }})">
        </div>

        <div class="col-md-3">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="all">{{ __('dashboard.all') }}</option>
                <option value="active">{{ __('dashboard.active') }}</option>
                <option value="inactive">{{ __('dashboard.inactive') }}</option>
            </select>
        </div>

        <div class="col-md-3">
            <select class="form-select" wire:model.live="typeFilter">
                <option value="all">{{ __('dashboard.all') }}</option>
                <option value="amount">{{ __('dashboard.amount') }}</option>
                <option value="percentage">{{ __('dashboard.percentage') }}</option>
            </select>
        </div>

        <div class="col-md-2">
            <select class="form-select" wire:model.live="perPage">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('dashboard.code') }}</th>
                    <th>{{ __('dashboard.applies-to') }}</th>
                    <th>{{ __('dashboard.type') }}</th>
                    <th>{{ __('dashboard.value') }}</th>
                    <th>{{ __('dashboard.audience') }}</th>
                    <th>{{ __('dashboard.minimum-order-amount') }}</th>
                    <th>{{ __('dashboard.maximum-discount-amount') }}</th>
                    <th>{{ __('dashboard.usage-limit') }}</th>
                    <th>{{ __('dashboard.usage-limit-per-user') }}</th>
                    <th>{{ __('dashboard.used-count') }}</th>
                    <th>{{ __('dashboard.starts-at') }}</th>
                    <th>{{ __('dashboard.expires-at') }}</th>
                    <th>{{ __('dashboard.status') }}</th>
                    <th>{{ __('dashboard.actions') }}</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($items as $index => $item)
                    <tr>
                        <td>{{ $items->firstItem() + $index }}</td>
                        <td><span class="badge bg-light-primary">{{ $item->code }}</span></td>
                        <td>
                            <span class="badge bg-light-secondary">
                                {{ $item->applies_to === 'delivery_fee' ? __('dashboard.applies-to-delivery-fee') : __('dashboard.applies-to-subtotal') }}
                            </span>
                        </td>
                        <td>
                            @if ($item->applies_to === 'delivery_fee')
                                <span class="badge bg-light-info">
                                    {{ $item->delivery_discount_type === 'free' ? __('dashboard.free-delivery') : ($item->delivery_discount_type === 'percentage' ? __('dashboard.percentage') : __('dashboard.amount')) }}
                                </span>
                            @else
                                <span class="badge bg-light-info">
                                    {{ $item->type === 'percentage' ? __('dashboard.percentage') : __('dashboard.amount') }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($item->applies_to === 'delivery_fee' && $item->delivery_discount_type === 'free')
                                -
                            @elseif ($item->applies_to === 'delivery_fee')
                                {{ $item->delivery_discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $item->value, 2), '0'), '.') . '%' : number_format((float) $item->value, 2) }}
                            @else
                                {{ $item->type === 'percentage' ? rtrim(rtrim(number_format((float) $item->value, 2), '0'), '.') . '%' : number_format((float) $item->value, 2) }}
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light-warning">
                                @if ($item->audience === 'regions')
                                    {{ __('dashboard.audience-regions') }}
                                @elseif ($item->audience === 'categories')
                                    {{ __('dashboard.audience-categories') }}
                                @else
                                    {{ __('dashboard.audience-all') }}
                                @endif
                            </span>
                        </td>
                        <td>{{ $item->min_order_amount !== null ? number_format((float) $item->min_order_amount, 2) : '-' }}</td>
                        <td>{{ $item->max_discount_amount !== null ? number_format((float) $item->max_discount_amount, 2) : '-' }}</td>
                        <td>{{ $item->usage_limit ?? '-' }}</td>
                        <td>{{ $item->usage_limit_per_user ?? '-' }}</td>
                        <td>{{ $item->used_count }}</td>
                        <td>{{ $item->starts_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $item->expires_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td style="min-width: 140px">
                            <div class="form-check form-switch form-check-success">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="toggle_status_{{ $item->id }}" @checked($item->status)
                                    wire:click="updateStatus({{ $item->id }}, {{ $item->status ? 0 : 1 }})">
                                <label class="form-check-label" for="toggle_status_{{ $item->id }}">
                                    {{ $item->status ? __('dashboard.active') : __('dashboard.inactive') }}
                                </label>
                            </div>
                        </td>

                        <td>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-primary" title="{{ __('dashboard.update') }}"
                                    wire:click="editCoupon({{ $item->id }})">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-danger" title="{{ __('dashboard.delete') }}"
                                    wire:click="confirmDelete({{ $item->id }})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center text-muted py-2">{{ __('dashboard.no-data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-1">
        {{ $items->links() }}
    </div>
</div>
