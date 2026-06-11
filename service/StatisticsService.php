<?php

class StatisticsService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getTopProducts(int $limit = 10): array
    {
        $limit = $this->cleanLimit($limit);

        $stmt = $this->pdo->prepare("
            SELECT
                p.id,
                p.name,
                p.slug,
                p.brand,
                p.price,
                p.view_count
            FROM products p
            ORDER BY p.view_count DESC, p.name ASC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'mapProductRow'], $stmt->fetchAll());
    }

    public function getMostFavorited(int $limit = 10): array
    {
        $limit = $this->cleanLimit($limit);

        $stmt = $this->pdo->prepare("
            SELECT
                p.id,
                p.name,
                p.slug,
                p.brand,
                p.price,
                COUNT(uf.user_id) AS favorites_count
            FROM products p
            LEFT JOIN user_favorites uf ON uf.product_id = p.id
            GROUP BY p.id, p.name, p.slug, p.brand, p.price
            ORDER BY favorites_count DESC, p.name ASC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'name' => $this->escape($row['name']),
                'slug' => $this->escape($row['slug']),
                'brand' => $this->escape($row['brand'] ?? ''),
                'price' => $row['price'] !== null ? (float)$row['price'] : null,
                'favorites_count' => (int)$row['favorites_count'],
            ];
        }, $stmt->fetchAll());
    }

    public function getCategoryDistribution(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                c.id,
                c.name,
                c.slug,
                COUNT(pc.product_id) AS products_count
            FROM categories c
            LEFT JOIN product_categories pc ON pc.category_id = c.id
            GROUP BY c.id, c.name, c.slug
            ORDER BY products_count DESC, c.name ASC
        ");

        return array_map(function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'name' => $this->escape($row['name']),
                'slug' => $this->escape($row['slug']),
                'products_count' => (int)$row['products_count'],
            ];
        }, $stmt->fetchAll());
    }

    private function cleanLimit(int $limit): int
    {
        return max(1, min($limit, 50));
    }

    private function mapProductRow(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'name' => $this->escape($row['name']),
            'slug' => $this->escape($row['slug']),
            'brand' => $this->escape($row['brand'] ?? ''),
            'price' => $row['price'] !== null ? (float)$row['price'] : null,
            'view_count' => isset($row['view_count']) ? (int)$row['view_count'] : 0,
        ];
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}