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
$controller           = new ProductController($productService, $userService);
$openFoodFactsService = new OpenFoodFactsService();

$action = $_GET['action'] ?? '';

match ($action) {
    'list'             => $controller->list(),
    'get'              => $controller->get(),
    'top'              => $controller->top(),
    'search'           => $controller->search(),
    'off_search'       => (function () use ($openFoodFactsService) {
        header('Content-Type: application/json; charset=utf-8');

        $barcode = trim($_GET['barcode'] ?? '');
        $query = trim($_GET['q'] ?? $_GET['name'] ?? '');

        if ($barcode === '' && preg_match('/^\d{6,}$/', $query) === 1) {
            $barcode = $query;
            $query = '';
        }

        if ($barcode === '' && $query === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Introdu un nume sau un cod de bare pentru cautarea Open Food Facts.',
                'products' => [],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            if ($barcode !== '') {
                $product = $openFoodFactsService->searchByBarcode($barcode);
                $products = $product ? [$openFoodFactsService->mapToProduct($product)] : [];
            } else {
                $products = array_map(
                    fn (array $product) => $openFoodFactsService->mapToProduct($product),
                    $openFoodFactsService->searchByName($query)
                );
            }

            echo json_encode([
                'success' => true,
                'message' => $products ? 'Produse gasite in Open Food Facts.' : 'Niciun produs gasit in Open Food Facts.',
                'products' => $products,
                'data' => $products,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $exception) {
            http_response_code(502);
            echo json_encode([
                'success' => false,
                'message' => 'Nu s-a putut cauta in Open Food Facts momentan.',
                'products' => [],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
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
        echo json_encode(['success' => false, 'message' => 'Actiune inexistenta.'], JSON_UNESCAPED_UNICODE);
        exit;
    })(),
};
