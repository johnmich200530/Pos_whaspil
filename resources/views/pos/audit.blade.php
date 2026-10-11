@extends('pos.layout')

@section('content')
<div class="screen" style="max-width:900px">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
            <div class="header-name">Audit Log</div>
        </div>
    </div>

    <div style="padding:14px 0 6px">

        {{-- Filters --}}
        <form method="GET" action="{{ route('audit.index') }}" id="auditFilterForm"
              style="display:flex;flex-direction:column;gap:8px">
            <div class="search-bar" style="margin-bottom:0">
                <input type="text" name="search" id="auditSearch" class="search-input"
                       placeholder="Search audit log…"
                       value="{{ request('search') }}" autocomplete="off">
            </div>
            <div style="display:flex;align-items:center;gap:8px;padding:0 16px;flex-wrap:wrap">
                <span style="font-size:.82rem;color:var(--muted);white-space:nowrap">Filter by type:</span>
                <select name="type" id="auditType" class="form-input"
                        style="width:auto;padding:7px 10px;font-size:.85rem"
                        onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="order"     {{ request('type') === 'order'     ? 'selected' : '' }}>Orders</option>
                    <option value="payment"   {{ request('type') === 'payment'   ? 'selected' : '' }}>Payments</option>
                    <option value="menu"      {{ request('type') === 'menu'      ? 'selected' : '' }}>Menu</option>
                    <option value="inventory" {{ request('type') === 'inventory' ? 'selected' : '' }}>Inventory</option>
                    <option value="employee"  {{ request('type') === 'employee'  ? 'selected' : '' }}>Employees</option>
                    <option value="auth"      {{ request('type') === 'auth'      ? 'selected' : '' }}>Login / Logout</option>
                </select>
                @if(request('search') || request('type'))
                    <a href="{{ route('audit.index') }}" style="font-size:.82rem;color:var(--muted);text-decoration:underline">Clear filters</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div style="padding:10px 16px 24px;overflow-x:auto">
        <table class="audit-table" id="auditTable">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Event Type</th>
                    <th>Change</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $category = explode('.', $log->action)[0] ?? 'other';
                    $typeLabels = [
                        'order'     => 'Orders',
                        'payment'   => 'Payments',
                        'menu'      => 'Menu',
                        'inventory' => 'Inventory',
                        'employee'  => 'Employees',
                        'auth'      => 'Login / Logout',
                    ];
                    $typeLabel = $typeLabels[$category] ?? ucfirst($category);
                @endphp
                <tr class="audit-row" data-type="{{ $category }}"
                    data-search="{{ strtolower(
                        \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') . ' ' .
                        ($log->employee ? $log->employee->FNM . ' ' . $log->employee->LNM : 'system') . ' ' .
                        ($log->employee ? $log->employee->Role : '') . ' ' .
                        $typeLabel . ' ' .
                        $log->description . ' ' .
                        ($log->subject ?? '')
                    ) }}">
                    <td class="audit-time">{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}</td>
                    <td class="audit-user">
                        @if($log->employee)
                            {{ $log->employee->FNM }} {{ $log->employee->LNM }}
                        @else
                            <span style="color:var(--muted)">System</span>
                        @endif
                    </td>
                    <td>
                        @if($log->employee)
                        <span class="badge badge-role">{{ ucfirst($log->employee->Role) }}</span>
                        @else
                        <span style="color:var(--muted);font-size:.8rem">—</span>
                        @endif
                    </td>
                    <td class="audit-type">{{ $typeLabel }}</td>
                    <td class="audit-desc">{{ $log->description }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:32px;color:var(--muted)">No audit logs yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
        <div class="inv-pagination" style="margin-top:16px">
            {{-- Prev --}}
            @if($logs->onFirstPage())
                <span class="inv-page-btn inv-page-btn--disabled">‹</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="inv-page-btn">‹</a>
            @endif

            {{-- Page numbers with ellipsis --}}
            @php
                $current = $logs->currentPage();
                $last    = $logs->lastPage();
                $slots   = [];
                if ($last <= 7) {
                    for ($i = 1; $i <= $last; $i++) $slots[] = $i;
                } else {
                    $slots[] = 1;
                    if ($current > 3) $slots[] = '...';
                    for ($i = max(2, $current - 1); $i <= min($last - 1, $current + 1); $i++) $slots[] = $i;
                    if ($current < $last - 2) $slots[] = '...';
                    $slots[] = $last;
                }
            @endphp
            @foreach($slots as $slot)
                @if($slot === '...')
                    <span class="inv-page-btn inv-page-btn--disabled" style="border:none;background:none">…</span>
                @elseif($slot == $current)
                    <span class="inv-page-btn inv-page-btn--active">{{ $slot }}</span>
                @else
                    <a href="{{ $logs->url($slot) }}" class="inv-page-btn">{{ $slot }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="inv-page-btn">›</a>
            @else
                <span class="inv-page-btn inv-page-btn--disabled">›</span>
            @endif
        </div>
        <div class="inv-page-info">
            Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    // Auto-submit search after user stops typing (400ms debounce)
    let auditSearchTimer;
    document.getElementById('auditSearch').addEventListener('input', function () {
        clearTimeout(auditSearchTimer);
        auditSearchTimer = setTimeout(() => {
            document.getElementById('auditFilterForm').submit();
        }, 400);
    });
</script>
@endpush
@endsection
