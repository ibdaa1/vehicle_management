// permissions.js
// Loads role permissions from API and applies them to elements with data-permission.
// Designed to be safe: uses same-origin credentials, logs debug info, re-applies on DOM changes.

window.Permissions = (function(){
  const API = '/vehicle_management/api/permissions/get_permissions.php';
  let perms = null;        // { create:bool, edit:bool, delete:bool }
  let roleId = null;
  let loaded = false;

  // debug log helper
  function log(...args){ try { console.debug('[Permissions]', ...args); } catch(e){} }

  // Try to get permissions from session_check result if it's already available
  async function tryFromSession() {
    if (typeof window.sessionCheck !== 'function') return null;
    try {
      const s = await window.sessionCheck();
      if (s && s.success && s.user) {
        // server-side session_check may include a permissions object already
        if (s.user.permissions) {
          log('Using permissions from session_check.user.permissions', s.user.permissions);
          roleId = s.user.role_id ? parseInt(s.user.role_id,10) : null;
          return {
            create: !!s.user.permissions.create,
            edit:   !!s.user.permissions.edit,
            delete: !!s.user.permissions.delete
          };
        }
        // fallback: use role_id and ask API
        if (s.user.role_id) {
          roleId = parseInt(s.user.role_id,10);
        }
      }
    } catch(e) {
      // ignore
      log('tryFromSession error', e && e.message);
    }
    return null;
  }

  // fetch permissions from API; if roleId provided we omit role_id param to get current user's role (unless caller wants explicit role)
  async function fetchFromApi(requestRoleId = null) {
    try {
      let url = API;
      // if explicit role requested and caller is admin, client-side may supply ?role_id= - but server enforces admin check
      if (requestRoleId) url += '?role_id=' + encodeURIComponent(requestRoleId);
      const resp = await fetch(url, { credentials: 'same-origin', headers: { 'Accept':'application/json' } });
      if (!resp.ok) {
        log('get_permissions API returned not-ok', resp.status);
        return null;
      }
      const j = await resp.json();
      if (!j || !j.success) {
        log('get_permissions API error response', j);
        return null;
      }
      roleId = j.role_id ?? roleId;
      const r = j.role || {};
      const p = {
        create: !!r.can_create,
        edit:   !!r.can_edit,
        delete: !!r.can_delete
      };
      log('Fetched permissions from API', p, 'role', roleId);
      return p;
    } catch (e) {
      log('fetchFromApi error', e && e.message);
      return null;
    }
  }

  // public load: try session then API
  async function loadForSession() {
    if (loaded) return perms;
    // try session_check first (fast)
    const fromSession = await tryFromSession();
    if (fromSession) {
      perms = fromSession;
      loaded = true;
      return perms;
    }
    // otherwise fetch from API
    const fromApi = await fetchFromApi();
    if (fromApi) {
      perms = fromApi;
      loaded = true;
      return perms;
    }
    // fallback conservative defaults: no edit/delete, allow create for authenticated users
    perms = { create: true, edit: false, delete: false };
    loaded = true;
    log('Using fallback permissions', perms);
    return perms;
  }

  // map data-permission token to boolean using perms
  function checkToken(token) {
    if (!perms) return false;
    token = (token || '').toString().trim();
    if (!token) return true; // no token => visible
    // explicit tokens that are always allowed for authenticated users
    if (token === 'view_profile' || token === 'view_settings' || token === 'view_reports' || token === 'view_users' || token === 'view') return true;
    // create tokens
    if (token.startsWith('create') || token.includes('add') || token.includes('new')) return !!perms.create;
    // edit/manage tokens
    if (token.startsWith('edit') || token.startsWith('manage') || token === 'manage_roles' || token.startsWith('assign') || token === 'manage_maintenance') return !!perms.edit;
    // delete tokens
    if (token.startsWith('delete') || token.includes('remove')) return !!perms.delete;
    // fallback: only admins (role 1/2) allowed
    if (roleId && (parseInt(roleId,10) === 1 || parseInt(roleId,10) === 2)) return true;
    // otherwise deny
    return false;
  }

  // apply permissions to DOM:
  // - finds elements with data-permission attribute and hide/show them
  // - elements without attribute are left untouched
  function apply() {
    if (!loaded) {
      log('Permissions not loaded yet; apply skipped');
      return;
    }
    const els = document.querySelectorAll('[data-permission]');
    log('Applying permissions to', els.length, 'elements; perms=', perms, 'role=', roleId);
    els.forEach(el=>{
      const token = el.getAttribute('data-permission');
      const allowed = checkToken(token);
      // Show/hide by inline style; keep original display if present
      if (allowed) {
        el.style.display = el.dataset._originalDisplay || '';
      } else {
        // remember original display style so we can restore later
        if (typeof el.dataset._originalDisplay === 'undefined') {
          el.dataset._originalDisplay = window.getComputedStyle(el).display;
        }
        el.style.display = 'none';
      }
      // also add aria-hidden for accessibility
      el.setAttribute('aria-hidden', allowed ? 'false' : 'true');
    });
    // also expose on window for debugging
    window.__Permissions = { perms, roleId, loaded, appliedAt: Date.now() };
  }

  // Observe DOM changes and re-apply (useful when UI injects buttons later)
  function observeDom() {
    try {
      const mo = new MutationObserver((mutations)=>{
        // small debounce: reapply after batch of mutations
        if (window.__Permissions && window.__Permissions._reapplyTimeout) clearTimeout(window.__Permissions._reapplyTimeout);
        window.__Permissions._reapplyTimeout = setTimeout(()=> {
          log('DOM changed, reapplying permissions');
          apply();
        }, 120);
      });
      mo.observe(document.documentElement || document.body, { childList: true, subtree: true, attributes: false });
      // store observer in window to avoid gc and possibly disconnect later
      window.__Permissions = window.__Permissions || {};
      window.__Permissions.domObserver = mo;
    } catch (e) {
      log('observeDom error', e && e.message);
    }
  }

  // init helper: load then apply and start observer
  async function init() {
    await loadForSession();
    apply();
    observeDom();
  }

  // Force reload permissions from API (optionally with explicit role_id) then apply
  async function reload(roleIdExplicit = null) {
    loaded = false;
    perms = null;
    roleId = roleIdExplicit ? parseInt(roleIdExplicit,10) : null;
    await loadForSession();
    apply();
  }

  // convenience: return perms
  function get() { return perms; }
  function getRoleId() { return roleId; }

  return { init, loadForSession, apply, reload, get, getRoleId };
})();