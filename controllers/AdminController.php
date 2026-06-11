<?php

class AdminController
{
    public function __construct(
        private PDO            $db,
        private UserRepository $userRepo,
        private ProductRepository $productRepo,
    ) {}

    public function getStats(): array
    {
        return [
            'total_products'=> (int)$this->db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
            'total_users' => (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_lists' => (int)$this->db->query("SELECT COUNT(*) FROM shopping_lists")->fetchColumn(),
            'total_ratings'=> (int)$this->db->query("SELECT COUNT(*) FROM product_ratings")->fetchColumn(),
            'total_favorites'=> (int)$this->db->query("SELECT COUNT(*) FROM user_favorites")->fetchColumn(),
        ];
    }

    //le facem cu paginare
    public function listProducts(int $page = 1, string $search = '', int $perPage = 15): array
    {
        $offset  = ($page - 1) * $perPage;
        $filters = $search !== '' ? ['search' => $search] : [];
        $products = $this->productRepo->findAll($filters, $perPage, $offset);
        $total    = $this->productRepo->countAll($filters);
        return [
            'products'   => array_map(fn(Product $p) => $this->serializeProduct($p), $products),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    public function getFormData(): array
    {
        $categories = $this->db->query("SELECT id, name, slug FROM categories ORDER BY name")->fetchAll();
        $allergens  = $this->db->query("SELECT id, name FROM allergens ORDER BY name")->fetchAll();
        $seasons    = $this->db->query("SELECT id, name FROM seasons ORDER BY id")->fetchAll();
        $regions    = $this->db->query("SELECT id, name, country FROM regions ORDER BY country, name")->fetchAll();
        return compact('categories', 'allergens', 'seasons', 'regions');
    }


    public function createProduct(array $data, int $adminId): int
    {
        $data['created_by'] = $adminId;
        $productId = $this->productRepo->create($data);
        $this->syncRelations($productId, $data);
        return $productId;
    }


    public function updateProduct(int $id, array $data): bool
    {
        $ok = $this->productRepo->update($id, $data);
        if ($ok) $this->syncRelations($id, $data);
        return $ok;
    }

    public function deleteProduct(int $id): bool
    {
        return $this->productRepo->delete($id);
    }


    public function importCsv(array $file, int $adminId): array
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Eroare la upload fișier.');
        }
        $handle  = fopen($file['tmp_name'], 'r');
        $headers = fgetcsv($handle);
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $imported = 0;
        $errors   = [];
        $line     = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count($row) < 2) continue;
            $data = array_combine($headers, array_pad($row, count($headers), ''));

            if (empty(trim($data['name'] ?? ''))) {
                $errors[] = "Linia $line: numele lipsește.";
                continue;
            }

            try {
                $this->productRepo->create([
                    'name'               => trim($data['name']),
                    'description'        => trim($data['description']        ?? ''),
                    'price'              => is_numeric($data['price'] ?? '') ? (float)$data['price'] : null,
                    'brand'              => trim($data['brand']              ?? ''),
                    'image_url'          => trim($data['image_url']          ?? ''),
                    'ingredients'        => trim($data['ingredients']        ?? ''),
                    'barcode'            => trim($data['barcode']            ?? ''),
                    'volume_ml'          => is_numeric($data['volume_ml'] ?? '') ? (int)$data['volume_ml'] : null,
                    'calories_per_100ml' => is_numeric($data['calories_per_100ml'] ?? '') ? (float)$data['calories_per_100ml'] : null,
                    'sugar_per_100ml'    => is_numeric($data['sugar_per_100ml']    ?? '') ? (float)$data['sugar_per_100ml']    : null,
                    'is_perishable'      => in_array(strtolower($data['is_perishable'] ?? ''), ['1', 'true', 'da', 'yes']),
                    'shelf_life_days'    => is_numeric($data['shelf_life_days'] ?? '') ? (int)$data['shelf_life_days'] : null,
                    'is_vegan'           => in_array(strtolower($data['is_vegan']      ?? ''), ['1', 'true', 'da', 'yes']),
                    'is_gluten_free'     => in_array(strtolower($data['is_gluten_free'] ?? ''), ['1', 'true', 'da', 'yes']),
                    'openfoodfacts_id'   => trim($data['openfoodfacts_id']   ?? ''),
                    'created_by'         => $adminId,
                ]);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Linia $line: " . $e->getMessage();
            }
        }
        fclose($handle);
        return ['imported' => $imported, 'errors' => $errors];
    }


    public function exportCsv(): string
    {
        $products = $this->productRepo->findAll([], 10000, 0);

        $rows = [['ID', 'Nume', 'Brand', 'Pret', 'Volum (ml)', 'Calorii/100ml',
            'Zahar/100ml', 'Vegan', 'Fara gluten', 'Ingrediente',
            'Perisabil', 'Zile valabilitate', 'Vizualizari', 'Data adaugare']];

        foreach ($products as $p) {
            $rows[] = [
                $p->id,
                $p->name,
                $p->brand              ?? '',
                $p->price              ?? '',
                $p->volumeMl           ?? '',
                $p->caloriesPer100ml   ?? '',
                $p->sugarPer100ml      ?? '',
                $p->isVegan            ? 'da' : 'nu',
                $p->isGlutenFree       ? 'da' : 'nu',
                $p->ingredients        ?? '',
                $p->isPerishable       ? 'da' : 'nu',
                $p->shelfLifeDays      ?? '',
                $p->viewCount,
                $p->createdAt,
            ];
        }

        $out = '';
        foreach ($rows as $row) {
            $out .= implode(',', array_map(
                    fn($cell) => '"' . str_replace('"', '""', $cell) . '"',
                    $row
                )) . "\n";
        }

        return $out;
    }

    public function exportJson(): string
    {
        $products = $this->productRepo->findAll([], 10000, 0);
        $data     = array_map(fn(Product $p) => $p->toArray(), $products);
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }


    public function listUsers(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare("
            SELECT
                u.id, u.username, u.email, u.role, u.avatar_url, u.created_at,
                COUNT(DISTINCT sl.id)  AS list_count,
                COUNT(DISTINCT uf.product_id) AS favorite_count
            FROM users u
            LEFT JOIN shopping_lists sl ON sl.user_id = u.id
            LEFT JOIN user_favorites uf ON uf.user_id = u.id
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        $users = $stmt->fetchAll();
        $total = $this->userRepo->countAll();

        $users = array_map(fn($u) => $this->serializeUser($u), $users);
        return [
            'users'       => $users,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }


    public function updateUserRole(int $targetId, string $role, int $adminId): bool
    {
        if ($targetId === $adminId) {
            throw new RuntimeException('Nu îți poți schimba propriul rol.');
        }
        $allowed = ['user', 'admin'];
        if (!in_array($role, $allowed, true)) {
            throw new RuntimeException('Rol invalid.');
        }
        $user = $this->userRepo->findById($targetId);
        if ($user === null) throw new RuntimeException('Userul nu există.');
        return $this->userRepo->update($targetId, [
            'username'   => $user->username,
            'email'      => $user->email,
            'role'       => $role,
            'avatar_url' => $user->avatarUrl,
        ]);
    }


    public function deleteUser(int $targetId, int $adminId): bool
    {
        if ($targetId === $adminId) {
            throw new RuntimeException('Nu te poți șterge pe tine însuți.');
        }
        $user = $this->userRepo->findById($targetId);
        if ($user === null) throw new RuntimeException('Userul nu există.');
        return $this->userRepo->delete($targetId);
    }


    private function syncRelations(int $productId, array $data): void
    {
        // categorii
        $categoryIds = array_map('intval', (array)($data['category_ids'] ?? []));
        $this->productRepo->syncCategories($productId, $categoryIds);

        // alergeni
        $allergenIds = array_map('intval', (array)($data['allergen_ids'] ?? []));
        $this->productRepo->syncAllergens($productId, $allergenIds);

        // sezoane — optional, doar daca exista metoda in repo
        if (!empty($data['season_ids']) && method_exists($this->productRepo, 'syncSeasons')) {
            $seasonIds = array_map('intval', (array)$data['season_ids']);
            $this->productRepo->syncSeasons($productId, $seasonIds);
        }

        // regiuni — optional
        if (!empty($data['region_ids']) && method_exists($this->productRepo, 'syncRegions')) {
            $regionIds = array_map('intval', (array)$data['region_ids']);
            $this->productRepo->syncRegions($productId, $regionIds);
        }
    }

    private function serializeProduct(Product $p): array
    {
        return [
            'id'                 => $p->id,
            'name'               => htmlspecialchars($p->name,        ENT_QUOTES, 'UTF-8'),
            'slug'               => $p->slug,
            'brand'              => $p->brand        ? htmlspecialchars($p->brand, ENT_QUOTES, 'UTF-8') : null,
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
}