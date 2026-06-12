<?php

error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';

/** @var PDO $pdo */

try {
    $stmt = $pdo->query("
        SELECT
            p.id,
            p.name,
            p.slug,
            COALESCE(p.brand, '') AS brand,
            p.price,
            p.view_count,
            p.created_at,
            COALESCE(STRING_AGG(DISTINCT c.name, ', '), '') AS categories
        FROM products p
        LEFT JOIN product_categories pc ON pc.product_id = p.id
        LEFT JOIN categories c ON c.id = pc.category_id
        GROUP BY p.id
        ORDER BY p.view_count DESC, p.name ASC
        LIMIT 10
    ");

    $products = $stmt->fetchAll();

    header('Content-Type: application/rss+xml; charset=utf-8');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    echo '<rss version="2.0">' . PHP_EOL;
    echo '<channel>' . PHP_EOL;
    echo '<title>SOr — Cele mai populare produse</title>' . PHP_EOL;
    echo '<link>' . escapeXml(getBaseUrl()) . '</link>' . PHP_EOL;
    echo '<description>Top 10 produse non-alcoolice din Soft Drink Web Organizer.</description>' . PHP_EOL;
    echo '<language>ro-RO</language>' . PHP_EOL;
    echo '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>' . PHP_EOL;

    foreach ($products as $product) {
        $productUrl = getBaseUrl() . '/pages/product.php?slug=' . urlencode($product['slug']);
        $price = $product['price'] !== null ? number_format((float)$product['price'], 2) . ' lei' : 'preț indisponibil';

        $description = trim(
            'Brand: ' . ($product['brand'] ?: 'necunoscut') .
            ' | Preț: ' . $price .
            ' | Categorii: ' . ($product['categories'] ?: 'fără categorie') .
            ' | Vizualizări: ' . (int)$product['view_count']
        );

        echo '<item>' . PHP_EOL;
        echo '<title>' . escapeXml($product['name']) . '</title>' . PHP_EOL;
        echo '<link>' . escapeXml($productUrl) . '</link>' . PHP_EOL;
        echo '<guid isPermaLink="true">' . escapeXml($productUrl) . '</guid>' . PHP_EOL;
        echo '<description>' . escapeXml($description) . '</description>' . PHP_EOL;
        echo '<pubDate>' . date(DATE_RSS, strtotime($product['created_at'])) . '</pubDate>' . PHP_EOL;
        echo '</item>' . PHP_EOL;
    }

    echo '</channel>' . PHP_EOL;
    echo '</rss>' . PHP_EOL;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/xml; charset=utf-8');

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<error>' . escapeXml($e->getMessage()) . '</error>';
}

function getBaseUrl(): string
{
    return rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');
}

function escapeXml(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
}