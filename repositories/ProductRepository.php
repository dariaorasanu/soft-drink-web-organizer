<?php

require_once __DIR__ . '/interfaces/ProductRepositoryInterface.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Allergen.php';

class ProductRepository implements ProductRepositoryInterface
{
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
        foreach ($params as $key => $value) $stmt->bindValue($key, $value);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn(array $row) => Product::fromArray($row), $stmt->fetchAll());
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
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY view_count DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn(array $row) => Product::fromArray($row), $stmt->fetchAll());
    }

    public function incrementView(int $productId): void
    {
        $this->productRepository->incrementViewCount($productId);
    }

    public function countAll(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = "SELECT COUNT(DISTINCT p.id) FROM products p LEFT JOIN product_categories pc ON pc.product_id = p.id $where";
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
            ':name'               => $data['name'],
            ':slug'               => $this->generateSlug($data['name']),
            ':description'        => $data['description'] ?: null,
            ':price'              => $data['price'] ?? null,
            ':image_url'          => $data['image_url'] ?: null,
            ':ingredients'        => $data['ingredients'] ?: null,
            ':barcode'            => $data['barcode'] ?: null,
            ':brand'              => $data['brand'] ?: null,
            ':volume_ml'          => $data['volume_ml'] ?? null,
            ':calories_per_100ml' => $data['calories_per_100ml'] ?? null,
            ':sugar_per_100ml'    => $data['sugar_per_100ml'] ?? null,
            ':is_perishable'      => !empty($data['is_perishable']) ? 'true' : 'false',
            ':shelf_life_days'    => $data['shelf_life_days'] ?? null,
            ':is_vegan'           => !empty($data['is_vegan']) ? 'true' : 'false',
            ':is_gluten_free'     => !empty($data['is_gluten_free']) ? 'true' : 'false',
            ':openfoodfacts_id'   => !empty($data['openfoodfacts_id']) ? $data['openfoodfacts_id'] : null,
            ':created_by'         => $data['created_by'] ?? null,
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
            ':id'                 => $id,
            ':name'               => $data['name'],
            ':description'        => !empty($data['description']) ? $data['description'] : null,
            ':price'              => $data['price'] !== '' ? $data['price'] : null,
            ':image_url'          => !empty($data['image_url']) ? $data['image_url'] : null,
            ':ingredients'        => !empty($data['ingredients']) ? $data['ingredients'] : null,
            ':brand'              => !empty($data['brand']) ? $data['brand'] : null,
            ':volume_ml'          => !empty($data['volume_ml']) ? (int)$data['volume_ml'] : null,
            ':calories_per_100ml' => !empty($data['calories_per_100ml']) ? (float)$data['calories_per_100ml'] : null,
            ':sugar_per_100ml'    => !empty($data['sugar_per_100ml']) ? (float)$data['sugar_per_100ml'] : null,
            ':is_perishable'      => !empty($data['is_perishable']) ? 'true' : 'false',
            ':shelf_life_days'    => !empty($data['shelf_life_days']) ? (int)$data['shelf_life_days'] : null,
            ':is_vegan'           => !empty($data['is_vegan']) ? 'true' : 'false',
            ':is_gluten_free'     => !empty($data['is_gluten_free']) ? 'true' : 'false',
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function incrementViewCount(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function findCategories(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.* FROM categories c
            JOIN product_categories pc ON pc.category_id = c.id
            WHERE pc.product_id = :product_id
        ");
        $stmt->execute([':product_id' => $productId]);
        return array_map(fn(array $row) => Category::fromArray($row), $stmt->fetchAll());
    }

    public function syncCategories(int $productId, array $categoryIds): void
    {
        $this->db->prepare("DELETE FROM product_categories WHERE product_id = :id")->execute([':id' => $productId]);
        $ins = $this->db->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (:pid, :cid)");
        foreach ($categoryIds as $cid) $ins->execute([':pid' => $productId, ':cid' => $cid]);
    }


    public function findAllergens(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT a.* FROM allergens a
            JOIN product_allergens pa ON pa.allergen_id = a.id
            WHERE pa.product_id = :product_id
        ");
        $stmt->execute([':product_id' => $productId]);
        return array_map(fn(array $row) => Allergen::fromArray($row), $stmt->fetchAll());
    }

    public function syncAllergens(int $productId, array $allergenIds): void
    {
        $this->db->prepare("DELETE FROM product_allergens WHERE product_id = :id")->execute([':id' => $productId]);
        $ins = $this->db->prepare("INSERT INTO product_allergens (product_id, allergen_id) VALUES (:pid, :aid)");
        foreach ($allergenIds as $aid) $ins->execute([':pid' => $productId, ':aid' => $aid]);
    }


    public function findSeasons(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.id, s.name AS slug, s.name
            FROM seasons s
            JOIN product_seasons ps ON ps.season_id = s.id
            WHERE ps.product_id = :product_id
        ");
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll();
    }


    public function findRegions(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.name, r.country
            FROM regions r
            JOIN product_regions pr ON pr.region_id = r.id
            WHERE pr.product_id = :product_id
        ");
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll();
    }


    public function findVenues(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT v.id, v.name, v.address, v.city,
                   pv.price_at_venue AS price
            FROM venues v
            JOIN product_venues pv ON pv.venue_id = v.id
            WHERE pv.product_id = :product_id
            ORDER BY v.name
        ");
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll();
    }


    public function isFavorite(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM user_favorites WHERE user_id = :uid AND product_id = :pid LIMIT 1");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        return (bool)$stmt->fetchColumn();
    }

    public function addFavorite(int $userId, int $productId): void
    {
        $stmt = $this->db->prepare("INSERT INTO user_favorites (user_id, product_id) VALUES (:uid, :pid) ON CONFLICT DO NOTHING");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    }

    public function removeFavorite(int $userId, int $productId): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_favorites WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    }

    public function countFavorites(int $productId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_favorites WHERE product_id = :pid");
        $stmt->execute([':pid' => $productId]);
        return (int)$stmt->fetchColumn();
    }


    public function addRating(int $userId, int $productId, int $rating, ?string $review = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO product_ratings (user_id, product_id, rating, review)
            VALUES (:uid, :pid, :rating, :review)
            ON CONFLICT (user_id, product_id) DO UPDATE SET
                rating = EXCLUDED.rating,
                review = EXCLUDED.review,
                created_at = NOW()
        ");
        $stmt->execute([':uid' => $userId, ':pid' => $productId, ':rating' => $rating, ':review' => $review]);
    }

    public function findRatings(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT pr.id, pr.user_id, pr.product_id, pr.rating, pr.review, pr.created_at, u.username
            FROM product_ratings pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.product_id = :pid
            ORDER BY pr.created_at DESC
        ");
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll();
    }

    public function getAverageRating(int $productId): ?float
    {
        $stmt = $this->db->prepare("SELECT AVG(rating) FROM product_ratings WHERE product_id = :pid");
        $stmt->execute([':pid' => $productId]);
        $avg = $stmt->fetchColumn();
        return $avg !== null ? round((float)$avg, 2) : null;
    }

    public function countRatings(int $productId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM product_ratings WHERE product_id = :pid");
        $stmt->execute([':pid' => $productId]);
        return (int)$stmt->fetchColumn();
    }



    private function buildFilters(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['category_id'])) {
            $conditions[] = 'pc.category_id = :category_id';
            $params[':category_id'] = (int)$filters['category_id'];
        }
        if (!empty($filters['category'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM product_categories pc2 JOIN categories c2 ON c2.id = pc2.category_id WHERE pc2.product_id = p.id AND c2.slug = :category)';
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['season'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM product_seasons ps2 JOIN seasons s2 ON s2.id = ps2.season_id WHERE ps2.product_id = p.id AND s2.name = :season)';
            $params[':season'] = $filters['season'];
        }
        if (!empty($filters['region'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM product_regions pr2 JOIN regions r2 ON r2.id = pr2.region_id WHERE pr2.product_id = p.id AND r2.name ILIKE :region)';
            $params[':region'] = $filters['region'];
        }
        if (!empty($filters['is_vegan']))       $conditions[] = 'p.is_vegan = true';
        if (!empty($filters['is_gluten_free'])) $conditions[] = 'p.is_gluten_free = true';
        if (!empty($filters['search'])) {
            $conditions[] = '(p.name ILIKE :search OR p.brand ILIKE :search OR p.description ILIKE :search OR p.ingredients ILIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['brand'])) {
            $conditions[] = 'p.brand ILIKE :brand';
            $params[':brand'] = '%' . $filters['brand'] . '%';
        }

        $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        return [$where, $params];
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = str_replace(
            ['ă','â','î','ș','ț','Ă','Â','Î','Ș','Ț'],
            ['a','a','i','s','t','a','a','i','s','t'],
            $slug
        );
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        $original = $slug;
        $counter  = 1;
        while ($this->slugExists($slug)) {
            $slug = $original . '-' . $counter++;
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