@extends('dashboard.master', ['title' => __('dashboard.order-details')])
@section('orders-active', 'active')

@section('content')
    @php
        $orderStatusClasses = [
            \App\Models\Order::STATUS_PENDING => 'warning',
            \App\Models\Order::STATUS_AWAITING_PAYMENT => 'info',
            \App\Models\Order::STATUS_CONFIRMED => 'primary',
            \App\Models\Order::STATUS_PREPARING => 'secondary',
            \App\Models\Order::STATUS_OUT_FOR_DELIVERY => 'info',
            \App\Models\Order::STATUS_DELIVERED => 'success',
            \App\Models\Order::STATUS_CANCELED => 'danger',
            \App\Models\Order::STATUS_FAILED => 'danger',
        ];

        $paymentStatusClasses = [
            \App\Models\Order::PAYMENT_STATUS_UNPAID => 'danger',
            \App\Models\Order::PAYMENT_STATUS_PENDING => 'warning',
            \App\Models\Order::PAYMENT_STATUS_PAID => 'success',
            \App\Models\Order::PAYMENT_STATUS_FAILED => 'danger',
            \App\Models\Order::PAYMENT_STATUS_REFUNDED => 'secondary',
        ];
    @endphp

    <section class="app-order-view">
        <div class="row mb-1">
            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-1">
                <div>
                    <h4 class="mb-0">{{ __('dashboard.order-details') }}</h4>
                    <small class="text-muted">{{ __('dashboard.order-number') }}: {{ $order->order_number }}</small>
                </div>

                <div class="d-flex flex-wrap gap-1">
                    <a href="{{ route('dashboard.orders.print', $order) }}" target="_blank" rel="noopener noreferrer"
                        class="btn btn-primary">
                        <i class="fa-solid fa-print"></i> {{ __('dashboard.print-invoice') }}
                    </a>

                    <a href="{{ route('dashboard.orders') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-arrow-left"></i> {{ __('dashboard.back') }}
                    </a>



                    @php
                    $allowedStatuses = ['confirmed', 'preparing', 'out_for_delivery','ready'];

                    $canAssign =
                    in_array($order->status, $allowedStatuses) &&
                    !($order->delivery_id && in_array($order->status, ['picked_up', 'delivered']));
                    @endphp

                    @if($canAssign)
                    <a href="{{ route('dashboard.orders.assign-delivery', $order->id) }}"
                       class="btn btn-success">
                        <i class="fa-solid fa-truck"></i> {{ __('dashboard.assign_delivery') }}
                    </a>
                    @endif

                    @if ($canChangeStatus && ! in_array($order->status, [\App\Models\Order::STATUS_DELIVERED, \App\Models\Order::STATUS_CANCELED, \App\Models\Order::STATUS_FAILED]))
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                            <i class="fa-solid fa-ban"></i> {{ __('dashboard.cancel-order') }}
                        </button>
                    @endif

                </div>
            </div>
        </div>

        @if ($canChangeStatus)
            <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('dashboard.orders.cancel', $order) }}" method="POST" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('dashboard.cancel-order') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted">{{ __('dashboard.confirm-cancel-order') }}</p>
                            <label class="form-label">{{ __('dashboard.cancellation-reason') }}</label>
                            <textarea name="reason" class="form-control" rows="3" required
                                placeholder="{{ __('dashboard.cancellation-reason-placeholder') }}"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                {{ __('dashboard.close') }}
                            </button>
                            <button type="submit" class="btn btn-danger">
                                {{ __('dashboard.cancel-order') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-xl-4 col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-2">
                            <h3 class="mb-50">{{ $order->order_number }}</h3>
                            <span class="text-muted">#{{ $order->id }}</span>
                        </div>

                        <div class="d-flex flex-column gap-75">
                            <div>
                                <span class="fw-bolder d-block mb-25">{{ __('dashboard.order-status') }}</span>
                                <span class="badge bg-light-{{ $orderStatusClasses[$order->status] ?? 'secondary' }}">
                                    {{ __('dashboard.order-status-' . str_replace('_', '-', $order->status)) }}
                                </span>
                            </div>

                            <div>
                                <span class="fw-bolder d-block mb-25">{{ __('dashboard.payment-status') }}</span>
                                <span class="badge bg-light-{{ $paymentStatusClasses[$order->payment_status] ?? 'secondary' }}">
                                    {{ __('dashboard.payment-status-' . str_replace('_', '-', $order->payment_status)) }}
                                </span>
                            </div>

                            <div>
                                <span class="fw-bolder d-block mb-25">{{ __('dashboard.payment-method') }}</span>
                                <span class="badge bg-light-primary">
                                    {{ __('dashboard.payment-method-' . str_replace('_', '-', $order->payment_method)) }}
                                </span>
                            </div>
                        </div>

                        @if ($canChangeStatus)
                            <hr>

                            @php
                                $selectableNextStatuses = array_values(array_diff($allowedNextStatuses, [\App\Models\Order::STATUS_CANCELED]));
                            @endphp

                            <div>
                                <span class="fw-bolder d-block mb-50">{{ __('dashboard.change-status') }}</span>

                                @if ($selectableNextStatuses !== [])
                                    <form action="{{ route('dashboard.orders.status.update', $order) }}" method="POST"
                                        class="d-flex flex-column gap-75">
                                        @csrf
                                        <select name="status" class="form-select" required>
                                            <option value="" selected disabled>{{ __('dashboard.next-status') }}</option>
                                            @foreach ($selectableNextStatuses as $nextStatus)
                                                <option value="{{ $nextStatus }}">
                                                    {{ __('dashboard.order-status-' . str_replace('_', '-', $nextStatus)) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit" class="btn btn-primary">
                                            {{ __('dashboard.change-status') }}
                                        </button>
                                    </form>
                                @else
                                    <p class="text-muted mb-0">{{ __('dashboard.no-available-status-changes') }}</p>
                                @endif
                            </div>
                        @endif

                        <hr>

                        <ul class="list-unstyled mb-0">
                            <li class="mb-75">
                                <span class="fw-bolder me-25">{{ __('dashboard.placed-at') }}:</span>
                                <span>{{ $order->placed_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </li>
                            <li class="mb-75">
                                <span class="fw-bolder me-25">{{ __('dashboard.paid-at') }}:</span>
                                <span>{{ $order->paid_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </li>
                            <li class="mb-75">
                                <span class="fw-bolder me-25">{{ __('dashboard.created-at') }}:</span>
                                <span>{{ $order->created_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </li>
                            <li>
                                <span class="fw-bolder me-25">{{ __('dashboard.updated-at') }}:</span>
                                <span>{{ $order->updated_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('dashboard.pricing-summary') }}</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between mb-75">
                                <span>{{ __('dashboard.subtotal') }}</span>
                                <span>{{ number_format((float) $order->subtotal, 2) }}</span>
                            </li>
                            <li class="d-flex justify-content-between mb-75">
                                <span>{{ __('dashboard.discount') }}</span>
                                <span>{{ number_format((float) $order->discount_amount, 2) }}</span>
                            </li>
                            <li class="d-flex justify-content-between mb-75">
                                <span>{{ __('dashboard.shipping') }}</span>
                                <span>{{ number_format((float) $order->delivery_fee, 2) }}</span>
                            </li>
                            <li class="d-flex justify-content-between fw-bolder border-top pt-75 mb-0">
                                <span>{{ __('dashboard.total') }}</span>
                                <span>{{ number_format((float) $order->total, 2) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('dashboard.customer-info') }}</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-75">
                                        <span class="fw-bolder me-25">{{ __('dashboard.customer') }}:</span>
                                        <span>{{ $order->user?->name ?? data_get($address, 'contact_name') ?? '-' }}</span>
                                    </li>
                                    <li class="mb-75">
                                        <span class="fw-bolder me-25">{{ __('dashboard.user-id') }}:</span>
                                        <span>{{ $order->user?->id ?? '-' }}</span>
                                    </li>
                                    <li class="mb-75">
                                        <span class="fw-bolder me-25">{{ __('dashboard.email') }}:</span>
                                        <span>{{ $order->user?->email ?? '-' }}</span>
                                    </li>
                                    <li class="d-flex align-items-center justify-content-between flex-wrap gap-50">
                                        <div>
                                            <span class="fw-bolder me-25">{{ __('dashboard.phone') }}:</span>
                                            <span>{{ $order->user?->phone ?? data_get($address, 'phone') ?? '-' }}</span>
                                        </div>
                                        @php $customerPhone = $order->user?->phone ?? data_get($address, 'phone'); @endphp
                                        @if ($customerPhone)
                                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $customerPhone) }}"
                                                target="_blank" rel="noopener noreferrer"
                                                class="btn btn-sm btn-light-success ok-whatsapp-btn" title="{{ __('dashboard.contact-on-whatsapp') }}">
                                                <i class="fa-brands fa-whatsapp"></i>
                                                <span>{{ __('dashboard.contact-on-whatsapp') }}</span>
                                            </a>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('dashboard.delivery-agent-info') }}</h4>
                            </div>
                            <div class="card-body">
                                @if ($order->delivery)
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.delivery-agent-name') }}:</span>
                                            <span>{{ $order->delivery->full_name }}</span>
                                        </li>
                                        <li class="mb-75 d-flex align-items-center justify-content-between flex-wrap gap-50">
                                            <div>
                                                <span class="fw-bolder me-25">{{ __('dashboard.phone') }}:</span>
                                                <span>{{ $order->delivery->phone ?? '-' }}</span>
                                            </div>
                                            @if ($order->delivery->phone)
                                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->delivery->phone) }}"
                                                    target="_blank" rel="noopener noreferrer"
                                                    class="btn btn-sm btn-light-success ok-whatsapp-btn" title="{{ __('dashboard.contact-on-whatsapp') }}">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                    <span>{{ __('dashboard.contact-on-whatsapp') }}</span>
                                                </a>
                                            @endif
                                        </li>
                                        <li>
                                            <span class="fw-bolder me-25">{{ __('dashboard.delivery-agent-vehicle') }}:</span>
                                            <span>{{ trim(($order->delivery->vehicle_brand ?? '') . ' ' . ($order->delivery->vehicle_model ?? '')) ?: ($order->delivery->vehicle_type ?? '-') }}</span>
                                        </li>
                                    </ul>
                                @else
                                    <p class="text-muted mb-0">{{ __('dashboard.no-delivery-agent-assigned') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('dashboard.delivery-info') }}</h4>
                            </div>
                            <div class="card-body">
                                @if ($address !== [])
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.recipient-name') }}:</span>
                                            <span>{{ data_get($address, 'contact_name', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.phone') }}:</span>
                                            <span>{{ data_get($address, 'phone', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.country') }}:</span>
                                            <span>{{ data_get($address, 'country_name', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.governorate') }}:</span>
                                            <span>{{ data_get($address, 'governorate_name', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.region') }}:</span>
                                            <span>{{ data_get($address, 'region_name', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.city') }}:</span>
                                            <span>{{ data_get($address, 'city', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.area') }}:</span>
                                            <span>{{ data_get($address, 'area', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.address-details') }}:</span>
                                            <span>{{ data_get($address, 'street', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.building-number') }}:</span>
                                            <span>{{ data_get($address, 'building_number', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.floor') }}:</span>
                                            <span>{{ data_get($address, 'floor', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.apartment-number') }}:</span>
                                            <span>{{ data_get($address, 'apartment_number', '-') }}</span>
                                        </li>
                                        <li class="mb-75">
                                            <span class="fw-bolder me-25">{{ __('dashboard.landmark') }}:</span>
                                            <span>{{ data_get($address, 'landmark', '-') }}</span>
                                        </li>
                                        <li>
                                            <span class="fw-bolder me-25">{{ __('dashboard.full-address') }}:</span>
                                            <span>{{ data_get($address, 'full_address', '-') }}</span>
                                        </li>
                                    </ul>
                                @else
                                    <p class="text-muted mb-0">{{ __('dashboard.no-address-available') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('dashboard.payment-info') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-unstyled mb-md-0 mb-1">
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25">{{ __('dashboard.payment-method') }}:</span>
                                                <span>{{ __('dashboard.payment-method-' . str_replace('_', '-', $order->payment_method)) }}</span>
                                            </li>
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25">{{ __('dashboard.payment-status') }}:</span>
                                                <span>{{ __('dashboard.payment-status-' . str_replace('_', '-', $order->payment_status)) }}</span>
                                            </li>
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25">{{ __('dashboard.paid-at') }}:</span>
                                                <span>{{ $order->paid_at?->format('Y-m-d H:i') ?? '-' }}</span>
                                            </li>
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25">{{ __('dashboard.payment-url') }}:</span>
                                                @if ($order->payment_url)
                                                    <a href="{{ $order->payment_url }}" target="_blank" rel="noopener noreferrer">
                                                        {{ __('dashboard.view') }}
                                                    </a>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </li>
                                            <li>
                                                <span class="fw-bolder me-25">{{ __('dashboard.notes') }}:</span>
                                                <span>{{ $order->notes ?: '-' }}</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-6">
                                        @if ($order->walletTransaction)
                                            <ul class="list-unstyled mb-0">
                                                <li class="mb-75">
                                                    <span class="fw-bolder me-25">{{ __('dashboard.wallet-transaction') }}:</span>
                                                    <span>#{{ $order->walletTransaction->id }}</span>
                                                </li>
                                                <li class="mb-75">
                                                    <span class="fw-bolder me-25">{{ __('dashboard.reference') }}:</span>
                                                    <span>{{ $order->walletTransaction->reference ?: '-' }}</span>
                                                </li>
                                                <li class="mb-75">
                                                    <span class="fw-bolder me-25">{{ __('dashboard.transaction-direction') }}:</span>
                                                    <span>{{ __('dashboard.transaction-direction-' . $order->walletTransaction->type) }}</span>
                                                </li>
                                                <li class="mb-75">
                                                    <span class="fw-bolder me-25">{{ __('dashboard.transaction-type') }}:</span>
                                                    <span>{{ __('dashboard.wallet-transaction-type-' . str_replace('_', '-', $order->walletTransaction->transaction_type)) }}</span>
                                                </li>
                                                <li class="mb-75">
                                                    <span class="fw-bolder me-25">{{ __('dashboard.amount') }}:</span>
                                                    <span>{{ number_format((float) $order->walletTransaction->amount, 2) }}</span>
                                                </li>
                                                <li class="mb-75">
                                                    <span class="fw-bolder me-25">{{ __('dashboard.balance-before') }}:</span>
                                                    <span>{{ number_format((float) $order->walletTransaction->balance_before, 2) }}</span>
                                                </li>
                                                <li class="mb-75">
                                                    <span class="fw-bolder me-25">{{ __('dashboard.balance-after') }}:</span>
                                                    <span>{{ number_format((float) $order->walletTransaction->balance_after, 2) }}</span>
                                                </li>
                                                <li>
                                                    <span class="fw-bolder me-25">{{ __('dashboard.status') }}:</span>
                                                    <span class="badge bg-light-{{ $order->walletTransaction->status ? 'success' : 'danger' }}">
                                                        {{ $order->walletTransaction->status ? __('dashboard.active') : __('dashboard.inactive') }}
                                                    </span>
                                                </li>
                                            </ul>
                                        @else
                                            <p class="text-muted mb-0">{{ __('dashboard.no-payment-data') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('dashboard.coupon') }}</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-75">
                                        <span class="fw-bolder me-25">{{ __('dashboard.code') }}:</span>
                                        <span>{{ $order->coupon?->code ?? data_get($couponSnapshot, 'code') ?? '-' }}</span>
                                    </li>
                                    <li>
                                        <span class="fw-bolder me-25">{{ __('dashboard.discount') }}:</span>
                                        <span>{{ number_format((float) data_get($couponSnapshot, 'discount_amount', $order->discount_amount), 2) }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('dashboard.items') }}</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-75 d-flex justify-content-between">
                                        <span>{{ __('dashboard.item-count') }}</span>
                                        <span>{{ $order->items->sum('quantity') }}</span>
                                    </li>
                                    <li class="d-flex justify-content-between">
                                        <span>{{ __('dashboard.order-items') }}</span>
                                        <span>{{ $order->items->count() }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        @livewire('dashboard.orders.order-items-editor', ['orderId' => $order->id], key('order-items-editor-' . $order->id))
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('dashboard.order-items') }}</h4>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('dashboard.image') }}</th>
                                            <th>{{ __('dashboard.product') }}</th>
                                            <th>{{ __('dashboard.sku') }}</th>
                                            <th>{{ __('dashboard.qty') }}</th>
                                            <th>{{ __('dashboard.unit-price') }}</th>
                                            <th>{{ __('dashboard.line-total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($order->items as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    @if ($item->product_image)
                                                        <img src="{{ asset($item->product_image) }}" alt="{{ $item->product_name }}"
                                                            class="rounded border object-fit-cover" width="52" height="52">
                                                    @else
                                                        <span class="text-muted">{{ __('dashboard.no-image') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $item->product_name }}</div>
                                                    @php $skuLabel = $item->sku_label ?: $item->sku?->label(); @endphp
                                                    @if ($skuLabel)
                                                        <div class="small text-muted mb-25">{{ $skuLabel }}</div>
                                                    @endif
                                                    @if ($item->product_id)
                                                        <small class="text-muted">#{{ $item->product_id }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $item->sku?->sku ?? $item->product?->sku ?? '-' }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                                                <td>{{ number_format((float) $item->line_total, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-2">{{ __('dashboard.no-data') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card card-user-timeline">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">{{ __('dashboard.order-timeline') }}</h4>
                                <span class="badge bg-light-primary">{{ $order->statusLogs->count() }}</span>
                            </div>
                            <div class="card-body">
                                @if ($order->statusLogs->isNotEmpty())
                                    <ul class="timeline ms-50 mb-0">
                                        @foreach ($order->statusLogs as $index => $log)
                                            <li class="timeline-item">
                                                <span class="timeline-point timeline-point-indicator"></span>
                                                <div class="timeline-event">
                                                    <div class="d-flex justify-content-between flex-sm-row flex-column mb-50 gap-50">
                                                        <div>
                                                            <h6 class="mb-25">{{ $log->title ?: ($log->old_status ? __('dashboard.order-status-updated') : __('dashboard.order-created')) }}</h6>
                                                            @if ($index === 0)
                                                                <span class="badge bg-light-success">{{ __('dashboard.latest-update') }}</span>
                                                            @endif
                                                        </div>
                                                        <span class="timeline-event-time">{{ $log->created_at?->format('Y-m-d H:i:s') ?? '-' }}</span>
                                                    </div>

                                                    <div class="mb-50">
                                                        @if ($log->old_status)
                                                            <div class="d-flex flex-wrap align-items-center gap-50">
                                                                <span class="text-muted">{{ __('dashboard.old-status') }}:</span>
                                                                <span class="badge bg-light-secondary">{{ __('dashboard.order-status-' . str_replace('_', '-', $log->old_status)) }}</span>
                                                                <span class="text-muted">{{ __('dashboard.new-status') }}:</span>
                                                                <span class="badge bg-light-{{ $orderStatusClasses[$log->new_status] ?? 'secondary' }}">{{ __('dashboard.order-status-' . str_replace('_', '-', $log->new_status)) }}</span>
                                                            </div>
                                                        @else
                                                            <div class="d-flex flex-wrap align-items-center gap-50">
                                                                <span class="text-muted">{{ __('dashboard.new-status') }}:</span>
                                                                <span class="badge bg-light-{{ $orderStatusClasses[$log->new_status] ?? 'secondary' }}">{{ __('dashboard.order-status-' . str_replace('_', '-', $log->new_status)) }}</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="mb-50 text-muted">
                                                        {{ __('dashboard.changed-by') }}:
                                                        {{ $log->changedByAdmin?->name ?? __('dashboard.system') }}
                                                    </div>

                                                    @if ($log->description)
                                                        <p class="mb-0 text-muted">{{ $log->description }}</p>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted mb-0">{{ __('dashboard.order-timeline-empty') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('js')
    <script>
        document.addEventListener('livewire:init', function () {
            if (!window.Echo) {
                return;
            }

            window.Echo.channel('orders-dashboard').listen('.order.status-changed', (payload) => {
                if (payload.id === {{ $order->id }}) {
                    window.location.reload();
                }
            });
        });
    </script>
@endpush
