<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    {{-- <meta name="description" content="{{ $setting->meta_desc }}"> --}}
    <meta name="author" content="PIXINVENT">
    <title>Dashboard | {{ $title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" href="{{ asset('dashboard') }}/app-assets/images/ico/apple-icon-120.png">
    {{-- <link rel="shortcut icon" type="image/x-icon" href="{{ asset($setting->favicon) }}"> --}}
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600"
        rel="stylesheet">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/vendors/css/charts/apexcharts.css">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('dashboard') }}/app-assets/vendors/css/extensions/toastr.min.css">



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
            href="{{ asset('dashboard') }}/app-assets/css-rtl/pages/dashboard-ecommerce.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/plugins/charts/chart-apex.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/plugins/extensions/ext-component-toastr.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css-rtl/custom-rtl.css">
        {{-- <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/assets/css/style-rtl.css"> --}}
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css-rtl/plugins/extensions/ext-component-sweet-alerts.css">
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
            href="{{ asset('dashboard') }}/app-assets/css/pages/dashboard-ecommerce.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css/plugins/charts/chart-apex.css">
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css/plugins/extensions/ext-component-toastr.css">
        {{-- <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/custom.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/assets/css/style.css"> --}}
        <link rel="stylesheet" type="text/css"
            href="{{ asset('dashboard') }}/app-assets/css/plugins/extensions/ext-component-sweet-alerts.css">
    @endif

    <link rel="stylesheet" type="text/css"
        href="{{ asset('dashboard') }}/app-assets/vendors/css/extensions/sweetalert2.min.css">

    {{-- file input to upload image and show it --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('vendor/file-input/css/fileinput.min.css') }}">
    {{-- <link href="{{ asset('vendor/file-input/themes/fa5/theme.min.css') }}" rel="stylesheet"> --}}
    {{-- end file input to upload image and show it --}}

    {{-- One Kilo brand theme (loaded last so it overrides the vendor theme) --}}
    @php
        $okThemePath = public_path('dashboard/app-assets/css/custom/one-kilo-theme.css');
        $okThemeVersion = file_exists($okThemePath) ? filemtime($okThemePath) : time();
    @endphp
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard') }}/app-assets/css/custom/one-kilo-theme.css?v={{ $okThemeVersion }}">

    <script>
        (function () {
            try {
                var saved = localStorage.getItem('ok-theme');
                if (saved === 'dark') {
                    document.documentElement.setAttribute('data-ok-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>

    @stack('css')
</head>
