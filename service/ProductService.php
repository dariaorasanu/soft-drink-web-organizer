<?php

require_once __DIR__ . '/../repositories/ProductRepository.php';

class ProductService
{
    public function __construct(private readonly ProductRepository $productRepository) {}

    public function getAll(array $filters = [], int $limit = 12, int $offset = 0): array
    {
        $limit = max(1, min($limit, 50));
        $offset = max(0, $offset);

        $cleanFilters = $this->cleanFilters($filters);

        $products = $this->productRepository->findAll($cleanFilters, $limit, $offset);
        $total = $this->productRepository->countAll($cleanFilters);

        return [
            'products' => array_map(
                fn(Product $product) => $this->enrichProduct($product),
                $products
            ),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $product = $this->productRepository->findById($id);

        return $product ? $this->enrichProduct($product) : null;
    }

    public function getBySlug(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $product = $this->productRepository->findBySlug($slug);

        return $product ? $this->enrichProduct($product) : null;
    }

    public function getTopViewed(int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));

        $products = $this->productRepository->findTopViewed($limit);

        return array_map(
            fn(Product $product) => $this->enrichProduct($product),
            $products
        );
    }

    public function getCategoriesForProduct(int $id): array
    {
        return array_map(
            fn(Category $category) => $this->escapeArray($category->toArray()),
            $this->productRepository->findCategories($id)
        );
    }

    public function getAllergensForProduct(int $id): array
    {
        return array_map(
            fn(Allergen $allergen) => $this->escapeArray($allergen->toArray()),
            $this->productRepository->findAllergens($id)
        );
    }

    private function enrichProduct(Product $product): array
    {
        $data = $product->toArray();

        $data['categories'] = array_map(
            fn(Category $category) => $category->toArray(),
            $this->productRepository->findCategories($product->id)
        );

        $data['allergens'] = array_map(
            fn(Allergen $allergen) => $allergen->toArray(),
            $this->productRepository->findAllergens($product->id)
        );

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
            $allowedSeasons = ['spring', 'summer', 'autumn', 'winter'];
            $season = trim((string)$filters['season']);

            if (in_array($season, $allowedSeasons, true)) {
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

        if ($product === null) {
            throw new RuntimeException('Produsul nu există.');
        }

        $isFavorite = $this->productRepository->isFavorite($userId, $productId);

        if ($isFavorite) {
            $this->productRepository->removeFavorite($userId, $productId);
            $isFavorite = false;
        } else {
            $this->productRepository->addFavorite($userId, $productId);
            $isFavorite = true;
        }

        return [
            'is_favorite' => $isFavorite,
            'favorites_count' => $this->productRepository->countFavorites($productId),
        ];
    }

    public function isFavorite(int $userId, int $productId): bool
    {
        if ($userId <= 0 || $productId <= 0) {
            return false;
        }

        return $this->productRepository->isFavorite($userId, $productId);
    }
}