<?php

error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../service/ProductService.php';
require_once __DIR__ . '/../service/OpenFoodFactsService.php';
require_once __DIR__ . '/../controllers/ProductController.php';

/** @var PDO $pdo */
/** @var UserService $userService */

$productRepository    = new ProductRepository($pdo);
$productService       = new ProductService($productRepository);
$controller           = new ProductController($productService, $userService); // ← adaugi $userService
$openFoodFactsService = new OpenFoodFactsService();

$action = $_GET['action'] ?? '';

match ($action) {
    'list'             => $controller->list(),
    'get'              => $controller->get(),
    'top'              => $controller->top(),
    'search'           => $controller->search(),
    'off_search'       => (function () use ($openFoodFactsService) {
        // ... același cod ca înainte ...
    })(),
    'toggle_favorite'  => $controller->toggleFavorite(),
    'rate'             => $controller->rate(),
    'increment_view'   => $controller->incrementView(),
    'get_ratings'      => $controller->getRatings(),
    'create'           => $controller->create(),
    'update'           => $controller->update(),
    'delete'           => $controller->delete(),
    default            => (function () {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Acțiune inexistentă.'], JSON_UNESCAPED_UNICODE);
        exit;
    })(),
};