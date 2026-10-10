@extends('pos.layout')

@section('content')
<div class="screen">
    
    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
            <div>
                <div class="header-name">Sales Insights</div>
                <div class="header-greeting">Management Terminal</div>
            </div>
        </div>
        <a href="{{ route('sales.export', ['range' => $range]) }}" class="btn-secondary btn-sm">
            <i data-lucide="file-text" style="width:14px;height:14px;vertical-align:middle;margin-right:4px"></i> Export PDF
        </a>
    </div>

    {{-- Range tabs --}}
    <div class="cat-tabs">
        @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $key => $label)
        <a href="{{ route('sales.index', ['range' => $key]) }}"
           class="cat-tab {{ $range === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- KPI Cards --}}
    <div class="stats-row stats-3">
        <div class="stat-card">
            <div class="stat-label">{{ $range === 'today' ? "Today's Sales" : 'Total Sales' }}</div>
            <div class="stat-value sm">₱{{ number_format($totalSales, 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Orders</div>
            <div class="stat-value sm">{{ $orderCount }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Avg Ticket</div>
            <div class="stat-value sm">₱{{ number_format($avgTicket, 2) }}</div>
        </div>
    </div>


    {{-- Recent settlements --}}
    <div class="detail-card">
        <div class="detail-card-title">Recent Settlements</div>
        @forelse($recentReceipts as $r)
        <div class="receipt-list-row">
            <div>
                <div class="receipt-tx">TX-{{ str_pad($r->Receipt_ID, 4, '0', STR_PAD_LEFT) }}</div>
                <div class="receipt-tx-sub">{{ $r->order->Table_number ?? '—' }} · {{ \Carbon\Carbon::parse($r->created_at)->format('M d, Y') }} · {{ \Carbon\Carbon::parse($r->created_at)->format('h:i A') }}</div>
            </div>
            <div class="receipt-tx-amt">₱{{ number_format($r->Total_Amount, 2) }}</div>
        </div>
        @empty
        <div class="empty-state" style="padding:16px">No settlements yet.</div>
        @endforelse
    </div>

</div>

@endsection
