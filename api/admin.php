<?php

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../repositories/AdminRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../service/AdminService.php';
require_once __DIR__ . '/../controllers/AdminController.php';

/** @var PDO         $pdo         injectat din Bootstrap */
/** @var UserService $userService injectat din Bootstrap */
/** @var AuthGuard   $guard       injectat din Bootstrap */


header('Content-Type: application/json; charset=utf-8');

$guard->requireAdmin();

$currentUser = $userService->getCurrentUser();
if ($currentUser === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Neautentificat.', 'data' => null]);
    exit;
}

$adminId = $currentUser->id;

$adminRepo   = new AdminRepository($pdo);
$userRepo    = new UserRepository($pdo);
$productRepo = new ProductRepository($pdo);
$service     = new AdminService($adminRepo, $userRepo, $productRepo);
$controller  = new AdminController($service);

$action = $_GET['action'] ?? '';

$postActions = [
    'create_product', 'update_product', 'delete_product',
    'import_csv', 'update_role', 'delete_user'
];

if (in_array($action, $postActions, true) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă. Folosește POST.', 'data' => null]);
    exit;
}

try {
    match ($action) {
        'stats'          => $controller->stats(),
        'form_data'      => $controller->formData(),
        'list_products'  => $controller->listProducts(),
        'create_product' => $controller->createProduct($adminId),
        'update_product' => $controller->updateProduct(),
        'delete_product' => $controller->deleteProduct(),
        'import_csv'     => $controller->importCsv($adminId),
        'export_csv'     => $controller->exportCsv(),
        'export_json'    => $controller->exportJson(),
        'list_users'     => $controller->listUsers(),
        'update_role'    => $controller->updateUserRole($adminId),
        'delete_user'    => $controller->deleteUser($adminId),

        default => (function () {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Acțiune inexistentă.',
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        })()
    };
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare internă.', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}