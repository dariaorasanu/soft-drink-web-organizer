document.addEventListener('DOMContentLoaded', () => {
    loadSvgChart('topProductsChart', 'top_products', 10);
    loadSvgChart('categoryDistributionChart', 'category_distribution', 10);
    loadSvgChart('avgRatingChart', 'avg_rating', 10);
    loadSvgChart('productsOverTimeChart', 'products_over_time', 12);
    loadMostFavorited();
});

async function loadSvgChart(containerId, metric, limit = 10) {
    const container = document.getElementById(containerId);
    if (!container) {
        return;
    }

    try {
        const response = await fetch(`/api/stats.php?action=export_svg&metric=${metric}&limit=${limit}`);

        if (!response.ok) {
            throw new Error('Graficul nu a putut fi încărcat.');
        }

        container.innerHTML = await response.text();
    } catch (error) {
        container.innerHTML = `<p class="stats-error">${escapeHtml(error.message)}</p>`;
    }
}

async function loadMostFavorited() {
    const container = document.getElementById('mostFavoritedList');
    if (!container) {
        return;
    }

    try {
        const response = await fetch('/api/stats.php?action=most_favorited&limit=5');
        const payload = await response.json();

        if (!payload.success) {
            throw new Error(payload.message || 'Produsele favorite nu au putut fi încărcate.');
        }

        if (!payload.data.length) {
            container.innerHTML = '<p class="stats-muted">Nu există produse favorite încă.</p>';
            return;
        }

        container.innerHTML = payload.data.map((product, index) => {
            const name = escapeHtml(product.name);
            const brand = product.brand ? escapeHtml(product.brand) : 'Brand necunoscut';
            const price = product.price !== null ? `${Number(product.price).toFixed(2)} lei` : 'Preț indisponibil';
            const favorites = Number(product.favorites_count || 0);

            return `
                <a class="favorite-row" href="/pages/product.php?slug=${encodeURIComponent(product.slug)}">
                    <span class="favorite-rank">${index + 1}</span>
                    <span class="favorite-info">
                        <strong>${name}</strong>
                        <small>${brand} · ${price}</small>
                    </span>
                    <span class="favorite-count">♡ ${favorites}</span>
                </a>
            `;
        }).join('');
    } catch (error) {
        container.innerHTML = `<p class="stats-error">${escapeHtml(error.message)}</p>`;
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}