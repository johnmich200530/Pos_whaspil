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

    {{-- Combined payment bar (cashier/manager only) --}}
    @if(in_array(session('pos_employee')->Role, ['cashier','manager']))
    <div id="combineBar" style="display:none;position:sticky;top:0;z-index:50;background:var(--dark2);border-bottom:1px solid var(--border);padding:10px 16px;display:none;align-items:center;gap:12px;flex-wrap:wrap">
        <span style="font-size:.85rem;color:var(--muted)" id="combineInfo">0 orders selected</span>
        <span style="font-weight:700;color:var(--gold)" id="combineTotalDisplay">₱0.00</span>
        <button onclick="openCombineModal()" id="combineBtn" class="btn-primary btn-sm" disabled>
            Pay Selected
        </button>
        <button onclick="clearSelection()" class="btn-secondary btn-sm">Clear</button>
    </div>
    @endif

    <div id="ordersContainer" style="padding:0 16px">
        {{-- Populated by JS polling --}}
    </div>

</div>

{{-- Combined Payment Modal --}}
@if(in_array(session('pos_employee')->Role, ['cashier','manager']))
<div class="modal-overlay" id="combineModal" style="display:none">
    <div class="modal">
        <div class="modal-title">Combined Payment</div>
        <div id="combineOrderList" style="font-size:.85rem;color:var(--muted);margin-bottom:12px"></div>
        <div class="modal-total-row">
            <span>Combined Total</span>
            <strong id="combineTotalModal" class="text-accent">₱0.00</strong>
        </div>
        <div class="form-group" style="margin-top:12px">
            <label class="form-label">Payment Method</label>
            <div class="method-btns">
                <input type="radio" name="cmethod" id="cmCash" value="cash" checked class="sr-only">
                <label for="cmCash" class="method-btn selected" onclick="switchCombineMethod('cash')">Cash</label>
                <input type="radio" name="cmethod" id="cmGcash" value="gcash" class="sr-only">
                <label for="cmGcash" class="method-btn" onclick="switchCombineMethod('gcash')">GCash</label>
            </div>
        </div>
        <div id="cmCashFields">
            <div class="form-group">
                <label class="form-label">Amount Tendered</label>
                <input type="number" id="cmTendered" class="form-input" placeholder="₱0.00" step="0.01" min="0" autocomplete="off" oninput="showCombineChange()">
            </div>
            <div class="modal-change-row" id="cmChangeDisplay" style="display:none">
                <span>Change</span>
                <strong id="cmChangeOut" class="text-accent"></strong>
            </div>
        </div>
        <div id="cmGcashFields" style="display:none">
            <p style="font-size:.82rem;color:var(--muted);text-align:center;padding:8px 0">GCash payment — total will be auto-applied.</p>
        </div>
        <button type="button" class="btn-primary" id="cmConfirmBtn" onclick="submitCombinedPayment()">Confirm Payment</button>
        <button type="button" class="btn-secondary" onclick="closeCombineModal()">Cancel</button>
    </div>
