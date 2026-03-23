</main>

    <!-- ========== FOOTER ========== -->
    <footer class="app-footer" id="appFooter">
        <span id="footerText"
              data-footer-ar="<?= htmlspecialchars($footerAr ?? '© 2025 بلدية مدينة الشارقة') ?>"
              data-footer-en="<?= htmlspecialchars($footerEn ?? '© 2025 Sharjah City Municipality') ?>">
            <?= htmlspecialchars($footerAr ?? '© 2025 بلدية مدينة الشارقة') ?>
        </span>
    </footer>

    <style>
    /* ══════════════════════════════════════════════════════════════
       LAYOUT SYNC — header + main + footer all move together.
       The sidebar toggle adds/removes .sidebar-collapsed on <body>,
       so a single CSS rule covers all three elements at once.
       ══════════════════════════════════════════════════════════════ */

    /* ── RTL default (sidebar on the right) ── */
    .app-header,
    .app-main,
    .app-footer {
        transition: margin 0.28s cubic-bezier(.4,0,.2,1);
    }

    /* Expanded sidebar */
    .app-main,
    .app-footer {
        margin-right: var(--sidebar-width, 260px);
        margin-left:  0;
    }
    .app-header {
        /* Header always full-width — no margin needed */
    }

    /* Collapsed sidebar — driven by body class */
    body.sidebar-collapsed .app-main,
    body.sidebar-collapsed .app-footer {
        margin-right: var(--sidebar-collapsed-width, 60px);
    }

    /* ── LTR (sidebar on the left) ── */
    body[dir="ltr"] .app-main,
    body[dir="ltr"] .app-footer {
        margin-right: 0;
        margin-left:  var(--sidebar-width, 260px);
    }
    body[dir="ltr"].sidebar-collapsed .app-main,
    body[dir="ltr"].sidebar-collapsed .app-footer {
        margin-right: 0;
        margin-left:  var(--sidebar-collapsed-width, 60px);
    }

    /* ── Mobile ≤768px — icons-only sidebar ── */
    @media (max-width: 768px) {
        .app-main,
        .app-footer {
            margin-right: var(--sidebar-collapsed-width, 60px) !important;
            margin-left:  0 !important;
        }
        body[dir="ltr"] .app-main,
        body[dir="ltr"] .app-footer {
            margin-right: 0 !important;
            margin-left:  var(--sidebar-collapsed-width, 60px) !important;
        }
    }

    /* ── Mobile XS ≤480px — sidebar hidden ── */
    @media (max-width: 480px) {
        .app-main,
        .app-footer {
            margin-right: 0 !important;
            margin-left:  0 !important;
        }
    }
    </style>

    <script>
    /* ── Sync body.sidebar-collapsed with the sidebar state ──────────────
       Runs immediately so there is no flash between page paint and JS.
       The toggle handler in app.js also calls syncSidebarBodyClass()
       after every toggle so the footer stays in sync at runtime.
    ─────────────────────────────────────────────────────────────────── */
    function syncSidebarBodyClass() {
        var sidebar = document.getElementById('appSidebar');
        if (!sidebar) return;
        if (sidebar.classList.contains('collapsed')) {
            document.body.classList.add('sidebar-collapsed');
        } else {
            document.body.classList.remove('sidebar-collapsed');
        }
    }
    // Run once immediately (sidebar class was already restored by header.php inline script)
    syncSidebarBodyClass();
    </script>

    <script>
    /* Apply stored language to footer text immediately — before app.js loads */
    (function(){
        try {
            if (localStorage.getItem('lang') === 'en') {
                var ft = document.getElementById('footerText');
                if (ft) ft.textContent = ft.getAttribute('data-footer-en') || ft.textContent;
            }
        } catch(e){}
    })();
    </script>

    <!-- Cache-mismatch detection -->
    <script>
    (function(){
        var pc = document.getElementById('pageContent');
        if (!pc) return;
        var renderedPage  = pc.getAttribute('data-page');
        if (!renderedPage) return;
        var params        = new URLSearchParams(window.location.search);
        var requestedPage = params.get('page') || 'dashboard';
        if (renderedPage !== requestedPage) {
            var retries = parseInt(params.get('_retry') || '0', 10);
            if (retries < 3) {
                params.set('_cb',    Date.now() + '' + Math.random());
                params.set('_retry', retries + 1);
                window.location.replace(window.location.pathname + '?' + params.toString());
                return;
            }
        }
        if (renderedPage === requestedPage && (params.has('_cb') || params.has('_retry') || params.has('_v'))) {
            params.delete('_cb'); params.delete('_retry'); params.delete('_v');
            var clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            if (window.history && window.history.replaceState) window.history.replaceState(null, '', clean);
        }
    })();
    </script>

    <!-- Shared JS -->
    <script src="<?= $publicUrl ?>/js/app.js"></script>

    <script>
    (function(){ API.baseUrl = <?= json_encode($appBaseUrl ?? '', JSON_UNESCAPED_UNICODE) ?>; })();
    </script>

    <?php if (!empty($pageScripts)): ?>
    <?= $pageScripts ?>
    <?php endif; ?>

    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= $publicUrl ?>/sw.js').catch(function(){});
    }
    </script>

</body>
</html>