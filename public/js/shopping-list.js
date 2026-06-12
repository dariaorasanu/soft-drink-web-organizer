const API = '/api/shopping-list.php';

async function apiPost(action, params = {}) {
    const fd = new FormData();
    for (const [k, v] of Object.entries(params)) fd.append(k, v);
    const res = await fetch(`${API}?action=${action}`, { method: 'POST', body: fd });
    return res.json();
}

async function apiGet(action, params = {}) {
    const qs = new URLSearchParams({ action, ...params }).toString();
    const res = await fetch(`${API}?${qs}`);
    return res.json();
}

//afisam notificarile
function toast(msg, isError = false) {
    const el = document.createElement('div');
    el.className = 'sl-toast' + (isError ? ' error' : '');
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3200);
}

// escape HTML pentru XSS safe
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}


function formatPrice(val) {
    if (val === null || val === undefined) return '—';
    return Number(val).toFixed(2).replace('.', ',') + ' RON';
}


function productEmoji(name = '') {
    const n = name.toLowerCase();
    if (n.includes('ceai') || n.includes('tea'))     return '🍵';
    if (n.includes('suc') || n.includes('juice'))    return '🥤';
    if (n.includes('lapte') || n.includes('milk'))   return '🥛';
    if (n.includes('apa') || n.includes('water'))    return '💧';
    if (n.includes('sirop'))                         return '🍯';
    if (n.includes('cafea') || n.includes('coffee')) return '☕';
    return '🥤';
}


let currentListId = null;
let currentListData = null;
let allItems = [];   // itemele listei curente


const sidebar         = document.getElementById('sl-sidebar');
const listsContainer  = document.getElementById('sl-lists-container');
const itemsContainer  = document.getElementById('sl-items-container');
const emptyState      = document.getElementById('sl-empty-state');
const listHeader      = document.getElementById('sl-list-header');
const listNameEl      = document.getElementById('sl-list-name');
const progressFill    = document.getElementById('sl-progress-fill');
const progressLabel   = document.getElementById('sl-progress-label');
const slFooter        = document.getElementById('sl-footer');
const totalValueEl    = document.getElementById('sl-total-value');
const newListInput    = document.getElementById('sl-new-list-name');
const btnCreate       = document.getElementById('sl-btn-create');
const btnShare        = document.getElementById('sl-btn-share');
const btnClear        = document.getElementById('sl-btn-clear');
const btnExport       = document.getElementById('sl-btn-export');
const mobileFab       = document.getElementById('sl-mobile-fab');
const mobileClose     = document.getElementById('sl-mobile-close');

// modale
const shareModal      = document.getElementById('share-modal');
const shareModalClose = document.getElementById('share-modal-close');
const shareLinkInput  = document.getElementById('share-link-input');
const shareLinkCopy   = document.getElementById('share-link-copy');
const shareDisableBtn = document.getElementById('share-disable-btn');
const confirmModal    = document.getElementById('confirm-modal');
const confirmTitle    = document.getElementById('confirm-modal-title');
const confirmDesc     = document.getElementById('confirm-modal-desc');
const confirmCancel   = document.getElementById('confirm-cancel');
const confirmOk       = document.getElementById('confirm-ok');


