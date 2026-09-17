<x-create-modal title="{{ __('dashboard.create-popup') }}">

    <div class="row">
        <div class="col-md-6">
            <label class="col-form-label">{{ __('dashboard.title') }} ({{ __('dashboard.arabic') }})</label>
            <input type="text" wire:model="title_ar" class="form-control" placeholder="{{ __('dashboard.title') }}">
            @include('dashboard.includes.error', ['property' => 'title_ar'])
        </div>

        <div class="col-md-6">
            <label class="col-form-label">{{ __('dashboard.title') }} ({{ __('dashboard.english') }})</label>
            <input type="text" wire:model="title_en" class="form-control" placeholder="{{ __('dashboard.title') }}">
            @include('dashboard.includes.error', ['property' => 'title_en'])
        </div>

        <div class="col-md-12 mt-1">
            <label class="col-form-label">{{ __('dashboard.image') }}</label>
            <input type="file" wire:model="image" class="form-control" accept="image/*">
            @include('dashboard.includes.error', ['property' => 'image'])
            @if ($image)
                <img src="{{ $image->temporaryUrl() }}" class="rounded border mt-1" width="120">
            @endif
        </div>

        <div class="col-md-6 mt-1">
            <label class="col-form-label">{{ __('dashboard.popup-link-type') }}</label>
            <select class="form-select" wire:model.live="link_type">
                <option value="none">{{ __('dashboard.popup-link-none') }}</option>
                <option value="product">{{ __('dashboard.popup-link-product') }}</option>
                <option value="category">{{ __('dashboard.popup-link-category') }}</option>
                <option value="coupon">{{ __('dashboard.popup-link-coupon') }}</option>
                <option value="url">{{ __('dashboard.popup-link-url') }}</option>
            </select>
            @include('dashboard.includes.error', ['property' => 'link_type'])
        </div>

        <div class="col-md-6 mt-1">
            @if ($link_type === 'product')
                <label class="col-form-label">{{ __('dashboard.select-product') }}</label>
                <select class="form-select" wire:model="link_id">
                    <option value="">-</option>
                    @foreach ($this->productOptions as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                @include('dashboard.includes.error', ['property' => 'link_id'])
            @elseif ($link_type === 'category')
                <label class="col-form-label">{{ __('dashboard.category') }}</label>
                <select class="form-select" wire:model="link_id">
                    <option value="">-</option>
                    @foreach ($this->categoryOptions as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @include('dashboard.includes.error', ['property' => 'link_id'])
            @elseif ($link_type === 'coupon')
                <label class="col-form-label">{{ __('dashboard.coupon') }}</label>
                <select class="form-select" wire:model="link_id">
                    <option value="">-</option>
                    @foreach ($this->couponOptions as $coupon)
                        <option value="{{ $coupon->id }}">{{ $coupon->code }}</option>
                    @endforeach
                </select>
                @include('dashboard.includes.error', ['property' => 'link_id'])
            @elseif ($link_type === 'url')
                <label class="col-form-label">{{ __('dashboard.popup-link-url') }}</label>
                <input type="text" wire:model="link_url" class="form-control" placeholder="https://...">
                @include('dashboard.includes.error', ['property' => 'link_url'])
            @endif
        </div>

        <div class="col-md-6 mt-1">
            <label class="col-form-label">{{ __('dashboard.starts-at') }}</label>
            <input type="datetime-local" wire:model="starts_at" class="form-control">
            @include('dashboard.includes.error', ['property' => 'starts_at'])
        </div>

        <div class="col-md-6 mt-1">
            <label class="col-form-label">{{ __('dashboard.expires-at') }}</label>
            <input type="datetime-local" wire:model="ends_at" class="form-control">
            @include('dashboard.includes.error', ['property' => 'ends_at'])
        </div>

        <div class="col-md-6 mt-1">
            <label class="col-form-label d-block">{{ __('dashboard.status') }}</label>
            <div class="d-flex align-items-center gap-2">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" wire:model.live="status">
                </div>
                <span class="fw-semibold">
                    {{ $status ? __('dashboard.active') : __('dashboard.inactive') }}
                </span>
            </div>
        </div>
    </div>

</x-create-modal>
