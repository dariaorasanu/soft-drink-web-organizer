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
    if (!el)
        return;

    el.textContent   = msg;
    el.className     = 'admin-toast' + (isError ? ' error' : '');
    el.style.display = 'block';

    clearTimeout(el._timer);
    el._timer = setTimeout(() => { el.style.display = 'none'; }, 3200);
}

// protectie XSS, transforma caracterele periculoase in text normal
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// returneaza un emoji potrivit pentru un produs pe baza numelui
function productEmoji(name = '') {
    const n = name.toLowerCase();
    if (n.includes('ceai') || n.includes('tea'))     return '🍵';
    if (n.includes('suc')  || n.includes('juice'))   return '🥤';
    if (n.includes('lapte')|| n.includes('milk'))    return '🥛';
    if (n.includes('apa')  || n.includes('water'))   return '💧';
    if (n.includes('sirop'))                         return '🍯';
    if (n.includes('cafea')|| n.includes('coffee'))  return '☕';
    return '🥤'; // default daca nu gasim nimic potrivit
}

// tinem minte ce functie sa rulam dupa ce userul confirma in modal
// e null la inceput, se seteaza cand deschidem modalul
let _confirmCb = null;

// deschide modalul de confirmare (ex: "Esti sigur ca vrei sa stergi?")
// cb = functia care se ruleaza daca userul apasa "Da, sterge"
function confirmAction(title, desc, cb) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;

    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-desc').textContent  = desc;
    modal.style.display = 'flex';

    // salvam functia ca sa o apelam mai tarziu la click pe "Ok"
    _confirmCb = cb;
}

// cand userul apasa "Ok" in modal, rulam functia salvata
document.getElementById('confirm-ok')?.addEventListener('click', async () => {
    document.getElementById('confirm-modal').style.display = 'none';
    if (_confirmCb) {
        await _confirmCb(); // asteptam sa se termine (ex: stergerea din DB)
        _confirmCb = null;  // resetam pentru data viitoare
    }
});

// cand userul apasa "Anuleaza", inchidem modalul fara sa facem nimic
document.getElementById('confirm-cancel')?.addEventListener('click', () => {
    document.getElementById('confirm-modal').style.display = 'none';
});

// verificam daca suntem pe pagina de dashboard
// daca da, incarcam datele
const statsGrid = document.getElementById('stats-grid');
if (statsGrid) {
    loadDashboard();
}

async function loadDashboard() {
    // cerem statisticile de la server
    const res = await apiGet('stats');
    if (res.success) {
        const d = res.data;
        // punem numerele in cardurile din pagina
        document.getElementById('val-products').textContent  = d.total_products;
        document.getElementById('val-users').textContent     = d.total_users;
        document.getElementById('val-lists').textContent     = d.total_lists;
        document.getElementById('val-ratings').textContent   = d.total_ratings;
        document.getElementById('val-favorites').textContent = d.total_favorites;
    }

    // cerem produsele pentru top 5
    const topRes = await apiGet('list_products', { page: 1, search: '' });
    const wrap   = document.getElementById('top-products-list');
    if (!wrap) return;

    if (!topRes.success || topRes.data.products.length === 0) {
        wrap.innerHTML = '<p style="color:#7a7d75;text-align:center;padding:1rem">Niciun produs.</p>';
        return;
    }

    // sortam dupa view_count (cele mai vizualizate primul) si luam primele 5
    const top5 = [...topRes.data.products]
        .sort((a, b) => b.view_count - a.view_count)
        .slice(0, 5);

    // construim HTML-ul pentru fiecare produs din top 5
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
    }).join(''); // join('') uneste toate string-urile intr-unul singur
}

// verificam daca suntem pe pagina de produse
const productsTbody = document.getElementById('products-tbody');
if (productsTbody) {
    initProductsPage();
}

let productPage   = 1;   // pagina curenta din tabel
let productSearch = '';  // termenul de cautare curent
let formData      = null; // datele pentru formular (categorii, alergeni etc)
let searchTimer   = null; // timer pentru debounce la cautare

async function initProductsPage() {
    // incarcam categoriile, alergenii etc pentru formularul de adaugare
    const fd = await apiGet('form_data');
    if (fd.success) formData = fd.data;

    // incarcam produsele in tabel
    await loadProducts();

    // atasam toate event listener-ele
    bindProductEvents();
}

