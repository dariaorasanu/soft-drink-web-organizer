<?php

$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    die('.env lipsește!');
}
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, " \"'");
}


spl_autoload_register(function (string $class): void {
    $dirs = ['models', 'repositories', 'repositories/Interfaces', 'service', 'controllers', 'config'];
    foreach ($dirs as $dir) {
        $file = dirname(__DIR__) . "/$dir/$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
require_once __DIR__ . '/../middleware/AuthGuard.php';
require_once __DIR__ . '/Database.php';
$pdo = Database::connect();

$userRepo = new UserRepository($pdo);
$userService = new UserService($userRepo);
$guard = new AuthGuard($userService);