</div>
@endif

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
    const isCashier   = @json(in_array(session('pos_employee')->Role, ['cashier','manager']));
    const pollUrl     = '{{ route("orders.poll") }}';
    const combineUrl  = '{{ route("payments.combine") }}';
    const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

    @if(in_array(session('pos_employee')->Role, ['waiter','manager']))
    const canCreate = true;
    @else
    const canCreate = false;
    @endif

    // ── Selection state ──────────────────────────────────────────
    let selected = {}; // { orderId: { table, total } }

    function toggleSelect(e, orderId, table, total) {
        e.preventDefault();
        e.stopPropagation();
        if (selected[orderId]) {
            delete selected[orderId];
        } else {
            selected[orderId] = { table, total: parseFloat(total) };
        }
        updateSelectionUI();
    }

    function clearSelection() {
        selected = {};
        updateSelectionUI();
    }

    function updateSelectionUI() {
        const ids    = Object.keys(selected);
        const count  = ids.length;
        const total  = Object.values(selected).reduce((s, o) => s + o.total, 0);
        const tables = [...new Set(Object.values(selected).map(o => o.table))];

        // Update checkboxes
        document.querySelectorAll('.order-select-cb').forEach(cb => {
            cb.checked = !!selected[cb.dataset.id];
            cb.closest('.order-card-wrap')?.classList.toggle('order-card-selected', !!selected[cb.dataset.id]);
        });

        // Update bar
        const bar = document.getElementById('combineBar');
        if (bar) {
            bar.style.display = count > 0 ? 'flex' : 'none';
            document.getElementById('combineInfo').textContent =
                count + ' order' + (count !== 1 ? 's' : '') + ' selected' +
                (tables.length === 1 ? ' · ' + tables[0] : '');
            document.getElementById('combineTotalDisplay').textContent =
                '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            document.getElementById('combineBtn').disabled = count < 1;
        }
    }

    // ── Combine payment modal ─────────────────────────────────────
    function openCombineModal() {
        const ids    = Object.keys(selected);
        const total  = Object.values(selected).reduce((s, o) => s + o.total, 0);
        document.getElementById('combineTotalModal').textContent =
            '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        document.getElementById('combineOrderList').innerHTML =
            ids.map(id => {
                const o = selected[id];
                return `<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border)">
                    <span>${o.table}</span>
                    <span>₱${o.total.toLocaleString('en-PH',{minimumFractionDigits:2})}</span>
                </div>`;
            }).join('');
        document.getElementById('cmTendered').value = '';
        document.getElementById('cmTendered').min = total;
        document.getElementById('cmChangeDisplay').style.display = 'none';
        document.getElementById('combineModal').style.display = 'flex';
    }

    function closeCombineModal() {
        document.getElementById('combineModal').style.display = 'none';
    }

    function switchCombineMethod(method) {
        document.querySelectorAll('#combineModal .method-btn').forEach(l => l.classList.remove('selected'));
        document.querySelector(`label[for="${method === 'cash' ? 'cmCash' : 'cmGcash'}"]`).classList.add('selected');
        document.getElementById('cmCashFields').style.display  = method === 'cash'  ? 'block' : 'none';
        document.getElementById('cmGcashFields').style.display = method === 'gcash' ? 'block' : 'none';
    }

    function showCombineChange() {
        const total    = Object.values(selected).reduce((s, o) => s + o.total, 0);
        const tendered = parseFloat(document.getElementById('cmTendered').value) || 0;
        const el       = document.getElementById('cmChangeDisplay');
        if (tendered > 0) {
            el.style.display = 'flex';
            document.getElementById('cmChangeOut').textContent =
                '₱' + Math.max(0, tendered - total).toLocaleString('en-PH', { minimumFractionDigits: 2 });
        } else {
            el.style.display = 'none';
        }
    }

    async function submitCombinedPayment() {
        const ids     = Object.keys(selected);
        const total   = Object.values(selected).reduce((s, o) => s + o.total, 0);
        const method  = document.querySelector('input[name="cmethod"]:checked').value;
        const tendered = method === 'cash'
            ? parseFloat(document.getElementById('cmTendered').value) || 0
            : total;

        if (method === 'cash' && tendered < total) {
            alert('Amount tendered must cover the combined total.');
            return;
        }

        const btn = document.getElementById('cmConfirmBtn');
        btn.disabled    = true;
        btn.textContent = 'Processing…';

        try {
            const res  = await fetch(combineUrl, {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':     csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ order_ids: ids, method, amount_tendered: tendered }),
            });
            const data = await res.json();
            if (data.receipt_url) {
                window.location.href = data.receipt_url;
            } else {
                alert(data.error || 'Something went wrong.');
                btn.disabled    = false;
                btn.textContent = 'Confirm Payment';
            }
        } catch {
            alert('Network error. Please try again.');
            btn.disabled    = false;
            btn.textContent = 'Confirm Payment';
        }
    }

    // ── Filters ───────────────────────────────────────────────────
    function filterStatus(status, el) {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        currentStatus = status;
        applyFilters();
    }

    function applyFilters() {
        document.querySelectorAll('.order-card-wrap').forEach(wrap => {
            const tm = currentType   === 'all' || wrap.dataset.type   === currentType;
            const sm = currentStatus === 'all' || wrap.dataset.status === currentStatus;
            wrap.style.display = (tm && sm) ? '' : 'none';
        });
    }

    // ── Render ────────────────────────────────────────────────────
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
            clearSelection();
            return;
        }

        const html = orders.map(o => {
            const typeLabel  = o.is_takeout ? 'Takeout' : 'Dine-in';
            const typeClass  = o.is_takeout ? 'takeout' : 'dinein';
            const chips      = o.items.map(i => `<span class="order-item-chip">${i.qty}× ${i.name}</span>`).join('');
            const extra      = o.items_extra > 0 ? `<span class="order-item-chip order-item-chip--more">+${o.items_extra} more</span>` : '';
            const isSelected = !!selected[o.id];

            const checkbox = isCashier ? `
                <label class="order-select-label" onclick="event.stopPropagation()">
                    <input type="checkbox" class="order-select-cb" data-id="${o.id}"
                        ${isSelected ? 'checked' : ''}
                        onchange="toggleSelect(event, ${o.id}, '${o.table}', ${o.total})">
                    <span class="order-select-box"></span>
                </label>` : '';

            return `
                <div class="order-card-wrap ${isSelected ? 'order-card-selected' : ''}"
                     data-status="${o.status}" data-type="${typeClass}">
                    ${checkbox}
                    <a href="/orders/${o.id}" class="order-card order-card-enhanced">
                        <div class="order-card-top">
                            <div class="order-card-left">
                                <div class="order-card-table">${o.table}</div>
                                <div class="order-card-badges">
                                    <span class="order-type-chip order-type-chip--${typeClass}">${typeLabel}</span>
                                    <span class="status-badge status-${o.status}">${o.status.charAt(0).toUpperCase()+o.status.slice(1)}</span>
                                </div>
                            </div>
                            <div class="order-card-right">
                                <div class="order-card-amount">₱${parseFloat(o.total).toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
                                <div class="order-card-time">${o.time} · ${o.updated_at}</div>
                            </div>
                        </div>
                        <div class="order-card-divider"></div>
                        <div class="order-card-bottom">
                            <span class="order-card-count">${o.items_count} item${o.items_count !== 1 ? 's' : ''}</span>
                            <div class="order-card-footer">${chips}${extra}</div>
                        </div>
                    </a>
                </div>`;
        }).join('');

        container.innerHTML = `<div class="orders-list" style="padding:0">${html}</div>`;

        // Re-apply selection state to new checkboxes
        document.querySelectorAll('.order-select-cb').forEach(cb => {
            const id = cb.dataset.id;
            cb.checked = !!selected[id];
            cb.closest('.order-card-wrap')?.classList.toggle('order-card-selected', !!selected[id]);
            cb.onchange = (e) => {
                const o = orders.find(x => x.id == id);
                if (o) toggleSelect(e, o.id, o.table, o.total);
            };
        });

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