//shared view, interactiv cu polling la 3 secunde
const sharedContent = document.getElementById('shared-content');
if (sharedContent) {
    const token = sharedContent.dataset.token;
    let sharedItems=[];
    let pollingActive = true;

    (async () => {
        await loadSharedView();
        startPolling();
    })();

    // incarca toate datele si randeaza pagina
    async function loadSharedView() {
        const res = await apiGet('shared_view', { token });
        if (!res.success) {
            sharedContent.innerHTML = `
                <div class="shared-empty">
                    <span class="shared-empty-icon">⚠️</span>
                    <h2>Eroare</h2>
                    <p>${esc(res.message)}</p>
                </div>`;
            pollingActive = false;
            return;
        }
        sharedItems = res.data.items;
        renderSharedItems();
    }

    // randam lista de iteme interactiva
    function renderSharedItems() {
        const hasPrice = sharedItems.some(i => i.product_price !== null);
        const total    = sharedItems.reduce((sum, i) => sum + (i.line_total ?? 0), 0);

        const itemsHtml = sharedItems.length === 0
            ? '<p style="color:#7a7d75;text-align:center;padding:3rem">Lista este goala.</p>'
            : sharedItems.map(item => {
                const imgHtml = item.product_image
                    ? `<img src="${esc(item.product_image)}" alt="" class="shared-item-img" loading="lazy">`
                    : `<div class="shared-item-emoji">${productEmoji(item.product_name)}</div>`;
                return `
                    <div class="shared-item${item.is_purchased ? ' purchased' : ''}" id="sv-item-${item.id}">
                        <!-- checkbox interactiv -->
                        <div class="sv-checkbox${item.is_purchased ? ' checked' : ''}"
                             data-item-id="${item.id}"
                             role="checkbox"
                             aria-checked="${item.is_purchased}"
                             tabindex="0"
                             title="${item.is_purchased ? 'Debifaza' : 'Marcheaza ca cumparat'}">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        ${imgHtml}
                        <div class="shared-item-info">
                            <p class="shared-item-name">${esc(item.product_name)}</p>
                            ${item.product_brand ? `<p class="shared-item-brand">${esc(item.product_brand)}</p>` : ''}
                        </div>
                        <div class="shared-item-right">
                            <div class="shared-item-qty">× ${item.quantity}</div>
                            ${item.line_total ? `<div class="shared-item-price">${formatPrice(item.line_total)}</div>` : ''}
                        </div>
                    </div>`;
            }).join('');

        sharedContent.innerHTML = `
            <div class="sv-live-badge" id="sv-live-badge">
                <span class="sv-live-dot"></span> Live
            </div>
            <div class="sv-progress-wrap">
                <div class="sv-progress-bar">
                    <div class="sv-progress-fill" id="sv-progress-fill"></div>
                </div>
                <span class="sv-progress-label" id="sv-progress-label"></span>
            </div>
            <div class="shared-items-list" id="sv-items-list">
                ${itemsHtml}
            </div>
            ${hasPrice ? `
            <div class="shared-total">
                <span class="shared-total-label">Total estimat</span>
                <span class="shared-total-value" id="sv-total">${formatPrice(total)}</span>
            </div>` : ''}`;

        updateSharedProgress();
        attachSharedCheckboxes();
    }

    // actualizeaza bara de progres
    function updateSharedProgress() {
        const total     = sharedItems.length;
        const purchased = sharedItems.filter(i => i.is_purchased).length;
        const pct       = total > 0 ? Math.round((purchased / total) * 100) : 0;

        const fill  = document.getElementById('sv-progress-fill');
        const label = document.getElementById('sv-progress-label');
        if (fill)  fill.style.width      = pct + '%';
        if (label) label.textContent     = `${purchased} / ${total} cumparate`;
    }

    // ataseaza listener-ele pe checkbox-uri
    function attachSharedCheckboxes() {
        document.querySelectorAll('.sv-checkbox').forEach(cb => {
            cb.addEventListener('click',   () => toggleSharedItem(cb));
            cb.addEventListener('keydown', e => {
                if (e.key === ' ' || e.key === 'Enter') toggleSharedItem(cb);
            });
        });

        // swipe pe mobile pentru fiecare item
        document.querySelectorAll('.shared-item').forEach(item => {
            let touchX = 0;

            item.addEventListener('touchstart', e => {
                touchX = e.touches[0].clientX;
            }, { passive: true });

            item.addEventListener('touchend', e => {
                const diff = e.changedTouches[0].clientX - touchX;
                if (Math.abs(diff) < 60) return;
                const itemId = parseInt(item.id.replace('sv-item-', ''));
                const cb     = document.querySelector(`.sv-checkbox[data-item-id="${itemId}"]`);
                if (!cb) return;

                const currentlyPurchased = cb.classList.contains('checked');
                const shouldBePurchased  = diff > 0;

                if (currentlyPurchased === shouldBePurchased) return;
                toggleSharedItem(cb);
            }, { passive: true });
        });
    }

    async function toggleSharedItem(cb) {
        const itemId    = parseInt(cb.dataset.itemId);
        const purchased = !cb.classList.contains('checked');

        // feedback vizual instant
        applySharedItemState(itemId, purchased);

        const fd = new FormData();
        fd.append('token',     token);
        fd.append('item_id',   itemId);
        fd.append('purchased', purchased ? 1 : 0);

        const res = await fetch(`${API}?action=shared_mark`, { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.success) {
            // daca a esuat, revenim la starea anterioara
            applySharedItemState(itemId, !purchased);
            alert(data.message ?? 'Eroare.');
        } else {
            // actualizam in memorie
            const item = sharedItems.find(i => i.id === itemId);
            if (item) item.is_purchased = purchased;
            updateSharedProgress();
            updateSharedTotal();
        }
    }

    // aplica starea vizuala pe un item (fara re-render complet)
    function applySharedItemState(itemId, purchased) {
        const card = document.getElementById(`sv-item-${itemId}`);
        const cb   = document.querySelector(`.sv-checkbox[data-item-id="${itemId}"]`);
        card?.classList.toggle('purchased', purchased);
        cb?.classList.toggle('checked', purchased);
        cb?.setAttribute('aria-checked', purchased);
    }

    // actualizeaza totalul din footer
    function updateSharedTotal() {
        const totalEl = document.getElementById('sv-total');
        if (!totalEl) return;
        const total = sharedItems.reduce((sum, i) => sum + (i.line_total ?? 0), 0);
        totalEl.textContent = formatPrice(total);
    }

    // polling la 3 secunde, verifica daca s a schimbat ceva
    function startPolling() {
        setInterval(async () => {
            if (!pollingActive)
                return;
            const res = await apiGet('shared_view', { token });
            if (!res.success) return;
            const newItems = res.data.items;

            let changed = false;
            for (const newItem of newItems) {
                const old = sharedItems.find(i => i.id === newItem.id);
                if (!old || old.is_purchased !== newItem.is_purchased) {
                    changed = true;
                    break;
                }
            }

            if (!changed)
                return;

            for (const newItem of newItems) {
                const old = sharedItems.find(i => i.id === newItem.id);
                if (old && old.is_purchased !== newItem.is_purchased) {
                    old.is_purchased = newItem.is_purchased;
                    applySharedItemState(newItem.id, newItem.is_purchased);
                }
            }

            updateSharedProgress();
            updateSharedTotal();

            // clipim badge-ul Live ca sa se vada ca s-a actualizat
            const badge = document.getElementById('sv-live-badge');
            badge?.classList.add('pulse');
            setTimeout(() => badge?.classList.remove('pulse'), 600);

        }, 3000);
    }
}

//pagina privata
if (sidebar) {
    init();
}

async function init() {
    await loadLists();
    bindEvents();
}

//incarcarea listelor in sidebar
async function loadLists() {
    listsContainer.innerHTML = '<div class="sl-loading"><div class="spinner"></div></div>';
    const res = await apiGet('my_lists');
    if (!res.success) {
        listsContainer.innerHTML = `<p style="color:#f72585;padding:1rem;font-size:.85rem">${esc(res.message)}</p>`;
        return;
    }
    if (res.data.length === 0) {
        listsContainer.innerHTML = '<p style="color:#7a7d75;padding:1rem;font-size:.85rem;text-align:center">Nicio lista inca.<br>Creeaza una jos!</p>';
        return;
    }
    listsContainer.innerHTML = res.data.map(list => renderListRow(list)).join('');
    if (currentListId) {
        document.querySelector(`.sl-list-item[data-id="${currentListId}"]`)?.classList.add('active');
    }
}

// randam randul unei liste in sidebar cu mood si budget
function renderListRow(list) {
    const shared   = list.is_shared ? `<span class="sl-shared-dot" title="Partajata"></span>` : '';
    const moodInfo = MOODS[list.mood] ?? MOODS['general'];
    return `
        <div class="sl-list-item mood-${esc(list.mood ?? 'general')}"
             data-id="${list.id}"
             data-name="${esc(list.name)}"
             data-shared="${list.is_shared ? '1' : '0'}"
             data-token="${esc(list.share_token ?? '')}"
             data-mood="${esc(list.mood ?? 'general')}"
             data-budget="${list.budget ?? ''}">
            <span class="sl-list-icon">${moodInfo.icon}</span>
            <div class="sl-list-info">
                <span class="sl-list-item-name" data-id="${list.id}">${esc(list.name)}</span>
                <div class="sl-list-meta">
                    <span class="sl-list-count">${list.item_count} ${list.item_count === 1 ? 'produs' : 'produse'}</span>
                    ${list.budget ? `<span class="sl-list-budget-badge">${formatPrice(list.budget)}</span>` : ''}
                    ${shared}
                </div>
            </div>
            <div class="sl-list-actions">
                <button class="sl-action-btn" data-action="rename" data-id="${list.id}" title="Redenumeste">✎</button>
                <button class="sl-action-btn danger" data-action="delete-list" data-id="${list.id}" title="Sterge">🗑</button>
            </div>
        </div>`;
}

async function loadItems(listId) {
    currentListId = listId;
    const listEl  = document.querySelector(`.sl-list-item[data-id="${listId}"]`);

    // citim si mood-ul si bugetul din data attributes
    currentListData = {
        id:          listId,
        name:        listEl?.dataset.name   ?? '',
        is_shared:   listEl?.dataset.shared === '1',
        share_token: listEl?.dataset.token  ?? null,
        mood:        listEl?.dataset.mood   ?? 'general',
        budget:      listEl?.dataset.budget ? parseFloat(listEl.dataset.budget) : null,
    };

    document.querySelectorAll('.sl-list-item').forEach(el => el.classList.remove('active'));
    listEl?.classList.add('active');

    emptyState.style.display = 'none';
    listHeader.style.display = 'flex';
    slFooter.style.display   = 'block';
    listNameEl.textContent   = currentListData.name;

    // aplicam mood-ul curent pe pagina
    applyMood(currentListData.mood);

    itemsContainer.innerHTML = '<div class="sl-loading"><div class="spinner"></div></div>';

    const res = await apiGet('items', { list_id: listId });
    if (!res.success) {
        itemsContainer.innerHTML = `<p style="color:#f72585;padding:2rem;text-align:center">${esc(res.message)}</p>`;
        return;
    }

    allItems = res.data;
    renderItems();
    updateBudgetTracker();
    closeSidebar();
}

function renderItems() {
    updateProgress();
    updateTotal();

    if (allItems.length === 0) {
        itemsContainer.innerHTML = `
            <div class="sl-empty-state" style="padding:3rem">
                <span class="sl-empty-icon">📋</span>
                <h3>Lista este goala</h3>
                <p>Adauga produse din <a href="/pages/catalog.php" style="color:#8df0c0;text-decoration:none;font-weight:700">catalog</a>.</p>
            </div>`;
        return;
    }
    itemsContainer.innerHTML = allItems.map(renderItemCard).join('');
}

function renderItemCard(item) {
    const purchased = item.is_purchased;
    const imgHtml   = item.product_image
        ? `<img src="${esc(item.product_image)}" alt="" class="sl-item-img" loading="lazy">`
        : `<div class="sl-item-emoji">${productEmoji(item.product_name)}</div>`;

    const unitPrice = item.product_price ? formatPrice(item.product_price) : null;
    const lineTotal = item.line_total     ? formatPrice(item.line_total)    : null;
    const priceHtml = lineTotal
        ? `<div class="sl-item-price">${lineTotal}</div>
           ${item.quantity > 1 && unitPrice ? `<div class="sl-item-price-sub">${unitPrice} / buc</div>` : ''}`
        : '';

    const notesHtml = `
        <div class="sl-item-notes-wrap">
            <button class="sl-item-notes-toggle" data-item-id="${item.id}">
                ${item.notes ? '📝 Notita' : '+ Adauga notita'}
            </button>
            <textarea class="sl-item-notes-text" data-item-id="${item.id}"
                      placeholder="Notita (ex: brand alternativ, varianta fara zahar…)"
                      style="display:none">${esc(item.notes ?? '')}</textarea>
        </div>`;

    return `
        <div class="sl-item-card${purchased ? ' purchased' : ''}" data-item-id="${item.id}" id="item-${item.id}">
            <div class="sl-checkbox-wrap">
                <div class="sl-checkbox${purchased ? ' checked' : ''}" data-item-id="${item.id}" role="checkbox" aria-checked="${purchased}" tabindex="0">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            ${imgHtml}
            <div class="sl-item-info">
                <p class="sl-item-name">${esc(item.product_name)}</p>
                ${item.product_brand ? `<p class="sl-item-brand">${esc(item.product_brand)}</p>` : ''}
                ${notesHtml}
            </div>
            <div class="sl-item-right">
                <div class="sl-item-stepper">
                    <button class="sl-stepper-btn" data-action="decrement" data-item-id="${item.id}">−</button>
                    <input type="number" class="sl-stepper-qty" value="${item.quantity}" min="1" max="99" data-item-id="${item.id}" aria-label="Cantitate">
                    <button class="sl-stepper-btn" data-action="increment" data-item-id="${item.id}">+</button>
                </div>
                ${priceHtml}
                <button class="sl-item-remove" data-action="remove-item" data-item-id="${item.id}" title="Sterge">✕</button>
            </div>
        </div>`;
}

function updateProgress() {
    const total     = allItems.length;
    const purchased = allItems.filter(i => i.is_purchased).length;
    const pct       = total > 0 ? Math.round((purchased / total) * 100) : 0;
    progressFill.style.width  = pct + '%';
    progressLabel.textContent = `${purchased} / ${total} cumparate`;
}


function updateTotal() {
    const total = allItems.reduce((sum, item) => {
        if (item.line_total) return sum + parseFloat(item.line_total);
        return sum;
    }, 0);
    totalValueEl.textContent = formatPrice(total);
    updateBudgetTracker();
}

function bindEvents() {
    // click pe randul unei liste
    listsContainer.addEventListener('click', async e => {
        const row       = e.target.closest('.sl-list-item');
        const actionBtn = e.target.closest('[data-action]');

        if (actionBtn) {
            const { action, id } = actionBtn.dataset;
            e.stopPropagation();
            if (action === 'rename') {
                startInlineRename(parseInt(id));
            } else if (action === 'delete-list') {
                confirmAction(
                    'Sterge lista',
                    'Esti sigur? Toate produsele din ea vor fi sterse.',
                    async () => {
                        const res = await apiPost('delete', { list_id: id });
                        if (res.success) {
                            if (currentListId === parseInt(id)) {
                                currentListId   = null;
                                currentListData = null;
                                allItems        = [];
                                listHeader.style.display = 'none';
                                slFooter.style.display   = 'none';
                                itemsContainer.innerHTML = '';
                                emptyState.style.display = 'flex';
                                applyMood('general');
                            }
                            await loadLists();
                            toast('Lista stearsa.');
                        } else {
                            toast(res.message, true);
                        }
                    }
                );
            }
            return;
        }
        if (row) loadItems(parseInt(row.dataset.id));
    });

    // creare lista noua
    btnCreate?.addEventListener('click', createList);
    newListInput?.addEventListener('keydown', e => { if (e.key === 'Enter') createList(); });

    // click pe containerul de iteme (event delegation)
    itemsContainer.addEventListener('click', async e => {

        // checkbox
        const checkbox = e.target.closest('.sl-checkbox');
        if (checkbox) {
            const itemId    = parseInt(checkbox.dataset.itemId);
            const purchased = !checkbox.classList.contains('checked');
            await togglePurchased(itemId, purchased);
            return;
        }

        // stepper
        const stepBtn = e.target.closest('[data-action="increment"], [data-action="decrement"]');
        if (stepBtn) {
            const itemId = parseInt(stepBtn.dataset.itemId);
            const inc    = stepBtn.dataset.action === 'increment';
            const item   = allItems.find(i => i.id === itemId);
            if (!item) return;
            const newQty = inc ? item.quantity + 1 : Math.max(1, item.quantity - 1);
            await updateQuantity(itemId, newQty, item.notes);
            return;
        }

        // notita toggle
        const notesToggle = e.target.closest('.sl-item-notes-toggle');
        if (notesToggle) {
            const itemId   = parseInt(notesToggle.dataset.itemId);
            const textarea = document.querySelector(`.sl-item-notes-text[data-item-id="${itemId}"]`);
            if (textarea) {
                const visible = textarea.style.display !== 'none';
                textarea.style.display = visible ? 'none' : 'block';
                if (!visible) textarea.focus();
            }
            return;
        }

        // stergere item
        const removeBtn = e.target.closest('[data-action="remove-item"]');
        if (removeBtn) {
            const itemId = parseInt(removeBtn.dataset.itemId);
            await removeItem(itemId);
            return;
        }
    });

    // salvare notita la blur
    itemsContainer.addEventListener('blur', async e => {
        const textarea = e.target.closest('.sl-item-notes-text');
        if (textarea) {
            const itemId = parseInt(textarea.dataset.itemId);
            const item   = allItems.find(i => i.id === itemId);
            if (!item) return;
            const notes = textarea.value.trim() || null;
            if (notes !== item.notes) await updateQuantity(itemId, item.quantity, notes);
        }
    }, true);

    // stepper input direct
    itemsContainer.addEventListener('change', async e => {
        const input = e.target.closest('.sl-stepper-qty');
        if (input) {
            const itemId = parseInt(input.dataset.itemId);
            const item   = allItems.find(i => i.id === itemId);
            if (!item) return;
            const newQty = Math.max(1, parseInt(input.value) || 1);
            await updateQuantity(itemId, newQty, item.notes);
        }
    });

    // header buttons
    btnShare?.addEventListener('click', openShareModal);
    btnClear?.addEventListener('click', clearPurchased);
    btnExport?.addEventListener('click', exportCsv);

    // mobile sidebar
    mobileFab?.addEventListener('click', openSidebar);
    mobileClose?.addEventListener('click', closeSidebar);

    // share modal
    shareModalClose?.addEventListener('click', () => { shareModal.style.display = 'none'; });
    shareModal?.addEventListener('click', e => { if (e.target === shareModal) shareModal.style.display = 'none'; });
    shareLinkCopy?.addEventListener('click', copyShareLink);
    shareDisableBtn?.addEventListener('click', disableShare);

    // confirm modal
    confirmCancel?.addEventListener('click', () => { confirmModal.style.display = 'none'; });
    confirmModal?.addEventListener('click', e => { if (e.target === confirmModal) confirmModal.style.display = 'none'; });

    // escape inchide modalele
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            shareModal.style.display   = 'none';
            confirmModal.style.display = 'none';
            closeSidebar();
        }
    });
}

