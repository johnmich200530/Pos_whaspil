@extends('pos.layout')

@section('content')
<div class="screen">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
            <div class="header-name">Menu Management</div>
        </div>
        <button class="btn-primary btn-sm" onclick="openModal('addMenuModal')">+ Add Item</button>
    </div>

    {{-- Search --}}
    <div class="search-bar">
        <input type="text" id="menuSearch" class="search-input"
               placeholder="Search menu items…" oninput="searchMenu()">
    </div>

    {{-- Category filter tabs --}}
    <div class="cat-tabs">
        <button class="cat-tab active" data-cat="all" onclick="filterMenuCat('all')">All</button>
        @foreach($menus->groupBy('Category')->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)->keys() as $cat)
            <button class="cat-tab" data-cat="{{ $cat }}" onclick="filterMenuCat('{{ $cat }}')">{{ ucfirst($cat) }}</button>
        @endforeach
    </div>

    @foreach($menus->groupBy('Category')->sortKeys(SORT_NATURAL | SORT_FLAG_CASE) as $cat => $items)
    <div class="section-title menu-section" data-cat="{{ $cat }}">{{ ucfirst($cat) }}</div>
    <div class="inventory-list menu-section" data-cat="{{ $cat }}" style="margin-bottom:8px">
        @foreach($items as $m)
        <div class="inv-row" data-name="{{ strtolower($m->Name) }}">
            <div class="menu-thumb-wrap">
                @if($m->Image)
                    <img src="{{ asset('storage/'.$m->Image) }}" class="menu-thumb" alt="{{ $m->Name }}">
                @else
                    <div class="menu-thumb-placeholder">
                        <i data-lucide="utensils" style="width:22px;height:22px;color:var(--muted)"></i>
                    </div>
                @endif
            </div>
            <div class="inv-info">
                <div class="inv-name">{{ $m->Name }}</div>
                <div class="inv-unit">{{ strcasecmp($m->Category, 'Drinks') === 0 ? ($m->inventory->Name ?? 'No inventory link') : $m->Category }}</div>
            </div>
            <div class="inv-right">
                <span class="inv-qty">₱{{ number_format($m->Price, 2) }}</span>
                <span class="stock-badge {{ $m->Availability ? 'stock-in_stock' : 'stock-out_of_stock' }}">
                    {{ $m->Availability ? 'Available' : 'Unavailable' }}
                </span>
            </div>
            <div class="inv-actions">
                <button class="btn-icon"
                    onclick="openEditMenu(
                        {{ $m->Menu_ID }},
                        '{{ addslashes($m->Name) }}',
                        {{ $m->Price }},
                        '{{ addslashes($m->Category) }}',
                        {{ $m->Availability ? 1 : 0 }},
                        {{ $m->Inventory_ID ?? 'null' }},
                        '{{ $m->Image ? asset('storage/'.$m->Image) : '' }}'
                    )"><i data-lucide="pencil"></i></button>
                <form method="POST" action="{{ route('menu.destroy', $m) }}"
                      onsubmit="return confirm('Remove {{ addslashes($m->Name) }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-icon"><i data-lucide="trash-2"></i></button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach

    @if($menus->isEmpty())
    <div class="empty-state">
        <div>No menu items yet.</div>
    </div>
    @endif

</div>

