<?php

session_start();
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../controllers/AdminController.php';

/** @var PDO         $pdo */
/** @var AuthGuard   $guard */
/** @var UserService $userService */

header('Content-Type: application/json; charset=utf-8');

// toate rutele necesita rol de admin
$guard->requireAdmin();

$currentUser = $userService->getCurrentUser();
if ($currentUser === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Neautentificat.']);
    exit;
}

$adminId    = $currentUser->id;
$action     = $_GET['action'] ?? '';
$productRepo = new ProductRepository($pdo);
$controller  = new AdminController($pdo, $userRepo, $productRepo);

// raspuns JSON uniform
function adminOk(mixed $data = null, string $message = 'OK'): never
{
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function adminErr(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message, 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    match ($action) {
        // dashboard — totaluri
        'stats' => (function () use ($controller): never {
            adminOk($controller->getStats());
        })(),

        // date pentru formularul de produs (categorii, alergeni etc.)
        'form_data' => (function () use ($controller): never {
            adminOk($controller->getFormData());
        })(),

        // lista produse paginata
        'list_products' => (function () use ($controller): never {
            $page   = max(1, (int)($_GET['page']   ?? 1));
            $search = trim($_GET['search'] ?? '');
            adminOk($controller->listProducts($page, $search));
        })(),

        // creeaza produs
        'create_product' => (function () use ($controller, $adminId): never {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') adminErr('Metoda invalida.', 405);

            $name = trim($_POST['name'] ?? '');
            if ($name === '') adminErr('Numele produsului este obligatoriu.');

            $data = [
                'name'               => $name,
                'description'        => trim($_POST['description']        ?? ''),
                'price'              => $_POST['price'] !== '' ? (float)$_POST['price'] : null,
                'image_url'          => trim($_POST['image_url']          ?? ''),
                'ingredients'        => trim($_POST['ingredients']        ?? ''),
                'barcode'            => trim($_POST['barcode']            ?? ''),
                'brand'              => trim($_POST['brand']              ?? ''),
                'volume_ml'          => $_POST['volume_ml']          !== '' ? (int)$_POST['volume_ml']          : null,
                'calories_per_100ml' => $_POST['calories_per_100ml'] !== '' ? (float)$_POST['calories_per_100ml'] : null,
                'sugar_per_100ml'    => $_POST['sugar_per_100ml']    !== '' ? (float)$_POST['sugar_per_100ml']    : null,
                'is_perishable'      => !empty($_POST['is_perishable']),
                'shelf_life_days'    => $_POST['shelf_life_days']    !== '' ? (int)$_POST['shelf_life_days']    : null,
                'is_vegan'           => !empty($_POST['is_vegan']),
                'is_gluten_free'     => !empty($_POST['is_gluten_free']),
                'openfoodfacts_id'   => trim($_POST['openfoodfacts_id']   ?? ''),
                'category_ids'       => $_POST['category_ids']  ?? [],
                'allergen_ids'       => $_POST['allergen_ids']  ?? [],
                'season_ids'         => $_POST['season_ids']    ?? [],
                'region_ids'         => $_POST['region_ids']    ?? [],
            ];

            $id = $controller->createProduct($data, $adminId);
            adminOk(['id' => $id], 'Produs creat.');
        })(),

        // actualizeaza produs
        'update_product' => (function () use ($controller): never {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') adminErr('Metoda invalida.', 405);

            $id   = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($id <= 0)   adminErr('ID invalid.');
            if ($name === '') adminErr('Numele produsului este obligatoriu.');

            $data = [
                'name'               => $name,
                'description'        => trim($_POST['description']        ?? ''),
                'price'              => $_POST['price'] !== '' ? (float)$_POST['price'] : null,
                'image_url'          => trim($_POST['image_url']          ?? ''),
                'ingredients'        => trim($_POST['ingredients']        ?? ''),
                'brand'              => trim($_POST['brand']              ?? ''),
                'volume_ml'          => $_POST['volume_ml']          !== '' ? (int)$_POST['volume_ml']          : null,
                'calories_per_100ml' => $_POST['calories_per_100ml'] !== '' ? (float)$_POST['calories_per_100ml'] : null,
                'sugar_per_100ml'    => $_POST['sugar_per_100ml']    !== '' ? (float)$_POST['sugar_per_100ml']    : null,
                'is_perishable'      => !empty($_POST['is_perishable']),
                'shelf_life_days'    => $_POST['shelf_life_days']    !== '' ? (int)$_POST['shelf_life_days']    : null,
                'is_vegan'           => !empty($_POST['is_vegan']),
                'is_gluten_free'     => !empty($_POST['is_gluten_free']),
                'category_ids'       => $_POST['category_ids']  ?? [],
                'allergen_ids'       => $_POST['allergen_ids']  ?? [],
                'season_ids'         => $_POST['season_ids']    ?? [],
                'region_ids'         => $_POST['region_ids']    ?? [],
            ];

            $ok = $controller->updateProduct($id, $data);
            $ok ? adminOk(null, 'Produs actualizat.') : adminErr('Actualizare eșuată.');
        })(),

        // sterge produs
        'delete_product' => (function () use ($controller): never {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') adminErr('Metoda invalida.', 405);
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) adminErr('ID invalid.');
            $ok = $controller->deleteProduct($id);
            $ok ? adminOk(null, 'Produs șters.') : adminErr('Ștergere eșuată.');
        })(),

        // import CSV
        'import_csv' => (function () use ($controller, $adminId): never {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') adminErr('Metoda invalida.', 405);
            if (empty($_FILES['csv'])) adminErr('Niciun fișier uploadat.');
            $result = $controller->importCsv($_FILES['csv'], $adminId);
            adminOk($result, "Importate {$result['imported']} produse.");
        })(),

        // export CSV — trimite fisierul direct
        'export_csv' => (function () use ($controller): never {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="produse-' . date('Y-m-d') . '.csv"');
            echo "\xEF\xBB\xBF"; // BOM pentru Excel
            echo $controller->exportCsv();
            exit;
        })(),

        // export JSON — trimite fisierul direct
        'export_json' => (function () use ($controller): never {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="produse-' . date('Y-m-d') . '.json"');
            echo $controller->exportJson();
            exit;
        })(),

        // lista useri paginata
        'list_users' => (function () use ($controller): never {
            $page = max(1, (int)($_GET['page'] ?? 1));
            adminOk($controller->listUsers($page));
        })(),

        // schimba rolul unui user
        'update_role' => (function () use ($controller, $adminId): never {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') adminErr('Metoda invalida.', 405);
            $targetId = (int)($_POST['user_id'] ?? 0);
            $role     = trim($_POST['role'] ?? '');
            if ($targetId <= 0) adminErr('user_id invalid.');
            if ($role === '')   adminErr('Rolul este obligatoriu.');
            $controller->updateUserRole($targetId, $role, $adminId);
            adminOk(null, 'Rol actualizat.');
        })(),

        // sterge user
        'delete_user' => (function () use ($controller, $adminId): never {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') adminErr('Metoda invalida.', 405);
            $targetId = (int)($_POST['user_id'] ?? 0);
            if ($targetId <= 0) adminErr('user_id invalid.');
            $controller->deleteUser($targetId, $adminId);
            adminOk(null, 'User șters.');
        })(),

        default => adminErr('Acțiune inexistentă.', 404),
    };

} catch (RuntimeException $e) {
    adminErr($e->getMessage());
} catch (Exception $e) {
    adminErr('Eroare internă: ' . $e->getMessage(), 500);
}