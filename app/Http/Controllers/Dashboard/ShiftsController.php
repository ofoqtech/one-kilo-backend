<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\Dashboard\ShiftService;
use Illuminate\Http\Request;

class ShiftsController extends Controller
{
    public function index()
    {
        return view('dashboard.shifts.index');
    }

    public function show(Shift $shift, ShiftService $shiftService)
    {
        return view('dashboard.shifts.show', [
            'report' => $shiftService->report($shift),
        ]);
    }
}
