@extends('dashboard.auth.partials.auth', ['title' => 'Login'])

@section('content')
    <h4 class="card-title mb-50 fw-bolder">{{ __('dashboard.title-login') }}</h4>
    <p class="text-muted mb-2">{{ __('dashboard.auth-brand-subtitle') }}</p>

    <form class="auth-login-form mt-2" action="{{ route('dashboard.login.post') }}" method="POST">
        @csrf
        <div class="mb-1">
            <label class="form-label">{{ __('auth.email') }}</label>
            <input type="text" class="form-control" name="email" autofocus />
            @error('email')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-1">
            <div class="d-flex justify-content-between">
                <label class="form-label">{{ __('auth.password') }}</label>
                <a href="{{route('dashboard.password.email')}}">
                    <small>{{ __('auth.forget-password') }}</small>
                </a>
            </div>
            <div class="input-group input-group-merge form-password-toggle">
                <input type="password" class="form-control form-control-merge" name="password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"/>
                <span class="input-group-text cursor-pointer"><i class="fa-solid fa-eye"></i></span>
            </div>
        </div>
        <div class="mb-1">
            <div class="form-check">
                <input name="remember" class="form-check-input" type="checkbox" id="remember-me" tabindex="3" />
                <label class="form-check-label" for="remember-me"> {{ __('auth.remember-me') }} </label>
            </div>
        </div>
        <div class="mb-2">
            {{-- {!! NoCaptcha::display() !!} --}}
        </div>
        @error('g-recaptcha-response')
            <strong class="text-danger">{{message}}</strong>
        @enderror
        <button type="submit" class="btn btn-primary w-100" >{{ __('auth.sign-in') }}</button>
    </form>
@endsection
