<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Services\Dashboard\Auth\AuthService;
use App\Services\Dashboard\HomeDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateAdminRequest;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class AuthController extends Controller implements HasMiddleware
{
    protected $authService, $homeDashboardService;
    public function __construct(AuthService $authService, HomeDashboardService $homeDashboardService)
    {
        $this->authService = $authService;
        $this->homeDashboardService = $homeDashboardService;
    }

    public static function middleware()
    {
        return [
            new Middleware(middleware: 'guest:admin', except: ['logout', 'home']),
        ];
    }

    public function home(Request $request)
    {
        return view('dashboard.dashboard', [
            'analytics' => $this->homeDashboardService->build(
                (string) $request->query('range', '30d'),
                $request->query('from'),
                $request->query('to')
            ),
        ]);
    } // End Method
    public function login()
    {
        return view('dashboard.auth.login');
    } // End Method

    public function loginPost(CreateAdminRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $admin = Admin::where('email', $credentials['email'])->first();

        if ($admin && $admin->status == 1) {
            if ($this->authService->login($credentials, 'admin', $request->remember)) {
                $adminName = auth('admin')->user()->name;
                return redirect()->intended(route('dashboard.home'))->with('success', __('validation.login-success') . $adminName);
            } else {
                return redirect()->back()->with('error', __('auth.not-match'));
            }
        } else {
            return redirect()->back()->with('error', __('auth.inactive-account'));
        }
    } // End Method

    public function logout()
    {
        $this->authService->logout('admin');
        return redirect()->route('dashboard.login');
    } // End Method

}
