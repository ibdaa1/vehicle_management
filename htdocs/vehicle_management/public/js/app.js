/**
 * Vehicle Management System — Shared Application Module
 * RTL-first, Arabic-primary frontend framework
 *
 * ARCHITECTURE RULES (one source of truth):
 *   • Auth.check()          → here (DOMContentLoaded)
 *   • ThemeManager          → here
 *   • i18n                  → here
 *   • Permission gate       → here  (NOT in footer.php)
 *   • Sidebar toggle events → here, _bindGlobalEvents()  (NOT in header.php)
 *   • Language toggle event → here, _bindGlobalEvents()  (NOT in footer.php)
 *   • Sidebar collapsed state restore → header.php inline <script> (zero-flash)
 */

/* ============================================
   Theme Manager
   ============================================ */
const ThemeManager = {
    currentTheme: null,

    async load() {
        try {
            const saved = localStorage.getItem('theme_slug');
            const res   = await API.get('/settings/theme' + (saved ? '?slug=' + encodeURIComponent(saved) : ''));
            if (res && res.data) {
                this.applyTheme(res.data);
                this.currentTheme = res.data;
            }
        } catch {
            // CSS custom property defaults are used when API is unavailable
        }
        this._applyMode(localStorage.getItem('theme_mode') || 'light');
    },

    applyTheme(theme) {
        const root = document.documentElement;
        const map  = {
            primary_dark:   '--primary-dark',
            primary_main:   '--primary-main',
            primary_light:  '--primary-light',
            accent_gold:    '--accent-gold',
            accent_beige:   '--accent-beige',
            bg_main:        '--bg-main',
            bg_card:        '--bg-card',
            bg_sidebar:     '--bg-sidebar',
            text_primary:   '--text-primary',
            text_secondary: '--text-secondary',
        };
        const colors = theme.colors || theme;
        Object.keys(map).forEach(key => {
            if (colors[key]) root.style.setProperty(map[key], colors[key]);
        });
        if (theme.fonts) {
            if (theme.fonts.ar) root.style.setProperty('--font-ar', theme.fonts.ar);
            if (theme.fonts.en) root.style.setProperty('--font-en', theme.fonts.en);
        }
        if (theme.slug) localStorage.setItem('theme_slug', theme.slug);
    },

    toggle() {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        this._applyMode(next);
    },

    _applyMode(mode) {
        document.documentElement.setAttribute('data-theme', mode);
        localStorage.setItem('theme_mode', mode);
    },

    getMode() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }
};

/* ============================================
   Auth Manager
   ============================================ */
const Auth = {
    getToken()        { return localStorage.getItem('auth_token'); },
    setToken(token)   { localStorage.setItem('auth_token', token); },
    clear()           { localStorage.removeItem('auth_token'); localStorage.removeItem('auth_user'); },

    getUser() {
        try   { return JSON.parse(localStorage.getItem('auth_user')); }
        catch { return null; }
    },

    setUser(user) { localStorage.setItem('auth_user', JSON.stringify(user)); },

    async check() {
        if (!this.getToken()) return null;
        try {
            const res  = await API.get('/auth/check');
            const user = (res && res.data) || (res && res.user) || null;
            if (user) { this.setUser(user); return user; }
            return null;
        } catch {
            this.clear();
            return null;
        }
    },

    headers() {
        const h = { 'Content-Type': 'application/json' };
        const t = this.getToken();
        if (t) h['Authorization'] = 'Bearer ' + t;
        return h;
    },

    logout() {
        this.clear();
        window.location.href = API.baseUrl + '/public/login.html';
    }
};

/* ============================================
   API Helper
   ============================================ */
const API = {
    baseUrl: '',

    _fullUrl(path) { return this.baseUrl + '/api/v1' + path; },

    async _request(method, path, data) {
        const opts = { method, headers: Auth.headers() };
        if (data !== undefined) opts.body = JSON.stringify(data);

        const res = await fetch(this._fullUrl(path), opts);

        if (res.status === 401) {
            Auth.clear();
            window.location.href = API.baseUrl + '/public/login.html?return_to=' + encodeURIComponent(window.location.href);
            throw new Error('Unauthorized');
        }

        const json = await res.json();
        if (!res.ok) throw new Error((json && json.message) || res.statusText);
        return json;
    },

    get(path)        { return this._request('GET',    path);       },
    post(path, data) { return this._request('POST',   path, data); },
    put(path, data)  { return this._request('PUT',    path, data); },
    del(path)        { return this._request('DELETE', path);       },
};

/* ============================================
   UI Helpers
   ============================================ */
