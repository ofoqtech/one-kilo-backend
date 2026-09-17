<?php

namespace App\Livewire\Dashboard\Shifts;

use App\Models\Shift;
use App\Services\Dashboard\ShiftService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShiftManager extends Component
{
    public string $openingCash = '';

    public string $closingCash = '';

    public function getCurrentShiftProperty(): ?Shift
    {
        return auth('admin')->user()?->currentShift();
    }

    public function getReportProperty(): ?array
    {
        $shift = $this->currentShift;

        return $shift ? app(ShiftService::class)->report($shift) : null;
    }

    public function openShift(ShiftService $shiftService): void
    {
        try {
            $shiftService->open(auth('admin')->user(), $this->nullableFloat($this->openingCash));
            $this->reset('openingCash');
            $this->dispatch('notify', type: 'success', message: __('dashboard.shift-opened-successfully'));
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());
        }
    }

    public function closeShift(ShiftService $shiftService): void
    {
        $shift = $this->currentShift;

        if (! $shift) {
            return;
        }

        try {
            $shiftService->close($shift, $this->nullableFloat($this->closingCash));
            $this->reset('closingCash');
            $this->dispatch('notify', type: 'success', message: __('dashboard.shift-closed-successfully'));
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());
        }
    }

    private function nullableFloat(string $value): ?float
    {
        $value = trim($value);

        return $value === '' ? null : round((float) $value, 2);
    }

    public function render()
    {
        return view('dashboard.shifts.shift-manager');
    }
}