// incarca produsele de la server si le afiseaza in tabel
async function loadProducts() {
    // afisam un spinner cat timp asteptam
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

    // construim un rand din tabel pentru fiecare produs
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

    // afisam butoanele de paginare
    renderPagination('products-pagination', productPage, total_pages, (page) => {
        productPage = page;
        loadProducts();
    });
}

// atasam toate butoanele si input-urile din pagina de produse
function bindProductEvents() {

    // cautare live - la fiecare tasta asteptam 350ms inainte sa cautam
    // (debounce - ca sa nu facem fetch la fiecare litera)
    document.getElementById('product-search')?.addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            productSearch = e.target.value.trim();
            productPage   = 1; // resetam la prima pagina
            loadProducts();
        }, 350);
    });

    // butonul de adaugare produs nou
    document.getElementById('btn-add-product')?.addEventListener('click', () => openPanel());

    // butoanele de edit si delete din tabel
    // folosim event delegation - un singur listener pe tabel in loc de unul per buton
    productsTbody.addEventListener('click', async e => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return; // nu s-a apasat un buton de actiune

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

    // inchidere panel lateral
    document.getElementById('panel-close')?.addEventListener('click',  closePanel);
    document.getElementById('panel-cancel')?.addEventListener('click', closePanel);
    document.getElementById('product-panel-overlay')?.addEventListener('click', closePanel);

    // salvare produs din panel
    document.getElementById('panel-save')?.addEventListener('click', saveProduct);

    // preview imagine - cand scriem un URL, afisam imaginea instant
    document.getElementById('f-image')?.addEventListener('input', e => {
        const wrap = document.getElementById('f-image-preview');
        const url  = e.target.value.trim();
        wrap.innerHTML = url
            ? `<img src="${esc(url)}" alt="" onerror="this.style.display='none'">`
            : '';
    });

    // import CSV - cand se selecteaza un fisier, il trimitem la server
    document.getElementById('import-csv-input')?.addEventListener('change', async e => {
        const file = e.target.files[0];
        if (!file) return;

        const fd = new FormData();
        fd.append('csv', file);

        const res  = await fetch(`${API}?action=import_csv`, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            toast(`Importate ${data.data.imported} produse.`);
            await loadProducts();
        } else {
            toast(data.message, true);
        }

        e.target.value = ''; // resetam input-ul de fisier
    });

    // cautare Open Food Facts
    document.getElementById('off-search-btn')?.addEventListener('click', searchOFF);
    document.getElementById('off-search-input')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') searchOFF();
    });
}

// deschide panel-ul lateral pentru adaugare sau editare
// productId = null inseamna adaugare, un numar inseamna editare
async function openPanel(productId = null) {
    const panel   = document.getElementById('product-panel');
    const overlay = document.getElementById('product-panel-overlay');
    const title   = document.getElementById('panel-title');

    // curatam formularul de la utilizarea anterioara
    resetForm();
    renderFormSelects();

    if (productId) {
        title.textContent = 'Editează produs';
        // gasim produsul in lista si populam formularul cu datele lui
        const res = await apiGet('list_products', { search: '' });
        if (res.success) {
            const p = res.data.products.find(x => x.id === productId);
            if (p) populateForm(p);
        }
    } else {
        title.textContent = 'Adaugă produs';
    }

    // deschidem panel-ul (clasa 'open' il face vizibil via CSS)
    panel.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden'; // blocam scroll-ul paginii
}

function closePanel() {
    document.getElementById('product-panel').classList.remove('open');
    document.getElementById('product-panel-overlay').classList.remove('open');
    document.body.style.overflow = ''; // deblocam scroll-ul
    document.getElementById('off-results').innerHTML = '';
}

// goleste toate campurile din formular
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

