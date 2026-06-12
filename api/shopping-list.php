<?php

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../controllers/ShoppingListController.php';
require_once __DIR__ . '/../repositories/ShoppingListRepository.php';

/** @var PDO         $pdo         injectat din Bootstrap */
/** @var UserService $userService injectat din Bootstrap */
/** @var AuthGuard   $guard       injectat din Bootstrap */

header('Content-Type: application/json; charset=utf-8');

$repo       = new ShoppingListRepository($pdo);
$service    = new ShoppingListService($repo);
$controller = new ShoppingListController($service);

$action = $_GET['action'] ?? '';

if ($action === 'shared_view') {
    $controller->sharedView();
    exit;
}

if ($action === 'shared_mark') {
    $controller->sharedMark();
    exit;
}


$guard->requireAuth();
$currentUser = $userService->getCurrentUser();
if ($currentUser === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Neautentificat.', 'data' => null]);
    exit;
}

$userId = $currentUser->id;
match ($action) {
    'my_lists'        => $controller->myLists($userId),
    'create'          => $controller->create($userId),
    'rename'          => $controller->rename($userId),
    'delete'          => $controller->delete($userId),
    'set_budget'      => $controller->setBudget($userId),
    'set_mood'        => $controller->setMood($userId),
    'share'           => $controller->share($userId),
    'unshare'         => $controller->unshare($userId),
    'items'           => $controller->items($userId),
    'add_item'        => $controller->addItem($userId),
    'remove_item'     => $controller->removeItem($userId),
    'update_item'     => $controller->updateItem($userId),
    'mark_purchased'  => $controller->markPurchased($userId),
    'clear_purchased' => $controller->clearPurchased($userId),

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