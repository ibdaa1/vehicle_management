// vehicle_management/assets/js/vehicle_movements.js
// Card-based vehicle selection for pickup/return
(function () {
  'use strict';

  // API Endpoints
  const API_SESSION_INIT = '/vehicle_management/api/config/session.php?init=1';
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
  const API_PERMISSIONS = '/vehicle_management/api/permissions/get_permissions.php';
  const API_REFERENCES = '/vehicle_management/api/helper/get_references.php';
  const API_VEHICLES = '/vehicle_management/api/vehicle/get_vehicle_movements.php';
  const API_VEHICLE_LIST = '/vehicle_management/api/vehicle/list.php';

  // DOM elements
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
  let currentPermissions = null;
  let references = { departments: [], sections: [], divisions: [] };

  // Fetch helper with credentials
  async function fetchJson(url, opts = {}) {
    opts = Object.assign({}, opts);
    opts.credentials = 'include';
    if (!opts.headers) opts.headers = {};
    opts.headers['Accept'] = 'application/json';
    opts.headers['X-Requested-With'] = 'XMLHttpRequest';
    
    const token = localStorage.getItem('api_token');
    if (token) {
      opts.headers['Authorization'] = `******;
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

  // Initialize session
  async function initSession() {
    try {
      await fetchJson(API_SESSION_INIT, { method: 'GET' });
    } catch (e) {
      console.error('Session init error:', e);
    }
  }

  // Session check
  async function sessionCheck() {
    const r = await fetchJson(API_SESSION, { method: 'GET' });
    
    if (!r.ok || !r.json || !r.json.success) {
      vehiclesContainer.innerHTML = '<div class="empty-state"><h3>غير مصرح</h3><p>يرجى <a href="/vehicle_management/public/login.html">تسجيل الدخول</a></p></div>';
      return null;
    }
    
    currentSession = r.json;
    if (loggedUserEl) loggedUserEl.textContent = `${r.json.user.username || ''} (${r.json.user.emp_id || ''})`;
    if (orgNameEl) orgNameEl.textContent = r.json.user.orgName || 'HCS Department';
    
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

  // Load references
  async function loadReferences() {
    const lang = 'ar';
    const res = await fetchJson(`${API_REFERENCES}?lang=${lang}`, { method: 'GET' });
    
    if (res.ok && res.json) {
      references.departments = res.json.departments || [];
      references.sections = res.json.sections || [];
      references.divisions = res.json.divisions || [];
      
      // Populate filters
      populateFilter(departmentFilter, references.departments, 'جميع الإدارات');
    }
    
    return references;
  }

  // Populate filter dropdown
  function populateFilter(select, items, placeholder) {
    if (!select) return;
    select.innerHTML = `<option value="">${placeholder}</option>`;
    
    (items || []).forEach(it => {
      const id = String(it.id ?? it.department_id ?? it.section_id ?? it.division_id ?? '');
      const label = it.name_ar || it.name || id;
      const o = document.createElement('option');
      o.value = id;
      o.textContent = label;
      select.appendChild(o);
    });
  }

  // Load vehicles
  async function loadVehicles() {
    const q = searchInput.value.trim();
    const deptId = departmentFilter.value;
    const secId = sectionFilter.value;
    const divId = divisionFilter.value;
    const status = statusFilter.value;
    
    if (loadingMsg) loadingMsg.style.display = 'block';
    vehiclesContainer.innerHTML = '';
    
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (deptId) params.append('department_id', deptId);
    if (secId) params.append('section_id', secId);
    if (divId) params.append('division_id', divId);
    if (status) params.append('status', status);
    
    const r = await fetchJson(`${API_VEHICLES}?${params.toString()}`, { method: 'GET' });
    
    if (loadingMsg) loadingMsg.style.display = 'none';
    
    if (!r.ok || !r.json || !r.json.success) {
      vehiclesContainer.innerHTML = '<div class="empty-state"><h3>فشل التحميل</h3><p>' + (r.json?.message || 'خطأ في الاتصال') + '</p></div>';
      return;
    }
    
    const vehicles = r.json.vehicles || [];
    
    if (vehicles.length === 0) {
      vehiclesContainer.innerHTML = '<div class="empty-state"><h3>لا توجد مركبات</h3><p>لا توجد مركبات متاحة حسب معايير البحث</p></div>';
      return;
    }
    
    renderVehicleCards(vehicles);
  }

  // Render vehicle cards
  function renderVehicleCards(vehicles) {
    let html = '';
    
    vehicles.forEach(v => {
      const statusClass = v.availability_status === 'available' ? 'available' : 
                         v.availability_status === 'checked_out_by_me' ? 'checked-out' : 
                         'unavailable';
      
      const statusText = v.availability_status === 'available' ? 'متاحة للاستلام' :
                        v.availability_status === 'checked_out_by_me' ? 'مستلمة من قبلك' :
                        'غير متاحة';
      
      const statusBadgeClass = v.availability_status === 'available' ? 'status-available' :
                               v.availability_status === 'checked_out_by_me' ? 'status-checked-out-by-me' :
                               'status-checked-out-by-other';
      
      html += `<div class="vehicle-card ${statusClass}" data-vehicle-id="${v.id}">`;
      html += `<div class="vehicle-code">${v.vehicle_code || 'N/A'}</div>`;
      
      html += '<div class="vehicle-info">';
      
      if (v.type) {
        html += '<div class="vehicle-info-row">';
        html += '<span class="info-label">النوع</span>';
        html += `<span class="info-value">${v.type}</span>`;
        html += '</div>';
      }
      
      if (v.manufacture_year) {
        html += '<div class="vehicle-info-row">';
        html += '<span class="info-label">سنة الصنع</span>';
        html += `<span class="info-value">${v.manufacture_year}</span>`;
        html += '</div>';
      }
      
      if (v.driver_name) {
        html += '<div class="vehicle-info-row">';
        html += '<span class="info-label">السائق</span>';
        html += `<span class="info-value">${v.driver_name}</span>`;
        html += '</div>';
      }
      
      if (v.driver_phone) {
        html += '<div class="vehicle-info-row">';
        html += '<span class="info-label">الهاتف</span>';
        html += `<span class="info-value">${v.driver_phone}</span>`;
        html += '</div>';
      }
      
      if (v.department_name) {
        html += '<div class="vehicle-info-row">';
        html += '<span class="info-label">الإدارة</span>';
        html += `<span class="info-value">${v.department_name}</span>`;
        html += '</div>';
      }
      
      if (v.section_name) {
        html += '<div class="vehicle-info-row">';
        html += '<span class="info-label">القسم</span>';
        html += `<span class="info-value">${v.section_name}</span>`;
        html += '</div>';
      }
      
      if (v.status) {
        html += '<div class="vehicle-info-row">';
        html += '<span class="info-label">حالة المركبة</span>';
        html += `<span class="info-value">${translateVehicleStatus(v.status)}</span>`;
        html += '</div>';
      }
      
      html += '</div>'; // vehicle-info
      
      html += `<div class="vehicle-status-badge ${statusBadgeClass}">${statusText}</div>`;
      
      html += '<div class="vehicle-actions">';
      
      if (v.can_pickup) {
        html += `<button class="btn btn-pickup" onclick="window.pickupVehicle('${v.vehicle_code}')">
                   <span>🚗</span> استلام
                 </button>`;
      }
      
      if (v.can_return) {
        html += `<button class="btn btn-return" onclick="window.returnVehicle('${v.vehicle_code}')">
                   <span>↩️</span> إرجاع
                 </button>`;
      }
      
      html += '</div>'; // vehicle-actions
      html += '</div>'; // vehicle-card
    });
    
    vehiclesContainer.innerHTML = html;
  }

  // Translate vehicle status
  function translateVehicleStatus(status) {
    const map = {
      operational: 'قيد التشغيل',
      maintenance: 'صيانة',
      out_of_service: 'خارج الخدمة'
    };
    return map[status] || status;
  }

  // Pickup vehicle
  window.pickupVehicle = async function(vehicleCode) {
    if (!confirm(`هل تريد استلام المركبة ${vehicleCode}؟`)) {
      return;
    }
    
    const empId = currentSession?.user?.emp_id;
    if (!empId) {
      alert('خطأ: لا يوجد رمز وظيفي');
      return;
    }
    
    // Create movement record
    const fd = new FormData();
    fd.append('vehicle_code', vehicleCode);
    fd.append('operation_type', 'pickup');
    fd.append('performed_by', empId);
    fd.append('movement_date', new Date().toISOString().split('T')[0]);
    
    const r = await fetchJson('/vehicle_management/api/vehicle/Vehicle_Maintenance.php', {
      method: 'POST',
      body: fd
    });
    
    if (r.ok && r.json && r.json.success) {
      alert('تم استلام المركبة بنجاح');
      loadVehicles();
    } else {
      alert('فشل استلام المركبة: ' + (r.json?.message || 'خطأ'));
    }
  };

  // Return vehicle
  window.returnVehicle = async function(vehicleCode) {
    if (!confirm(`هل تريد إرجاع المركبة ${vehicleCode}؟`)) {
      return;
    }
    
    const empId = currentSession?.user?.emp_id;
    if (!empId) {
      alert('خطأ: لا يوجد رمز وظيفي');
      return;
    }
    
    // Create movement record
    const fd = new FormData();
    fd.append('vehicle_code', vehicleCode);
    fd.append('operation_type', 'return');
    fd.append('performed_by', empId);
    fd.append('movement_date', new Date().toISOString().split('T')[0]);
    
    const r = await fetchJson('/vehicle_management/api/vehicle/Vehicle_Maintenance.php', {
      method: 'POST',
      body: fd
    });
    
    if (r.ok && r.json && r.json.success) {
      alert('تم إرجاع المركبة بنجاح');
      loadVehicles();
    } else {
      alert('فشل إرجاع المركبة: ' + (r.json?.message || 'خطأ'));
    }
  };

  // Initialize
  async function init() {
    // Initialize session first
    await initSession();
    
    // Check session
    const session = await sessionCheck();
    if (!session) return;
    
    // Get permissions
    await getPermissions();
    
    // Load references
    await loadReferences();
    
    // Load vehicles
    await loadVehicles();
    
    // Event listeners
    if (searchInput) {
      searchInput.addEventListener('input', debounce(() => {
        loadVehicles();
      }, 500));
    }
    
    if (departmentFilter) {
      departmentFilter.addEventListener('change', () => {
        const deptId = departmentFilter.value;
        const filteredSections = references.sections.filter(s => 
          String(s.department_id ?? '') === String(deptId)
        );
        populateFilter(sectionFilter, filteredSections, 'جميع الأقسام');
        divisionFilter.innerHTML = '<option value="">جميع الشعب</option>';
        loadVehicles();
      });
    }
    
    if (sectionFilter) {
      sectionFilter.addEventListener('change', () => {
        const secId = sectionFilter.value;
        const filteredDivisions = references.divisions.filter(d => 
          String(d.section_id ?? '') === String(secId)
        );
        populateFilter(divisionFilter, filteredDivisions, 'جميع الشعب');
        loadVehicles();
      });
    }
    
    if (divisionFilter) {
      divisionFilter.addEventListener('change', () => {
        loadVehicles();
      });
    }
    
    if (statusFilter) {
      statusFilter.addEventListener('change', () => {
        loadVehicles();
      });
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

  document.addEventListener('DOMContentLoaded', init);
})();
