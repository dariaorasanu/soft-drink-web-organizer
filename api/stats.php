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

function sendJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}