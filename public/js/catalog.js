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
                        <button type="button" class="favorite-btn" data-product-id="${product.id}">
                            ♡ Favorite
                        </button>
                        <button type="button" class="list-btn" data-product-id="${product.id}">
                            + Adaugă la listă
                        </button>
                    </div>
                </article>
            `;
        }).join('');
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
});