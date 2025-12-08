(function () {
  'use strict';
  
  // API Endpoints
  const API_SESSION_INIT = '/vehicle_management/api/config/session.php?init=1';
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
  const API_PERMISSIONS = '/vehicle_management/api/permissions/get_permissions.php';
  const API_REFERENCES = '/vehicle_management/api/helper/get_references.php';
  const API_VEHICLES = '/vehicle_management/api/vehicle/get_vehicle_movements.php';
  const API_ADD_MOVEMENT = '/vehicle_management/api/vehicle/add_vehicle_movements.php';
  const API_RANDOM_ASSIGNMENT = '/vehicle_management/api/vehicle/random_assignment.php';
  
  // DOM elements
  const searchInput = document.getElementById('searchInput');
  const departmentFilter = document.getElementById('departmentFilter');
  const sectionFilter = document.getElementById('sectionFilter');
  const divisionFilter = document.getElementById('divisionFilter');
  const statusFilter = document.getElementById('statusFilter');
  const checkoutStatusFilter = document.getElementById('checkoutStatusFilter');
  const vehicleTypeFilter = document.getElementById('vehicleTypeFilter');
  const movementTypeFilter = document.getElementById('movementTypeFilter');
  const vehiclesContainer = document.getElementById('vehiclesContainer');
  const loadingMsg = document.getElementById('loadingMsg');
  const loggedUserEl = document.getElementById('loggedUser');
  const orgNameEl = document.getElementById('orgName');
  const randomAssignmentBtn = document.getElementById('randomAssignmentBtn');
  const adminReturnBtn = document.getElementById('adminReturnBtn');
  const vehicleCountEl = document.getElementById('vehicleCount');
  
  // State
  let currentSession = null;
  let permissions = {};
  let references = { departments: [], sections: [], divisions: [] };
  let userHasVehicleCheckedOut = false;
  let userHasPrivateVehicle = false;
  let recentlyAssignedVehicles = [];
  let allVehicles = [];
  
  // Fetch helper
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
        console.error('JSON parse error:', e, 'Raw text:', text.substring(0, 500));
      }
      console.log(`Fetch ${url}: status ${res.status}, ok ${res.ok}`);
      return { ok: res.ok, status: res.status, json, text, headers: res.headers };
    } catch (e) {
      console.error('Fetch error for', url, e);
      return { ok: false, status: 0, json: null, text: null, error: e };
    }
  }
  
  // Session check
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
  
  // Load references
  async function loadReferences() {
    const res = await fetchJson(`${API_REFERENCES}?lang=ar`, { method: 'GET' });
    if (res.ok && res.json) {
      references.departments = res.json.departments || [];
      references.sections = res.json.sections || [];
      references.divisions = res.json.divisions || [];
      populateFilter(departmentFilter, references.departments, 'جميع الإدارات');
      if (statusFilter) statusFilter.value = '';
    } else {
      console.error('References load failed');
    }
    return references;
  }
  
  // Populate filter dropdown
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
  
  // Load vehicles
  async function loadVehicles() {
    const q = searchInput ? searchInput.value.trim() : '';
    const deptId = departmentFilter?.value || '';
    const secId = sectionFilter?.value || '';
    const divId = divisionFilter?.value || '';
    const status = statusFilter?.value || '';
    
    if (loadingMsg) loadingMsg.style.display = 'block';
    if (vehiclesContainer) vehiclesContainer.innerHTML = '';
    
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (deptId) params.append('department_id', deptId);
    if (secId) params.append('section_id', secId);
    if (divId) params.append('division_id', divId);
    if (status) params.append('status', status);
    
    const apiUrl = `${API_VEHICLES}?${params.toString()}`;
    console.log('Loading vehicles from:', apiUrl);
    const r = await fetchJson(apiUrl, { method: 'GET' });
    
    if (loadingMsg) loadingMsg.style.display = 'none';
    
    if (!r.ok || !r.json || !r.json.success) {
      const errorMsg = r.json?.message || r.text || 'خطأ في الاتصال';
      console.error('Load vehicles error:', { status: r.status, text: r.text?.substring(0, 200), json: r.json });
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>فشل التحميل</h3><p>${errorMsg}</p><p>تحقق من Console للتفاصيل.</p></div>`;
      return;
    }
    
    const vehicles = r.json.vehicles || [];
    allVehicles = vehicles; // Store all vehicles
    permissions = r.json.permissions || {};
    userHasVehicleCheckedOut = r.json.user_has_vehicle_checked_out || false;
    userHasPrivateVehicle = r.json.user_has_private_vehicle || false;
    recentlyAssignedVehicles = r.json.recently_assigned_vehicles || [];
    
    // Show/hide random assignment button
    if (randomAssignmentBtn) {
      const shouldShowRandom = !userHasVehicleCheckedOut && (permissions.can_assign_vehicle || permissions.can_override_department);
      randomAssignmentBtn.style.display = shouldShowRandom ? 'inline-block' : 'none';
    }
    
    // Show/hide admin return button
    if (adminReturnBtn) {
      const isAdmin = permissions.is_admin || permissions.can_self_assign_vehicle;
      adminReturnBtn.style.display = isAdmin ? 'inline-block' : 'none';
    }
    
    // عرض تحذير إذا كان لدى المستخدم سيارة مستلمة
    if (userHasVehicleCheckedOut && !permissions.can_self_assign_vehicle) {
      showWarningMessage();
    }
    
    if (vehicles.length === 0) {
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>لا توجد مركبات</h3><p>تحقق من الفلاتر أو الصلاحيات.</p></div>`;
      if (vehicleCountEl) vehicleCountEl.textContent = 'عدد المركبات: 0';
      return;
    }
    
    applyClientSideFilters();
    console.log('Loaded', vehicles.length, 'vehicles');
  }
  
  // عرض تحذير للمستخدم بأن لديه سيارة مستلمة
  function showWarningMessage() {
    // إزالة أي تحذير سابق
    const existingWarning = document.querySelector('.warning-message');
    if (existingWarning) existingWarning.remove();
    
    const warningDiv = document.createElement('div');
    warningDiv.className = 'warning-message';
    warningDiv.innerHTML = `
      <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 12px; margin: 15px 0; color: #92400e;">
        <strong>⚠️ تنبيه:</strong> لديك سيارة مستلمة حالياً. يجب إرجاعها قبل استلام سيارة جديدة.
      </div>
    `;
    
    const container = document.querySelector('.filter-controls') || document.querySelector('.search-controls');
    if (container) {
      container.parentNode.insertBefore(warningDiv, container.nextSibling);
    }
  }
  
  // عرض زر القرعة العشوائية
  function applyClientSideFilters() {
    let filtered = allVehicles.slice();
    
    // Filter by checkout status
    const checkoutStatus = checkoutStatusFilter ? checkoutStatusFilter.value : '';
    if (checkoutStatus === 'available') {
      filtered = filtered.filter(v => v.availability_status === 'available');
    } else if (checkoutStatus === 'checked_out') {
      filtered = filtered.filter(v => v.availability_status !== 'available');
    }
    
    // Filter by vehicle type
    const vehicleType = vehicleTypeFilter ? vehicleTypeFilter.value : '';
    if (vehicleType) {
      filtered = filtered.filter(v => v.vehicle_mode === vehicleType);
    }
    
    // Filter by movement type (availability for pickup/return)
    const movementType = movementTypeFilter ? movementTypeFilter.value : '';
    if (movementType === 'pickup') {
      filtered = filtered.filter(v => v.can_pickup);
    } else if (movementType === 'return') {
      filtered = filtered.filter(v => v.can_return);
    }
    
    // Update vehicle count
    if (vehicleCountEl) {
      vehicleCountEl.textContent = `عدد المركبات: ${filtered.length}`;
    }
    
    renderVehicleCards(filtered);
  }
  
  // عرض زر القرعة العشوائية (legacy - now handled in loadVehicles)
  function showRandomAssignmentButton() {
    // إزالة أي زر سابق
    const existingButton = document.querySelector('.random-assignment-btn');
    if (existingButton) existingButton.remove();
    
    const randomButton = document.createElement('button');
    randomButton.className = 'btn btn-random random-assignment-btn';
    randomButton.innerHTML = '🎲 سحب عشوائي لسيارة';
    randomButton.style.backgroundColor = '#8B5CF6';
    randomButton.style.color = 'white';
    randomButton.style.border = 'none';
    randomButton.style.padding = '10px 20px';
    randomButton.style.borderRadius = '6px';
    randomButton.style.cursor = 'pointer';
    randomButton.style.marginLeft = '10px';
    randomButton.style.fontWeight = 'bold';
    
    randomButton.addEventListener('click', async function() {
      if (!confirm('هل تريد سحب سيارة عشوائية؟ سيتم تعيين سيارة لك بشكل عشوائي.')) return;
      
      const r = await fetchJson(API_RANDOM_ASSIGNMENT, { method: 'POST' });
      if (r.ok && r.json) {
        if (r.json.success) {
          alert(r.json.message + '\n\nتفاصيل السيارة:\n' +
                'رمز المركبة: ' + r.json.vehicle.code + '\n' +
                'نوع المركبة: ' + r.json.vehicle.type + '\n' +
                'اسم السائق: ' + r.json.vehicle.driver_name + '\n' +
                'هاتف السائق: ' + r.json.vehicle.driver_phone);
          loadVehicles(); // إعادة تحميل القائمة
        } else {
          // Display error message from JSON response
          alert('فشل السحب العشوائي: ' + (r.json.message || 'خطأ غير معروف'));
        }
      } else {
        const errorMsg = r.json?.message || r.text || 'خطأ في الاتصال بالخادم';
        alert(errorMsg.includes('خطأ') ? errorMsg : 'خطأ في الاتصال بالخادم: ' + errorMsg);
      }
    });
    
    const filterControls = document.querySelector('.filter-controls');
    if (filterControls) {
      filterControls.appendChild(randomButton);
    }
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
  
  // Translate vehicle mode
  function translateVehicleMode(mode) {
    const map = {
      private: 'خاصة',
      shift: 'ورديات'
    };
    return map[mode] || mode;
  }
  
  // Translate availability status
  function translateAvailabilityStatus(status) {
    const map = {
      'private_unavailable': 'خاصة - غير متاحة',
      'available': 'متاحة للاستلام',
      'checked_out_by_me': 'مستلمة من قبلك',
      'checked_out_by_other': 'مستلمة من آخر'
    };
    return map[status] || status;
  }
  
  // Render vehicle cards
  function renderVehicleCards(vehicles) {
    let html = '';
    try {
      vehicles.forEach(v => {
        const statusClass = v.availability_status === 'available' ? 'available' : 
                            (v.availability_status === 'checked_out_by_me' ? 'checked-out-by-me' : 
                            (v.availability_status === 'private_unavailable' ? 'private-unavailable' : 'checked-out'));
        
        const statusText = translateAvailabilityStatus(v.availability_status);
        const statusBadgeClass = v.availability_status === 'available' ? 'status-available' : 
                                 (v.availability_status === 'checked_out_by_me' ? 'status-checked-out-by-me' : 
                                 (v.availability_status === 'private_unavailable' ? 'status-private' : 'status-checked-out-by-other'));
        
        html += `<div class="vehicle-card ${statusClass}" data-vehicle-id="${v.id}">`;
        html += `<div class="vehicle-code">${v.vehicle_code || 'N/A'}</div>`;
        
        // إضافة رمز خاص إذا كانت السيارة خاصة
        if (v.vehicle_mode === 'private') {
          html += `<div style="position: absolute; top: 15px; right: 15px; background: #6D28D9; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">خاصة</div>`;
        } else {
          html += `<div style="position: absolute; top: 15px; right: 15px; background: #059669; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">ورديات</div>`;
        }
        
        html += '<div class="vehicle-info">';
        
        const fields = [
          { label: 'النوع', key: 'type' },
          { label: 'سنة الصنع', key: 'manufacture_year' },
          { label: 'السائق', key: 'driver_name' },
          { label: 'الهاتف', key: 'driver_phone' },
          { label: 'الإدارة', key: 'department_name' },
          { label: 'القسم', key: 'section_name' },
          { label: 'الشعبة', key: 'division_name' },
          { label: 'وضع الاستخدام', key: 'vehicle_mode', translator: translateVehicleMode },
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
        html += '</div>';
        
        html += `<div class="vehicle-status-badge ${statusBadgeClass}">${statusText}</div>`;
        
        html += '<div class="vehicle-actions">';
        
        // تحديد الأزرار المتاحة
        if (v.can_pickup && !userHasVehicleCheckedOut) {
          html += `<button class="btn btn-pickup" onclick="window.pickupVehicle('${v.vehicle_code}')"><span>🚗</span> استلام</button>`;
        } else if (v.availability_status === 'available' && userHasVehicleCheckedOut && !permissions.can_assign_vehicle) {
          html += `<button class="btn btn-disabled" disabled><span>🚫</span> لديك سيارة مستلمة</button>`;
        }
        
        if (v.can_return) {
          html += `<button class="btn btn-return" onclick="window.returnVehicle('${v.vehicle_code}')"><span>↩️</span> إرجاع</button>`;
        }
        
        if (v.can_open_form) {
          html += `<button class="btn btn-form" onclick="window.openMovementForm('${v.vehicle_code}')"><span>📝</span> نموذج حركة</button>`;
        }
        
        html += '</div>';
        html += '</div>';
      });
      
      if (vehiclesContainer) vehiclesContainer.innerHTML = html;
    } catch (e) {
      console.error("FATAL RENDERING ERROR:", e);
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>خطأ في عرض البيانات</h3><p>حدث خطأ أثناء محاولة بناء البطاقات.</p></div>`;
    }
  }
  
  // Pickup vehicle
  window.pickupVehicle = async function(vehicleCode) {
    if (!confirm(`هل تريد استلام المركبة ${vehicleCode}؟`)) return;
    
    const empId = currentSession?.user?.emp_id;
    if (!empId) {
      alert('خطأ: لا يوجد رمز وظيفي');
      return;
    }
    
    // التحقق مرة أخرى إذا كان لدى المستخدم سيارة مستلمة
    if (!permissions.can_assign_vehicle && userHasVehicleCheckedOut) {
      alert('لا يمكنك استلام سيارة جديدة لأن لديك سيارة مستلمة حالياً. يرجى إرجاع السيارة أولاً.');
      return;
    }
    
    // التحقق من عدم استلام نفس السيارة في آخر 24 ساعة
    if (recentlyAssignedVehicles.includes(vehicleCode) && !permissions.can_assign_vehicle) {
      alert('لا يمكنك استلام نفس السيارة خلال 24 ساعة من آخر استلام. يرجى اختيار سيارة أخرى.');
      return;
    }
    
    const fd = new FormData();
    fd.append('vehicle_code', vehicleCode);
    fd.append('operation_type', 'pickup');
    fd.append('performed_by', empId);
    
    const r = await fetchJson(API_ADD_MOVEMENT, { method: 'POST', body: fd });
    if (r.ok && r.json && r.json.success) {
      alert('تم استلام المركبة بنجاح');
      loadVehicles();
    } else {
      alert('فشل استلام المركبة: ' + (r.json?.message || r.text || 'خطأ غير معروف'));
    }
  };
  
  // Return vehicle
  window.returnVehicle = async function(vehicleCode) {
    if (!confirm(`هل تريد إرجاع المركبة ${vehicleCode}؟`)) return;
    
    const empId = currentSession?.user?.emp_id;
    if (!empId) {
      alert('خطأ: لا يوجد رمز وظيفي');
      return;
    }
    
    const fd = new FormData();
    fd.append('vehicle_code', vehicleCode);
    fd.append('operation_type', 'return');
    fd.append('performed_by', empId);
    
    const r = await fetchJson(API_ADD_MOVEMENT, { method: 'POST', body: fd });
    if (r.ok && r.json && r.json.success) {
      alert('تم إرجاع المركبة بنجاح');
      loadVehicles();
    } else {
      alert('فشل إرجاع المركبة: ' + (r.json?.message || r.text || 'خطأ غير معروف'));
    }
  };
  
  // Open movement form
  window.openMovementForm = function(vehicleCode) {
    const url = `/vehicle_management/public/add_vehicle_movements.html?vehicle_code=${encodeURIComponent(vehicleCode)}`;
    window.open(url, '_blank', 'width=600,height=400');
  };
  
  // Initialize
  async function init() {
    await fetchJson(API_SESSION_INIT, { method: 'GET' }).catch(e => console.error('Session init error:', e));
    const session = await sessionCheck();
    if (!session) return;
    
    await loadReferences();
    await loadVehicles();
    
    // Event listeners
    if (searchInput) searchInput.addEventListener('input', debounce(() => loadVehicles(), 500));
    
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
    
    if (divisionFilter) divisionFilter.addEventListener('change', () => loadVehicles());
    if (statusFilter) statusFilter.addEventListener('change', () => loadVehicles());
    
    // Client-side filter event listeners
    if (checkoutStatusFilter) checkoutStatusFilter.addEventListener('change', () => applyClientSideFilters());
    if (vehicleTypeFilter) vehicleTypeFilter.addEventListener('change', () => applyClientSideFilters());
    if (movementTypeFilter) movementTypeFilter.addEventListener('change', () => applyClientSideFilters());
    
    // Random assignment button click
    if (randomAssignmentBtn) {
      randomAssignmentBtn.addEventListener('click', async () => {
        if (!confirm('هل تريد سحب سيارة عشوائية؟ سيتم تعيين سيارة لك بشكل عشوائي.')) return;
        
        const r = await fetchJson(API_RANDOM_ASSIGNMENT, { method: 'POST' });
        if (r.ok && r.json) {
          if (r.json.success) {
            alert(r.json.message + '\n\nتفاصيل السيارة:\n' +
                  'رمز المركبة: ' + r.json.vehicle.code + '\n' +
                  'نوع المركبة: ' + r.json.vehicle.type + '\n' +
                  'اسم السائق: ' + r.json.vehicle.driver_name + '\n' +
                  'هاتف السائق: ' + r.json.vehicle.driver_phone);
            loadVehicles(); // إعادة تحميل القائمة
          } else {
            alert('فشل السحب العشوائي: ' + (r.json.message || 'خطأ غير معروف'));
          }
        } else {
          const errorMsg = r.json?.message || r.text || 'خطأ في الاتصال بالخادم';
          alert(errorMsg.includes('خطأ') ? errorMsg : 'خطأ في الاتصال بالخادم: ' + errorMsg);
        }
      });
    }
    
    // Admin return button click
    if (adminReturnBtn) {
      adminReturnBtn.addEventListener('click', () => {
        window.open('/vehicle_management/public/add_vehicle_movements.html', '_blank', 'width=800,height=600');
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
