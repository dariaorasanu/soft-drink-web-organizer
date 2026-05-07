<?php
session_start();

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var AuthGuard $guard */
/** @var PDO $pdo */

$guard->requireAuth();

$query = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');

$sql = "
    SELECT DISTINCT 
        p.id,
        p.name,
        p.slug,
        p.description,
        p.price,
        p.image_url,
        p.brand,
        p.volume_ml,
        p.view_count,
        p.created_at
    FROM products p
    LEFT JOIN product_categories pc ON pc.product_id = p.id
    LEFT JOIN categories c ON c.id = pc.category_id
    WHERE 1 = 1
";

$params = [];

if ($query !== '') {
    $sql .= "
        AND (
            p.name ILIKE :query
            OR p.brand ILIKE :query
            OR p.description ILIKE :query
            OR p.ingredients ILIKE :query
        )
    ";
    $params[':query'] = '%' . $query . '%';
}

if ($categorySlug !== '') {
    $sql .= " AND c.slug = :category_slug";
    $params[':category_slug'] = $categorySlug;
}

$sql .= " ORDER BY p.created_at DESC LIMIT 30";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categoryLabel = $categorySlug !== ''
        ? ucfirst(str_replace('-', ' ', $categorySlug))
        : 'Toate produsele';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Explorează produse</title>
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
            <h1><?= htmlspecialchars($categoryLabel) ?></h1>

            <?php if ($query !== ''): ?>
                <p class="subtitle">
                    Rezultate pentru: <strong><?= htmlspecialchars($query) ?></strong>
                </p>
            <?php elseif ($categorySlug !== ''): ?>
                <p class="subtitle">
                    Produse din categoria: <strong><?= htmlspecialchars($categorySlug) ?></strong>
                </p>
            <?php else: ?>
                <p class="subtitle">
                    Toate produsele disponibile în catalog.
                </p>
            <?php endif; ?>

            <form class="search-box" action="/pages/catalog.php" method="get">
                <span class="search-icon">⌕</span>
                <input
                        type="search"
                        name="q"
                        value="<?= htmlspecialchars($query) ?>"
                        placeholder="Caută un produs, ingredient, marcă sau local..."
                >
            </form>
        </section>

        <section class="category-row" aria-label="Categorii produse">
            <a href="/pages/catalog.php" class="category-pill <?= $categorySlug === '' ? 'active' : '' ?>">▦ Toate</a>
            <a href="/pages/catalog.php?category=ceaiuri" class="category-pill <?= $categorySlug === 'ceaiuri' ? 'active' : '' ?>">🍵 Ceaiuri</a>
            <a href="/pages/catalog.php?category=sucuri" class="category-pill <?= $categorySlug === 'sucuri' ? 'active' : '' ?>">🍊 Sucuri</a>
            <a href="/pages/catalog.php?category=lactate" class="category-pill <?= $categorySlug === 'lactate' ? 'active' : '' ?>">🥛 Lactate</a>
            <a href="/pages/catalog.php?category=siropuri" class="category-pill <?= $categorySlug === 'siropuri' ? 'active' : '' ?>">🍓 Siropuri</a>
            <a href="/pages/catalog.php?category=ape" class="category-pill <?= $categorySlug === 'ape' ? 'active' : '' ?>">〰 Ape</a>
            <a href="/pages/catalog.php?category=sezoniere" class="category-pill <?= $categorySlug === 'sezoniere' ? 'active' : '' ?>">✦ Sezonier</a>
        </section>

        <section class="catalog-grid">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <h2>Nu există produse de afișat.</h2>
                    <p>
                        Momentan nu avem produse pentru filtrul ales sau baza de date nu este populată.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <article class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                <span>🥤</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h2><?= htmlspecialchars($product['name']) ?></h2>

                            <?php if (!empty($product['brand'])): ?>
                                <p><?= htmlspecialchars($product['brand']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($product['description'])): ?>
                                <p><?= htmlspecialchars($product['description']) ?></p>
                            <?php endif; ?>

                            <div class="product-meta">
                                <?php if ($product['price'] !== null): ?>
                                    <span><?= number_format((float)$product['price'], 2) ?> RON</span>
                                <?php endif; ?>

                                <?php if (!empty($product['volume_ml'])): ?>
                                    <span><?= (int)$product['volume_ml'] ?> ml</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>