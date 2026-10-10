<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Whaspil POS</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
</head>
<body>

@if(session('pos_employee'))
{{-- Hamburger toggle (mobile) --}}
<button class="nav-hamburger" id="navToggle" onclick="toggleNav()" aria-label="Menu">
    <span></span><span></span><span></span>
</button>

{{-- Overlay for mobile --}}
<div class="nav-overlay" id="navOverlay" onclick="closeNav()"></div>

<nav class="side-nav" id="sideNav">
    {{-- Logo --}}
    <div class="side-nav-logo">
        <img src="/images/whaspil_logo.png" alt="Whaspil" class="side-nav-logo-img">
        <div class="side-nav-brand">
            <div class="side-nav-brand-name">Whaspil</div>
            <div class="side-nav-brand-sub">Restaurant POS</div>
        </div>
    </div>

    <div class="side-nav-links">

        {{-- MAIN --}}
        <div class="nav-category">Dashboard</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="home" class="nav-icon"></i>
            <span class="nav-label">Home</span>
        </a>

        {{-- ORDERS --}}
        <div class="nav-category">Operations</div>
        @if(in_array(session('pos_employee')->Role, ['waiter','manager']))
        <a href="{{ route('orders.create') }}" class="nav-item {{ request()->routeIs('orders.create') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="plus-circle" class="nav-icon"></i>
            <span class="nav-label">New Order</span>
        </a>
        @endif

        @if(session('pos_employee')->Role === 'cashier')
        <a href="{{ route('tables.index') }}" class="nav-item {{ request()->routeIs('tables.index') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="layout-grid" class="nav-icon"></i>
            <span class="nav-label">Tables</span>
        </a>
        @endif

        <a href="{{ route('orders.active') }}" class="nav-item {{ request()->routeIs('orders.active','orders.show') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="clipboard-list" class="nav-icon"></i>
            <span class="nav-label">Active Orders</span>
        </a>

        {{-- KITCHEN --}}
        @if(in_array(session('pos_employee')->Role, ['chef','manager']))
        <div class="nav-category">Inventory</div>
        <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="package" class="nav-icon"></i>
            <span class="nav-label">Stocks</span>
        </a>
        @endif
        @if(session('pos_employee')->Role === 'manager')
        <a href="{{ route('menu.index') }}" class="nav-item {{ request()->routeIs('menu.*') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="utensils" class="nav-icon"></i>
            <span class="nav-label">Menu</span>
        </a>
        @endif

        {{-- MANAGEMENT --}}
        @if(session('pos_employee')->Role === 'manager')
        <div class="nav-category">Management</div>
        <a href="{{ route('sales.index') }}" class="nav-item {{ request()->routeIs('sales.*') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="bar-chart-2" class="nav-icon"></i>
            <span class="nav-label">Sales</span>
        </a>
        <a href="{{ route('employees.index') }}" class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="users" class="nav-icon"></i>
            <span class="nav-label">Staff</span>
        </a>
        <a href="{{ route('audit.index') }}" class="nav-item {{ request()->routeIs('audit.*') ? 'active' : '' }}" onclick="closeNav()">
            <i data-lucide="shield-check" class="nav-icon"></i>
            <span class="nav-label">Audit Log</span>
        </a>
        @endif

    </div>

    {{-- User + Logout --}}
    <div class="side-nav-footer">
        <div class="side-nav-user">
            <div class="side-nav-avatar">{{ substr(session('pos_employee')->FNM ?? '?', 0, 1) }}</div>
            <div class="side-nav-user-info">
                <div class="side-nav-user-name">
                    {{ session('pos_employee')->FNM }}
                    {{ session('pos_employee')->LNM }}
                </div>
                <div class="side-nav-user-role">{{ ucfirst(session('pos_employee')->Role) }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="width:100%">
            @csrf
            <button type="submit" class="nav-logout-btn">
                <i data-lucide="log-out" class="nav-icon"></i>
                Logout
            </button>
        </form>
    </div>
</nav>
@endif

<div class="page-wrapper" id="pageWrapper">
    @if(session('success'))
        <div class="toast toast-success" id="toast">{{ session('success') }}</div>
    @endif
    @if(session('error') || $errors->any())
        <div class="toast toast-error" id="toast">
            {{ session('error') ?? $errors->first() }}
        </div>
    @endif

    @yield('content')
</div>

<script>
    const toast = document.getElementById('toast');
    if (toast) setTimeout(() => toast.classList.add('toast-hide'), 3000);

    function toggleNav() {
        document.getElementById('sideNav').classList.toggle('open');
        document.getElementById('navOverlay').classList.toggle('show');
    }
    function closeNav() {
        document.getElementById('sideNav').classList.remove('open');
        document.getElementById('navOverlay').classList.remove('show');
    }
</script>
@stack('scripts')
<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
</body>
</html>
