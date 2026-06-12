<?php foreach ($extraJs ?? [] as $js): ?>
    <?php
    $jsPath = parse_url($js, PHP_URL_PATH);
    $jsFile = $jsPath ? dirname(__DIR__) . $jsPath : null;
    $jsVersion = $jsFile && file_exists($jsFile) ? '?v=' . filemtime($jsFile) : '';
    ?>
    <script src="<?= htmlspecialchars($js . $jsVersion) ?>"></script>
<?php endforeach; ?>

<footer class="site-footer">
    <div class="footer-inner">
        <span class="footer-brand">S<span>O</span>r</span>
        <span class="footer-copy">&copy; <?= date('Y') ?> Soft Drink Organizer</span>
        <a href="/api/rss.php" class="footer-rss" title="Feed RSS top produse">📡 RSS</a>
    </div>
</footer>

</body>
</html>
