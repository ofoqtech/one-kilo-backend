<?php

namespace App\Livewire\Dashboard\Settings\Popups;

use App\Models\AppPopup;
use App\Utils\ImageManger;
use Livewire\Component;
use Livewire\WithPagination;

class PopupsData extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'refreshData' => '$refresh',
        'deleteItem',
    ];

    protected ImageManger $imageManger;

    public int $perPage = 10;

    public function boot(ImageManger $imageManger): void
    {
        $this->imageManger = $imageManger;
    }

    public function editPopup(int $id): void
    {
        $this->dispatch('popupUpdate', id: $id)->to(PopupUpdate::class);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('popupDelete', id: $id);
    }

    public function updateStatus(int $itemId, int $newStatus): void
    {
        $item = AppPopup::query()->find($itemId);

        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.no-data'));

            return;
        }

        $item->status = (bool) $newStatus;
        $item->save();

        $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
    }

    public function deleteItem(int $id): void
    {
        $item = AppPopup::query()->find($id);

        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.no-data'));

            return;
        }

        if ($item->image && str_starts_with($item->image, 'uploads/popups/')) {
            $this->imageManger->deleteImage($item->image);
        }

        $item->delete();

        $this->dispatch('refreshData');
        $this->dispatch('itemDeleted');
    }

    public function render()
    {
        $items = AppPopup::query()
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($this->perPage);

        return view('dashboard.settings.popups.popups-data', compact('items'));
    }
}
