<?php
session_start();

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var AuthGuard $guard */
/** @var UserService $userService */
/** @var PDO $pdo */

$guard->requireAuth();

$currentUser = $userService->getCurrentUser();
$username    = $currentUser?->username ?? 'Utilizator';
$initials    = strtoupper(substr($username, 0, 1));
$userId      = $currentUser?->id;

$activeListsCount      = 0;
$favoriteProductsCount = 0;

if ($userId !== null) {
    $activeListsStmt = $pdo->prepare("SELECT COUNT(*) FROM shopping_lists WHERE user_id = :user_id");
    $activeListsStmt->execute([':user_id' => $userId]);
    $activeListsCount = (int) $activeListsStmt->fetchColumn();

    $favoriteProductsStmt = $pdo->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = :user_id");
    $favoriteProductsStmt->execute([':user_id' => $userId]);
    $favoriteProductsCount = (int) $favoriteProductsStmt->fetchColumn();
}

$title      = 'SOr — Acasă';
$extraCss   = [];
$extraJs    = ['/public/js/home.js'];
$activePage = 'home';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/navbar.php';
?>

<div class="home-page">
    <main class="hero">
        <section class="welcome">
            <p class="eyebrow">Bine ai revenit</p>
            <h1>Bună ziua, <?= htmlspecialchars($username) ?> 👋</h1>
            <p class="subtitle">
                Ai <?= $activeListsCount ?> liste active · <?= $favoriteProductsCount ?> produse favorite
            </p>

            <form class="search-box" action="/pages/catalog.php" method="get">
                <span class="search-icon">⌕</span>
                <input type="search" name="q" placeholder="Caută un produs, ingredient, marcă sau local...">
            </form>
        </section>

        <section class="category-row" aria-label="Categorii produse">
            <a href="/pages/catalog.php"                    class="category-pill active">▦ Toate</a>
            <a href="/pages/catalog.php?category=ceaiuri"   class="category-pill">☕ Ceaiuri</a>
            <a href="/pages/catalog.php?category=sucuri"    class="category-pill">🍊 Sucuri</a>
            <a href="/pages/catalog.php?category=lactate"   class="category-pill">🥛 Lactate</a>
            <a href="/pages/catalog.php?category=siropuri"  class="category-pill">🍓 Siropuri</a>
            <a href="/pages/catalog.php?category=ape"       class="category-pill">〰 Ape</a>
            <a href="/pages/catalog.php?category=sezoniere" class="category-pill">✦ Sezonier</a>
        </section>
    </main>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
