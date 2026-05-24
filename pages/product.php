<?php
session_start();

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var AuthGuard $guard */
/** @var UserService $userService */

$guard->requireAuth();

$currentUser = $userService->getCurrentUser();
$username    = $currentUser?->username ?? 'Utilizator';
$initials    = strtoupper(substr($username, 0, 1));

$title      = 'SOr — Produs';
$extraCss   = ['/public/css/product.css'];
$extraJs    = ['/public/js/product.js'];
$activePage = 'catalog';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
?>

    <div class="home-page">
        <div id="product-page">

            <!-- Loading -->
            <div class="product-loading" id="product-loading">
                <div class="loading-spinner"></div>
                <p>Se încarcă produsul…</p>
            </div>

            <!-- Eroare -->
            <div class="product-error hidden" id="product-error">
                <p class="eyebrow">404</p>
                <h1>Produsul nu a fost găsit</h1>
                <a href="/pages/catalog.php" class="btn-back">← Înapoi la catalog</a>
            </div>

            <!-- Conținut produs -->
            <div class="product-content hidden" id="product-content">

                <!-- Buton back -->
                <a href="/pages/catalog.php" class="btn-back-top">← Înapoi la catalog</a>

                <!-- Hero banner -->
                <div class="product-hero" id="product-hero">
                    <div class="product-hero-img" id="product-hero-img"></div>
                    <div class="product-hero-overlay">
                        <div class="product-hero-badges" id="product-badges"></div>
                        <h1 class="product-hero-title" id="product-name"></h1>
                        <div class="product-hero-meta">
                            <p class="product-hero-brand" id="product-brand"></p>
                            <div class="product-hero-rating" id="product-hero-rating"></div>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="product-body">

                    <!-- Sidebar stânga -->
                    <aside class="product-sidebar">

                        <!-- Preț + favorite -->
                        <div class="product-price-card">
                            <div class="product-price" id="product-price"></div>
                            <button class="fav-btn" id="fav-btn">
                                <span class="fav-icon">♡</span>
                                <span class="fav-label">Adaugă la favorite</span>
                            </button>
                            <div class="fav-count" id="fav-count"></div>
                        </div>

                        <!-- Detalii rapide -->
                        <div class="product-details-card">
                            <h3>Detalii</h3>
                            <div id="detail-volume"    class="detail-row"></div>
                            <div id="detail-calories"  class="detail-row"></div>
                            <div id="detail-sugar"     class="detail-row"></div>
                            <div id="detail-shelf"     class="detail-row"></div>
                            <div id="detail-perishable" class="detail-row"></div>
                        </div>

                        <!-- Alergeni -->
                        <div class="product-allergens-card hidden" id="allergens-card">
                            <h3>⚠ Alergeni</h3>
                            <div class="allergen-badges" id="allergen-badges"></div>
                        </div>

                        <!-- Disponibilitate -->
                        <div class="product-availability-card hidden" id="availability-card">
                            <h3>Disponibil în</h3>
                            <div class="availability-badges" id="availability-badges"></div>
                        </div>

                    </aside>

                    <!-- Main dreapta -->
                    <main class="product-main">

                        <!-- Valori nutriționale -->
                        <section class="product-section hidden" id="nutrition-section">
                            <h2>📊 Valori nutriționale</h2>
                            <p class="nutrition-per">Per 100ml</p>
                            <div class="nutrition-table" id="nutrition-table"></div>
                            <p class="nutrition-source hidden" id="nutrition-source"></p>
                        </section>

                        <!-- Ingrediente -->
                        <section class="product-section" id="ingredients-section">
                            <h2>Ingrediente</h2>
                            <div class="ingredients-text collapsed" id="ingredients-text"></div>
                            <button class="btn-expand hidden" id="btn-expand">Arată mai mult ↓</button>
                        </section>

                        <!-- Localuri -->
                        <section class="product-section hidden" id="venues-section">
                            <h2>📍 Unde îl găsești</h2>
                            <div class="venues-list" id="venues-list"></div>
                        </section>

                        <!-- Adaugă recenzie -->
                        <section class="product-section" id="rating-section">
                            <h2>Lasă o recenzie</h2>
                            <div class="stars-input" id="stars-input">
                                <span class="star" data-value="1">★</span>
                                <span class="star" data-value="2">★</span>
                                <span class="star" data-value="3">★</span>
                                <span class="star" data-value="4">★</span>
                                <span class="star" data-value="5">★</span>
                            </div>
                            <textarea id="review-text" placeholder="Scrie ceva despre acest produs… (opțional)"></textarea>
                            <button class="btn-submit" id="btn-submit-rating">Trimite recenzia</button>
                            <p class="rating-msg hidden" id="rating-msg"></p>
                        </section>

                        <!-- Recenzii existente -->
                        <section class="product-section" id="reviews-section">
                            <h2>Recenzii</h2>
                            <div class="reviews-list" id="reviews-list">
                                <p class="no-reviews">Nicio recenzie încă. Fii primul!</p>
                            </div>
                        </section>

                    </main>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>