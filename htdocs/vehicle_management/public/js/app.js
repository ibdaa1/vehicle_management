/**
 * Vehicle Management System — Shared Application Module
 * RTL-first, Arabic-primary frontend framework
 */

/* ============================================
   Theme Manager
   ============================================ */
const ThemeManager = {
    currentTheme: null,

    async load() {
        try {
            const saved = localStorage.getItem('theme_slug');
            const res = await API.get('/settings/theme' + (saved ? '?slug=' + encodeURIComponent(saved) : ''));
            if (res && res.data) {
                this.applyTheme(res.data);
                this.currentTheme = res.data;
            }
        } catch {
            // Defaults from CSS custom properties are used when API is unavailable
        }
        this._applyMode(localStorage.getItem('theme_mode') || 'light');
    },

    applyTheme(theme) {
        const root = document.documentElement;
        const map = {
            primary_dark:  '--primary-dark',
            primary_main:  '--primary-main',
            primary_light: '--primary-light',
            accent_gold:   '--accent-gold',
            accent_beige:  '--accent-beige',
            bg_main:       '--bg-main',
            bg_card:       '--bg-card',
            bg_sidebar:    '--bg-sidebar',
            text_primary:  '--text-primary',
            text_secondary:'--text-secondary',
        };

        const colors = theme.colors || theme;
        Object.keys(map).forEach(key => {
            if (colors[key]) {
                root.style.setProperty(map[key], colors[key]);
            }
        });

        if (theme.fonts) {
            if (theme.fonts.ar) root.style.setProperty('--font-ar', theme.fonts.ar);
            if (theme.fonts.en) root.style.setProperty('--font-en', theme.fonts.en);
        }

        if (theme.slug) {
            localStorage.setItem('theme_slug', theme.slug);
        }
    },

    toggle() {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
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
    getToken() {
        return localStorage.getItem('auth_token');
    },

    setToken(token) {
        localStorage.setItem('auth_token', token);
    },

    clear() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
    },

    getUser() {
        try {
            return JSON.parse(localStorage.getItem('auth_user'));
        } catch {
            return null;
        }
    },

    setUser(user) {
        localStorage.setItem('auth_user', JSON.stringify(user));
    },

    async check() {
        const token = this.getToken();
        if (!token) return null;
        try {
            const res = await API.get('/auth/check');
            const user = (res && res.data) || (res && res.user) || null;
            if (user) {
                this.setUser(user);
                return user;
            }
            return null;
        } catch {
            this.clear();
            return null;
        }
    },

    headers() {
        const token = this.getToken();
        const h = { 'Content-Type': 'application/json' };
        if (token) h['Authorization'] = 'Bearer ' + token;
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

    _fullUrl(path) {
        return this.baseUrl + '/api/v1' + path;
    },

    async _request(method, path, data) {
        const opts = {
            method,
            headers: Auth.headers(),
        };
        if (data !== undefined) {
            opts.body = JSON.stringify(data);
        }

        const res = await fetch(this._fullUrl(path), opts);

        if (res.status === 401) {
            Auth.logout();
            throw new Error('Unauthorized');
        }

        const json = await res.json();
        if (!res.ok) {
            const msg = (json && json.message) || res.statusText;
            throw new Error(msg);
        }
        return json;
    },

    get(path)       { return this._request('GET', path); },
    post(path, data){ return this._request('POST', path, data); },
    put(path, data) { return this._request('PUT', path, data); },
    del(path)       { return this._request('DELETE', path); },
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
        const toast = document.createElement('div');
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
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    },

    hideModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
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
                            <button class="btn btn-ghost" data-action="no">${i18n.t('cancel')}</button>
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
    lang: localStorage.getItem('lang') || 'ar',
    strings: {},

    async load(userLang) {
        // If a user language preference is provided, use it
        if (userLang && (userLang === 'ar' || userLang === 'en')) {
            this.lang = userLang;
            localStorage.setItem('lang', userLang);
        }
        try {
            // Use API.baseUrl (set in DOMContentLoaded or footer.php) for reliable path
            let langUrl = '';
            if (typeof API !== 'undefined' && API.baseUrl) {
                langUrl = API.baseUrl + '/public/languages/' + this.lang + '.json';
            } else {
                // Fallback: detect base path from stylesheet or script references
                const link = document.querySelector('link[rel="stylesheet"][href*="css/theme.css"]');
                let basePath = './';
                if (link) {
                    basePath = link.getAttribute('href').replace(/css\/theme\.css.*$/, '');
                } else {
                    const script = document.querySelector('script[src*="js/app.js"]');
                    if (script) basePath = script.getAttribute('src').replace(/js\/app\.js.*$/, '');
                }
                langUrl = basePath + 'languages/' + this.lang + '.json';
            }
            const res = await fetch(langUrl);
            if (res.ok) {
                this.strings = await res.json();
            }
        } catch {
            // Strings remain empty; keys are returned as-is
        }
        this._applyDirection();
    },

    t(key) {
        return this.strings[key] || key;
    },

    toggle() {
        this.lang = this.lang === 'ar' ? 'en' : 'ar';
        localStorage.setItem('lang', this.lang);
        window.location.reload();
    },

    _applyDirection() {
        const dir = this.lang === 'ar' ? 'rtl' : 'ltr';
        document.body.setAttribute('dir', dir);
        document.documentElement.setAttribute('lang', this.lang);
    }
};

/* ============================================
   Menu / Sidebar
   ============================================ */
