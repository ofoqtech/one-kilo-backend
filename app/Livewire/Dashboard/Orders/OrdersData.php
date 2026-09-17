<?php

namespace App\Livewire\Dashboard\Orders;

use App\Models\Country;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersData extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[On('echo:orders-dashboard,order.created')]
    #[On('echo:orders-dashboard,order.status-changed')]
    public function refreshList(): void
    {
        // no-op: triggers a re-render on the next Livewire request
    }

    public string $search = '';

    public string $statusFilter = 'all';

    public string $paymentStatusFilter = 'all';

    public string $paymentMethodFilter = 'all';

    public string $customerFilter = 'all';

    public string $countryFilter = 'all';

    public string $governorateFilter = 'all';

    public string $regionFilter = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $totalMin = null;

    public ?string $totalMax = null;

    public int $perPage = 10;

    public function updated(string $property): void
    {
        if (in_array($property, [
            'search',
            'statusFilter',
            'paymentStatusFilter',
            'paymentMethodFilter',
            'customerFilter',
            'countryFilter',
            'governorateFilter',
            'regionFilter',
            'dateFrom',
            'dateTo',
            'totalMin',
            'totalMax',
            'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    public function updatedCountryFilter(): void
    {
        $this->governorateFilter = 'all';
        $this->regionFilter = 'all';
        $this->resetPage();
    }

    public function updatedGovernorateFilter(): void
    {
        $this->regionFilter = 'all';
        $this->resetPage();
    }

    protected function customerOptions(): array
    {
        return User::query()
            ->whereHas('orders')
            ->select(['id', 'name', 'phone'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => trim($user->name . ($user->phone ? ' - ' . $user->phone : '')),
            ])
            ->all();
    }

    protected function countryOptions(): array
    {
        return Country::query()
            ->select(['id', 'name', 'status'])
            ->orderBy('id')
            ->get()
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'label' => $country->name . ($country->status ? '' : ' (' . __('dashboard.inactive') . ')'),
            ])
            ->all();
    }

    protected function governorateOptions(): array
    {
        return Governorate::query()
            ->select(['id', 'country_id', 'name', 'status'])
            ->when(
                $this->countryFilter !== 'all',
                fn (Builder $query) => $query->where('country_id', (int) $this->countryFilter)
            )
            ->orderBy('id')
            ->get()
            ->map(fn (Governorate $governorate) => [
                'id' => $governorate->id,
                'label' => $governorate->name . ($governorate->status ? '' : ' (' . __('dashboard.inactive') . ')'),
            ])
            ->all();
    }

    protected function regionOptions(): array
    {
        return Region::query()
            ->select(['id', 'governorate_id', 'name', 'status'])
            ->when(
                $this->governorateFilter !== 'all',
                fn (Builder $query) => $query->where('governorate_id', (int) $this->governorateFilter)
            )
            ->when(
                $this->governorateFilter === 'all' && $this->countryFilter !== 'all',
                fn (Builder $query) => $query->whereHas('governorate', fn (Builder $govQuery) => $govQuery->where('country_id', (int) $this->countryFilter))
            )
            ->orderBy('id')
            ->get()
            ->map(fn (Region $region) => [
                'id' => $region->id,
                'label' => $region->name . ($region->status ? '' : ' (' . __('dashboard.inactive') . ')'),
            ])
            ->all();
    }

    protected function parseAmount(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    public function render()
    {
        $search = trim($this->search);
        $totalMin = $this->parseAmount($this->totalMin);
        $totalMax = $this->parseAmount($this->totalMax);

        $items = Order::query()
            ->with([
                'user:id,name,email,phone',
                'address:id,country_id,governorate_id,region_id,contact_name,phone,city,area,street',
                'address.country:id,name',
                'address.governorate:id,name,country_id',
                'address.region:id,name,governorate_id',
                'walletTransaction:id,reference',
            ])
            ->withCount('items')
            ->withSum('items as items_quantity_sum', 'quantity')
            ->when($search !== '', function (Builder $query) use ($search) {
                $term = '%' . $search . '%';

                $query->where(function (Builder $subQuery) use ($search, $term) {
                    $hasNumericId = is_numeric($search);

                    if ($hasNumericId) {
                        $subQuery->where('id', (int) $search);
                    }

                    $firstTextCondition = $hasNumericId ? 'orWhere' : 'where';

                    $subQuery->{$firstTextCondition}('order_number', 'like', $term)
                        ->orWhereHas('user', function (Builder $userQuery) use ($term) {
                            $userQuery->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        })
                        ->orWhereHas('walletTransaction', function (Builder $walletQuery) use ($term) {
                            $walletQuery->where('reference', 'like', $term);
                        })
                        ->orWhere(function (Builder $snapshotQuery) use ($term) {
                            $snapshotQuery->where('meta->address_snapshot->contact_name', 'like', $term)
                                ->orWhere('meta->address_snapshot->phone', 'like', $term)
                                ->orWhere('meta->address_snapshot->city', 'like', $term)
                                ->orWhere('meta->address_snapshot->area', 'like', $term)
                                ->orWhere('meta->address_snapshot->street', 'like', $term)
                                ->orWhere('meta->address_snapshot->full_address', 'like', $term)
                                ->orWhere('meta->address_snapshot->country_name', 'like', $term)
                                ->orWhere('meta->address_snapshot->governorate_name', 'like', $term)
                                ->orWhere('meta->address_snapshot->region_name', 'like', $term);
                        })
                        ->orWhereHas('address', function (Builder $addressQuery) use ($term) {
                            $addressQuery->where('contact_name', 'like', $term)
                                ->orWhere('phone', 'like', $term)
                                ->orWhere('city', 'like', $term)
                                ->orWhere('area', 'like', $term)
                                ->orWhere('street', 'like', $term);
                        });
                });
            })
            ->when(
                $this->statusFilter !== 'all',
                fn (Builder $query) => $query->where('status', $this->statusFilter)
            )
            ->when(
                $this->paymentStatusFilter !== 'all',
                fn (Builder $query) => $query->where('payment_status', $this->paymentStatusFilter)
            )
            ->when(
                $this->paymentMethodFilter !== 'all',
                fn (Builder $query) => $query->where('payment_method', $this->paymentMethodFilter)
            )
            ->when(
                $this->customerFilter !== 'all',
                fn (Builder $query) => $query->where('user_id', (int) $this->customerFilter)
            )
            ->when($this->countryFilter !== 'all', function (Builder $query) {
                $countryId = (int) $this->countryFilter;

                $query->where(function (Builder $subQuery) use ($countryId) {
                    $subQuery->where('meta->address_snapshot->country_id', $countryId)
                        ->orWhereHas('address', fn (Builder $addressQuery) => $addressQuery->where('country_id', $countryId));
                });
            })
            ->when($this->governorateFilter !== 'all', function (Builder $query) {
                $governorateId = (int) $this->governorateFilter;

                $query->where(function (Builder $subQuery) use ($governorateId) {
                    $subQuery->where('meta->address_snapshot->governorate_id', $governorateId)
                        ->orWhereHas('address', fn (Builder $addressQuery) => $addressQuery->where('governorate_id', $governorateId));
                });
            })
            ->when($this->regionFilter !== 'all', function (Builder $query) {
                $regionId = (int) $this->regionFilter;

                $query->where(function (Builder $subQuery) use ($regionId) {
                    $subQuery->where('meta->address_snapshot->region_id', $regionId)
                        ->orWhereHas('address', fn (Builder $addressQuery) => $addressQuery->where('region_id', $regionId));
                });
            })
            ->when(
                filled($this->dateFrom),
                fn (Builder $query) => $query->whereDate('placed_at', '>=', $this->dateFrom)
            )
            ->when(
                filled($this->dateTo),
                fn (Builder $query) => $query->whereDate('placed_at', '<=', $this->dateTo)
            )
            ->when($totalMin !== null, fn (Builder $query) => $query->where('total', '>=', $totalMin))
            ->when($totalMax !== null, fn (Builder $query) => $query->where('total', '<=', $totalMax))
            ->latest('placed_at')
            ->latest('id')
            ->paginate($this->perPage);

        return view('dashboard.orders.orders-data', [
            'items' => $items,
            'customers' => $this->customerOptions(),
            'countries' => $this->countryOptions(),
            'governorates' => $this->governorateOptions(),
            'regions' => $this->regionOptions(),
            'statuses' => Order::statuses(),
            'paymentStatuses' => Order::paymentStatuses(),
            'paymentMethods' => Order::paymentMethods(),
            'canChangeStatus' => (bool) auth('admin')->user()?->hasAccess('orders_change_status'),
        ]);
    }
}
