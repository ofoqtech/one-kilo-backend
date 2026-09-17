@extends('dashboard.master', ['title' => __('dashboard.shifts')])
@section('shifts-active', 'active')

@section('content')
    <div class="row">
        <div class="col-12">
            @livewire('dashboard.shifts.shift-manager')
        </div>

        <div class="col-12 mt-1">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('dashboard.shifts-history') }}</h4>
                </div>
                <div class="card-body">
                    @livewire('dashboard.shifts.shifts-data')
                </div>
            </div>
        </div>
    </div>
@endsection
