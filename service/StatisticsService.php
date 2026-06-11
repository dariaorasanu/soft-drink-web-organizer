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

    public function getAverageRatingPerCategory(): array
    {
        $stmt = $this->pdo->query("
        SELECT
            c.id,
            c.name,
            c.slug,
            ROUND(AVG(pr.rating)::numeric, 2) AS average_rating,
            COUNT(pr.id) AS ratings_count
        FROM categories c
        LEFT JOIN product_categories pc ON pc.category_id = c.id
        LEFT JOIN product_ratings pr ON pr.product_id = pc.product_id
        GROUP BY c.id, c.name, c.slug
        HAVING COUNT(pr.id) > 0
        ORDER BY average_rating DESC, ratings_count DESC, c.name ASC
    ");

        return array_map(function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'name' => $this->escape($row['name']),
                'slug' => $this->escape($row['slug']),
                'average_rating' => $row['average_rating'] !== null ? (float)$row['average_rating'] : 0,
                'ratings_count' => (int)$row['ratings_count'],
            ];
        }, $stmt->fetchAll());
    }
    public function getProductsAddedOverTime(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') AS month,
                COUNT(*) AS products_count
            FROM products
            GROUP BY DATE_TRUNC('month', created_at)
            ORDER BY DATE_TRUNC('month', created_at) ASC
        ");

        return array_map(function (array $row): array {
            return [
                'month' => $this->escape($row['month']),
                'products_count' => (int)$row['products_count'],
            ];
        }, $stmt->fetchAll());
    }

    public function exportProductsCsv(): string
    {
        $products = $this->getProductsForExport();

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fputcsv($handle, [
            'id',
            'name',
            'slug',
            'brand',
            'price',
            'volume_ml',
            'calories_per_100ml',
            'sugar_per_100ml',
            'is_vegan',
            'is_gluten_free',
            'is_perishable',
            'shelf_life_days',
            'view_count',
            'categories',
            'created_at',
        ]);

        foreach ($products as $product) {
            fputcsv($handle, [
                $product['id'],
                $product['name'],
                $product['slug'],
                $product['brand'],
                $product['price'],
                $product['volume_ml'],
                $product['calories_per_100ml'],
                $product['sugar_per_100ml'],
                $product['is_vegan'] ? '1' : '0',
                $product['is_gluten_free'] ? '1' : '0',
                $product['is_perishable'] ? '1' : '0',
                $product['shelf_life_days'],
                $product['view_count'],
                $product['categories'],
                $product['created_at'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    public function exportProductsJson(): string
    {
        return json_encode(
            $this->getProductsForExport(),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ) ?: '[]';
    }

    public function generateBarChartSvg(array $data, string $title): string
    {
        $chartData = $this->normalizeChartData($data);

        $width = 760;
        $height = 360;
        $paddingTop = 60;
        $paddingRight = 30;
        $paddingBottom = 80;
        $paddingLeft = 55;
        $chartWidth = $width - $paddingLeft - $paddingRight;
        $chartHeight = $height - $paddingTop - $paddingBottom;
        $barGap = 14;
        $count = max(count($chartData), 1);
        $barWidth = max(24, ($chartWidth - ($barGap * ($count - 1))) / $count);
        $maxValue = max(array_column($chartData, 'value') ?: [1]);
        $maxValue = $maxValue > 0 ? $maxValue : 1;

        $svg = [];
        $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . $this->escapeXml($title) . '">';
        $svg[] = '<rect width="100%" height="100%" rx="24" fill="#171817"/>';
        $svg[] = '<text x="' . ($width / 2) . '" y="32" text-anchor="middle" fill="#f7f7f2" font-size="22" font-family="Arial, sans-serif" font-weight="700">' . $this->escapeXml($title) . '</text>';
        $svg[] = '<line x1="' . $paddingLeft . '" y1="' . ($height - $paddingBottom) . '" x2="' . ($width - $paddingRight) . '" y2="' . ($height - $paddingBottom) . '" stroke="#3a3d3a" stroke-width="2"/>';

        if (empty($chartData)) {
            $svg[] = '<text x="' . ($width / 2) . '" y="' . ($height / 2) . '" text-anchor="middle" fill="#b9c7bd" font-size="16" font-family="Arial, sans-serif">Nu există date pentru acest grafic.</text>';
            $svg[] = '</svg>';
            return implode('', $svg);
        }

        foreach ($chartData as $index => $item) {
            $value = (float)$item['value'];
            $label = (string)$item['label'];
            $barHeight = ($value / $maxValue) * $chartHeight;
            $x = $paddingLeft + ($index * ($barWidth + $barGap));
            $y = ($height - $paddingBottom) - $barHeight;

            $svg[] = '<rect x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($barWidth, 2) . '" height="' . round($barHeight, 2) . '" rx="10" fill="#8df0c0"/>';
            $svg[] = '<text x="' . round($x + $barWidth / 2, 2) . '" y="' . round($y - 8, 2) . '" text-anchor="middle" fill="#f72585" font-size="13" font-family="Arial, sans-serif" font-weight="700">' . $this->escapeXml((string)$value) . '</text>';
            $svg[] = '<text x="' . round($x + $barWidth / 2, 2) . '" y="' . ($height - 48) . '" text-anchor="middle" fill="#f7f7f2" font-size="12" font-family="Arial, sans-serif">' . $this->escapeXml($this->shortenLabel($label)) . '</text>';
        }

        $svg[] = '</svg>';
        return implode('', $svg);
    }

    private function getProductsForExport(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                p.id,
                p.name,
                p.slug,
                COALESCE(p.brand, '') AS brand,
                p.price,
                p.volume_ml,
                p.calories_per_100ml,
                p.sugar_per_100ml,
                p.is_vegan,
                p.is_gluten_free,
                p.is_perishable,
                p.shelf_life_days,
                p.view_count,
                COALESCE(STRING_AGG(DISTINCT c.name, ', '), '') AS categories,
                TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at
            FROM products p
            LEFT JOIN product_categories pc ON pc.product_id = p.id
            LEFT JOIN categories c ON c.id = pc.category_id
            GROUP BY p.id
            ORDER BY p.name ASC
        ");

        return array_map(function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'brand' => $row['brand'],
                'price' => $row['price'] !== null ? (float)$row['price'] : null,
                'volume_ml' => $row['volume_ml'] !== null ? (int)$row['volume_ml'] : null,
                'calories_per_100ml' => $row['calories_per_100ml'] !== null ? (float)$row['calories_per_100ml'] : null,
                'sugar_per_100ml' => $row['sugar_per_100ml'] !== null ? (float)$row['sugar_per_100ml'] : null,
                'is_vegan' => (bool)$row['is_vegan'],
                'is_gluten_free' => (bool)$row['is_gluten_free'],
                'is_perishable' => (bool)$row['is_perishable'],
                'shelf_life_days' => $row['shelf_life_days'] !== null ? (int)$row['shelf_life_days'] : null,
                'view_count' => (int)$row['view_count'],
                'categories' => $row['categories'],
                'created_at' => $row['created_at'],
            ];
        }, $stmt->fetchAll());
    }

    private function normalizeChartData(array $data): array
    {
        return array_map(function (array $row): array {
            $label = $row['name'] ?? $row['month'] ?? $row['label'] ?? 'N/A';
            $value = $row['view_count']
                ?? $row['favorites_count']
                ?? $row['products_count']
                ?? $row['average_rating']
                ?? $row['value']
                ?? 0;

            return [
                'label' => (string)$label,
                'value' => (float)$value,
            ];
        }, $data);
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

    private function shortenLabel(string $label): string
    {
        return mb_strlen($label) > 14 ? mb_substr($label, 0, 13) . '…' : $label;
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
    private function escapeXml(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}