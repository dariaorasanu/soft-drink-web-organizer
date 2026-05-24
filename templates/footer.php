<?php foreach ($extraJs ?? [] as $js): ?>
    <script src="<?= htmlspecialchars($js) ?>"></script>
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
