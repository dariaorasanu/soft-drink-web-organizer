<?php
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

$routes = [
    '/'              => '/pages/home.php',
    '/catalog'       => '/pages/catalog.php',
    '/product'       => '/pages/product.php',
    '/shopping-list' => '/pages/shopping-list.php',
    '/stats'         => '/pages/stats.php',
    '/auth'          => '/pages/auth.php',
    '/admin'         => '/admin/index.php',
];

if (isset($routes[$uri])) {
    require __DIR__ . $routes[$uri];
} elseif (file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
} else {
    http_response_code(404);
    echo '404 Not Found';
}