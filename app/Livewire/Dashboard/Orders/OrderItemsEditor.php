<?php

namespace App\Livewire\Dashboard\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\Dashboard\OrderService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OrderItemsEditor extends Component
{
    public int $orderId;

    public bool $showAddForm = false;

    public ?int $replacingItemId = null;

    public string $productSearch = '';

    public ?int $selectedProductId = null;

    public ?int $selectedSkuId = null;

    public int $quantity = 1;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderProperty(): Order
    {
        return app(OrderService::class)->getOrderDetails(Order::findOrFail($this->orderId));
    }

    public function getIsEditableProperty(): bool
    {
        return in_array($this->order->status, [Order::STATUS_PENDING, Order::STATUS_AWAITING_PAYMENT], true);
    }

    public function getProductOptionsProperty()
    {
        if (trim($this->productSearch) === '') {
            return collect();
        }

        return Product::query()
            ->active()
            ->where(function ($query) {
                $term = '%' . trim($this->productSearch) . '%';
                $query->where('name->ar', 'like', $term)
                    ->orWhere('name->en', 'like', $term)
                    ->orWhere('sku', 'like', $term);
            })
            ->limit(15)
            ->get();
    }

    public function getSelectedProductProperty(): ?Product
    {
        return $this->selectedProductId ? Product::find($this->selectedProductId) : null;
    }

    public function updatingSelectedProductId(): void
    {
        $this->selectedSkuId = null;
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $item = OrderItem::findOrFail($itemId);

        try {
            app(OrderService::class)->updateItemQuantity($this->order, $item, $quantity, auth('admin')->user());
            $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
            $this->dispatch('orderItemsShouldRefresh');
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());
        }
    }

    public function deleteItem(int $itemId): void
    {
        $item = OrderItem::findOrFail($itemId);

        try {
            app(OrderService::class)->deleteItem($this->order, $item, auth('admin')->user());
            $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
            $this->dispatch('orderItemsShouldRefresh');
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());
        }
    }

    public function openAddForm(): void
    {
        $this->reset(['productSearch', 'selectedProductId', 'selectedSkuId', 'replacingItemId']);
        $this->quantity = 1;
        $this->showAddForm = true;
    }

    public function openReplaceForm(int $itemId): void
    {
        $this->reset(['productSearch', 'selectedProductId', 'selectedSkuId']);
        $this->replacingItemId = $itemId;
        $this->quantity = 1;
        $this->showAddForm = true;
    }

    public function cancelForm(): void
    {
        $this->reset(['showAddForm', 'productSearch', 'selectedProductId', 'selectedSkuId', 'replacingItemId']);
        $this->quantity = 1;
    }

    public function submitForm(): void
    {
        $this->validate([
            'selectedProductId' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($this->selectedProductId);

        if ($product->has_variants && ! $this->selectedSkuId) {
            $this->addError('selectedSkuId', __('dashboard.select-variant'));

            return;
        }

        $sku = $this->selectedSkuId ? ProductSku::find($this->selectedSkuId) : null;

        try {
            if ($this->replacingItemId) {
                $item = OrderItem::findOrFail($this->replacingItemId);
                app(OrderService::class)->replaceItem($this->order, $item, $product, $sku, $this->quantity, auth('admin')->user());
            } else {
                app(OrderService::class)->addItem($this->order, $product, $sku, $this->quantity, auth('admin')->user());
            }

            $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
            $this->cancelForm();
            $this->dispatch('orderItemsShouldRefresh');
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('dashboard.orders.order-items-editor');
    }
}