{{-- Add Modal --}}
<div class="modal-overlay" id="addMenuModal" style="display:none">
    <div class="modal">
        <div class="modal-title">Add Menu Item</div>
        <form method="POST" action="{{ route('menu.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" name="name" class="form-input" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Price (₱)</label>
                    <input type="number" name="price" class="form-input" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" id="addCat" class="form-input" required onchange="toggleInvLink('add', this.value)">
                        <option value="">Select…</option>
                        <option value="Drinks">Drinks</option>
                        <option value="Rice">Rice</option>
                        <option value="Pork">Pork</option>
                        <option value="Chicken">Chicken</option>
                        <option value="Sizzling">Sizzling</option>
                        <option value="Appitizer">Appitizer</option>
                        <option value="Hipon">Hipon</option>
                        <option value="Fish">Fish</option>
                        <option value="Tinola/Sinigang">Tinola/Sinigang</option>
                        <option value="Inihaw">Inihaw</option>
                        <option value="Kinilaw">Kinilaw</option>
                        <option value="Noodles">Noodles</option>
                        <option value="Vegetable">Vegetable</option>
                    </select>
                </div>
            </div>
            <div class="form-group" id="addInvLinkGroup" style="display:none">
                <label class="form-label">Link to Inventory
                    <span style="color:#888;font-size:.78rem">(Drinks only — stock auto-deducts on order)</span>
                </label>
                <select name="inventory_id" id="addInv" class="form-input">
                    <option value="">No link</option>
                    @foreach($inventories as $inv)
                    <option value="{{ $inv->Inventory_ID }}">
                        {{ $inv->Name }} — {{ $inv->Quantity }} {{ $inv->Unit }}
                    </option>
                    @endforeach
                </select>
            </div>
            {{-- Image upload --}}
            <div class="form-group">
                <label class="form-label">Item Image</label>
                <div class="img-upload-wrap" onclick="document.getElementById('addImgInput').click()">
                    <img id="addImgPreview" src="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:8px;">
                    <div id="addImgPlaceholder" class="img-upload-placeholder">
                        <i data-lucide="camera" style="width:28px;height:28px;color:var(--muted)"></i>
                        <span style="font-size:.75rem;color:var(--muted)">Click to upload photo</span>
                    </div>
                </div>
                <input type="file" id="addImgInput" name="image" accept="image/*" style="display:none"
                       onchange="previewImg(this,'addImgPreview','addImgPlaceholder')">
            </div>
            <div class="form-group">
                <label class="form-label">Availability</label>
                <div class="avail-toggle-row">
                    <label class="avail-option">
                        <input type="radio" name="availability" value="1" checked>
                        <div class="avail-option-card avail-yes">Available</div>
                    </label>
                    <label class="avail-option">
                        <input type="radio" name="availability" value="0">
                        <div class="avail-option-card avail-no">Not Available</div>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn-primary">Add Item</button>
        </form>
        <button type="button" class="btn-secondary" onclick="closeModal('addMenuModal')">Cancel</button>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal-overlay" id="editMenuModal" style="display:none">
    <div class="modal">
        <div class="modal-title">Edit Menu Item</div>
        <form method="POST" id="editMenuForm" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" name="name" id="emName" class="form-input" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Price (₱)</label>
                    <input type="number" name="price" id="emPrice" class="form-input" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" id="emCat" class="form-input" required onchange="toggleInvLink('edit', this.value)">
                        <option value="">Select…</option>
                        <option value="Drinks">Drinks</option>
                        <option value="Rice">Rice</option>
                        <option value="Pork">Pork</option>
                        <option value="Chicken">Chicken</option>
                        <option value="Sizzling">Sizzling</option>
                        <option value="Appitizer">Appitizer</option>
                        <option value="Hipon">Hipon</option>
                        <option value="Fish">Fish</option>
                        <option value="Tinola/Sinigang">Tinola/Sinigang</option>
                        <option value="Inihaw">Inihaw</option>
                        <option value="Kinilaw">Kinilaw</option>
                        <option value="Noodles">Noodles</option>
                        <option value="Vegetable">Vegetable</option>
                    </select>
                </div>
            </div>
            <div class="form-group" id="editInvLinkGroup" style="display:none">
                <label class="form-label">Link to Inventory
                    <span style="color:#888;font-size:.78rem">(Drinks only — stock auto-deducts on order)</span>
                </label>
                <select name="inventory_id" id="emInv" class="form-input">
                    <option value="">No link</option>
                    @foreach($inventories as $inv)
                    <option value="{{ $inv->Inventory_ID }}">
                        {{ $inv->Name }} — {{ $inv->Quantity }} {{ $inv->Unit }}
                    </option>
                    @endforeach
                </select>
            </div>
            {{-- Image upload --}}
            <div class="form-group">
                <label class="form-label">Item Image <span style="color:#888;font-size:.78rem">(leave blank to keep current)</span></label>
                <div class="img-upload-wrap" onclick="document.getElementById('editImgInput').click()">
                    <img id="editImgPreview" src="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:8px;">
                    <div id="editImgPlaceholder" class="img-upload-placeholder">
                        <i data-lucide="camera" style="width:28px;height:28px;color:var(--muted)"></i>
                        <span style="font-size:.75rem;color:var(--muted)">Click to change photo</span>
                    </div>
                </div>
                <input type="file" id="editImgInput" name="image" accept="image/*" style="display:none"
                       onchange="previewImg(this,'editImgPreview','editImgPlaceholder')">
            </div>
            <div class="form-group">
                <label class="form-label">Availability</label>
                <div class="avail-toggle-row">
                    <label class="avail-option">
                        <input type="radio" name="availability" id="emAvailYes" value="1">
                        <div class="avail-option-card avail-yes">Available</div>
                    </label>
                    <label class="avail-option">
                        <input type="radio" name="availability" id="emAvailNo" value="0">
                        <div class="avail-option-card avail-no">Not Available</div>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn-primary">Update</button>
        </form>
        <button type="button" class="btn-secondary" onclick="closeModal('editMenuModal')">Cancel</button>
    </div>
