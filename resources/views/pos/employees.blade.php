@extends('pos.layout')

@section('content')
<div class="screen">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
            <div class="header-name">Employees</div>
        </div>
        <button class="btn-primary btn-sm" onclick="openModal('addEmpModal')">+ Add Staff</button>
    </div>

    {{-- Search & Filter --}}
    <div class="search-filter-bar">
        <input type="text" id="empSearch" class="form-input" placeholder="Search name…" oninput="filterEmployees()">
        <select id="empRoleFilter" class="form-input" onchange="filterEmployees()">
            <option value="">All Roles</option>
            <option value="manager">Manager</option>
            <option value="cashier">Cashier</option>
            <option value="waiter">Waiter</option>
            <option value="chef">Chef</option>
        </select>
    </div>

    <div class="inventory-list" style="margin-top:4px" id="empList">
        @forelse($employees as $emp)
        <div class="inv-row" data-name="{{ strtolower($emp->FNM.' '.$emp->LNM) }}" data-role="{{ $emp->Role }}">
            <div class="inv-info">
                <div class="inv-name">{{ $emp->FNM }} {{ $emp->LNM }}</div>
                <div class="inv-unit">{{ $emp->Username }}</div>
            </div>
            <div class="inv-right">
                <span class="badge badge-role">{{ ucfirst($emp->Role) }}</span>
                <span class="inv-unit">{{ $emp->orders_count }} orders</span>
            </div>
            <div class="inv-actions">
                <button class="btn-icon"
                    onclick="openEditEmp(
                        {{ $emp->Employee_ID }},
                        '{{ addslashes($emp->FNM) }}',
                        '{{ addslashes($emp->LNM) }}',
                        '{{ $emp->Role }}',
                        '{{ addslashes($emp->Username) }}'
                    )"><i data-lucide="pencil"></i></button>
                <form method="POST" action="{{ route('employees.destroy', $emp) }}" onsubmit="return confirm('Remove employee?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-icon"><i data-lucide="trash-2"></i></button>
                </form>
            </div>
        </div>
        @empty
        <div class="empty-state">No employees found.</div>
        @endforelse
    </div>

</div>

{{-- Add Modal --}}
<div class="modal-overlay" id="addEmpModal" style="display:none">
    <div class="modal">
        <div class="modal-title">Add Employee</div>
        <form method="POST" action="{{ route('employees.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-input" required>
                    <option value="manager">Manager</option>
                    <option value="cashier">Cashier</option>
                    <option value="waiter">Waiter</option>
                    <option value="chef">Chef</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" placeholder="e.g. juan.delacruz" autocomplete="off" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Min. 6 characters" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn-primary">Add Employee</button>
        </form>
        <button type="button" class="btn-secondary" onclick="closeModal('addEmpModal')">Cancel</button>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal-overlay" id="editEmpModal" style="display:none">
    <div class="modal">
        <div class="modal-title">Edit Employee</div>
        <form method="POST" id="editEmpForm">
            @csrf @method('PATCH')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" id="eeFName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" id="eeLName" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" id="eeRole" class="form-input" required>
                    <option value="manager">Manager</option>
                    <option value="cashier">Cashier</option>
                    <option value="waiter">Waiter</option>
                    <option value="chef">Chef</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="eeUsername" class="form-input" autocomplete="off" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password <span style="color:var(--muted);font-size:.78rem">(leave blank to keep current)</span></label>
                <input type="password" name="password" class="form-input" placeholder="New password…" autocomplete="new-password">
            </div>
            <button type="submit" class="btn-primary">Update</button>
        </form>
        <button type="button" class="btn-secondary" onclick="closeModal('editEmpModal')">Cancel</button>
    </div>
</div>

@push('scripts')
<script>
    function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openEditEmp(id, fn, ln, role, username) {
        document.getElementById('editEmpForm').action = '/employees/' + id;
        document.getElementById('eeFName').value    = fn;
        document.getElementById('eeLName').value    = ln;
        document.getElementById('eeRole').value     = role;
        document.getElementById('eeUsername').value = username;
        openModal('editEmpModal');
    }

    function filterEmployees() {
        const q    = document.getElementById('empSearch').value.toLowerCase();
        const role = document.getElementById('empRoleFilter').value;
        document.querySelectorAll('#empList .inv-row').forEach(row => {
            const nameMatch = row.dataset.name.includes(q);
            const roleMatch = !role || row.dataset.role === role;
            row.style.display = (nameMatch && roleMatch) ? '' : 'none';
        });
    }
</script>
@endpush
@endsection
