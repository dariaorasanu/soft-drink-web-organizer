
const API = '/api/admin.php';

async function apiGet(action, params = {}) {
    const qs  = new URLSearchParams({ action, ...params }).toString();
    const res = await fetch(`${API}?${qs}`);
    return res.json();
}

async function apiPost(action, data = {}) {
    const fd = new FormData();
    for (const [k, v] of Object.entries(data)) {
        if (Array.isArray(v)) {
            v.forEach(item => fd.append(k + '[]', item));
        } else {
            fd.append(k, v ?? '');
        }
    }
    const res = await fetch(`${API}?action=${action}`, { method: 'POST', body: fd });
    return res.json();
}


function toast(msg, isError = false) {
    const el = document.getElementById('admin-toast');
    if (!el) return;
    el.textContent  = msg;
    el.className    = 'admin-toast' + (isError ? ' error' : '');
    el.style.display = 'block';
    clearTimeout(el._timer);
    el._timer = setTimeout(() => { el.style.display = 'none'; }, 3200);
}


function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}


function productEmoji(name = '') {
    const n = name.toLowerCase();
    if (n.includes('ceai') || n.includes('tea'))     return '🍵';
    if (n.includes('suc')  || n.includes('juice'))   return '🥤';
    if (n.includes('lapte')|| n.includes('milk'))    return '🥛';
    if (n.includes('apa')  || n.includes('water'))   return '💧';
    if (n.includes('sirop'))                         return '🍯';
    if (n.includes('cafea')|| n.includes('coffee'))  return '☕';
    return '🥤';
}


let _confirmCb = null;

function confirmAction(title, desc, cb) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-desc').textContent  = desc;
    modal.style.display = 'flex';
    _confirmCb = cb;
}

document.getElementById('confirm-ok')?.addEventListener('click', async () => {
    document.getElementById('confirm-modal').style.display = 'none';
    if (_confirmCb) { await _confirmCb(); _confirmCb = null; }
});

document.getElementById('confirm-cancel')?.addEventListener('click', () => {
    document.getElementById('confirm-modal').style.display = 'none';
});


const statsGrid = document.getElementById('stats-grid');
if (statsGrid) {
    loadDashboard();
}

async function loadDashboard() {
    // statistici
    const res = await apiGet('stats');
    if (res.success) {
        const d = res.data;
        document.getElementById('val-products').textContent  = d.total_products;
        document.getElementById('val-users').textContent     = d.total_users;
        document.getElementById('val-lists').textContent     = d.total_lists;
        document.getElementById('val-ratings').textContent   = d.total_ratings;
        document.getElementById('val-favorites').textContent = d.total_favorites;
    }

    // top produse
    const topRes = await apiGet('list_products', { page: 1, search: '' });
    const wrap   = document.getElementById('top-products-list');
    if (!wrap) return;

    if (!topRes.success || topRes.data.products.length === 0) {
        wrap.innerHTML = '<p style="color:#7a7d75;text-align:center;padding:1rem">Niciun produs.</p>';
        return;
    }

    // sortam dupa view_count si luam top 5
    const top5 = [...topRes.data.products]
        .sort((a, b) => b.view_count - a.view_count)
        .slice(0, 5);

    wrap.innerHTML = top5.map((p, i) => {
        const img = p.image_url
            ? `<img src="${esc(p.image_url)}" alt="" class="top-img" loading="lazy">`
            : `<div class="top-emoji">${productEmoji(p.name)}</div>`;
        return `
            <div class="admin-top-item">
                <span class="top-rank">#${i + 1}</span>
                ${img}
                <div class="top-info">
                    <div class="top-name">${esc(p.name)}</div>
                    ${p.brand ? `<div class="top-brand">${esc(p.brand)}</div>` : ''}
                </div>
                <span class="top-views">${p.view_count} vizualizări</span>
            </div>`;
    }).join('');
}


const productsTbody = document.getElementById('products-tbody');
if (productsTbody) {
    initProductsPage();
}

