<?php
$activePage  ??= '';
$currentUser ??= null;
$username    ??= '';
$initials    ??= '?';
?>

<header class="topbar">
    <a href="/pages/home.php" class="brand">
        <span class="brand-main">S<span>O</span>r</span>
        <span class="brand-subtitle">Soft Drink Organizer</span>
    </a>

    <nav class="nav-menu">
        <a href="/pages/home.php"          class="nav-link <?= $activePage === 'home'          ? 'active' : '' ?>">Acasă</a>
        <a href="/pages/catalog.php"       class="nav-link <?= $activePage === 'catalog'       ? 'active' : '' ?>">Explorează</a>
        <a href="/pages/shopping-list.php" class="nav-link <?= $activePage === 'shopping-list' ? 'active' : '' ?>">Listele mele</a>
        <a href="#"                        class="nav-link <?= $activePage === 'stats'          ? 'active' : '' ?>">Statistici</a>
        <?php if ($currentUser?->isAdmin()): ?>
            <a href="/admin/index.php"     class="nav-link <?= $activePage === 'admin'         ? 'active' : '' ?>">Admin</a>
        <?php endif; ?>
    </nav>

    <?php if ($currentUser !== null): ?>
        <div class="user-area">
            <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            <span class="username"><?= htmlspecialchars($username) ?></span>
            <button type="button" class="logout-btn" id="logout-btn">Ieșire</button>
        </div>
    <?php else: ?>
        <div class="user-area">
            <a href="/pages/auth.php" class="nav-link">Autentificare</a>
        </div>
    <?php endif; ?>
</header>

<script>
    document.getElementById('logout-btn')?.addEventListener('click', async () => {
        await fetch('/api/users.php?action=logout', { method: 'POST' });
        window.location.href = '/pages/auth.php';
    });
</script>
