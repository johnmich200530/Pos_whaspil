@extends('pos.layout')

@section('content')
<div class="screen">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
            <div>
                <div class="header-name">Inventory</div>
                <div class="header-greeting">Stock Management</div>
            </div>
        </div>
        <button class="btn-primary btn-sm" onclick="openModal('addModal')">+ Add Stock</button>
    </div>

    @if($outCount > 0)
    <div class="alert-banner" style="background:rgba(231,76,60,.12);border-color:rgba(231,76,60,.4);color:#e74c3c">
        <strong>{{ $outCount }} item(s) are out of stock</strong>
    </div>
    @endif
    @if($lowCount > 0)
    <div class="alert-banner">
        {{ $lowCount }} item(s) are running low on stock
    </div>
    @endif

    <form method="GET" action="{{ route('inventory.index') }}" class="search-bar">
        <input type="text" id="invSearch" name="search" class="search-input" placeholder="Search stock…"
               value="{{ $search ?? '' }}" oninput="applyInvFilters()" autocomplete="off">
    </form>

    {{-- Status filter chips --}}
    <div class="cat-tabs">
        <button class="cat-tab active" data-status="all"    onclick="filterInvStatus('all', this)">All</button>
        <button class="cat-tab"        data-status="in_stock"    onclick="filterInvStatus('in_stock', this)">In Stock</button>
        <button class="cat-tab"        data-status="low_stock"   onclick="filterInvStatus('low_stock', this)">Low Stock</button>
        <button class="cat-tab"        data-status="out_of_stock" onclick="filterInvStatus('out_of_stock', this)">Out of Stock</button>
    </div>

    <div class="inventory-list" style="margin-top:8px">
        @forelse($inventories as $inv)
        <div class="inv-row" data-name="{{ strtolower($inv->Name) }}" data-status="{{ $inv->Status }}">
            <div class="inv-info">
                <div class="inv-name">{{ $inv->Name }}</div>
                <div class="inv-unit">{{ $inv->Unit }}</div>
            </div>
            <div class="inv-right">
                <span class="inv-qty">{{ $inv->Quantity }} {{ $inv->Unit }}</span>
                <span class="stock-badge stock-{{ $inv->Status }}">
                    {{ ucfirst(str_replace('_', ' ', $inv->Status)) }}
                </span>
            </div>
            <div class="inv-actions">
                <button class="btn-icon"
                    onclick="openEditModal(
                        {{ $inv->Inventory_ID }},
                        '{{ addslashes($inv->Name) }}',
                        {{ $inv->Quantity }},
                        '{{ addslashes($inv->Unit) }}'
                    )"><i data-lucide="pencil"></i></button>
                <form method="POST" action="{{ route('inventory.destroy', $inv) }}"
                      onsubmit="return confirm('Remove {{ addslashes($inv->Name) }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-icon"><i data-lucide="trash-2"></i></button>
                </form>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <div>No inventory items found.</div>
        </div>
        @endforelse
    </div>

</div>

{{-- Add Modal --}}
<div class="modal-overlay" id="addModal" style="display:none">
    <div class="modal modal--top">
        <div class="modal-title">Add Stock Item</div>
        <form method="POST" action="{{ route('inventory.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" name="name" class="form-input" placeholder="e.g. Jasmine Rice" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-input" placeholder="0" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-input" required>
                        <option value="" disabled selected>Select…</option>
                        <optgroup label="Count">
                            <option value="pcs">pcs</option>
                            <option value="bottles">bottles</option>
                            <option value="cans">cans</option>
                            <option value="packs">packs</option>
                            <option value="boxes">boxes</option>
                        </optgroup>
                        <optgroup label="Weight">
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="lbs">lbs</option>
                        </optgroup>
                        <optgroup label="Volume">
                            <option value="L">L</option>
                            <option value="mL">mL</option>
                        </optgroup>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary">Add Item</button>
        </form>
        <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal-overlay" id="editModal" style="display:none">
    <div class="modal modal--top">
        <div class="modal-title">Update Stock</div>
        <form method="POST" id="editForm" action="">
            @csrf @method('PATCH')
            <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" name="name" id="editName" class="form-input" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" id="editQty" class="form-input" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select name="unit" id="editUnit" class="form-input" required>
                        <option value="" disabled>Select…</option>
                        <optgroup label="Count">
                            <option value="pcs">pcs</option>
                            <option value="bottles">bottles</option>
                            <option value="cans">cans</option>
                            <option value="packs">packs</option>
                            <option value="boxes">boxes</option>
                        </optgroup>
                        <optgroup label="Weight">
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="lbs">lbs</option>
                        </optgroup>
                        <optgroup label="Volume">
                            <option value="L">L</option>
                            <option value="mL">mL</option>
                        </optgroup>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary">Update</button>
        </form>
        <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
    </div>
</div>

@push('scripts')
<script>
    function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openEditModal(id, name, qty, unit) {
        document.getElementById('editForm').action = '/inventory/' + id;
        document.getElementById('editName').value  = name;
        document.getElementById('editQty').value   = qty;
        document.getElementById('editUnit').value  = unit;
        openModal('editModal');
    }

    let activeInvStatus = 'all';

    function filterInvStatus(status, el) {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        activeInvStatus = status;
        applyInvFilters();
    }

    function applyInvFilters() {
        const q = document.getElementById('invSearch').value.toLowerCase();
        document.querySelectorAll('.inventory-list .inv-row').forEach(row => {
            const nameMatch   = row.dataset.name.includes(q);
            const statusMatch = activeInvStatus === 'all' || row.dataset.status === activeInvStatus;
            row.style.display = (nameMatch && statusMatch) ? '' : 'none';
        });
    }
</script>
@endpush
@endsection
