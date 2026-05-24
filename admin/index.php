<?php
/**
 * Router principal — SOr
 */

define('ROOT_PATH', __DIR__);

// ── Extrage path-ul curat ────────────────────────────────────────────────────
$uri      = $_SERVER['REQUEST_URI'] ?? '/';
$path     = parse_url($uri, PHP_URL_PATH);

// Scoate subfolder-ul (ex: /soft-drink-web-organizer) dacă rulezi din subdirector
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
    $path = substr($path, strlen($scriptDir));
}

$path = rtrim($path, '/');
$path = $path === '' ? '/' : $path;

// ── Tabla de rute ────────────────────────────────────────────────────────────
$routes = [
    '/'               => 'pages/home.php',
    '/home'           => 'pages/home.php',
    '/auth'           => 'pages/auth.php',
    '/login'          => 'pages/auth.php',
    '/register'       => 'pages/auth.php',
    '/catalog'        => 'pages/catalog.php',
    '/product'        => 'pages/product.php',
    '/shopping-list'  => 'pages/shopping-list.php',
    '/stats'          => 'pages/stats.php',
    '/admin'          => 'admin/index.php',
    '/admin/products' => 'admin/products.php',
    '/admin/users'    => 'admin/users.php',
];

// ── Potrivire rută ───────────────────────────────────────────────────────────
if (array_key_exists($path, $routes)) {
    $file = ROOT_PATH . '/' . $routes[$path];
    if (is_readable($file)) {
        require $file;
        exit;
    }
    http_response_code(503);
    require ROOT_PATH . '/pages/error.php';
    exit;
}

// ── 404 ─────────────────────────────────────────────────────────────────────
http_response_code(404);
$errorPage = ROOT_PATH . '/pages/error.php';
if (is_readable($errorPage)) {
    require $errorPage;
} else {
    echo '<!DOCTYPE html><html lang="ro"><head><meta charset="UTF-8">
          <title>404 — SOr</title></head><body>
          <h1>404 — Pagina nu există</h1>
          <a href="/">Înapoi acasă</a></body></html>';
}
exit;