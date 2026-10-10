@extends('pos.layout')

@section('content')
@php
    $showPayment = !in_array($order->Status, ['paid','cancelled'])
                  && !$order->payment
                  && in_array(session('pos_employee')->Role, ['cashier','manager']);
@endphp

<div class="new-order-layout" style="align-items:flex-start">

    <div class="new-order-left" style="max-width:560px">

        <div class="page-header">
            <div class="header-left">
                <a href="{{ route('orders.active') }}" class="btn-back">‹</a>
                <div>
                    <div class="header-name">{{ $order->Table_number }}</div>
                    <span class="status-badge status-{{ $order->Status }}">{{ ucfirst($order->Status) }}</span>
                </div>
            </div>
            <div class="order-meta">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y h:i A') }}</div>
        </div>

        {{-- Items --}}
        <div class="detail-card">
            <div class="detail-card-title">Order Items</div>
            @foreach($order->orderItems as $oi)
            <div class="detail-row" id="row-{{ $oi->OrderItem_ID }}">
                <div style="flex:1">
                    <div class="detail-item-name">{{ $oi->menu->Name }}</div>
                    @if(!in_array($order->Status, ['paid','cancelled']) && in_array(session('pos_employee')->Role, ['waiter','manager']))
                    <div style="display:flex;align-items:center;gap:10px;margin-top:6px">
                        <div class="qty-ctrl">
                            <button type="button" onclick="changeItemQty({{ $oi->OrderItem_ID }}, -1, {{ $oi->menu->Price }})">−</button>
                            <span id="qty-{{ $oi->OrderItem_ID }}">{{ $oi->Quantity }}</span>
                            <button type="button" onclick="changeItemQty({{ $oi->OrderItem_ID }}, 1, {{ $oi->menu->Price }})">+</button>
                        </div>
                        <button type="button" onclick="removeItem({{ $oi->OrderItem_ID }})" class="btn-icon">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                    @else
                    <div class="detail-item-qty">Qty: {{ $oi->Quantity }}</div>
                    @endif
                </div>
                <div class="detail-item-price" id="sub-{{ $oi->OrderItem_ID }}">
                    ₱{{ number_format($oi->Subtotal, 2) }}
                </div>
            </div>
            @endforeach
            <div class="detail-total-row">
                <span>Total</span>
                <strong id="orderTotal">₱{{ number_format($order->Total_Amount, 2) }}</strong>
            </div>
        </div>

        {{-- Status update --}}
        @if(!in_array($order->Status, ['paid','cancelled']))
        <div class="detail-card">
            <div class="detail-card-title">Update Status</div>
            <div class="status-btn-row">
                @foreach(['pending','preparing','served'] as $s)
                <form method="POST" action="{{ route('orders.status', $order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $s }}">
                    <button type="submit" class="status-upd-btn {{ $order->Status === $s ? 'active-status' : '' }}">
                        {{ ucfirst($s) }}
                    </button>
                </form>
                @endforeach
                {{-- Cancel — separate with confirmation + redirect to active orders --}}
                <form method="POST" action="{{ route('orders.status', $order) }}"
                      onsubmit="return confirm('Cancel this order? This cannot be undone.')">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="status-upd-btn"
                            style="border-color:rgba(231,76,60,.4);color:#e74c3c">
                        Cancel Order
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Paid confirmation --}}
        @if($order->receipt)
        <div class="detail-card" style="text-align:center">
            <i data-lucide="check-circle" style="width:40px;height:40px;color:var(--green);margin-bottom:8px"></i>
            <div style="font-weight:600;margin:4px 0">Payment Received</div>
            <div style="color:#777;font-size:.85rem">via {{ ucfirst($order->payment->Method ?? '') }}</div>
            <a href="{{ route('receipt.show', $order->receipt) }}" class="btn-secondary" style="margin-top:12px;display:inline-block">
                <i data-lucide="receipt" style="width:14px;height:14px;vertical-align:middle"></i> View Receipt
            </a>
        </div>
        @endif

    </div>

    {{-- RIGHT: payment panel --}}
    @if($showPayment)
    <div class="new-order-right" style="padding-top:82px">
        <div class="order-panel-sticky">
            <div class="order-panel-header">
                <span>Process Payment</span>
            </div>

            <form method="POST" action="{{ route('payments.store') }}" id="paymentForm">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->Order_ID }}">

                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <div class="method-btns">
                        <input type="radio" name="method" id="mCash" value="cash" checked class="sr-only">
                        <label for="mCash" class="method-btn selected" onclick="switchMethod('cash')">Cash</label>
                        <input type="radio" name="method" id="mGcash" value="gcash" class="sr-only">
                        <label for="mGcash" class="method-btn" onclick="switchMethod('gcash')">GCash</label>
                    </div>
                </div>

                <div id="cashFields">
                    <div class="form-group">
                        <label class="form-label">Amount Tendered</label>
                        <input type="number" name="amount_tendered" id="tendered" class="form-input"
                               placeholder="₱0.00" min="{{ $order->Total_Amount }}" step="0.01"
                               autocomplete="off"
                               oninput="showChange({{ $order->Total_Amount }})">
                    </div>
                    <div class="modal-change-row" id="changeDisplay" style="display:none">
                        <span>Change</span>
                        <strong id="changeOut" class="text-accent"></strong>
                    </div>
                </div>

                <div id="gcashFields" style="display:none">
                    @if($gcashQr)
                    <div class="gcash-qr-wrap">
                        <img src="{{ asset('storage/'.$gcashQr) }}" alt="GCash QR" class="gcash-qr-img"
                             title="Click to change QR"
                             onclick="document.getElementById('gcashQrInput').click()">
                        <div class="gcash-qr-label">Scan to pay ₱{{ number_format($order->Total_Amount, 2) }}</div>
                    </div>
                    @else
                    <div class="gcash-qr-wrap">
                        <button type="button" class="gcash-qr-empty" onclick="document.getElementById('gcashQrInput').click()">
                            <i data-lucide="qr-code" style="width:36px;height:36px;color:var(--muted)"></i>
                            <span>Click to add GCash QR</span>
                        </button>
                    </div>
                    @endif
                </div>

                <button type="submit" class="btn-primary" id="payBtn">
                    Confirm Payment
                </button>
            </form>

            <form method="POST" action="{{ route('settings.gcash-qr') }}" enctype="multipart/form-data" id="gcashQrForm" hidden>
                @csrf
                <input type="file" id="gcashQrInput" name="gcash_qr" accept="image/*" required
                       onchange="if (this.files[0]) this.form.submit()">
            </form>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
    function switchMethod(method) {
        document.querySelectorAll('.method-btn').forEach(l => l.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        const cashFields = document.getElementById('cashFields');
        const gcashFields = document.getElementById('gcashFields');
        const tendered   = document.getElementById('tendered');
        if (method === 'cash') {
            cashFields.style.display = 'block';
            gcashFields.style.display = 'none';
            tendered.value = '';
            document.getElementById('changeDisplay').style.display = 'none';
            tendered.removeAttribute('disabled');
            tendered.setAttribute('required','required');
        } else {
            cashFields.style.display = 'none';
            gcashFields.style.display = 'block';
            tendered.value = {{ $order->Total_Amount }};
            tendered.setAttribute('disabled','disabled');
            tendered.removeAttribute('required');
        }
    }

    document.getElementById('paymentForm')?.addEventListener('submit', () => {
        document.getElementById('tendered').removeAttribute('disabled');
    });

    const token = document.querySelector('meta[name="csrf-token"]').content;

    function changeItemQty(id, delta, price) {
        const qtyEl = document.getElementById('qty-' + id);
        let qty = parseInt(qtyEl.textContent) + delta;
        if (qty < 1) { removeItem(id, false); return; }
        fetch('/order-items/' + id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ quantity: qty, _method: 'PATCH' }),
        }).then(r => {
            if (!r.ok) throw new Error();
            qtyEl.textContent = qty;
            document.getElementById('sub-' + id).textContent = '₱' + (price * qty).toFixed(2);
            recalcTotal();
        }).catch(() => alert('Failed to update quantity.'));
    }

    function removeItem(id, confirm_prompt = true) {
        if (confirm_prompt && !confirm('Remove this item?')) return;
        fetch('/order-items/' + id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ _method: 'DELETE' }),
        }).then(r => r.json()).then(data => {
            if (!data.success) throw new Error();
            if (data.order_deleted) { window.location.href = '{{ route("orders.active") }}'; return; }
            document.getElementById('row-' + id)?.remove();
            recalcTotal();
        }).catch(() => alert('Failed to remove item.'));
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('[id^="sub-"]').forEach(el => {
            total += parseFloat(el.textContent.replace('₱','').replace(',','')) || 0;
        });
        const el = document.getElementById('orderTotal');
        if (el) el.textContent = '₱' + total.toFixed(2);
    }

    function showChange(total) {
        const t  = parseFloat(document.getElementById('tendered').value) || 0;
        const el = document.getElementById('changeDisplay');
        if (t > 0) {
            el.style.display = 'flex';
            document.getElementById('changeOut').textContent = '₱' + Math.max(0, t - total).toFixed(2);
        } else { el.style.display = 'none'; }
    }

    // Auto-reload if status changes from another device
    const orderId   = {{ $order->Order_ID }};
    const initStatus = '{{ $order->Status }}';
    async function pollOrderStatus() {
        try {
            const res  = await fetch('/orders/' + orderId + '/status-json', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();
            if (data.status === 'cancelled') {
                window.location.href = '{{ route("orders.active") }}';
                return;
            }
            if (data.status && data.status !== initStatus) {
                window.location.reload();
            }
        } catch {}
    }
    setInterval(pollOrderStatus, 6000);
</script>
@endpush
@endsection
