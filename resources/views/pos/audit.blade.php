@extends('pos.layout')

@section('content')
<div class="screen" style="max-width:900px">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
            <div class="header-name">Audit Log</div>
        </div>
    </div>

    <div style="padding:14px 16px 6px">
        <p style="font-size:.85rem;color:var(--muted);margin-bottom:14px">
            A history of all actions performed in the system — who did what and when.
        </p>

        {{-- Filters --}}
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:8px;background:var(--dark3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px;min-width:200px">
                <span style="color:var(--muted)"></span>
                <input type="text" id="auditSearch" placeholder="Filter by keyword…"
                       class="form-input" style="background:none;border:none;padding:0;font-size:.88rem;box-shadow:none"
                       oninput="filterAudit()">
            </div>
            <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;color:var(--muted)">
                Filter by type:
                <select id="auditType" class="form-input" style="width:auto;padding:7px 10px;font-size:.85rem" onchange="filterAudit()">
                    <option value="">All</option>
                    <option value="order">Orders</option>
                    <option value="payment">Payments</option>
                    <option value="menu">Menu</option>
                    <option value="inventory">Inventory</option>
                    <option value="employee">Employees</option>
                    <option value="auth">Login / Logout</option>
                </select>
            </div>
        </div>
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
        <div style="margin-top:16px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:.85rem">
            {{-- Previous --}}
            @if($logs->onFirstPage())
                <span style="padding:5px 10px;border-radius:6px;background:var(--dark3);color:var(--muted);cursor:default">‹ Prev</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" style="padding:5px 10px;border-radius:6px;background:var(--dark3);color:var(--text);text-decoration:none">‹ Prev</a>
            @endif

            {{-- Page numbers --}}
            @foreach($logs->getUrlRange(1, $logs->lastPage()) as $page => $url)
                @if($page == $logs->currentPage())
                    <span style="padding:5px 10px;border-radius:6px;background:var(--green);color:#fff;font-weight:600">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" style="padding:5px 10px;border-radius:6px;background:var(--dark3);color:var(--text);text-decoration:none">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" style="padding:5px 10px;border-radius:6px;background:var(--dark3);color:var(--text);text-decoration:none">Next ›</a>
            @else
                <span style="padding:5px 10px;border-radius:6px;background:var(--dark3);color:var(--muted);cursor:default">Next ›</span>
            @endif

            <span style="color:var(--muted);margin-left:6px">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} results
            </span>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    function filterAudit() {
        const keyword = document.getElementById('auditSearch').value.toLowerCase();
        const type    = document.getElementById('auditType').value.toLowerCase();

        document.querySelectorAll('.audit-row').forEach(row => {
            const matchKeyword = !keyword || row.dataset.search.includes(keyword);
            const matchType    = !type    || row.dataset.type === type;
            row.style.display  = (matchKeyword && matchType) ? '' : 'none';
        });
    }
</script>
@endpush
@endsection
