<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web', 'auth:admin']]);

        Paginator::useBootstrap();
        foreach (config('permessions_en') as $config_permession => $value) {
            Gate::define($config_permession, function ($auth) use ($config_permession) {
                return $auth->hasAccess($config_permession);
            });
        }
        Order::observe(OrderObserver::class);
    }
}
