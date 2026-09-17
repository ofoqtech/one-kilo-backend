<?php

namespace App\Livewire\Dashboard\Shifts;

use App\Models\Admin;
use App\Models\Delivery;
use App\Models\Shift;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftsData extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $typeFilter = 'all';

    public string $statusFilter = 'all';

    public int $perPage = 10;

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $items = Shift::query()
            ->with('shiftable')
            ->when($this->typeFilter === 'cashier', fn ($query) => $query->where('shiftable_type', Admin::class))
            ->when($this->typeFilter === 'delivery', fn ($query) => $query->where('shiftable_type', Delivery::class))
            ->when($this->statusFilter === 'open', fn ($query) => $query->whereNull('ended_at'))
            ->when($this->statusFilter === 'closed', fn ($query) => $query->whereNotNull('ended_at'))
            ->latest('started_at')
            ->paginate($this->perPage);

        return view('dashboard.shifts.shifts-data', compact('items'));
    }
}
