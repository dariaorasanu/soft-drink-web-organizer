<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'SOr — Soft Drink Organizer') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/home.css">
    <?php foreach ($extraCss ?? [] as $css): ?>
        <?php
        $cssPath = parse_url($css, PHP_URL_PATH);
        $cssFile = $cssPath ? dirname(__DIR__) . $cssPath : null;
        $cssVersion = $cssFile && file_exists($cssFile) ? '?v=' . filemtime($cssFile) : '';
        ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css . $cssVersion) ?>">
    <?php endforeach; ?>
    <link rel="alternate" type="application/rss+xml" title="SOr — Top produse" href="/api/rss.php">
</head>
<body>
