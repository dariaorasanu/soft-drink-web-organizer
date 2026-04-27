<?php

require_once __DIR__ . '/Interfaces/ProductRepositoryInterface.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Allergen.php';

class ProductRepository implements ProductRepositoryInterface
{
    //injectam baza de date
    public function __construct(private PDO $db) {}


    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $sql = "
            SELECT DISTINCT p.*
            FROM products p
            LEFT JOIN product_categories pc ON pc.product_id = p.id
            $where
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn(array $row) => Product::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? Product::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Product
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? Product::fromArray($row) : null;
    }

    public function findTopViewed(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM products
            ORDER BY view_count DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn(array $row) => Product::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function countAll(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);

        $sql = "
            SELECT COUNT(DISTINCT p.id)
            FROM products p
            LEFT JOIN product_categories pc ON pc.product_id = p.id
            $where
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO products
                (name, slug, description, price, image_url, ingredients, barcode,
                 brand, volume_ml, calories_per_100ml, sugar_per_100ml,
                 is_perishable, shelf_life_days, is_vegan, is_gluten_free,
                 openfoodfacts_id, created_by)
            VALUES
                (:name, :slug, :description, :price, :image_url, :ingredients, :barcode,
                 :brand, :volume_ml, :calories_per_100ml, :sugar_per_100ml,
                 :is_perishable, :shelf_life_days, :is_vegan, :is_gluten_free,
                 :openfoodfacts_id, :created_by)
            RETURNING id
        ");

        $stmt->execute([
            ':name'             => $data['name'],
            ':slug'             => $this->generateSlug($data['name']),
            ':description'      => $data['description']      ?? null,
            ':price'            => $data['price']            ?? null,
            ':image_url'        => $data['image_url']        ?? null,
            ':ingredients'      => $data['ingredients']      ?? null,
            ':barcode'          => $data['barcode']          ?? null,
            ':brand'            => $data['brand']            ?? null,
            ':volume_ml'        => $data['volume_ml']        ?? null,
            ':calories_per_100ml' => $data['calories_per_100ml'] ?? null,
            ':sugar_per_100ml'  => $data['sugar_per_100ml']  ?? null,
            ':is_perishable'    => $data['is_perishable']    ?? false,
            ':shelf_life_days'  => $data['shelf_life_days']  ?? null,
            ':is_vegan'         => $data['is_vegan']         ?? false,
            ':is_gluten_free'   => $data['is_gluten_free']   ?? false,
            ':openfoodfacts_id' => $data['openfoodfacts_id'] ?? null,
            ':created_by'       => $data['created_by']       ?? null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE products SET
                name               = :name,
                description        = :description,
                price              = :price,
                image_url          = :image_url,
                ingredients        = :ingredients,
                brand              = :brand,
                volume_ml          = :volume_ml,
                calories_per_100ml = :calories_per_100ml,
                sugar_per_100ml    = :sugar_per_100ml,
                is_perishable      = :is_perishable,
                shelf_life_days    = :shelf_life_days,
                is_vegan           = :is_vegan,
                is_gluten_free     = :is_gluten_free,
                updated_at         = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id'                => $id,
            ':name'              => $data['name'],
            ':description'       => $data['description']      ?? null,
            ':price'             => $data['price']            ?? null,
            ':image_url'         => $data['image_url']        ?? null,
            ':ingredients'       => $data['ingredients']      ?? null,
            ':brand'             => $data['brand']            ?? null,
            ':volume_ml'         => $data['volume_ml']        ?? null,
            ':calories_per_100ml'=> $data['calories_per_100ml'] ?? null,
            ':sugar_per_100ml'   => $data['sugar_per_100ml']  ?? null,
            ':is_perishable'     => $data['is_perishable']    ?? false,
            ':shelf_life_days'   => $data['shelf_life_days']  ?? null,
            ':is_vegan'          => $data['is_vegan']         ?? false,
            ':is_gluten_free'    => $data['is_gluten_free']   ?? false,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function incrementViewCount(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE products SET view_count = view_count + 1 WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    // ── PIVOT: categorii & alergeni ───────────────────────────────────────────

    public function findCategories(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*
            FROM categories c
            JOIN product_categories pc ON pc.category_id = c.id
            WHERE pc.product_id = :product_id
        ");
        $stmt->execute([':product_id' => $productId]);

        return array_map(
            fn(array $row) => Category::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function findAllergens(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*
            FROM allergens a
            JOIN product_allergens pa ON pa.allergen_id = a.id
            WHERE pa.product_id = :product_id
        ");
        $stmt->execute([':product_id' => $productId]);

        return array_map(
            fn(array $row) => Allergen::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function syncCategories(int $productId, array $categoryIds): void
    {
        // Șterge ce există și inserează din nou — simplu și sigur
        $del = $this->db->prepare("DELETE FROM product_categories WHERE product_id = :id");
        $del->execute([':id' => $productId]);

        $ins = $this->db->prepare("
            INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)
        ");

        foreach ($categoryIds as $categoryId) {
            $ins->execute([':product_id' => $productId, ':category_id' => $categoryId]);
        }
    }

    public function syncAllergens(int $productId, array $allergenIds): void
    {
        $del = $this->db->prepare("DELETE FROM product_allergens WHERE product_id = :id");
        $del->execute([':id' => $productId]);

        $ins = $this->db->prepare("
            INSERT INTO product_allergens (product_id, allergen_id) VALUES (:product_id, :allergen_id)
        ");

        foreach ($allergenIds as $allergenId) {
            $ins->execute([':product_id' => $productId, ':allergen_id' => $allergenId]);
        }
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    /**
     * Construiește clauza WHERE și parametrii din array-ul de filtre.
     * Returneaza [$whereClause, $params]
     */
    private function buildFilters(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['category_id'])) {
            $conditions[] = 'pc.category_id = :category_id';
            $params[':category_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['is_vegan'])) {
            $conditions[] = 'p.is_vegan = true';
        }

        if (!empty($filters['is_gluten_free'])) {
            $conditions[] = 'p.is_gluten_free = true';
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(p.name ILIKE :search OR p.brand ILIKE :search OR p.description ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['brand'])) {
            $conditions[] = 'p.brand ILIKE :brand';
            $params[':brand'] = '%' . $filters['brand'] . '%';
        }

        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        return [$where, $params];
    }

    /**
     * Generează un slug unic din numele produsului.
     * Ex: "Suc de Portocale Bio" → "suc-de-portocale-bio"
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));

        // Înlocuiește diacriticele românești
        $slug = str_replace(
            ['ă','â','î','ș','ț','Ă','Â','Î','Ș','Ț'],
            ['a','a','i','s','t','a','a','i','s','t'],
            $slug
        );

        // Înlocuiește orice non-alfanumeric cu liniuță
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Verifică unicitate și adaugă sufix dacă e nevoie
        $original = $slug;
        $counter  = 1;

        while ($this->slugExists($slug)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM products WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch() !== false;
    }
}