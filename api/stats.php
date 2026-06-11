<?php

session_start();

error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../service/StatisticsService.php';

/** @var PDO $pdo */

$statisticsService = new StatisticsService($pdo);
$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'top_products' => sendJson([
            'success' => true,
            'data' => $statisticsService->getTopProducts(getLimit()),
        ]),

        'most_favorited' => sendJson([
            'success' => true,
            'data' => $statisticsService->getMostFavorited(getLimit()),
        ]),

        'category_distribution' => sendJson([
            'success' => true,
            'data' => $statisticsService->getCategoryDistribution(),
        ]),

        'avg_rating' => sendJson([
            'success' => true,
            'data' => $statisticsService->getAverageRatingPerCategory(),
        ]),

        'products_over_time' => sendJson([
            'success' => true,
            'data' => $statisticsService->getProductsAddedOverTime(),
        ]),

        'export_csv' => sendDownload(
            $statisticsService->exportProductsCsv(),
            'text/csv; charset=utf-8',
            'produse.csv'
        ),

        'export_json' => sendDownload(
            $statisticsService->exportProductsJson(),
            'application/json; charset=utf-8',
            'produse.json'
        ),

        'export_svg' => sendSvg($statisticsService),

        default => sendJson([
            'success' => false,
            'message' => 'Acțiune inexistentă pentru statistici.',
        ], 404),
    };
} catch (Throwable $e) {
    sendJson([
        'success' => false,
        'message' => $e->getMessage(),
    ], 500);
}

function getLimit(): int
{
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    return max(1, min($limit, 50));
}

function sendSvg(StatisticsService $statisticsService): void
{
    $metric = $_GET['metric'] ?? 'top_products';

    [$data, $title] = match ($metric) {
        'most_favorited' => [
            $statisticsService->getMostFavorited(getLimit()),
            'Cele mai favorite produse',
        ],
        'category_distribution' => [
            $statisticsService->getCategoryDistribution(),
            'Distribuția produselor pe categorii',
        ],
        'avg_rating' => [
            $statisticsService->getAverageRatingPerCategory(),
            'Rating mediu pe categorie',
        ],
        'products_over_time' => [
            $statisticsService->getProductsAddedOverTime(),
            'Produse adăugate pe luni',
        ],
        default => [
            $statisticsService->getTopProducts(getLimit()),
            'Top produse după vizualizări',
        ],
    };

    header('Content-Type: image/svg+xml; charset=utf-8');
    echo $statisticsService->generateBarChartSvg($data, $title);
    exit;
}

function sendDownload(string $content, string $contentType, string $filename): void
{
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}

function sendJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}