const UI = {
    _toastContainer: null,

    _ensureToastContainer() {
        if (!this._toastContainer) {
            this._toastContainer = document.createElement('div');
            this._toastContainer.className = 'toast-container';
            document.body.appendChild(this._toastContainer);
        }
        return this._toastContainer;
    },

    showToast(message, type = 'info', duration = 4000) {
        const container = this._ensureToastContainer();
        const toast     = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('removing');
            toast.addEventListener('animationend', () => toast.remove());
        }, duration);
    },

    showSpinner(container) {
        if (!container) return;
        container.style.position = 'relative';
        const overlay = document.createElement('div');
        overlay.className = 'spinner-overlay';
        overlay.innerHTML = '<div class="spinner"></div>';
        overlay.setAttribute('data-spinner', 'true');
        container.appendChild(overlay);
    },

    hideSpinner(container) {
        if (!container) return;
        const overlay = container.querySelector('[data-spinner]');
        if (overlay) overlay.remove();
    },

    showModal(id) {
        const modal = document.getElementById(id);
        if (modal) { modal.classList.add('show'); document.body.style.overflow = 'hidden'; }
    },

    hideModal(id) {
        const modal = document.getElementById(id);
        if (modal) { modal.classList.remove('show'); document.body.style.overflow = ''; }
    },

    confirm(message) {
        return new Promise(resolve => {
            const modal = document.createElement('div');
            modal.className = 'modal show';
            modal.innerHTML = `
                <div class="modal-content" style="max-width:400px">
                    <div class="modal-body text-center" style="padding:32px 24px">
                        <p style="margin-bottom:24px;font-size:1rem">${this._escapeHtml(message)}</p>
                        <div class="d-flex align-center gap-1" style="justify-content:center">
                            <button class="btn btn-danger" data-action="yes">${i18n.t('delete')}</button>
                            <button class="btn btn-ghost"  data-action="no">${i18n.t('cancel')}</button>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
            modal.addEventListener('click', e => {
                const action = e.target.getAttribute('data-action');
                if (action === 'yes' || action === 'no' || e.target === modal) {
                    modal.remove();
                    document.body.style.overflow = '';
                    resolve(action === 'yes');
                }
            });
        });
    },

    formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString(i18n.lang === 'ar' ? 'ar-SA' : 'en-US', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
    },

    formatNumber(num) {
        if (num == null) return '—';
        return Number(num).toLocaleString(i18n.lang === 'ar' ? 'ar-SA' : 'en-US');
    },

    _escapeHtml(str) {
        const el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }
};

/* ============================================
   i18n — Language Support
   ============================================ */
const i18n = {
    lang:    localStorage.getItem('lang') || 'ar',
    strings: {},

    async load(userLang) {
        if (userLang === 'ar' || userLang === 'en') {
            this.lang = userLang;
            localStorage.setItem('lang', userLang);
        }
        try {
            let langUrl = '';
            if (API.baseUrl) {
                langUrl = API.baseUrl + '/public/languages/' + this.lang + '.json';
            } else {
                const link   = document.querySelector('link[rel="stylesheet"][href*="css/theme.css"]');
                const script = document.querySelector('script[src*="js/app.js"]');
                const base   = link   ? link.getAttribute('href').replace(/css\/theme\.css.*$/, '')
                             : script ? script.getAttribute('src').replace(/js\/app\.js.*$/, '')
                             : './';
                langUrl = base + 'languages/' + this.lang + '.json';
            }
            const res = await fetch(langUrl);
            if (res.ok) this.strings = await res.json();
        } catch { /* strings remain empty; keys returned as-is */ }
        this._applyDirection();
    },

    t(key) { return this.strings[key] || key; },

    toggle() {
        this.lang = this.lang === 'ar' ? 'en' : 'ar';
        localStorage.setItem('lang', this.lang);
        window.location.reload();
    },

    _applyDirection() {
        const dir = this.lang === 'ar' ? 'rtl' : 'ltr';
        document.body.setAttribute('dir',  dir);
        document.documentElement.setAttribute('lang', this.lang);
        document.documentElement.setAttribute('dir',  dir);
    }
};

/* ============================================
   Menu / Sidebar (dynamic — non-PHP pages only)
   ============================================ */
const Menu = {
    items: [
        { key: 'dashboard',      icon: '📊', page: 'dashboard.php?page=dashboard',      permission: null },
        { key: 'my_vehicles',    icon: '🚙', page: 'dashboard.php?page=my_vehicles',    permission: null },
        { key: 'admin_vehicles', icon: '🚐', page: 'dashboard.php?page=admin_vehicles', permission: 'manage_movements' },
        { key: 'vehicles',       icon: '🚗', page: 'dashboard.php?page=vehicle_list',   permission: 'manage_vehicles' },
        { key: 'movements',      icon: '🔄', page: 'dashboard.php?page=movements',      permission: 'manage_movements' },
        { key: 'maintenance',    icon: '🔧', page: 'dashboard.php?page=maintenance',    permission: 'manage_maintenance' },
        { key: 'violations',     icon: '⚠️', page: 'dashboard.php?page=violations',     permission: 'manage_violations' },
        { divider: true },
        { key: 'users',          icon: '👥', page: 'dashboard.php?page=users',          permission: 'manage_users' },
        { key: 'roles',          icon: '🔑', page: 'dashboard.php?page=roles',          permission: 'manage_roles' },
        { key: 'settings',       icon: '⚙️', page: 'dashboard.php?page=settings',       permission: 'manage_settings' },
        { divider: true },
        { key: 'profile',        icon: '👤', page: 'dashboard.php?page=profile',        permission: null },
    ],

    render(container, userPermissions) {
        if (!container) return;
        container.innerHTML = '';
        const perms = Array.isArray(userPermissions) ? userPermissions : [];
        this.items.forEach(item => {
            if (item.divider) {
                const div = document.createElement('div');
                div.className = 'menu-divider';
                container.appendChild(div);
                return;
            }
            if (item.permission && !perms.includes(item.permission) && !perms.includes('*')) return;
            const a = document.createElement('a');
            a.className = 'menu-item';
            a.href      = item.page;
            a.setAttribute('data-tooltip', i18n.t(item.key));
            a.innerHTML = '<span class="menu-icon">' + item.icon + '</span>'
                        + '<span class="menu-label">' + i18n.t(item.key) + '</span>';
            container.appendChild(a);
        });
    },

    setActive(page) {
        document.querySelectorAll('.app-sidebar .menu-item').forEach(el => {
            const href = el.getAttribute('href');
            el.classList.toggle('active', !!(href && page.includes(href)));
        });
    }
};

/* ============================================
   Sidebar (reference only — events in _bindGlobalEvents)
   ============================================ */
const Sidebar = {
    _el: null,
    init()         { this._el = document.getElementById('appSidebar') || document.querySelector('.app-sidebar'); },
    toggle()       { if (!this._el) return; const c = this._el.classList.toggle('collapsed'); localStorage.setItem('sidebar_collapsed', c ? '1' : '0'); if (typeof syncSidebarBodyClass === 'function') syncSidebarBodyClass(); },
    toggleMobile() {
        if (!this._el) return;
        const overlay = document.getElementById('sidebarOverlay');
        const open    = this._el.classList.toggle('mobile-open');
        if (overlay) overlay.classList.toggle('active', open);
    },
    // restore() is a no-op — header.php inline script handles it synchronously (zero-flash)
    restore() {}
};

/* ============================================
   Auto-Initialise on DOMContentLoaded
   ============================================ */
document.addEventListener('DOMContentLoaded', async () => {

    // 1. Detect base URL
    const pathParts = window.location.pathname.split('/public');
    API.baseUrl     = API.baseUrl || (window.location.origin + (pathParts[0] || ''));

    // 2. Language strings (needed before UI is shown)
    await i18n.load();

    // 3. Theme
    await ThemeManager.load();

    // 4. Public pages — skip auth
    const currentPage  = window.location.pathname.split('/').pop() || 'index.html';
    const publicPages  = ['login.html', 'activation_success.html'];
    const isPhpDash    = currentPage.includes('dashboard.php');

    if (publicPages.includes(currentPage)) {
        Sidebar.init();
        _bindGlobalEvents();
        return;
    }

    // 5. Auth check
    const user = await Auth.check();
    if (!user) {
        window.location.href = API.baseUrl + '/public/login.html?return_to=' + encodeURIComponent(window.location.href);
        return;
    }

    // 6. Reload i18n with user's preferred language if different
    if (user.preferred_language && user.preferred_language !== i18n.lang) {
        await i18n.load(user.preferred_language);
    }

    // 7. Render user avatar in header
    const userInfoEl = document.querySelector('.app-header .user-info');
    if (userInfoEl) {
        const initials = (user.name || user.username || '?').substring(0, 2);
        userInfoEl.innerHTML =
            '<span class="avatar">' + initials + '</span>' +
            '<span>' + (user.name || user.username) + '</span>';
    }

    // 8. Menu — PHP dashboard is server-rendered, only hide items without permission
    const perms = user.permissions || [];
    if (!isPhpDash) {
        const menuContainer = document.querySelector('.app-sidebar .menu-list');
        if (menuContainer) {
            Menu.render(menuContainer, perms);
            Menu.setActive(currentPage);
        }
    } else {
        document.querySelectorAll('.app-sidebar .menu-item[data-perm]').forEach(el => {
            const perm = el.getAttribute('data-perm');
            if (perm && !perms.includes(perm) && !perms.includes('*')) {
                el.style.display = 'none';
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // 9. PERMISSION GATE — single source of truth
    //    footer.php no longer has a duplicate of this logic.
    //    dashboard.php hides #pageContent when a perm is required.
    //    Here we check the user has it and reveal/deny accordingly.
    // ─────────────────────────────────────────────────────────────────
    const pageContentEl  = document.getElementById('pageContent');
    const accessDeniedEl = document.getElementById('accessDenied');

    if (pageContentEl) {
        const requiredPerm = pageContentEl.getAttribute('data-required-perm') || '';
        const hasAccess    = !requiredPerm
                           || perms.includes(requiredPerm)
                           || perms.includes('*');

        if (hasAccess) {
            // ✅ User has permission — show the page
            pageContentEl.style.display  = '';
            if (accessDeniedEl) accessDeniedEl.style.display = 'none';
        } else {
            // 🚫 User lacks permission — show access denied
            pageContentEl.style.display  = 'none';
            if (accessDeniedEl) {
                accessDeniedEl.style.display = '';
                // Translate access denied labels
                const t    = k => i18n.t(k);
                const isEn = i18n.lang === 'en';
                const safe = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
                safe('accessDeniedTitle', t('access_denied'));
                safe('accessDeniedMsg',   t('no_permission'));
                safe('accessDeniedBack',  t('back_to_dashboard'));
                // Translate any data-label-* attributes inside the block
                accessDeniedEl.querySelectorAll('[data-label-ar]').forEach(el => {
                    el.textContent = el.getAttribute(isEn ? 'data-label-en' : 'data-label-ar') || el.textContent;
                });
            }
            window.__pageDenied = true;
        }
    }

    // 10. Sidebar init + global events
    Sidebar.init();
    _bindGlobalEvents();
});

/* ============================================
   Global Event Bindings
   Single place where ALL button events are wired.
   ============================================ */
function _bindGlobalEvents() {

    // Theme toggle
    document.querySelectorAll('[data-action="toggle-theme"]').forEach(btn =>
        btn.addEventListener('click', () => ThemeManager.toggle())
    );

    // Language toggle — single binding, no footer.php duplicate
    document.querySelectorAll('[data-action="toggle-lang"]').forEach(btn =>
        btn.addEventListener('click', () => i18n.toggle())
    );

    // Logout
    document.querySelectorAll('[data-action="logout"]').forEach(btn =>
        btn.addEventListener('click', e => { e.preventDefault(); Auth.logout(); })
    );

    // Modal close
    document.querySelectorAll('.modal-close, [data-action="close-modal"]').forEach(btn =>
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) { modal.classList.remove('show'); document.body.style.overflow = ''; }
        })
    );

    // Modal backdrop click
    document.querySelectorAll('.modal').forEach(modal =>
        modal.addEventListener('click', e => {
            if (e.target === modal) { modal.classList.remove('show'); document.body.style.overflow = ''; }
        })
    );

    // ── Sidebar ──────────────────────────────────────────────────────
    const sidebar  = document.getElementById('appSidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const isMobile = () => window.innerWidth <= 768;

    function closeMobile() {
        if (sidebar) sidebar.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('active');
    }

    // Desktop collapse (data-action="toggle-sidebar")
    document.querySelectorAll('[data-action="toggle-sidebar"]').forEach(btn =>
        btn.addEventListener('click', () => {
            if (!sidebar) return;
            const collapsed = sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
            if (typeof syncSidebarBodyClass === 'function') syncSidebarBodyClass();
        })
    );

    // Mobile open/close (data-action="toggle-sidebar-mobile")
    document.querySelectorAll('[data-action="toggle-sidebar-mobile"]').forEach(btn =>
        btn.addEventListener('click', () => {
            if (!sidebar) return;
            const open = sidebar.classList.toggle('mobile-open');
            if (overlay) overlay.classList.toggle('active', open);
        })
    );

    if (overlay) overlay.addEventListener('click', closeMobile);

    document.querySelectorAll('#sidebarMenu .menu-item').forEach(el =>
        el.addEventListener('click', () => { if (isMobile()) closeMobile(); })
    );

    window.addEventListener('resize', () => { if (!isMobile()) closeMobile(); });
}