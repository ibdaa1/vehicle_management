// vehicle_management/assets/js/login.js
// Controls the login/register page (vehicle_management/public/login.html).
// - Adds a visible language toggle (AR/EN) above the form.
// - Loads roles (public fallback) and reference lists (departments/sections/divisions).
// - Cascading selects: department -> sections -> divisions.
// - Login submits as application/x-www-form-urlencoded (legacy PHP friendly).
// - Register submits as application/x-www-form-urlencoded (max compatibility).
// - Bilingual placeholders/labels updated on language switch.
// - Resilient loading: accepts multiple JSON shapes for roles/references.
// - Minimal DOM assumptions: expects elements with ids used in HTML (see login.html).
//
// Install: replace vehicle_management/assets/js/login.js with this file and clear browser cache.

(function () {
  'use strict';

  const API_BASE = '/vehicle_management/api';
  const REFS_URL = `${API_BASE}/helper/get_references.php`;
  // Prefer a public roles endpoint; fallback to protected list.php (may return Forbidden)
  const ROLES_PUBLIC = `${API_BASE}/permissions/roles/roles_public.php`;
  const ROLES_PROTECTED = `${API_BASE}/permissions/roles/list.php`;
  const LOGIN_URL = `${API_BASE}/users/login.php`;
  const REGISTER_URL = `${API_BASE}/users/register_user.php`;

  // DOM elements (expected in login.html)
  const authCard = document.querySelector('.auth-card') || document.body;
  const authForm = document.getElementById('authForm');
  const btnLogin = document.getElementById('btn_login');
  const btnRegister = document.getElementById('btn_register');
  const registerFields = document.getElementById('register_fields');
  const loginFields = document.getElementById('login_fields');
  const submitBtn = document.getElementById('submitBtn');
  const msg = document.getElementById('msg');

  const preferredLanguage = document.getElementById('preferred_language'); // may exist
  const roleSel = document.getElementById('role_id');
  const deptSel = document.getElementById('department_id');
  const sectionSel = document.getElementById('section_id');
  const divisionSel = document.getElementById('division_id');

  // create language toggle button and insert it before the form if not present
  let langToggleBtn = document.getElementById('langToggleBtn');
  if (!langToggleBtn) {
    langToggleBtn = document.createElement('div');
    langToggleBtn.id = 'langToggleBtn';
    langToggleBtn.style.display = 'flex';
    langToggleBtn.style.justifyContent = 'center';
    langToggleBtn.style.gap = '8px';
    langToggleBtn.style.marginBottom = '12px';

    const btnAr = document.createElement('button');
    btnAr.type = 'button';
    btnAr.textContent = 'العربية';
    btnAr.dataset.lang = 'ar';
    btnAr.className = 'lang-btn';
    btnAr.style.padding = '6px 10px';
    btnAr.style.borderRadius = '6px';
    btnAr.style.border = '1px solid transparent';
    btnAr.style.cursor = 'pointer';

    const btnEn = document.createElement('button');
    btnEn.type = 'button';
    btnEn.textContent = 'English';
    btnEn.dataset.lang = 'en';
    btnEn.className = 'lang-btn';
    btnEn.style.padding = '6px 10px';
    btnEn.style.borderRadius = '6px';
    btnEn.style.border = '1px solid transparent';
    btnEn.style.cursor = 'pointer';

    langToggleBtn.appendChild(btnAr);
    langToggleBtn.appendChild(btnEn);

    // insert at top of auth card
    if (authCard) authCard.insertBefore(langToggleBtn, authCard.firstChild);
  }

  // basic I18N dictionary for placeholders and button labels
  const I18N = {
    ar: {
      loginTitle: 'تسجيل الدخول',
      registerTitle: 'تسجيل جديد',
      identifierPlaceholder: 'اسم المستخدم أو البريد أو الرمز الوظيفي',
      passwordPlaceholder: 'كلمة المرور',
      empIdPlaceholder: 'الرمز الوظيفي',
      usernamePlaceholder: 'اسم المستخدم',
      emailPlaceholder: 'البريد الإلكتروني',
      phonePlaceholder: 'الهاتف (اختياري)',
      regPasswordPlaceholder: 'كلمة المرور',
      selectRole: 'اختر الدور',
      selectDept: 'اختر الإدارة',
      selectSection: 'اختر القسم',
      selectDivision: 'اختر الوحدة',
      loginBtn: 'دخول',
      registerBtn: 'تسجيل',
      missingFields: 'حقول مطلوبة: ',
      creatingAccount: 'جارٍ إنشاء الحساب...',
      loginProgress: 'جارٍ تسجيل الدخول...',
      loginFailed: 'فشل تسجيل الدخول',
      registerSuccess: 'تم التسجيل بنجاح',
      passwordMismatch: 'كلمتا المرور غير متطابقتان',
      permissionsTitle: 'الصلاحيات'
    },
    en: {
      loginTitle: 'Login',
      registerTitle: 'Register',
      identifierPlaceholder: 'Username or Email or Emp ID',
      passwordPlaceholder: 'Password',
      empIdPlaceholder: 'Employee ID',
      usernamePlaceholder: 'Username',
      emailPlaceholder: 'Email',
      phonePlaceholder: 'Phone (optional)',
      regPasswordPlaceholder: 'Password',
      selectRole: 'Select role',
      selectDept: 'Select department',
      selectSection: 'Select section',
      selectDivision: 'Select division',
      loginBtn: 'Login',
      registerBtn: 'Register',
      missingFields: 'Missing fields: ',
      creatingAccount: 'Creating account...',
      loginProgress: 'Signing in...',
      loginFailed: 'Login failed',
      registerSuccess: 'Registered successfully',
      passwordMismatch: 'Passwords do not match',
      permissionsTitle: 'Permissions'
    }
  };

  // current mode: 'login' or 'register'
  let mode = 'login';

  // helper: current language
  function currentLang() {
    if (preferredLanguage && preferredLanguage.value) return preferredLanguage.value;
    // find active button
    const active = langToggleBtn.querySelector('.lang-btn[aria-pressed="true"]');
    return active ? active.dataset.lang : 'ar';
  }

  // apply i18n to UI placeholders/buttons
  function applyI18n(lang) {
    // Update placeholders
    const idEl = document.getElementById('identifier');
    if (idEl) idEl.placeholder = I18N[lang].identifierPlaceholder;
    const pwd = document.getElementById('password');
    if (pwd) pwd.placeholder = I18N[lang].passwordPlaceholder;
    const emp = document.getElementById('emp_id');
    if (emp) emp.placeholder = I18N[lang].empIdPlaceholder;
    const uname = document.getElementById('reg_username');
    if (uname) uname.placeholder = I18N[lang].usernamePlaceholder;
    const em = document.getElementById('reg_email');
    if (em) em.placeholder = I18N[lang].emailPlaceholder;
    const phone = document.getElementById('phone');
    if (phone) phone.placeholder = I18N[lang].phonePlaceholder;
    const rpwd = document.getElementById('reg_password');
    if (rpwd) rpwd.placeholder = I18N[lang].regPasswordPlaceholder;
    // labels/buttons
    if (btnLogin) btnLogin.textContent = I18N[lang].loginTitle;
    if (btnRegister) btnRegister.textContent = I18N[lang].registerTitle;
    if (submitBtn) submitBtn.textContent = (mode === 'login') ? I18N[lang].loginBtn : I18N[lang].registerBtn;
    // update select default placeholders if selects exist
    if (roleSel) setSelectPlaceholder(roleSel, I18N[lang].selectRole);
    if (deptSel) setSelectPlaceholder(deptSel, I18N[lang].selectDept);
    if (sectionSel) setSelectPlaceholder(sectionSel, I18N[lang].selectSection);
    if (divisionSel) setSelectPlaceholder(divisionSel, I18N[lang].selectDivision);
  }

  function setSelectPlaceholder(sel, text) {
    if (!sel) return;
    const first = sel.querySelector('option');
    if (first) first.textContent = text;
  }

  // set mode (login/register)
  function setMode(m) {
    mode = m;
    if (mode === 'login') {
      if (btnLogin) btnLogin.classList.add('active');
      if (btnRegister) btnRegister.classList.remove('active');
      if (registerFields) registerFields.style.display = 'none';
      if (loginFields) loginFields.style.display = 'block';
    } else {
      if (btnLogin) btnLogin.classList.remove('active');
      if (btnRegister) btnRegister.classList.add('active');
      if (registerFields) registerFields.style.display = 'block';
      if (loginFields) loginFields.style.display = 'none';
    }
    applyI18n(currentLang());
    if (submitBtn) submitBtn.textContent = (mode === 'login') ? I18N[currentLang()].loginBtn : I18N[currentLang()].registerBtn;
    clearMessage();
  }

  // message helpers
  function showMessage(text, type = 'error') {
    if (!msg) return;
    msg.style.color = (type === 'success') ? 'green' : ((type === 'info') ? '#0369a1' : '#b22222');
    msg.textContent = text;
  }
  function clearMessage() {
    if (!msg) return;
    msg.textContent = '';
  }

  // fetch JSON helper with same-origin credentials
  async function fetchJSON(url, opts = {}) {
    opts.credentials = opts.credentials || 'same-origin';
    const res = await fetch(url, opts);
    if (!res.ok) {
      // return a structured error
      const text = await res.text().catch(()=>null);
      throw new Error(res.status + ' ' + (text || res.statusText));
    }
    return res.json();
  }

  // roles loader: try public endpoint, if Forbidden try protected (will likely 403)
  async function loadRoles(lang = 'ar') {
    if (!roleSel) return;
    const prev = roleSel.value;
    // start with placeholder
    roleSel.innerHTML = `<option value="">${I18N[lang].selectRole}</option>`;
    try {
      let j;
      try {
        j = await fetchJSON(`${ROLES_PUBLIC}?lang=${encodeURIComponent(lang)}`);
      } catch (errPublic) {
        // try protected (may return Forbidden)
        try {
          j = await fetchJSON(`${ROLES_PROTECTED}?lang=${encodeURIComponent(lang)}`);
        } catch (errProtected) {
          // both failed: leave placeholder and log
          console.warn('roles endpoints failed', errPublic, errProtected);
          return;
        }
      }
      // detect array of roles
      let roles = [];
      if (Array.isArray(j.roles)) roles = j.roles;
      else if (Array.isArray(j.data)) roles = j.data;
      else if (Array.isArray(j.items)) roles = j.items;
      else if (Array.isArray(j)) roles = j;
      // normalize and add options
      roles.forEach(r => {
        const id = r.id ?? r.role_id ?? r.id_role ?? null;
        const name_ar = r.name_ar ?? r.name ?? r.label ?? r.title ?? '';
        const name_en = r.name_en ?? r.name ?? r.title ?? '';
        if (id == null) return;
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = (lang === 'en') ? (name_en || name_ar || `role ${id}`) : (name_ar || name_en || `role ${id}`);
        // attach permissions as JSON string if present
        const perms = r.permissions ?? r.perms ?? r.actions ?? r.permissions_list ?? [];
        try { opt.dataset.perms = JSON.stringify(perms); } catch(e) { opt.dataset.perms = '[]'; }
        roleSel.appendChild(opt);
      });
      // restore previous if exists
      if (prev) {
        const restored = roleSel.querySelector(`option[value="${prev}"]`);
        if (restored) roleSel.value = prev;
      }
    } catch (e) {
      console.error('loadRoles error', e);
    }
  }

  // generic reference loader and fill helper
  function fillSelectFromResponse(sel, items, lang, placeholder) {
    if (!sel) return;
    const prev = sel.value;
    sel.innerHTML = '';
    const placeholderOpt = document.createElement('option');
    placeholderOpt.value = '';
    placeholderOpt.textContent = placeholder;
    sel.appendChild(placeholderOpt);
    (items || []).forEach(it => {
      const id = it.id ?? it.department_id ?? it.section_id ?? it.division_id ?? null;
      if (id == null) return;
      const name = (lang === 'en') ? (it.name_en || it.name || it.name_ar) : (it.name_ar || it.name || it.name_en);
      const opt = document.createElement('option');
      opt.value = id;
      opt.textContent = name;
      sel.appendChild(opt);
    });
    // attempt restore
    if (prev) {
      const opt = sel.querySelector(`option[value="${prev}"]`);
      if (opt) sel.value = prev;
    }
  }

  async function loadDepartments(lang = 'ar') {
    if (!deptSel) return;
    try {
      const j = await fetchJSON(`${REFS_URL}?lang=${encodeURIComponent(lang)}&type=departments`);
      fillSelectFromResponse(deptSel, j.departments || j.items || j.data || [], lang, I18N[lang].selectDept);
    } catch (e) {
      console.warn('loadDepartments failed', e);
    }
  }

  async function loadSectionsFor(departmentId, lang = 'ar') {
    if (!sectionSel) return;
    try {
      const url = `${REFS_URL}?lang=${encodeURIComponent(lang)}&type=sections` + (departmentId ? `&parent_id=${encodeURIComponent(departmentId)}` : '');
      const j = await fetchJSON(url);
      fillSelectFromResponse(sectionSel, j.sections || j.items || j.data || [], lang, I18N[lang].selectSection);
    } catch (e) {
      console.warn('loadSectionsFor failed', e);
    }
  }

  async function loadDivisionsFor(sectionId, lang = 'ar') {
    if (!divisionSel) return;
    try {
      const url = `${REFS_URL}?lang=${encodeURIComponent(lang)}&type=divisions` + (sectionId ? `&parent_id=${encodeURIComponent(sectionId)}` : '');
      const j = await fetchJSON(url);
      fillSelectFromResponse(divisionSel, j.divisions || j.items || j.data || [], lang, I18N[lang].selectDivision);
    } catch (e) {
      console.warn('loadDivisionsFor failed', e);
    }
  }

  // cascade listeners
  deptSel && deptSel.addEventListener('change', () => {
    const dep = deptSel.value || '';
    // load sections for selected department
    loadSectionsFor(dep, currentLang());
    // clear divisions
    if (divisionSel) fillSelectFromResponse(divisionSel, [], currentLang(), I18N[currentLang()].selectDivision);
  });
  sectionSel && sectionSel.addEventListener('change', () => {
    const sec = sectionSel.value || '';
    loadDivisionsFor(sec, currentLang());
  });

  // show role permissions (if option has data-perms)
  function showRolePermissions(roleId) {
    // create box in registerFields if not exist
    if (!registerFields) return;
    let box = document.getElementById('rolePermissionsBox');
    if (!box) {
      box = document.createElement('div');
      box.id = 'rolePermissionsBox';
      box.style.marginTop = '8px';
      box.style.fontSize = '13px';
      box.style.color = '#444';
      registerFields.appendChild(box);
    }
    box.innerHTML = '';
    if (!roleSel) return;
    const opt = roleSel.querySelector(`option[value="${roleId}"]`);
    if (!opt) { box.style.display = 'none'; return; }
    const permsRaw = opt.dataset.perms || '[]';
    let perms;
    try { perms = JSON.parse(permsRaw); } catch(e) { perms = []; }
    if (!perms || perms.length === 0) { box.style.display = 'none'; return; }
    box.style.display = 'block';
    const title = document.createElement('div');
    title.textContent = I18N[currentLang()].permissionsTitle;
    title.style.fontWeight = '700';
    title.style.marginBottom = '6px';
    box.appendChild(title);
    const ul = document.createElement('ul'); ul.style.margin = 0; ul.style.paddingInlineStart = '18px';
    perms.forEach(p => {
      const li = document.createElement('li');
      li.textContent = typeof p === 'string' ? p : (p.name || p.label || JSON.stringify(p));
      ul.appendChild(li);
    });
    box.appendChild(ul);
  }

  // bind role change to show permissions
  roleSel && roleSel.addEventListener('change', () => {
    showRolePermissions(roleSel.value);
  });

  // language toggle buttons behavior
  (function bindLangButtons() {
    const btns = langToggleBtn.querySelectorAll('.lang-btn');
    btns.forEach(b => {
      // set aria-pressed for styling and state
      b.addEventListener('click', () => {
        const lang = b.dataset.lang || 'ar';
        // mark pressed
        btns.forEach(x => { x.setAttribute('aria-pressed', 'false'); x.style.opacity = '0.7'; });
        b.setAttribute('aria-pressed', 'true'); b.style.opacity = '1';
        // update preferredLanguage select if exists
        if (preferredLanguage) preferredLanguage.value = lang;
        // apply i18n and reload roles/refs in this language
        applyI18n(lang);
        loadRoles(lang);
        loadDepartments(lang).then(()=> {
          if (deptSel && deptSel.value) loadSectionsFor(deptSel.value, lang);
        });
      });
    });
    // default: select currentLang or set first (Arabic)
    const initial = currentLang() || 'ar';
    const activeBtn = langToggleBtn.querySelector(`.lang-btn[data-lang="${initial}"]`);
    if (activeBtn) activeBtn.click();
  })();

  // apply i18n function: update placeholders/button text
  function applyI18n(lang) {
    const map = I18N[lang] || I18N.ar;
    const idEl = document.getElementById('identifier'); if (idEl) idEl.placeholder = map.identifierPlaceholder;
    const pwd = document.getElementById('password'); if (pwd) pwd.placeholder = map.passwordPlaceholder;
    const emp = document.getElementById('emp_id'); if (emp) emp.placeholder = map.empIdPlaceholder;
    const uname = document.getElementById('reg_username'); if (uname) uname.placeholder = map.usernamePlaceholder;
    const email = document.getElementById('reg_email'); if (email) email.placeholder = map.emailPlaceholder;
    const phone = document.getElementById('phone'); if (phone) phone.placeholder = map.phonePlaceholder;
    const rpwd = document.getElementById('reg_password'); if (rpwd) rpwd.placeholder = map.regPasswordPlaceholder;
    if (btnLogin) btnLogin.textContent = map.loginTitle;
    if (btnRegister) btnRegister.textContent = map.registerTitle;
    if (submitBtn) submitBtn.textContent = (mode === 'login') ? map.loginBtn : map.registerBtn;
    if (roleSel) setSelectPlaceholder(roleSel, map.selectRole);
    if (deptSel) setSelectPlaceholder(deptSel, map.selectDept);
    if (sectionSel) setSelectPlaceholder(sectionSel, map.selectSection);
    if (divisionSel) setSelectPlaceholder(divisionSel, map.selectDivision);
  }

  // form submission handlers
  if (authForm) {
    authForm.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      clearMessage();
      if (mode === 'login') {
        await handleLogin();
      } else {
        await handleRegister();
      }
    });
  }

  async function handleLogin() {
    const identifier = (document.getElementById('identifier') || {}).value?.trim() || '';
    const password = (document.getElementById('password') || {}).value || '';
    if (!identifier || !password) {
      showMessage(I18N[currentLang()].missingFields + ' identifier, password', 'error');
      return;
    }
    submitBtn.disabled = true;
    showMessage(I18N[currentLang()].loginProgress, 'info');

    try {
      const params = new URLSearchParams();
      params.append('login', identifier);
      params.append('password', password);
      const res = await fetch(LOGIN_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: params.toString()
      });
      const j = await res.json().catch(()=>({ success:false, message: I18N[currentLang()].loginFailed }));
      if (j.success) {
        showMessage(I18N[currentLang()].loginProgress + ' ✓', 'success');
        setTimeout(()=> { window.location.href = '/vehicle_management/public/index.html'; }, 500);
      } else {
        showMessage(j.message || I18N[currentLang()].loginFailed, 'error');
        submitBtn.disabled = false;
      }
    } catch (e) {
      console.error('login error', e);
      showMessage('Network error', 'error');
      submitBtn.disabled = false;
    }
  }

  async function handleRegister() {
    const username = (document.getElementById('reg_username') || {}).value?.trim() || '';
    const email = (document.getElementById('reg_email') || {}).value?.trim() || '';
    const password = (document.getElementById('reg_password') || {}).value || '';
    let confirm = (document.getElementById('reg_password_confirm') || {}).value || '';
    const emp = (document.getElementById('emp_id') || {}).value?.trim() || '';
    const role = (document.getElementById('role_id') || {}).value || '';

    // if confirm field not present, create it and ask the user to confirm (simple UX improvement)
    if (!document.getElementById('reg_password_confirm')) {
      const row = document.createElement('div');
      row.className = 'form-row';
      const inp = document.createElement('input');
      inp.type = 'password';
      inp.id = 'reg_password_confirm';
      inp.placeholder = currentLang() === 'en' ? 'Confirm password' : 'تأكيد كلمة المرور';
      row.appendChild(inp);
      if (registerFields) registerFields.appendChild(row);
      showMessage(currentLang() === 'en' ? 'Please confirm password' : 'الرجاء تأكيد كلمة المرور', 'info');
      return;
    }
    confirm = (document.getElementById('reg_password_confirm') || {}).value || '';

    const missing = [];
    if (!emp) missing.push('emp_id');
    if (!username) missing.push('username');
    if (!email) missing.push('email');
    if (!password) missing.push('password');
    if (!role) missing.push('role');

    if (missing.length) {
      showMessage(I18N[currentLang()].missingFields + missing.join(', '), 'error');
      return;
    }
    if (password !== confirm) {
      showMessage(I18N[currentLang()].passwordMismatch, 'error');
      return;
    }

    submitBtn.disabled = true;
    showMessage(I18N[currentLang()].creatingAccount, 'info');

    try {
      // send as application/x-www-form-urlencoded for maximum compatibility
      const params = new URLSearchParams();
      params.append('emp_id', emp);
      params.append('username', username);
      params.append('email', email);
      params.append('password', password);
      params.append('role_id', role);
      params.append('department_id', deptSel?.value || '');
      params.append('section_id', sectionSel?.value || '');
      params.append('division_id', divisionSel?.value || '');
      params.append('preferred_language', currentLang());

      const res = await fetch(REGISTER_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: params.toString()
      });

      const j = await res.json().catch(()=>({ success:false, message:'Invalid response' }));
      if (j.success) {
        showMessage(j.message || I18N[currentLang()].registerSuccess, 'success');
        setTimeout(()=> {
          setMode('login');
          const idEl = document.getElementById('identifier');
          if (idEl) idEl.value = username;
          submitBtn.disabled = false;
        }, 800);
      } else {
        showMessage(j.message || 'Register failed', 'error');
        submitBtn.disabled = false;
      }
    } catch (e) {
      console.error('register error', e);
      showMessage('Network error', 'error');
      submitBtn.disabled = false;
    }
  }

  // initial setup: bind buttons and load references
  (function init() {
    // mode toggle
    if (btnLogin) btnLogin.addEventListener('click', () => setMode('login'));
    if (btnRegister) btnRegister.addEventListener('click', () => setMode('register'));
    // language buttons already bound earlier by creation; ensure preferredLanguage exists
    if (!preferredLanguage) {
      // create a hidden select to keep compatibility with other code
      const sel = document.createElement('select');
      sel.id = 'preferred_language';
      sel.style.display = 'none';
      sel.innerHTML = '<option value="ar">العربية</option><option value="en">English</option>';
      document.body.appendChild(sel);
    }
    // apply i18n for initial language
    applyI18n(currentLang());
    // Load roles (preserve) and refs
    Promise.resolve().then(async () => {
      await loadRoles(currentLang());
      await loadDepartments(currentLang());
      if (deptSel && deptSel.value) await loadSectionsFor(deptSel.value, currentLang());
      if (sectionSel && sectionSel.value) await loadDivisionsFor(sectionSel.value, currentLang());
    });
  })();

})();