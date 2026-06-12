<?php

/**
 * admin/users.php
 * Tabel useri cu schimbare rol și ștergere.
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
$currentId   = $currentUser?->id;

$title      = 'SOr — Admin Utilizatori';
$extraCss   = ['/public/css/admin.css'];
$extraJs    = ['/public/js/admin.js'];
$activePage = 'admin';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
?>

    <main class="admin-layout">

        <!-- sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <span class="admin-sidebar-title">Admin Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="/admin/index.php"    class="admin-nav-link">
                    <span class="admin-nav-icon">📊</span> Dashboard
                </a>
                <a href="/admin/products.php" class="admin-nav-link">
                    <span class="admin-nav-icon">🥤</span> Produse
                </a>
                <a href="/admin/users.php"    class="admin-nav-link active">
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
                <h1 class="admin-page-title">Utilizatori</h1>
            </div>

            <div class="admin-card">
                <div class="admin-table-wrap">
                    <table class="admin-table" id="users-table">
                        <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Liste</th>
                            <th>Favorite</th>
                            <th>Înregistrat</th>
                            <th>Acțiuni</th>
                        </tr>
                        </thead>
                        <tbody id="users-tbody">
                        <tr><td colspan="7" class="admin-table-loading">
                                <div class="spinner"></div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="admin-pagination" id="users-pagination"></div>
            </div>

        </section>
    </main>

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

    <div id="admin-toast" class="admin-toast" style="display:none"></div>

    <!-- id-ul adminului curent — folosit in JS sa dezactiveze actiunile pe sine -->
    <meta name="current-user-id" content="<?= (int)$currentId ?>">

<?php require_once __DIR__ . '/../templates/footer.php'; ?>