</div>

@push('scripts')
<script>
    function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function toggleInvLink(which, cat) {
        const isDrinks = (cat || '').toLowerCase() === 'drinks';
        const group = document.getElementById(which === 'add' ? 'addInvLinkGroup' : 'editInvLinkGroup');
        const select = document.getElementById(which === 'add' ? 'addInv' : 'emInv');
        group.style.display = isDrinks ? '' : 'none';
        if (!isDrinks) select.value = '';
    }

    function applyMenuFilters() {
        const active = document.querySelector('.cat-tab.active');
        const cat = active ? active.dataset.cat : 'all';
        const q = document.getElementById('menuSearch').value.toLowerCase().trim();
        document.querySelectorAll('.menu-section').forEach(section => {
            const catMatch = cat === 'all' || section.dataset.cat === cat;
            let visible = 0;
            section.querySelectorAll('.inv-row').forEach(row => {
                const match = catMatch && (!q || row.dataset.name.includes(q));
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            section.style.display = visible > 0 ? '' : 'none';
        });
    }

    function syncMenuUrl() {
        const params = new URLSearchParams();
        const active = document.querySelector('.cat-tab.active');
        const cat = active ? active.dataset.cat : 'all';
        const q = document.getElementById('menuSearch').value.trim();
        if (cat !== 'all') params.set('cat', cat);
        if (q) params.set('search', q);
        const qs = params.toString();
        history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
    }

    function filterMenuCat(cat) {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
        applyMenuFilters();
        syncMenuUrl();
    }

    function searchMenu() {
        applyMenuFilters();
        syncMenuUrl();
    }

    // Restore category/search after add-edit-delete redirects back here
    (function () {
        const params = new URLSearchParams(location.search);
        const cat = params.get('cat') || 'all';
        const q = params.get('search') || '';
        document.getElementById('menuSearch').value = q;
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
        applyMenuFilters();
    })();

    function openEditMenu(id, name, price, cat, avail, invId, imgUrl) {
        document.getElementById('editMenuForm').action = '/menu/' + id;
        document.getElementById('emName').value        = name;
        document.getElementById('emPrice').value       = price;
        document.getElementById('emCat').value         = cat;
        document.getElementById('emInv').value         = invId || '';
        toggleInvLink('edit', cat);
        document.getElementById('emAvailYes').checked  = avail == 1;
        document.getElementById('emAvailNo').checked   = avail != 1;

        const preview = document.getElementById('editImgPreview');
        const ph      = document.getElementById('editImgPlaceholder');
        if (imgUrl) {
            preview.src           = imgUrl;
            preview.style.display = 'block';
            ph.style.display      = 'none';
        } else {
            preview.style.display = 'none';
            ph.style.display      = 'flex';
        }
        document.getElementById('editImgInput').value = '';
        openModal('editMenuModal');
    }

    function previewImg(input, previewId, placeholderId) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById(previewId).src           = e.target.result;
            document.getElementById(previewId).style.display = 'block';
            document.getElementById(placeholderId).style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
</script>
@endpush
@endsection
