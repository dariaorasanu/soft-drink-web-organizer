<?php

/**
 * admin/index.php
 * Dashboard admin — statistici generale + grafic top produse.
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

$title      = 'SOr — Admin Dashboard';
$extraCss   = ['/public/css/admin.css'];
$extraJs    = ['/public/js/admin.js'];
$activePage = 'admin';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
?>

    <main class="admin-layout">

        <!-- sidebar navigare admin -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <span class="admin-sidebar-title">Admin Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="/admin/index.php"    class="admin-nav-link active">
                    <span class="admin-nav-icon">📊</span> Dashboard
                </a>
                <a href="/admin/products.php" class="admin-nav-link">
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

        <!-- continut principal -->
        <section class="admin-content">

            <div class="admin-page-header">
                <h1 class="admin-page-title">Dashboard</h1>
                <span class="admin-page-sub">Bine ai venit, <?= htmlspecialchars($username) ?>!</span>
            </div>

            <!-- carduri statistici -->
            <div class="admin-stats-grid" id="stats-grid">
                <div class="admin-stat-card" id="stat-products">
                    <span class="stat-icon">🥤</span>
                    <div class="stat-info">
                        <span class="stat-value" id="val-products">—</span>
                        <span class="stat-label">Produse</span>
                    </div>
                </div>
                <div class="admin-stat-card" id="stat-users">
                    <span class="stat-icon">👥</span>
                    <div class="stat-info">
                        <span class="stat-value" id="val-users">—</span>
                        <span class="stat-label">Utilizatori</span>
                    </div>
                </div>
                <div class="admin-stat-card" id="stat-lists">
                    <span class="stat-icon">🛒</span>
                    <div class="stat-info">
                        <span class="stat-value" id="val-lists">—</span>
                        <span class="stat-label">Liste</span>
                    </div>
                </div>
                <div class="admin-stat-card" id="stat-ratings">
                    <span class="stat-icon">⭐</span>
                    <div class="stat-info">
                        <span class="stat-value" id="val-ratings">—</span>
                        <span class="stat-label">Ratinguri</span>
                    </div>
                </div>
                <div class="admin-stat-card" id="stat-favorites">
                    <span class="stat-icon">♡</span>
                    <div class="stat-info">
                        <span class="stat-value" id="val-favorites">—</span>
                        <span class="stat-label">Favorite</span>
                    </div>
                </div>
            </div>

            <!-- top produse -->
            <div class="admin-card" style="margin-top:2rem">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">Top produse vizualizate</h2>
                    <div class="admin-card-actions">
                        <a href="/admin/products.php" class="btn-secondary-sm">Vezi toate →</a>
                    </div>
                </div>
                <div id="top-products-list" class="admin-top-list">
                    <div class="admin-loading"><div class="spinner"></div></div>
                </div>
            </div>

            <!-- linkuri rapide -->
            <div class="admin-quick-links">
                <a href="/admin/products.php" class="admin-quick-card">
                    <span class="quick-icon">➕</span>
                    <span class="quick-label">Adaugă produs</span>
                </a>
                <a href="/admin/users.php" class="admin-quick-card">
                    <span class="quick-icon">👤</span>
                    <span class="quick-label">Gestionează useri</span>
                </a>
                <a href="/api/rss.php" target="_blank" class="admin-quick-card">
                    <span class="quick-icon">📡</span>
                    <span class="quick-label">Feed RSS</span>
                </a>
                <a href="/api/admin.php?action=export_csv" class="admin-quick-card">
                    <span class="quick-icon">⬇</span>
                    <span class="quick-label">Export CSV</span>
                </a>
            </div>

        </section>
    </main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>