//actiuni pe liste

// creeaza lista noua cu mood-ul selectat din picker
async function createList() {
    const name         = newListInput.value.trim();
    if (!name) { newListInput.focus(); return; }

    const selectedMood = document.querySelector('.mood-option.selected')?.dataset.mood ?? 'general';
    const res          = await apiPost('create', { name, mood: selectedMood });

    if (res.success) {
        newListInput.value = '';
        // resetam mood picker-ul la general
        document.querySelectorAll('.mood-option').forEach(b => b.classList.remove('selected'));
        document.querySelector('.mood-option[data-mood="general"]')?.classList.add('selected');
        await loadLists();
        toast('Lista creata!');
        loadItems(res.data.id);
    } else {
        toast(res.message, true);
    }
}

function startInlineRename(listId) {
    const nameEl = document.querySelector(`.sl-list-item-name[data-id="${listId}"]`);
    if (!nameEl) return;

    const original = nameEl.textContent;
    nameEl.classList.add('editing');
    nameEl.contentEditable = 'true';
    nameEl.focus();

    const range = document.createRange();
    range.selectNodeContents(nameEl);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);

    const save = async () => {
        nameEl.contentEditable = 'false';
        nameEl.classList.remove('editing');
        const newName = nameEl.textContent.trim();
        if (!newName || newName === original) { nameEl.textContent = original; return; }
        const res = await apiPost('rename', { list_id: listId, name: newName });
        if (res.success) {
            await loadLists();
            if (currentListId === listId) listNameEl.textContent = newName;
            toast('Redenumit.');
        } else {
            nameEl.textContent = original;
            toast(res.message, true);
        }
    };

    nameEl.addEventListener('blur',    save, { once: true });
    nameEl.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); nameEl.blur(); } }, { once: true });
}