let productPage    = 1;
let productSearch  = '';
let formData       = null; // categorii, alergeni, sezoane, regiuni
let searchTimer    = null;

async function initProductsPage() {
    // incarcam datele pentru formular (categorii, alergeni etc.)
    const fd = await apiGet('form_data');
    if (fd.success) formData = fd.data;

    await loadProducts();
    bindProductEvents();
}

async function loadProducts() {
    productsTbody.innerHTML = `<tr><td colspan="7" class="admin-table-loading"><div class="spinner"></div></td></tr>`;

    const res = await apiGet('list_products', { page: productPage, search: productSearch });
    if (!res.success) {
        productsTbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#f72585;padding:2rem">${esc(res.message)}</td></tr>`;
        return;
    }

    const { products, total_pages } = res.data;

    if (products.length === 0) {
        productsTbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#7a7d75;padding:2rem">Niciun produs găsit.</td></tr>`;
        renderPagination('products-pagination', 0, 0);
        return;
    }

    productsTbody.innerHTML = products.map(p => {
        const img = p.image_url
            ? `<img src="${esc(p.image_url)}" alt="" class="tbl-thumb" loading="lazy">`
            : `<div class="tbl-emoji">${productEmoji(p.name)}</div>`;
        return `
            <tr data-id="${p.id}">
                <td style="color:#7a7d75">#${p.id}</td>
                <td>${img}</td>
                <td style="font-weight:800">${esc(p.name)}</td>
                <td style="color:#bcb5aa">${esc(p.brand ?? '—')}</td>
                <td>${p.price ? p.price + ' RON' : '—'}</td>
                <td style="color:#bcb5aa">${p.view_count}</td>
                <td>
                    <div class="tbl-actions">
                        <button class="tbl-btn" data-action="edit" data-id="${p.id}">✎ Edit</button>
                        <button class="tbl-btn danger" data-action="delete" data-id="${p.id}">🗑</button>
                    </div>
                </td>
            </tr>`;
    }).join('');

    renderPagination('products-pagination', productPage, total_pages, (page) => {
        productPage = page;
        loadProducts();
    });
}

function bindProductEvents() {
    // cautare live cu debounce
    document.getElementById('product-search')?.addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            productSearch = e.target.value.trim();
            productPage   = 1;
            loadProducts();
        }, 350);
    });

    // buton adauga
    document.getElementById('btn-add-product')?.addEventListener('click', () => openPanel());

    // actiuni tabel (edit / delete)
    productsTbody.addEventListener('click', async e => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const { action, id } = btn.dataset;

        if (action === 'edit') {
            await openPanel(parseInt(id));
        } else if (action === 'delete') {
            confirmAction('Șterge produs', 'Ești sigur? Acțiunea este ireversibilă.', async () => {
                const res = await apiPost('delete_product', { id });
                if (res.success) { toast('Produs șters.'); await loadProducts(); }
                else toast(res.message, true);
            });
        }
    });

    // inchidere panel
    document.getElementById('panel-close')?.addEventListener('click',  closePanel);
    document.getElementById('panel-cancel')?.addEventListener('click', closePanel);
    document.getElementById('product-panel-overlay')?.addEventListener('click', closePanel);

    // salvare panel
    document.getElementById('panel-save')?.addEventListener('click', saveProduct);

    // preview imagine la schimbare URL
    document.getElementById('f-image')?.addEventListener('input', e => {
        const wrap = document.getElementById('f-image-preview');
        const url  = e.target.value.trim();
        wrap.innerHTML = url ? `<img src="${esc(url)}" alt="" onerror="this.style.display='none'">` : '';
    });

    // import CSV
    document.getElementById('import-csv-input')?.addEventListener('change', async e => {
        const file = e.target.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('csv', file);
        const res = await fetch(`${API}?action=import_csv`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            toast(`Importate ${data.data.imported} produse.`);
            await loadProducts();
        } else {
            toast(data.message, true);
        }
        e.target.value = '';
    });

    // Open Food Facts search
    document.getElementById('off-search-btn')?.addEventListener('click', searchOFF);
    document.getElementById('off-search-input')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') searchOFF();
    });
}

