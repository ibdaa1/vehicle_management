// vehicle_management/assets/js/add_Vehicles.js
// Production-ready vehicle listing with search, pagination, and permission-aware UI
(function () {
  'use strict';

  // API Endpoints
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
  const API_PERMISSIONS = '/vehicle_management/api/permissions/get_permissions.php';
  const API_VEHICLE_LIST = '/vehicle_management/api/vehicle/list.php';
  const API_VEHICLE_ADD = '/vehicle_management/api/vehicle/add_Vehicles.php';
  const API_VEHICLE_GET = '/vehicle_management/api/vehicle/get.php';
  const API_VEHICLE_DELETE = '/vehicle_management/api/vehicle/delete.php';
  const API_HELPER = '/vehicle_management/api/helper/get_references.php';

  // DOM elements - Search/List
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const perPageSelect = document.getElementById('perPageSelect');
  const addNewBtn = document.getElementById('addNewBtn');
  const vehiclesList = document.getElementById('vehiclesList');
  const paginationControls = document.getElementById('paginationControls');
  
  // DOM elements - Form
  const vehicleFormCard = document.getElementById('vehicleFormCard');
  const searchCard = document.getElementById('searchCard');
  const vehicleForm = document.getElementById('vehicleForm');
  const vehicleId = document.getElementById('vehicleId');
  const submitBtn = document.getElementById('submitBtn');
  const cancelFormBtn = document.getElementById('cancelFormBtn');
  const formMsg = document.getElementById('formMsg');
  const formTitle = document.getElementById('formTitle');
  
  // Form inputs
  const plateNumber = document.getElementById('plate_number');
  const typeInput = document.getElementById('type');
  const yearInput = document.getElementById('year');
  const vehicleMode = document.getElementById('vehicle_mode');
  const empIdInput = document.getElementById('emp_id');
  const assignedUserId = document.getElementById('assigned_user_id');
  const driverName = document.getElementById('driver_name');
  const driverPhone = document.getElementById('driver_phone');
  const departmentId = document.getElementById('department_id');
  const sectionId = document.getElementById('section_id');
  const divisionId = document.getElementById('division_id');
  const statusInput = document.getElementById('status');
  const notesInput = document.getElementById('notes');
  
  // User info elements
  const loggedUserEl = document.getElementById('loggedUser');
  const orgNameEl = document.getElementById('orgName');

  // State
  let currentSession = null;
  let currentPermissions = null;
  let currentPage = 1;
  let totalPages = 1;
  let totalRecords = 0;
  let references = { departments: [], sections: [], divisions: [] };

  // Fetch helper with credentials
  async function fetchJson(url, opts = {}) {
    opts = Object.assign({}, opts);
    opts.credentials = 'include';
    if (!opts.headers) opts.headers = {};
    opts.headers['Accept'] = 'application/json';
    opts.headers['X-Requested-With'] = 'XMLHttpRequest';
    
    // Check for token in localStorage
    const token = localStorage.getItem('api_token');
    if (token) {
      opts.headers['Authorization'] = `Bearer ${token}`;
    }
    
    try {
      const res = await fetch(url, opts);
      const text = await res.text().catch(() => null);
      let json = null;
      try {
        if (text) json = JSON.parse(text);
      } catch (e) {
        json = null;
      }
      return { ok: res.ok, status: res.status, json, text, headers: res.headers };
    } catch (e) {
      return { ok: false, status: 0, json: null, text: null, error: e };
    }
  }

  // Show message
  function showMsg(text, type = 'info', el = formMsg) {
    if (!el) return;
    const colors = {
      error: '#dc2626',
      success: '#059669',
      info: '#6b7280'
    };
    el.innerHTML = `<div style="color:${colors[type] || colors.info}">${text}</div>`;
  }

  function clearMsg(el = formMsg) {
    if (el) el.innerHTML = '';
  }

  // Session check
  async function sessionCheck() {
    const r = await fetchJson(API_SESSION, { method: 'GET' });
    
    if (!r.ok || !r.json || !r.json.success) {
      showMsg('غير مصرح - يرجى تسجيل الدخول', 'error');
      vehiclesList.innerHTML = '<div class="loading">غير مصرح - يرجى <a href="/vehicle_management/public/login.html">تسجيل الدخول</a></div>';
      if (addNewBtn) addNewBtn.disabled = true;
      return null;
    }
    
    currentSession = r.json;
    if (loggedUserEl) loggedUserEl.textContent = `${r.json.user.username || ''} (ID: ${r.json.user.emp_id || ''})`;
    if (orgNameEl) orgNameEl.textContent = r.json.user.orgName || 'HCS Department';
    if (empIdInput) empIdInput.value = r.json.user.emp_id || '';
    
    return r.json;
  }

  // Get permissions
  async function getPermissions() {
    const r = await fetchJson(API_PERMISSIONS, { method: 'GET' });
    
    if (r.ok && r.json && r.json.success) {
      currentPermissions = r.json.role;
      return r.json.role;
    }
    
    return null;
  }

  // Load references (departments, sections, divisions)
  async function loadReferences() {
    const lang = 'ar';
    const res = await fetchJson(`${API_HELPER}?lang=${lang}`, { method: 'GET' });
    
    let deps = [], secs = [], divs = [];
    
    if (res.ok && res.json) {
      deps = res.json.departments || [];
      secs = res.json.sections || [];
      divs = res.json.divisions || [];
    }
    
    // Try individual requests if needed
    if (!Array.isArray(deps) || deps.length === 0) {
      const rd = await fetchJson(`${API_HELPER}?type=departments&lang=${lang}`);
      deps = (rd.json && rd.json.departments) || [];
    }
    
    if (!Array.isArray(secs) || secs.length === 0) {
      const rs = await fetchJson(`${API_HELPER}?type=sections&lang=${lang}`);
      secs = (rs.json && rs.json.sections) || [];
    }
    
    if (!Array.isArray(divs) || divs.length === 0) {
      const rv = await fetchJson(`${API_HELPER}?type=divisions&lang=${lang}`);
      divs = (rv.json && rv.json.divisions) || [];
    }
    
    references = { departments: deps, sections: secs, divisions: divs };
    return references;
  }

  // Populate select dropdown
  function populateSelect(sel, items, placeholder = 'اختر') {
    if (!sel) return;
    sel.innerHTML = '';
    const op0 = document.createElement('option');
    op0.value = '';
    op0.textContent = placeholder;
    sel.appendChild(op0);
    
    (items || []).forEach(it => {
      const id = String(it.id ?? it.department_id ?? it.section_id ?? it.division_id ?? '');
      const label = it.name_ar || it.name || id;
      const o = document.createElement('option');
      o.value = id;
      o.textContent = label;
      sel.appendChild(o);
    });
  }

  // Setup form selects
  function setupFormSelects() {
    populateSelect(departmentId, references.departments, 'اختر الإدارة');
    
    departmentId.addEventListener('change', function () {
      const dep = this.value || '';
      const filteredSections = references.sections.filter(s => 
        String(s.department_id ?? '') === String(dep)
      );
      populateSelect(sectionId, filteredSections, 'اختر القسم');
      divisionId.innerHTML = '<option value="">اختر الشعبة</option>';
    });
    
    sectionId.addEventListener('change', function () {
      const sec = this.value || '';
      const filteredDivisions = references.divisions.filter(d => 
        String(d.section_id ?? '') === String(sec)
      );
      populateSelect(divisionId, filteredDivisions, 'اختر الشعبة');
    });
  }

  // Load vehicle list
  async function loadVehicles() {
    const q = searchInput.value.trim();
    const status = statusFilter.value;
    const per_page = parseInt(perPageSelect.value) || 30;
    
    vehiclesList.innerHTML = '<div class="loading">جاري التحميل...</div>';
    
    const params = new URLSearchParams({
      page: currentPage,
      per_page: per_page
    });
    
    if (q) params.append('q', q);
    if (status) params.append('status', status);
    
    const r = await fetchJson(`${API_VEHICLE_LIST}?${params.toString()}`, { method: 'GET' });
    
    if (!r.ok || !r.json || !r.json.success) {
      vehiclesList.innerHTML = '<div class="loading" style="color:#dc2626">فشل تحميل القائمة</div>';
      return;
    }
    
    const data = r.json;
    totalRecords = data.total || 0;
    totalPages = Math.ceil(totalRecords / per_page);
    
    renderVehicleTable(data.vehicles || []);
    renderPagination();
  }

  // Render vehicle table
  function renderVehicleTable(vehicles) {
    if (vehicles.length === 0) {
      vehiclesList.innerHTML = '<div class="loading">لا توجد نتائج</div>';
      return;
    }
    
    const canEdit = currentPermissions?.can_edit || false;
    const canDelete = currentPermissions?.can_delete || false;
    const currentUserId = currentSession?.user?.id;
    
    let html = '<div class="table-container"><table><thead><tr>';
    html += '<th>ID</th>';
    html += '<th>رقم المركبة</th>';
    html += '<th>السائق</th>';
    html += '<th>هاتف السائق</th>';
    html += '<th>الرمز الوظيفي</th>';
    html += '<th>الإدارة</th>';
    html += '<th>القسم</th>';
    html += '<th>الشعبة</th>';
    html += '<th>الحالة</th>';
    html += '<th>الإجراءات</th>';
    html += '</tr></thead><tbody>';
    
    vehicles.forEach(v => {
      const isOwner = currentUserId && v.created_by && (parseInt(currentUserId) === parseInt(v.created_by));
      const showEdit = canEdit || isOwner;
      const showDelete = canDelete || isOwner;
      
      html += '<tr>';
      html += `<td>${v.id}</td>`;
      html += `<td>${v.vehicle_code || '-'}</td>`;
      html += `<td>${v.driver_name || '-'}</td>`;
      html += `<td>${v.driver_phone || '-'}</td>`;
      html += `<td>${v.emp_id || '-'}</td>`;
      html += `<td>${v.department_name || '-'}</td>`;
      html += `<td>${v.section_name || '-'}</td>`;
      html += `<td>${v.division_name || '-'}</td>`;
      html += `<td><span class="status-badge status-${v.status || 'operational'}">${translateStatus(v.status)}</span></td>`;
      html += '<td><div class="action-buttons">';
      
      if (showEdit) {
        html += `<button class="btn small ghost" onclick="window.editVehicle(${v.id})">تعديل</button>`;
      }
      if (showDelete) {
        html += `<button class="btn small danger" onclick="window.deleteVehicle(${v.id})">حذف</button>`;
      }
      
      html += '</div></td>';
      html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    vehiclesList.innerHTML = html;
  }

  // Translate status
  function translateStatus(status) {
    const map = {
      operational: 'قيد التشغيل',
      maintenance: 'صيانة',
      out_of_service: 'خارج الخدمة'
    };
    return map[status] || status;
  }

  // Render pagination
  function renderPagination() {
    if (totalPages <= 1) {
      paginationControls.innerHTML = '';
      return;
    }
    
    let html = '<div class="pagination-info">';
    html += `عرض ${totalRecords} نتيجة - صفحة ${currentPage} من ${totalPages}`;
    html += '</div>';
    
    html += '<div class="pagination-buttons">';
    
    if (currentPage > 1) {
      html += `<button class="btn ghost small" onclick="window.goToPage(${currentPage - 1})">السابق</button>`;
    }
    
    // Show page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
      if (i === currentPage) {
        html += `<button class="btn small" disabled>${i}</button>`;
      } else {
        html += `<button class="btn ghost small" onclick="window.goToPage(${i})">${i}</button>`;
      }
    }
    
    if (currentPage < totalPages) {
      html += `<button class="btn ghost small" onclick="window.goToPage(${currentPage + 1})">التالي</button>`;
    }
    
    html += '</div>';
    paginationControls.innerHTML = html;
  }

  // Go to page
  window.goToPage = function (page) {
    currentPage = page;
    loadVehicles();
  };

  // Show form for new vehicle
  function showAddForm() {
    vehicleForm.reset();
    vehicleId.value = '';
    formTitle.textContent = 'إضافة مركبة جديدة';
    empIdInput.value = currentSession?.user?.emp_id || '';
    
    // Populate selects
    populateSelect(departmentId, references.departments, 'اختر الإدارة');
    populateSelect(sectionId, [], 'اختر القسم');
    populateSelect(divisionId, [], 'اختر الشعبة');
    
    searchCard.style.display = 'none';
    vehicleFormCard.style.display = 'block';
    clearMsg();
  }

  // Show form for editing
  window.editVehicle = async function (id) {
    clearMsg();
    vehicleForm.reset();
    vehicleId.value = id;
    formTitle.textContent = 'تعديل مركبة';
    
    const r = await fetchJson(`${API_VEHICLE_GET}?id=${id}`, { method: 'GET' });
    
    if (!r.ok || !r.json || !r.json.success || !r.json.vehicle) {
      alert('فشل تحميل بيانات المركبة');
      return;
    }
    
    const v = r.json.vehicle;
    
    // Fill form
    if (plateNumber) plateNumber.value = v.vehicle_code || '';
    if (typeInput) typeInput.value = v.type || '';
    if (yearInput) yearInput.value = v.manufacture_year || '';
    if (vehicleMode) vehicleMode.value = v.vehicle_mode || 'shift';
    if (empIdInput) empIdInput.value = v.emp_id || '';
    if (assignedUserId) assignedUserId.value = v.assigned_user_id || '';
    if (driverName) driverName.value = v.driver_name || '';
    if (driverPhone) driverPhone.value = v.driver_phone || '';
    if (notesInput) notesInput.value = v.notes || '';
    if (statusInput) statusInput.value = v.status || 'operational';
    
    // Populate and set departments
    populateSelect(departmentId, references.departments, 'اختر الإدارة');
    if (v.department_id) departmentId.value = v.department_id;
    
    // Populate and set sections
    const filteredSections = references.sections.filter(s => 
      String(s.department_id ?? '') === String(v.department_id)
    );
    populateSelect(sectionId, filteredSections, 'اختر القسم');
    if (v.section_id) sectionId.value = v.section_id;
    
    // Populate and set divisions
    const filteredDivisions = references.divisions.filter(d => 
      String(d.section_id ?? '') === String(v.section_id)
    );
    populateSelect(divisionId, filteredDivisions, 'اختر الشعبة');
    if (v.division_id) divisionId.value = v.division_id;
    
    searchCard.style.display = 'none';
    vehicleFormCard.style.display = 'block';
  };

  // Delete vehicle
  window.deleteVehicle = async function (id) {
    if (!confirm('هل أنت متأكد من حذف هذه المركبة؟')) {
      return;
    }
    
    const fd = new FormData();
    fd.append('id', id);
    
    const r = await fetchJson(API_VEHICLE_DELETE, {
      method: 'POST',
      body: fd
    });
    
    if (r.ok && r.json && r.json.success) {
      alert('تم الحذف بنجاح');
      loadVehicles();
    } else {
      alert(r.json?.message || 'فشل الحذف');
    }
  };

  // Cancel form
  function cancelForm() {
    searchCard.style.display = 'block';
    vehicleFormCard.style.display = 'none';
    clearMsg();
  }

  // Submit form
  async function submitForm(e) {
    e.preventDefault();
    clearMsg();
    submitBtn.disabled = true;
    showMsg('جاري الحفظ...', 'info');
    
    const fd = new FormData(vehicleForm);
    
    // Map plate_number to vehicle_code
    if (fd.has('plate_number')) {
      const v = fd.get('plate_number');
      fd.delete('plate_number');
      fd.append('vehicle_code', v);
    }
    
    // Map year to manufacture_year
    if (fd.has('year')) {
      const v = fd.get('year');
      fd.delete('year');
      fd.append('manufacture_year', v);
    }
    
    // Ensure emp_id
    if (!fd.get('emp_id')) {
      fd.set('emp_id', currentSession?.user?.emp_id || '');
    }
    
    try {
      const r = await fetchJson(API_VEHICLE_ADD, {
        method: 'POST',
        body: fd
      });
      
      if (r.ok && r.json && r.json.success) {
        showMsg(r.json.message || 'تم الحفظ بنجاح', 'success');
        setTimeout(() => {
          cancelForm();
          loadVehicles();
        }, 1000);
      } else {
        showMsg(r.json?.message || 'فشل الحفظ', 'error');
      }
    } catch (e) {
      showMsg('خطأ في الاتصال', 'error');
    } finally {
      submitBtn.disabled = false;
    }
  }

  // Initialize
  async function init() {
    // Check session
    const session = await sessionCheck();
    if (!session) return;
    
    // Get permissions
    await getPermissions();
    
    // Load references
    await loadReferences();
    
    // Setup form
    setupFormSelects();
    
    // Load initial list
    await loadVehicles();
    
    // Event listeners
    if (searchInput) {
      searchInput.addEventListener('input', debounce(() => {
        currentPage = 1;
        loadVehicles();
      }, 500));
    }
    
    if (statusFilter) {
      statusFilter.addEventListener('change', () => {
        currentPage = 1;
        loadVehicles();
      });
    }
    
    if (perPageSelect) {
      perPageSelect.addEventListener('change', () => {
        currentPage = 1;
        loadVehicles();
      });
    }
    
    if (addNewBtn) {
      addNewBtn.addEventListener('click', showAddForm);
    }
    
    if (cancelFormBtn) {
      cancelFormBtn.addEventListener('click', cancelForm);
    }
    
    if (vehicleForm) {
      vehicleForm.addEventListener('submit', submitForm);
    }
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

  // Start when DOM ready
  document.addEventListener('DOMContentLoaded', init);
})();
