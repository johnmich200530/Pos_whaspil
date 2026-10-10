@extends('pos.layout')

@section('content')
<div class="new-order-layout">

    {{-- LEFT: menu + table selection --}}
    <div class="new-order-left">

        <div class="page-header">
            <div class="header-left">
                <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
                <div><div class="header-name">New Order</div></div>
            </div>
            <input type="text" id="menuSearch" class="search-input" placeholder="Search menu…" oninput="filterMenu(this.value)">
        </div>

        {{-- Order type toggle --}}
        <div class="order-type-row">
            <button type="button" class="order-type-btn order-type-btn--active" id="btnDineIn" onclick="setOrderType('dinein')">
                Dine-in
            </button>
            <button type="button" class="order-type-btn" id="btnTakeout" onclick="setOrderType('takeout')">
                Takeout
            </button>
        </div>

        {{-- Dine-in: Table grid --}}
        <div id="dineInSection">
            <div class="table-select-row">
                <label class="form-label" style="margin-bottom:8px"><b>Select Table</b></label>
                <div class="table-grid" id="tableGrid">
                    @foreach(range(1, 20) as $t)
                    @php $key = 'Table '.$t; $active = $activeTables->get($key); @endphp
                    <button type="button"
                        class="table-tile {{ $active ? 'table-tile--occupied' : 'table-tile--free' }}"
                        data-table="{{ $key }}" data-status="{{ $active->status ?? '' }}"
                        onclick="selectTable(this)">
                        <span class="table-tile-num">{{ $t }}</span>
                        <span class="table-tile-status">{{ $active ? 'Occupied' : 'Free' }}</span>
                    </button>
                    @endforeach
                </div>
                <div id="tableSelectedLabel" style="display:none;margin-top:8px;font-size:.85rem;font-weight:700;color:var(--green)"></div>
                <div id="tableReminder" style="display:none;margin-top:8px;font-size:.85rem;font-weight:700;color:#e74c3c;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.3);border-radius:8px;padding:8px 12px;">
                     Please select a table before sending to kitchen.
                </div>
            </div>
        </div>

        {{-- Takeout: auto label --}}
        <div id="takeoutSection" style="display:none">
            <div class="table-select-row">
                <div class="takeout-badge">
                    <span id="takeoutLabel">{{ $nextTakeout }}</span>
                    <span style="font-size:.78rem;color:var(--muted);font-weight:500;margin-left:6px">— assigned automatically</span>
                </div>
            </div>
        </div>

        {{-- Category tabs --}}
        <div class="cat-tabs" id="catTabs">
            <button class="cat-tab active" onclick="filterCat('all', this)">All</button>
            @foreach($categories as $cat)
                <button class="cat-tab" onclick="filterCat('{{ $cat }}', this)">{{ ucfirst($cat) }}</button>
            @endforeach
        </div>

        {{-- Menu grid --}}
        <div class="menu-grid" id="menuGrid">
            @foreach($menus as $cat => $items)
                @foreach($items as $item)
                @php $notAvailable = !$item->Availability; @endphp
                <div class="menu-card {{ $notAvailable ? 'menu-card--out' : '' }}"
                     data-cat="{{ $cat }}"
                     data-name="{{ strtolower($item->Name) }}"
                >
                    <div class="menu-card-img">
                        @if($item->Image)
                            <img src="{{ asset('storage/'.$item->Image) }}"
                                 alt="{{ $item->Name }}"
                                 style="width:100%;height:100%;object-fit:cover;display:block;">
                        @else
                            <div class="menu-card-emoji">{{ str_contains(strtolower($item->Category), 'drink') ? '🥤' : (str_contains(strtolower($item->Category), 'dessert') ? '🍮' : '🍽️') }}</div>
                        @endif
                        @if($notAvailable)
                            <div class="menu-card-out-badge">NOT AVAILABLE</div>
                        @endif
                    </div>
                    <div class="menu-card-body">
                        <div class="menu-card-name">{{ $item->Name }}</div>
                        <div class="menu-card-price">₱{{ number_format($item->Price, 2) }}</div>
                    </div>
                    @if(!$notAvailable)
                    <button type="button" class="menu-card-add" aria-label="Add {{ $item->Name }}"
                            onclick="addItem({{ $item->Menu_ID }}, '{{ addslashes($item->Name) }}', {{ $item->Price }})">+</button>
                    @else
                    <button type="button" class="menu-card-add" disabled style="opacity:.35;cursor:not-allowed;pointer-events:none">+</button>
                    @endif
                </div>
                @endforeach
            @endforeach
        </div>

    </div>{{-- end left --}}

    {{-- RIGHT: sticky order summary --}}
    <div class="new-order-right">
        <div class="order-panel-sticky">
            <div class="order-panel-header">
                <span>Order Summary</span>
                <button type="button" class="btn-clear" onclick="clearOrder()">CLEAR ALL</button>
            </div>
            <div class="order-items" id="orderItems">
                <div class="empty-order">No items added yet</div>
            </div>
            <div class="order-total">
                Grand Total <strong id="grandTotal">₱0.00</strong>
            </div>
            <button type="button" class="btn-primary" id="sendBtn" onclick="openPayModal()" disabled>
                Send to Kitchen
            </button>
        </div>
    </div>

