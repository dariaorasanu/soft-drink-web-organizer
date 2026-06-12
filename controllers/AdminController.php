<?php

require_once __DIR__ . '/../service/AdminService.php';

class AdminController
{
    public function __construct(
        private readonly AdminService $service
    ) {}


    public function stats(): void
    {
        $this->jsonSuccess($this->service->getStats());
    }

    public function formData(): void
    {
        $this->jsonSuccess($this->service->getFormData());
    }

    public function listProducts(): void
    {
        $page   = max(1, (int)($_GET['page']   ?? 1));
        $search = trim($_GET['search'] ?? '');

        $result = $this->service->listProducts($page, $search);
        $result['products'] = array_map(
            fn(Product $p) => $this->serializeProduct($p),
            $result['products']
        );

        $this->jsonSuccess($result);
    }

    public function createProduct(int $adminId): void
    {
        $data = $this->extractProductData();
        try {
            $id = $this->service->createProduct($data, $adminId);
            $this->jsonSuccess(['id' => $id], 'Produs creat.');
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        }
    }


    public function updateProduct(): void
    {
        $id   = (int)($_POST['id'] ?? 0);
        $data = $this->extractProductData();
        if ($id <= 0) {
            $this->jsonError('ID invalid.');
            return;
        }
        try {
            $this->service->updateProduct($id, $data);
            $this->jsonSuccess(null, 'Produs actualizat.');
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }


    public function deleteProduct(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('ID invalid.');
            return;
        }
        try {
            $this->service->deleteProduct($id);
            $this->jsonSuccess(null, 'Produs șters.');
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }


    public function importCsv(int $adminId): void
    {
        if (empty($_FILES['csv'])) {
            $this->jsonError('Niciun fișier uploadat.');
            return;
        }
        try {
            $result = $this->service->importCsv($_FILES['csv'], $adminId);
            $this->jsonSuccess($result, "Importate {$result['imported']} produse.");
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }


    public function exportCsv(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="produse-' . date('Y-m-d') . '.csv"');

        echo "\xEF\xBB\xBF";
        echo $this->service->exportCsv();
        exit;
    }


    public function exportJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="produse-' . date('Y-m-d') . '.json"');
        echo $this->service->exportJson();
        exit;
    }


    public function listUsers(): void
    {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->service->listUsers($page);

        $result['users'] = array_map(
            fn(array $u) => $this->serializeUser($u),
            $result['users']
        );

        $this->jsonSuccess($result);
    }


    public function updateUserRole(int $adminId): void
    {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $role = trim($_POST['role']     ?? '');

        if ($targetId <= 0) {
            $this->jsonError('user_id invalid.');
            return;
        }
        if ($role === '') {
            $this->jsonError('Rolul este obligatoriu.');
            return;
        }

        try {
            $this->service->updateUserRole($targetId, $role, $adminId);
            $this->jsonSuccess(null, 'Rol actualizat.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }

    public function deleteUser(int $adminId): void
    {
        $targetId = (int)($_POST['user_id'] ?? 0);
        if ($targetId <= 0) {
            $this->jsonError('user_id invalid.');
            return;
        }
        try {
            $this->service->deleteUser($targetId, $adminId);
            $this->jsonSuccess(null, 'User șters.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    private function serializeProduct(Product $p): array
    {
        return [
            'id'                 => $p->id,
            'name'               => htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8'),
            'slug'               => $p->slug,
            'brand'              => $p->brand
                ? htmlspecialchars($p->brand, ENT_QUOTES, 'UTF-8')
                : null,
            'price'              => $p->price,
            'image_url'          => $p->imageUrl,
            'volume_ml'          => $p->volumeMl,
            'calories_per_100ml' => $p->caloriesPer100ml,
            'sugar_per_100ml'    => $p->sugarPer100ml,
            'is_vegan'           => $p->isVegan,
            'is_gluten_free'     => $p->isGlutenFree,
            'is_perishable'      => $p->isPerishable,
            'shelf_life_days'    => $p->shelfLifeDays,
            'view_count'         => $p->viewCount,
            'created_at'         => $p->createdAt,
        ];
    }

    private function serializeUser(array $row): array
    {
        return [
            'id'             => (int)$row['id'],
            'username'       => htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'),
            'email'          => htmlspecialchars($row['email'],    ENT_QUOTES, 'UTF-8'),
            'role'           => $row['role'],
            'avatar_url'     => $row['avatar_url'] ?? null,
            'created_at'     => $row['created_at'],
            'list_count'     => (int)$row['list_count'],
            'favorite_count' => (int)$row['favorite_count'],
        ];
    }

    private function extractProductData(): array
    {
        return [
            'name'               => trim($_POST['name']               ?? ''),
            'description'        => trim($_POST['description']        ?? ''),
            'price'              => $_POST['price']              !== '' ? (float)$_POST['price']              : null,
            'image_url'          => trim($_POST['image_url']          ?? ''),
            'ingredients'        => trim($_POST['ingredients']        ?? ''),
            'barcode'            => trim($_POST['barcode']            ?? ''),
            'brand'              => trim($_POST['brand']              ?? ''),
            'volume_ml'          => ($_POST['volume_ml']          ?? '') !== '' ? (int)$_POST['volume_ml']          : null,
            'calories_per_100ml' => ($_POST['calories_per_100ml'] ?? '') !== '' ? (float)$_POST['calories_per_100ml'] : null,
            'sugar_per_100ml'    => ($_POST['sugar_per_100ml']    ?? '') !== '' ? (float)$_POST['sugar_per_100ml']    : null,
            'is_perishable'      => !empty($_POST['is_perishable']),
            'shelf_life_days'    => ($_POST['shelf_life_days']    ?? '') !== '' ? (int)$_POST['shelf_life_days']    : null,
            'is_vegan'           => !empty($_POST['is_vegan']),
            'is_gluten_free'     => !empty($_POST['is_gluten_free']),
            'openfoodfacts_id'   => trim($_POST['openfoodfacts_id']   ?? ''),
            'category_ids'       => $_POST['category_ids']  ?? [],
            'allergen_ids'       => $_POST['allergen_ids']  ?? [],
            'season_ids'         => $_POST['season_ids']    ?? [],
            'region_ids'         => $_POST['region_ids']    ?? [],
        ];
    }

    private function jsonSuccess(mixed $data, string $message = 'OK', int $status = 200): void
    {
        http_response_code($status);
        echo json_encode(
            ['success' => true, 'message' => $message, 'data' => $data],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(
            ['success' => false, 'message' => $message, 'data' => null],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}