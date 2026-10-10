@extends('pos.layout')

@section('content')
<div class="screen">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('orders.active') }}" class="btn-back">‹</a>
            <div class="header-name">Receipt</div>
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
                <span>TX-{{ str_pad($receipt->Receipt_ID, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="receipt-meta-row">
                <span>Date & Time</span>
                <span>{{ \Carbon\Carbon::parse($receipt->created_at)->format('M d, Y h:i A') }}</span>
            </div>
            <div class="receipt-meta-row">
                <span>Table</span>
                <span>{{ $receipt->order->Table_number }}</span>
            </div>
            <div class="receipt-meta-row">
                <span>Cashier</span>
                <span>{{ $receipt->order->employee->FNM ?? '' }} {{ $receipt->order->employee->LNM ?? '' }}</span>
            </div>
            <div class="receipt-meta-row">
                <span>Method</span>
                <span>{{ ucfirst($receipt->payment->Method) }}</span>
            </div>
        </div>

        <div class="receipt-divider">- - - - - - - - - - - - - - -</div>

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

        <div class="receipt-divider">- - - - - - - - - - - - - - -</div>

        <div class="receipt-total-row" style="color:#000;-webkit-text-fill-color:#000">
            <span>TOTAL</span>
            <strong>₱{{ number_format($receipt->Total_Amount, 2) }}</strong>
        </div>

        @if($receipt->payment->Method === 'cash')
        @php $change = $receipt->payment->amount_tendered - $receipt->Total_Amount; @endphp
        <div class="receipt-meta-row" style="padding-top:6px">
            <span>Cash Tendered</span>
            <span>₱{{ number_format($receipt->payment->amount_tendered, 2) }}</span>
        </div>
        <div class="receipt-meta-row" style="font-weight:700">
            <span>Change</span>
            <span>₱{{ number_format(max(0, $change), 2) }}</span>
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
