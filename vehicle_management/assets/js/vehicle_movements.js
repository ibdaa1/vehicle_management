// vehicle_management/assets/js/vehicle_movements.js
(function () {
  'use strict';
  // API Endpoints
  const API_SESSION_INIT = '/vehicle_management/api/config/session.php?init=1';
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
  const API_PERMISSIONS = '/vehicle_management/api/permissions/get_permissions.php';
  const API_REFERENCES = '/vehicle_management/api/helper/get_references.php';
  const API_VEHICLES = '/vehicle_management/api/vehicle/get_vehicle_movements.php';
  const API_ADD_MOVEMENT = '/vehicle_management/api/vehicle/add_vehicle_movements.php';
  // DOM elements - يجب أن تتطابق مع HTML
  const searchInput = document.getElementById('searchInput');
  const departmentFilter = document.getElementById('departmentFilter');
  const sectionFilter = document.getElementById('sectionFilter');
  const divisionFilter = document.getElementById('divisionFilter');
  const statusFilter = document.getElementById('statusFilter');
  const vehiclesContainer = document.getElementById('vehiclesContainer');
  const loadingMsg = document.getElementById('loadingMsg');
  const loggedUserEl = document.getElementById('loggedUser');
  const orgNameEl = document.getElementById('orgName');
  // State
  let currentSession = null;
  let permissions = {}; // إضافة تخزين الصلاحيات
  let references = { departments: [], sections: [], divisions: [] };
  // Fetch helper (كما هو)
  async function fetchJson(url, opts = {}) {
    opts = Object.assign({}, opts);
    opts.credentials = 'include';
    if (!opts.headers) opts.headers = {};
    opts.headers['Accept'] = 'application/json';
    opts.headers['X-Requested-With'] = 'XMLHttpRequest';
   
    const token = localStorage.getItem('api_token');
    if (token) opts.headers['Authorization'] = `Bearer ${token}`;
   
    try {
      const res = await fetch(url, opts);
      const text = await res.text().catch(() => null);
      let json = null;
      try {
        if (text) json = JSON.parse(text);
      } catch (e) {
        json = null;
        console.error('JSON parse error:', e, 'Raw text:', text.substring(0, 500)); // Debug raw response
      }
      console.log(`Fetch ${url}: status ${res.status}, ok ${res.ok}`); // Debug
      return { ok: res.ok, status: res.status, json, text, headers: res.headers };
    } catch (e) {
      console.error('Fetch error for', url, e);
      return { ok: false, status: 0, json: null, text: null, error: e };
    }
  }
  // Session check and Permissions
  async function sessionCheck() {
    const r = await fetchJson(API_SESSION, { method: 'GET' });
    if (!r.ok || !r.json || !r.json.success) {
      const errorMsg = r.json?.message || r.text || 'Unknown session error';
      console.error('Session check failed:', errorMsg);
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>غير مصرح</h3><p>يرجى <a href="/vehicle_management/public/login.html">تسجيل الدخول</a></p><p>تفاصيل: ${errorMsg}</p></div>`;
      return null;
    }
    currentSession = r.json;
    if (loggedUserEl) loggedUserEl.textContent = `${r.json.user.username || ''} (${r.json.user.emp_id || ''})`;
    return r.json;
  }
  async function getPermissions() {
    const res = await fetchJson(API_PERMISSIONS, { method: 'GET' });
    if (res.ok && res.json && res.json.permissions) {
      permissions = res.json.permissions;
      console.log('Permissions loaded:', permissions);
    } else {
      console.warn('Permissions load failed or empty');
    }
    return permissions;
  }
  // Load references (كما هو)
  async function loadReferences() {
    const res = await fetchJson(`${API_REFERENCES}?lang=ar`, { method: 'GET' });
    if (res.ok && res.json) {
      references.departments = res.json.departments || [];
      references.sections = res.json.sections || [];
      references.divisions = res.json.divisions || [];
      populateFilter(departmentFilter, references.departments, 'جميع الإدارات');
      // تحديث statusFilter إذا لزم للعرض الافتراضي operational
      if (statusFilter) statusFilter.value = '';
    } else {
      console.error('References load failed');
    }
    return references;
  }
  // Populate filter dropdown (كما هو)
  function populateFilter(select, items, placeholder) {
    if (!select) return;
    select.innerHTML = `<option value="">${placeholder}</option>`;
    (items || []).forEach(it => {
      const id = String(it.department_id ?? it.section_id ?? it.division_id ?? it.id ?? '');
      const label = it.name_ar || it.name || id;
      const o = document.createElement('option');
      o.value = id;
      o.textContent = label;
      select.appendChild(o);
    });
  }
  // Load vehicles (مع تمرير الصلاحيات إذا لزم)
  async function loadVehicles() {
    const q = searchInput ? searchInput.value.trim() : '';
    const deptId = departmentFilter?.value || '';
    const secId = sectionFilter?.value || '';
    const divId = divisionFilter?.value || '';
    const status = statusFilter?.value || ''; // السماح بـ '' للاستخدام الافتراضي في PHP
   
    if (loadingMsg) loadingMsg.style.display = 'block';
    if (vehiclesContainer) vehiclesContainer.innerHTML = '';
   
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (deptId) params.append('department_id', deptId);
    if (secId) params.append('section_id', secId);
    if (divId) params.append('division_id', divId);
    if (status) params.append('status', status);
   
    const apiUrl = `${API_VEHICLES}?${params.toString()}`;
    console.log('Loading vehicles from:', apiUrl); // للديباج
    const r = await fetchJson(apiUrl, { method: 'GET' });
   
    if (loadingMsg) loadingMsg.style.display = 'none';
   
    if (!r.ok || !r.json || !r.json.success) {
      const errorMsg = r.json?.message || r.text || 'خطأ في الاتصال';
      console.error('Load vehicles error:', { status: r.status, text: r.text?.substring(0, 200), json: r.json }); // Enhanced debug
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>فشل التحميل</h3><p>${errorMsg}</p><p>تحقق من Console للتفاصيل.</p></div>`;
      return;
    }
   
    const vehicles = r.json.vehicles || [];
    if (vehicles.length === 0) {
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>لا توجد مركبات</h3><p>تحقق من الفلاتر أو الصلاحيات.</p></div>`;
      return;
    }
   
    renderVehicleCards(vehicles);
    console.log('Loaded', vehicles.length, 'vehicles'); // للديباج
  }
  // Translate vehicle status (كما هو)
  function translateVehicleStatus(status) {
    const map = {
      operational: 'قيد التشغيل',
      maintenance: 'صيانة',
      out_of_service: 'خارج الخدمة'
    };
    return map[status] || status;
  }
  // Translate availability
  function translateAvailabilityStatus(status, vehicleMode, vehicleEmpId, currentEmpId) {
    if (status === 'private_unavailable') return 'خاصة - غير متاحة';
    if (status === 'available') return 'متاحة للاستلام';
    if (status === 'checked_out_by_me') return 'مستلمة من قبلك';
    if (status === 'checked_out_by_other') return 'مستلمة من آخر';
    return status;
  }
  // Render vehicle cards - تم تحسين للصلاحيات والـ private mode
  function renderVehicleCards(vehicles) {
    let html = '';
    try {
        vehicles.forEach(v => {
           
            const statusClass = v.availability_status === 'available' ? 'available' : 
                                (v.availability_status === 'checked_out_by_me' ? 'checked-out-by-me' : 'checked-out');
            const statusText = translateAvailabilityStatus(
              v.availability_status, v.vehicle_mode, v.emp_id, currentSession?.user?.emp_id
            );
            const statusBadgeClass = v.availability_status === 'available' ? 'status-available' : 
                                     (v.availability_status === 'checked_out_by_me' ? 'status-checked-out-by-me' : 
                                      (v.availability_status === 'private_unavailable' ? 'status-private' : 'status-checked-out-by-other'));
           
            html += `<div class="vehicle-card ${statusClass}" data-vehicle-id="${v.id}">`;
            html += `<div class="vehicle-code">${v.vehicle_code || 'N/A'}</div>`;
           
            html += '<div class="vehicle-info">';
           
            // قائمة الحقول مع إضافة vehicle_mode
            const fields = [
                { label: 'النوع', key: 'type' },
                { label: 'سنة الصنع', key: 'manufacture_year' },
                { label: 'السائق', key: 'driver_name' },
                { label: 'الهاتف', key: 'driver_phone' },
                { label: 'الإدارة', key: 'department_name' },
                { label: 'القسم', key: 'section_name' },
                { label: 'الشعبة', key: 'division_name' },
                { label: 'وضع الاستخدام', key: 'vehicle_mode', translator: (mode) => mode === 'private' ? 'خاص' : 'ورديات' },
                { label: 'حالة المركبة', key: 'status', translator: translateVehicleStatus }
            ];
            fields.forEach(field => {
                let value = v[field.key];
               
                if (field.translator && value) {
                    value = field.translator(value);
                }
                if (value !== null && value !== undefined && value !== '') {
                    html += '<div class="vehicle-info-row">';
                    html += `<span class="info-label">${field.label}:</span>`;
                    html += `<span class="info-value">${value}</span>`;
                    html += '</div>';
                }
            });
            html += '</div>'; // vehicle-info
           
            html += `<div class="vehicle-status-badge ${statusBadgeClass}">${statusText}</div>`;
           
            html += '<div class="vehicle-actions">';
           
            if (v.can_pickup) {
              html += `<button class="btn btn-pickup" onclick="window.pickupVehicle('${v.vehicle_code}')"><span>🚗</span> استلام</button>`;
            }
           
            if (v.can_return) {
              html += `<button class="btn btn-return" onclick="window.returnVehicle('${v.vehicle_code}')"><span>↩️</span> إرجاع</button>`;
            }
           
            if (v.can_open_form) {
              html += `<button class="btn btn-form" onclick="window.openMovementForm('${v.vehicle_code}')"><span>📝</span> نموذج حركة</button>`;
            }
           
            html += '</div>'; // vehicle-actions
            html += '</div>'; // vehicle-card
        });
   
        if (vehiclesContainer) vehiclesContainer.innerHTML = html;
    } catch (e) {
        console.error("FATAL RENDERING ERROR:", e);
        if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>خطأ في عرض البيانات (R-100)</h3><p>حدث خطأ أثناء محاولة بناء البطاقات. يرجى مراجعة Console.</p></div>`;
    }
  }
  // Pickup/Return functions (كما هو)
  window.pickupVehicle = async function(vehicleCode) {
    if (!confirm(`هل تريد استلام المركبة ${vehicleCode}؟`)) { return; }
    const empId = currentSession?.user?.emp_id;
    if (!empId) { alert('خطأ: لا يوجد رمز وظيفي'); return; }
    const fd = new FormData();
    fd.append('vehicle_code', vehicleCode);
    fd.append('operation_type', 'pickup');
    fd.append('performed_by', empId);
   
    const r = await fetchJson(API_ADD_MOVEMENT, { method: 'POST', body: fd });
    if (r.ok && r.json && r.json.success) { alert('تم استلام المركبة بنجاح'); loadVehicles(); }
    else { alert('فشل استلام المركبة: ' + (r.json?.message || r.text || 'خطأ غير معروف')); }
  };
  window.returnVehicle = async function(vehicleCode) {
    if (!confirm(`هل تريد إرجاع المركبة ${vehicleCode}؟`)) { return; }
    const empId = currentSession?.user?.emp_id;
    if (!empId) { alert('خطأ: لا يوجد رمز وظيفي'); return; }
    const fd = new FormData();
    fd.append('vehicle_code', vehicleCode);
    fd.append('operation_type', 'return');
    fd.append('performed_by', empId);
   
    const r = await fetchJson(API_ADD_MOVEMENT, { method: 'POST', body: fd });
    if (r.ok && r.json && r.json.success) { alert('تم إرجاع المركبة بنجاح'); loadVehicles(); }
    else { alert('فشل إرجاع المركبة: ' + (r.json?.message || r.text || 'خطأ غير معروف')); }
  };
  // إضافة دالة فتح النموذج
  window.openMovementForm = function(vehicleCode) {
    const url = `/vehicle_management/public/add_vehicle_movements.html?vehicle_code=${encodeURIComponent(vehicleCode)}`;
    window.open(url, '_blank', 'width=600,height=400');
  };
  // Initialize
  async function init() {
    await fetchJson(API_SESSION_INIT, { method: 'GET' }).catch(e => console.error('Session init error:', e));
    const session = await sessionCheck();
    if (!session) { return; }
   
    await getPermissions(); // جلب الصلاحيات
    await loadReferences();
    await loadVehicles();
   
    // Event listeners
    if (searchInput) searchInput.addEventListener('input', debounce(() => { loadVehicles(); }, 500));
   
    if (departmentFilter) departmentFilter.addEventListener('change', () => {
        const deptId = departmentFilter.value;
        const filteredSections = references.sections.filter(s => String(s.department_id ?? '') === String(deptId));
        populateFilter(sectionFilter, filteredSections, 'جميع الأقسام');
        if (divisionFilter) divisionFilter.innerHTML = '<option value="">جميع الشعب</option>';
        loadVehicles();
    });
   
    if (sectionFilter) sectionFilter.addEventListener('change', () => {
        const secId = sectionFilter.value;
        const filteredDivisions = references.divisions.filter(d => String(d.section_id ?? '') === String(secId));
        populateFilter(divisionFilter, filteredDivisions, 'جميع الشعب');
        loadVehicles();
    });
   
    if (divisionFilter) divisionFilter.addEventListener('change', () => { loadVehicles(); });
    if (statusFilter) statusFilter.addEventListener('change', () => { loadVehicles(); });
  }
  // Debounce helper
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
  document.addEventListener('DOMContentLoaded', init);
})();
