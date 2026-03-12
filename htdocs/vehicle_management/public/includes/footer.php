    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="app-footer">
        <span id="footerText" data-footer-ar="<?= htmlspecialchars($footerAr ?? '© 2025 بلدية مدينة الشارقة') ?>" data-footer-en="<?= htmlspecialchars($footerEn ?? '© 2025 Sharjah City Municipality') ?>"><?= htmlspecialchars($footerAr ?? '© 2025 بلدية مدينة الشارقة') ?></span>
    </footer>

    <!-- Shared JS -->
    <script src="<?= $publicUrl ?>/js/app.js"></script>
    <script>
    /* Override base URL for API calls — detect from server-rendered value */
    (function(){
        API.baseUrl = <?= json_encode($appBaseUrl, JSON_UNESCAPED_UNICODE) ?>;

        /* ---- Language toggle for sidebar, header, footer ---- */
        function applyLang(lang) {
            var isEn = (lang === 'en');
            var dir = isEn ? 'ltr' : 'rtl';
            document.documentElement.setAttribute('lang', lang);
            document.documentElement.setAttribute('dir', dir);
            document.body.setAttribute('dir', dir);

            // Sidebar menu labels
            document.querySelectorAll('.menu-label[data-label-ar]').forEach(function(el) {
                el.textContent = el.getAttribute(isEn ? 'data-label-en' : 'data-label-ar') || el.textContent;
            });
            // Header title
            var ht = document.getElementById('headerTitle');
            if (ht && ht.hasAttribute('data-title-ar')) {
                ht.textContent = ht.getAttribute(isEn ? 'data-title-en' : 'data-title-ar') || ht.textContent;
            }
            // Footer
            var ft = document.getElementById('footerText');
            if (ft && ft.hasAttribute('data-footer-ar')) {
                ft.textContent = ft.getAttribute(isEn ? 'data-footer-en' : 'data-footer-ar') || ft.textContent;
            }
            // Lang button
            var lb = document.getElementById('langBtn');
            if (lb) lb.textContent = isEn ? 'AR' : 'EN';
            // Logout button
            var lo = document.getElementById('logoutBtn');
            if (lo) lo.textContent = isEn ? 'Logout' : 'خروج';
        }

        // Apply stored language on load
        var savedLang = localStorage.getItem('lang') || 'ar';
        applyLang(savedLang);

        // Listen for language toggle
        var langBtn = document.getElementById('langBtn');
        if (langBtn) {
            langBtn.addEventListener('click', function() {
                var current = localStorage.getItem('lang') || 'ar';
                var next = (current === 'ar') ? 'en' : 'ar';
                localStorage.setItem('lang', next);
                applyLang(next);
            });
        }
    })();
    </script>
    <?php if (!empty($pageScripts)): ?>
    <!-- Page-specific scripts -->
    <?= $pageScripts ?>
    <?php endif; ?>
</body>
</html>
