<?php

require_once __DIR__ . '/../repositories/AdminRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';

class AdminService
{
    private const ALLOWED_ROLES = ['user', 'admin'];

    public function __construct(
        private readonly AdminRepository   $adminRepo,
        private readonly UserRepository    $userRepo,
        private readonly ProductRepository $productRepo,
    ) {}

    public function getStats(): array
    {
        return $this->adminRepo->getStats();
    }

    public function getFormData(): array
    {
        return $this->adminRepo->getFormData();
    }

    public function listProducts(int $page = 1, string $search = '', int $perPage = 15): array
    {
        $offset   = ($page - 1) * $perPage;
        $filters  = $search !== '' ? ['search' => $search] : [];
        $products = $this->productRepo->findAll($filters, $perPage, $offset);
        $total    = $this->productRepo->countAll($filters);

        return [
            'products'    => $products, // obiecte Product — controller-ul le serializează
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }


    public function createProduct(array $data, int $adminId): int
    {
        if (empty(trim($data['name'] ?? ''))) {
            throw new InvalidArgumentException('Numele produsului este obligatoriu.');
        }

        $data['created_by'] = $adminId;
        $productId = $this->productRepo->create($data);
        $this->syncRelations($productId, $data);

        return $productId;
    }


    public function updateProduct(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID produs invalid.');
        }
        if (empty(trim($data['name'] ?? ''))) {
            throw new InvalidArgumentException('Numele produsului este obligatoriu.');
        }

        $ok = $this->productRepo->update($id, $data);
        if (!$ok) {
            throw new RuntimeException('Actualizarea a eșuat.');
        }

        $this->syncRelations($id, $data);
    }


    public function deleteProduct(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID produs invalid.');
        }