//actiuni pe iteme
async function togglePurchased(itemId, purchased) {
    const res = await apiPost('mark_purchased', { item_id: itemId, purchased: purchased ? 1 : 0 });
    if (res.success) {
        const item = allItems.find(i => i.id === itemId);
        if (item) item.is_purchased = purchased;
        const card     = document.getElementById(`item-${itemId}`);
        const checkbox = document.querySelector(`.sl-checkbox[data-item-id="${itemId}"]`);
        card?.classList.toggle('purchased', purchased);
        checkbox?.classList.toggle('checked', purchased);
        checkbox?.setAttribute('aria-checked', purchased);
        updateProgress();
    }
}

async function updateQuantity(itemId, quantity, notes) {
    const res = await apiPost('update_item', { item_id: itemId, quantity, notes: notes ?? '' });
    if (res.success) {
        const idx = allItems.findIndex(i => i.id === itemId);
        if (idx !== -1) {
            allItems[idx] = res.data;
            const card = document.getElementById(`item-${itemId}`);
            if (card) {
                const newCard = document.createElement('div');
                newCard.innerHTML = renderItemCard(res.data);
                card.replaceWith(newCard.firstElementChild);
            }
            updateTotal();
        }
    } else {
        toast(res.message, true);
    }
}

async function removeItem(itemId) {
    const res = await apiPost('remove_item', { item_id: itemId });
    if (res.success) {
        allItems = allItems.filter(i => i.id !== itemId);
        document.getElementById(`item-${itemId}`)?.remove();
        updateProgress();
        updateTotal();
        if (allItems.length === 0) renderItems();
        await loadLists();
        document.querySelector(`.sl-list-item[data-id="${currentListId}"]`)?.classList.add('active');
    } else {
        toast(res.message, true);
    }
}

