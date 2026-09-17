<div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('dashboard.image') }}</th>
                    <th>{{ __('dashboard.title') }}</th>
                    <th>{{ __('dashboard.popup-link-type') }}</th>
                    <th>{{ __('dashboard.starts-at') }}</th>
                    <th>{{ __('dashboard.expires-at') }}</th>
                    <th>{{ __('dashboard.status') }}</th>
                    <th>{{ __('dashboard.actions') }}</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($items as $index => $item)
                    <tr wire:key="popup-row-{{ $item->id }}">
                        <td>{{ $items->firstItem() + $index }}</td>
                        <td>
                            @if ($item->image)
                                <img src="{{ asset($item->image) }}" alt="popup"
                                    class="rounded border object-fit-cover" width="60" height="60">
                            @else
                                <span class="text-muted">{{ __('dashboard.no-image') }}</span>
                            @endif
                        </td>
                        <td>{{ $item->title ?: '-' }}</td>
                        <td>
                            <span class="badge bg-light-info">
                                {{ __('dashboard.popup-link-' . str_replace('_', '-', $item->link_type)) }}
                            </span>
                        </td>
                        <td>{{ $item->starts_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $item->ends_at?->format('Y-m-d H:i') ?? '-' }}</td>
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
                                <button type="button" class="btn btn-sm btn-primary"
                                    title="{{ __('dashboard.update') }}" wire:click="editPopup({{ $item->id }})">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-danger"
                                    title="{{ __('dashboard.delete') }}" wire:click="confirmDelete({{ $item->id }})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-2">{{ __('dashboard.no-data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-1">
        {{ $items->links() }}
    </div>
</div>
