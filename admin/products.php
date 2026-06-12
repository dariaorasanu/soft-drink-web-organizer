<?php

/**
 * admin/products.php
 * Tabel produse cu CRUD complet, import/export CSV, căutare live.
 */

session_start();
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var AuthGuard   $guard */
/** @var UserService $userService */

$guard->requireAdmin();

$currentUser = $userService->getCurrentUser();
$username    = $currentUser?->username ?? 'Admin';
$initials    = strtoupper(substr($username, 0, 1));

$title      = 'SOr — Admin Produse';
$extraCss   = ['/public/css/admin.css'];
$extraJs    = ['/public/js/admin.js'];
$activePage = 'admin';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
?>

    <main class="admin-layout">

        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <span class="admin-sidebar-title">Admin Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="/admin/index.php"    class="admin-nav-link">
                    <span class="admin-nav-icon">📊</span> Dashboard
                </a>
                <a href="/admin/products.php" class="admin-nav-link active">
                    <span class="admin-nav-icon">🥤</span> Produse
                </a>
                <a href="/admin/users.php"    class="admin-nav-link">
                    <span class="admin-nav-icon">👥</span> Utilizatori
                </a>
            </nav>
            <div class="admin-sidebar-footer">
                <a href="/pages/home.php" class="admin-back-link">← Înapoi la site</a>
            </div>
        </aside>

        <!-- continut -->
        <section class="admin-content">

            <div class="admin-page-header">
                <h1 class="admin-page-title">Produse</h1>
                <div class="admin-page-actions">
                    <button class="btn-primary" id="btn-add-product">+ Adaugă produs</button>
                    <a href="/api/admin.php?action=export_csv"  class="btn-secondary">⬇ CSV</a>
                    <a href="/api/admin.php?action=export_json" class="btn-secondary">⬇ JSON</a>
                    <label class="btn-secondary" style="cursor:pointer">
                        ⬆ Import CSV
                        <input type="file" id="import-csv-input" accept=".csv" style="display:none">
                    </label>
                </div>
            </div>

            <!-- bara cautare -->
            <div class="admin-search-bar">
                <input type="text" id="product-search" class="admin-search-input"
                       placeholder="Caută după nume, brand, descriere…" autocomplete="off">
            </div>

            <!-- tabel produse -->
            <div class="admin-card">
                <div class="admin-table-wrap">
                    <table class="admin-table" id="products-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagine</th>
                            <th>Nume</th>
                            <th>Brand</th>
                            <th>Preț</th>
                            <th>Vizualizări</th>
                            <th>Acțiuni</th>
                        </tr>
                        </thead>
                        <tbody id="products-tbody">
                        <tr><td colspan="7" class="admin-table-loading">
                                <div class="spinner"></div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- paginare -->
                <div class="admin-pagination" id="products-pagination"></div>
            </div>

        </section>
    </main>

    <!-- panel lateral formular produs -->
    <div class="admin-panel-overlay" id="product-panel-overlay"></div>
    <aside class="admin-panel" id="product-panel">
        <div class="admin-panel-header">
            <h2 class="admin-panel-title" id="panel-title">Adaugă produs</h2>
            <button class="admin-panel-close" id="panel-close">✕</button>
        </div>

        <div class="admin-panel-body">

            <!-- cautare Open Food Facts -->
            <div class="off-search-wrap">
                <input type="text" id="off-search-input" class="admin-input"
                       placeholder="Caută pe Open Food Facts (opțional)…">
                <button type="button" class="btn-secondary-sm" id="off-search-btn">Caută</button>
            </div>
            <div id="off-results" class="off-results"></div>

            <!-- formular produs -->
            <div class="admin-form" id="product-form">
                <input type="hidden" id="f-id">
                <input type="hidden" id="f-openfoodfacts-id">

                <div class="form-group">
                    <label class="form-label">Nume *</label>
                    <input type="text" id="f-name" class="admin-input" placeholder="ex: Ceai verde Lipton" maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">Brand</label>
                    <input type="text" id="f-brand" class="admin-input" placeholder="ex: Lipton">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Preț (RON)</label>
                        <input type="number" id="f-price" class="admin-input" placeholder="0.00" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Volum (ml)</label>
                        <input type="number" id="f-volume" class="admin-input" placeholder="500" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">URL imagine</label>
                    <input type="text" id="f-image" class="admin-input" placeholder="https://...">
                    <div id="f-image-preview" class="image-preview"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Descriere</label>
                    <textarea id="f-description" class="admin-input admin-textarea" rows="3"
                              placeholder="Descriere produs…"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ingrediente</label>
                    <textarea id="f-ingredients" class="admin-input admin-textarea" rows="2"
                              placeholder="Apă, zahăr, extract de ceai…"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Calorii/100ml</label>
                        <input type="number" id="f-calories" class="admin-input" placeholder="0" step="0.1" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Zahăr/100ml (g)</label>
                        <input type="number" id="f-sugar" class="admin-input" placeholder="0" step="0.1" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Zile valabilitate</label>
                        <input type="number" id="f-shelf-life" class="admin-input" placeholder="365" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cod bare</label>
                        <input type="text" id="f-barcode" class="admin-input" placeholder="5900259128354">
                    </div>
                </div>

                <!-- checkboxuri -->
                <div class="form-checks">
                    <label class="form-check">
                        <input type="checkbox" id="f-vegan"> Vegan
                    </label>
                    <label class="form-check">
                        <input type="checkbox" id="f-gluten-free"> Fără gluten
                    </label>
                    <label class="form-check">
                        <input type="checkbox" id="f-perishable"> Perisabil
                    </label>
                </div>

                <!-- categorii multi-select -->
                <div class="form-group">
                    <label class="form-label">Categorii</label>
                    <div id="f-categories" class="form-multiselect"></div>
                </div>

                <!-- alergeni multi-select -->
                <div class="form-group">
                    <label class="form-label">Alergeni</label>
                    <div id="f-allergens" class="form-multiselect"></div>
                </div>

                <!-- sezoane multi-select -->
                <div class="form-group">
                    <label class="form-label">Sezoane disponibile</label>
                    <div id="f-seasons" class="form-multiselect"></div>
                </div>

                <!-- regiuni multi-select -->
                <div class="form-group">
                    <label class="form-label">Regiuni disponibile</label>
                    <div id="f-regions" class="form-multiselect"></div>
                </div>

                <div class="form-actions">
                    <button class="btn-secondary" id="panel-cancel">Anulează</button>
                    <button class="btn-primary"   id="panel-save">Salvează</button>
                </div>
            </div>
        </div>
    </aside>

    <!-- modal confirmare stergere -->
    <div class="modal-overlay" id="confirm-modal" style="display:none" role="dialog">
        <div class="modal-box modal-box--sm">
            <div class="modal-icon">🗑</div>
            <h3 class="modal-title" id="confirm-title">Confirmare</h3>
            <p class="modal-desc"  id="confirm-desc">Ești sigur?</p>
            <div class="modal-actions">
                <button class="btn-secondary" id="confirm-cancel">Anulează</button>
                <button class="btn-danger"    id="confirm-ok">Șterge</button>
            </div>
        </div>
    </div>

    <!-- toast notificari -->
    <div id="admin-toast" class="admin-toast" style="display:none"></div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
