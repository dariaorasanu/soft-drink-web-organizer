<?php
session_start();
require_once __DIR__ . '/../config/Bootstrap.php';
/** @var \PDO $pdo */

// Dacă e deja logat, trimite la home
if (isset($_SESSION['user_id'])) {
    header('Location: /pages/home.php');
    exit;
}

// Top 4 produse după view_count
$topProducts = [];
try {
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.brand, p.price, p.image_url, p.view_count,
               COALESCE(AVG(r.rating), 0) AS avg_rating,
               COUNT(r.id) AS rating_count
        FROM products p
        LEFT JOIN ratings r ON r.product_id = p.id
        GROUP BY p.id
        ORDER BY p.view_count DESC
        LIMIT 4
    ");
    $topProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Exception $e) { /* fallback la date statice */ }

// Statistici globale
$stats = ['products' => 0, 'users' => 0, 'ratings' => 0];
try {
    $stats['products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['users']    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['ratings']  = $pdo->query("SELECT COUNT(*) FROM ratings")->fetchColumn();
} catch (\Exception $e) {}

// Fallback date demo dacă DB e goală
if (empty($topProducts)) {
    $topProducts = [
        ['name' => 'Ceai verde Matcha', 'brand' => 'Ito En',       'price' => 12.50, 'avg_rating' => 4.9, 'rating_count' => 142, 'view_count' => 980,  'image_url' => null],
        ['name' => 'Limonadă cu mentă', 'brand' => 'FreshDrop',    'price' => 8.90,  'avg_rating' => 4.7, 'rating_count' => 98,  'view_count' => 820,  'image_url' => null],
        ['name' => 'Suc căpșuni bio',   'brand' => 'NaturaBio',    'price' => 10.00, 'avg_rating' => 4.6, 'rating_count' => 76,  'view_count' => 710,  'image_url' => null],
        ['name' => 'Sirop de soc',      'brand' => 'Bunica\'s',    'price' => 15.00, 'avg_rating' => 4.4, 'rating_count' => 63,  'view_count' => 590,  'image_url' => null],
    ];
}
if ($stats['products'] == 0) { $stats = ['products' => 248, 'users' => 1200, 'ratings' => 5800]; }

$emojis = ['🍵', '🥤', '🍓', '🧃', '🫖', '🍋', '🧋', '🍹'];

function formatNum(int $n): string {
    if ($n >= 1000) return round($n / 1000, 1) . 'k';
    return (string)$n;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOr — Soft Drink Organizer</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/landing.css">
</head>
<body>

<!-- Navbar minimal -->
<nav class="topbar">
    <div class="topbar-inner">
        <div class="nav-logo">
            <span class="logo-s">S</span><span class="logo-o">O</span><span class="logo-r">r</span>
            <span class="logo-label">Soft Drink Organizer</span>
        </div>
        <a href="/pages/auth.php" class="btn-nav">Intră în cont</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge">🧃 Băuturi non-alcoolice</div>
        <h1 class="hero-title">
            Organizează tot ce<br>
            <span class="accent-green">bei</span> și <span class="accent-pink">iubești</span>
        </h1>
        <p class="hero-sub">
            Ceaiuri, sucuri, siropuri, lactate și mult mai mult — descoperă,
            salvează și împărtășește preferințele tale.
        </p>
        <div class="hero-cta">
            <a href="/pages/auth.php?tab=register" class="btn-primary">Începe gratuit</a>
            <a href="/pages/auth.php" class="btn-ghost">Am deja cont</a>
        </div>
    </div>

    <!-- Decorativ fundal -->
    <div class="hero-glow hero-glow--green"></div>
    <div class="hero-glow hero-glow--pink"></div>
</section>

<!-- Statistici -->
<section class="stats-bar">
    <div class="stats-inner">
        <div class="stat-item">
            <span class="stat-num"><?= formatNum((int)$stats['products']) ?></span>
            <span class="stat-lbl">Băuturi catalogate</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-num"><?= formatNum((int)$stats['users']) ?></span>
            <span class="stat-lbl">Utilizatori activi</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-num"><?= formatNum((int)$stats['ratings']) ?></span>
            <span class="stat-lbl">Recenzii scrise</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-num">4</span>
            <span class="stat-lbl">Regiuni acoperite</span>
        </div>
    </div>
</section>

<!-- Top băuturi -->
<section class="top-section">
    <div class="section-inner">
        <div class="section-header">
            <p class="section-eyebrow">Clasament live</p>
            <h2 class="section-title">Cele mai iubite băuturi</h2>
            <p class="section-sub">Actualizat în timp real după recenziile comunității</p>
        </div>

        <div class="drinks-grid">
            <?php foreach ($topProducts as $i => $p): ?>
                <div class="drink-card <?= $i === 0 ? 'drink-card--top' : '' ?>">
                    <div class="drink-card-rank">#<?= $i + 1 ?></div>
                    <div class="drink-card-img">
                        <?php if (!empty($p['image_url'])): ?>
                            <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <span class="drink-emoji"><?= $emojis[$i % count($emojis)] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="drink-card-body">
                        <div class="drink-card-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="drink-card-brand"><?= htmlspecialchars($p['brand'] ?? '') ?></div>
                        <div class="drink-card-meta">
                        <span class="drink-stars">
                            <?php
                            $r = round((float)$p['avg_rating'] * 2) / 2;
                            for ($s = 1; $s <= 5; $s++) {
                                if ($s <= $r) echo '<span class="star star--full">★</span>';
                                else echo '<span class="star star--empty">★</span>';
                            }
                            ?>
                        </span>
                            <span class="drink-rating-num"><?= number_format((float)$p['avg_rating'], 1) ?></span>
                            <span class="drink-rating-count">(<?= (int)$p['rating_count'] ?>)</span>
                        </div>
                    </div>
                    <?php if (!empty($p['price'])): ?>
                        <div class="drink-card-price"><?= number_format((float)$p['price'], 2) ?> lei</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section-cta">
            <a href="/pages/auth.php?tab=register" class="btn-primary">
                Vezi toate băuturile →
            </a>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features-section">
    <div class="section-inner">
        <div class="section-header">
            <p class="section-eyebrow">Ce poți face</p>
            <h2 class="section-title">Tot ce ai nevoie într-un loc</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3>Liste de cumpărături</h3>
                <p>Creează liste, adaugă produse, marchează ce ai cumpărat și partajează cu prietenii.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Filtrare avansată</h3>
                <p>Caută după categorie, alergeni, sezon, regiune geografică sau disponibilitate.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <h3>Recenzii și rating</h3>
                <p>Evaluează băuturile preferate și citește părerile comunității.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Statistici și export</h3>
                <p>Grafice cu preferințele tale, export CSV/JSON și feed RSS cu topul produselor.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA final -->
<section class="final-cta">
    <div class="final-cta-inner">
        <h2>Gata să descoperi?</h2>
        <p>Alătură-te comunității și organizează preferințele tale.</p>
        <a href="/pages/auth.php?tab=register" class="btn-primary btn-primary--lg">Creează cont gratuit</a>
    </div>
    <div class="hero-glow hero-glow--green" style="opacity:0.4"></div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-logo">
            <span class="logo-s">S</span><span class="logo-o">O</span><span class="logo-r">r</span>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> SOr — Soft Drink Organizer</p>
        <a href="/api/rss.php" class="footer-rss">📡 RSS Feed</a>
    </div>
</footer>

</body>
</html>