        $ok = $this->productRepo->delete($id);
        if (!$ok) {
            throw new RuntimeException('Ștergerea a eșuat.');
        }
    }

    // -------------------------------------------------------------------------
    // IMPORT / EXPORT
    // -------------------------------------------------------------------------

    /**
     * Importă produse dintr-un fișier CSV uploadat.
     *
     * Format CSV așteptat (primul rând = header):
     * name, description, price, brand, image_url, ingredients, barcode,
     * volume_ml, calories_per_100ml, sugar_per_100ml, is_perishable,
     * shelf_life_days, is_vegan, is_gluten_free, openfoodfacts_id
     *
     * Câmpul 'name' este obligatoriu — rândurile fără nume sunt sărite.
     * Erorile per rând sunt colectate și returnate — nu opresc importul.
     *
     * @param  array $file     array din $_FILES['csv']
     * @param  int   $adminId  ID-ul adminului care face importul
     * @return array           ['imported' => int, 'errors' => string[]]
     * @throws RuntimeException dacă fișierul nu poate fi deschis
     */
    public function importCsv(array $file, int $adminId): array
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Eroare la upload fișier.');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            throw new RuntimeException('Nu s-a putut deschide fișierul.');
        }

        // Primul rând = header-ele coloanelor
        $headers = fgetcsv($handle);
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $imported = 0;
        $errors   = [];
        $line     = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            // Sărim rândurile goale
            if (count($row) < 2) continue;

            // Combinăm header-ele cu valorile rândului curent
            $data = array_combine($headers, array_pad($row, count($headers), ''));

            if (empty(trim($data['name'] ?? ''))) {
                $errors[] = "Linia $line: numele lipsește.";
                continue;
            }

            try {
                $this->productRepo->create([
                    'name'               => trim($data['name']),
                    'description'        => trim($data['description']        ?? ''),
                    'price'              => is_numeric($data['price']        ?? '') ? (float)$data['price']              : null,
                    'brand'              => trim($data['brand']              ?? ''),
                    'image_url'          => trim($data['image_url']          ?? ''),
                    'ingredients'        => trim($data['ingredients']        ?? ''),
                    'barcode'            => trim($data['barcode']            ?? ''),
                    'volume_ml'          => is_numeric($data['volume_ml']    ?? '') ? (int)$data['volume_ml']            : null,
                    'calories_per_100ml' => is_numeric($data['calories_per_100ml'] ?? '') ? (float)$data['calories_per_100ml'] : null,
                    'sugar_per_100ml'    => is_numeric($data['sugar_per_100ml']    ?? '') ? (float)$data['sugar_per_100ml']    : null,
                    // acceptăm mai multe formate pentru boolean: 1, true, da, yes
                    'is_perishable'  => in_array(strtolower($data['is_perishable']  ?? ''), ['1', 'true', 'da', 'yes']),
                    'shelf_life_days'=> is_numeric($data['shelf_life_days']  ?? '') ? (int)$data['shelf_life_days']      : null,
                    'is_vegan'       => in_array(strtolower($data['is_vegan']       ?? ''), ['1', 'true', 'da', 'yes']),
                    'is_gluten_free' => in_array(strtolower($data['is_gluten_free'] ?? ''), ['1', 'true', 'da', 'yes']),
                    'openfoodfacts_id' => trim($data['openfoodfacts_id']     ?? ''),
                    'created_by'       => $adminId,
                ]);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Linia $line: " . $e->getMessage();
            }
        }

        fclose($handle);
        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Exportă toate produsele ca string CSV.
     * Celulele cu virgule sau ghilimele sunt escapate corect.
     * BOM-ul UTF-8 e adăugat de controller pentru compatibilitate Excel.
     */
    public function exportCsv(): string
    {
        $products = $this->productRepo->findAll([], 10000, 0);

        // Header-ul CSV
        $rows = [[
            'ID', 'Nume', 'Brand', 'Pret', 'Volum (ml)', 'Calorii/100ml',
            'Zahar/100ml', 'Vegan', 'Fara gluten', 'Ingrediente',
            'Perisabil', 'Zile valabilitate', 'Vizualizari', 'Data adaugare',
        ]];

        foreach ($products as $p) {
            $rows[] = [
                $p->id,
                $p->name,
                $p->brand            ?? '',
                $p->price            ?? '',
                $p->volumeMl         ?? '',
                $p->caloriesPer100ml ?? '',
                $p->sugarPer100ml    ?? '',
                $p->isVegan          ? 'da' : 'nu',
                $p->isGlutenFree     ? 'da' : 'nu',
                $p->ingredients      ?? '',
                $p->isPerishable     ? 'da' : 'nu',
                $p->shelfLifeDays    ?? '',
                $p->viewCount,
                $p->createdAt,
            ];
        }

        // Construim string-ul CSV manual
        // Fiecare celulă e înconjurată de ghilimele, ghilimelele din interior sunt dublate
        $out = '';
        foreach ($rows as $row) {
            $out .= implode(',', array_map(
                    fn($cell) => '"' . str_replace('"', '""', (string)$cell) . '"',
                    $row
                )) . "\n";
        }

        return $out;
    }

    /**
     * Exportă toate produsele ca string JSON formatat.
     */
    public function exportJson(): string
    {
        $products = $this->productRepo->findAll([], 10000, 0);
        $data     = array_map(fn(Product $p) => $p->toArray(), $products);
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // -------------------------------------------------------------------------
    // USERI
    // -------------------------------------------------------------------------

    /**
     * Returnează userii paginați cu date agregate (nr liste, nr favorite).
     *
     * @return array  ['users' => [...], 'total' => int, 'page' => int, ...]
     */
    public function listUsers(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $users  = $this->adminRepo->findUsersPaginated($perPage, $offset);
        $total  = $this->userRepo->countAll();

        return [
            'users'       => $users, // array-uri raw — controller-ul le serializează
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Schimbă rolul unui user.
     *
     * Reguli:
     * - Adminul nu își poate schimba propriul rol
     * - Rolul trebuie să fie 'user' sau 'admin'
     * - Userul target trebuie să existe
     *
     * @throws RuntimeException dacă oricare regulă e încălcată
     */
    public function updateUserRole(int $targetId, string $role, int $adminId): void
    {
        if ($targetId === $adminId) {
            throw new RuntimeException('Nu îți poți schimba propriul rol.');
        }
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new RuntimeException('Rol invalid. Valori permise: user, admin.');
        }

        $user = $this->userRepo->findById($targetId);
        if ($user === null) {
            throw new RuntimeException('Userul nu există.');
        }

        $this->userRepo->update($targetId, [
            'username'   => $user->username,
            'email'      => $user->email,
            'role'       => $role,
            'avatar_url' => $user->avatarUrl ?? null,
        ]);
    }

    /**
     * Șterge un user și toate datele lui.
     *
     * Reguli:
     * - Adminul nu se poate șterge pe sine
     * - Userul target trebuie să existe
     *
     * @throws RuntimeException dacă oricare regulă e încălcată
     */
    public function deleteUser(int $targetId, int $adminId): void
    {
        if ($targetId === $adminId) {
            throw new RuntimeException('Nu te poți șterge pe tine însuți.');
        }

        $user = $this->userRepo->findById($targetId);
        if ($user === null) {
            throw new RuntimeException('Userul nu există.');
        }

        $this->userRepo->delete($targetId);
    }

    // -------------------------------------------------------------------------
    // HELPERS PRIVATE
    // -------------------------------------------------------------------------

    /**
     * Sincronizează relațiile many-to-many ale unui produs:
     * categorii, alergeni, sezoane, regiuni.
     *
     * "Sync" înseamnă: șterge tot ce era, inserează ce vine acum.
     * E mai simplu decât a calcula diferența.
     */
    private function syncRelations(int $productId, array $data): void
    {
        // Categorii — obligatorii în ProductRepository
        $categoryIds = array_map('intval', (array)($data['category_ids'] ?? []));
        $this->productRepo->syncCategories($productId, $categoryIds);

        // Alergeni
        $allergenIds = array_map('intval', (array)($data['allergen_ids'] ?? []));
        $this->productRepo->syncAllergens($productId, $allergenIds);

        // Sezoane — verificăm că metoda există (poate fi adăugată ulterior)
        if (!empty($data['season_ids']) && method_exists($this->productRepo, 'syncSeasons')) {
            $seasonIds = array_map('intval', (array)$data['season_ids']);
            $this->productRepo->syncSeasons($productId, $seasonIds);
        }

        // Regiuni
        if (!empty($data['region_ids']) && method_exists($this->productRepo, 'syncRegions')) {
            $regionIds = array_map('intval', (array)$data['region_ids']);
            $this->productRepo->syncRegions($productId, $regionIds);
        }
    }
}