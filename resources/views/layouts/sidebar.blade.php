<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="index.html" class="app-brand-link">
            <span class="app-brand-logo demo">
                <!-- Logo SVG -->
                <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- SVG paths -->
                </svg>
            </span>
            <span class="app-brand-text demo menu-text fw-bold">WA BLAST AI</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
            <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboards -->
        <li class="menu-item active open">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons ti ti-smart-home"></i>
                <div data-i18n="Dashboards">Dashboards</div>
                <div class="badge bg-label-primary rounded-pill ms-auto">4</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ Request::is('master') ? 'active' : '' }}">
                    <a href="{{ url('/master') }}" class="menu-link">
                        <div data-i18n="Orders">Orders</div>
                    </a>
                </li>
                <li class="menu-item {{ Request::is('customer') ? 'active' : '' }}">
                    <a href="{{ url('/customer') }}" class="menu-link">
                        <div data-i18n="Customers">Customers</div>
                    </a>
                </li>
                <li class="menu-item {{ Request::is('admin') ? 'active' : '' }}">
                    <a href="{{ url('/admin') }}" class="menu-link">
                        <div data-i18n="Partners">Partners</div>
                    </a>
                </li>
                <li class="menu-item {{ Request::is('cs') ? 'active' : '' }}">
                    <a href="{{ url('/cs') }}" class="menu-link">
                        <div data-i18n="Services">Services</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item active open">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons ti ti-smart-home"></i>
                <div data-i18n="Informasi">Informasi</div>
                <div class="badge bg-label-primary rounded-pill ms-auto">4</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ Request::is('histori') ? 'active' : '' }}">
                    <a href="{{ url('/histori') }}" class="menu-link">
                        <div data-i18n="Histori Chat">Histori Chat</div>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</aside>