// deschide panel pentru adaugare (fara id) sau editare (cu id)
async function openPanel(productId = null) {
    const panel   = document.getElementById('product-panel');
    const overlay = document.getElementById('product-panel-overlay');
    const title   = document.getElementById('panel-title');

    // curatam formularul
    resetForm();
    renderFormSelects();

    if (productId) {
        title.textContent = 'Editează produs';
        // incarcam datele produsului
        const res = await apiGet('list_products', { search: '' });
        if (res.success) {
            const p = res.data.products.find(x => x.id === productId);
            if (p) populateForm(p);
        }
    } else {
        title.textContent = 'Adaugă produs';
    }

    panel.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    document.getElementById('product-panel').classList.remove('open');
    document.getElementById('product-panel-overlay').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('off-results').innerHTML = '';
}

function resetForm() {
    ['f-id','f-name','f-brand','f-price','f-volume','f-image',
        'f-description','f-ingredients','f-calories','f-sugar',
        'f-shelf-life','f-barcode'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    ['f-vegan','f-gluten-free','f-perishable'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.checked = false;
    });
    document.getElementById('f-image-preview').innerHTML = '';
}

function populateForm(p) {
    document.getElementById('f-id').value          = p.id;
    document.getElementById('f-name').value         = p.name         ?? '';
    document.getElementById('f-brand').value        = p.brand        ?? '';
    document.getElementById('f-price').value        = p.price        ?? '';
    document.getElementById('f-volume').value       = p.volume_ml    ?? '';
    document.getElementById('f-image').value        = p.image_url    ?? '';
    document.getElementById('f-description').value  = p.description  ?? '';
    document.getElementById('f-ingredients').value  = p.ingredients  ?? '';
    document.getElementById('f-calories').value     = p.calories_per_100ml ?? '';
    document.getElementById('f-sugar').value        = p.sugar_per_100ml    ?? '';
    document.getElementById('f-shelf-life').value   = p.shelf_life_days    ?? '';
    document.getElementById('f-barcode').value      = p.barcode      ?? '';
    document.getElementById('f-vegan').checked      = !!p.is_vegan;
    document.getElementById('f-gluten-free').checked = !!p.is_gluten_free;
    document.getElementById('f-perishable').checked  = !!p.is_perishable;

    if (p.image_url) {
        document.getElementById('f-image-preview').innerHTML =
            `<img src="${esc(p.image_url)}" alt="">`;
    }
}

// randeaza checkbox-urile pentru categorii, alergeni, sezoane, regiuni
function renderFormSelects() {
    if (!formData) return;

    renderMultiselect('f-categories', formData.categories, 'name');
    renderMultiselect('f-allergens',  formData.allergens,  'name');
    renderMultiselect('f-seasons',    formData.seasons,    'name');
    renderMultiselect('f-regions',    formData.regions,    'name');
}

function renderMultiselect(containerId, items, labelKey) {
    const wrap = document.getElementById(containerId);
    if (!wrap || !items) return;
    wrap.innerHTML = items.map(item => `
        <button type="button" class="multiselect-option"
                data-id="${item.id}"
                data-container="${containerId}">
            ${esc(item[labelKey])}
        </button>
    `).join('');

    wrap.addEventListener('click', e => {
        const btn = e.target.closest('.multiselect-option');
        if (btn) btn.classList.toggle('selected');
    });
}

// ia id-urile selectate dintr-un multiselect
function getSelectedIds(containerId) {
    return [...document.querySelectorAll(`#${containerId} .multiselect-option.selected`)]
        .map(el => el.dataset.id);
}

