<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            @php
                $setting = \App\Models\Setting::first();
            @endphp
            <li class="nav-item me-auto"><a class="navbar-brand" href="{{ route('dashboard.home') }}"><span
                        class="brand-logo"><img src="{{ asset($setting->logo) }}"></span>
                    <h2 class="brand-text">{{ $setting->site_name }}</h2>
                </a></li>
            <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pe-0" data-bs-toggle="collapse"><i
                        class="d-block d-xl-none text-primary toggle-icon font-medium-4 fa-solid fa-xmark"></i><i
                        class="d-none d-xl-block collapse-toggle-icon font-medium-4  text-primary fa-solid fa-circle"
                        data-ticon="disc"></i></a></li>
        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            <li class="nav-item @yield('dashboard-active')"><a class="d-flex align-items-center"
                    href="{{ route('dashboard.home') }}"><i class="fa-solid fa-home"></i><span
                        class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.home') }}</span></a>
            </li>
            <li class=" navigation-header"><span data-i18n="Apps &amp; Pages">Apps &amp; Pages</span><i
                    class="fa-solid fa-ellipsis-h"></i>
            </li>

            @can('roles')
                <li class="nav-item @yield('roles-open') @yield('createRole-open')"><a class="d-flex align-items-center"
                        href="#"><i class="fa-solid fa-bars"></i><span class="menu-title text-truncate"
                            data-i18n="Roles &amp; Permission">{{ __('dashboard.roles') }}</span></a>
                    <ul class="menu-content">
                        <li><a class="@yield('roles-active') d-flex align-items-center"
                                href="{{ route('dashboard.roles.index') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate" data-i18n="Roles">{{ __('dashboard.roles') }}</span></a>
                        </li>
                        <li><a class="@yield('createRole-active') d-flex align-items-center"
                                href="{{ route('dashboard.roles.create') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Permission">{{ __('dashboard.create-role') }}</span></a>
                        </li>
                    </ul>
                </li>
            @endcan

            @can('admins')
                <li class="nav-item @yield('admins-open') @yield('createAdmin-open')"><a class="d-flex align-items-center"
                        href="#"><i class="fa-solid fa-users"></i><span class="menu-title text-truncate">
                            {{ __('dashboard.admins') }}</span>
                        <span
                            class="badge badge-light-warning rounded-pill ms-auto me-1">{{ App\Models\Admin::count() }}</span>
                    </a>
                    <ul class="menu-content">
                        <li><a class="@yield('admins-active') d-flex align-items-center"
                                href="{{ route('dashboard.admins.index') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.admins') }}</span></a>
                        </li>
                        <li><a class="@yield('createAdmin-active') d-flex align-items-center"
                                href="{{ route('dashboard.admins.create') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Permission">{{ __('dashboard.create-admin') }}</span></a>
                        </li>
                    </ul>
                </li>
            @endcan

            @can('users')
                <li class="nav-item @yield('users-open') @yield('createUser-open')"><a class="d-flex align-items-center"
                        href="#"><i class="fa-solid fa-users"></i><span class="menu-title text-truncate">
                            {{ __('dashboard.users') }}</span>
                        <span class="badge badge-light-warning rounded-pill ms-auto me-1"> {{ App\Models\User::count() }}
                        </span>
                    </a>
                    <ul class="menu-content">
                        <li><a class="@yield('users-active') d-flex align-items-center"
                                href="{{ route('dashboard.users.index') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.users') }}</span></a>
                        </li>
                    </ul>
                </li>
            @endcan

            @can('users')
            <li class="nav-item @yield('deliveries-open') @yield('createDelivery-open')"><a class="d-flex align-items-center"
                                                                                   href="#"><i class="fa-solid fa-users"></i><span class="menu-title text-truncate">
                            {{ __('dashboard.deliveries') }}</span>
                    <span class="badge badge-light-warning rounded-pill ms-auto me-1"> {{ App\Models\Delivery::count() }}
                        </span>
                </a>
                <ul class="menu-content">
                    <li><a class="@yield('deliveries-active') d-flex align-items-center"
                           href="{{ route('dashboard.deliveries.index') }}"><i class="fa-solid fa-circle"></i><span
                                class="menu-item text-truncate"
                                data-i18n="Roles">{{ __('dashboard.deliveries') }}</span></a>
                    </li>
                </ul>
            </li>
            @endcan

            <li class="nav-item @yield('shifts-active')"><a class="d-flex align-items-center"
                    href="{{ route('dashboard.shifts.index') }}"><i class="fa-solid fa-clock"></i><span
                        class="menu-title text-truncate">{{ __('dashboard.shifts') }}</span>
                </a>
            </li>

            @can('countries')
                <li class="nav-item @yield('countries-active')"><a class="d-flex align-items-center"
                        href="{{ route('dashboard.countries') }}"><i class="fa-solid fa-flag"></i><span
                            class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.countries') }}</span></a>
                </li>
            @endcan

            @can('countries')
            <li class="nav-item @yield('notifications-active')"><a class="d-flex align-items-center"
                                                               href="{{ route('dashboard.notifications') }}"><i class="fa-solid fa-bell"></i><span
                        class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.notifications') }}</span></a>
            </li>
            @endcan

            @can('categories')
                <li class="nav-item @yield('categories-active')"><a class="d-flex align-items-center"
                        href="{{ route('dashboard.categories') }}"><i class="fa-solid fa-th-large"></i><span
                            class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.categories') }}</span></a>
                </li>
            @endcan

            @can('products')
                <li class="nav-item @yield('products-active')"><a class="d-flex align-items-center"
                        href="{{ route('dashboard.products') }}"><i class="fa-solid fa-box"></i><span
                            class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.products') }}</span></a>
                </li>
            @endcan

            @can('variants')
                <li class="nav-item @yield('variants-active')"><a class="d-flex align-items-center"
                        href="{{ route('dashboard.variants') }}"><i class="fa-solid fa-layer-group"></i><span
                            class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.variants') }}</span></a>
                </li>
            @endcan

            @can('orders')
                <li class="nav-item @yield('orders-active')"><a class="d-flex align-items-center"
                        href="{{ route('dashboard.orders') }}"><i class="fa-solid fa-bag-shopping"></i><span
                            class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.orders') }}</span></a>
                </li>
            @endcan

            @can('contacts')
                <li class="nav-item @yield('contacts-active')"><a class="d-flex align-items-center"
                        href="{{ route('dashboard.contacts') }}"><i class="fa-solid fa-message"></i><span
                            class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.contacts') }}</span></a>
                </li>
            @endcan

            @can('coupons')
                <li class="nav-item @yield('coupons-active')"><a class="d-flex align-items-center"
                        href="{{ route('dashboard.coupons') }}"><i class="fa-solid fa-gift"></i><span
                            class="menu-title text-truncate" data-i18n="Email">{{ __('dashboard.coupons') }}</span></a>
                </li>
            @endcan



            @can('settings')
                <li class="nav-item @yield('settings-open')"><a class="d-flex align-items-center" href="#">
                        <i class="fa-solid fa-gear"></i><span class="menu-title text-truncate"
                            data-i18n="Roles &amp; Permission">{{ __('dashboard.settings') }}</span>
                    </a>
                    <ul class="menu-content">
                        <li><a class="@yield('settings-active') d-flex align-items-center"
                                href="{{ route('dashboard.settings') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.genral-setting') }}</span></a>
                        </li>
                    </ul>
                    <ul class="menu-content">
                        <li><a class="@yield('banners-active') d-flex align-items-center"
                                href="{{ route('dashboard.banners') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.banners') }}</span></a>
                        </li>
                    </ul>
                    <ul class="menu-content">
                        <li><a class="@yield('popups-active') d-flex align-items-center"
                                href="{{ route('dashboard.popups') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Popups">{{ __('dashboard.app-popups') }}</span></a>
                        </li>
                    </ul>
                    <ul class="menu-content">
                        <li><a class="@yield('about-active') d-flex align-items-center"
                                href="{{ route('dashboard.about.setting') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.about-setting') }}</span></a>
                        </li>
                    </ul>
                    <ul class="menu-content">
                        <li><a class="@yield('privacy-active') d-flex align-items-center"
                                href="{{ route('dashboard.privacy.setting') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.privacy-setting') }}</span></a>
                        </li>
                    </ul>
                    <ul class="menu-content">
                        <li><a class="@yield('terms-active') d-flex align-items-center"
                                href="{{ route('dashboard.terms.setting') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.terms-setting') }}</span></a>
                        </li>
                    </ul>
                    <ul class="menu-content">
                        <li><a class="@yield('faqs-active') d-flex align-items-center"
                                href="{{ route('dashboard.faqs.setting') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.faqs-settings') }}</span></a>
                        </li>
                    </ul>

                    <ul class="menu-content">
                        <li><a class="@yield('workingHours-active') d-flex align-items-center"
                               href="{{ route('dashboard.workingHours.setting') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item text-truncate"
                                    data-i18n="Roles">{{ __('dashboard.workingHours-settings') }}</span></a>
                        </li>
                    </ul>
                </li>
            @endcan

        </ul>
    </div>
</div>
