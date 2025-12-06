// /vehicle_management/assets/js/Vehicle_Maintenance.js
(function () {
  'use strict';
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
  const API_MAINTENANCE = '/vehicle_management/api/vehicle/Vehicle_Maintenance.php';
  const API_PERMISSIONS = '/vehicle_management/api/permissions/get_permissions.php';
  const SESSION_INIT = '/vehicle_management/api/config/session.php?init=1';
  
  const form = document.getElementById('maintenanceForm');
  const submitBtn = document.getElementById('submitBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const msgEl = document.getElementById('msg');
  const loggedUserEl = document.getElementById('loggedUser');
  const orgNameEl = document.getElementById('orgName');
  const searchInput = document.getElementById('searchInput');
  const typeFilter = document.getElementById('typeFilter');
  const searchBtn = document.getElementById('searchBtn');
  const addNewBtn = document.getElementById('addNewBtn');
  const resultsSection = document.getElementById('resultsSection');
  const formSection = document.getElementById('formSection');
  const maintenanceTableBody = document.getElementById('maintenanceTableBody');
  const totalCount = document.getElementById('totalCount');
  const pagination = document.getElementById('pagination');
  const formTitle = document.getElementById('formTitle');
  
  let globalSessionId = null;
  let currentSession = null;
  let currentPermissions = null;
  let currentPage = 1;
  let perPage = 30;
  let currentLang = 'ar';
  let translations = {};
  
  function showMsg(text, type='info'){
    if (!msgEl) return;
    const color = type==='error' ? '#8b1e1e' : (type==='success' ? '#065f46' : '#6b7280');
    msgEl.innerHTML = `<div style="color:${color}">${text}</div>`;
  }
  function clearMsg(){ if (msgEl) msgEl.innerHTML = ''; }
  
  function getCookie(name) {
    const re = new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[]\/+^])/g, '\\$1') + '=([^;]*)');
    const m = document.cookie.match(re);
    const val = m ? decodeURIComponent(m[1]) : null;
    return val;
  }
  
  async function loadTranslations(lang) {
    try {
      const r = await fetch(`/vehicle_management/languages/${lang}_Vehicle_Maintenance.json`, { credentials: 'include' });
      if (r.ok) {
        translations = await r.json();
        applyTranslations();
      }
    } catch (e) {
      console.error('Failed to load translations:', e);
    }
  }
  
  function applyTranslations() {
    if (!translations) return;
    const t = translations;
    
    // Update page elements
    if (document.getElementById('pageTitle')) document.getElementById('pageTitle').textContent = t.title || 'إدارة صيانة المركبات';
    if (document.getElementById('pageSubtitle')) document.getElementById('pageSubtitle').textContent = t.subtitle || '';
    
    // Update buttons
    const backBtn = document.querySelector('a[href*="index.html"]');
    if (backBtn) backBtn.textContent = currentLang === 'en' ? '🏠 Back to Home' : '🏠 العودة إلى الرئيسية';
    
    // Apply translations to all elements with data-i18n attribute
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (t[key]) el.textContent = t[key];
    });
    
    // Apply translations to placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (t[key]) el.placeholder = t[key];
    });
    
    // Update option elements in selects
    document.querySelectorAll('option[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (t[key]) el.textContent = t[key];
    });
  }
  
  async function loadVehicles() {
    try {
      const r = await fetchJson('/vehicle_management/api/vehicle/list.php?page=1&per_page=1000', { method: 'GET' });
      if (r.ok && r.json && r.json.success && r.json.vehicles) {
        const datalist = document.getElementById('vehicleList');
        if (datalist) {
          datalist.innerHTML = '';
          r.json.vehicles.forEach(v => {
            const option = document.createElement('option');
            option.value = v.vehicle_code;
            option.textContent = `${v.vehicle_code} - ${v.driver_name || 'N/A'}`;
            datalist.appendChild(option);
          });
        }
      }
    } catch (e) {
      console.error('Failed to load vehicles:', e);
    }
  }
  
  async function fetchJson(url, opts = {}) {
    opts = Object.assign({}, opts);
    opts.credentials = 'include';
    if (!opts.headers) opts.headers = {};
    opts.headers['Accept'] = 'application/json';
    opts.headers['X-Requested-With'] = 'XMLHttpRequest';
    const sid = globalSessionId || getCookie('PHPSESSID') || getCookie('phpsessid');
    if (sid) opts.headers['X-Session-Id'] = sid;
    try {
      const res = await fetch(url, opts);
      const text = await res.text().catch(()=>null);
      let json = null;
      try { if (text) json = JSON.parse(text); } catch(e){ json = null; }
      return { ok: res.ok, status: res.status, json, text, headers: res.headers };
    } catch (e) {
      return { ok: false, status: 0, json: null, text: null, error: e };
    }
  }
  
  async function initSessionOnServer() {
    try {
      const initRes = await fetchJson(SESSION_INIT, { method: 'GET' });
      if (initRes.ok && initRes.json && initRes.json.session_id) {
        globalSessionId = initRes.json.session_id;
      }
    } catch (e) {
    }
  }
  
  async function sessionCheck() {
    const r = await fetchJson(API_SESSION, { method: 'GET' });
    if (!r.ok || !r.json || !r.json.success) {
      showMsg('Not authenticated — سجل الدخول أولاً ثم أعد المحاولة.', 'error');
      submitBtn.disabled = true;
      return null;
    }
    clearMsg();
    if (loggedUserEl) loggedUserEl.textContent = `${r.json.user.username || ''} (ID: ${r.json.user.emp_id || ''})`;
    if (orgNameEl) orgNameEl.textContent = r.json.user.orgName || 'HCS Department';
    submitBtn.disabled = false;
    globalSessionId = r.json.session_id || globalSessionId;
    return r.json;
  }
  
  async function loadPermissions() {
    try {
      const r = await fetchJson(API_PERMISSIONS, { method: 'GET' });
      if (r.ok && r.json && r.json.success && r.json.role) {
        currentPermissions = r.json.role;
        return currentPermissions;
      }
    } catch (e) {
      console.error('Failed to load permissions:', e);
    }
    return { can_create: false, can_edit: false, can_delete: false };
  }
  
  async function loadMaintenance(page = 1) {
    const q = searchInput.value.trim();
    const type = typeFilter.value;
    currentPage = page;
    
    const params = new URLSearchParams();
    params.append('action', 'list');
    if (q) params.append('q', q);
    if (type) params.append('type', type);
    params.append('page', String(page));
    params.append('per_page', String(perPage));
    
    const r = await fetchJson(API_MAINTENANCE + '?' + params.toString(), { method: 'GET' });
    
    if (!r.ok || !r.json || !r.json.success) {
      showMsg('فشل تحميل سجلات الصيانة', 'error');
      return;
    }
    
    const data = r.json;
    totalCount.textContent = String(data.total || 0);
    resultsSection.style.display = 'block';
    
    renderMaintenanceTable(data.records || []);
    renderPagination(data.total, data.page, data.per_page);
  }
  
  function renderMaintenanceTable(records) {
    maintenanceTableBody.innerHTML = '';
    
    if (records.length === 0) {
      const noResultsMsg = translations.no_results || 'لا توجد نتائج';
      maintenanceTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--muted)">${noResultsMsg}</td></tr>`;
      return;
    }
    
    records.forEach(m => {
      const tr = document.createElement('tr');
      
      // Type badge with translation
      const typeClass = `type-${(m.maintenance_type || 'Other').replace(/\s+/g, '')}`;
      let typeText = m.maintenance_type || 'Other';
      // Translate type if available
      const typeKey = `type_${(m.maintenance_type || 'other').toLowerCase().replace(/\s+/g, '_').replace('check', 'technical').replace('repair', 'mechanical')}`;
      if (translations[typeKey]) typeText = translations[typeKey];
      
      const editBtnText = translations.btn_edit || '✏️ تعديل';
      const deleteBtnText = translations.btn_delete || '🗑️ حذف';
      
      tr.innerHTML = `
        <td>${m.id}</td>
        <td>${m.vehicle_code || '-'}</td>
        <td>${m.visit_date || '-'}</td>
        <td>${m.next_visit_date || '-'}</td>
        <td><span class="type-badge ${typeClass}">${typeText}</span></td>
        <td>${m.location || '-'}</td>
        <td class="action-buttons">
          ${currentPermissions && currentPermissions.can_edit ? 
            `<button class="btn small ghost" data-action="edit" data-id="${m.id}">${editBtnText}</button>` : ''}
          ${currentPermissions && currentPermissions.can_delete ? 
            `<button class="btn small danger" data-action="delete" data-id="${m.id}">${deleteBtnText}</button>` : ''}
        </td>
      `;
      
      maintenanceTableBody.appendChild(tr);
    });
    
    // Attach event listeners to action buttons
    document.querySelectorAll('[data-action="edit"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = e.target.getAttribute('data-id');
        editMaintenance(id);
      });
    });
    
    document.querySelectorAll('[data-action="delete"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = e.target.getAttribute('data-id');
        deleteMaintenance(id);
      });
    });
  }
  
  function renderPagination(total, page, per_page) {
    const totalPages = Math.ceil(total / per_page);
    pagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'page-btn';
    prevBtn.textContent = '«';
    prevBtn.disabled = page <= 1;
    prevBtn.addEventListener('click', () => loadMaintenance(page - 1));
    pagination.appendChild(prevBtn);
    
    // Page numbers (show max 5 pages around current)
    const start = Math.max(1, page - 2);
    const end = Math.min(totalPages, page + 2);
    
    for (let i = start; i <= end; i++) {
      const pageBtn = document.createElement('button');
      pageBtn.className = 'page-btn' + (i === page ? ' active' : '');
      pageBtn.textContent = String(i);
      pageBtn.addEventListener('click', () => loadMaintenance(i));
      pagination.appendChild(pageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'page-btn';
    nextBtn.textContent = '»';
    nextBtn.disabled = page >= totalPages;
    nextBtn.addEventListener('click', () => loadMaintenance(page + 1));
    pagination.appendChild(nextBtn);
    
    // Page info
    const info = document.createElement('span');
    info.className = 'page-info';
    info.textContent = `صفحة ${page} من ${totalPages}`;
    pagination.appendChild(info);
  }
  
  async function editMaintenance(id) {
    formSection.style.display = 'block';
    formTitle.textContent = 'تعديل سجل الصيانة';
    form.scrollIntoView({ behavior: 'smooth' });
    
    const params = new URLSearchParams();
    params.append('action', 'get');
    params.append('id', id);
    
    const r = await fetchJson(API_MAINTENANCE + '?' + params.toString(), { method: 'GET' });
    if (r.ok && r.json && r.json.success && r.json.record) {
      const m = r.json.record;
      document.getElementById('vehicle_code').value = m.vehicle_code || '';
      document.getElementById('maintenance_type').value = m.maintenance_type || '';
      document.getElementById('visit_date').value = m.visit_date || '';
      document.getElementById('next_visit_date').value = m.next_visit_date || '';
      document.getElementById('location').value = m.location || '';
      document.getElementById('notes').value = m.notes || '';
      
      let hid = form.querySelector('input[name="id"]');
      if (!hid) {
        hid = document.createElement('input');
        hid.type = 'hidden';
        hid.name = 'id';
        form.appendChild(hid);
      }
      hid.value = m.id;
    } else {
      showMsg(translations.msg_failed || 'فشل تحميل بيانات الصيانة', 'error');
    }
  }
  
  async function deleteMaintenance(id) {
    const confirmMsg = translations.msg_delete_confirm || 'هل أنت متأكد من حذف سجل الصيانة هذا؟';
    if (!confirm(confirmMsg)) return;
    
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    
    const r = await fetchJson(API_MAINTENANCE, { method: 'POST', body: fd });
    
    if (r.ok && r.json && r.json.success) {
      showMsg(r.json.message || translations.msg_deleted || 'تم الحذف بنجاح', 'success');
      await loadMaintenance(currentPage);
    } else {
      showMsg((r.json && r.json.message) || 'فشل الحذف', 'error');
    }
  }
  
  async function init() {
    await initSessionOnServer();
    submitBtn.disabled = true;
    clearMsg();
    const sess = await sessionCheck();
    if (!sess) return;
    
    currentSession = sess;
    currentPermissions = await loadPermissions();
    
    // Set language and direction based on user preference
    currentLang = (sess.user && sess.user.preferred_language) ? sess.user.preferred_language.toLowerCase() : 'ar';
    const htmlEl = document.documentElement;
    htmlEl.lang = currentLang;
    htmlEl.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    
    // Load translations
    await loadTranslations(currentLang);
    
    // Event listeners for search and add
    searchBtn.addEventListener('click', () => loadMaintenance(1));
    searchInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') loadMaintenance(1); });
    typeFilter.addEventListener('change', () => loadMaintenance(1));
    
    addNewBtn.addEventListener('click', () => {
      formSection.style.display = 'block';
      formTitle.textContent = translations.form_title_add || 'إضافة سجل صيانة جديد';
      form.reset();
      const hid = form.querySelector('input[name="id"]');
      if (hid) hid.remove();
      formSection.scrollIntoView({ behavior: 'smooth' });
    });
    
    // Load vehicle list for autocomplete
    await loadVehicles();
    
    // Load records initially
    await loadMaintenance(1);
    
    submitBtn.disabled = false;
    form.addEventListener('submit', async function(ev){
      ev.preventDefault();
      clearMsg();
      submitBtn.disabled = true;
      showMsg(translations.msg_saving || 'جاري الحفظ...', 'info');
      try {
        const fd = new FormData(form);
        const isUpdate = fd.has('id') && fd.get('id');
        fd.append('action', isUpdate ? 'update' : 'create');
        if (!fd.get('created_by')) fd.append('created_by', sess.user.emp_id || sess.user.username || '');
        
        const postRes = await fetchJson(API_MAINTENANCE, { method: 'POST', body: fd });
        if (postRes.ok && postRes.json && postRes.json.success) {
          showMsg(postRes.json.message || translations.msg_saved || 'تم الحفظ', 'success');
          await loadMaintenance(currentPage);
          if (!isUpdate) {
            form.reset();
            formSection.style.display = 'none';
          }
        } else {
          const body = postRes.json || {};
          // Show appropriate message based on language and error code
          let errorMsg = body.message || translations.msg_failed || 'فشل الحفظ';
          if (body.error_code === 'VEHICLE_NOT_FOUND') {
            errorMsg = currentLang === 'en' ? (body.message_en || 'Please register the vehicle first') : (body.message || 'الرجاء تسجيل المركبة أولاً');
          }
          showMsg(errorMsg, 'error');
        }
      } catch (e) {
        showMsg('خطأ في الاتصال', 'error');
      } finally { submitBtn.disabled = false; }
    });
    
    cancelBtn && cancelBtn.addEventListener('click', function(){
      formSection.style.display = 'none';
      form.reset();
      const hid = form.querySelector('input[name="id"]');
      if (hid) hid.remove();
    });
  }
  
  document.addEventListener('DOMContentLoaded', init);
})();