async function saveProduct() {
    const id   = document.getElementById('f-id').value;
    const name = document.getElementById('f-name').value.trim();

    if (!name) { toast('Numele este obligatoriu.', true); return; }

    const data = {
        name,
        brand:               document.getElementById('f-brand').value.trim(),
        price:               document.getElementById('f-price').value,
        volume_ml:           document.getElementById('f-volume').value,
        image_url:           document.getElementById('f-image').value.trim(),
        description:         document.getElementById('f-description').value.trim(),
        ingredients:         document.getElementById('f-ingredients').value.trim(),
        calories_per_100ml:  document.getElementById('f-calories').value,
        sugar_per_100ml:     document.getElementById('f-sugar').value,
        shelf_life_days:     document.getElementById('f-shelf-life').value,
        barcode:             document.getElementById('f-barcode').value.trim(),
        is_vegan:            document.getElementById('f-vegan').checked      ? '1' : '',
        is_gluten_free:      document.getElementById('f-gluten-free').checked ? '1' : '',
        is_perishable:       document.getElementById('f-perishable').checked  ? '1' : '',
        'category_ids[]':    getSelectedIds('f-categories'),
        'allergen_ids[]':    getSelectedIds('f-allergens'),
        'season_ids[]':      getSelectedIds('f-seasons'),
        'region_ids[]':      getSelectedIds('f-regions'),
    };

    const action = id ? 'update_product' : 'create_product';
    if (id) data.id = id;

    const res = await apiPost(action, data);
    if (res.success) {
        toast(res.message);
        closePanel();
        await loadProducts();
    } else {
        toast(res.message, true);
    }
}

// Open Food Facts — cauta produs si populeaza formularul
async function searchOFF() {
    const query   = document.getElementById('off-search-input').value.trim();
    const results = document.getElementById('off-results');
    if (!query) return;

    results.innerHTML = '<div class="admin-loading"><div class="spinner"></div></div>';

    const res = await apiGet('off_search', { q: query });
    if (!res.success || !res.data?.length) {
        results.innerHTML = '<p style="color:#7a7d75;font-size:.85rem;padding:.5rem">Niciun rezultat.</p>';
        return;
    }

    results.innerHTML = res.data.map((item, i) => `
        <div class="off-result-item" data-index="${i}">
            ${item.image_url ? `<img src="${esc(item.image_url)}" class="off-result-img" alt="">` : ''}
            <div>
                <div class="off-result-name">${esc(item.name)}</div>
                ${item.brand ? `<div class="off-result-brand">${esc(item.brand)}</div>` : ''}
            </div>
        </div>`).join('');

    // click pe rezultat populeaza formularul
    results.querySelectorAll('.off-result-item').forEach((el, i) => {
        el.addEventListener('click', () => {
            const item = res.data[i];
            document.getElementById('f-name').value        = item.name        ?? '';
            document.getElementById('f-brand').value       = item.brand       ?? '';
            document.getElementById('f-image').value       = item.image_url   ?? '';
            document.getElementById('f-ingredients').value = item.ingredients ?? '';
            document.getElementById('f-calories').value    = item.calories    ?? '';
            document.getElementById('f-sugar').value       = item.sugar       ?? '';
            document.getElementById('f-barcode').value     = item.barcode     ?? '';

            if (item.image_url) {
                document.getElementById('f-image-preview').innerHTML =
                    `<img src="${esc(item.image_url)}" alt="">`;
            }
            results.innerHTML = '';
            document.getElementById('off-search-input').value = '';
            toast('Produs importat din Open Food Facts!');
        });
    });
}

let userPage     = 1;
// id-ul adminului curent, citit din meta tag (pus in users.php)
const currentUserId = parseInt(
    document.querySelector('meta[name="current-user-id"]')?.content ?? '0'
);

const usersTbody = document.getElementById('users-tbody');
if (usersTbody) {
    initUsersPage();
}

async function initUsersPage() {
    await loadUsers();
}

