// vehicle_management/assets/js/login.js
// Controls the login/register page (vehicle_management/public/login.html).
// FIX: Added form submission handler and ensured 'phone' field is sent during registration.
// FIX: Modified handleLogin to send identifier as login, emp_id, username, and email for compatibility.
// FIX: Added a direct 'click' listener to submitBtn to ensure handler execution.

(function () {
  'use strict';

  // --- Static Data & Configuration ---
  
  // 1. Language File Paths (Based on user request)
  const LANG_PATHS = {
    ar: '/vehicle_management/languages/ar_login.json',
    en: '/vehicle_management/languages/en_login.json'
  };

  // Global cache for loaded translations
  const I18N_MESSAGES_CACHE = {}; 

  // 2. Static Roles Data (from user's table request)
  const STATIC_ROLES_DATA = [
    { id: 4, name_en: 'maintenance_supervisor', name_ar: 'مسؤول صيانة', perms: ['can_view_all_vehicles', 'can_view_department_vehicles', 'can_assign_vehicle'] },
    { id: 6, name_en: 'technician', name_ar: 'فني صيانة', perms: ['can_view_department_vehicles'] },
    { id: 7, name_en: 'administrative_staff', name_ar: 'الموظف الإداري', perms: ['can_view_department_vehicles', 'can_assign_vehicle', 'can_receive_vehicle'] },
    { id: 1, name_en: 'super_admin', name_ar: 'سوبر أدمن', perms: ['can_create', 'can_edit', 'can_delete', 'can_view_all_vehicles', 'can_view_department_vehicles', 'can_assign_vehicle', 'can_receive_vehicle', 'can_override_department', 'can_self_assign_vehicle'] },
    { id: 2, name_en: 'admin', name_ar: 'مدير نظام', perms: ['can_create', 'can_edit', 'can_view_department_vehicles', 'can_assign_vehicle', 'can_receive_vehicle', 'can_override_department'] },
    { id: 3, name_en: 'regular_user', name_ar: 'مستخدم عادي', perms: ['can_assign_vehicle', 'can_receive_vehicle', 'can_override_department'] },
    { id: 5, name_en: 'shift_supervisor', name_ar: 'مسؤول مناوبة', perms: ['can_view_all_vehicles', 'can_view_department_vehicles', 'can_assign_vehicle', 'can_receive_vehicle', 'can_override_department'] },
    { id: 8, name_en: 'custom_user', name_ar: 'مستخدم مخصص', perms: [] }
  ];

  // --- API Endpoints ---
  const API_BASE = '/vehicle_management/api';
  const REFS_URL = `${API_BASE}/helper/get_references.php`;
  const LOGIN_URL = `${API_BASE}/users/login.php`;
  const REGISTER_URL = `${API_BASE}/users/register_user.php`;

  // --- DOM Elements ---
  const htmlEl = document.documentElement;
  const authCard = document.querySelector('.auth-card') || document.body;
  const authForm = document.getElementById('authForm');
  const btnLogin = document.getElementById('btn_login');
  const btnRegister = document.getElementById('btn_register');
  const registerFields = document.getElementById('register_fields');
  const loginFields = document.getElementById('login_fields');
  const submitBtn = document.getElementById('submitBtn');
  const msg = document.getElementById('msg');
  const titleEl = document.getElementById('title');
  const subtitleEl = document.getElementById('subtitle');

  const roleSel = document.getElementById('role_id');
  const deptSel = document.getElementById('department_id');
  const sectionSel = document.getElementById('section_id');
  const divisionSel = document.getElementById('division_id');

  // --- Language State and Toggle Creation (Single Button) ---
  let currentLanguage = 'ar'; // Default to Arabic
  let isLoadingTranslations = false; 

  // Create single language toggle button and insert it before the form
  let langToggleBtn = document.getElementById('langToggleBtn');
  if (!langToggleBtn) {
    langToggleBtn = document.createElement('div');
    langToggleBtn.id = 'langToggleBtn';
    langToggleBtn.style.display = 'flex';
    langToggleBtn.style.justifyContent = 'center';
    langToggleBtn.style.gap = '8px';
    langToggleBtn.style.marginBottom = '12px';

    const btnToggle = document.createElement('button');
    btnToggle.type = 'button';
    btnToggle.textContent = currentLanguage === 'ar' ? 'English' : 'العربية';
    btnToggle.dataset.lang = currentLanguage === 'ar' ? 'en' : 'ar';
    btnToggle.className = 'lang-toggle-btn';

    langToggleBtn.appendChild(btnToggle);

    if (authCard && titleEl) {
        authCard.insertBefore(langToggleBtn, titleEl);
    } else if (authCard) {
        authCard.insertBefore(langToggleBtn, authCard.firstChild);
    }
  }
  const btnToggle = langToggleBtn.querySelector('.lang-toggle-btn');


  // current mode: 'login' or 'register'
  let mode = 'login';

  // helper: get current language data map
  function getI18nMap() {
    return I18N_MESSAGES_CACHE[currentLanguage] || {}; // Return cached map or empty object
  }

  // Helper to fetch JSON data
  async function fetchJSON(url, opts = {}) {
    opts.credentials = opts.credentials || 'same-origin';
    const res = await fetch(url, opts);
    if (!res.ok) {
      const text = await res.text().catch(()=>null);
      throw new Error(res.status + ' ' + (text || res.statusText));
    }
    return res.json();
  }
  
  // *** Dynamic Language Loader ***
  async function loadLanguageFile(lang) {
    if (I18N_MESSAGES_CACHE[lang]) {
      return I18N_MESSAGES_CACHE[lang]; // Use cached version
    }
    if (isLoadingTranslations) return;
    isLoadingTranslations = true;
    
    try {
      const path = LANG_PATHS[lang];
      if (!path) throw new Error(`Path for language ${lang} not defined.`);
      
      const json = await fetchJSON(path);
      
      // Add custom messages/titles that are not in the JSON file
      const map = {
          ...json,
          loginBtn: json.submitLogin || (lang === 'ar' ? 'دخول' : 'Login'),
          registerBtn: json.submitRegister || (lang === 'ar' ? 'تسجيل' : 'Register'),
          // Fallback messages (Important for a clean user experience if translations are missing)
          missingFields: lang === 'ar' ? 'حقول مطلوبة: ' : 'Missing fields: ',
          creatingAccount: lang === 'ar' ? 'جارٍ إنشاء الحساب...' : 'Creating account...',
          loginProgress: lang === 'ar' ? 'جارٍ تسجيل الدخول...' : 'Signing in...',
          loginFailed: lang === 'ar' ? 'فشل تسجيل الدخول' : 'Login failed',
          registerSuccess: lang === 'ar' ? 'تم التسجيل بنجاح' : 'Registered successfully',
          passwordMismatch: lang === 'ar' ? 'كلمتا المرور غير متطابقتان' : 'Passwords do not match',
          permissionsTitle: lang === 'ar' ? 'الصلاحيات' : 'Permissions',
          pleaseConfirmPassword: lang === 'ar' ? 'الرجاء تأكيد كلمة المرور' : 'Please confirm password',
      };
      
      I18N_MESSAGES_CACHE[lang] = map;
      return map;
    } catch (e) {
      console.error(`Failed to load language file for ${lang} from ${LANG_PATHS[lang]}:`, e);
      // Fallback to minimal strings if file loading fails
      const fallback = {
          systemTitleEn: 'Vehicle Management System',
          systemTitleAr: 'نظام متابعة وإدارة السيارات',
          selectRole: lang === 'ar' ? 'اختر الدور' : 'Select role',
          selectDept: lang === 'ar' ? 'اختر الإدارة' : 'Select department',
          selectSection: lang === 'ar' ? 'اختر القسم' : 'Select section',
          selectDivision: lang === 'ar' ? 'اختر الشعبة' : 'Select division',
          loginBtn: lang === 'ar' ? 'دخول' : 'Login',
          registerBtn: lang === 'ar' ? 'تسجيل' : 'Register',
          missingFields: 'Error loading translations.',
          identifierPlaceholder: lang === 'ar' ? 'اسم المستخدم' : 'Username',
          passwordPlaceholder: lang === 'ar' ? 'كلمة المرور' : 'Password',
          institutionNameAr: 'بلدية مدينة الشارقة',
          institutionNameEn: 'Sharjah City Municipality',
      };
      I18N_MESSAGES_CACHE[lang] = fallback;
      return fallback;
    } finally {
      isLoadingTranslations = false;
    }
  }


  // helper: update a select's first option text (placeholder)
  function setSelectPlaceholder(sel, text) {
    if (!sel) return;
    const first = sel.querySelector('option');
    if (first) first.textContent = text;
  }

  // apply i18n to UI placeholders/buttons and set RTL/LTR
  function applyI18n(lang) {
    currentLanguage = lang;
    const map = getI18nMap();
    
    // 1. Set global directionality on the HTML element
    htmlEl.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
    htmlEl.setAttribute('lang', lang);

    // 2. Update main titles: (FIXED to ensure both are in the selected language)
    if (lang === 'ar') {
        if (titleEl) titleEl.textContent = map.systemTitleAr || 'نظام متابعة وإدارة السيارات';
        if (subtitleEl) subtitleEl.textContent = map.institutionNameAr || 'بلدية مدينة الشارقة'; 
    } else {
        if (titleEl) titleEl.textContent = map.systemTitleEn || 'Vehicle Management System';
        if (subtitleEl) subtitleEl.textContent = map.institutionNameEn || 'Sharjah City Municipality';
    }

    // 3. Update placeholders
    if (document.getElementById('identifier')) document.getElementById('identifier').placeholder = map.identifierPlaceholder || '';
    if (document.getElementById('password')) document.getElementById('password').placeholder = map.passwordPlaceholder || '';
    if (document.getElementById('emp_id')) document.getElementById('emp_id').placeholder = map.empIdPlaceholder || '';
    if (document.getElementById('reg_username')) document.getElementById('reg_username').placeholder = map.usernamePlaceholder || '';
    if (document.getElementById('reg_email')) document.getElementById('reg_email').placeholder = map.emailPlaceholder || '';
    if (document.getElementById('phone')) document.getElementById('phone').placeholder = map.phonePlaceholder || '';
    if (document.getElementById('reg_password')) document.getElementById('reg_password').placeholder = map.regPasswordPlaceholder || '';
    
    // 4. labels/buttons
    if (btnLogin) btnLogin.textContent = map.loginTitle || 'Login';
    if (btnRegister) btnRegister.textContent = map.registerTitle || 'Register';
    if (submitBtn) submitBtn.textContent = (mode === 'login') ? (map.loginBtn || 'Login') : (map.registerBtn || 'Create Account');

    // 5. update select default placeholders
    setSelectPlaceholder(roleSel, map.selectRole || 'Select role');
    setSelectPlaceholder(deptSel, map.selectDept || 'Select department');
    setSelectPlaceholder(sectionSel, map.selectSection || 'Select section');
    setSelectPlaceholder(divisionSel, map.selectDivision || 'Select division');

    // 6. Update the single toggle button text
    if (btnToggle) {
        btnToggle.textContent = lang === 'ar' ? 'English' : 'العربية';
        btnToggle.dataset.lang = lang === 'ar' ? 'en' : 'ar';
    }
    // Update confirm password placeholder if it exists (now it is permanent in HTML)
    const confPwd = document.getElementById('reg_password_confirm');
    if (confPwd) confPwd.placeholder = map.confirmPasswordPlaceholder || 'Confirm Password';

    // Re-load role permissions if box is visible to update text/direction
    showRolePermissions(roleSel.value);
    clearMessage(); // Clear message to reset directionality
  }

  // set mode (login/register)
  function setMode(m) {
    mode = m;
    
    // The confirm password field is now permanent in HTML, so no dynamic removal needed.

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
    applyI18n(currentLanguage);
    clearMessage();
  }

  // message helpers
  function showMessage(text, type = 'error') {
    if (!msg) return;
    const color = (type === 'success') ? 'green' : ((type === 'info') ? '#0369a1' : '#b22222');
    msg.style.color = color;
    msg.textContent = text;
    // Set directionality for the message
    msg.style.direction = currentLanguage === 'ar' ? 'rtl' : 'ltr';
  }
  function clearMessage() {
    if (!msg) return;
    msg.textContent = '';
  }

  // roles loader: Uses static data instead of API
  async function loadRoles(lang) {
    if (!roleSel) return;
    const map = getI18nMap();
    const prev = roleSel.value;
    roleSel.innerHTML = `<option value="">${map.selectRole || 'Select role'}</option>`;

    try {
      // Use STATIC_ROLES_DATA
      STATIC_ROLES_DATA.forEach(r => {
        const id = r.id;
        const name_ar = r.name_ar;
        const name_en = r.name_en;
        if (id == null) return;
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = (lang === 'en') ? (name_en || name_ar || `role ${id}`) : (name_ar || name_en || `role ${id}`);
        // attach permissions
        const perms = r.perms || [];
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

  // generic reference loader and fill helper (Kept for compatibility)
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
      // Ensure we prioritize the correct language field from the API response
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

  // Reference loading functions (Kept for compatibility)
  async function loadDepartments(lang = 'ar') {
    if (!deptSel) return;
    try {
      const j = await fetchJSON(`${REFS_URL}?lang=${encodeURIComponent(lang)}&type=departments`);
      fillSelectFromResponse(deptSel, j.departments || j.items || j.data || [], lang, getI18nMap().selectDept || 'Select department');
    } catch (e) {
      console.warn('loadDepartments failed', e);
    }
  }

  async function loadSectionsFor(departmentId, lang = 'ar') {
    if (!sectionSel) return;
    try {
      const url = `${REFS_URL}?lang=${encodeURIComponent(lang)}&type=sections` + (departmentId ? `&parent_id=${encodeURIComponent(departmentId)}` : '');
      const j = await fetchJSON(url);
      fillSelectFromResponse(sectionSel, j.sections || j.items || j.data || [], lang, getI18nMap().selectSection || 'Select section');
    } catch (e) {
      console.warn('loadSectionsFor failed', e);
    }
  }

  async function loadDivisionsFor(sectionId, lang = 'ar') {
    if (!divisionSel) return;
    try {
      const url = `${REFS_URL}?lang=${encodeURIComponent(lang)}&type=divisions` + (sectionId ? `&parent_id=${encodeURIComponent(sectionId)}` : '');
      const j = await fetchJSON(url);
      fillSelectFromResponse(divisionSel, j.divisions || j.items || j.data || [], lang, getI18nMap().selectDivision || 'Select division');
    } catch (e) {
      console.warn('loadDivisionsFor failed', e);
    }
  }

  // cascade listeners
  deptSel && deptSel.addEventListener('change', () => {
    const dep = deptSel.value || '';
    loadSectionsFor(dep, currentLanguage);
    if (divisionSel) fillSelectFromResponse(divisionSel, [], currentLanguage, getI18nMap().selectDivision || 'Select division');
  });
  sectionSel && sectionSel.addEventListener('change', () => {
    const sec = sectionSel.value || '';
    loadDivisionsFor(sec, currentLanguage);
  });

  // show role permissions (if option has data-perms)
  function showRolePermissions(roleId) {
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
    // Set directionality for the permissions box
    box.style.direction = currentLanguage === 'ar' ? 'rtl' : 'ltr';

    const map = getI18nMap();
    const title = document.createElement('div');
    title.textContent = map.permissionsTitle || 'Permissions';
    title.style.fontWeight = '700';
    title.style.marginBottom = '6px';
    box.appendChild(title);

    const ul = document.createElement('ul'); ul.style.margin = 0;
    // Adjust padding based on direction
    ul.style.paddingInlineStart = currentLanguage === 'ar' ? '0' : '18px';
    ul.style.paddingInlineEnd = currentLanguage === 'ar' ? '18px' : '0';

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

  // language toggle button behavior
  btnToggle && btnToggle.addEventListener('click', async () => {
    const newLang = btnToggle.dataset.lang;
    
    btnToggle.disabled = true; // Disable while loading
    
    // 1. Load the new language file
    await loadLanguageFile(newLang); 

    // 2. Apply translations and direction
    applyI18n(newLang); 
    
    // 3. Reload references with new language
    loadRoles(newLang); 
    loadDepartments(newLang).then(()=> { 
      if (deptSel && deptSel.value) loadSectionsFor(deptSel.value, newLang);
      if (sectionSel && sectionSel.value) loadDivisionsFor(sectionSel.value, newLang);
    });
    
    btnToggle.disabled = false; // Re-enable
  });

  // form submission handlers
  
  async function handleLogin() {
    const map = getI18nMap();
    const identifier = (document.getElementById('identifier') || {}).value?.trim() || '';
    const password = (document.getElementById('password') || {}).value || '';
    if (!identifier || !password) {
      showMessage((map.missingFields || 'Missing fields: ') + ' identifier, password', 'error');
      return;
    }
    submitBtn.disabled = true;
    showMessage(map.loginProgress || 'Signing in...', 'info');

    try {
      const params = new URLSearchParams();
      // ******* الإصلاح لتوافق تسجيل الدخول: إرسال المُعرِّف بأكثر من اسم *******
      params.append('login', identifier); 
      params.append('emp_id', identifier);
      params.append('username', identifier);
      params.append('email', identifier);
      // *************************************************************************
      params.append('password', password);
      
      const res = await fetch(LOGIN_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: params.toString()
      });
      const j = await res.json().catch(()=>({ success:false, message: map.loginFailed || 'Login failed' }));
      if (j.success) {
        showMessage((map.loginProgress || 'Signing in...') + ' ✓', 'success');
        setTimeout(()=> { window.location.href = '/vehicle_management/public/index.html'; }, 500);
      } else {
        showMessage(j.message || map.loginFailed || 'Login failed', 'error');
        submitBtn.disabled = false;
      }
    } catch (e) {
      console.error('login error', e);
      showMessage('Network error', 'error');
      submitBtn.disabled = false;
    }
  }

  async function handleRegister() {
    const map = getI18nMap();
    const username = (document.getElementById('reg_username') || {}).value?.trim() || '';
    const email = (document.getElementById('reg_email') || {}).value?.trim() || '';
    const password = (document.getElementById('reg_password') || {}).value || '';
    const confirm = (document.getElementById('reg_password_confirm') || {}).value || '';
    const emp = (document.getElementById('emp_id') || {}).value?.trim() || '';
    const phone = (document.getElementById('phone') || {}).value?.trim() || ''; // <== تم تضمين حقل الهاتف
    const role = (document.getElementById('role_id') || {}).value || '';
    
    const missing = [];
    if (!emp) missing.push('emp_id');
    if (!username) missing.push('username');
    if (!email) missing.push('email');
    if (!password) missing.push('password');
    if (!confirm) missing.push('confirm_password');
    if (!role) missing.push('role');

    if (missing.length) {
      showMessage((map.missingFields || 'Missing fields: ') + missing.join(', '), 'error');
      return;
    }
    if (password !== confirm) {
      showMessage(map.passwordMismatch || 'Passwords do not match', 'error');
      return;
    }

    submitBtn.disabled = true;
    showMessage(map.creatingAccount || 'Creating account...', 'info');

    try {
      const params = new URLSearchParams();
      params.append('emp_id', emp);
      params.append('username', username);
      params.append('email', email);
      params.append('password', password);
      params.append('role_id', role);
      params.append('department_id', deptSel?.value || '');
      params.append('section_id', sectionSel?.value || '');
      params.append('division_id', divisionSel?.value || '');
      params.append('preferred_language', currentLanguage);
      params.append('phone', phone); // <== إرسال قيمة حقل الهاتف

      const res = await fetch(REGISTER_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: params.toString()
      });

      const j = await res.json().catch(()=>({ success:false, message:'Invalid response' }));
      if (j.success) {
        showMessage(j.message || map.registerSuccess || 'Registered successfully', 'success');
        
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

    // 1. معالج حدث إرسال النموذج (التعامل الأساسي)
    if (authForm) {
        authForm.addEventListener('submit', (e) => {
            e.preventDefault(); // يمنع إعادة تحميل الصفحة الافتراضية
            if (mode === 'login') {
                handleLogin();
            } else {
                handleRegister();
            }
        });
    }
    
    // 2. *** الإضافة الجديدة: معالج حدث النقر المباشر على زر الإرسال ***
    // هذا يضمن تشغيل الدالة المطلوبة حتى لو كان هناك مشكلة في معالج submit.
    if (submitBtn) {
        submitBtn.addEventListener('click', (e) => {
            e.preventDefault(); 
            if (mode === 'login') {
                handleLogin();
            } else {
                handleRegister();
            }
        });
    }
    // ***************************************************************

    // 1. Load initial language (default 'ar')
    loadLanguageFile(currentLanguage).then(() => {
        // 2. Apply translations and direction
        applyI18n(currentLanguage);

        // 3. Load resources (roles and refs)
        Promise.resolve().then(async () => {
          await loadRoles(currentLanguage); 
          await loadDepartments(currentLanguage);
          if (deptSel && deptSel.value) await loadSectionsFor(deptSel.value, currentLanguage);
          if (sectionSel && sectionSel.value) await loadDivisionsFor(sectionSel.value, currentLanguage);
        });
    });
  })();

})();