// populeaza formularul cu datele unui produs existent (pentru editare)
function populateForm(p) {
    document.getElementById('f-id').value           = p.id;
    document.getElementById('f-name').value          = p.name              ?? '';
    document.getElementById('f-brand').value         = p.brand             ?? '';
    document.getElementById('f-price').value         = p.price             ?? '';
    document.getElementById('f-volume').value        = p.volume_ml         ?? '';
    document.getElementById('f-image').value         = p.image_url         ?? '';
    document.getElementById('f-description').value   = p.description       ?? '';
    document.getElementById('f-ingredients').value   = p.ingredients       ?? '';
    document.getElementById('f-calories').value      = p.calories_per_100ml ?? '';
    document.getElementById('f-sugar').value         = p.sugar_per_100ml   ?? '';
    document.getElementById('f-shelf-life').value    = p.shelf_life_days   ?? '';
    document.getElementById('f-barcode').value       = p.barcode           ?? '';

    // !! converteste orice valoare la boolean (true/false)
    document.getElementById('f-vegan').checked       = !!p.is_vegan;
    document.getElementById('f-gluten-free').checked = !!p.is_gluten_free;
    document.getElementById('f-perishable').checked  = !!p.is_perishable;

    if (p.image_url) {
        document.getElementById('f-image-preview').innerHTML =
            `<img src="${esc(p.image_url)}" alt="">`;
    }
}

// randeaza butoanele toggle pentru categorii, alergeni, sezoane, regiuni
function renderFormSelects() {
    if (!formData) return; // daca nu avem datele inca, nu facem nimic

    renderMultiselect('f-categories', formData.categories, 'name');
    renderMultiselect('f-allergens',  formData.allergens,  'name');
    renderMultiselect('f-seasons',    formData.seasons,    'name');
    renderMultiselect('f-regions',    formData.regions,    'name');
}

// creeaza butoane toggle pentru un container (ex: categorii)
// containerId = id-ul div-ului unde punem butoanele
// items = array de obiecte (ex: [{id: 1, name: 'Ceaiuri'}, ...])
// labelKey = ce camp din obiect sa afisam ca text
function renderMultiselect(containerId, items, labelKey) {
    const wrap = document.getElementById(containerId);
    if (!wrap || !items) return;

    wrap.innerHTML = items.map(item => `
        <button type="button" class="multiselect-option" data-id="${item.id}">
            ${esc(item[labelKey])}
        </button>
    `).join('');

    // cand se apasa un buton, il marcam ca selectat (sau deselectat)
    wrap.addEventListener('click', e => {
        const btn = e.target.closest('.multiselect-option');
        if (btn) btn.classList.toggle('selected');
    });
}

// returneaza id-urile optiunilor selectate dintr-un multiselect
function getSelectedIds(containerId) {
    return [...document.querySelectorAll(`#${containerId} .multiselect-option.selected`)]
        .map(el => el.dataset.id);
}

// trimite formularul la server (creare sau actualizare)
async function saveProduct() {
    const id   = document.getElementById('f-id').value;
    const name = document.getElementById('f-name').value.trim();

    if (!name) { toast('Numele este obligatoriu.', true); return; }

    // colectam toate datele din formular
    const data = {
        name,
        brand:              document.getElementById('f-brand').value.trim(),
        price:              document.getElementById('f-price').value,
        volume_ml:          document.getElementById('f-volume').value,
        image_url:          document.getElementById('f-image').value.trim(),
        description:        document.getElementById('f-description').value.trim(),
        ingredients:        document.getElementById('f-ingredients').value.trim(),
        calories_per_100ml: document.getElementById('f-calories').value,
        sugar_per_100ml:    document.getElementById('f-sugar').value,
        shelf_life_days:    document.getElementById('f-shelf-life').value,
        barcode:            document.getElementById('f-barcode').value.trim(),
        // pentru checkboxuri: '1' daca e bifat, '' daca nu
        is_vegan:           document.getElementById('f-vegan').checked       ? '1' : '',
        is_gluten_free:     document.getElementById('f-gluten-free').checked  ? '1' : '',
        is_perishable:      document.getElementById('f-perishable').checked   ? '1' : '',
        // id-urile optiunilor selectate
        'category_ids[]':   getSelectedIds('f-categories'),
        'allergen_ids[]':   getSelectedIds('f-allergens'),
        'season_ids[]':     getSelectedIds('f-seasons'),
        'region_ids[]':     getSelectedIds('f-regions'),
    };

    // daca avem id = editam, daca nu = cream nou
    const action = id ? 'update_product' : 'create_product';
    if (id) data.id = id;

    const res = await apiPost(action, data);
    if (res.success) {
        toast(res.message);
        closePanel();
        await loadProducts(); // reincarcam tabelul cu datele noi
    } else {
        toast(res.message, true);
    }
}

