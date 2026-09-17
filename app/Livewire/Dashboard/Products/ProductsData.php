<?php

namespace App\Livewire\Dashboard\Products;

use App\Models\Category;
use App\Models\Product;
use App\Utils\ImageManger;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsData extends Component
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

    public string $categoryFilter = 'all';

    public string $featuredFilter = 'all';

    public string $stockFilter = 'all';

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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFeaturedFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function editProduct(int $id): void
    {
        $this->dispatch('productUpdate', id: $id)->to(ProductUpdate::class);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('productDelete', id: $id);
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            Product::query()->whereKey($id)->update(['sort_order' => $position + 1]);
        }

        $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
    }

    public function updateStatus(int $itemId, int $newStatus): void
    {
        $item = Product::query()->find($itemId);

        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.no-data'));

            return;
        }

        $item->status = (bool) $newStatus;
        $item->save();

        $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
    }

    public function updateFeatured(int $itemId, int $newValue): void
    {
        $item = Product::query()->find($itemId);

        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.no-data'));

            return;
        }

        $item->is_featured = (bool) $newValue;
        $item->save();

        $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
    }

    public function deleteItem(int $id): void
    {
        $item = Product::query()
            ->with(['images:id,product_id,image'])
            ->withCount('orderItems')
            ->find($id);

        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.no-data'));

            return;
        }

        if ((int) $item->order_items_count > 0) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.product-cannot-delete-has-orders'));

            return;
        }

        $galleryPaths = $item->images
            ->pluck('image')
            ->filter(fn(?string $path) => $this->shouldDeleteManagedImage($path))
            ->values();

        $mainImagePath = $this->shouldDeleteManagedImage($item->image) ? $item->image : null;

        try {
            $item->delete();
        } catch (\Throwable $exception) {
            report($exception);

            $this->dispatch('notify', type: 'error', message: __('dashboard.error_occurred'));

            return;
        }

        foreach ($galleryPaths as $path) {
            $this->imageManger->deleteImage($path);
        }

        if ($mainImagePath) {
            $this->imageManger->deleteImage($mainImagePath);
        }

        $this->dispatch('refreshData');
        $this->dispatch('itemDeleted');
    }

    protected function shouldDeleteManagedImage(?string $path): bool
    {
        return filled($path) && str_starts_with($path, 'uploads/products/');
    }

    protected function categoryOptions(): array
    {
        return Category::query()
            ->select(['id', 'name', 'status', 'sort_order'])
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn(Category $category) => [
                'id' => $category->id,
                'label' => $category->name . ($category->status ? '' : ' (' . __('dashboard.inactive') . ')'),
            ])
            ->all();
    }

    public function render()
    {
        $search = trim($this->search);

        $items = Product::query()
            ->with([
                'category:id,name,status,parent_id',
                'images:id,product_id,image,sort_order',
                'activeSkus' => fn($query) => $query->select([
                    'id',
                    'product_id',
                    'signature',
                    'price',
                    'quantity',
                    'status',
                    'sort_order',
                ]),
            ])
            ->withCount(['images', 'skus', 'activeSkus'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $term = '%' . $search . '%';

                $query->where(function (Builder $subQuery) use ($term) {
                    $subQuery->where('slug', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('name->ar', 'like', $term)
                        ->orWhere('name->en', 'like', $term)
                        ->orWhere('short_description->ar', 'like', $term)
                        ->orWhere('short_description->en', 'like', $term)
                        ->orWhere('description->ar', 'like', $term)
                        ->orWhere('description->en', 'like', $term)
                        ->orWhereHas('category', function (Builder $categoryQuery) use ($term) {
                            $categoryQuery->where('name->ar', 'like', $term)
                                ->orWhere('name->en', 'like', $term);
                        });
                });
            })
            ->when($this->statusFilter === 'active', fn(Builder $query) => $query->where('status', true))
            ->when($this->statusFilter === 'inactive', fn(Builder $query) => $query->where('status', false))
            ->when(
                $this->categoryFilter !== 'all',
                fn(Builder $query) => $query->where('category_id', (int) $this->categoryFilter)
            )
            ->when($this->featuredFilter === 'featured', fn(Builder $query) => $query->where('is_featured', true))
            ->when($this->featuredFilter === 'regular', fn(Builder $query) => $query->where('is_featured', false))
            ->when($this->stockFilter === 'in_stock', function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery->where(function (Builder $simpleQuery) {
                        $simpleQuery
                            ->where('has_variants', false)
                            ->where('stock', '>', 0);
                    })->orWhere(function (Builder $variantQuery) {
                        $variantQuery
                            ->where('has_variants', true)
                            ->whereHas('skus', function (Builder $skuStockQuery) {
                                $skuStockQuery
                                    ->where('status', true)
                                    ->where('quantity', '>', 0);
                            });
                    });
                });
            })
            ->when($this->stockFilter === 'out_of_stock', function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery->where(function (Builder $simpleQuery) {
                        $simpleQuery
                            ->where('has_variants', false)
                            ->where('stock', '<=', 0);
                    })->orWhere(function (Builder $variantQuery) {
                        $variantQuery
                            ->where('has_variants', true)
                            ->whereDoesntHave('skus', function (Builder $skuStockQuery) {
                                $skuStockQuery
                                    ->where('status', true)
                                    ->where('quantity', '>', 0);
                            });
                    });
                });
            })
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($this->perPage);

        return view('dashboard.products.products-data', [
            'items' => $items,
            'categories' => $this->categoryOptions(),
        ]);
    }
}
