<?php

namespace App\Livewire\Dashboard\Coupons;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CouponCreate extends Component
{
    public string $code = '';

    public string $applies_to = 'subtotal';

    public string $type = 'amount';

    public string $delivery_discount_type = 'amount';

    public string $value = '';

    public string $min_order_amount = '';

    public string $max_discount_amount = '';

    public string $usage_limit = '';

    public string $usage_limit_per_user = '';

    public string $starts_at = '';

    public string $expires_at = '';

    public bool $status = true;

    public string $audience = 'all';

    public array $region_ids = [];

    public array $category_ids = [];

    public function rules(): array
    {
        $isDelivery = $this->applies_to === 'delivery_fee';

        return [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('coupons', 'code')],
            'applies_to' => ['required', Rule::in(['subtotal', 'delivery_fee'])],
            'type' => [Rule::requiredIf(! $isDelivery), Rule::in(['amount', 'percentage'])],
            'delivery_discount_type' => [Rule::requiredIf($isDelivery), Rule::in(['amount', 'percentage', 'free'])],
            'value' => [Rule::requiredIf(fn () => ! ($isDelivery && $this->delivery_discount_type === 'free')), 'nullable', 'numeric', 'gt:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'gt:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'boolean'],
            'audience' => ['required', Rule::in(['all', 'categories', 'regions'])],
            'region_ids' => [Rule::requiredIf($this->audience === 'regions'), 'array'],
            'region_ids.*' => ['integer', 'exists:regions,id'],
            'category_ids' => [Rule::requiredIf($this->audience === 'categories'), 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }

    public function updatedAppliesTo(): void
    {
        $this->resetErrorBag();
    }

    public function updatedAudience(): void
    {
        $this->region_ids = [];
        $this->category_ids = [];
        $this->resetErrorBag();
    }

    public function getRegionsProperty()
    {
        return Region::query()->orderBy('name')->get(['id', 'name']);
    }

    public function getCategoriesProperty()
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    public function submit(): void
    {
        $this->code = Str::upper(trim($this->code));
        $this->validate();

        if (! $this->passesBusinessRules()) {
            return;
        }

        $coupon = Coupon::query()->create($this->payload());

        if ($this->audience === 'regions') {
            $coupon->regions()->sync($this->region_ids);
        } elseif ($this->audience === 'categories') {
            $coupon->categories()->sync($this->category_ids);
        }

        $this->resetForm();
        $this->resetValidation();

        $this->dispatch('notify', type: 'success', message: __('dashboard.coupon-add-successfully'));
        $this->dispatch('createModalToggle');
        $this->dispatch('refreshData')->to(CouponsData::class);
    }

    private function payload(): array
    {
        $isDelivery = $this->applies_to === 'delivery_fee';

        return [
            'code' => $this->code,
            'applies_to' => $this->applies_to,
            'type' => $isDelivery ? 'amount' : $this->type,
            'delivery_discount_type' => $isDelivery ? $this->delivery_discount_type : null,
            'value' => $isDelivery && $this->delivery_discount_type === 'free'
                ? 0
                : round((float) $this->value, 2),
            'min_order_amount' => $this->nullableFloat($this->min_order_amount),
            'max_discount_amount' => (! $isDelivery && $this->type === 'percentage')
                ? $this->nullableFloat($this->max_discount_amount)
                : null,
            'usage_limit' => $this->nullableInt($this->usage_limit),
            'usage_limit_per_user' => $this->nullableInt($this->usage_limit_per_user),
            'starts_at' => $this->nullableDateTime($this->starts_at),
            'expires_at' => $this->nullableDateTime($this->expires_at),
            'status' => $this->status,
            'audience' => $this->audience,
        ];
    }

    private function passesBusinessRules(): bool
    {
        $isDelivery = $this->applies_to === 'delivery_fee';
        $percentageType = $isDelivery ? $this->delivery_discount_type === 'percentage' : $this->type === 'percentage';

        if ($percentageType && (float) $this->value > 100) {
            $this->addError(
                'value',
                __('validation.max.numeric', ['attribute' => __('dashboard.percentage'), 'max' => 100])
            );

            return false;
        }

        $usageLimit = $this->nullableInt($this->usage_limit);
        $usageLimitPerUser = $this->nullableInt($this->usage_limit_per_user);

        if ($usageLimit !== null && $usageLimitPerUser !== null && $usageLimitPerUser > $usageLimit) {
            $this->addError('usage_limit_per_user', __('dashboard.per-user-limit-cannot-exceed-usage-limit'));

            return false;
        }

        return true;
    }

    private function nullableFloat(?string $value): ?float
    {
        $value = $this->normalizeNullableValue($value);

        return $value === null ? null : round((float) $value, 2);
    }

    private function nullableInt(?string $value): ?int
    {
        $value = $this->normalizeNullableValue($value);

        return $value === null ? null : (int) $value;
    }

    private function nullableDateTime(?string $value): ?string
    {
        $value = $this->normalizeNullableValue($value);

        return $value === null ? null : Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function normalizeNullableValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resetForm(): void
    {
        $this->reset([
            'code',
            'applies_to',
            'type',
            'delivery_discount_type',
            'value',
            'min_order_amount',
            'max_discount_amount',
            'usage_limit',
            'usage_limit_per_user',
            'starts_at',
            'expires_at',
            'status',
            'audience',
            'region_ids',
            'category_ids',
        ]);

        $this->applies_to = 'subtotal';
        $this->type = 'amount';
        $this->delivery_discount_type = 'amount';
        $this->status = true;
        $this->audience = 'all';
    }

    public function render()
    {
        return view('dashboard.coupons.coupon-create');
    }
}
