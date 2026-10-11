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

    <form method="GET" action="{{ route('inventory.index') }}" class="search-bar" id="invFilterForm">
        <input type="text" id="invSearch" name="search" class="search-input" placeholder="Search stock…"
               value="{{ $search ?? '' }}" autocomplete="off">
        <input type="hidden" name="status" id="invStatusInput" value="{{ request('status', 'all') }}">
        <input type="hidden" name="category" id="invCategoryInput" value="{{ request('category', 'all') }}">
        <button type="submit" class="btn-primary btn-sm" style="width:auto;padding:8px 14px">Search</button>
    </form>

    {{-- Category filter tabs (same style as menu page) --}}
    @php $activeCat = request('category', 'all'); @endphp
    @if($categories->isNotEmpty())
    <div class="cat-tabs">
        <button type="button" class="cat-tab {{ $activeCat === 'all' ? 'active' : '' }}"
            onclick="submitCategory('all')">All</button>
        @foreach($categories as $cat)
        <button type="button" class="cat-tab {{ $activeCat === $cat ? 'active' : '' }}"
            onclick="submitCategory('{{ addslashes($cat) }}')">{{ ucfirst($cat) }}</button>
        @endforeach
    </div>
    @endif

    {{-- Status filter chips --}}
    @php $activeStatus = request('status', 'all'); @endphp
    <div class="cat-tabs">
        <button type="button" class="cat-tab {{ $activeStatus === 'all' ? 'active' : '' }}"
            onclick="submitStatus('all')">All</button>
        <button type="button" class="cat-tab {{ $activeStatus === 'in_stock' ? 'active' : '' }}"
            onclick="submitStatus('in_stock')">In Stock</button>
        <button type="button" class="cat-tab {{ $activeStatus === 'low_stock' ? 'active' : '' }}"
            onclick="submitStatus('low_stock')">Low Stock</button>
        <button type="button" class="cat-tab {{ $activeStatus === 'out_of_stock' ? 'active' : '' }}"
            onclick="submitStatus('out_of_stock')">Out of Stock</button>
    </div>

    <div class="inventory-list" style="margin-top:8px">
        @forelse($inventories as $inv)
        <div class="inv-row">
            <div class="inv-info">
                <div class="inv-name">{{ $inv->Name }}</div>
                <div class="inv-unit">{{ $inv->Unit }}@if($inv->Category) · {{ $inv->Category }}@endif</div>
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
                        '{{ addslashes($inv->Unit) }}',
                        '{{ addslashes($inv->Category ?? '') }}'
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

    {{-- Pagination --}}
    @if($inventories->hasPages())
    <div class="inv-pagination">
        {{-- Prev --}}
        @if($inventories->onFirstPage())
            <span class="inv-page-btn inv-page-btn--disabled">‹</span>
        @else
            <a href="{{ $inventories->previousPageUrl() }}" class="inv-page-btn">‹</a>
        @endif

        {{-- Page numbers --}}
        @foreach($inventories->getUrlRange(1, $inventories->lastPage()) as $page => $url)
            @if($page == $inventories->currentPage())
                <span class="inv-page-btn inv-page-btn--active">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="inv-page-btn">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next --}}
        @if($inventories->hasMorePages())
            <a href="{{ $inventories->nextPageUrl() }}" class="inv-page-btn">›</a>
        @else
            <span class="inv-page-btn inv-page-btn--disabled">›</span>
        @endif
    </div>
    <div class="inv-page-info">
        Showing {{ $inventories->firstItem() }}–{{ $inventories->lastItem() }} of {{ $inventories->total() }} items
    </div>
    @endif

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
            <div class="form-group">
                <label class="form-label">Category <span style="opacity:.6">(optional)</span></label>
                <input type="text" name="category" class="form-input" list="invCatList"
                       placeholder="e.g. Meat, Vegetables, Sauces" maxlength="50">
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
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" id="editCategory" class="form-input" list="invCatList" maxlength="50">
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
@php
    $catSuggestions = collect($categories ?? [])
        ->merge(['Staples / Dry Goods', 'Meat & Seafood', 'Vegetables & Fresh', 'Sauces & Condiments', 'Dairy & Frozen', 'Drinks & Beverages'])
        ->filter()->unique()->sort()->values();
@endphp
<datalist id="invCatList">
    @foreach($catSuggestions as $cat)
    <option value="{{ $cat }}">
    @endforeach
</datalist>
<script>
    function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openEditModal(id, name, qty, unit, category) {
        document.getElementById('editForm').action = '/inventory/' + id;
        document.getElementById('editName').value  = name;
        document.getElementById('editQty').value   = qty;
        document.getElementById('editUnit').value  = unit;
        document.getElementById('editCategory').value = category || '';
        openModal('editModal');
    }

    function submitStatus(status) {
        document.getElementById('invStatusInput').value = status;
        document.getElementById('invFilterForm').submit();
    }

    function submitCategory(cat) {
        document.getElementById('invCategoryInput').value = cat;
        document.getElementById('invFilterForm').submit();
    }
</script>
@endpush
@endsection
