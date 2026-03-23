// /vehicle_management/assets/js/add_Vehicles.js
(function () {
  'use strict';
  const API_HELPER = '/vehicle_management/api/v1/references';
  const API_SESSION = '/vehicle_management/api/v1/auth/check';
  const API_VEHICLE_ADD = '/vehicle_management/api/v1/vehicles';
  const API_VEHICLE_GET = '/vehicle_management/api/v1/vehicles';
  const API_VEHICLE_LIST = '/vehicle_management/api/v1/vehicles';
  const API_VEHICLE_DELETE = '/vehicle_management/api/v1/vehicles';
  const API_PERMISSIONS = '/vehicle_management/api/v1/permissions/my';
  const SESSION_INIT = '/vehicle_management/api/v1/auth/check';
  const REPORT_PATH = '/vehicle_management/public/report_add_Vehicles.html'; // مسار التقرير
  const form = document.getElementById('vehicleForm');
  const deptSel = document.getElementById('department_id');
  const sectionSel = document.getElementById('section_id');
  const divisionSel = document.getElementById('division_id');
  const sectorSel = document.getElementById('sector_id');
  const vmSel = document.getElementById('vehicle_mode');
  const statusSel = document.getElementById('status');
  const empInput = document.getElementById('emp_id');
  const submitBtn = document.getElementById('submitBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const msgEl = document.getElementById('msg');
  const loggedUserEl = document.getElementById('loggedUser');
  const orgNameEl = document.getElementById('orgName');
  const searchInput = document.getElementById('searchInput');
  const sectorFilter = document.getElementById('sectorFilter');
  const deptFilter = document.getElementById('deptFilter');
  const sectionFilter = document.getElementById('sectionFilter');
  const statusFilter = document.getElementById('statusFilter');
  const searchBtn = document.getElementById('searchBtn');
  const addNewBtn = document.getElementById('addNewBtn');
  const reportBtn = document.getElementById('reportBtn');
  const resultsSection = document.getElementById('resultsSection');
  const formSection = document.getElementById('formSection');
  const vehiclesTableBody = document.getElementById('vehiclesTableBody');
  const totalCount = document.getElementById('totalCount');
  const pagination = document.getElementById('pagination');
  const formTitle = document.getElementById('formTitle');
  const yearInput = document.getElementById('year');
  const DEFAULT_DEPARTMENT = '1';
  const DEFAULT_SECTION = '1';
  const DEFAULT_DIVISION = '1';
  let globalSessionId = null;
  let currentSession = null;
  let currentPermissions = null;
  let currentReferences = null;
  let currentPage = 1;
  let perPage = 30;
  let currentLang = 'ar';
  // كائن الترجمة المحدث
  const translations = {
    ar: {
      title: "تسجيل مركبة جديدة",
      subtitle: "أدخل بيانات المركبة. الاختيارات مرتبطة: الإدارة → القسم → الشعبة",
      pageTitle: 'إدارة المركبات',
      pageSubtitle: 'بحث، إضافة، تعديل، وحذف المركبات',
      orgName: "HCS Department",
      searchPlaceholder: '🔍 بحث: رقم المركبة، الرمز الوظيفي، اسم السائق، هاتف السائق...',
      deptFilterPlaceholder: 'كل الإدارات',
      sectorFilterPlaceholder: 'كل القطاعات',
      sectionFilterPlaceholder: 'كل الأقسام',
      statusFilterPlaceholder: 'كل الحالات',
      searchBtnTitle: 'بحث',
      addNewBtnTitle: 'إضافة مركبة',
      reportBtnTitle: 'تقرير',
      resultsHeader: 'نتائج البحث',
      thId: 'ID',
      thVehicleCode: 'رقم المركبة',
      thDriver: 'السائق',
      thDriverPhone: 'هاتف السائق',
      thEmpId: 'الرمز الوظيفي',
      thSector: 'القطاع',
      thDepartment: 'الإدارة',
      thSection: 'القسم',
      thDivision: 'الشعبة',
      thStatus: 'الحالة',
      thActions: 'الإجراءات',
      formTitle: 'نموذج المركبة',
      label_plate: "رقم المركبة (vehicle_code)",
      platePlaceholder: 'مثال: 12345',
      label_type: "النوع (type)",
      typePlaceholder: 'مثال: شاحنة',
      label_year: "سنة الصنع (manufacture_year)",
      label_mode: "نمط المركبة",
      vehicleModeSelect: 'اختر النمط',
      vehicleModeShift: "خدمة/وردية",
      vehicleModePrivate: "خاصة",
      label_emp: "الرمز الوظيفي لسائق المركبة (emp_id)",
      empPlaceholder: 'أدخل رمز الوظيفي لسائق المركبة',
      label_driver: "اسم السائق",
      driverNamePlaceholder: 'اسم السائق',
      label_driver_phone: "هاتف السائق",
      driverPhonePlaceholder: 'هاتف السائق',
      deptSelPlaceholder: 'اختر الإدارة',
      sectorSelPlaceholder: 'اختر القطاع',
      sectionSelPlaceholder: 'اختر القسم',
      divisionSelPlaceholder: 'اختر الشعبة',
      statusSelPlaceholder: 'اختر الحالة',
      label_notes: "ملاحظات",
      notesPlaceholder: 'أي ملاحظات إضافية...',
      submit: "حفظ المركبة",
      cancel: "إلغاء",
      msg_no_auth: "غير مسموح — الرجاء تسجيل الدخول",
      msg_no_perm: "لا تملك صلاحية إضافة مركبات",
      msg_saving: "جاري الحفظ...",
      msg_saved: "تم حفظ المركبة بنجاح",
      msg_failed: "فشل الحفظ",
      msg_loading: "جاري تحميل بيانات المركبة...",
      msg_emp_required: "الرمز الوظيفي لسائق المركبة مطلوب",
      msg_year_invalid: "سنة الصنع يجب أن تكون بين 1900 و 2099",
      statusOperational: "✅ قيد التشغيل",
      statusMaintenance: "🔧 صيانة",
      statusOutOfService: "❌ خارج الخدمة",
      statusOperationalSel: "قيد التشغيل",
      statusMaintenanceSel: "صيانة",
      statusOutOfServiceSel: "خارج الخدمة",
      noResults: 'لا توجد نتائج',
      pageInfo: (page, totalPages) => `صفحة ${page} من ${totalPages}`,
      prevPage: '«',
      nextPage: '»',
      editTitle: 'تعديل',
      deleteTitle: 'حذف',
      addNewTitle: 'إضافة مركبة جديدة',
      editVehicleTitle: 'تعديل المركبة',
      notSpecified: 'غير محدد'
    },
    en: {
      title: "New Vehicle Registration",
      subtitle: "Enter vehicle details. Selections are linked: Department → Section → Division",
      pageTitle: 'Vehicle Management',
      pageSubtitle: 'Search, Add, Edit, and Delete Vehicles',
      orgName: "HCS Department",
      searchPlaceholder: '🔍 Search: Vehicle Number, Employee ID, Driver Name, Driver Phone...',
      deptFilterPlaceholder: 'All Departments',
      sectorFilterPlaceholder: 'All Sectors',
      sectionFilterPlaceholder: 'All Sections',
      statusFilterPlaceholder: 'All Statuses',
      searchBtnTitle: 'Search',
      addNewBtnTitle: 'Add Vehicle',
      reportBtnTitle: 'Report',
      resultsHeader: 'Search Results',
      thId: 'ID',
      thVehicleCode: 'Vehicle Number',
      thDriver: 'Driver',
      thDriverPhone: 'Driver Phone',
      thEmpId: 'Employee ID',
      thSector: 'Sector',
      thDepartment: 'Department',
      thSection: 'Section',
      thDivision: 'Division',
      thStatus: 'Status',
      thActions: 'Actions',
      formTitle: 'Vehicle Form',
      label_plate: "Vehicle Number (vehicle_code)",
      platePlaceholder: 'Example: 12345',
      label_type: "Type (type)",
      typePlaceholder: 'Example: Truck',
      label_year: "Manufacture Year (manufacture_year)",
      label_mode: "Vehicle Mode",
      vehicleModeSelect: 'Select Mode',
      vehicleModeShift: "Shift Service",
      vehicleModePrivate: "Private",
      label_emp: "Vehicle Driver Employee ID (emp_id)",
      empPlaceholder: 'Enter vehicle driver employee ID',
      label_driver: "Driver Name",
      driverNamePlaceholder: 'Driver Name',
      label_driver_phone: "Driver Phone",
      driverPhonePlaceholder: 'Driver Phone',
      deptSelPlaceholder: 'Select Department',
      sectorSelPlaceholder: 'Select Sector',
      sectionSelPlaceholder: 'Select Section',
      divisionSelPlaceholder: 'Select Division',
      statusSelPlaceholder: 'Select Status',
      label_notes: "Notes",
      notesPlaceholder: 'Any additional notes...',
      submit: "Save Vehicle",
      cancel: "Cancel",
      msg_no_auth: "Not authorized — Please log in",
      msg_no_perm: "You do not have permission to add vehicles",
      msg_saving: "Saving...",
      msg_saved: "Vehicle saved successfully",
      msg_failed: "Save failed",
      msg_loading: "Loading vehicle data...",
      msg_emp_required: "Vehicle driver employee ID is required",
      msg_year_invalid: "Manufacture year must be between 1900 and 2099",
      statusOperational: "✅ Operational",
      statusMaintenance: "🔧 Maintenance",
      statusOutOfService: "❌ Out of Service",
      statusOperationalSel: "Operational",
      statusMaintenanceSel: "Maintenance",
      statusOutOfServiceSel: "Out of Service",
      noResults: 'No results found',
      pageInfo: (page, totalPages) => `Page ${page} of ${totalPages}`,
      prevPage: '«',
      nextPage: '»',
      editTitle: 'Edit',
      deleteTitle: 'Delete',
      addNewTitle: 'Add New Vehicle',
      editVehicleTitle: 'Edit Vehicle',
      notSpecified: 'Not specified'
    }
  };
  function applyTranslation(lang) {
    currentLang = lang.toLowerCase();
    const t = translations[currentLang] || translations.ar;
    // تحديث العنوان
    document.title = t.pageTitle;
    // تحديث العناصر بـ data-i18n
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (t[key]) el.textContent = t[key];
    });
    // تحديث placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (t[key]) el.placeholder = t[key];
    });
    // تحديث titles
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
      const key = el.getAttribute('data-i18n-title');
      if (t[key]) el.title = t[key];
    });
    // تحديث خيارات statusFilter كاملة بما في ذلك الخيار الأول
    if (statusFilter) {
      statusFilter.options[0].textContent = t.statusFilterPlaceholder;
      if (statusFilter.options[1]) statusFilter.options[1].textContent = t.statusOperational;
      if (statusFilter.options[2]) statusFilter.options[2].textContent = t.statusMaintenance;
      if (statusFilter.options[3]) statusFilter.options[3].textContent = t.statusOutOfService;
    }
    // تحديث خيارات deptFilter و sectionFilter و sectorFilter الأولى
    if (sectorFilter && sectorFilter.options[0]) sectorFilter.options[0].textContent = t.sectorFilterPlaceholder;
    if (deptFilter && deptFilter.options[0]) deptFilter.options[0].textContent = t.deptFilterPlaceholder;
    if (sectionFilter && sectionFilter.options[0]) sectionFilter.options[0].textContent = t.sectionFilterPlaceholder;
    // تحديث الاتجاه
    document.documentElement.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = currentLang;
  }
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
      showMsg(translations[currentLang].msg_no_auth || 'Not authenticated — سجل الدخول أولاً ثم أعد المحاولة.', 'error');
      appendMsgHtml(`<div style="margin-top:8px"><button id="openLoginBtn" class="btn ghost">${translations[currentLang].msg_no_auth || 'فتح صفحة الدخول'}</button></div>`);
      const b = document.getElementById('openLoginBtn');
      if (b) b.addEventListener('click', ()=> window.location.href = '/vehicle_management/public/login.html');
      submitBtn.disabled = true;
      return null;
    }
    clearMsg();
    if (loggedUserEl) loggedUserEl.textContent = `${r.json.user.username || ''} (ID: ${r.json.user.emp_id || ''})`;
    if (orgNameEl) orgNameEl.textContent = r.json.user.orgName || translations[currentLang].orgName;
    // Make emp_id editable
    if (empInput) {
      empInput.removeAttribute('readonly');
    }
    submitBtn.disabled = false;
    globalSessionId = r.json.session_id || globalSessionId;
    return r.json;
  }
  async function loadPermissions() {
    try {
      const r = await fetchJson(API_PERMISSIONS, { method: 'GET' });
      if (r.ok && r.json && r.json.success && r.json.role) {
        currentPermissions = r.json.role;
        if (!currentPermissions.can_create) {
          showMsg(translations[currentLang].msg_no_perm || 'لا تملك صلاحية إضافة مركبات', 'error');
        }
        return currentPermissions;
      }
    } catch (e) {
      console.error('Failed to load permissions:', e);
    }
    return { can_create: false, can_edit: false, can_delete: false };
  }
  async function loadVehicles(page = 1) {
    const q = searchInput.value.trim();
    const sector = sectorFilter.value;
    const dept = deptFilter.value;
    const section = sectionFilter.value;
    const status = statusFilter.value;
    currentPage = page;
 
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (sector) params.append('sector_id', sector);
    if (dept) params.append('department_id', dept);
    if (section) params.append('section_id', section);
    if (status) params.append('status', status);
    params.append('page', String(page));
    params.append('per_page', String(perPage));
 
    const r = await fetchJson(API_VEHICLE_LIST + '?' + params.toString(), { method: 'GET' });
 
    if (!r.ok || !r.json || !r.json.success) {
      showMsg(translations[currentLang].msg_failed || 'فشل تحميل المركبات', 'error');
      return;
    }
 
    const data = r.json;
    totalCount.textContent = String(data.total || 0);
    resultsSection.style.display = 'flex';
 
    const t = translations[currentLang];
    const nameField = currentLang === 'ar' ? 'name_ar' : 'name_en';
    const vehiclesWithNames = (data.vehicles || []).map(v => {
      const sectorName = currentReferences?.sectors?.find(sc => String(sc.id) === String(v.sector_id))?.name || v.sector_name || t.notSpecified;
      const deptName = currentReferences?.departments?.find(d => String(d.id) === String(v.department_id))?.[nameField] || v.department_name || t.notSpecified;
      const secName = currentReferences?.sections?.find(s => String(s.id) === String(v.section_id))?.[nameField] || v.section_name || t.notSpecified;
      const divName = currentReferences?.divisions?.find(d => String(d.id) === String(v.division_id))?.[nameField] || v.division_name || t.notSpecified;
      return { ...v, sector_name: sectorName, department_name: deptName, section_name: secName, division_name: divName };
    });
 
    renderVehiclesTable(vehiclesWithNames);
    renderPagination(data.total, data.page, data.per_page);
  }
  function renderVehiclesTable(vehicles) {
    const t = translations[currentLang];
    vehiclesTableBody.innerHTML = '';
 
    if (vehicles.length === 0) {
      vehiclesTableBody.innerHTML = `<tr><td colspan="11" style="text-align:center;color:var(--muted)">${t.noResults}</td></tr>`;
      return;
    }
 
    vehicles.forEach(v => {
      const tr = document.createElement('tr');
   
      const statusClass = `status-${v.status || 'operational'}`;
      const statusText = v.status === 'operational' ? t.statusOperational :
                         v.status === 'maintenance' ? t.statusMaintenance : t.statusOutOfService;
   
      tr.innerHTML = `
        <td data-label="${t.thId}">${v.id}</td>
        <td data-label="${t.thVehicleCode}">${v.vehicle_code || '-'}</td>
        <td data-label="${t.thDriver}">${v.driver_name || '-'}</td>
        <td data-label="${t.thDriverPhone}">${v.driver_phone || '-'}</td>
        <td data-label="${t.thEmpId}">${v.emp_id || '-'}</td>
        <td data-label="${t.thSector}">${v.sector_name || '-'}</td>
        <td data-label="${t.thDepartment}">${v.department_name}</td>
        <td data-label="${t.thSection}">${v.section_name}</td>
        <td data-label="${t.thDivision}">${v.division_name}</td>
        <td data-label="${t.thStatus}"><span class="status-badge ${statusClass}">${statusText}</span></td>
        <td data-label="${t.thActions}" class="action-buttons">
          ${currentPermissions && currentPermissions.can_edit ?
            `<button class="btn small ghost icon-only" data-action="edit" data-id="${v.id}" title="${t.editTitle}">✏️</button>` : ''}
          ${currentPermissions && currentPermissions.can_delete ?
            `<button class="btn small danger icon-only" data-action="delete" data-id="${v.id}" title="${t.deleteTitle}">🗑️</button>` : ''}
        </td>
      `;
   
      vehiclesTableBody.appendChild(tr);
    });
 
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
    const t = translations[currentLang];
    const totalPages = Math.ceil(total / per_page);
    pagination.innerHTML = '';
 
    if (totalPages <= 1) return;
 
    const prevBtn = document.createElement('button');
    prevBtn.className = 'page-btn';
    prevBtn.textContent = t.prevPage;
    prevBtn.disabled = page <= 1;
    prevBtn.addEventListener('click', () => loadVehicles(page - 1));
    pagination.appendChild(prevBtn);
 
    const start = Math.max(1, page - 2);
    const end = Math.min(totalPages, page + 2);
 
    for (let i = start; i <= end; i++) {
      const pageBtn = document.createElement('button');
      pageBtn.className = 'page-btn' + (i === page ? ' active' : '');
      pageBtn.textContent = String(i);
      pageBtn.addEventListener('click', () => loadVehicles(i));
      pagination.appendChild(pageBtn);
    }
 
    const nextBtn = document.createElement('button');
    nextBtn.className = 'page-btn';
    nextBtn.textContent = t.nextPage;
    nextBtn.disabled = page >= totalPages;
    nextBtn.addEventListener('click', () => loadVehicles(page + 1));
    pagination.appendChild(nextBtn);
 
    const info = document.createElement('span');
    info.className = 'page-info';
    info.textContent = t.pageInfo(page, totalPages);
    pagination.appendChild(info);
  }
  async function editVehicle(id) {
    formSection.style.display = 'block';
    formTitle.textContent = translations[currentLang].editVehicleTitle;
    form.scrollIntoView({ behavior: 'smooth' });
 
    showMsg(translations[currentLang].msg_loading, 'info');
 
    const rv = await fetchJson(API_VEHICLE_GET + '?id=' + encodeURIComponent(id));
    if (rv.ok && rv.json && rv.json.success && rv.json.vehicle) {
      const v = rv.json.vehicle;
      document.getElementById('plate_number').value = v.vehicle_code || '';
      document.getElementById('type').value = v.type || '';
      document.getElementById('year').value = v.manufacture_year || '';
      document.getElementById('emp_id').value = v.emp_id || '';
      document.getElementById('driver_name').value = v.driver_name || '';
      document.getElementById('driver_phone').value = v.driver_phone || '';
      document.getElementById('notes').value = v.notes || '';
      statusSel.value = v.status || '';
      vmSel.value = v.vehicle_mode || '';
   
      const refs = await loadReferences(currentLang);
      const t = translations[currentLang];
      const nameField = currentLang === 'ar' ? 'name_ar' : 'name_en';
   
      if (v.sector_id && sectorSel) {
        setPreselected(sectorSel, String(v.sector_id));
      }
      if (v.department_id) {
        setPreselected(deptSel, String(v.department_id));
        const filteredSections = refs.sections.filter(s => String(s.department_id ?? '') === String(v.department_id));
        populateSelectSingleLanguage(sectionSel, filteredSections, currentLang, t.sectionSelPlaceholder);
      }
      if (v.section_id) {
        setPreselected(sectionSel, String(v.section_id));
        const filteredDivisions = refs.divisions.filter(d => String(d.section_id ?? '') === String(v.section_id));
        populateSelectSingleLanguage(divisionSel, filteredDivisions, currentLang, t.divisionSelPlaceholder);
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
      clearMsg();
    } else {
      showMsg(translations[currentLang].msg_failed || 'فشل تحميل بيانات المركبة', 'error');
    }
  }
  async function deleteVehicle(id) {
    if (!confirm(translations[currentLang].deleteTitle || 'هل أنت متأكد من حذف هذه المركبة؟')) return;
 
    const fd = new FormData();
    fd.append('id', id);
 
    const r = await fetchJson(API_VEHICLE_DELETE, { method: 'POST', body: fd });
 
    if (r.ok && r.json && r.json.success) {
      showMsg(r.json.message || translations[currentLang].msg_saved || 'تم الحذف بنجاح', 'success');
      await loadVehicles(currentPage);
    } else {
      showMsg((r.json && r.json.message) || translations[currentLang].msg_failed || 'فشل الحذف', 'error');
    }
  }
  async function loadReferences(lang) {
    const res = await fetchJson(API_HELPER + '?lang=' + encodeURIComponent(lang), { method: 'GET' });
    let sectors = [], deps = [], secs = [], divs = [];
    if (res.ok && res.json) {
      sectors = res.json.sectors || [];
      deps = res.json.departments || [];
      secs = res.json.sections || [];
      divs = res.json.divisions || [];
    }
    if (!Array.isArray(sectors) || sectors.length === 0) {
      const rsc = await fetchJson(API_HELPER + '?type=sectors&lang=' + encodeURIComponent(lang));
      sectors = (rsc.json && rsc.json.sectors) || [];
    }
    if (!Array.isArray(deps) || deps.length === 0) {
      const rd = await fetchJson(API_HELPER + '?type=departments&lang=' + encodeURIComponent(lang));
      deps = (rd.json && rd.json.departments) || [];
    }
    if (!Array.isArray(secs) || secs.length === 0) {
      const rs = await fetchJson(API_HELPER + '?type=sections&lang=' + encodeURIComponent(lang));
      secs = (rs.json && rs.json.sections) || [];
    }
    if (!Array.isArray(divs) || divs.length === 0) {
      const rv = await fetchJson(API_HELPER + '?type=divisions&lang=' + encodeURIComponent(lang));
      divs = (rv.json && rv.json.divisions) || [];
    }
    currentReferences = { sectors: sectors, departments: deps, sections: secs, divisions: divs };
    clearMsg();
    return { sectors: sectors, departments: deps, sections: secs, divisions: divs };
  }
  function populateFilterSelect(sel, items, lang, placeholder) {
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    (items || []).forEach(it => {
      const id = String(it.id ?? '');
      const label = (lang === 'en') ? (it.name_en || it.name || it.name_ar) : (it.name_ar || it.name || it.name_en);
      const o = document.createElement('option');
      o.value = id;
      o.textContent = label || id;
      sel.appendChild(o);
    });
  }
  function populateSelectSingleLanguage(sel, items, lang, placeholder) {
    if (!sel) return;
    const prev = sel.value;
    sel.innerHTML = '';
    const op0 = document.createElement('option');
    op0.value = '';
    op0.textContent = placeholder || (lang === 'en' ? 'Select' : 'اختر');
    sel.appendChild(op0);
    (items || []).forEach(it => {
      const id = String(it.id ?? it.department_id ?? it.section_id ?? it.division_id ?? it.value ?? '');
      const label = (lang === 'en') ? (it.name_en || it.name || it.name_ar) : (it.name_ar || it.name || it.name_en);
      const o = document.createElement('option');
      o.value = id;
      o.textContent = label || id;
      sel.appendChild(o);
    });
    if (prev) {
      const f = sel.querySelector(`option[value="${prev}"]`);
      if (f) sel.value = prev;
    }
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
    // تحويل الحقول لتطابق جدول البيانات
    if (fd.has('plate_number')) { 
      const v = fd.get('plate_number'); 
      fd.delete('plate_number'); 
      fd.append('vehicle_code', v); 
    }
    if (fd.has('year')) { 
      const v = fd.get('year'); 
      fd.delete('year'); 
      fd.append('manufacture_year', v); 
    }
    // emp_id مطلوب لسائق المركبة
    // Defaults للحقول المطلوبة
    if (!fd.get('department_id') || fd.get('department_id') === '') fd.set('department_id', DEFAULT_DEPARTMENT);
    if (!fd.get('section_id') || fd.get('section_id') === '') fd.set('section_id', DEFAULT_SECTION);
    if (!fd.get('division_id') || fd.get('division_id') === '') fd.set('division_id', DEFAULT_DIVISION);
    if (!fd.get('status')) fd.set('status', 'operational');
    if (!fd.get('vehicle_mode')) fd.set('vehicle_mode', 'shift');
    // لا نرسل created_by/updated_by؛ الباك يقوم بها من الجلسة (emp_id المستخدم الحالي)
    return fd;
  }
  function validateForm(fd) {
    const t = translations[currentLang];
    if (!fd.get('emp_id')) {
      showMsg(t.msg_emp_required, 'error');
      return false;
    }
    const year = fd.get('manufacture_year');
    if (year && (isNaN(year) || parseInt(year) < 1900 || parseInt(year) > 2099)) {
      showMsg(t.msg_year_invalid, 'error');
      return false;
    }
    return true;
  }
  function openReport() {
    const q = searchInput.value.trim();
    const sector = sectorFilter.value;
    const dept = deptFilter.value;
    const section = sectionFilter.value;
    const status = statusFilter.value;
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (sector) params.append('sector_id', sector);
    if (dept) params.append('department_id', dept);
    if (section) params.append('section_id', section);
    if (status) params.append('status', status);
    window.open(REPORT_PATH + '?' + params.toString(), '_blank');
  }
  async function init() {
    await initSessionOnServer();
    submitBtn.disabled = true;
    clearMsg();
    const sess = await sessionCheck();
    if (!sess) return;
 
    currentSession = sess;
    currentPermissions = await loadPermissions();
 
    currentLang = (sess.user && sess.user.preferred_language) ? sess.user.preferred_language.toLowerCase() : (document.documentElement.lang || 'ar');
    applyTranslation(currentLang);
 
    const refs = await loadReferences(currentLang);
    const t = translations[currentLang];
    populateSelectSingleLanguage(sectorSel, refs.sectors, currentLang, t.sectorSelPlaceholder);
    const preSector = (sess.user && sess.user.sector_id) || '';
    if (preSector) setPreselected(sectorSel, String(preSector));
    populateSelectSingleLanguage(deptSel, refs.departments, currentLang, t.deptSelPlaceholder);
    const preDep = form.getAttribute('data-department-id') || (sess.user && sess.user.department_id) || DEFAULT_DEPARTMENT;
    setPreselected(deptSel, String(preDep));
    const filteredSections = refs.sections.filter(s => String(s.department_id ?? '') === String(preDep));
    populateSelectSingleLanguage(sectionSel, filteredSections, currentLang, t.sectionSelPlaceholder);
    const preSec = form.getAttribute('data-section-id') || (sess.user && sess.user.section_id) || '';
    setPreselected(sectionSel, String(preSec));
    const sectionToUse = preSec || (sectionSel.options.length > 1 ? sectionSel.options[1].value : '');
    const filteredDivisions = refs.divisions.filter(d => String(d.section_id ?? '') === String(sectionToUse));
    populateSelectSingleLanguage(divisionSel, filteredDivisions, currentLang, t.divisionSelPlaceholder);
    const preDiv = form.getAttribute('data-division-id') || (sess.user && sess.user.division_id) || DEFAULT_DIVISION;
    setPreselected(divisionSel, String(preDiv));
    deptSel.addEventListener('change', function(){
      const dep = this.value || '';
      const filteredSections = refs.sections.filter(s => String(s.department_id ?? '') === String(dep));
      populateSelectSingleLanguage(sectionSel, filteredSections, currentLang, t.sectionSelPlaceholder);
      divisionSel.innerHTML = '';
      const o = document.createElement('option');
      o.value = '';
      o.textContent = t.divisionSelPlaceholder;
      divisionSel.appendChild(o);
    });
    sectionSel.addEventListener('change', function(){
      const sec = this.value || '';
      const filteredDivisions = refs.divisions.filter(d => String(d.section_id ?? '') === String(sec));
      populateSelectSingleLanguage(divisionSel, filteredDivisions, currentLang, t.divisionSelPlaceholder);
    });
    if (vmSel) {
      vmSel.innerHTML = '';
      const op0 = document.createElement('option');
      op0.value = '';
      op0.textContent = t.vehicleModeSelect;
      vmSel.appendChild(op0);
      const vm_opts = { shift: t.vehicleModeShift, private: t.vehicleModePrivate };
      ['shift','private'].forEach(k => {
        const o = document.createElement('option');
        o.value = k;
        o.textContent = vm_opts[k] || k;
        vmSel.appendChild(o);
      });
    }
    if (statusSel) {
      statusSel.innerHTML = '';
      const op0 = document.createElement('option');
      op0.value = '';
      op0.textContent = t.statusSelPlaceholder;
      statusSel.appendChild(op0);
      const st_opts = {
        operational: t.statusOperationalSel,
        maintenance: t.statusMaintenanceSel,
        out_of_service: t.statusOutOfServiceSel
      };
      ['operational','maintenance','out_of_service'].forEach(k => {
        const o = document.createElement('option');
        o.value = k;
        o.textContent = st_opts[k] || k;
        statusSel.appendChild(o);
      });
    }
    populateFilterSelect(sectorFilter, refs.sectors, currentLang, t.sectorFilterPlaceholder);
    populateFilterSelect(deptFilter, refs.departments, currentLang, t.deptFilterPlaceholder);
    populateFilterSelect(sectionFilter, refs.sections, currentLang, t.sectionFilterPlaceholder);
 
    searchBtn.addEventListener('click', () => loadVehicles(1));
    searchInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') loadVehicles(1); });
    sectorFilter.addEventListener('change', () => loadVehicles(1));
    deptFilter.addEventListener('change', () => {
      const selectedDept = deptFilter.value;
      const filteredSections = refs.sections.filter(s => String(s.department_id ?? '') === String(selectedDept));
      populateFilterSelect(sectionFilter, filteredSections, currentLang, t.sectionFilterPlaceholder);
      loadVehicles(1);
    });
    sectionFilter.addEventListener('change', () => loadVehicles(1));
    statusFilter.addEventListener('change', () => loadVehicles(1));
    addNewBtn.addEventListener('click', () => {
      formSection.style.display = 'block';
      formTitle.textContent = t.addNewTitle;
      form.reset();
      const hid = form.querySelector('input[name="id"]');
      if (hid) hid.remove();
      formSection.scrollIntoView({ behavior: 'smooth' });
    });
    if (reportBtn) {
      reportBtn.addEventListener('click', openReport);
    }
 
    await loadVehicles(1);
 
    const vid = new URL(location.href).searchParams.get('id');
    if (vid) {
      formSection.style.display = 'block';
      await editVehicle(vid);
    }
 
    submitBtn.disabled = false;
    form.addEventListener('submit', async function(ev){
      ev.preventDefault();
      clearMsg();
      const fd = buildFormData(currentSession);
      if (!validateForm(fd)) {
        submitBtn.disabled = false;
        return;
      }
      submitBtn.disabled = true;
      showMsg(translations[currentLang].msg_saving, 'info');
      try {
        const postRes = await fetchJson(API_VEHICLE_ADD, { method: 'POST', body: fd });
        if (postRes.ok && postRes.json && postRes.json.success) {
          showMsg(postRes.json.message || translations[currentLang].msg_saved, 'success');
          await loadVehicles(currentPage);
          if (!fd.get('id')) {
            form.reset();
            formSection.style.display = 'none';
          }
        } else {
          const body = postRes.json || {};
          if (body && body.message && /not authenticated/i.test(String(body.message))) {
            showMsg(translations[currentLang].msg_no_auth, 'error');
            appendMsgHtml(`<div style="margin-top:8px"><button id="openLoginBtn2" class="btn ghost">${translations[currentLang].msg_no_auth}</button></div>`);
            const b2 = document.getElementById('openLoginBtn2');
            if (b2) b2.addEventListener('click', ()=> window.location.href = '/vehicle_management/public/login.html');
          } else {
            showMsg((body && body.message) ? body.message : translations[currentLang].msg_failed, 'error');
          }
        }
      } catch (e) {
        showMsg(translations[currentLang].msg_failed, 'error');
      } finally {
        submitBtn.disabled = false;
      }
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