    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="app-footer">
        <span id="footerText"><?= htmlspecialchars($footerAr ?? '© 2025 بلدية مدينة الشارقة') ?></span>
    </footer>

    <!-- Shared JS -->
    <script src="<?= $publicUrl ?>/js/app.js"></script>
    <script>
    /* Override base URL for API calls — detect from server-rendered value */
    (function(){
        API.baseUrl = <?= json_encode($appBaseUrl, JSON_UNESCAPED_UNICODE) ?>;
    })();
    </script>
    <?php if (!empty($pageScripts)): ?>
    <!-- Page-specific scripts -->
    <?= $pageScripts ?>
    <?php endif; ?>
</body>
</html>