// cauta un produs pe Open Food Facts si populeaza formularul automat
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

    // afisam rezultatele
    results.innerHTML = res.data.map((item, i) => `
        <div class="off-result-item" data-index="${i}">
            ${item.image_url ? `<img src="${esc(item.image_url)}" class="off-result-img" alt="">` : ''}
            <div>
                <div class="off-result-name">${esc(item.name)}</div>
                ${item.brand ? `<div class="off-result-brand">${esc(item.brand)}</div>` : ''}
            </div>
        </div>`).join('');

    // cand dai click pe un rezultat, il importam in formular
    results.querySelectorAll('.off-result-item').forEach((el, i) => {
        el.addEventListener('click', () => {
            const item = res.data[i];

            // populam campurile cu datele de la Open Food Facts
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

            // curatam rezultatele si inputul de cautare
            results.innerHTML = '';
            document.getElementById('off-search-input').value = '';
            toast('Produs importat din Open Food Facts!');
        });
    });
}

// ─────────────────────────────────────────────────────────────
// USERI
// ─────────────────────────────────────────────────────────────

let userPage = 1; // pagina curenta din tabelul de useri

// citim id-ul adminului logat din meta tag-ul pus in users.php
// asa stim sa dezactivam butoanele pe propriul rand
const currentUserId = parseInt(
    document.querySelector('meta[name="current-user-id"]')?.content ?? '0'
);

// verificam daca suntem pe pagina de useri
const usersTbody = document.getElementById('users-tbody');
if (usersTbody) {
    initUsersPage();
}

async function initUsersPage() {
    await loadUsers();
}

// incarca userii de la server si ii afiseaza in tabel
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

    // construim cate un rand pentru fiecare user
    usersTbody.innerHTML = users.map(u => {
        const initials = u.username.charAt(0).toUpperCase(); // prima litera din username
        const isMe     = u.id === currentUserId; // suntem pe propriul rand?
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
                    <!-- dropdown pentru schimbarea rolului, dezactivat pe propriul rand -->
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

    // schimbare rol instant la selectare din dropdown
    usersTbody.querySelectorAll('.role-select').forEach(sel => {
        sel.addEventListener('change', async () => {
            const userId = parseInt(sel.dataset.userId);
            const role   = sel.value;
            const res    = await apiPost('update_role', { user_id: userId, role });
            if (res.success) toast(`Rol actualizat: ${role}.`);
            else { toast(res.message, true); await loadUsers(); } // reincarcam daca a dat eroare
        });
    });

    // stergere user cu confirmare
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


// afiseaza butoanele de paginare sub tabel
// onPageChange = functia apelata cand userul schimba pagina
function renderPagination(containerId, currentPage, totalPages, onPageChange) {
    const wrap = document.getElementById(containerId);
    if (!wrap) return;

    // daca e o singura pagina, nu afisam nimic
    if (totalPages <= 1) { wrap.innerHTML = ''; return; }

    // buton "pagina anterioara"
    let html = `<button class="page-btn" ${currentPage <= 1 ? 'disabled' : ''}
                        data-page="${currentPage - 1}">←</button>`;

    // butoanele cu numerele paginilor
    for (let i = 1; i <= totalPages; i++) {
        // daca sunt multe pagini, sarim peste unele si punem "..."
        if (totalPages > 7 && Math.abs(i - currentPage) > 2 && i !== 1 && i !== totalPages) {
            if (i === 2 || i === totalPages - 1) {
                html += `<span style="color:#7a7d75;padding:0 .25rem">…</span>`;
            }
            continue;
        }
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}"
                         data-page="${i}">${i}</button>`;
    }

    // buton "pagina urmatoare"
    html += `<button class="page-btn" ${currentPage >= totalPages ? 'disabled' : ''}
                     data-page="${currentPage + 1}">→</button>`;

    wrap.innerHTML = html;

    // atasam click pe fiecare buton de pagina
    wrap.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', () => onPageChange(parseInt(btn.dataset.page)));
    });
}