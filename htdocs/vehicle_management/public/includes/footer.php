    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="app-footer">
        <span id="footerText" data-footer-ar="<?= htmlspecialchars($footerAr ?? '© 2025 بلدية مدينة الشارقة') ?>" data-footer-en="<?= htmlspecialchars($footerEn ?? '© 2025 Sharjah City Municipality') ?>"><?= htmlspecialchars($footerAr ?? '© 2025 بلدية مدينة الشارقة') ?></span>
    </footer>
    <script>
    // Apply stored language to footer text immediately
    (function(){
        try {
            var lang = localStorage.getItem('lang');
            if (lang === 'en') {
                var ft = document.getElementById('footerText');
                if (ft) ft.textContent = ft.getAttribute('data-footer-en') || ft.textContent;
            }
        } catch(e) {}
    })();
    </script>

    <!-- Cache-mismatch detection: fix for hosting that caches PHP output ignoring query params -->
    <script>
    (function(){
        var pc = document.getElementById('pageContent');
        if (!pc) return;
        var renderedPage = pc.getAttribute('data-page');
        if (!renderedPage) return;
        var params = new URLSearchParams(window.location.search);
        var requestedPage = params.get('page') || 'dashboard';
        if (renderedPage !== requestedPage) {
            // Server rendered a different page than what URL requested (CDN caching issue)
            // Retry up to 3 times with increasingly aggressive cache-busting
            var retries = parseInt(params.get('_retry') || '0', 10);
            if (retries < 3) {
                params.set('_cb', Date.now() + '' + Math.random());
                params.set('_retry', retries + 1);
                window.location.replace(window.location.pathname + '?' + params.toString());
                return;
            }
        }
        // Clean up cache-bust parameters from URL (cosmetic, using replaceState if available)
        if (renderedPage === requestedPage && (params.has('_cb') || params.has('_retry') || params.has('_v'))) {
            params.delete('_cb');
            params.delete('_retry');
            params.delete('_v');
            var cleanUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', cleanUrl);
            }
        }
    })();
    </script>

    <!-- Shared JS -->
    <script src="<?= $publicUrl ?>/js/app.js"></script>
    <script>
    /* Override base URL for API calls — detect from server-rendered value */
    (function(){
        API.baseUrl = <?= json_encode($appBaseUrl, JSON_UNESCAPED_UNICODE) ?>;

        /* ---- Language toggle for sidebar, header, footer, and all page content ---- */
        function applyLang(lang) {
            var isEn = (lang === 'en');
            var dir = isEn ? 'ltr' : 'rtl';
            document.documentElement.setAttribute('lang', lang);
            document.documentElement.setAttribute('dir', dir);
            document.body.setAttribute('dir', dir);

            // All elements with data-label-ar/en (sidebar, header, footer, fragment content)
            document.querySelectorAll('[data-label-ar]').forEach(function(el) {
                el.textContent = el.getAttribute(isEn ? 'data-label-en' : 'data-label-ar') || el.textContent;
            });
            // All elements with data-title-ar/en
            document.querySelectorAll('[data-title-ar]').forEach(function(el) {
                el.textContent = el.getAttribute(isEn ? 'data-title-en' : 'data-title-ar') || el.textContent;
            });
            // Footer text
            var ft = document.getElementById('footerText');
            if (ft && ft.hasAttribute('data-footer-ar')) {
                ft.textContent = ft.getAttribute(isEn ? 'data-footer-en' : 'data-footer-ar') || ft.textContent;
            }
            // Lang button
            var lb = document.getElementById('langBtn');
            if (lb) lb.textContent = isEn ? 'AR' : 'EN';
            // All elements with data-placeholder-ar/en
            document.querySelectorAll('[data-placeholder-ar]').forEach(function(el) {
                el.placeholder = el.getAttribute(isEn ? 'data-placeholder-en' : 'data-placeholder-ar') || el.placeholder;
            });
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
    <script>
    /* ---- Page-level permission gate ---- */
    (function(){
        var pageContent = document.getElementById('pageContent');
        var accessDenied = document.getElementById('accessDenied');
        if (!pageContent || !accessDenied) return;

        var requiredPerm = pageContent.getAttribute('data-required-perm');
        if (!requiredPerm) { pageContent.style.display = ''; return; } // No permission required — show immediately

        // Check permission after Auth has loaded (Auth.check is called by app.js on DOMContentLoaded)
        function checkPagePermission() {
            var user = Auth.getUser();
            if (!user) {
                // Auth not loaded yet, retry
                setTimeout(checkPagePermission, 100);
                return;
            }
            var perms = user.permissions || [];
            if (perms.includes(requiredPerm) || perms.includes('*')) {
                // User HAS permission — show page content
                pageContent.style.display = '';
            } else {
                // User lacks permission — keep page content hidden, show access denied
                pageContent.style.display = 'none';
                accessDenied.style.display = 'block';
                // Apply language to access denied message
                var lang = localStorage.getItem('lang') || 'ar';
                var isEn = (lang === 'en');
                accessDenied.querySelectorAll('[data-label-ar]').forEach(function(el) {
                    el.textContent = el.getAttribute(isEn ? 'data-label-en' : 'data-label-ar') || el.textContent;
                });
                // Set a global flag so fragment scripts can skip initialization
                window.__pageDenied = true;
            }
        }
        // Start checking after a small delay to allow Auth.check() to complete
        setTimeout(checkPagePermission, 200);
    })();
    </script>
    <?php if (!empty($pageScripts)): ?>
    <!-- Page-specific scripts -->
    <?= $pageScripts ?>
    <?php endif; ?>
    <script>
    /* Register Service Worker for PWA support */
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= $publicUrl ?>/sw.js').catch(function(){});
    }
    </script>
</body>
</html>
