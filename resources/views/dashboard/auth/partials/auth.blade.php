<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="{{ Config::get('app.locale') == 'ar' ? 'rtl' : 'ltr' }}">
<!-- BEGIN: Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    @php
        $setting = \App\Models\Setting::first();
    @endphp
    <meta name="description" content="{{ $setting->meta_desc }}">
    <meta name="keywords"
        content="admin template, Vuexy admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="PIXINVENT">
    <title>Dashboard | {{ $title }}</title>
    <link rel="apple-touch-icon" href="{{ asset('dashboard') }}/app-assets/images/ico/apple-icon-120.png">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset($setting->favicon) }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600"
        rel="stylesheet">

    @if (Config::get('app.locale') == 'ar')
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/vendors/css/vendors-rtl.min.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css-rtl/bootstrap.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/bootstrap-extended.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css-rtl/colors.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css-rtl/components.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/themes/dark-layout.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/themes/bordered-layout.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/themes/semi-dark-layout.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/core/menu/menu-types/vertical-menu.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/plugins/forms/form-validation.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/pages/authentication.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css-rtl/custom-rtl.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/assets/css/style-rtl.css">
    @else
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/vendors/css/vendors.min.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/bootstrap.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/bootstrap-extended.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/colors.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/components.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/themes/dark-layout.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css/themes/bordered-layout.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css/themes/semi-dark-layout.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css/core/menu/menu-types/vertical-menu.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css/plugins/forms/form-validation.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/pages/authentication.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/custom.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/assets/css/style.css">
    @endif

    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/custom/one-kilo-theme.css">

</head>
<!-- END: Head-->

<!-- BEGIN: Body-->

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static  " data-open="click"
    data-menu="vertical-menu-modern" data-col="blank-page">
    <nav class="header-navbar navbar navbar-expand-lg align-items-center  navbar-light navbar-shadow container-xxl">
        <div class="navbar-container d-flex content">
            <ul class="nav navbar-nav bookmark-icons">
                <li class="nav-item d-none d-lg-block">
                    {{-- <a class="nav-link" href="#" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" title="{{__('dashboard.back')}}">
                        <i class="ficon fa-solid fa-arrow-{{-Config::get('app.locale')-==-'ar'-?-'right'-:-'left'-}}"></i>
                    </a> --}}
                </li>
            </ul>
            <ul class="nav navbar-nav align-items-center ms-auto">
                <li class="nav-item dropdown dropdown-language"><a class="nav-link dropdown-toggle"
                        id="dropdown-flag" href="#" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <i class="flag-icon flag-icon-{{ Config::get('app.locale') == 'ar' ? 'eg' : 'us' }}"></i><span
                            class="selected-language">{{ Config::get('app.locale') == 'ar' ? 'العربية' : 'English' }}</span></a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-flag">
                        @foreach (LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                            <a class="dropdown-item" hreflang="{{ $localeCode }}"
                                href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}"
                                data-language="en">
                                <i class="flag-icon flag-icon-{{ $localeCode == 'ar' ? 'eg' : 'us' }}"></i>
                                {{ $properties['native'] }}
                            </a>
                        @endforeach

                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
            </div>
            <div class="content-body">
                <div class="ok-auth-shell">
                    <div class="ok-auth-brand-panel">
                        <div class="ok-auth-brand-inner">
                            <img src="{{ asset($setting->logo) }}" alt="Logo" class="ok-auth-brand-logo">
                            <h1 class="ok-auth-brand-title">{{ $setting->site_name }}</h1>
                            <p class="ok-auth-brand-subtitle">{{ __('dashboard.auth-brand-subtitle') }}</p>

                            <ul class="ok-auth-brand-points">
                                <li><i class="fa-solid fa-bolt"></i> {{ __('dashboard.auth-point-realtime') }}</li>
                                <li><i class="fa-solid fa-chart-line"></i> {{ __('dashboard.auth-point-analytics') }}</li>
                                <li><i class="fa-solid fa-truck-fast"></i> {{ __('dashboard.auth-point-delivery') }}</li>
                            </ul>
                        </div>
                        <div class="ok-auth-brand-cart">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                    </div>

                    <div class="ok-auth-form-panel">
                        <div class="ok-auth-form-card">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END: Content-->


    <!-- BEGIN: Vendor JS-->
    <script src="{{ asset('dashboard') }}/app-assets/vendors/js/vendors.min.js"></script>
    <!-- BEGIN Vendor JS-->

    <!-- BEGIN: Page Vendor JS-->
    <script src="{{ asset('dashboard') }}/app-assets/vendors/js/forms/validation/jquery.validate.min.js"></script>
    <!-- END: Page Vendor JS-->

    <!-- BEGIN: Theme JS-->
    <script src="{{ asset('dashboard') }}/app-assets/js/core/app-menu.js"></script>
    <script src="{{ asset('dashboard') }}/app-assets/js/core/app.js"></script>
    <!-- END: Theme JS-->

    <!-- BEGIN: Page JS-->
    <script src="{{ asset('dashboard') }}/app-assets/js/scripts/pages/auth-login.js"></script>
    <!-- END: Page JS-->

    <script>
        $(window).on('load', function() {
            /* feather.replace removed */
        })
    </script>
    {{-- {!! NoCaptcha::renderJs() !!} --}}

</body>
<!-- END: Body-->

</html>
