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

    @if($lowCount > 0)
    <div class="alert-banner">
        {{ $lowCount }} item(s) are low or out of stock
    </div>
    @endif

    <form method="GET" action="{{ route('inventory.index') }}" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="Search stock…" value="{{ $search ?? '' }}">
        <button type="submit" class="btn-secondary btn-sm">Search</button>
    </form>

    <div class="inventory-list" style="margin-top:8px">
        @forelse($inventories as $inv)
        <div class="inv-row">
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
</script>
@endpush
@endsection
