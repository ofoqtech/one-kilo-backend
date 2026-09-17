<?php

namespace App\Livewire\Dashboard\Settings\Popups;

use App\Models\AppPopup;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Utils\ImageManger;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class PopupCreate extends Component
{
    use WithFileUploads;

    public string $title_ar = '';

    public string $title_en = '';

    public $image;

    public string $link_type = 'none';

    public ?int $link_id = null;

    public string $link_url = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $status = true;

    public function updatedLinkType(): void
    {
        $this->link_id = null;
        $this->link_url = '';
    }

    public function rules(): array
    {
        return [
            'title_ar' => ['nullable', 'string', 'max:150'],
            'title_en' => ['nullable', 'string', 'max:150'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'link_type' => ['required', Rule::in(AppPopup::linkTypes())],
            'link_id' => [Rule::requiredIf(in_array($this->link_type, ['product', 'category', 'coupon'], true)), 'nullable', 'integer'],
            'link_url' => [Rule::requiredIf($this->link_type === 'url'), 'nullable', 'url', 'max:2048'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function getProductOptionsProperty()
    {
        return $this->link_type === 'product'
            ? Product::query()->active()->orderByDesc('id')->limit(50)->get(['id', 'name'])
            : collect();
    }

    public function getCategoryOptionsProperty()
    {
        return $this->link_type === 'category'
            ? Category::query()->where('status', true)->orderBy('name')->get(['id', 'name'])
            : collect();
    }

    public function getCouponOptionsProperty()
    {
        return $this->link_type === 'coupon'
            ? Coupon::query()->where('status', true)->orderByDesc('id')->get(['id', 'code'])
            : collect();
    }

    public function submit(ImageManger $imageManger): void
    {
        $this->validate();

        $imagePath = null;

        if ($this->image instanceof TemporaryUploadedFile) {
            $imagePath = $imageManger->uploadImage('uploads/popups', $this->image);
        }

        AppPopup::create([
            'title' => [
                'ar' => $this->title_ar ?: null,
                'en' => $this->title_en ?: null,
            ],
            'image' => $imagePath,
            'link_type' => $this->link_type,
            'link_id' => in_array($this->link_type, ['product', 'category', 'coupon'], true) ? $this->link_id : null,
            'link_url' => $this->link_type === 'url' ? $this->link_url : null,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
            'status' => $this->status,
        ]);

        $this->reset(['title_ar', 'title_en', 'image', 'link_type', 'link_id', 'link_url', 'starts_at', 'ends_at']);
        $this->status = true;

        $this->dispatch('notify', type: 'success', message: __('dashboard.add-successfully'));
        $this->dispatch('createModalToggle');
        $this->dispatch('refreshData')->to(PopupsData::class);
    }

    public function render()
    {
        return view('dashboard.settings.popups.popup-create');
    }
}
