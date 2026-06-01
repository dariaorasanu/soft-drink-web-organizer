document.addEventListener('DOMContentLoaded', () => {
    const productsGrid = document.getElementById('productsGrid');
    const searchInput = document.getElementById('catalogSearch');
    const categoryButtons = document.querySelectorAll('[data-category]');
    const veganFilter = document.getElementById('veganFilter');
    const glutenFreeFilter = document.getElementById('glutenFreeFilter');
    const seasonFilter = document.getElementById('seasonFilter');
    const regionFilter = document.getElementById('regionFilter');
    const catalogTitle = document.getElementById('catalogTitle');
    const catalogSubtitle = document.getElementById('catalogSubtitle');
    const prevPageButton = document.getElementById('prevPage');
    const nextPageButton = document.getElementById('nextPage');
    const pageInfo = document.getElementById('pageInfo');

    let currentCategory = '';
    let currentSearch = '';
    let currentPage = 1;
    let totalPages = 1;

    async function loadProducts() {
        const params = new URLSearchParams();
        params.set('action', 'list');
        params.set('page', currentPage.toString());
        params.set('limit', '12');

        if (currentCategory) {
            params.set('category', currentCategory);
        }

        if (currentSearch) {
            params.set('q', currentSearch);
        }

        if (veganFilter.checked) {
            params.set('vegan', '1');
        }

        if (glutenFreeFilter.checked) {
            params.set('gluten_free', '1');
        }

        if (seasonFilter.value) {
            params.set('season', seasonFilter.value);
        }

        if (regionFilter.value) {
            params.set('region', regionFilter.value);
        }

        productsGrid.innerHTML = `<p class="catalog-loading">Se încarcă produsele...</p>`;

        try {
            const response = await fetch(`/api/product.php?${params.toString()}`);
            const data = await response.json();

            if (!data.success) {
                productsGrid.innerHTML = `<div class="empty-state">Nu am putut încărca produsele.</div>`;
                return;
            }

            totalPages = data.pagination.total_pages || 1;
            renderProducts(data.products);
            updatePagination();
            updateTitle();
        } catch (error) {
            productsGrid.innerHTML = `<div class="empty-state">A apărut o eroare la încărcarea catalogului.</div>`;
        }
    }

    function renderProducts(products) {
        if (!products.length) {
            productsGrid.innerHTML = `
                <div class="empty-state">
                    <h2>Nu există produse de afișat.</h2>
                    <p>Încearcă alte filtre sau alt termen de căutare.</p>
                </div>
            `;
            return;
        }

        productsGrid.innerHTML = products.map(product => {
            const image = product.image_url
                ? `<img src="${product.image_url}" alt="${escapeHtml(product.name)}">`
                : `<span>🥤</span>`;

            const categories = Array.isArray(product.categories)
                ? product.categories.map(category => `<span class="product-badge">${escapeHtml(category.name)}</span>`).join('')
                : '';

            return `
                <article class="product-card">
                    <a class="product-card-link" href="/pages/product.php?slug=${encodeURIComponent(product.slug)}">
                        <div class="product-image">
                            ${image}
                        </div>

                        <div class="product-info">
                            <h2>${escapeHtml(product.name)}</h2>

                            ${product.brand ? `<p>${escapeHtml(product.brand)}</p>` : ''}
                            ${product.description ? `<p>${escapeHtml(product.description)}</p>` : ''}

                            <div class="product-meta">
                                <span>${Number(product.price).toFixed(2)} RON</span>
                                ${product.volume_ml ? `<span>${product.volume_ml} ml</span>` : ''}
                            </div>

                            <div class="product-badges">
                                ${categories}
                                ${product.is_vegan ? `<span class="product-badge">Vegan</span>` : ''}
                                ${product.is_gluten_free ? `<span class="product-badge">Fără gluten</span>` : ''}
                            </div>
                        </div>
                    </a>

                    <div class="product-actions">
                        <button 
                            type="button" 
                            class="favorite-btn ${product.is_favorite ? 'active' : ''}" 
                            data-product-id="${product.id}"
                        >
                            ${product.is_favorite ? '♥ Favorit' : '♡ Favorite'}
                        </button>
                        <button type="button" class="list-btn" data-product-id="${product.id}">
                            + Adaugă la listă
                        </button>
                    </div>
                </article>
            `;
        }).join('');
        attachFavoriteEvents();
    }

    function attachFavoriteEvents() {
        document.querySelectorAll('.favorite-btn').forEach(button => {
            button.addEventListener('click', async (event) => {
                event.preventDefault();

                const productId = button.dataset.productId;

                const formData = new FormData();
                formData.append('product_id', productId);

                button.disabled = true;

                try {
                    const response = await fetch('/api/product.php?action=toggle_favorite', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (!data.success) {
                        alert(data.message || 'Nu am putut actualiza favoritele.');
                        return;
                    }

                    if (data.is_favorite) {
                        button.textContent = '♥ Favorit';
                        button.classList.add('active');
                    } else {
                        button.textContent = '♡ Favorite';
                        button.classList.remove('active');
                    }
                } catch (error) {
                    alert('A apărut o eroare la favorite.');
                } finally {
                    button.disabled = false;
                }
            });
        });

        document.querySelectorAll('.list-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                e.stopPropagation();
                openListDropdown(btn, parseInt(btn.dataset.productId));
            });
        });
    }

    function updatePagination() {
        pageInfo.textContent = `Pagina ${currentPage} din ${totalPages}`;
        prevPageButton.disabled = currentPage <= 1;
        nextPageButton.disabled = currentPage >= totalPages;
    }

    function updateTitle() {
        const activeCategory = document.querySelector('[data-category].active');
        const categoryName = activeCategory ? activeCategory.textContent.trim() : 'Toate produsele';

        catalogTitle.textContent = categoryName || 'Toate produsele';

        if (currentSearch) {
            catalogSubtitle.innerHTML = `Rezultate pentru: <strong>${escapeHtml(currentSearch)}</strong>`;
        } else {
            catalogSubtitle.textContent = 'Produse filtrate din catalog.';
        }
    }

    function resetToFirstPageAndLoad() {
        currentPage = 1;
        loadProducts();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            currentCategory = button.dataset.category;
            resetToFirstPageAndLoad();
        });
    });

    searchInput.addEventListener('input', () => {
        currentSearch = searchInput.value.trim();
        resetToFirstPageAndLoad();
    });

    veganFilter.addEventListener('change', resetToFirstPageAndLoad);
    glutenFreeFilter.addEventListener('change', resetToFirstPageAndLoad);
    seasonFilter.addEventListener('change', resetToFirstPageAndLoad);
    regionFilter.addEventListener('change', resetToFirstPageAndLoad);

    prevPageButton.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadProducts();
        }
    });

    nextPageButton.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            loadProducts();
        }
    });

    loadProducts();
    // dropdown adauga la lista — se deschide sub butonul cardului
    let openDropdown = null;

    function closeAllDropdowns() {
        document.querySelectorAll('.catalog-list-dropdown').forEach(d => d.remove());
        openDropdown = null;
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('.list-btn') && !e.target.closest('.catalog-list-dropdown')) {
            closeAllDropdowns();
        }
    });

    async function openListDropdown(btn, productId) {
        // daca e deja deschis acelasi, inchidem
        if (openDropdown === btn) {
            closeAllDropdowns();
            return;
        }
        closeAllDropdowns();
        openDropdown = btn;

        const dropdown = document.createElement('div');
        dropdown.className = 'catalog-list-dropdown';
        dropdown.innerHTML = '<div class="catalog-atl-loading">Se incarca...</div>';

        // pozitionam dropdown-ul sub buton
        btn.parentElement.style.position = 'relative';
        btn.parentElement.appendChild(dropdown);

        try {
            const res  = await fetch('/api/shopping-list.php?action=my_lists');
            const data = await res.json();

            if (!data.success || !data.data.length) {
                dropdown.innerHTML = `
                <p class="catalog-atl-empty">Nicio lista inca.</p>
                <div class="catalog-atl-new">
                    <input type="text" class="catalog-atl-input" placeholder="Lista noua..." maxlength="200">
                    <button class="catalog-atl-create">+</button>
                </div>`;
            } else {
                dropdown.innerHTML = `
                <div class="catalog-atl-lists">
                    ${data.data.map(list => `
                        <button class="catalog-atl-row" data-list-id="${list.id}">
                            <span>${escapeHtml(list.name)}</span>
                            <span class="catalog-atl-count">${list.item_count} produse</span>
                        </button>
                    `).join('')}
                </div>
                <div class="catalog-atl-new">
                    <input type="text" class="catalog-atl-input" placeholder="Lista noua..." maxlength="200">
                    <button class="catalog-atl-create">+</button>
                </div>`;
            }

            // click pe o lista existenta
            dropdown.querySelectorAll('.catalog-atl-row').forEach(row => {
                row.addEventListener('click', () => addToList(parseInt(row.dataset.listId), productId, btn));
            });

            // creare lista noua si adaugare
            const input     = dropdown.querySelector('.catalog-atl-input');
            const createBtn = dropdown.querySelector('.catalog-atl-create');

            const createAndAdd = async () => {
                const name = input.value.trim();
                if (!name) { input.focus(); return; }
                const fd = new FormData();
                fd.append('name', name);
                const res  = await fetch('/api/shopping-list.php?action=create', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    await addToList(data.data.id, productId, btn);
                } else {
                    showCatalogToast(data.message ?? 'Eroare.', true);
                }
            };

            createBtn?.addEventListener('click', createAndAdd);
            input?.addEventListener('keydown', e => { if (e.key === 'Enter') createAndAdd(); });
            input?.focus();

        } catch {
            dropdown.innerHTML = '<p class="catalog-atl-empty">Eroare la incarcare.</p>';
        }
    }

    async function addToList(listId, productId, btn) {
        const fd = new FormData();
        fd.append('list_id',    listId);
        fd.append('product_id', productId);
        fd.append('quantity',   1);
        const res  = await fetch('/api/shopping-list.php?action=add_item', { method: 'POST', body: fd });
        const data = await res.json();
        closeAllDropdowns();
        showCatalogToast(data.success ? '✓ Adaugat in lista!' : (data.message ?? 'Eroare.'), !data.success);
    }

    function showCatalogToast(msg, isError = false) {
        const t = document.createElement('div');
        t.style.cssText = `position:fixed;bottom:2rem;right:2rem;background:#252824;
        border:1px solid ${isError ? '#f72585' : '#8df0c0'};
        color:${isError ? '#f72585' : '#f4f1ea'};
        border-radius:14px;padding:.9rem 1.4rem;font-size:.9rem;font-weight:700;
        z-index:9999;font-family:'Nunito',sans-serif;box-shadow:0 8px 32px rgba(0,0,0,.4);`;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }
});