</div>{{-- end layout --}}

{{-- Modal --}}
<div class="modal-overlay" id="payModal" style="display:none">
    <div class="modal">
        <div class="modal-title">Send to Kitchen</div>
        <div class="modal-sub" id="modalTableLabel">—</div>
        <div class="modal-total-row">
            <span>Order Total</span>
            <strong id="modalTotal">₱0.00</strong>
        </div>
        <div class="modal-order-note">
            Payment will be collected after the customer is done eating.
        </div>
        <button type="button" class="btn-primary" id="confirmOrderBtn" onclick="submitOrder()">
            Confirm &amp; Send to Kitchen
        </button>
        <button type="button" class="btn-secondary" onclick="closePayModal()">Cancel</button>
    </div>
</div>

<div id="orderToast" class="toast toast-success" style="display:none">Order sent to kitchen!</div>

@push('scripts')
<script>
    let cart       = {};
    let tableNo    = '';
    let grandTotal = 0;
    let orderType  = 'dinein';
    const nextTakeout = '{{ $nextTakeout }}';

    function setOrderType(type) {
        orderType = type;
        document.getElementById('btnDineIn').classList.toggle('order-type-btn--active', type === 'dinein');
        document.getElementById('btnTakeout').classList.toggle('order-type-btn--active', type === 'takeout');
        document.getElementById('dineInSection').style.display  = type === 'dinein'  ? 'block' : 'none';
        document.getElementById('takeoutSection').style.display = type === 'takeout' ? 'block' : 'none';
        if (type === 'takeout') {
            tableNo = nextTakeout;
        } else {
            tableNo = '';
            document.querySelectorAll('.table-tile').forEach(b => b.classList.remove('table-tile--selected'));
            document.getElementById('tableSelectedLabel').style.display = 'none';
        }
    }

    function selectTable(btn) {
        document.querySelectorAll('.table-tile').forEach(b => b.classList.remove('table-tile--selected'));
        btn.classList.add('table-tile--selected');
        tableNo = btn.dataset.table;
        const label = document.getElementById('tableSelectedLabel');
        label.textContent = tableNo + ' selected';
        label.style.display = 'block';
        document.getElementById('tableReminder').style.display = 'none';
    }

    function filterCat(cat, el) {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        document.querySelectorAll('.menu-card').forEach(c => {
            c.style.display = (cat === 'all' || c.dataset.cat === cat) ? '' : 'none';
        });
    }

    function filterMenu(q) {
        q = q.toLowerCase();
        document.querySelectorAll('.menu-card').forEach(c => {
            c.style.display = c.dataset.name.includes(q) ? '' : 'none';
        });
    }

    function addItem(id, name, price) {
        if (!cart[id]) cart[id] = { name, price, qty: 0 };
        cart[id].qty++;
        renderCart();
    }

    function changeQty(id, delta) {
        if (!cart[id]) return;
        cart[id].qty += delta;
        if (cart[id].qty <= 0) delete cart[id];
        renderCart();
    }

    function clearOrder() { cart = {}; renderCart(); }

    function renderCart() {
        const container = document.getElementById('orderItems');
        const sendBtn   = document.getElementById('sendBtn');
        const keys      = Object.keys(cart);
        grandTotal = 0;
        container.innerHTML = '';
        if (keys.length === 0) {
            container.appendChild(Object.assign(document.createElement('div'), {
                className: 'empty-order', textContent: 'No items added yet'
            }));
            sendBtn.disabled = true;
        } else {
            keys.forEach(id => {
                const item = cart[id];
                const sub  = item.price * item.qty;
                grandTotal += sub;
                const row  = document.createElement('div');
                row.className = 'cart-row';
                row.innerHTML = `
                    <div class="cart-name">${item.qty}x ${item.name}</div>
                    <div class="cart-right">
                        <span class="cart-price">₱${sub.toFixed(2)}</span>
                        <div class="qty-ctrl">
                            <button type="button" onclick="changeQty(${id},-1)">−</button>
                            <span>${item.qty}</span>
                            <button type="button" onclick="changeQty(${id},1)">+</button>
                        </div>
                    </div>`;
                container.appendChild(row);
            });
            sendBtn.disabled = false;
        }
        document.getElementById('grandTotal').textContent = '₱' + grandTotal.toFixed(2);
    }

    function openPayModal() {
        if (orderType === 'dinein' && !tableNo) {
            const reminder = document.getElementById('tableReminder');
            reminder.style.display = 'block';
            document.getElementById('tableGrid').scrollIntoView({ behavior: 'smooth' });
            setTimeout(() => reminder.style.display = 'none', 4000);
            return;
        }
        document.getElementById('tableReminder').style.display = 'none';
        if (Object.keys(cart).length === 0) { alert('Cart is empty.'); return; }
        document.getElementById('modalTableLabel').textContent = tableNo;
        document.getElementById('modalTotal').textContent = '₱' + grandTotal.toFixed(2);
        document.getElementById('payModal').style.display = 'flex';
    }

    function closePayModal() { document.getElementById('payModal').style.display = 'none'; }

    function submitOrder() {
        const btn = document.getElementById('confirmOrderBtn');
        btn.disabled = true;
        btn.textContent = 'Sending…';
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('table_number', tableNo);
        let i = 0;
        Object.keys(cart).forEach(id => {
            formData.append(`items[${i}][menu_id]`, id);
            formData.append(`items[${i}][quantity]`, cart[id].qty);
            i++;
        });
        fetch('{{ route("orders.store") }}', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(res => {
            if (!res.ok) throw new Error('Server error');
            closePayModal();
            if (orderType === 'dinein') {
                const tile = document.querySelector(`.table-tile[data-table="${tableNo}"]`);
                if (tile) {
                    tile.classList.remove('table-tile--free','table-tile--selected');
                    tile.classList.add('table-tile--occupied');
                    tile.querySelector('.table-tile-status').textContent = 'Occupied';
                }
            }
            cart = {}; tableNo = orderType === 'takeout' ? nextTakeout : ''; grandTotal = 0;
            renderCart();
            if (orderType === 'dinein') {
                document.querySelectorAll('.table-tile').forEach(b => b.classList.remove('table-tile--selected'));
                document.getElementById('tableSelectedLabel').style.display = 'none';
            }
            document.getElementById('menuSearch').value = '';
            filterMenu('');
            const toast = document.getElementById('orderToast');
            toast.style.display = 'block';
            toast.classList.remove('toast-hide');
            setTimeout(() => { toast.classList.add('toast-hide'); setTimeout(() => toast.style.display='none', 400); }, 3000);
        })
        .catch(() => alert('Something went wrong. Please try again.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = '🍳 Confirm &amp; Send to Kitchen'; });
    }
</script>
@endpush
@endsection
