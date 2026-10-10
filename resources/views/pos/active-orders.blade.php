@extends('pos.layout')

@section('content')
<div class="screen">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
            <div>
                <div class="header-name">Active Orders</div>
                <div class="header-greeting" id="orderCountLabel">Monitoring {{ $orders->count() }} order(s)</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span id="realtimeDot" style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block;box-shadow:0 0 6px var(--green)" title="Live"></span>
            <select class="form-input" style="width:auto;font-size:.82rem;padding:7px 10px" onchange="currentType=this.value;applyFilters()">
                <option value="all">All Orders</option>
                <option value="dinein">Dine-in</option>
                <option value="takeout">Takeout</option>
            </select>
        </div>
    </div>

    {{-- Status filter --}}
    <div class="cat-tabs">
        <button class="cat-tab active" onclick="filterStatus('all', this)">All</button>
        <button class="cat-tab" onclick="filterStatus('pending', this)">Pending</button>
        <button class="cat-tab" onclick="filterStatus('preparing', this)">Preparing</button>
        <button class="cat-tab" onclick="filterStatus('served', this)">Served</button>
    </div>

    <div id="ordersContainer" style="padding:0 16px">
        {{-- Populated by JS polling --}}
    </div>

</div>

@php
    $initialOrders = $orders->map(function ($o) {
        return [
            'id'          => $o->Order_ID,
            'table'       => $o->Table_number,
            'status'      => $o->Status,
            'total'       => $o->Total_Amount,
            'updated_at'  => $o->updated_at?->diffForHumans(),
            'time'        => $o->created_at?->format('h:i A'),
            'items_count' => $o->orderItems->count(),
            'items'       => $o->orderItems->take(3)->map(function ($i) {
                return ['qty' => $i->Quantity, 'name' => $i->menu->Name ?? '—'];
            })->values(),
            'items_extra' => max(0, $o->orderItems->count() - 3),
            'is_takeout'  => str_starts_with($o->Table_number, 'Takeout'),
        ];
    })->values();
@endphp

@push('scripts')
<script>
    let currentType   = 'all';
    let currentStatus = 'all';

    const pollUrl = '{{ route("orders.poll") }}';
    @if(in_array(session('pos_employee')->Role, ['waiter','manager']))
    const canCreate = true;
    @else
    const canCreate = false;
    @endif

    function filterStatus(status, el) {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        currentStatus = status;
        applyFilters();
    }

    function applyFilters() {
        document.querySelectorAll('.order-card').forEach(card => {
            const tm = currentType   === 'all' || card.dataset.type   === currentType;
            const sm = currentStatus === 'all' || card.dataset.status === currentStatus;
            card.style.display = (tm && sm) ? '' : 'none';
        });
    }

    function renderOrders(orders) {
        const container = document.getElementById('ordersContainer');
        document.getElementById('orderCountLabel').textContent = 'Monitoring ' + orders.length + ' order(s)';

        if (orders.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--muted);margin-bottom:10px"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
                    <div>No active orders right now</div>
                    ${canCreate ? '<a href="/orders/new" class="btn-primary" style="margin-top:16px">Create New Order</a>' : ''}
                </div>`;
            return;
        }

        const html = orders.map(o => {
            const typeLabel = o.is_takeout ? 'Takeout' : 'Dine-in';
            const typeClass = o.is_takeout ? 'takeout' : 'dinein';
            const chips = o.items.map(i => `<span class="order-item-chip">${i.qty}x ${i.name}</span>`).join('');
            const extra = o.items_extra > 0 ? `<span class="order-item-chip">+${o.items_extra} more</span>` : '';
            return `
                <a href="/orders/${o.id}" class="order-card" data-status="${o.status}" data-type="${typeClass}">
                    <div class="order-card-header">
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                            <span class="order-table">${o.table}</span>
                            <span class="order-type-chip order-type-chip--${typeClass}">${typeLabel}</span>
                            <span class="status-badge status-${o.status}">${o.status.charAt(0).toUpperCase()+o.status.slice(1)}</span>
                        </div>
                        <div class="order-time">${o.time} · ${o.updated_at}</div>
                    </div>
                    <div class="order-card-items">${o.items_count} item(s) · ₱${parseFloat(o.total).toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
                    <div class="order-card-footer">${chips}${extra}</div>
                </a>`;
        }).join('');

        container.innerHTML = `<div class="orders-list" style="padding:0">${html}</div>`;
        applyFilters();
    }

    async function pollOrders() {
        try {
            const res  = await fetch(pollUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            renderOrders(data.orders);
            document.getElementById('realtimeDot').style.background = 'var(--green)';
        } catch {
            document.getElementById('realtimeDot').style.background = 'var(--danger)';
        }
    }

    // Initial render from server data (no flash on first load)
    renderOrders(@json($initialOrders));

    // Poll every 5 seconds
    setInterval(pollOrders, 5000);
</script>
@endpush
@endsection
