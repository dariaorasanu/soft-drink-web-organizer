<?php

session_start();

//ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../service/ProductService.php';
require_once __DIR__ . '/../service/OpenFoodFactsService.php';
require_once __DIR__ . '/../controllers/ProductController.php';

/** @var PDO $pdo */

$productRepository = new ProductRepository($pdo);
$productService = new ProductService($productRepository);
$controller = new ProductController($productService);
$openFoodFactsService = new OpenFoodFactsService();

$action = $_GET['action'] ?? '';

match ($action) {
    'list' => $controller->list(),
    'get' => $controller->get(),
    'top' => $controller->top(),
    'search' => $controller->search(),
    'off_search' => (function () use ($openFoodFactsService) {
        try {
            $barcode = trim((string)($_GET['barcode'] ?? ''));
            $name = trim((string)($_GET['name'] ?? $_GET['q'] ?? ''));

            if ($barcode === '' && $name === '') {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Trimite barcode sau name pentru cautare.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $offProducts = $barcode !== ''
                ? array_filter([$openFoodFactsService->searchByBarcode($barcode)])
                : $openFoodFactsService->searchByName($name);

            $products = array_map(
                fn(array $product) => $openFoodFactsService->mapToProduct($product),
                $offProducts
            );

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'products' => array_values($products),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            http_response_code(502);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Eroare la cautarea in Open Food Facts.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    })(),
    'toggle_favorite' => $controller->toggleFavorite(),
    'rate' => $controller->rate(),
    'get_ratings' => $controller->getRatings(),
    'create' => $controller->create(),
    'update' => $controller->update(),
    'delete' => $controller->delete(),
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
