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

class PopupUpdate extends Component
{
    use WithFileUploads;

    public ?int $popupId = null;

    public string $title_ar = '';

    public string $title_en = '';

    public $image;

    public ?string $currentImage = null;

    public string $link_type = 'none';

    public ?int $link_id = null;

    public string $link_url = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $status = true;

    protected $listeners = [
        'popupUpdate' => 'loadItem',
    ];

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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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

    public function loadItem(int $id): void
    {
        $item = AppPopup::query()->find($id);

        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.no-data'));

            return;
        }

        $this->popupId = $item->id;
        $this->title_ar = $item->getTranslation('title', 'ar') ?? '';
        $this->title_en = $item->getTranslation('title', 'en') ?? '';
        $this->currentImage = $item->image;
        $this->link_type = $item->link_type;
        $this->link_id = $item->link_id;
        $this->link_url = $item->link_url ?? '';
        $this->starts_at = optional($item->starts_at)->format('Y-m-d\TH:i');
        $this->ends_at = optional($item->ends_at)->format('Y-m-d\TH:i');
        $this->status = (bool) $item->status;
        $this->image = null;
        $this->resetValidation();

        $this->dispatch('updateModalToggle');
    }

    public function submit(ImageManger $imageManger): void
    {
        $popup = AppPopup::query()->find($this->popupId);

        if (! $popup) {
            $this->dispatch('notify', type: 'error', message: __('dashboard.no-data'));

            return;
        }

        $this->validate();

        $payload = [
            'title' => [
                'ar' => $this->title_ar ?: null,
                'en' => $this->title_en ?: null,
            ],
            'link_type' => $this->link_type,
            'link_id' => in_array($this->link_type, ['product', 'category', 'coupon'], true) ? $this->link_id : null,
            'link_url' => $this->link_type === 'url' ? $this->link_url : null,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
            'status' => $this->status,
        ];

        if ($this->image instanceof TemporaryUploadedFile) {
            $newImagePath = $imageManger->uploadImage('uploads/popups', $this->image);

            if ($popup->image && str_starts_with($popup->image, 'uploads/popups/')) {
                $imageManger->deleteImage($popup->image);
            }

            $payload['image'] = $newImagePath;
        }

        $popup->update($payload);

        $this->dispatch('notify', type: 'success', message: __('dashboard.update-successfully'));
        $this->dispatch('updateModalToggle');
        $this->dispatch('refreshData')->to(PopupsData::class);
    }

    public function render()
    {
        return view('dashboard.settings.popups.popup-update');
    }
}