async function clearPurchased() {
    if (!currentListId) return;
    const count = allItems.filter(i => i.is_purchased).length;
    if (count === 0) { toast('Niciun produs marcat ca cumparat.'); return; }
    confirmAction(
        'Sterge cumparatele',
        `Vrei sa stergi ${count} produs${count === 1 ? '' : 'e'} marcate ca cumparate?`,
        async () => {
            const res = await apiPost('clear_purchased', { list_id: currentListId });
            if (res.success) {
                await loadItems(currentListId);
                await loadLists();
                document.querySelector(`.sl-list-item[data-id="${currentListId}"]`)?.classList.add('active');
                toast(`${res.data.deleted_count} item(e) sterse.`);
            } else {
                toast(res.message, true);
            }
        }
    );
}

//share
async function openShareModal() {
    if (!currentListData) return;
    if (!currentListData.is_shared) {
        const res = await apiPost('share', { list_id: currentListData.id });
        if (!res.success) { toast(res.message, true); return; }
        currentListData.is_shared   = true;
        currentListData.share_token = res.data.share_token;
        const row = document.querySelector(`.sl-list-item[data-id="${currentListData.id}"]`);
        if (row) { row.dataset.shared = '1'; row.dataset.token = res.data.share_token; }
        await loadLists();
        document.querySelector(`.sl-list-item[data-id="${currentListId}"]`)?.classList.add('active');
    }
    const link = `${location.origin}/pages/shopping-list.php?token=${encodeURIComponent(currentListData.share_token)}`;
    shareLinkInput.value     = link;
    shareModal.style.display = 'flex';
}

