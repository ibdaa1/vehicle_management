// /vehicle_management/assets/js/add_Vehicles.js
(function () {
  'use strict';
  const API_HELPER = '/vehicle_management/api/helper/get_references.php';
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
  const API_VEHICLE_ADD = '/vehicle_management/api/vehicle/add_Vehicles.php';
  const API_VEHICLE_GET = '/vehicle_management/api/vehicle/get.php';
  const API_VEHICLE_LIST = '/vehicle_management/api/vehicle/list.php';
  const API_VEHICLE_DELETE = '/vehicle_management/api/vehicle/delete.php';
  const API_PERMISSIONS = '/vehicle_management/api/permissions/get_permissions.php';
  const SESSION_INIT = '/vehicle_management/api/config/session.php?init=1';
  
  const form = document.getElementById('vehicleForm');
  const deptSel = document.getElementById('department_id');
  const sectionSel = document.getElementById('section_id');
  const divisionSel = document.getElementById('division_id');
  const vmSel = document.getElementById('vehicle_mode');
  const statusSel = document.getElementById('status');
  const empInput = document.getElementById('emp_id');
  const submitBtn = document.getElementById('submitBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const msgEl = document.getElementById('msg');
  const loggedUserEl = document.getElementById('loggedUser');
  const orgNameEl = document.getElementById('orgName');
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const searchBtn = document.getElementById('searchBtn');
  const addNewBtn = document.getElementById('addNewBtn');
  const resultsSection = document.getElementById('resultsSection');
  const formSection = document.getElementById('formSection');
  const vehiclesTableBody = document.getElementById('vehiclesTableBody');
  const totalCount = document.getElementById('totalCount');
  const pagination = document.getElementById('pagination');
  const formTitle = document.getElementById('formTitle');
  
  const DEFAULT_DEPARTMENT = '1';
  const DEFAULT_SECTION = '1';
  const DEFAULT_DIVISION = '1';
  let globalSessionId = null;
  let currentSession = null;
  let currentPermissions = null;
  let currentPage = 1;
  let perPage = 30;
  
  function showMsg(text, type='info'){
    if (!msgEl) return;
    const color = type==='error' ? '#8b1e1e' : (type==='success' ? '#065f46' : '#6b7280');
    msgEl.innerHTML = `<div style="color:${color}">${text}</div>`;
  }
  function appendMsgHtml(html){ if (!msgEl) return; msgEl.innerHTML += html; }
  function clearMsg(){ if (msgEl) msgEl.innerHTML = ''; }
  
  function getCookie(name) {
    const re = new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[]\/+^])/g, '\\$1') + '=([^;]*)');
    const m = document.cookie.match(re);
    const val = m ? decodeURIComponent(m[1]) : null;
    return val;
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
      appendMsgHtml(`<div style="margin-top:8px"><button id="openLoginBtn" class="btn ghost">فتح صفحة الدخول</button></div>`);
      const b = document.getElementById('openLoginBtn'); 
      if (b) b.addEventListener('click', ()=> window.location.href = '/vehicle_management/public/login.html');
      submitBtn.disabled = true;
      return null;
    }
    clearMsg();
    if (loggedUserEl) loggedUserEl.textContent = `${r.json.user.username || ''} (ID: ${r.json.user.emp_id || ''})`;
    if (orgNameEl) orgNameEl.textContent = r.json.user.orgName || 'HCS Department';
    if (empInput) empInput.value = r.json.user.emp_id || '';
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
  
  async function loadVehicles(page = 1) {
    const q = searchInput.value.trim();
    const status = statusFilter.value;
    currentPage = page;
    
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (status) params.append('status', status);
    params.append('page', String(page));
    params.append('per_page', String(perPage));
    
    const r = await fetchJson(API_VEHICLE_LIST + '?' + params.toString(), { method: 'GET' });
    
    if (!r.ok || !r.json || !r.json.success) {
      showMsg('فشل تحميل المركبات', 'error');
      return;
    }
    
    const data = r.json;
    totalCount.textContent = String(data.total || 0);
    resultsSection.style.display = 'block';
    
    renderVehiclesTable(data.vehicles || []);
    renderPagination(data.total, data.page, data.per_page);
  }
  
  function renderVehiclesTable(vehicles) {
    vehiclesTableBody.innerHTML = '';
    
    if (vehicles.length === 0) {
      vehiclesTableBody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:var(--muted)">لا توجد نتائج</td></tr>';
      return;
    }
    
    vehicles.forEach(v => {
      const tr = document.createElement('tr');
      
      // Status badge
      const statusClass = `status-${v.status || 'operational'}`;
      const statusText = v.status === 'operational' ? 'قيد التشغيل' : 
                         v.status === 'maintenance' ? 'صيانة' : 'خارج الخدمة';
      
      tr.innerHTML = `
        <td>${v.id}</td>
        <td>${v.vehicle_code || '-'}</td>
        <td>${v.driver_name || '-'}</td>
        <td>${v.driver_phone || '-'}</td>
        <td>${v.emp_id || '-'}</td>
        <td>${v.department_name || 'غير محدد'}</td>
        <td>${v.section_name || 'غير محدد'}</td>
        <td>${v.division_name || 'غير محدد'}</td>
        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
        <td class="action-buttons">
          ${currentPermissions && currentPermissions.can_edit ? 
            `<button class="btn small ghost" data-action="edit" data-id="${v.id}">تعديل</button>` : ''}
          ${currentPermissions && currentPermissions.can_delete ? 
            `<button class="btn small danger" data-action="delete" data-id="${v.id}">حذف</button>` : ''}
        </td>
      `;
      
      vehiclesTableBody.appendChild(tr);
    });
    
    // Attach event listeners to action buttons
    document.querySelectorAll('[data-action="edit"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = e.target.getAttribute('data-id');
        editVehicle(id);
      });
    });
    
    document.querySelectorAll('[data-action="delete"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = e.target.getAttribute('data-id');
        deleteVehicle(id);
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
    prevBtn.addEventListener('click', () => loadVehicles(page - 1));
    pagination.appendChild(prevBtn);
    
    // Page numbers (show max 5 pages around current)
    const start = Math.max(1, page - 2);
    const end = Math.min(totalPages, page + 2);
    
    for (let i = start; i <= end; i++) {
      const pageBtn = document.createElement('button');
      pageBtn.className = 'page-btn' + (i === page ? ' active' : '');
      pageBtn.textContent = String(i);
      pageBtn.addEventListener('click', () => loadVehicles(i));
      pagination.appendChild(pageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'page-btn';
    nextBtn.textContent = '»';
    nextBtn.disabled = page >= totalPages;
    nextBtn.addEventListener('click', () => loadVehicles(page + 1));
    pagination.appendChild(nextBtn);
    
    // Page info
    const info = document.createElement('span');
    info.className = 'page-info';
    info.textContent = `صفحة ${page} من ${totalPages}`;
    pagination.appendChild(info);
  }
  
  async function editVehicle(id) {
    // Load vehicle data and populate form
    formSection.style.display = 'block';
    formTitle.textContent = 'تعديل المركبة';
    form.scrollIntoView({ behavior: 'smooth' });
    
    const rv = await fetchJson(API_VEHICLE_GET + '?id=' + encodeURIComponent(id));
    if (rv.ok && rv.json && rv.json.success && rv.json.vehicle) {
      const v = rv.json.vehicle;
      if (v.vehicle_code) document.getElementById('plate_number').value = v.vehicle_code;
      if (v.type) document.getElementById('type').value = v.type;
      if (v.manufacture_year) document.getElementById('year').value = v.manufacture_year;
      if (v.driver_name) document.getElementById('driver_name').value = v.driver_name;
      if (v.driver_phone) document.getElementById('driver_phone').value = v.driver_phone;
      if (v.notes) document.getElementById('notes').value = v.notes;
      if (v.status) statusSel.value = v.status;
      if (v.vehicle_mode) vmSel.value = v.vehicle_mode;
      
      // Update cascading selects
      const lang = currentSession && currentSession.user ? currentSession.user.preferred_language : 'ar';
      const refs = await loadReferences(lang);
      
      if (v.department_id) {
        setPreselected(deptSel, String(v.department_id));
        populateSelectSingleLanguage(sectionSel, refs.sections.filter(s => String(s.department_id ?? '') === String(v.department_id)), lang);
      }
      if (v.section_id) {
        setPreselected(sectionSel, String(v.section_id));
        populateSelectSingleLanguage(divisionSel, refs.divisions.filter(d => String(d.section_id ?? '') === String(v.section_id)), lang);
      }
      if (v.division_id) {
        setPreselected(divisionSel, String(v.division_id));
      }
      
      let hid = form.querySelector('input[name="id"]'); 
      if (!hid) { 
        hid = document.createElement('input'); 
        hid.type='hidden'; 
        hid.name='id'; 
        form.appendChild(hid); 
      } 
      hid.value = v.id;
    } else {
      showMsg('فشل تحميل بيانات المركبة', 'error');
    }
  }
  
  async function deleteVehicle(id) {
    if (!confirm('هل أنت متأكد من حذف هذه المركبة؟')) return;
    
    const fd = new FormData();
    fd.append('id', id);
    
    const r = await fetchJson(API_VEHICLE_DELETE, { method: 'POST', body: fd });
    
    if (r.ok && r.json && r.json.success) {
      showMsg(r.json.message || 'تم الحذف بنجاح', 'success');
      // Reload the list
      await loadVehicles(currentPage);
    } else {
      showMsg((r.json && r.json.message) || 'فشل الحذف', 'error');
    }
  }
  
  async function loadReferences(lang) {
    const res = await fetchJson(API_HELPER + '?lang=' + encodeURIComponent(lang), { method: 'GET' });
    let deps = [], secs = [], divs = [];
    if (res.ok && res.json) {
      deps = res.json.departments || res.json.items || res.json.data || [];
      secs = res.json.sections || res.json.items || res.json.data || [];
      divs = res.json.divisions || res.json.items || res.json.data || [];
    }
    if (!Array.isArray(deps) || deps.length === 0) {
      const rd = await fetchJson(API_HELPER + '?type=departments&lang=' + encodeURIComponent(lang));
      deps = (rd.json && (rd.json.departments || rd.json.items || rd.json.data)) || [];
    }
    if (!Array.isArray(secs) || secs.length === 0) {
      const rs = await fetchJson(API_HELPER + '?type=sections&lang=' + encodeURIComponent(lang));
      secs = (rs.json && (rs.json.sections || rs.json.items || rs.json.data)) || [];
    }
    if (!Array.isArray(divs) || divs.length === 0) {
      const rv = await fetchJson(API_HELPER + '?type=divisions&lang=' + encodeURIComponent(lang));
      divs = (rv.json && (rv.json.divisions || rv.json.items || rv.json.data)) || [];
    }
    clearMsg();
    return { departments: deps, sections: secs, divisions: divs };
  }
  function populateSelectSingleLanguage(sel, items, lang, placeholder) {
    if (!sel) return;
    const prev = sel.value;
    sel.innerHTML = '';
    const op0 = document.createElement('option'); op0.value = ''; op0.textContent = placeholder || (lang === 'en' ? 'Select' : 'اختر'); sel.appendChild(op0);
    (items || []).forEach(it => {
      const id = String(it.id ?? it.department_id ?? it.section_id ?? it.division_id ?? it.value ?? '');
      const label = (lang === 'en') ? (it.name_en || it.name || it.name_ar) : (it.name_ar || it.name || it.name_en);
      const o = document.createElement('option'); o.value = id; o.textContent = label || id; sel.appendChild(o);
    });
    if (prev) { const f = sel.querySelector(`option[value="${prev}"]`); if (f) sel.value = prev; }
  }
  function setPreselected(selectEl, value) {
    if (!selectEl) return;
    if (value) {
      const opt = selectEl.querySelector(`option[value="${value}"]`);
      if (opt) selectEl.value = value;
      return;
    }
    const ds = selectEl.getAttribute('data-selected');
    if (ds) selectEl.value = ds;
  }
  function buildFormData(session) {
    const fd = new FormData(form);
    if (fd.has('plate_number')) { const v = fd.get('plate_number'); fd.delete('plate_number'); fd.append('vehicle_code', v); }
    if (fd.has('year')) { const v = fd.get('year'); fd.delete('year'); fd.append('manufacture_year', v); }
    if (!fd.get('emp_id') || fd.get('emp_id') === '') fd.set('emp_id', session.user.emp_id || '');
    if (!fd.get('department_id') || fd.get('department_id') === '') fd.set('department_id', DEFAULT_DEPARTMENT);
    if (!fd.get('section_id') || fd.get('section_id') === '') fd.set('section_id', DEFAULT_SECTION);
    if (!fd.get('division_id') || fd.get('division_id') === '') fd.set('division_id', DEFAULT_DIVISION);
    if (!fd.get('status')) fd.set('status', 'operational');
    if (!fd.get('vehicle_mode')) fd.set('vehicle_mode', 'shift');
    return fd;
  }
  async function init() {
    await initSessionOnServer();
    submitBtn.disabled = true;
    clearMsg();
    const sess = await sessionCheck();
    if (!sess) return;
    
    currentSession = sess;
    currentPermissions = await loadPermissions();
    
    const lang = (sess.user && sess.user.preferred_language) ? sess.user.preferred_language.toLowerCase() : (document.documentElement.lang || 'ar');
    try { document.documentElement.lang = lang; } catch(e){ }
    const refs = await loadReferences(lang);
    populateSelectSingleLanguage(deptSel, refs.departments, lang, (lang==='en'?'Select department':'اختر الإدارة'));
    const preDep = form.getAttribute('data-department-id') || (sess.user && sess.user.department_id) || DEFAULT_DEPARTMENT;
    setPreselected(deptSel, String(preDep));
    populateSelectSingleLanguage(sectionSel, refs.sections.filter(s => String(s.department_id ?? '') === String(preDep)), lang, (lang==='en'?'Select section':'اختر القسم'));
    const preSec = form.getAttribute('data-section-id') || (sess.user && sess.user.section_id) || '';
    setPreselected(sectionSel, String(preSec));
    const sectionToUse = preSec || (sectionSel.options.length > 1 ? sectionSel.options[1].value : '');
    populateSelectSingleLanguage(divisionSel, refs.divisions.filter(d => String(d.section_id ?? '') === String(sectionToUse)), lang, (lang==='en'?'Select division':'اختر الشعبة'));
    const preDiv = form.getAttribute('data-division-id') || (sess.user && sess.user.division_id) || DEFAULT_DIVISION;
    setPreselected(divisionSel, String(preDiv));
    deptSel.addEventListener('change', function(){ const dep = this.value || ''; populateSelectSingleLanguage(sectionSel, refs.sections.filter(s => String(s.department_id ?? '') === String(dep)), lang); divisionSel.innerHTML=''; const o=document.createElement('option'); o.value=''; o.textContent=(lang==='en'?'Select division':'اختر الشعبة'); divisionSel.appendChild(o); });
    sectionSel.addEventListener('change', function(){ const sec=this.value||''; populateSelectSingleLanguage(divisionSel, refs.divisions.filter(d => String(d.section_id ?? '') === String(sec)), lang); });
    if (vmSel) {
      vmSel.innerHTML = '';
      const vm_opts = { shift: (lang === 'en' ? 'Shift' : 'خدمة/وردية'), private: (lang === 'en' ? 'Private' : 'خاصة') };
      ['shift','private'].forEach(k => { const o=document.createElement('option'); o.value=k; o.textContent = vm_opts[k] || k; vmSel.appendChild(o); });
    }
    if (statusSel) {
      statusSel.innerHTML = '';
      const st_opts = { operational:(lang==='en'?'Operational':'قيد التشغيل'), maintenance:(lang==='en'?'Maintenance':'صيانة'), out_of_service:(lang==='en'?'Out of service':'خارج الخدمة') };
      ['operational','maintenance','out_of_service'].forEach(k => { const o=document.createElement('option'); o.value=k; o.textContent = st_opts[k] || k; statusSel.appendChild(o); });
    }
    
    // Event listeners for search and add
    searchBtn.addEventListener('click', () => loadVehicles(1));
    searchInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') loadVehicles(1); });
    statusFilter.addEventListener('change', () => loadVehicles(1));
    addNewBtn.addEventListener('click', () => {
      formSection.style.display = 'block';
      formTitle.textContent = 'إضافة مركبة جديدة';
      form.reset();
      // Remove hidden id field if exists
      const hid = form.querySelector('input[name="id"]');
      if (hid) hid.remove();
      formSection.scrollIntoView({ behavior: 'smooth' });
    });
    
    // Load vehicles initially
    await loadVehicles(1);
    
    // Check if editing from URL parameter
    const vid = new URL(location.href).searchParams.get('id');
    if (vid) {
      formSection.style.display = 'block';
      await editVehicle(vid);
    }
    
    submitBtn.disabled = false;
    form.addEventListener('submit', async function(ev){
      ev.preventDefault();
      clearMsg();
      submitBtn.disabled = true;
      showMsg('جاري الحفظ...', 'info');
      try {
        const fd = buildFormData(sess);
        const postRes = await fetchJson(API_VEHICLE_ADD, { method: 'POST', body: fd });
        if (postRes.ok && postRes.json && postRes.json.success) {
          showMsg(postRes.json.message || 'تم الحفظ', 'success');
          // Reload the list after save
          await loadVehicles(currentPage);
          // Reset form if adding new
          if (!fd.get('id')) {
            form.reset();
            formSection.style.display = 'none';
          }
        } else {
          const body = postRes.json || {};
          if (body && body.message && /not authenticated/i.test(String(body.message))) {
            showMsg('Not authenticated — سجل الدخول أولاً ثم أعد المحاولة.', 'error');
            appendMsgHtml(`<div style="margin-top:8px"><button id="openLoginBtn2" class="btn ghost">فتح صفحة الدخول</button></div>`);
            const b2 = document.getElementById('openLoginBtn2'); if (b2) b2.addEventListener('click', ()=> window.location.href = '/vehicle_management/public/login.html');
          } else {
            showMsg((body && body.message) ? body.message : 'فشل الحفظ', 'error');
          }
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