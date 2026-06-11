<?php
session_start();

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var AuthGuard $guard */
/** @var UserService $userService */

$guard->requireAuth();

$currentUser = $userService->getCurrentUser();
$username    = $currentUser?->username ?? 'Utilizator';
$initials    = strtoupper(substr($username, 0, 1));

$title      = 'SOr — Statistici';
$extraCss   = ['/public/css/stats.css'];
$extraJs    = ['/public/js/stats.js'];
$activePage = 'stats';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
?>

    <div class="home-page stats-page">
        <main class="hero">
            <section class="welcome stats-hero">
                <p class="eyebrow">Statistici</p>
                <h1>Statistici și exporturi</h1>
                <p class="subtitle">
                    Vezi cele mai populare produse, distribuția pe categorii și exportă datele aplicației.
                </p>

                <div class="stats-actions">
                    <a class="stats-action" href="/api/stats.php?action=export_csv">⬇ Export CSV</a>
                    <a class="stats-action" href="/api/stats.php?action=export_json">⬇ Export JSON</a>
                    <a class="stats-action" href="/api/stats.php?action=export_svg&metric=top_products" target="_blank">⬇ Export SVG</a>
                    <a class="stats-action rss" href="/api/rss.php" target="_blank">📡 RSS</a>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stats-card stats-card-wide">
                    <div class="stats-card-header">
                        <div>
                            <p class="stats-label">Vizualizări</p>
                            <h2>Top produse</h2>
                        </div>
                        <span class="stats-badge">SVG</span>
                    </div>
                    <div id="topProductsChart" class="chart-box">
                        <p>Se încarcă graficul...</p>
                    </div>
                </article>

                <article class="stats-card stats-card-wide">
                    <div class="stats-card-header">
                        <div>
                            <p class="stats-label">Categorii</p>
                            <h2>Distribuția produselor</h2>
                        </div>
                        <span class="stats-badge">SVG</span>
                    </div>
                    <div id="categoryDistributionChart" class="chart-box">
                        <p>Se încarcă graficul...</p>
                    </div>
                </article>

                <article class="stats-card">
                    <div class="stats-card-header">
                        <div>
                            <p class="stats-label">Favorite</p>
                            <h2>Cele mai favorite</h2>
                        </div>
                        <span class="stats-badge">Top 5</span>
                    </div>
                    <div id="mostFavoritedList" class="favorites-list">
                        <p>Se încarcă produsele...</p>
                    </div>
                </article>

                <article class="stats-card">
                    <div class="stats-card-header">
                        <div>
                            <p class="stats-label">Rating</p>
                            <h2>Rating mediu pe categorii</h2>
                        </div>
                        <span class="stats-badge">SVG</span>
                    </div>
                    <div id="avgRatingChart" class="chart-box small-chart">
                        <p>Se încarcă graficul...</p>
                    </div>
                </article>

                <article class="stats-card stats-card-wide">
                    <div class="stats-card-header">
                        <div>
                            <p class="stats-label">Evoluție</p>
                            <h2>Produse adăugate în timp</h2>
                        </div>
                        <span class="stats-badge">SVG</span>
                    </div>
                    <div id="productsOverTimeChart" class="chart-box">
                        <p>Se încarcă graficul...</p>
                    </div>
                </article>
            </section>
        </main>
    </div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>