<?php

// 1. Citește .env
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    die('.env lipsește!');
}
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, " \"'");
}

// 2. Autoload clase
spl_autoload_register(function (string $class): void {
    $dirs = ['models', 'repositories', 'service', 'controllers', 'config'];
    foreach ($dirs as $dir) {
        $file = dirname(__DIR__) . "/$dir/$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 3. Creează PDO o singură dată
require_once __DIR__ . '/Database.php';
$pdo = Database::connect();

// 4. Injectează PDO în repositories
// (le adăugăm pe măsură ce le scriem)
// $productRepo  = new ProductRepository($pdo);
// $userRepo     = new UserRepository($pdo);

// 5. Injectează repositories în services
// $productService = new ProductService($productRepo);
// $userService    = new UserService($userRepo);