const Menu = {
    items: [
        { key: 'dashboard',          icon: '📊', page: 'dashboard.html',          permission: null },
        { key: 'my_vehicles',        icon: '🚙', page: 'my_vehicles.html',        permission: null },
        { key: 'vehicles',           icon: '🚗', page: 'vehicle_management.html', permission: 'manage_vehicles' },
        { key: 'movements',          icon: '🔄', page: 'vehicle_movements.html',  permission: 'manage_movements' },
        { key: 'maintenance',        icon: '🔧', page: 'Vehicle_Maintenance.html', permission: 'manage_maintenance' },
        { key: 'violations',         icon: '⚠️', page: 'violations_list.html',    permission: 'manage_violations' },
        { divider: true },
        { key: 'users',              icon: '👥', page: 'admin_users.html',        permission: 'manage_users' },
        { key: 'roles',              icon: '🔑', page: 'admin_roles.html',        permission: 'manage_roles' },
        { key: 'settings',           icon: '⚙️', page: 'admin_activations.html',  permission: 'manage_settings' },
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

            if (item.permission && !perms.includes(item.permission) && !perms.includes('*')) {
                return;
            }

            const a = document.createElement('a');
            a.className = 'menu-item';
            a.href = item.page;
            a.innerHTML =
                '<span class="menu-icon">' + item.icon + '</span>' +
                '<span class="menu-label">' + i18n.t(item.key) + '</span>';
            container.appendChild(a);
        });
    },

    setActive(page) {
        document.querySelectorAll('.app-sidebar .menu-item').forEach(el => {
            const href = el.getAttribute('href');
            if (href && page.includes(href)) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });
    }
};

/* ============================================
   Sidebar Toggle
   ============================================ */
const Sidebar = {
    _el: null,

    init() {
        this._el = document.querySelector('.app-sidebar');
    },

    toggle() {
        if (!this._el) return;
        this._el.classList.toggle('collapsed');
        localStorage.setItem('sidebar_collapsed', this._el.classList.contains('collapsed') ? '1' : '0');
    },

    toggleMobile() {
        if (!this._el) return;
        this._el.classList.toggle('mobile-open');
    },

    restore() {
        if (!this._el) return;
        if (localStorage.getItem('sidebar_collapsed') === '1') {
            this._el.classList.add('collapsed');
        }
    }
};

/* ============================================
   Auto-Initialise on DOMContentLoaded
   ============================================ */
document.addEventListener('DOMContentLoaded', async () => {
    // Detect base URL from current page location
    const pathParts = window.location.pathname.split('/public');
    API.baseUrl = window.location.origin + (pathParts[0] || '');

    // Load language strings first (needed for menu labels)
    // Try to get user's preferred language from auth
    await i18n.load();

    // Load and apply theme
    await ThemeManager.load();

    // Check authentication (skip on login page)
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const publicPages = ['login.html', 'activation_success.html'];
    const isPhpDashboard = currentPage.indexOf('dashboard.php') !== -1;

    if (!publicPages.includes(currentPage)) {
        const user = await Auth.check();
        if (!user) {
            window.location.href = API.baseUrl + '/public/login.html';
            return;
        }

        // If user has a preferred language, reload i18n with it
        if (user.preferred_language && user.preferred_language !== i18n.lang) {
            await i18n.load(user.preferred_language);
        }

        // Render header user info
        const userInfoEl = document.querySelector('.app-header .user-info');
        if (userInfoEl && user) {
            const initials = (user.name || user.username || '?').substring(0, 2);
            userInfoEl.innerHTML =
                '<span class="avatar">' + initials + '</span>' +
                '<span>' + (user.name || user.username) + '</span>';
        }

        // Render sidebar menu (skip for PHP dashboard — menu is server-rendered)
        if (!isPhpDashboard) {
            const menuContainer = document.querySelector('.app-sidebar .menu-list');
            if (menuContainer) {
                Menu.render(menuContainer, user.permissions || []);
                Menu.setActive(currentPage);
            }
        } else {
            // For PHP dashboard, filter menu items by permission client-side
            const perms = user.permissions || [];
            document.querySelectorAll('.app-sidebar .menu-item[data-perm]').forEach(el => {
                const perm = el.getAttribute('data-perm');
                if (perm && !perms.includes(perm) && !perms.includes('*')) {
                    el.style.display = 'none';
                }
            });
        }
    }

    // Initialise sidebar state
    Sidebar.init();
    Sidebar.restore();

    // Wire global event handlers
    _bindGlobalEvents();
});

/* ============================================
   Global Event Bindings
   ============================================ */
function _bindGlobalEvents() {
    // Theme toggle button
    document.querySelectorAll('[data-action="toggle-theme"]').forEach(btn => {
        btn.addEventListener('click', () => ThemeManager.toggle());
    });

    // Language toggle
    document.querySelectorAll('[data-action="toggle-lang"]').forEach(btn => {
        btn.addEventListener('click', () => i18n.toggle());
    });

    // Sidebar toggle
    document.querySelectorAll('[data-action="toggle-sidebar"]').forEach(btn => {
        btn.addEventListener('click', () => Sidebar.toggle());
    });

    // Mobile sidebar
    document.querySelectorAll('[data-action="toggle-sidebar-mobile"]').forEach(btn => {
        btn.addEventListener('click', () => Sidebar.toggleMobile());
    });

    // Logout
    document.querySelectorAll('[data-action="logout"]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            Auth.logout();
        });
    });

    // Modal close buttons
    document.querySelectorAll('.modal-close, [data-action="close-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // Close modal on backdrop click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', e => {
            if (e.target === modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
}
