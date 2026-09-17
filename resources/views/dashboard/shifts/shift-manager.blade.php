<div>
    @if (! $this->currentShift)
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ __('dashboard.open-shift') }}</h4>
            </div>
            <div class="card-body">
                <div class="row g-1 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('dashboard.opening-cash') }}</label>
                        <input type="number" step="0.01" min="0" wire:model="openingCash" class="form-control"
                            placeholder="{{ __('dashboard.opening-cash') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-success" wire:click="openShift">
                            <i class="fa-solid fa-play"></i> {{ __('dashboard.open-shift') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        @php $report = $this->report; @endphp
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">{{ __('dashboard.current-shift') }}</h4>
                <span class="badge bg-light-success">
                    {{ __('dashboard.started-at') }}: {{ $this->currentShift->started_at->format('Y-m-d H:i') }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-1 mb-1">
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

                <div class="row g-1 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('dashboard.closing-cash') }}</label>
                        <input type="number" step="0.01" min="0" wire:model="closingCash" class="form-control"
                            placeholder="{{ __('dashboard.closing-cash') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-danger" wire:click="closeShift"
                            wire:confirm="{{ __('dashboard.confirm-close-shift') }}">
                            <i class="fa-solid fa-stop"></i> {{ __('dashboard.close-shift') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
