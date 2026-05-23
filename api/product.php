<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../service/ProductService.php';
require_once __DIR__ . '/../controllers/ProductController.php';

/** @var PDO $pdo */

$productRepository = new ProductRepository($pdo);
$productService = new ProductService($productRepository);
$controller = new ProductController($productService);

$action = $_GET['action'] ?? '';

match ($action) {
    'list' => $controller->list(),
    'get' => $controller->get(),
    'top' => $controller->top(),
    'search' => $controller->search(),
    'toggle_favorite' => $controller->toggleFavorite(),
    'rate' => $controller->rate(),
    'get_ratings' => $controller->getRatings(),
    default => (function () {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Acțiune inexistentă.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    })(),
};