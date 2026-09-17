<div>
    <div class="row mb-1">
        <div class="col-md-3">
            <select class="form-select" wire:model.live="typeFilter">
                <option value="all">{{ __('dashboard.all') }}</option>
                <option value="cashier">{{ __('dashboard.cashiers') }}</option>
                <option value="delivery">{{ __('dashboard.deliveries') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="all">{{ __('dashboard.all') }}</option>
                <option value="open">{{ __('dashboard.shift-open') }}</option>
                <option value="closed">{{ __('dashboard.shift-closed') }}</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('dashboard.type') }}</th>
                    <th>{{ __('dashboard.name') }}</th>
                    <th>{{ __('dashboard.started-at') }}</th>
                    <th>{{ __('dashboard.ended-at') }}</th>
                    <th>{{ __('dashboard.opening-cash') }}</th>
                    <th>{{ __('dashboard.closing-cash') }}</th>
                    <th>{{ __('dashboard.status') }}</th>
                    <th>{{ __('dashboard.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $index => $shift)
                    <tr wire:key="shift-row-{{ $shift->id }}">
                        <td>{{ $items->firstItem() + $index }}</td>
                        <td>{{ $shift->shiftable_type === \App\Models\Admin::class ? __('dashboard.cashier') : __('dashboard.delivery') }}</td>
                        <td>{{ $shift->shiftable?->name ?? $shift->shiftable?->full_name ?? '-' }}</td>
                        <td>{{ $shift->started_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $shift->ended_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $shift->opening_cash !== null ? number_format((float) $shift->opening_cash, 2) : '-' }}</td>
                        <td>{{ $shift->closing_cash !== null ? number_format((float) $shift->closing_cash, 2) : '-' }}</td>
                        <td>
                            <span class="badge bg-light-{{ $shift->isOpen() ? 'success' : 'secondary' }}">
                                {{ $shift->isOpen() ? __('dashboard.shift-open') : __('dashboard.shift-closed') }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('dashboard.shifts.show', $shift) }}" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-2">{{ __('dashboard.no-data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-1">
        {{ $items->links() }}
    </div>
</div>
