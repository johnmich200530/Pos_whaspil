@extends('pos.layout')

@section('content')
<div class="screen">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('orders.active') }}" class="btn-back">‹</a>
            <div class="header-name">Combined Receipt</div>
        </div>
        <button onclick="window.print()" class="btn-secondary btn-sm">
            <i data-lucide="printer" style="width:14px;height:14px;vertical-align:middle;margin-right:4px"></i> Print
        </button>
    </div>

    <div class="receipt-card" id="receiptPrint" style="color:#000">

        <div class="receipt-header">
            <div class="receipt-brand" style="color:#000;-webkit-text-fill-color:#000">Whaspil Restaurant</div>
            <div class="receipt-loc" style="color:#000">Davao City</div>
            <div class="receipt-divider">- - - - - - - - - - - - - - -</div>
        </div>

        <div class="receipt-meta">
            <div class="receipt-meta-row">
                <span>Receipt #</span>
                <span>TX-{{ $receipts->pluck('Receipt_ID')->map(fn($id) => str_pad($id, 4, '0', STR_PAD_LEFT))->implode(' / TX-') }}</span>
            </div>
            <div class="receipt-meta-row">
                <span>Date & Time</span>
                <span>{{ \Carbon\Carbon::now()->format('M d, Y h:i A') }}</span>
            </div>
            <div class="receipt-meta-row">
                <span>Tables</span>
                <span>{{ $receipts->map(fn($r) => $r->order->Table_number)->unique()->implode(', ') }}</span>
            </div>
            <div class="receipt-meta-row">
                <span>Cashier</span>
                <span>{{ $receipts->first()->order->employee->FNM ?? '' }} {{ $receipts->first()->order->employee->LNM ?? '' }}</span>
            </div>
            <div class="receipt-meta-row">
                <span>Method</span>
                <span>{{ ucfirst($method) }}</span>
            </div>
        </div>

        <div class="receipt-divider">- - - - - - - - - - - - - - -</div>

        {{-- Items grouped by order --}}
        @foreach($receipts as $receipt)
        <div style="margin-bottom:8px">
            <div style="font-weight:700;font-size:.82rem;color:#555;margin-bottom:4px">
                {{ $receipt->order->Table_number }}
            </div>
            <div class="receipt-items">
                @foreach($receipt->order->orderItems as $oi)
                <div class="receipt-item-row">
                    <div>
                        <div class="receipt-item-name">{{ $oi->menu->Name }}</div>
                        <div class="receipt-item-qty">{{ $oi->Quantity }} × ₱{{ number_format($oi->menu->Price, 2) }}</div>
                    </div>
                    <div class="receipt-item-sub" style="color:#000">₱{{ number_format($oi->Subtotal, 2) }}</div>
                </div>
                @endforeach
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:4px 0;border-top:1px dashed #ccc;margin-top:4px">
                <span>Subtotal</span>
                <span>₱{{ number_format($receipt->Total_Amount, 2) }}</span>
            </div>
        </div>
        @endforeach

        <div class="receipt-divider">- - - - - - - - - - - - - - -</div>

        <div class="receipt-total-row" style="color:#000;-webkit-text-fill-color:#000">
            <span>COMBINED TOTAL</span>
            <strong>₱{{ number_format($combinedTotal, 2) }}</strong>
        </div>

        @if($method === 'cash')
        <div class="receipt-meta-row" style="padding-top:6px">
            <span>Cash Tendered</span>
            <span>₱{{ number_format($amountTendered, 2) }}</span>
        </div>
        <div class="receipt-meta-row" style="font-weight:700">
            <span>Change</span>
            <span>₱{{ number_format($change, 2) }}</span>
        </div>
        @endif

        <div class="receipt-footer">
            <div>Thank you for dining with us!</div>
        </div>
    </div>

    <a href="{{ route('orders.create') }}" class="btn-primary"
       style="margin:16px auto;display:block;max-width:400px;text-align:center">
        + New Order
    </a>

</div>
@endsection
