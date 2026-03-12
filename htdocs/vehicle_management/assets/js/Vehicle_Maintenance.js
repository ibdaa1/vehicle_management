(function () {
  'use strict';
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
  const API_MAINTENANCE = '/vehicle_management/api/vehicle/Vehicle_Maintenance.php';
  const API_PERMISSIONS = '/vehicle_management/api/permissions/get_permissions.php';
  const API_VEHICLES = '/vehicle_management/api/vehicle/list.php';
  const API_REFERENCES = '/vehicle_management/api/helper/get_references.php';
  const SESSION_INIT = '/vehicle_management/api/config/session.php?init=1';
  const REPORT_PAGE = '/vehicle_management/public/report_Vehicle_Maintenance.html';
  const form = document.getElementById('maintenanceForm');
  const submitBtn = document.getElementById('submitBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const msgEl = document.getElementById('msg');
  const loggedUserEl = document.getElementById('loggedUser');
  const orgNameEl = document.getElementById('orgName');
  const searchInput = document.getElementById('searchInput');
  const typeFilter = document.getElementById('typeFilter');
  const deptFilter = document.getElementById('deptFilter');
  const sectionFilter = document.getElementById('sectionFilter');
  const divisionFilter = document.getElementById('divisionFilter');
  const fromDate = document.getElementById('fromDate');
  const toDate = document.getElementById('toDate');
  const searchBtn = document.getElementById('searchBtn');
  const addNewBtn = document.getElementById('addNewBtn');
  const printBtn = document.getElementById('printBtn');
  const resultsSection = document.getElementById('resultsSection');
  const formSection = document.getElementById('formSection');
  const maintenanceTableBody = document.getElementById('maintenanceTableBody');
  const totalCount = document.getElementById('totalCount');
  const pagination = document.getElementById('pagination');
  const formTitle = document.getElementById('formTitle');
  const vehicleCodeInput = document.getElementById('vehicle_code');
  const vehicleList = document.getElementById('vehicleList');
  const vehicleNotFound = document.getElementById('vehicleNotFound');
  const vehicleDetailsDiv = document.getElementById('vehicleDetails');
  let globalSessionId = null;
  let currentSession = null;
  let currentPermissions = null;
  let currentPage = 1;
  let perPage = 30;
  let currentLang = 'ar';
  let translations = {};
  let vehiclesData = {}; // {code: vehicle}
  let referencesData = {}; // {departments: [], sections: [], divisions: []}
  let deptMap = {}; // {id: dept object}
  let sectionMap = {}; // {id: section object}
  let divisionMap = {}; // {id: division object}
  let vehicleOptions = []; // Array of full option texts for search
  function showMsg(text, type='info'){
    if (!msgEl) return;
    const color = type==='error' ? '#8b1e1e' : (type==='success' ? '#065f46' : '#6b7280');
    msgEl.innerHTML = `<div style="color:${color}; direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${text}</div>`;
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
 
    // Update buttons (الأيقونات موجودة في HTML الآن)
    const backBtn = document.querySelector('a[href*="index.html"]');
    if (backBtn) backBtn.title = currentLang === 'en' ? 'Back to Home' : 'العودة إلى الرئيسية';
 
    // Apply translations to all elements with data-i18n attribute
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (t[key]) el.textContent = t[key];
      // Set direction for translated elements
      el.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      el.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      el.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    });
 
    // Apply translations to placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (t[key]) el.placeholder = t[key];
      // Set direction for inputs
      el.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      el.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      el.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    });
 
    // Update option elements in selects
    document.querySelectorAll('option[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (t[key]) el.textContent = t[key];
      el.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    });
    // Specific fix for vehicle_code placeholder - ensure it's applied correctly
    if (vehicleCodeInput && t.placeholder_vehicle_code) {
      vehicleCodeInput.placeholder = t.placeholder_vehicle_code;
      vehicleCodeInput.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      vehicleCodeInput.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      vehicleCodeInput.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    }
 
    // Apply to other specific placeholders if needed
    const locationInput = document.getElementById('location');
    if (locationInput && t.placeholder_location) {
      locationInput.placeholder = t.placeholder_location;
      locationInput.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      locationInput.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      locationInput.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    }
    const notesInput = document.getElementById('notes');
    if (notesInput && t.placeholder_notes) {
      notesInput.placeholder = t.placeholder_notes;
      notesInput.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      notesInput.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      notesInput.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    }
    const searchInputEl = document.getElementById('searchInput');
    if (searchInputEl && t.search_placeholder) {
      searchInputEl.placeholder = t.search_placeholder;
      searchInputEl.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      searchInputEl.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      searchInputEl.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    }

    // Apply direction to form title and form section
    if (formTitle) {
      formTitle.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      formTitle.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      formTitle.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    }
    if (formSection) {
      formSection.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      formSection.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      formSection.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    }
    if (form) {
      form.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      form.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      form.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    }

    // Apply to labels in the form
    document.querySelectorAll('label[for]').forEach(label => {
      label.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      label.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      label.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    });

    // Apply to vehicle details div and its children
    if (vehicleDetailsDiv) {
      vehicleDetailsDiv.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      vehicleDetailsDiv.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      vehicleDetailsDiv.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
      // Apply to detail elements
      ['detailDriver', 'detailDriverPhone', 'detailStatus', 'detailManufactureYear', 'detailEmpId', 'detailType', 'detailDept', 'detailSection', 'detailDivision'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
          el.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
          el.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
          el.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
        }
      });
    }
  }
  // دالة مساعدة للحصول على اسم الإدارة بناءً على ID واللغة
  function getDeptName(deptId) {
    if (!deptId || !deptMap[deptId]) return '-';
    return currentLang === 'ar' ? (deptMap[deptId].name_ar || deptMap[deptId].name || '-') : (deptMap[deptId].name_en || deptMap[deptId].name || '-');
  }
  // دالة مساعدة للحصول على اسم القسم
  function getSectionName(sectionId) {
    if (!sectionId || !sectionMap[sectionId]) return '-';
    return currentLang === 'ar' ? (sectionMap[sectionId].name_ar || sectionMap[sectionId].name || '-') : (sectionMap[sectionId].name_en || sectionMap[sectionId].name || '-');
  }
  // دالة مساعدة للحصول على اسم الشعبة
  function getDivisionName(divisionId) {
    if (!divisionId || !divisionMap[divisionId]) return '-';
    return currentLang === 'ar' ? (divisionMap[divisionId].name_ar || divisionMap[divisionId].name || '-') : (divisionMap[divisionId].name_en || divisionMap[divisionId].name || '-');
  }
  // دالة مساعدة للحصول على بيانات المركبة الكاملة بناءً على vehicle_code
  function getVehicleDetails(vehicleCode) {
    if (!vehicleCode || !vehiclesData[vehicleCode]) return { driver_name: '-', driver_phone: '-', status: '-', manufacture_year: '-', emp_id: '-', type: '-', department_id: null, section_id: null, division_id: null };
    const v = vehiclesData[vehicleCode];
    return {
      driver_name: v.driver_name || '-',
      driver_phone: v.driver_phone || '-',
      status: v.status || '-',
      manufacture_year: v.manufacture_year || '-',
      emp_id: v.emp_id || '-',
      type: v.type || '-',
      department_id: v.department_id,
      section_id: v.section_id,
      division_id: v.division_id
    };
  }
  // دالة للتحقق من صحة رقم المركبة وإظهار الرسالة
  function validateVehicleCode(code) {
    vehicleNotFound.style.display = vehiclesData[code] ? 'none' : 'block';
    if (vehiclesData[code]) {
      const details = getVehicleDetails(code);
      document.getElementById('detailDriver').textContent = details.driver_name;
      document.getElementById('detailDriverPhone').textContent = details.driver_phone;
      document.getElementById('detailStatus').textContent = details.status;
      document.getElementById('detailManufactureYear').textContent = details.manufacture_year;
      document.getElementById('detailEmpId').textContent = details.emp_id;
      document.getElementById('detailType').textContent = details.type;
      document.getElementById('detailDept').textContent = getDeptName(details.department_id);
      document.getElementById('detailSection').textContent = getSectionName(details.section_id);
      document.getElementById('detailDivision').textContent = getDivisionName(details.division_id);
      vehicleDetailsDiv.style.display = 'block';
    } else {
      vehicleDetailsDiv.style.display = 'none';
    }
  }
  async function loadReferences() {
    try {
      const lang = currentLang || 'ar';
      const r = await fetchJson(API_REFERENCES + '?lang=' + lang, { method: 'GET' });
      if (r.ok && r.json && r.json.success) {
        referencesData = r.json;
     
        // بناء الـ maps للوصول السريع
        deptMap = {};
        r.json.departments.forEach(dept => { deptMap[dept.id] = dept; });
        sectionMap = {};
        r.json.sections.forEach(sect => { sectionMap[sect.id] = sect; });
        divisionMap = {};
        r.json.divisions.forEach(div => { divisionMap[div.id] = div; });
     
        // Populate department filter
        if (r.json.departments && deptFilter) {
          deptFilter.innerHTML = '<option value="">' + (translations.filter_all_depts || 'كل الإدارات') + '</option>';
          r.json.departments.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept.id;
            option.textContent = getDeptName(dept.id);
            option.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
            deptFilter.appendChild(option);
          });
        }
     
        // Populate section filter
        if (r.json.sections && sectionFilter) {
          sectionFilter.innerHTML = '<option value="">' + (translations.filter_all_sections || 'كل الأقسام') + '</option>';
          r.json.sections.forEach(sect => {
            const option = document.createElement('option');
            option.value = sect.id;
            option.textContent = getSectionName(sect.id);
            option.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
            sectionFilter.appendChild(option);
          });
        }
     
        // Populate division filter
        if (r.json.divisions && divisionFilter) {
          divisionFilter.innerHTML = '<option value="">' + (translations.filter_all_divisions || 'كل الشعب') + '</option>';
          r.json.divisions.forEach(div => {
            const option = document.createElement('option');
            option.value = div.id;
            option.textContent = getDivisionName(div.id);
            option.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
            divisionFilter.appendChild(option);
          });
        }
      }
    } catch (e) {
      console.error('Failed to load references:', e);
    }
  }
  async function loadVehicles() {
    try {
      const r = await fetchJson(API_VEHICLES + '?page=1&per_page=1000', { method: 'GET' });
      if (r.ok && r.json && r.json.success && r.json.vehicles) {
        vehiclesData = {};
        vehicleList.innerHTML = '';
        vehicleOptions = [];
     
        r.json.vehicles.forEach(v => {
          vehiclesData[v.vehicle_code] = v;
          const deptName = getDeptName(v.department_id);
          const sectName = getSectionName(v.section_id);
          const divName = getDivisionName(v.division_id);
          const driverName = v.driver_name || 'N/A';
          const fullText = `${v.vehicle_code} - ${driverName} - ${v.driver_phone || '-'} - ${v.status || '-'} - ${v.manufacture_year || '-'} - ${v.emp_id || '-'} - ${v.type || '-'} - ${deptName} - ${sectName} - ${divName}`;
          vehicleOptions.push(fullText);
          const option = document.createElement('option');
          option.value = v.vehicle_code;
          option.textContent = fullText;
          option.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
          vehicleList.appendChild(option);
        });
        // Event listener for manual search and validation
        vehicleCodeInput.addEventListener('input', function() {
          const code = this.value.trim();
          if (code) {
            // Filter datalist options client-side for better UX
            Array.from(vehicleList.options).forEach(opt => {
              opt.hidden = !opt.value.toLowerCase().includes(code.toLowerCase());
            });
          }
        });
        vehicleCodeInput.addEventListener('change', function() {
          const code = this.value.trim();
          validateVehicleCode(code);
        });
        vehicleCodeInput.addEventListener('blur', function() {
          const code = this.value.trim();
          if (code && !vehiclesData[code]) {
            this.value = '';
            vehicleDetailsDiv.style.display = 'none';
          }
        });
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
  // Helper: Debounce function for filters
  function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
  }
  async function loadMaintenance(page = 1) {
    const q = searchInput.value.trim();
    const type = typeFilter.value;
    const dept = deptFilter ? deptFilter.value : '';
    const section = sectionFilter ? sectionFilter.value : '';
    const division = divisionFilter ? divisionFilter.value : '';
    const from = fromDate.value;
    const to = toDate.value;
    currentPage = page;
 
    // Debug: Log filter values to console (remove in production)
    console.log('Filters applied:', { q, type, dept, section, division, from, to, page });
 
    const params = new URLSearchParams();
    params.append('action', 'list');
    if (q) params.append('q', q); // البحث يشمل جميع الحقول الجديدة عبر الـ backend
    if (type) params.append('type', type);
    if (dept) params.append('department_id', dept);
    if (section) params.append('section_id', section);
    if (division) params.append('division_id', division);
    if (from) params.append('from_date', from);
    if (to) params.append('to_date', to);
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
 
    // Enhance records with vehicle details (including new fields)
    const enhancedRecords = (data.records || []).map(m => {
      const vehicleDetails = getVehicleDetails(m.vehicle_code);
      return {
        ...m,
        driver_name: vehicleDetails.driver_name,
        driver_phone: vehicleDetails.driver_phone,
        status: vehicleDetails.status,
        manufacture_year: vehicleDetails.manufacture_year,
        emp_id: vehicleDetails.emp_id,
        type: vehicleDetails.type,
        department_id: vehicleDetails.department_id,
        section_id: vehicleDetails.section_id,
        division_id: vehicleDetails.division_id
      };
    });
 
    renderMaintenanceTable(enhancedRecords);
    renderPagination(data.total, data.page, data.per_page);
  }
  function renderMaintenanceTable(records) {
    maintenanceTableBody.innerHTML = '';
 
    if (records.length === 0) {
      const noResultsMsg = translations.no_results || 'لا توجد نتائج';
      maintenanceTableBody.innerHTML = `<tr><td colspan="16" style="text-align:center;color:var(--muted); direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${noResultsMsg}</td></tr>`;
      return;
    }
 
    records.forEach(m => {
      const tr = document.createElement('tr');
   
      // Type badge with translation
      const typeClass = `type-${(m.maintenance_type || 'Other').replace(/\s+/g, '')}`;
      let typeText = m.maintenance_type || 'Other';
      const typeKey = `type_${(m.maintenance_type || 'other').toLowerCase().replace(/\s+/g, '_').replace('check', 'technical').replace('repair', 'mechanical')}`;
      if (translations[typeKey]) typeText = translations[typeKey];
   
      const editBtnText = translations.btn_edit || 'تعديل';
      const deleteBtnText = translations.btn_delete || 'حذف';
   
      // استخدام البيانات المعززة
      const driverName = m.driver_name || '-';
      const driverPhone = m.driver_phone || '-';
      const status = m.status || '-';
      const manufactureYear = m.manufacture_year || '-';
      const empId = m.emp_id || '-';
      const vehicleType = m.type || '-';
      const deptName = getDeptName(m.department_id);
      const sectName = getSectionName(m.section_id);
      const divName = getDivisionName(m.division_id);
   
      tr.innerHTML = `
        <td data-label="ID" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${m.id}</td>
        <td data-label="رقم المركبة" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${m.vehicle_code || '-'}</td>
        <td data-label="السائق" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${driverName}</td>
        <td data-label="هاتف السائق" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${driverPhone}</td>
        <td data-label="الحالة" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${status}</td>
        <td data-label="سنة الصنع" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${manufactureYear}</td>
        <td data-label="ID الموظف" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${empId}</td>
        <td data-label="النوع" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${vehicleType}</td>
        <td data-label="الإدارة" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${deptName}</td>
        <td data-label="القسم" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${sectName}</td>
        <td data-label="الشعبة" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${divName}</td>
        <td data-label="تاريخ الزيارة" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${m.visit_date || '-'}</td>
        <td data-label="الزيارة القادمة" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${m.next_visit_date || '-'}</td>
        <td data-label="نوع الصيانة" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};"><span class="type-badge ${typeClass}" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${typeText}</span></td>
        <td data-label="الموقع" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">${m.location || '-'}</td>
        <td data-label="الإجراءات" class="action-buttons" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};">
          ${currentPermissions && currentPermissions.can_edit ?
            `<button class="btn small ghost" data-action="edit" data-id="${m.id}" title="${editBtnText}" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};"><i class="fas fa-edit"></i></button>` : ''}
          ${currentPermissions && currentPermissions.can_delete ?
            `<button class="btn small danger" data-action="delete" data-id="${m.id}" title="${deleteBtnText}" style="direction: ${currentLang === 'ar' ? 'rtl' : 'ltr'}; text-align: ${currentLang === 'ar' ? 'right' : 'left'};"><i class="fas fa-trash"></i></button>` : ''}
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
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.title = 'الصفحة السابقة';
    prevBtn.disabled = page <= 1;
    prevBtn.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
    prevBtn.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    prevBtn.addEventListener('click', () => loadMaintenance(page - 1));
    pagination.appendChild(prevBtn);
 
    // Page numbers (show max 5 pages around current)
    const start = Math.max(1, page - 2);
    const end = Math.min(totalPages, page + 2);
 
    for (let i = start; i <= end; i++) {
      const pageBtn = document.createElement('button');
      pageBtn.className = 'page-btn' + (i === page ? ' active' : '');
      pageBtn.textContent = String(i);
      pageBtn.title = `الانتقال إلى الصفحة ${i}`;
      pageBtn.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      pageBtn.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
      pageBtn.addEventListener('click', () => loadMaintenance(i));
      pagination.appendChild(pageBtn);
    }
 
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'page-btn';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.title = 'الصفحة التالية';
    nextBtn.disabled = page >= totalPages;
    nextBtn.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
    nextBtn.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    nextBtn.addEventListener('click', () => loadMaintenance(page + 1));
    pagination.appendChild(nextBtn);
 
    // Page info
    const info = document.createElement('span');
    info.className = 'page-info';
    info.textContent = `صفحة ${page} من ${totalPages}`;
    info.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
    info.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
    pagination.appendChild(info);
  }
  // Updated: printReport - Open report page with current filters
  function printReport() {
    if (resultsSection.style.display === 'none') {
      showMsg('لا توجد نتائج للطباعة. قم بالبحث أولاً.', 'warning');
      return;
    }
    const q = searchInput.value.trim();
    const type = typeFilter.value;
    const dept = deptFilter ? deptFilter.value : '';
    const section = sectionFilter ? sectionFilter.value : '';
    const division = divisionFilter ? divisionFilter.value : '';
    const from = fromDate.value;
    const to = toDate.value;
   
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (type) params.append('type', type);
    if (dept) params.append('department_id', dept);
    if (section) params.append('section_id', section);
    if (division) params.append('division_id', division);
    if (from) params.append('from_date', from);
    if (to) params.append('to_date', to);
   
    const reportUrl = REPORT_PAGE + (params.toString() ? '?' + params.toString() : '');
    window.open(reportUrl, '_blank', 'width=1000,height=800,scrollbars=yes');
  }
  async function editMaintenance(id) {
    formSection.style.display = 'block';
    formTitle.innerHTML = '<i class="fas fa-edit"></i> تعديل سجل الصيانة';
    formTitle.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    formTitle.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
    formTitle.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
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
   
      // تحديث التفاصيل المرتبطة للمركبة (مع الحقول الجديدة)
      if (m.vehicle_code && vehiclesData[m.vehicle_code]) {
        const details = getVehicleDetails(m.vehicle_code);
        document.getElementById('detailDriver').textContent = details.driver_name;
        document.getElementById('detailDriverPhone').textContent = details.driver_phone;
        document.getElementById('detailStatus').textContent = details.status;
        document.getElementById('detailManufactureYear').textContent = details.manufacture_year;
        document.getElementById('detailEmpId').textContent = details.emp_id;
        document.getElementById('detailType').textContent = details.type;
        document.getElementById('detailDept').textContent = getDeptName(details.department_id);
        document.getElementById('detailSection').textContent = getSectionName(details.section_id);
        document.getElementById('detailDivision').textContent = getDivisionName(details.division_id);
        vehicleDetailsDiv.style.display = 'block';
        vehicleNotFound.style.display = 'none';
      }
   
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
    htmlEl.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
    htmlEl.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
 
    // Load translations
    await loadTranslations(currentLang);
 
    // Load references FIRST, then vehicles (للربط)
    await loadReferences();
    await loadVehicles();
 
    // Load records initially
    await loadMaintenance(1);
 
    // Event listeners for search and add - AFTER loading references and vehicles
    const debouncedLoad = debounce(() => loadMaintenance(1), 300);
   
    searchBtn.addEventListener('click', () => loadMaintenance(1));
    searchInput.addEventListener('input', debouncedLoad); // New: Real-time search
    searchInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') loadMaintenance(1); });
   
    typeFilter.addEventListener('change', debouncedLoad);
    if (deptFilter) {
      deptFilter.addEventListener('change', debouncedLoad);
      deptFilter.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      deptFilter.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      deptFilter.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
      console.log('Dept filter listener attached'); // Debug
    }
    if (sectionFilter) {
      sectionFilter.addEventListener('change', debouncedLoad);
      sectionFilter.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      sectionFilter.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      sectionFilter.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
      console.log('Section filter listener attached'); // Debug
    }
    if (divisionFilter) {
      divisionFilter.addEventListener('change', debouncedLoad);
      divisionFilter.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      divisionFilter.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      divisionFilter.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
      console.log('Division filter listener attached'); // Debug
    }
    if (fromDate) {
      fromDate.addEventListener('change', debouncedLoad);
      fromDate.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      fromDate.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      fromDate.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
      console.log('From date listener attached'); // Debug
    }
    if (toDate) {
      toDate.addEventListener('change', debouncedLoad);
      toDate.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      toDate.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      toDate.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
      console.log('To date listener attached'); // Debug
    }
 
    addNewBtn.addEventListener('click', () => {
      formSection.style.display = 'block';
      formTitle.innerHTML = '<i class="fas fa-plus"></i> ' + (translations.form_title_add || 'إضافة سجل صيانة جديد');
      formTitle.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
      formTitle.style.direction = currentLang === 'ar' ? 'rtl' : 'ltr';
      formTitle.style.textAlign = currentLang === 'ar' ? 'right' : 'left';
      form.reset();
      const hid = form.querySelector('input[name="id"]');
      if (hid) hid.remove();
      vehicleDetailsDiv.style.display = 'none';
      vehicleNotFound.style.display = 'none';
      vehicleCodeInput.value = '';
      formSection.scrollIntoView({ behavior: 'smooth' });
    });
    printBtn.addEventListener('click', printReport);
 
    submitBtn.disabled = false;
    form.addEventListener('submit', async function(ev){
      ev.preventDefault();
      clearMsg();
      const code = vehicleCodeInput.value.trim();
      if (!vehiclesData[code]) {
        showMsg('المركبة غير موجودة - الرجاء اختيار مركبة صالحة', 'error');
        return;
      }
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
            vehicleDetailsDiv.style.display = 'none';
            vehicleNotFound.style.display = 'none';
          }
        } else {
          const body = postRes.json || {};
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
      vehicleDetailsDiv.style.display = 'none';
      vehicleNotFound.style.display = 'none';
      vehicleCodeInput.value = '';
    });
  }
  document.addEventListener('DOMContentLoaded', init);
})();