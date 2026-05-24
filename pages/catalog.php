<?php
session_start();

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var AuthGuard $guard */
/** @var UserService $userService */

$guard->requireAuth();

$currentUser = $userService->getCurrentUser();
$username    = $currentUser?->username ?? 'Utilizator';
$initials    = strtoupper(substr($username, 0, 1));

$title      = 'SOr — Explorează produse';
$extraCss   = ['/public/css/catalog.css'];
$extraJs    = ['/public/js/catalog.js'];
$activePage = 'catalog';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
?>

<div class="home-page">
    <main class="hero">
        <section class="welcome">
            <p class="eyebrow">Explorează</p>
            <h1 id="catalogTitle">Toate produsele</h1>
            <p class="subtitle" id="catalogSubtitle">Toate produsele disponibile în catalog.</p>

            <div class="search-box">
                <span class="search-icon">⌕</span>
                <input id="catalogSearch" type="search" placeholder="Caută un produs, ingredient, marcă sau local...">
            </div>
        </section>

        <section class="catalog-layout">
            <aside class="catalog-filters">
                <h2>Filtre</h2>

                <div class="filter-group">
                    <p>Categorii</p>
                    <button type="button" class="category-pill active" data-category="">▦ Toate</button>
                    <button type="button" class="category-pill" data-category="ceaiuri">🍵 Ceaiuri</button>
                    <button type="button" class="category-pill" data-category="sucuri">🍊 Sucuri</button>
                    <button type="button" class="category-pill" data-category="lactate">🥛 Lactate</button>
                    <button type="button" class="category-pill" data-category="siropuri">🍓 Siropuri</button>
                    <button type="button" class="category-pill" data-category="ape">〰 Ape</button>
                    <button type="button" class="category-pill" data-category="sezoniere">✦ Sezonier</button>
                </div>

                <div class="filter-group">
                    <label><input type="checkbox" id="veganFilter"> Vegan</label>
                    <label><input type="checkbox" id="glutenFreeFilter"> Fără gluten</label>
                </div>

                <div class="filter-group">
                    <label for="seasonFilter">Sezon</label>
                    <select id="seasonFilter">
                        <option value="">Toate</option>
                        <option value="spring">Primăvară</option>
                        <option value="summer">Vară</option>
                        <option value="autumn">Toamnă</option>
                        <option value="winter">Iarnă</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="regionFilter">Regiune</label>
                    <select id="regionFilter">
                        <option value="">Toate</option>
                        <option value="Moldova">Moldova</option>
                        <option value="Muntenia">Muntenia</option>
                        <option value="Transilvania">Transilvania</option>
                        <option value="Dobrogea">Dobrogea</option>
                        <option value="Basarabia">Basarabia</option>
                        <option value="Bavaria">Bavaria</option>
                        <option value="Toscana">Toscana</option>
                    </select>
                </div>
            </aside>

            <section class="catalog-content">
                <div id="productsGrid" class="catalog-grid">
                    <p class="catalog-loading">Se încarcă produsele...</p>
                </div>

                <div class="pagination">
                    <button type="button" id="prevPage">← Anterior</button>
                    <span id="pageInfo">Pagina 1</span>
                    <button type="button" id="nextPage">Următor →</button>
                </div>
            </section>
        </section>
    </main>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
