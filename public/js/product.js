/**
 * product.js — Logica paginii de produs SOr
 */

const CATEGORY_EMOJI = {
    ceaiuri: '🍵', sucuri: '🍊', lactate: '🥛',
    siropuri: '🍓', ape: '💧', sezoniere: '✦',
};

const SEASON_LABEL = {
    spring: '🌸 Primăvară', summer: '☀️ Vară',
    autumn: '🍂 Toamnă',   winter: '❄️ Iarnă',
};

let productId          = null;
let selectedRating     = 0;
let ingredientsExpanded = false;

const show = id => document.getElementById(id)?.classList.remove('hidden');
const hide = id => document.getElementById(id)?.classList.add('hidden');
const el   = id => document.getElementById(id);
const setHtml = (id, html) => { const n = el(id); if (n) n.innerHTML = html; };

function detailRow(label, value, unit = '') {
    if (!value && value !== 0) return '';
    return `<div class="detail-row">
        <span class="detail-label">${label}</span>
        <span class="detail-value">${value}${unit}</span>
    </div>`;
}

function starsHtml(rating) {
    const r = Math.round(rating);
    return '★'.repeat(r) + '☆'.repeat(5 - r);
}

// ── Încarcă produsul ──────────────────────────────────────────────────────────
async function loadProduct(slug) {
    try {
        const res  = await fetch(`/api/product.php?action=get&slug=${encodeURIComponent(slug)}`);
        const data = await res.json();

        if (!data.success || !data.product) {
            hide('product-loading');
            show('product-error');
            return;
        }

        renderProduct(data.product);
        await loadRatings(data.product.id);

        fetch('/api/product.php?action=increment_view', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${data.product.id}`,
        });

    } catch (err) {
        console.error('Eroare:', err);
        hide('product-loading');
        show('product-error');
    }
}

// ── Randează produsul ─────────────────────────────────────────────────────────
function renderProduct(p) {
    productId = p.id;
    document.title = `SOr — ${p.name}`;

    // Hero imagine
    const heroImg = el('product-hero-img');
    if (p.image_url) {
        heroImg.style.backgroundImage = `url('${p.image_url}')`;
    } else {
        const category = p.categories?.[0]?.slug ?? '';
        heroImg.classList.add('emoji-fallback');
        heroImg.textContent = CATEGORY_EMOJI[category] || '🥤';
    }

    el('product-name').textContent = p.name ?? '';
    el('product-brand').textContent = p.brand ? `@ ${p.brand}` : '';

    // Rating mediu în hero
    if (p.average_rating) {
        setHtml('product-hero-rating',
            `<span class="hero-stars">${starsHtml(p.average_rating)}</span>
             <span class="hero-rating-val">${parseFloat(p.average_rating).toFixed(1)}</span>`
        );
    }

    // Badge-uri hero
    const badges = [];
    p.categories?.forEach(c => badges.push(`<span class="badge-category">${c.name}</span>`));
    if (p.is_vegan)       badges.push('<span class="badge-category badge-vegan">🌱 Vegan</span>');
    if (p.is_gluten_free) badges.push('<span class="badge-category badge-gluten">⚠ Fără gluten</span>');
    setHtml('product-badges', badges.join(''));

    // Preț
    setHtml('product-price', p.price
        ? `${parseFloat(p.price).toFixed(2)} <small>RON</small>`
        : '<span style="color:#7a7d75">Preț nedisponibil</span>'
    );

    // Favorite
    const favBtn = el('fav-btn');
    favBtn.dataset.productId = p.id;
    updateFavBtn(p.is_favorite, p.favorites_count);
    favBtn.addEventListener('click', toggleFavorite);

    // Detalii
    setHtml('detail-volume',    detailRow('Volum',        p.volume_ml,          ' ml'));
    setHtml('detail-calories',  detailRow('Calorii',      p.calories_per_100ml, '/100ml'));
    setHtml('detail-sugar',     detailRow('Zahăr',        p.sugar_per_100ml,    'g/100ml'));
    setHtml('detail-shelf',     detailRow('Valabilitate', p.shelf_life_days,    ' zile'));
    setHtml('detail-perishable', p.is_perishable
        ? `<div class="detail-row"><span class="detail-label">Perisabil</span><span class="badge-warn">⚠ Da</span></div>`
        : ''
    );

    // ── Valori nutriționale ───────────────────────────────────────────────────
    renderNutrition(p);

    // Alergeni
    if (p.allergens?.length) {
        setHtml('allergen-badges', p.allergens.map(a =>
            `<span class="badge-allergen">⚠ ${a.name}</span>`
        ).join(''));
        show('allergens-card');
    }

    // Sezoane & regiuni
    const availBadges = [];
    p.seasons?.forEach(s  => availBadges.push(`<span class="badge-season">${SEASON_LABEL[s.slug] ?? s.name}</span>`));
    p.regions?.forEach(r  => availBadges.push(`<span class="badge-region">📍 ${r.name}</span>`));
    if (availBadges.length) {
        setHtml('availability-badges', availBadges.join(''));
        show('availability-card');
    }

    // Ingrediente
    if (p.ingredients) {
        el('ingredients-text').textContent = p.ingredients;
        if (p.ingredients.length > 200) {
            show('btn-expand');
            el('btn-expand').addEventListener('click', () => {
                ingredientsExpanded = !ingredientsExpanded;
                el('ingredients-text').classList.toggle('collapsed', !ingredientsExpanded);
                el('btn-expand').textContent = ingredientsExpanded ? 'Arată mai puțin ↑' : 'Arată mai mult ↓';
            });
        } else {
            el('ingredients-text').classList.remove('collapsed');
        }
    } else {
        const p2 = document.createElement('p');
        p2.style.color = '#7a7d75';
        p2.style.fontStyle = 'italic';
        p2.textContent = 'Lista de ingrediente nu este disponibilă.';
        el('ingredients-text').replaceWith(p2);
    }

    // Localuri
    if (p.venues?.length) {
        setHtml('venues-list', p.venues.map(v => `
            <div class="venue-item">
                <div>
                    <div class="venue-name">${v.name}</div>
                    <div class="venue-address">${v.address ?? ''}</div>
                </div>
                ${v.price ? `<div class="venue-price">${parseFloat(v.price).toFixed(2)} RON</div>` : ''}
            </div>
        `).join(''));
        show('venues-section');
    }

    hide('product-loading');
    show('product-content');
}

// ── Valori nutriționale ───────────────────────────────────────────────────────
function renderNutrition(p) {
    const rows = [];

    // Date din DB
    if (p.calories_per_100ml)  rows.push({ label: '🔥 Calorii',    value: `${p.calories_per_100ml} kcal` });
    if (p.sugar_per_100ml)     rows.push({ label: '🍬 Zahăr',       value: `${p.sugar_per_100ml} g` });

    // Date din Open Food Facts (dacă există)
    if (p.nutriments) {
        const n = p.nutriments;
        if (n.fat)              rows.push({ label: '🧈 Grăsimi',     value: `${n.fat} g` });
        if (n.saturated_fat)    rows.push({ label: '↳ din care saturate', value: `${n.saturated_fat} g` });
        if (n.carbohydrates)    rows.push({ label: '🌾 Carbohidrați', value: `${n.carbohydrates} g` });
        if (n.proteins)         rows.push({ label: '💪 Proteine',     value: `${n.proteins} g` });
        if (n.salt)             rows.push({ label: '🧂 Sare',         value: `${n.salt} g` });
        if (n.fiber)            rows.push({ label: '🌿 Fibre',        value: `${n.fiber} g` });

        // Sursa Open Food Facts
        const src = el('nutrition-source');
        if (src) {
            src.innerHTML = '📦 Date preluate de pe <a href="https://world.openfoodfacts.org" target="_blank">Open Food Facts</a>';
            src.classList.remove('hidden');
        }
    }

    if (rows.length) {
        setHtml('nutrition-table', rows.map(r => `
            <div class="nutrition-row">
                <span class="nutrition-label">${r.label}</span>
                <span class="nutrition-value">${r.value}</span>
            </div>
        `).join(''));
        show('nutrition-section');
    }
}

// ── Favorite ──────────────────────────────────────────────────────────────────
function updateFavBtn(isFav, count) {
    const btn   = el('fav-btn');
    const icon  = btn.querySelector('.fav-icon');
    const label = btn.querySelector('.fav-label');
    btn.classList.toggle('active', isFav);
    icon.textContent  = isFav ? '♥' : '♡';
    label.textContent = isFav ? 'Elimină din favorite' : 'Adaugă la favorite';
    setHtml('fav-count', count ? `${count} persoane au adăugat la favorite` : '');
}

async function toggleFavorite() {
    if (!productId) return;
    try {
        const res  = await fetch('/api/product.php?action=toggle_favorite', {
            method: 'POST',
            body: new URLSearchParams({ product_id: productId }),
        });
        const data = await res.json();
        if (data.success) updateFavBtn(data.is_favorite, data.favorites_count);
    } catch (err) { console.error('Eroare favorite:', err); }
}

// ── Rating stele ──────────────────────────────────────────────────────────────
function initStars() {
    const stars = document.querySelectorAll('#stars-input .star');
    stars.forEach(star => {
        star.addEventListener('mouseover', () => {
            const val = parseInt(star.dataset.value);
            stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.value) <= val));
        });
        star.addEventListener('mouseleave', () => {
            stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.value) <= selectedRating));
        });
        star.addEventListener('click', () => {
            selectedRating = parseInt(star.dataset.value);
            stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.value) <= selectedRating));
        });
    });
}

async function submitRating() {
    const msg = el('rating-msg');
    if (!selectedRating) {
        msg.textContent = 'Selectează un număr de stele.';
        msg.className = 'rating-msg error';
        show('rating-msg');
        return;
    }
    try {
        const res  = await fetch('/api/product.php?action=rate', {
            method: 'POST',
            body: new URLSearchParams({ product_id: productId, rating: selectedRating, review: el('review-text').value.trim() }),
        });
        const data = await res.json();
        if (data.success) {
            msg.textContent = '✓ Recenzia a fost trimisă!';
            msg.className = 'rating-msg success';
            show('rating-msg');
            el('review-text').value = '';
            selectedRating = 0;
            document.querySelectorAll('#stars-input .star').forEach(s => s.classList.remove('active'));
            renderReviews(data.ratings);
        } else {
            msg.textContent = data.message ?? 'Eroare la trimitere.';
            msg.className = 'rating-msg error';
            show('rating-msg');
        }
    } catch (err) {
        msg.textContent = 'Eroare la trimitere.';
        msg.className = 'rating-msg error';
        show('rating-msg');
    }
}

// ── Recenzii ──────────────────────────────────────────────────────────────────
async function loadRatings(id) {
    try {
        const res  = await fetch(`/api/product.php?action=get_ratings&product_id=${id}`);
        const data = await res.json();
        if (data.success && data.ratings?.length) renderReviews(data.ratings);
    } catch (err) { console.error('Eroare recenzii:', err); }
}

function renderReviews(ratings) {
    if (!ratings?.length) {
        setHtml('reviews-list', '<p class="no-reviews">Nicio recenzie încă. Fii primul!</p>');
        return;
    }
    setHtml('reviews-list', ratings.map(r => {
        const initial = (r.username ?? 'U')[0].toUpperCase();
        const date    = r.created_at
            ? new Date(r.created_at).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' })
            : '';
        return `
        <div class="review-item">
            <div class="review-header">
                <div class="review-avatar">${initial}</div>
                <div class="review-meta">
                    <div class="review-username">${r.username}</div>
                    <div class="review-date">${date}</div>
                </div>
                <div class="review-stars">${starsHtml(r.rating)}</div>
            </div>
            ${r.review ? `<div class="review-text">${r.review}</div>` : ''}
        </div>`;
    }).join(''));
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const slug = new URLSearchParams(window.location.search).get('slug');
    if (!slug) { hide('product-loading'); show('product-error'); return; }
    initStars();
    el('btn-submit-rating')?.addEventListener('click', submitRating);
    loadProduct(slug);
});