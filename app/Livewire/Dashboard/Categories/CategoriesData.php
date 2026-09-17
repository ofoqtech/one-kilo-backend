<?php

namespace App\Livewire\Dashboard\Categories;

use App\Models\Category;
use App\Utils\ImageManger;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriesData extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'refreshData' => '$refresh',
        'deleteItem',
    ];

    protected ImageManger $imageManger;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $structureFilter = 'all';

    public int $perPage = 10;

    public function boot(ImageManger $imageManger): void
    {
        $this->imageManger = $imageManger;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStructureFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function editCategory(int $id): void
    {
        $this->dispatch('categoryUpdate', id: $id)->to(CategoryUpdate::class);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('categoryDelete', id: $id);
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            Category::query()->whereKey($id)->update(['sort_order' => $position + 1]);
        }

        $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
    }

    public function updateStatus(int $itemId, int $newStatus): void
    {
        $item = Category::query()->find($itemId);

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
        $item = Category::query()
            ->withCount(['children', 'products'])
            ->find($id);

        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.no-data'));

            return;
        }

        if ((int) $item->children_count > 0) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.category-cannot-delete-has-children'));

            return;
        }

        if ((int) $item->products_count > 0) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.category-cannot-delete-has-products'));

            return;
        }

        if ($this->shouldDeleteManagedImage($item->image)) {
            $this->imageManger->deleteImage($item->image);
        }

        $item->delete();

        $this->dispatch('refreshData');
        $this->dispatch('itemDeleted');
    }

    protected function shouldDeleteManagedImage(?string $path): bool
    {
        return filled($path) && str_starts_with($path, 'uploads/categories/');
    }

    public function render()
    {
        $search = trim($this->search);

        $items = Category::query()
            ->with('parent:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhereHas('parent', function ($parentQuery) use ($search) {
                            $parentQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('status', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('status', false))
            ->when($this->structureFilter === 'main', fn ($query) => $query->whereNull('parent_id'))
            ->when($this->structureFilter === 'child', fn ($query) => $query->whereNotNull('parent_id'))
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($this->perPage);

        return view('dashboard.categories.categories-data', compact('items'));
    }
}
