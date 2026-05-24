<?php

require_once __DIR__ . '/../repositories/ProductRepository.php';

class ProductService
{
    public function __construct(private readonly ProductRepository $productRepository) {}

    public function getAll(array $filters = [], int $limit = 12, int $offset = 0, ?int $userId = null): array
    {
        $limit  = max(1, min($limit, 50));
        $offset = max(0, $offset);

        $cleanFilters = $this->cleanFilters($filters);
        $products     = $this->productRepository->findAll($cleanFilters, $limit, $offset);
        $total        = $this->productRepository->countAll($cleanFilters);

        return [
            'products' => array_map(
                fn(Product $product) => $this->enrichProduct($product, $userId),
                $products
            ),
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    public function getById(int $id): ?array
    {
        if ($id <= 0) return null;
        $product = $this->productRepository->findById($id);
        return $product ? $this->enrichProduct($product) : null;
    }

    public function getBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $product = $this->productRepository->findBySlug($slug);
        return $product ? $this->enrichProduct($product) : null;
    }

    public function getTopViewed(int $limit = 10): array
    {
        $limit    = max(1, min($limit, 20));
        $products = $this->productRepository->findTopViewed($limit);
        return array_map(fn(Product $p) => $this->enrichProduct($p), $products);
    }

    public function getCategoriesForProduct(int $id): array
    {
        return array_map(
            fn(Category $c) => $this->escapeArray($c->toArray()),
            $this->productRepository->findCategories($id)
        );
    }

    public function getAllergensForProduct(int $id): array
    {
        return array_map(
            fn(Allergen $a) => $this->escapeArray($a->toArray()),
            $this->productRepository->findAllergens($id)
        );
    }

    /**
     * Îmbogățește produsul cu toate datele relaționate:
     * categorii, alergeni, sezoane, regiuni, localuri, rating, favorite.
     */
    private function enrichProduct(Product $product, ?int $userId = null): array
    {
        $data = $product->toArray();

        $data['categories'] = array_map(
            fn(Category $c) => $c->toArray(),
            $this->productRepository->findCategories($product->id)
        );

        $data['allergens'] = array_map(
            fn(Allergen $a) => $a->toArray(),
            $this->productRepository->findAllergens($product->id)
        );

        // Sezoane, regiuni, localuri
        $data['seasons'] = $this->productRepository->findSeasons($product->id);
        $data['regions'] = $this->productRepository->findRegions($product->id);
        $data['venues']  = $this->productRepository->findVenues($product->id);

        // Rating
        $data['average_rating'] = $this->productRepository->getAverageRating($product->id);
        $data['ratings_count']  = $this->productRepository->countRatings($product->id);

        // Favorite
        $data['is_favorite']     = $userId
            ? $this->productRepository->isFavorite($userId, $product->id)
            : false;
        $data['favorites_count'] = $this->productRepository->countFavorites($product->id);

        return $data;
    }

    private function cleanFilters(array $filters): array
    {
        $clean = [];

        if (!empty($filters['category_id'])) {
            $clean['category_id'] = (int)$filters['category_id'];
        }
        if (!empty($filters['category'])) {
            $clean['category'] = trim((string)$filters['category']);
        }
        if (!empty($filters['season'])) {
            $allowed = ['spring', 'summer', 'autumn', 'winter'];
            $season  = trim((string)$filters['season']);
            if (in_array($season, $allowed, true)) {
                $clean['season'] = $season;
            }
        }
        if (!empty($filters['region'])) {
            $clean['region'] = trim((string)$filters['region']);
        }
        if (!empty($filters['is_vegan'])) {
            $clean['is_vegan'] = true;
        }
        if (!empty($filters['is_gluten_free'])) {
            $clean['is_gluten_free'] = true;
        }
        if (!empty($filters['search'])) {
            $clean['search'] = trim((string)$filters['search']);
        }
        if (!empty($filters['brand'])) {
            $clean['brand'] = trim((string)$filters['brand']);
        }

        return $clean;
    }

    private function escapeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $data[$key] = $this->escapeArray($value);
            }
        }
        return $data;
    }

    public function toggleFavorite(int $userId, int $productId): array
    {
        if ($userId <= 0 || $productId <= 0) {
            throw new InvalidArgumentException('Date invalide pentru favorite.');
        }
        $product = $this->productRepository->findById($productId);
        if ($product === null) throw new RuntimeException('Produsul nu există.');

        $isFavorite = $this->productRepository->isFavorite($userId, $productId);
        if ($isFavorite) {
            $this->productRepository->removeFavorite($userId, $productId);
            $isFavorite = false;
        } else {
            $this->productRepository->addFavorite($userId, $productId);
            $isFavorite = true;
        }

        return [
            'is_favorite'     => $isFavorite,
            'favorites_count' => $this->productRepository->countFavorites($productId),
        ];
    }

    public function isFavorite(int $userId, int $productId): bool
    {
        if ($userId <= 0 || $productId <= 0) return false;
        return $this->productRepository->isFavorite($userId, $productId);
    }

    public function addRating(int $userId, int $productId, int $rating, ?string $review = null): array
    {
        if ($userId <= 0 || $productId <= 0) {
            throw new InvalidArgumentException('Date invalide pentru rating.');
        }
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Ratingul trebuie să fie între 1 și 5.');
        }
        $product = $this->productRepository->findById($productId);
        if ($product === null) throw new RuntimeException('Produsul nu există.');

        $review = $review !== null ? trim($review) : null;
        if ($review === '') $review = null;

        $this->productRepository->addRating($userId, $productId, $rating, $review);

        return [
            'average_rating' => $this->productRepository->getAverageRating($productId),
            'ratings_count'  => $this->productRepository->countRatings($productId),
            'ratings'        => $this->getRatings($productId),
        ];
    }

    public function getRatings(int $productId): array
    {
        if ($productId <= 0) return [];

        return array_map(function (array $r) {
            return [
                'id'         => (int)$r['id'],
                'user_id'    => (int)$r['user_id'],
                'product_id' => (int)$r['product_id'],
                'rating'     => (int)$r['rating'],
                'review'     => $r['review'],
                'username'   => htmlspecialchars($r['username'], ENT_QUOTES, 'UTF-8'),
                'created_at' => $r['created_at'],
            ];
        }, $this->productRepository->findRatings($productId));
    }

    public function create(array $data, int $userId): array
    {
        $cleanData = $this->validateProductData($data);
        $cleanData['created_by'] = $userId;
        $productId = $this->productRepository->create($cleanData);

        if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
            $this->productRepository->syncCategories($productId, array_map('intval', $data['category_ids']));
        }
        if (!empty($data['allergen_ids']) && is_array($data['allergen_ids'])) {
            $this->productRepository->syncAllergens($productId, array_map('intval', $data['allergen_ids']));
        }

        return $this->getById($productId);
    }

    public function update(int $id, array $data): array
    {
        if ($id <= 0) throw new InvalidArgumentException('Produs invalid.');
        if ($this->productRepository->findById($id) === null) throw new RuntimeException('Produsul nu există.');

        $this->productRepository->update($id, $this->validateProductData($data));

        if (isset($data['category_ids']) && is_array($data['category_ids'])) {
            $this->productRepository->syncCategories($id, array_map('intval', $data['category_ids']));
        }
        if (isset($data['allergen_ids']) && is_array($data['allergen_ids'])) {
            $this->productRepository->syncAllergens($id, array_map('intval', $data['allergen_ids']));
        }

        return $this->getById($id);
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) throw new InvalidArgumentException('Produs invalid.');
        if ($this->productRepository->findById($id) === null) throw new RuntimeException('Produsul nu există.');
        return $this->productRepository->delete($id);
    }

    private function validateProductData(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') throw new InvalidArgumentException('Numele produsului este obligatoriu.');

        $price = $data['price'] ?? null;
        if ($price !== null && $price !== '' && (float)$price < 0) {
            throw new InvalidArgumentException('Prețul nu poate fi negativ.');
        }

        return [
            'name'               => $name,
            'description'        => trim((string)($data['description'] ?? '')),
            'price'              => $price !== '' ? $price : null,
            'image_url'          => trim((string)($data['image_url'] ?? '')),
            'ingredients'        => trim((string)($data['ingredients'] ?? '')),
            'barcode'            => trim((string)($data['barcode'] ?? '')),
            'brand'              => trim((string)($data['brand'] ?? '')),
            'volume_ml'          => !empty($data['volume_ml']) ? (int)$data['volume_ml'] : null,
            'calories_per_100ml' => !empty($data['calories_per_100ml']) ? (float)$data['calories_per_100ml'] : null,
            'sugar_per_100ml'    => !empty($data['sugar_per_100ml']) ? (float)$data['sugar_per_100ml'] : null,
            'is_perishable'      => !empty($data['is_perishable']),
            'shelf_life_days'    => !empty($data['shelf_life_days']) ? (int)$data['shelf_life_days'] : null,
            'is_vegan'           => !empty($data['is_vegan']),
            'is_gluten_free'     => !empty($data['is_gluten_free']),
            'openfoodfacts_id'   => trim((string)($data['openfoodfacts_id'] ?? '')),
        ];
    }
}