async function loadUsers() {
    usersTbody.innerHTML = `<tr><td colspan="7" class="admin-table-loading"><div class="spinner"></div></td></tr>`;

    const res = await apiGet('list_users', { page: userPage });
    if (!res.success) {
        usersTbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#f72585;padding:2rem">${esc(res.message)}</td></tr>`;
        return;
    }

    const { users, total_pages } = res.data;

    if (users.length === 0) {
        usersTbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#7a7d75;padding:2rem">Niciun utilizator.</td></tr>`;
        return;
    }

    usersTbody.innerHTML = users.map(u => {
        const initials = u.username.charAt(0).toUpperCase();
        const isMe     = u.id === currentUserId;
        const date     = new Date(u.created_at).toLocaleDateString('ro-RO');

        return `
            <tr data-id="${u.id}">
                <td>
                    <div class="user-cell">
                        <div class="user-avatar">${esc(initials)}</div>
                        <span style="font-weight:800">${esc(u.username)}</span>
                        ${isMe ? '<span style="color:#7a7d75;font-size:.75rem">(tu)</span>' : ''}
                    </div>
                </td>
                <td style="color:#bcb5aa">${esc(u.email)}</td>
                <td>
                    <select class="role-select" data-user-id="${u.id}" ${isMe ? 'disabled' : ''}>
                        <option value="user"  ${u.role === 'user'  ? 'selected' : ''}>User</option>
                        <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Admin</option>
                    </select>
                </td>
                <td style="color:#bcb5aa">${u.list_count}</td>
                <td style="color:#bcb5aa">${u.favorite_count}</td>
                <td style="color:#7a7d75;font-size:.8rem">${date}</td>
                <td>
                    <button class="tbl-btn danger" data-action="delete-user" data-id="${u.id}"
                            ${isMe ? 'disabled' : ''}>🗑</button>
                </td>
            </tr>`;
    }).join('');

    // schimbare rol instant
    usersTbody.querySelectorAll('.role-select').forEach(sel => {
        sel.addEventListener('change', async e => {
            const userId = parseInt(sel.dataset.userId);
            const role   = sel.value;
            const res    = await apiPost('update_role', { user_id: userId, role });
            if (res.success) toast(`Rol actualizat: ${role}.`);
            else { toast(res.message, true); await loadUsers(); }
        });
    });

    // stergere user
    usersTbody.addEventListener('click', async e => {
        const btn = e.target.closest('[data-action="delete-user"]');
        if (!btn) return;
        const id = parseInt(btn.dataset.id);
        confirmAction('Șterge utilizator', 'Ești sigur? Toate datele userului vor fi șterse.', async () => {
            const res = await apiPost('delete_user', { user_id: id });
            if (res.success) { toast('Utilizator șters.'); await loadUsers(); }
            else toast(res.message, true);
        });
    });

    renderPagination('users-pagination', userPage, total_pages, (page) => {
        userPage = page;
        loadUsers();
    });
}


function renderPagination(containerId, currentPage, totalPages, onPageChange) {
    const wrap = document.getElementById(containerId);
    if (!wrap) return;

    if (totalPages <= 1) { wrap.innerHTML = ''; return; }

    let html = `<button class="page-btn" ${currentPage <= 1 ? 'disabled' : ''}
                        data-page="${currentPage - 1}">←</button>`;

    for (let i = 1; i <= totalPages; i++) {
        if (totalPages > 7 && Math.abs(i - currentPage) > 2 && i !== 1 && i !== totalPages) {
            if (i === 2 || i === totalPages - 1) html += `<span style="color:#7a7d75;padding:0 .25rem">…</span>`;
            continue;
        }
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}"
                         data-page="${i}">${i}</button>`;
    }

    html += `<button class="page-btn" ${currentPage >= totalPages ? 'disabled' : ''}
                     data-page="${currentPage + 1}">→</button>`;

    wrap.innerHTML = html;

    wrap.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', () => onPageChange(parseInt(btn.dataset.page)));
    });
}