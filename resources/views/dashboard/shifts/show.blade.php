@extends('dashboard.master', ['title' => __('dashboard.shift-report')])
@section('shifts-active', 'active')

@section('content')
    @php $shift = $report['shift']; @endphp
    <div class="row mb-1">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ __('dashboard.shift-report') }}</h4>
            <a href="{{ route('dashboard.shifts.index') }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-arrow-left"></i> {{ __('dashboard.back') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">
                {{ $shift->shiftable_type === \App\Models\Admin::class ? __('dashboard.cashier') : __('dashboard.delivery') }}:
                {{ $shift->shiftable?->name ?? $shift->shiftable?->full_name ?? '-' }}
            </h4>
        </div>
        <div class="card-body">
            <ul class="list-unstyled mb-2">
                <li class="mb-75"><span class="fw-bolder me-25">{{ __('dashboard.started-at') }}:</span> {{ $shift->started_at?->format('Y-m-d H:i') }}</li>
                <li class="mb-75"><span class="fw-bolder me-25">{{ __('dashboard.ended-at') }}:</span> {{ $shift->ended_at?->format('Y-m-d H:i') ?? __('dashboard.shift-open') }}</li>
                <li class="mb-75"><span class="fw-bolder me-25">{{ __('dashboard.opening-cash') }}:</span> {{ $shift->opening_cash !== null ? number_format((float) $shift->opening_cash, 2) : '-' }}</li>
                <li><span class="fw-bolder me-25">{{ __('dashboard.closing-cash') }}:</span> {{ $shift->closing_cash !== null ? number_format((float) $shift->closing_cash, 2) : '-' }}</li>
            </ul>

            <div class="row g-1">
                <div class="col-md-3">
                    <div class="border rounded p-1 text-center">
                        <div class="text-muted small">{{ __('dashboard.shift-orders-count') }}</div>
                        <div class="h4 mb-0">{{ $report['orders_count'] }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-1 text-center">
                        <div class="text-muted small">{{ __('dashboard.shift-sales-total') }}</div>
                        <div class="h4 mb-0">{{ number_format($report['sales_total'], 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-1 text-center">
                        <div class="text-muted small">{{ __('dashboard.shift-cash-total') }}</div>
                        <div class="h4 mb-0">{{ number_format($report['cash_orders_total'], 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-1 text-center">
                        <div class="text-muted small">{{ __('dashboard.shift-canceled-count') }}</div>
                        <div class="h4 mb-0">{{ $report['canceled_orders_count'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