function copyShareLink() {
    navigator.clipboard.writeText(shareLinkInput.value).then(() => {
        shareLinkCopy.classList.add('copied');
        shareLinkCopy.textContent = 'Copiat!';
        setTimeout(() => {
            shareLinkCopy.classList.remove('copied');
            shareLinkCopy.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke="currentColor" stroke-width="2"/></svg> Copiaza`;
        }, 2000);
    }).catch(() => {
        shareLinkInput.select();
        document.execCommand('copy');
        toast('Link copiat!');
    });
}

async function disableShare() {
    if (!currentListData) return;
    const res = await apiPost('unshare', { list_id: currentListData.id });
    if (res.success) {
        currentListData.is_shared   = false;
        currentListData.share_token = null;
        shareModal.style.display    = 'none';
        await loadLists();
        document.querySelector(`.sl-list-item[data-id="${currentListId}"]`)?.classList.add('active');
        toast('Partajare dezactivata.');
    } else {
        toast(res.message, true);
    }
}

//export csv
function exportCsv() {
    if (!allItems.length) {
        toast('Lista este goala.');
        return;
    }
    const rows = [['Produs', 'Brand', 'Cantitate', 'Pret unitar', 'Total', 'Cumparat', 'Notita']];
    for (const item of allItems) {
        rows.push([
            item.product_name  ?? '',
            item.product_brand ?? '',
            item.quantity,
            item.product_price ?? '',
            item.line_total    ?? '',
            item.is_purchased  ? 'da' : 'nu',
            item.notes         ?? '',
        ]);
    }
    const csv  = rows.map(r => r.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `lista-${(currentListData?.name ?? 'cumparaturi').replace(/\s+/g, '-')}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    toast('CSV descarcat.');
}


let _confirmCallback = null;

function confirmAction(title, desc, callback) {
    confirmTitle.textContent   = title;
    confirmDesc.textContent    = desc;
    confirmModal.style.display = 'flex';
    _confirmCallback           = callback;
}

confirmOk?.addEventListener('click', async () => {
    confirmModal.style.display = 'none';
    if (_confirmCallback) {
        await _confirmCallback();
        _confirmCallback = null;
    }
});


function openSidebar()  { sidebar?.classList.add('open'); }
function closeSidebar() { sidebar?.classList.remove('open'); }

//mod cumparaturi
const shopOverlay = document.getElementById('shop-mode-overlay');
const smCardInner = document.getElementById('sm-card-inner');
const smCounter   = document.getElementById('sm-counter');
const smProgFill  = document.getElementById('sm-progress-fill');
const smBtnSkip   = document.getElementById('sm-btn-skip');
const smBtnCheck  = document.getElementById('sm-btn-check');
const smBtnClose  = document.getElementById('sm-close');
const smQtyVal    = document.getElementById('sm-qty-val');
const smQtyDec    = document.getElementById('sm-qty-dec');
const smQtyInc    = document.getElementById('sm-qty-inc');
const smDone      = document.getElementById('sm-done');
const smDoneSub   = document.getElementById('sm-done-sub');
const smDoneTotal = document.getElementById('sm-done-total');
const smDoneBtn   = document.getElementById('sm-done-btn');
const btnShopMode = document.getElementById('sl-btn-shop-mode');

let smIndex      = 0;
let smQueue      = [];
let smBought     = 0;
let smTotalSpent = 0;
let smCurrentQty = 1;
let smAnimating  = false;

function openShopMode() {
    if (!allItems.length) { toast('Lista este goala.'); return; }
    smQueue      = [...allItems.filter(i => !i.is_purchased), ...allItems.filter(i => i.is_purchased)];
    smIndex      = 0;
    smBought     = 0;
    smTotalSpent = 0;
    smDone.style.display = 'none';
    document.querySelector('.sm-stage').style.display   = 'flex';
    document.querySelector('.sm-actions').style.display = 'flex';
    shopOverlay.style.display    = 'flex';
    document.body.style.overflow = 'hidden';
    btnShopMode?.classList.add('active');
    renderSmCard();
}

function closeShopMode() {
    shopOverlay.style.display    = 'none';
    document.body.style.overflow = '';
    btnShopMode?.classList.remove('active');
}

function renderSmCard() {
    if (smIndex >= smQueue.length) { showSmDone(); return; }
    const item = smQueue[smIndex];
    smCurrentQty         = item.quantity;
    smQtyVal.textContent = smCurrentQty;
    updateSmCounter();
    const imgHtml = item.product_image
        ? `<img src="${esc(item.product_image)}" alt="" class="sm-card-img" loading="lazy">`
        : `<div class="sm-card-emoji">${productEmoji(item.product_name)}</div>`;
    smCardInner.innerHTML = `
        ${item.is_purchased ? '<span class="sm-card-purchased-badge">Deja cumparat</span>' : ''}
        ${imgHtml}
        <h2 class="sm-card-name">${esc(item.product_name)}</h2>
        ${item.product_brand ? `<p class="sm-card-brand">${esc(item.product_brand)}</p>` : ''}
        ${item.product_price ? `<div class="sm-card-price">${formatPrice(item.product_price * smCurrentQty)}</div>` : ''}
        ${item.notes ? `<div class="sm-card-notes">${esc(item.notes)}</div>` : ''}`;
    smCardInner.style.animation = 'none';
    smCardInner.offsetHeight;
    smCardInner.style.animation = '';
}

function updateSmCounter() {
    const total            = smQueue.length;
    smCounter.textContent  = `${smIndex + 1} / ${total}`;
    smProgFill.style.width = `${(smIndex / total) * 100}%`;
}

function smAdvance(dir) {
    if (smAnimating) return;
    smAnimating = true;
    smCardInner.classList.add(dir === 'left' ? 'exit-left' : 'exit-right');
    setTimeout(() => {
        smCardInner.classList.remove('exit-left', 'exit-right');
        smIndex++;
        smAnimating = false;
        smIndex >= smQueue.length ? showSmDone() : renderSmCard();
    }, 250);
}

function smSkip() { smAdvance('left'); }

async function smCheck() {
    if (smAnimating) return;
    const item = smQueue[smIndex];
    smBtnCheck.classList.add('pressed');
    setTimeout(() => smBtnCheck.classList.remove('pressed'), 300);
    if (smCurrentQty !== item.quantity) {
        await apiPost('update_item', { item_id: item.id, quantity: smCurrentQty, notes: item.notes ?? '' });
        const idx = allItems.findIndex(i => i.id === item.id);
        if (idx !== -1) allItems[idx].quantity = smCurrentQty;
    }
    if (!item.is_purchased) {
        await apiPost('mark_purchased', { item_id: item.id, purchased: 1 });
        const idx = allItems.findIndex(i => i.id === item.id);
        if (idx !== -1) allItems[idx].is_purchased = true;
        smQueue[smIndex].is_purchased = true;
        smBought++;
        if (item.product_price) smTotalSpent += item.product_price * smCurrentQty;
    }
    smAdvance('right');
}

function showSmDone() {
    smDone.style.display = 'flex';
    document.querySelector('.sm-stage').style.display   = 'none';
    document.querySelector('.sm-actions').style.display = 'none';
    smProgFill.style.width = '100%';
    smCounter.textContent  = `${smQueue.length} / ${smQueue.length}`;
    const total = smQueue.length;
    smDoneSub.textContent   = `Ai cumparat ${smBought} din ${total} produs${total === 1 ? '' : 'e'}.`;
    smDoneTotal.textContent = smTotalSpent > 0 ? `Total cheltuit: ${formatPrice(smTotalSpent)}` : '';
    renderItems(); updateProgress(); updateTotal();
    loadLists().then(() => {
        document.querySelector(`.sl-list-item[data-id="${currentListId}"]`)?.classList.add('active');
    });
}

btnShopMode?.addEventListener('click', openShopMode);
smBtnClose?.addEventListener('click',  closeShopMode);
smBtnSkip?.addEventListener('click',   smSkip);
smBtnCheck?.addEventListener('click',  smCheck);
smDoneBtn?.addEventListener('click',   closeShopMode);

smQtyDec?.addEventListener('click', () => {
    smCurrentQty = Math.max(1, smCurrentQty - 1);
    smQtyVal.textContent = smCurrentQty;
    const p = smCardInner.querySelector('.sm-card-price');
    const price = smQueue[smIndex]?.product_price;
    if (p && price) p.textContent = formatPrice(price * smCurrentQty);
});
smQtyInc?.addEventListener('click', () => {
    smCurrentQty = Math.min(99, smCurrentQty + 1);
    smQtyVal.textContent = smCurrentQty;
    const p = smCardInner.querySelector('.sm-card-price');
    const price = smQueue[smIndex]?.product_price;
    if (p && price) p.textContent = formatPrice(price * smCurrentQty);
});

// taste pentru mod cumparaturi
document.addEventListener('keydown', e => {
    if (!shopOverlay || shopOverlay.style.display === 'none') return;
    if (e.key === 'ArrowLeft')  smSkip();
    if (e.key === 'ArrowRight') smCheck();
});

// swipe pe mobile
let smTouchX = 0;
shopOverlay?.addEventListener('touchstart', e => { smTouchX = e.touches[0].clientX; }, { passive: true });
shopOverlay?.addEventListener('touchend', e => {
    if (smDone.style.display !== 'none') return;
    const diff = e.changedTouches[0].clientX - smTouchX;
    if (Math.abs(diff) > 60) diff < 0 ? smSkip() : smCheck();
}, { passive: true });

//mood + buget traker

// definitia mood-urilor cu icon, label si culorile de accent
const MOODS = {
    general:   { icon: '🛒', label: 'General',   accent: '#8df0c0', glow: 'rgba(141,240,192,0.15)' },
    picnic:    { icon: '☀️',  label: 'Picnic',    accent: '#ffd166', glow: 'rgba(255,209,102,0.15)' },
    acasa:     { icon: '🏠',  label: 'Acasa',     accent: '#a8dadc', glow: 'rgba(168,218,220,0.15)' },
    petrecere: { icon: '🎉',  label: 'Petrecere', accent: '#f72585', glow: 'rgba(247,37,133,0.15)'  },
    sport:     { icon: '💪',  label: 'Sport',     accent: '#06d6a0', glow: 'rgba(6,214,160,0.15)'   },
    birou:     { icon: '📚',  label: 'Birou',     accent: '#b5838d', glow: 'rgba(181,131,141,0.15)' },
};

// aplicam accentul de culoare al mood-ului pe pagina via CSS variables
function applyMood(mood) {
    const m = MOODS[mood] ?? MOODS['general'];
    document.documentElement.style.setProperty('--mood-accent', m.accent);
    document.documentElement.style.setProperty('--mood-glow',   m.glow);
    const badge = document.getElementById('sl-mood-badge');
    if (badge) {
        badge.textContent       = `${m.icon} ${m.label}`;
        badge.style.borderColor = m.accent + '55';
        badge.style.color       = m.accent;
    }
}

// actualizam buget tracker-ul vizual, verde/galben/rosu dupa cat am cheltuit
function updateBudgetTracker() {
    const tracker = document.getElementById('sl-budget-tracker');
    if (!tracker) return;

    const budget = currentListData?.budget;
    if (!budget) { tracker.style.display = 'none'; return; }

    const spent = allItems.reduce((sum, item) => {
        return sum + (item.product_price ? item.product_price * item.quantity : 0);
    }, 0);

    const pct   = Math.min((spent / budget) * 100, 100);
    const over  = spent > budget;
    const close = pct >= 80 && !over;

    tracker.style.display = 'flex';

    const fill  = tracker.querySelector('.sl-budget-fill');
    const label = tracker.querySelector('.sl-budget-label');

    fill.style.width = pct + '%';
    fill.className   = 'sl-budget-fill' + (over ? ' over' : close ? ' close' : '');

    label.textContent = over
        ? `Depasit cu ${formatPrice(spent - budget)}`
        : `${formatPrice(spent)} din ${formatPrice(budget)}`;
    label.className = 'sl-budget-label' + (over ? ' over' : close ? ' close' : '');
}

// randam mood picker-ul in sidebar la initializare
function renderMoodPicker() {
    const container = document.getElementById('sl-mood-picker');
    if (!container) return;
    container.innerHTML = Object.entries(MOODS).map(([key, m]) => `
        <button class="mood-option${key === 'general' ? ' selected' : ''}" data-mood="${key}" title="${m.label}">
            <span class="mood-icon">${m.icon}</span>
            <span class="mood-label">${m.label}</span>
        </button>
    `).join('');
    container.querySelectorAll('.mood-option').forEach(btn => {
        btn.addEventListener('click', () => {
            container.querySelectorAll('.mood-option').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        });
    });
}

// initializam input-ul de buget din header
function initBudgetInput() {
    const editBtn     = document.getElementById('sl-budget-edit');
    const budgetWrap  = document.getElementById('sl-budget-input-wrap');
    const budgetInput = document.getElementById('sl-budget-input');
    const budgetSave  = document.getElementById('sl-budget-save');
    const budgetClear = document.getElementById('sl-budget-clear');

    editBtn?.addEventListener('click', () => {
        budgetWrap.style.display = budgetWrap.style.display === 'none' ? 'flex' : 'none';
        if (budgetWrap.style.display === 'flex') {
            budgetInput.value = currentListData?.budget ?? '';
            budgetInput.focus();
        }
    });

    const saveBudget = async () => {
        const val    = parseFloat(budgetInput.value);
        const budget = val > 0 ? val : null;
        const res    = await apiPost('set_budget', { list_id: currentListId, budget: budget ?? '' });
        if (res.success) {
            currentListData.budget = budget;
            const row = document.querySelector(`.sl-list-item[data-id="${currentListId}"]`);
            if (row) row.dataset.budget = budget ?? '';
            budgetWrap.style.display = 'none';
            updateBudgetTracker();
            await loadLists();
            document.querySelector(`.sl-list-item[data-id="${currentListId}"]`)?.classList.add('active');
            toast(budget ? `Buget setat: ${formatPrice(budget)}` : 'Buget sters.');
        } else {
            toast(res.message, true);
        }
    };

    budgetSave?.addEventListener('click', saveBudget);
    budgetClear?.addEventListener('click', async () => { budgetInput.value = ''; await saveBudget(); });
    budgetInput?.addEventListener('keydown', e => { if (e.key === 'Enter') saveBudget(); });
}

// initializam butoanele de schimbare mood din header
function initMoodChange() {
    document.querySelectorAll('.sl-mood-change-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!currentListId) return;
            const mood = btn.dataset.mood;
            const res  = await apiPost('set_mood', { list_id: currentListId, mood });
            if (res.success) {
                currentListData.mood = mood;
                const row = document.querySelector(`.sl-list-item[data-id="${currentListId}"]`);
                if (row) row.dataset.mood = mood;
                applyMood(mood);
                await loadLists();
                document.querySelector(`.sl-list-item[data-id="${currentListId}"]`)?.classList.add('active');
            }
        });
    });
}

// initializare la incarcarea paginii
renderMoodPicker();
initBudgetInput();
initMoodChange();
applyMood('general');