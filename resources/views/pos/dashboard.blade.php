@extends('pos.layout')

@section('content')
@php $role = session('pos_employee')->Role; @endphp
<div class="screen">

    <div class="page-header">
        <div class="header-left">
            <div class="avatar-circle">{{ substr(session('pos_employee')->FNM, 0, 1) }}</div>
            <div>
                <div class="header-greeting">Welcome back,</div>
                <div class="header-name">{{ session('pos_employee')->FNM }} {{ session('pos_employee')->LNM }}</div>
                <span class="badge badge-role">{{ ucfirst($role) }}</span>
            </div>
        </div>
    </div>

    @if(in_array($role, ['cashier','manager']))
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Today's Sales</div>
            <div class="stat-value" id="todaySales">₱{{ number_format($todaySales, 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Orders</div>
            <div class="stat-value" id="activeOrders">{{ $activeOrders }}</div>
        </div>
    </div>
    @else
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Active Orders</div>
            <div class="stat-value" id="activeOrders">{{ $activeOrders }}</div>
        </div>
    </div>
    @endif

    <h2 class="section-title">Quick Access</h2>
    <div class="module-list">

        @if(in_array($role, ['cashier','waiter','manager']))
        <a href="{{ route('orders.create') }}" class="module-item">
            <div class="module-icon"><i data-lucide="plus-circle"></i></div>
            <div class="module-info">
                <div class="module-name">New Order</div>
                <div class="module-desc">Assign table &amp; take order</div>
            </div>
            <i data-lucide="chevron-right" class="module-arrow"></i>
        </a>
        @endif

        <a href="{{ route('orders.active') }}" class="module-item">
            <div class="module-icon"><i data-lucide="clipboard-list"></i></div>
            <div class="module-info">
                <div class="module-name">Active Orders</div>
                @if($role === 'chef')
                    <div class="module-desc">View &amp; update food preparation</div>
                @elseif($role === 'waiter')
                    <div class="module-desc">Serve food &amp; update order status</div>
                @else
                    <div class="module-desc">Monitor preparing / served orders</div>
                @endif
            </div>
            <i data-lucide="chevron-right" class="module-arrow"></i>
        </a>

        @if(in_array($role, ['chef','manager']))
        <a href="{{ route('inventory.index') }}" class="module-item">
            <div class="module-icon"><i data-lucide="package"></i></div>
            <div class="module-info">
                <div class="module-name">Inventory</div>
                <div class="module-desc">Manage raw ingredients &amp; stock</div>
            </div>
            <i data-lucide="chevron-right" class="module-arrow"></i>
        </a>
        @endif

        @if($role === 'manager')
        <a href="{{ route('sales.index') }}" class="module-item">
            <div class="module-icon"><i data-lucide="bar-chart-2"></i></div>
            <div class="module-info">
                <div class="module-name">Sales Reports</div>
                <div class="module-desc">Daily shift overview &amp; summaries</div>
            </div>
            <i data-lucide="chevron-right" class="module-arrow"></i>
        </a>

        <a href="{{ route('menu.index') }}" class="module-item">
            <div class="module-icon"><i data-lucide="utensils"></i></div>
            <div class="module-info">
                <div class="module-name">Menu Management</div>
                <div class="module-desc">Add, edit, or remove menu items</div>
            </div>
            <i data-lucide="chevron-right" class="module-arrow"></i>
        </a>

        <a href="{{ route('employees.index') }}" class="module-item">
            <div class="module-icon"><i data-lucide="users"></i></div>
            <div class="module-info">
                <div class="module-name">Employees</div>
                <div class="module-desc">Manage staff and roles</div>
            </div>
            <i data-lucide="chevron-right" class="module-arrow"></i>
        </a>

        <a href="{{ route('audit.index') }}" class="module-item">
            <div class="module-icon"><i data-lucide="shield-check"></i></div>
            <div class="module-info">
                <div class="module-name">Audit Log</div>
                <div class="module-desc">Track all system activity</div>
            </div>
            <i data-lucide="chevron-right" class="module-arrow"></i>
        </a>
        @endif

    </div>
</div>
@push('scripts')
<script>
    async function pollStats() {
        try {
            const res  = await fetch('{{ route("dashboard.stats") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            const sales = document.getElementById('todaySales');
            const active = document.getElementById('activeOrders');
            if (sales)  sales.textContent  = '₱' + parseFloat(data.todaySales).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            if (active) active.textContent = data.activeOrders;
        } catch {}
    }
    setInterval(pollStats, 8000);
</script>
@endpush
@endsection
