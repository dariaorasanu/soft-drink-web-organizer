<?php
session_start();

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var AuthGuard $guard */
/** @var UserService $userService */
/** @var PDO $pdo */

$guard->requireAuth();

$currentUser = $userService->getCurrentUser();
$username = $currentUser?->username ?? 'Utilizator';
$initials = strtoupper(substr($username, 0, 1));
$userId = $currentUser?->id;

$activeListsCount = 0;
$favoriteProductsCount = 0;

if ($userId !== null) {
    $activeListsStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM shopping_lists
        WHERE user_id = :user_id
    ");
    $activeListsStmt->execute([':user_id' => $userId]);
    $activeListsCount = (int) $activeListsStmt->fetchColumn();

    $favoriteProductsStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM user_favorites
        WHERE user_id = :user_id
    ");
    $favoriteProductsStmt->execute([':user_id' => $userId]);
    $favoriteProductsCount = (int) $favoriteProductsStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOr — Acasă</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/home.css">
</head>
<body>

<div class="home-page">

    <header class="topbar">
        <a href="/pages/home.php" class="brand">
            <span class="brand-main">S<span>O</span>r</span>
            <span class="brand-subtitle">Soft Drink Organizer</span>
        </a>

        <nav class="nav-menu">
            <a href="/pages/home.php" class="nav-link active">Acasă</a>
            <a href="/pages/catalog.php" class="nav-link">Explorează</a>
            <a href="/pages/shopping-list.php" class="nav-link">Listele mele</a>
            <a href="#" class="nav-link">Clasament</a>
            <a href="#" class="nav-link">Statistici</a>

            <?php if ($currentUser?->isAdmin()): ?>
                <a href="/admin/index.php" class="nav-link">Admin</a>
            <?php endif; ?>
        </nav>

        <div class="user-area">
            <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            <span class="username"><?= htmlspecialchars($username) ?></span>
            <button type="button" class="logout-btn" id="logout-btn">Ieșire</button>        </div>
    </header>

    <main class="hero">
        <section class="welcome">
            <p class="eyebrow">Bine ai revenit</p>
            <h1>Bună ziua, <?= htmlspecialchars($username) ?> 👋</h1>
            <p class="subtitle">
                Ai <?= $activeListsCount ?> liste active · <?= $favoriteProductsCount ?> produse favorite
            </p>

            <form class="search-box" action="/pages/catalog.php" method="get">
                <span class="search-icon">⌕</span>
                <input
                    type="search"
                    name="q"
                    placeholder="Caută un produs, ingredient, marcă sau local..."
                >
            </form>
        </section>

        <section class="category-row" aria-label="Categorii produse">
            <a href="/pages/catalog.php" class="category-pill active">▦ Toate</a>
            <a href="/pages/catalog.php?category=ceaiuri" class="category-pill">☕ Ceaiuri</a>
            <a href="/pages/catalog.php?category=sucuri" class="category-pill">🍊 Sucuri</a>
            <a href="/pages/catalog.php?category=lactate" class="category-pill">🥛 Lactate</a>
            <a href="/pages/catalog.php?category=siropuri" class="category-pill">🍓 Siropuri</a>
            <a href="/pages/catalog.php?category=ape" class="category-pill">〰 Ape</a>
            <a href="/pages/catalog.php?category=sezoniere" class="category-pill">✦ Sezonier</a>
        </section>
    </main>

</div>
<script src="/public/js/home.js"></script>
</body>
</html>