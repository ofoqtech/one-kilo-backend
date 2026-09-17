@extends('dashboard.master', ['title' => __('dashboard.analytics-overview')])
@section('dashboard-active', 'active')

@section('content')
    @php
        $filters = $analytics['filters'];
        $kpis = $analytics['kpis'];
        $hero = $analytics['hero'];
        $charts = $analytics['charts'];
        $sections = $analytics['sections'];

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

        $formatNumber = fn ($value, $decimals = 0) => number_format((float) $value, $decimals);
        $statusChartTotal = array_sum($charts['statusDistribution']['series']);
        $paymentChartTotal = array_sum($charts['paymentMethodDistribution']['series']);
        $topProductsChartTotal = array_sum($charts['topProductsChart']['series']);
        $topCategoryMax = max(1, (int) $sections['topCategories']->max('units_sold'));
    @endphp

    <section class="dashboard-home-revamp">
        <div class="home-dashboard-header d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-1 mb-2">
            <div>
                <span class="dashboard-home-eyebrow">{{ __('dashboard.performance-snapshot') }}</span>
                <h2 class="dashboard-home-title mb-50">{{ __('dashboard.analytics-overview') }}</h2>
                <p class="dashboard-home-subtitle mb-0">{{ __('dashboard.analytics-description') }}</p>
            </div>

            <div class="dashboard-range-switcher d-flex flex-wrap align-items-center gap-50">
                <a href="{{ route('dashboard.shifts.index') }}" class="dashboard-range-pill">
                    <i class="fa-solid fa-clock"></i> {{ __('dashboard.current-shift') }}
                </a>
                @foreach ($filters['options'] as $key => $label)
                    @if ($key !== 'custom')
                        <a href="{{ route('dashboard.home', ['range' => $key]) }}"
                            class="dashboard-range-pill {{ $filters['active'] === $key ? 'active' : '' }}">
                            {{ $label }}
                        </a>
                    @endif
                @endforeach

                <button type="button" class="dashboard-range-pill {{ $filters['active'] === 'custom' ? 'active' : '' }}"
                    data-bs-toggle="modal" data-bs-target="#customRangeModal">
                    <i class="fa-solid fa-calendar-days"></i> {{ __('dashboard.custom-range') }}
                </button>
            </div>
        </div>

        <div class="modal fade" id="customRangeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('dashboard.home') }}" method="GET" class="modal-content">
                    <input type="hidden" name="range" value="custom">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('dashboard.custom-range') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-1">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('dashboard.from-date') }}</label>
                                <input type="date" name="from" class="form-control"
                                    value="{{ $filters['custom_from'] ?? '' }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('dashboard.to-date') }}</label>
                                <input type="date" name="to" class="form-control"
                                    value="{{ $filters['custom_to'] ?? '' }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            {{ __('dashboard.close') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            {{ __('dashboard.apply') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-1 mb-1">
            <div class="col-xl-8 col-12">
                <div class="card analytics-hero-card h-100 mb-0">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
                            <div class="analytics-hero-copy">
                                <span class="analytics-chip">{{ __('dashboard.selected-range') }}: {{ $filters['label'] }}</span>
                                <h3 class="analytics-hero-heading mt-1 mb-50">{{ __('dashboard.range-performance') }}</h3>
                                <p class="analytics-hero-text mb-1">
                                    {{ __('dashboard.range-performance-description') }}
                                </p>

                                <div class="analytics-hero-grid">
                                    <div class="analytics-hero-metric">
                                        <span>{{ __('dashboard.orders-in-range') }}</span>
                                        <strong>{{ $formatNumber($hero['orders_in_range']) }}</strong>
                                    </div>
                                    <div class="analytics-hero-metric">
                                        <span>{{ __('dashboard.sales-in-range') }}</span>
                                        <strong>{{ $formatNumber($hero['sales_revenue_in_range'], 2) }}</strong>
                                    </div>
                                    <div class="analytics-hero-metric">
                                        <span>{{ __('dashboard.new-users') }}</span>
                                        <strong>{{ $formatNumber($hero['new_users_in_range']) }}</strong>
                                    </div>
                                    <div class="analytics-hero-metric">
                                        <span>{{ __('dashboard.average-order-value') }}</span>
                                        <strong>{{ $formatNumber($hero['average_order_value_in_range'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="analytics-hero-side">
                                <div class="analytics-hero-side-card">
                                    <span class="analytics-hero-side-label">{{ __('dashboard.realized-revenue') }}</span>
                                    <h4>{{ $formatNumber($kpis['realized_revenue_total'], 2) }}</h4>
                                    <small>{{ __('dashboard.in-selected-range') }}: {{ $formatNumber($kpis['realized_revenue_in_range'], 2) }}</small>
                                </div>
                                <div class="analytics-hero-side-card subdued">
                                    <span class="analytics-hero-side-label">{{ __('dashboard.pipeline-revenue') }}</span>
                                    <h4>{{ $formatNumber($kpis['pipeline_revenue_total'], 2) }}</h4>
                                    <small>{{ __('dashboard.delivered-rate') }}: {{ $formatNumber($hero['delivered_rate_in_range'], 1) }}%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-12">
                <div class="card analytics-highlight-card h-100 mb-0">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <span class="analytics-chip soft">{{ __('dashboard.wallet-balance-snapshot') }}</span>
                            <h3 class="mt-1 mb-50">{{ $formatNumber($kpis['wallet_balance_total'], 2) }}</h3>
                            <p class="text-muted mb-1">{{ __('dashboard.analytics-highlight-description') }}</p>
                        </div>

                        <div class="analytics-highlight-list">
                            <div class="analytics-highlight-item">
                                <span>{{ __('dashboard.active-wallets') }}</span>
                                <strong>{{ $formatNumber($kpis['active_wallets']) }}</strong>
                            </div>
                            <div class="analytics-highlight-item">
                                <span>{{ __('dashboard.messages-in-range') }}</span>
                                <strong>{{ $formatNumber($hero['contacts_in_range']) }}</strong>
                            </div>
                            <div class="analytics-highlight-item">
                                <span>{{ __('dashboard.sales-orders') }}</span>
                                <strong>{{ $formatNumber($hero['sales_orders_in_range']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-1 mb-1">
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card analytics-stat-card h-100 mb-0">
                    <div class="card-body">
                        <div class="analytics-stat-top">
                            <span class="analytics-icon bg-light-primary text-primary"><i class="fa-solid fa-users"></i></span>
                            <span class="analytics-stat-label">{{ __('dashboard.users') }}</span>
                        </div>
                        <h3 class="analytics-stat-value">{{ $formatNumber($kpis['users_total']) }}</h3>
                        <p class="analytics-stat-meta mb-0">
                            {{ __('dashboard.active-users') }}: {{ $formatNumber($kpis['active_users']) }}
                            <span class="separator-dot"></span>
                            {{ __('dashboard.new-users') }}: {{ $formatNumber($hero['new_users_in_range']) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card analytics-stat-card h-100 mb-0">
                    <div class="card-body">
                        <div class="analytics-stat-top">
                            <span class="analytics-icon bg-light-warning text-warning"><i class="fa-solid fa-bag-shopping"></i></span>
                            <span class="analytics-stat-label">{{ __('dashboard.orders') }}</span>
                        </div>
                        <h3 class="analytics-stat-value">{{ $formatNumber($kpis['orders_total']) }}</h3>
                        <p class="analytics-stat-meta mb-0">
                            {{ __('dashboard.order-status-delivered') }}: {{ $formatNumber($kpis['delivered_orders_total']) }}
                            <span class="separator-dot"></span>
                            {{ __('dashboard.order-status-canceled') }}: {{ $formatNumber($kpis['canceled_orders_total']) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card analytics-stat-card h-100 mb-0">
                    <div class="card-body">
                        <div class="analytics-stat-top">
                            <span class="analytics-icon bg-light-success text-success"><i class="fa-solid fa-dollar-sign"></i></span>
                            <span class="analytics-stat-label">{{ __('dashboard.total-revenue') }}</span>
                        </div>
                        <h3 class="analytics-stat-value">{{ $formatNumber($kpis['sales_revenue_total'], 2) }}</h3>
                        <p class="analytics-stat-meta mb-0">
                            {{ __('dashboard.realized-revenue') }}: {{ $formatNumber($kpis['realized_revenue_total'], 2) }}
                            <span class="separator-dot"></span>
                            {{ __('dashboard.pipeline-revenue') }}: {{ $formatNumber($kpis['pipeline_revenue_total'], 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card analytics-stat-card h-100 mb-0">
                    <div class="card-body">
                        <div class="analytics-stat-top">
                            <span class="analytics-icon bg-light-danger text-danger"><i class="fa-solid fa-box"></i></span>
                            <span class="analytics-stat-label">{{ __('dashboard.products') }}</span>
                        </div>
                        <h3 class="analytics-stat-value">{{ $formatNumber($kpis['products_total']) }}</h3>
                        <p class="analytics-stat-meta mb-0">{{ __('dashboard.active-products') }}: {{ $formatNumber($kpis['active_products']) }}</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card analytics-stat-card h-100 mb-0">
                    <div class="card-body">
                        <div class="analytics-stat-top">
                            <span class="analytics-icon bg-light-info text-info"><i class="fa-solid fa-th-large"></i></span>
                            <span class="analytics-stat-label">{{ __('dashboard.categories') }}</span>
                        </div>
                        <h3 class="analytics-stat-value">{{ $formatNumber($kpis['categories_total']) }}</h3>
                        <p class="analytics-stat-meta mb-0">{{ __('dashboard.active-categories') }}: {{ $formatNumber($kpis['active_categories']) }}</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card analytics-stat-card h-100 mb-0">
                    <div class="card-body">
                        <div class="analytics-stat-top">
                            <span class="analytics-icon bg-light-secondary text-secondary"><i class="fa-solid fa-credit-card"></i></span>
                            <span class="analytics-stat-label">{{ __('dashboard.wallet-balance-snapshot') }}</span>
                        </div>
                        <h3 class="analytics-stat-value">{{ $formatNumber($kpis['wallet_balance_total'], 2) }}</h3>
                        <p class="analytics-stat-meta mb-0">{{ __('dashboard.active-wallets') }}: {{ $formatNumber($kpis['active_wallets']) }}</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card analytics-stat-card h-100 mb-0">
                    <div class="card-body">
                        <div class="analytics-stat-top">
                            <span class="analytics-icon bg-light-danger text-danger"><i class="fa-solid fa-heart"></i></span>
                            <span class="analytics-stat-label">{{ __('dashboard.favorites-count') }}</span>
                        </div>
                        <h3 class="analytics-stat-value">{{ $formatNumber($kpis['favorites_total']) }}</h3>
                        <p class="analytics-stat-meta mb-0">{{ __('dashboard.in-selected-range') }}: {{ $formatNumber($kpis['favorites_in_range']) }}</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card analytics-stat-card h-100 mb-0">
                    <div class="card-body">
                        <div class="analytics-stat-top">
                            <span class="analytics-icon bg-light-primary text-primary"><i class="fa-solid fa-envelope"></i></span>
                            <span class="analytics-stat-label">{{ __('dashboard.contacts') }}</span>
                        </div>
                        <h3 class="analytics-stat-value">{{ $formatNumber($kpis['contacts_total']) }}</h3>
                        <p class="analytics-stat-meta mb-0">{{ __('dashboard.messages-in-range') }}: {{ $formatNumber($kpis['contacts_in_range']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-1 mb-1">
            <div class="col-xl-8 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0">
                        <div>
                            <h4 class="card-title mb-25">{{ __('dashboard.orders-trend') }}</h4>
                            <small class="text-muted">{{ $filters['label'] }}</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="orders-trend-chart" class="analytics-chart-lg"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title mb-0">{{ __('dashboard.order-status-distribution') }}</h4>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        @if ($statusChartTotal > 0)
                            <div id="order-status-chart" class="analytics-chart-sm"></div>
                            <div class="analytics-legend-grid">
                                @foreach ($charts['statusDistribution']['labels'] as $index => $label)
                                    <div class="analytics-legend-item">
                                        <span class="analytics-legend-dot status-{{ $index % 8 }}"></span>
                                        <span>{{ $label }}</span>
                                        <strong>{{ $formatNumber($charts['statusDistribution']['series'][$index]) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="analytics-empty-state">
                                <i class="fa-solid fa-chart-pie"></i>
                                <p class="mb-0">{{ __('dashboard.no-analytics-data') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-1 mb-1">
            <div class="col-xl-8 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0">
                        <div>
                            <h4 class="card-title mb-25">{{ __('dashboard.revenue-trend') }}</h4>
                            <small class="text-muted">{{ __('dashboard.revenue-status-note') }}</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="revenue-trend-chart" class="analytics-chart-lg"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title mb-0">{{ __('dashboard.payment-method-distribution') }}</h4>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        @if ($paymentChartTotal > 0)
                            <div id="payment-method-chart" class="analytics-chart-sm"></div>
                            <div class="analytics-legend-grid compact">
                                @foreach ($charts['paymentMethodDistribution']['labels'] as $index => $label)
                                    <div class="analytics-legend-item">
                                        <span class="analytics-legend-dot payment-{{ $index % 3 }}"></span>
                                        <span>{{ $label }}</span>
                                        <strong>{{ $formatNumber($charts['paymentMethodDistribution']['series'][$index]) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="analytics-empty-state">
                                <i class="fa-solid fa-credit-card"></i>
                                <p class="mb-0">{{ __('dashboard.no-analytics-data') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-1 mb-1">
            <div class="col-xl-8 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-50">
                        <div>
                            <h4 class="card-title mb-25">{{ __('dashboard.top-selling-products') }}</h4>
                            <small class="text-muted">{{ $filters['label'] }}</small>
                        </div>
                        <a href="{{ route('dashboard.products') }}" class="btn btn-sm btn-outline-primary">{{ __('dashboard.view-all') }}</a>
                    </div>
                    <div class="card-body">
                        @if ($topProductsChartTotal > 0)
                            <div id="top-products-chart" class="analytics-chart-lg"></div>
                        @else
                            <div class="analytics-empty-state tall">
                                <i class="fa-solid fa-chart-bar"></i>
                                <p class="mb-0">{{ __('dashboard.no-top-products') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">{{ __('dashboard.top-categories') }}</h4>
                        <a href="{{ route('dashboard.categories') }}" class="btn btn-sm btn-outline-primary">{{ __('dashboard.view-all') }}</a>
                    </div>
                    <div class="card-body">
                        @forelse ($sections['topCategories'] as $category)
                            <div class="analytics-category-item {{ ! $loop->last ? 'mb-1' : '' }}">
                                <div class="d-flex justify-content-between align-items-start gap-1 mb-50">
                                    <div>
                                        <h6 class="mb-25">{{ $category->name }}</h6>
                                        <small class="text-muted">
                                            {{ __('dashboard.units-sold') }}: {{ $formatNumber($category->units_sold) }}
                                            <span class="separator-dot"></span>
                                            {{ __('dashboard.products') }}: {{ $formatNumber($category->products_count) }}
                                        </small>
                                    </div>
                                    <strong>{{ $formatNumber($category->sales_total, 2) }}</strong>
                                </div>
                                <div class="progress analytics-progress">
                                    <div class="progress-bar" role="progressbar"
                                        style="width: {{ $category->units_sold > 0 ? max(8, round(($category->units_sold / $topCategoryMax) * 100)) : 8 }}%"
                                        aria-valuenow="{{ $category->units_sold }}" aria-valuemin="0"
                                        aria-valuemax="{{ $topCategoryMax }}"></div>
                                </div>
                            </div>
                        @empty
                            <div class="analytics-empty-state">
                                <i class="fa-solid fa-th-large"></i>
                                <p class="mb-0">{{ __('dashboard.no-top-categories') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-1 mb-1">
            <div class="col-xl-8 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-50">
                        <div>
                            <h4 class="card-title mb-25">{{ __('dashboard.recent-orders') }}</h4>
                            <small class="text-muted">{{ __('dashboard.analytics-latest-orders-note') }}</small>
                        </div>
                        <a href="{{ route('dashboard.orders') }}" class="btn btn-sm btn-outline-primary">{{ __('dashboard.view-all') }}</a>
                    </div>
                    <div class="card-body">
                        @if ($sections['recentOrders']->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table analytics-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ __('dashboard.order-number') }}</th>
                                            <th>{{ __('dashboard.customer') }}</th>
                                            <th>{{ __('dashboard.total') }}</th>
                                            <th>{{ __('dashboard.order-status') }}</th>
                                            <th>{{ __('dashboard.payment-method') }}</th>
                                            <th>{{ __('dashboard.created-at') }}</th>
                                            <th>{{ __('dashboard.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sections['recentOrders'] as $order)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $order->order_number }}</div>
                                                    <small class="text-muted">#{{ $order->id }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-75">
                                                        <img src="{{ asset($order->user?->image ?: 'uploads/images/image.png') }}"
                                                            alt="{{ $order->user?->name ?? __('dashboard.customer') }}"
                                                            class="analytics-avatar rounded-circle">
                                                        <div>
                                                            <div class="fw-semibold">{{ $order->user?->name ?? '-' }}</div>
                                                            <small class="text-muted">{{ __('dashboard.item-count') }}: {{ $order->items_count }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $formatNumber($order->total, 2) }}</td>
                                                <td>
                                                    <span class="badge bg-light-{{ $orderStatusClasses[$order->status] ?? 'secondary' }}">
                                                        {{ __('dashboard.order-status-' . str_replace('_', '-', $order->status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-25">
                                                        <span class="badge bg-light-primary">
                                                            {{ __('dashboard.payment-method-' . str_replace('_', '-', $order->payment_method)) }}
                                                        </span>
                                                        <span class="badge bg-light-{{ $paymentStatusClasses[$order->payment_status] ?? 'secondary' }}">
                                                            {{ __('dashboard.payment-status-' . str_replace('_', '-', $order->payment_status)) }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>{{ ($order->placed_at ?? $order->created_at)?->format('Y-m-d H:i') ?? '-' }}</td>
                                                <td>
                                                    <a href="{{ route('dashboard.orders.show', $order) }}" class="btn btn-sm btn-primary">
                                                        {{ __('dashboard.open-order-details') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="analytics-empty-state tall">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <p class="mb-0">{{ __('dashboard.no-orders-found') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">{{ __('dashboard.top-products') }}</h4>
                        <a href="{{ route('dashboard.products') }}" class="btn btn-sm btn-outline-primary">{{ __('dashboard.view-all') }}</a>
                    </div>
                    <div class="card-body">
                        @forelse ($sections['topProducts'] as $product)
                            <div class="analytics-product-item d-flex align-items-center gap-1 {{ ! $loop->last ? 'mb-1' : '' }}">
                                @if ($product->image)
                                    <img src="{{ asset($product->image) }}" alt="{{ $product->name }}"
                                        class="analytics-product-thumb rounded-3">
                                @else
                                    <div class="analytics-product-thumb placeholder-thumb rounded-3">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                @endif

                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $product->name }}</div>
                                    <small class="text-muted d-block text-truncate">
                                        {{ $product->category_name ?: __('dashboard.uncategorized') }}
                                    </small>
                                    <small class="text-muted d-block">
                                        {{ __('dashboard.units-sold') }}: {{ $formatNumber($product->units_sold) }}
                                    </small>
                                </div>

                                <div class="text-end">
                                    <div class="fw-semibold">{{ $formatNumber($product->sales_total, 2) }}</div>
                                    <small class="text-muted">{{ $product->current_price !== null ? $formatNumber($product->current_price, 2) : '-' }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="analytics-empty-state">
                                <i class="fa-solid fa-box"></i>
                                <p class="mb-0">{{ __('dashboard.no-top-products') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-1">
            <div class="col-xl-5 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">{{ __('dashboard.recent-users') }}</h4>
                        <a href="{{ route('dashboard.users.index') }}" class="btn btn-sm btn-outline-primary">{{ __('dashboard.view-all') }}</a>
                    </div>
                    <div class="card-body">
                        @forelse ($sections['recentUsers'] as $user)
                            <div class="analytics-user-item d-flex align-items-center gap-1 {{ ! $loop->last ? 'mb-1' : '' }}">
                                <img src="{{ asset($user->image ?: 'uploads/images/image.png') }}" alt="{{ $user->name }}"
                                    class="analytics-avatar-lg rounded-circle">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $user->name }}</div>
                                    <small class="text-muted d-block text-truncate">{{ $user->email ?: $user->phone }}</small>
                                    <small class="text-muted d-block">{{ $user->created_at?->format('Y-m-d H:i') ?? '-' }}</small>
                                </div>
                                <a href="{{ route('dashboard.user.profile', ['id' => $user->id]) }}"
                                    class="btn btn-sm btn-flat-primary">{{ __('dashboard.view') }}</a>
                            </div>
                        @empty
                            <div class="analytics-empty-state">
                                <i class="fa-solid fa-user-plus"></i>
                                <p class="mb-0">{{ __('dashboard.no-recent-users') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-7 col-12">
                <div class="card analytics-panel h-100 mb-0">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title mb-25">{{ __('dashboard.recent-wallet-transactions') }}</h4>
                        <small class="text-muted">{{ __('dashboard.analytics-wallet-note') }}</small>
                    </div>
                    <div class="card-body">
                        @if ($sections['recentWalletTransactions']->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table analytics-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ __('dashboard.customer') }}</th>
                                            <th>{{ __('dashboard.transaction-type') }}</th>
                                            <th>{{ __('dashboard.transaction-direction') }}</th>
                                            <th>{{ __('dashboard.amount') }}</th>
                                            <th>{{ __('dashboard.status') }}</th>
                                            <th>{{ __('dashboard.created-at') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sections['recentWalletTransactions'] as $transaction)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $transaction->user?->name ?? '-' }}</div>
                                                    <small class="text-muted">{{ $transaction->order?->order_number ?? $transaction->reference ?? '-' }}</small>
                                                </td>
                                                <td>{{ __('dashboard.wallet-transaction-type-' . str_replace('_', '-', $transaction->transaction_type)) }}</td>
                                                <td>
                                                    <span class="badge bg-light-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">
                                                        {{ __('dashboard.transaction-direction-' . $transaction->type) }}
                                                    </span>
                                                </td>
                                                <td>{{ $formatNumber($transaction->amount, 2) }}</td>
                                                <td>
                                                    <span class="badge bg-light-{{ $transaction->status ? 'success' : 'secondary' }}">
                                                        {{ $transaction->status ? __('dashboard.active') : __('dashboard.inactive') }}
                                                    </span>
                                                </td>
                                                <td>{{ $transaction->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="analytics-empty-state tall">
                                <i class="fa-solid fa-credit-card"></i>
                                <p class="mb-0">{{ __('dashboard.no-wallet-transactions') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('css')
    <style>
        .dashboard-home-revamp {
            --analytics-surface: #ffffff;
            --analytics-text: #23304a;
            --analytics-muted: #7c879d;
            --analytics-shadow: 0 16px 40px rgba(31, 47, 70, 0.08);
            --analytics-shadow-soft: 0 10px 24px rgba(31, 47, 70, 0.06);
        }

        html[data-ok-theme="dark"] .dashboard-home-revamp {
            --analytics-surface: #1E2338;
            --analytics-text: #EBEDF7;
            --analytics-muted: #9AA1C2;
            --analytics-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
            --analytics-shadow-soft: 0 10px 24px rgba(0, 0, 0, 0.3);
        }

        html[data-ok-theme="dark"] .dashboard-range-switcher {
            background: rgba(30, 35, 56, 0.9);
            border-color: rgba(255, 255, 255, 0.08);
        }

        .dashboard-home-eyebrow {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            background: rgba(25, 70, 185, 0.12);
            color: #1946B9;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .dashboard-home-title {
            color: var(--analytics-text);
            font-size: 2rem;
            font-weight: 700;
        }

        .dashboard-home-subtitle {
            max-width: 760px;
            color: var(--analytics-muted);
            font-size: 0.98rem;
        }

        .dashboard-range-switcher {
            padding: 0.4rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: var(--analytics-shadow-soft);
            border: 1px solid rgba(82, 94, 124, 0.08);
        }

        .dashboard-range-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 92px;
            padding: 0.7rem 1rem;
            border-radius: 999px;
            color: var(--analytics-muted);
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .dashboard-range-pill:hover,
        .dashboard-range-pill.active {
            color: #ffffff;
            background: linear-gradient(120deg, #1946B9 0%, #1946B9 55%, #E90000 100%);
            box-shadow: 0 10px 20px rgba(25, 70, 185, 0.28);
        }

        .analytics-hero-card,
        .analytics-highlight-card,
        .analytics-stat-card,
        .analytics-panel {
            border: 0;
            border-radius: 1.4rem;
            background: var(--analytics-surface);
            box-shadow: var(--analytics-shadow);
        }

        .analytics-hero-card {
            color: #ffffff;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 32%),
                linear-gradient(120deg, #1946B9 0%, #1946B9 55%, #E90000 100%);
        }

        .analytics-hero-card h3,
        .analytics-hero-card h4,
        .analytics-hero-card span,
        .analytics-hero-card small,
        .analytics-hero-card strong,
        .analytics-hero-card p {
            color: inherit;
        }

        .analytics-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 0.82rem;
            font-weight: 600;
        }

        .analytics-chip.soft {
            background: rgba(25, 70, 185, 0.12);
            color: #1946B9;
        }

        .analytics-hero-text {
            max-width: 560px;
            font-size: 0.96rem;
            opacity: 0.9;
        }

        .analytics-hero-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
            margin-top: 1.25rem;
        }

        .analytics-hero-metric,
        .analytics-hero-side-card,
        .analytics-highlight-item {
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
        }

        .analytics-hero-metric span,
        .analytics-hero-side-label,
        .analytics-highlight-item span {
            display: block;
            font-size: 0.85rem;
            opacity: 0.82;
        }

        .analytics-hero-metric strong,
        .analytics-highlight-item strong {
            display: block;
            margin-top: 0.35rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .analytics-hero-side {
            display: grid;
            gap: 1rem;
            min-width: 260px;
        }

        .analytics-hero-side-card h4 {
            margin: 0.55rem 0 0.25rem;
            font-size: 1.65rem;
        }

        .analytics-hero-side-card.subdued {
            background: rgba(17, 24, 39, 0.18);
        }

        .analytics-highlight-card {
            background:
                radial-gradient(circle at top left, rgba(115, 103, 240, 0.15), transparent 38%),
                #ffffff;
        }

        .analytics-highlight-list {
            display: grid;
            gap: 0.75rem;
        }

        .analytics-highlight-item {
            background: rgba(115, 103, 240, 0.08);
            padding: 0.85rem 1rem;
        }

        .analytics-stat-card .card-body,
        .analytics-panel .card-body,
        .analytics-highlight-card .card-body {
            padding: 1.35rem;
        }

        .analytics-stat-top {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1rem;
        }

        .analytics-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.9rem;
            height: 2.9rem;
            border-radius: 1rem;
        }

        .analytics-stat-label {
            color: var(--analytics-muted);
            font-size: 0.92rem;
            font-weight: 600;
        }

        .analytics-stat-value {
            margin-bottom: 0.35rem;
            color: var(--analytics-text);
            font-size: 1.8rem;
            font-weight: 700;
        }

        .analytics-stat-meta {
            color: var(--analytics-muted);
            font-size: 0.88rem;
            line-height: 1.7;
        }
    </style>
@endpush

@push('js')
    <script>
        window.addEventListener('load', function() {
            if (typeof ApexCharts === 'undefined') {
                return;
            }

            const locale = @json(app()->getLocale());
            const isRtl = document.documentElement.getAttribute('data-textdirection') === 'rtl';
            const numberFormatter = new Intl.NumberFormat(locale);
            const amountFormatter = new Intl.NumberFormat(locale, {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });

            const chartPalette = {
                primary: '#1946B9',
                primarySoft: '#3E67D6',
                success: '#17B366',
                info: '#009EDB',
                warning: '#FF9F1C',
                danger: '#E90000',
                secondary: '#82868b',
                dark: '#4b4b4b',
                grid: 'rgba(82, 94, 124, 0.12)',
                text: '#5e5873',
                muted: '#7c879d'
            };

            const ordersTrend = @json($charts['ordersTrend']);
            const revenueTrend = @json($charts['revenueTrend']);
            const statusDistribution = @json($charts['statusDistribution']);
            const paymentMethodDistribution = @json($charts['paymentMethodDistribution']);
            const topProductsChart = @json($charts['topProductsChart']);

            const createChart = (selector, options) => {
                const element = document.querySelector(selector);

                if (!element) {
                    return null;
                }

                const chart = new ApexCharts(element, options);
                chart.render();
                return chart;
            };

            createChart('#orders-trend-chart', {
                chart: { type: 'area', height: 340, toolbar: { show: false }, zoom: { enabled: false } },
                series: [{ name: @json(__('dashboard.orders')), data: ordersTrend.series }],
                colors: [chartPalette.primary],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 0.9, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 90, 100] }
                },
                grid: { borderColor: chartPalette.grid, strokeDashArray: 4 },
                xaxis: {
                    categories: ordersTrend.labels,
                    labels: { style: { colors: chartPalette.muted } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    opposite: isRtl,
                    labels: {
                        style: { colors: chartPalette.muted },
                        formatter: value => numberFormatter.format(value)
                    }
                },
                tooltip: { y: { formatter: value => numberFormatter.format(value) } },
                legend: { show: false }
            });

            createChart('#revenue-trend-chart', {
                chart: { type: 'bar', height: 340, toolbar: { show: false } },
                plotOptions: { bar: { borderRadius: 8, columnWidth: '42%' } },
                series: [{ name: @json(__('dashboard.total-revenue')), data: revenueTrend.series }],
                colors: [chartPalette.success],
                dataLabels: { enabled: false },
                grid: { borderColor: chartPalette.grid, strokeDashArray: 4 },
                xaxis: {
                    categories: revenueTrend.labels,
                    labels: { style: { colors: chartPalette.muted } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    opposite: isRtl,
                    labels: {
                        style: { colors: chartPalette.muted },
                        formatter: value => amountFormatter.format(value)
                    }
                },
                tooltip: { y: { formatter: value => amountFormatter.format(value) } },
                legend: { show: false }
            });

            createChart('#order-status-chart', {
                chart: { type: 'donut', height: 290 },
                series: statusDistribution.series,
                labels: statusDistribution.labels,
                colors: [
                    chartPalette.warning,
                    chartPalette.info,
                    chartPalette.primary,
                    chartPalette.secondary,
                    chartPalette.primarySoft,
                    chartPalette.success,
                    chartPalette.danger,
                    chartPalette.dark
                ],
                legend: { show: false },
                dataLabels: { enabled: false },
                stroke: { width: 0 },
                plotOptions: { pie: { donut: { size: '68%' } } },
                tooltip: { y: { formatter: value => numberFormatter.format(value) } }
            });

            createChart('#payment-method-chart', {
                chart: { type: 'donut', height: 290 },
                series: paymentMethodDistribution.series,
                labels: paymentMethodDistribution.labels,
                colors: [chartPalette.primary, chartPalette.info, chartPalette.success],
                legend: { show: false },
                dataLabels: { enabled: false },
                stroke: { width: 0 },
                plotOptions: { pie: { donut: { size: '68%' } } },
                tooltip: { y: { formatter: value => numberFormatter.format(value) } }
            });

            createChart('#top-products-chart', {
                chart: { type: 'bar', height: 340, toolbar: { show: false } },
                series: [{ name: @json(__('dashboard.units-sold')), data: topProductsChart.series }],
                colors: [chartPalette.primary],
                plotOptions: { bar: { horizontal: true, borderRadius: 8, barHeight: '56%' } },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: topProductsChart.labels,
                    labels: {
                        style: { colors: chartPalette.muted },
                        formatter: value => numberFormatter.format(value)
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: { style: { colors: chartPalette.text, fontWeight: 600 } },
                    opposite: isRtl
                },
                grid: { borderColor: chartPalette.grid, strokeDashArray: 4 },
                tooltip: { y: { formatter: value => numberFormatter.format(value) } },
                legend: { show: false }
            });
        });
    </script>
@endpush

@push('css')
    <style>
        .separator-dot {
            display: inline-block;
            width: 0.3rem;
            height: 0.3rem;
            margin: 0 0.45rem;
            border-radius: 999px;
            background: rgba(124, 135, 157, 0.55);
            vertical-align: middle;
        }

        .analytics-panel .card-header {
            padding: 1.35rem 1.35rem 0;
        }

        .analytics-panel .card-title {
            color: var(--analytics-text);
            font-size: 1.1rem;
            font-weight: 700;
        }

        .analytics-chart-lg {
            min-height: 340px;
        }

        .analytics-chart-sm {
            min-height: 280px;
        }

        .analytics-legend-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .analytics-legend-grid.compact {
            grid-template-columns: 1fr;
        }

        .analytics-legend-item {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 0.88rem;
            color: var(--analytics-muted);
        }

        .analytics-legend-item strong {
            margin-inline-start: auto;
            color: var(--analytics-text);
        }

        .analytics-legend-dot {
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .analytics-legend-dot.status-0 { background: #FF9F1C; }
        .analytics-legend-dot.status-1 { background: #009EDB; }
        .analytics-legend-dot.status-2 { background: #1946B9; }
        .analytics-legend-dot.status-3 { background: #82868b; }
        .analytics-legend-dot.status-4 { background: #3E67D6; }
        .analytics-legend-dot.status-5 { background: #17B366; }
        .analytics-legend-dot.status-6 { background: #E90000; }
        .analytics-legend-dot.status-7 { background: #4b4b4b; }
        .analytics-legend-dot.payment-0 { background: #1946B9; }
        .analytics-legend-dot.payment-1 { background: #009EDB; }
        .analytics-legend-dot.payment-2 { background: #17B366; }

        .analytics-empty-state {
            min-height: 240px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            border: 1px dashed rgba(115, 103, 240, 0.2);
            border-radius: 1rem;
            color: var(--analytics-muted);
            background: rgba(115, 103, 240, 0.04);
            text-align: center;
        }

        .analytics-empty-state.tall {
            min-height: 320px;
        }

        .analytics-table thead th {
            border-top: 0;
            border-bottom-color: rgba(82, 94, 124, 0.12);
            color: var(--analytics-muted);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .analytics-table tbody td {
            border-color: rgba(82, 94, 124, 0.1);
        }

        .analytics-avatar {
            width: 2.2rem;
            height: 2.2rem;
            object-fit: cover;
        }

        .analytics-avatar-lg {
            width: 3rem;
            height: 3rem;
            object-fit: cover;
        }

        .analytics-product-thumb {
            width: 3.4rem;
            height: 3.4rem;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid rgba(82, 94, 124, 0.08);
        }

        .placeholder-thumb {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(25, 70, 185, 0.08);
            color: #1946B9;
        }

        .analytics-product-item,
        .analytics-user-item,
        .analytics-category-item {
            padding: 0.15rem 0;
        }

        .analytics-progress {
            height: 0.45rem;
            background: rgba(25, 70, 185, 0.12);
            border-radius: 999px;
        }

        .analytics-progress .progress-bar {
            border-radius: 999px;
            background: linear-gradient(90deg, #1946B9 0%, #E90000 100%);
        }

        .min-w-0 {
            min-width: 0;
        }

        @media (max-width: 1199.98px) {
            .analytics-hero-grid {
                grid-template-columns: 1fr 1fr;
            }

            .analytics-hero-side {
                min-width: 0;
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-home-title {
                font-size: 1.65rem;
            }

            .analytics-hero-grid,
            .analytics-legend-grid {
                grid-template-columns: 1fr;
            }

            .analytics-chart-lg,
            .analytics-chart-sm,
            .analytics-empty-state.tall {
                min-height: 280px;
            }

            .dashboard-range-pill {
                min-width: 0;
                flex: 1 1 calc(50% - 0.5rem);
            }
        }
    </style>
@endpush
