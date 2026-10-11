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

    {{-- Flat list for paginated rendering --}}
    <div id="menuList">
        @foreach($menus->sortBy('Category')->sortBy('Name') as $m)
        <div class="inv-row menu-card-item"
             data-name="{{ strtolower($m->Name) }}"
             data-cat="{{ $m->Category }}"
             style="display:none">
            <div class="menu-thumb-wrap">
                @if($m->Image)
                    <img src="{{ str_starts_with($m->Image, 'http') ? $m->Image : asset('storage/'.$m->Image) }}" class="menu-thumb" alt="{{ $m->Name }}">
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
                        '{{ $m->Image ? (str_starts_with($m->Image, 'http') ? $m->Image : asset('storage/'.$m->Image)) : '' }}'
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

    <div id="menuEmpty" class="empty-state" style="display:none">
        <div>No menu items found.</div>
    </div>

    {{-- Pagination bar --}}
    <div class="inv-pagination" id="menuPagination" style="display:none">
        <button class="inv-page-btn" id="menuPrevBtn" onclick="menuChangePage(menuCurrentPage - 1)">‹</button>
        <div id="menuPageNumbers" style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center"></div>
        <button class="inv-page-btn" id="menuNextBtn" onclick="menuChangePage(menuCurrentPage + 1)">›</button>
    </div>
    <div class="inv-page-info" id="menuPageInfo" style="display:none"></div>

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
                <div class="inv-search-wrap" id="addInvWrap">
                    <input type="hidden" name="inventory_id" id="addInvId">
                    <input type="text" id="addInvSearch" class="form-input" placeholder="Search inventory…"
                           autocomplete="off" oninput="filterInvDropdown('add')" onfocus="openInvDropdown('add')"
                           onblur="setTimeout(()=>closeInvDropdown('add'),150)">
                    <div class="inv-dropdown" id="addInvDropdown" style="display:none">
                        <div class="inv-dropdown-item" data-id="" onmousedown="selectInv('add','','No link')">No link</div>
                        @foreach($inventories as $inv)
                        <div class="inv-dropdown-item"
                             data-id="{{ $inv->Inventory_ID }}"
                             data-label="{{ $inv->Name }} — {{ $inv->Quantity }} {{ $inv->Unit }}"
                             onmousedown="selectInv('add','{{ $inv->Inventory_ID }}','{{ addslashes($inv->Name) }} — {{ $inv->Quantity }} {{ $inv->Unit }}')">
                            {{ $inv->Name }} — {{ $inv->Quantity }} {{ $inv->Unit }}
                        </div>
                        @endforeach
                    </div>
                </div>
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
                <div class="inv-search-wrap" id="editInvWrap">
                    <input type="hidden" name="inventory_id" id="editInvId">
                    <input type="text" id="editInvSearch" class="form-input" placeholder="Search inventory…"
                           autocomplete="off" oninput="filterInvDropdown('edit')" onfocus="openInvDropdown('edit')"
                           onblur="setTimeout(()=>closeInvDropdown('edit'),150)">
                    <div class="inv-dropdown" id="editInvDropdown" style="display:none">
                        <div class="inv-dropdown-item" data-id="" onmousedown="selectInv('edit','','No link')">No link</div>
                        @foreach($inventories as $inv)
                        <div class="inv-dropdown-item"
                             data-id="{{ $inv->Inventory_ID }}"
                             data-label="{{ $inv->Name }} — {{ $inv->Quantity }} {{ $inv->Unit }}"
                             onmousedown="selectInv('edit','{{ $inv->Inventory_ID }}','{{ addslashes($inv->Name) }} — {{ $inv->Quantity }} {{ $inv->Unit }}')">
                            {{ $inv->Name }} — {{ $inv->Quantity }} {{ $inv->Unit }}
                        </div>
                        @endforeach
                    </div>
                </div>
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
    const MENU_PER_PAGE = 10;
    let menuCurrentPage = 1;
    let menuFilteredItems = [];

    function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function toggleInvLink(which, cat) {
        const isDrinks = (cat || '').toLowerCase() === 'drinks';
        const group = document.getElementById(which === 'add' ? 'addInvLinkGroup' : 'editInvLinkGroup');
        group.style.display = isDrinks ? '' : 'none';
        if (!isDrinks) selectInv(which, '', 'No link');
    }

    // ── Searchable inventory dropdown ────────────────────────────
    function openInvDropdown(which) {
        const dd = document.getElementById(which === 'add' ? 'addInvDropdown' : 'editInvDropdown');
        dd.style.display = 'block';
        filterInvDropdown(which);
    }
    function closeInvDropdown(which) {
        document.getElementById(which === 'add' ? 'addInvDropdown' : 'editInvDropdown').style.display = 'none';
    }
    function filterInvDropdown(which) {
        const input = document.getElementById(which === 'add' ? 'addInvSearch' : 'editInvSearch');
        const dd    = document.getElementById(which === 'add' ? 'addInvDropdown' : 'editInvDropdown');
        const q     = input.value.toLowerCase();
        dd.style.display = 'block';
        dd.querySelectorAll('.inv-dropdown-item').forEach(item => {
            const label = item.dataset.label || item.textContent;
            item.style.display = !q || label.toLowerCase().includes(q) ? '' : 'none';
        });
    }
    function selectInv(which, id, label) {
        document.getElementById(which === 'add' ? 'addInvId'     : 'editInvId').value     = id;
        document.getElementById(which === 'add' ? 'addInvSearch' : 'editInvSearch').value = id ? label : '';
        closeInvDropdown(which);
    }

    // ── Menu pagination ───────────────────────────────────────────
    function getFilteredItems() {
        const cat = (document.querySelector('.cat-tab.active') || {}).dataset?.cat || 'all';
        const q   = document.getElementById('menuSearch').value.toLowerCase().trim();
        return Array.from(document.querySelectorAll('.menu-card-item')).filter(row => {
            const catMatch  = cat === 'all' || row.dataset.cat === cat;
            const nameMatch = !q || row.dataset.name.includes(q);
            return catMatch && nameMatch;
        });
    }

    function renderMenuPage(page) {
        menuFilteredItems = getFilteredItems();
        const total   = menuFilteredItems.length;
        const pages   = Math.max(1, Math.ceil(total / MENU_PER_PAGE));
        menuCurrentPage = Math.min(Math.max(1, page), pages);

        const start = (menuCurrentPage - 1) * MENU_PER_PAGE;
        const end   = start + MENU_PER_PAGE;

        // Hide all, show only current page slice
        document.querySelectorAll('.menu-card-item').forEach(r => r.style.display = 'none');
        menuFilteredItems.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });

        // Empty state
        document.getElementById('menuEmpty').style.display = total === 0 ? '' : 'none';

        // Pagination bar
        const pag  = document.getElementById('menuPagination');
        const info = document.getElementById('menuPageInfo');
        if (pages <= 1 && total > 0) {
            pag.style.display  = 'none';
            info.style.display = 'none';
        } else if (total > 0) {
            pag.style.display  = 'flex';
            info.style.display = '';

            document.getElementById('menuPrevBtn').classList.toggle('inv-page-btn--disabled', menuCurrentPage === 1);
            document.getElementById('menuNextBtn').classList.toggle('inv-page-btn--disabled', menuCurrentPage === pages);

            // Page number buttons
            const numWrap = document.getElementById('menuPageNumbers');
            numWrap.innerHTML = '';
            for (let p = 1; p <= pages; p++) {
                const btn = document.createElement('button');
                btn.className = 'inv-page-btn' + (p === menuCurrentPage ? ' inv-page-btn--active' : '');
                btn.textContent = p;
                btn.onclick = () => menuChangePage(p);
                numWrap.appendChild(btn);
            }

            const showing = Math.min(end, total);
            info.textContent = `Showing ${start + 1}–${showing} of ${total} items`;
        } else {
            pag.style.display  = 'none';
            info.style.display = 'none';
        }
    }

    function menuChangePage(page) {
        renderMenuPage(page);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function filterMenuCat(cat) {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
        renderMenuPage(1);
    }

    function searchMenu() {
        renderMenuPage(1);
    }

    // Init on load
    (function () {
        const params = new URLSearchParams(location.search);
        const cat = params.get('cat') || 'all';
        const q   = params.get('search') || '';
        document.getElementById('menuSearch').value = q;
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
        renderMenuPage(1);
    })();

    function openEditMenu(id, name, price, cat, avail, invId, imgUrl) {
        document.getElementById('editMenuForm').action = '/menu/' + id;
        document.getElementById('emName').value        = name;
        document.getElementById('emPrice').value       = price;
        document.getElementById('emCat').value         = cat;
        toggleInvLink('edit', cat);

        if (invId) {
            const item = document.querySelector(`#editInvDropdown [data-id="${invId}"]`);
            const label = item ? (item.dataset.label || item.textContent.trim()) : '';
            selectInv('edit', invId, label);
        } else {
            selectInv('edit', '', 'No link');
        }

        document.getElementById('emAvailYes').checked = avail == 1;
        document.getElementById('emAvailNo').checked  = avail != 1;

        const preview = document.getElementById('editImgPreview');
        const ph      = document.getElementById('editImgPlaceholder');
        if (imgUrl) {
            preview.src = imgUrl; preview.style.display = 'block'; ph.style.display = 'none';
        } else {
            preview.style.display = 'none'; ph.style.display = 'flex';
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
