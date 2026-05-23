<?php
session_start();

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var AuthGuard $guard */

$guard->requireAuth();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Explorează produse</title>
    <link rel="stylesheet" href="/public/css/home.css">
    <link rel="stylesheet" href="/public/css/catalog.css">
</head>
<body>

<div class="home-page">
    <header class="topbar">
        <a href="/pages/home.php" class="brand">
            <span class="brand-main">S<span>O</span>r</span>
            <span class="brand-subtitle">Soft Drink Organizer</span>
        </a>

        <nav class="nav-menu">
            <a href="/pages/home.php" class="nav-link">Acasă</a>
            <a href="/pages/catalog.php" class="nav-link active">Explorează</a>
            <a href="/pages/shopping-list.php" class="nav-link">Listele mele</a>
            <a href="#" class="nav-link disabled">Clasament</a>
            <a href="#" class="nav-link disabled">Statistici</a>
        </nav>
    </header>

    <main class="hero">
        <section class="welcome">
            <p class="eyebrow">Explorează</p>
            <h1 id="catalogTitle">Toate produsele</h1>

            <p class="subtitle" id="catalogSubtitle">
                Toate produsele disponibile în catalog.
            </p>

            <div class="search-box">
                <span class="search-icon">⌕</span>
                <input
                        id="catalogSearch"
                        type="search"
                        placeholder="Caută un produs, ingredient, marcă sau local..."
                >
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
                    <label>
                        <input type="checkbox" id="veganFilter">
                        Vegan
                    </label>

                    <label>
                        <input type="checkbox" id="glutenFreeFilter">
                        Fără gluten
                    </label>
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

<script src="/public/js/catalog.js"></script>
</body>
</html>