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
  const htmlRoot = document.documentElement;
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
  let permissions = {};
  let references = { departments: [], sections: [], divisions: [] };
  let userHasVehicleCheckedOut = false;
  let userHasPrivateVehicle = false;
  let recentlyAssignedVehicles = [];
  let userLang = 'ar'; // اللغة الافتراضية
  let translations = {}; // ملف الترجمة المحمّل
  
  // دالة تحميل ملف الترجمة من المسار: /vehicle_management/languages/{lang}_vehicle_movements.json
  async function loadTranslations(lang) {
    const path = `/vehicle_management/languages/${lang}_vehicle_movements.json`;
    try {
      const response = await fetch(path);
      if (!response.ok) {
        console.warn(`Failed to load translations for ${lang}, falling back to empty`);
        translations = {};
        return false;
      }
      translations = await response.json();
      console.log(`Loaded translations for ${lang}:`, translations);
      return true;
    } catch (e) {
      console.error(`Error loading translations for ${lang}:`, e);
      translations = {};
      return false;
    }
  }
  
  // دالة الترجمة: تُرجع النص المقابل للمفتاح، أو المفتاح نفسه إذا لم يُوجد
  function t(key, fallback = null) {
    return translations[key] || fallback || key;
  }
  
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
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>${t('not_authenticated', 'غير مصرح')}</h3><p>${t('please_login', 'يرجى')} <a href="/vehicle_management/public/login.html">${t('login', 'تسجيل الدخول')}</a></p><p>${t('details', 'تفاصيل')}: ${errorMsg}</p></div>`;
      return null;
    }
    currentSession = r.json;
    
    // تحديد لغة المستخدم من preferred_language أو من navigator
    userLang = currentSession.user?.preferred_language || navigator.language?.split('-')[0] || 'ar';
    if (userLang !== 'ar' && userLang !== 'en') userLang = 'ar';
    
    // تحميل ملف الترجمة
    await loadTranslations(userLang);
    
    // تعيين اتجاه الصفحة ولغتها
    if (htmlRoot) {
      htmlRoot.setAttribute('lang', userLang);
      htmlRoot.setAttribute('dir', userLang === 'ar' ? 'rtl' : 'ltr');
    }
    
    // تحديث النصوص في الصفحة بعد تحميل الترجمات
    updatePageTexts();
    
    if (loggedUserEl) loggedUserEl.textContent = `${r.json.user.username || ''} (${r.json.user.emp_id || ''})`;
    return r.json;
  }
  
  // دالة لتحديث النصوص في الصفحة بعد تحميل الترجمات
  function updatePageTexts() {
    // تحديث عنوان المستند
    const docTitle = document.getElementById('docTitle');
    if (docTitle) docTitle.textContent = t('doc_title', userLang === 'ar' ? 'حركة المركبات (استلام وإرجاع)' : 'Vehicle Movements (Pickup & Return)');
    
    // تحديث العناوين
    const pageTitle = document.getElementById('pageTitle');
    const pageSubtitle = document.getElementById('pageSubtitle');
    if (pageTitle) pageTitle.textContent = t('page_header', userLang === 'ar' ? 'لوحة التحكم السريع للمركبات' : 'Quick Vehicle Control Panel');
    if (pageSubtitle) pageSubtitle.textContent = t('page_header_subtitle', userLang === 'ar' ? 'استلام وإرجاع المركبات المتاحة' : 'Pick up and return available vehicles');
    
    // تحديث placeholder للبحث
    if (searchInput) searchInput.placeholder = t('search_input_placeholder', userLang === 'ar' ? 'بحث (رقم المركبة، السائق، النوع...)' : 'Search (vehicle code, driver, type...)');
    
    // تحديث خيارات الفلاتر الافتراضية
    const allStatusText = t('all_statuses', userLang === 'ar' ? 'جميع الحالات التشغيلية' : 'All Operational Statuses');
    const operationalText = t('status_operational', userLang === 'ar' ? 'قيد التشغيل' : 'Operational');
    const maintenanceText = t('status_maintenance', userLang === 'ar' ? 'صيانة' : 'Maintenance');
    const outOfServiceText = t('status_out_of_service', userLang === 'ar' ? 'خارج الخدمة' : 'Out of Service');
    
    if (statusFilter && statusFilter.options.length > 0) {
      statusFilter.options[0].textContent = allStatusText;
      if (statusFilter.options.length > 1) statusFilter.options[1].textContent = operationalText;
      if (statusFilter.options.length > 2) statusFilter.options[2].textContent = maintenanceText;
      if (statusFilter.options.length > 3) statusFilter.options[3].textContent = outOfServiceText;
    }
  }
  
  // Load references - إضافة معامل lang
  async function loadReferences() {
    const res = await fetchJson(`${API_REFERENCES}?lang=${userLang}`, { method: 'GET' });
    if (res.ok && res.json) {
      references.departments = res.json.departments || [];
      references.sections = res.json.sections || [];
      references.divisions = res.json.divisions || [];
      populateFilter(departmentFilter, references.departments, t('all_departments', 'جميع الإدارات'));
      if (statusFilter) statusFilter.value = '';
    } else {
      console.error('References load failed');
    }
    return references;
  }
  
  // Populate filter dropdown - استخدام الحقول المحلية
  function populateFilter(select, items, placeholder) {
    if (!select) return;
    select.innerHTML = `<option value="">${placeholder}</option>`;
    (items || []).forEach(it => {
      const id = String(it.department_id ?? it.section_id ?? it.division_id ?? it.id ?? '');
      // استخدام name المحدد من السيرفر (name_ar أو name_en حسب اللغة) أو name fallback
      const label = it.name || id;
      const o = document.createElement('option');
      o.value = id;
      o.textContent = label;
      select.appendChild(o);
    });
  }
  
  // Load vehicles - إضافة معامل lang
  async function loadVehicles() {
    const q = searchInput ? searchInput.value.trim() : '';
    const deptId = departmentFilter?.value || '';
    const secId = sectionFilter?.value || '';
    const divId = divisionFilter?.value || '';
    const status = statusFilter?.value || '';
    
    if (loadingMsg) {
      loadingMsg.style.display = 'block';
      loadingMsg.textContent = t('loading', 'جاري التحميل...');
    }
    if (vehiclesContainer) vehiclesContainer.innerHTML = '';
    
    const params = new URLSearchParams();
    params.append('lang', userLang); // إضافة معامل اللغة
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
      const errorMsg = r.json?.message || r.text || t('connection_error', 'خطأ في الاتصال');
      console.error('Load vehicles error:', { status: r.status, text: r.text?.substring(0, 200), json: r.json });
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>${t('load_failed', 'فشل التحميل')}</h3><p>${errorMsg}</p><p>${t('check_console', 'تحقق من Console للتفاصيل.')}</p></div>`;
      return;
    }
    
    const vehicles = r.json.vehicles || [];
    permissions = r.json.permissions || {};
    userHasVehicleCheckedOut = r.json.user_has_vehicle_checked_out || false;
    userHasPrivateVehicle = r.json.user_has_private_vehicle || false;
    recentlyAssignedVehicles = r.json.recently_assigned_vehicles || [];
    
    // عرض تحذير إذا كان لدى المستخدم سيارة مستلمة
    if (userHasVehicleCheckedOut && !permissions.can_assign_vehicle) {
      showWarningMessage();
    }
    
    // عرض زر القرعة إذا كان المستخدم مؤهلاً
    if (!userHasVehicleCheckedOut && !userHasPrivateVehicle && permissions.can_self_assign_vehicle) {
      showRandomAssignmentButton();
    }
    
    if (vehicles.length === 0) {
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>${t('no_vehicles', 'لا توجد مركبات')}</h3><p>${t('check_filters', 'تحقق من الفلاتر أو الصلاحيات.')}</p></div>`;
      return;
    }
    
    renderVehicleCards(vehicles);
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
        <strong>⚠️ ${t('warning', 'تنبيه')}:</strong> ${t('vehicle_checked_out_warning', 'لديك سيارة مستلمة حالياً. يجب إرجاعها قبل استلام سيارة جديدة.')}
      </div>
    `;
    
    const container = document.querySelector('.filter-controls') || document.querySelector('.search-controls');
    if (container) {
      container.parentNode.insertBefore(warningDiv, container.nextSibling);
    }
  }
  
  // عرض زر القرعة العشوائية
  function showRandomAssignmentButton() {
    // إزالة أي زر سابق
    const existingButton = document.querySelector('.random-assignment-btn');
    if (existingButton) existingButton.remove();
    
    const randomButton = document.createElement('button');
    randomButton.className = 'btn btn-random random-assignment-btn';
    randomButton.innerHTML = '🎲 ' + t('random_assignment', 'سحب عشوائي لسيارة');
    randomButton.style.backgroundColor = '#8B5CF6';
    randomButton.style.color = 'white';
    randomButton.style.border = 'none';
    randomButton.style.padding = '10px 20px';
    randomButton.style.borderRadius = '6px';
    randomButton.style.cursor = 'pointer';
    randomButton.style.marginLeft = '10px';
    randomButton.style.fontWeight = 'bold';
    
    randomButton.addEventListener('click', async function() {
      if (!confirm(t('random_assignment_confirm', 'هل تريد سحب سيارة عشوائية؟ سيتم تعيين سيارة لك بشكل عشوائي.'))) return;
      
      // إضافة معامل lang إلى API_RANDOM_ASSIGNMENT
      const r = await fetchJson(`${API_RANDOM_ASSIGNMENT}?lang=${userLang}`, { method: 'POST' });
      if (r.ok && r.json) {
        if (r.json.success) {
          // عرض الرسالة من السيرفر (message_en أو message_ar حسب اللغة، أو message fallback)
          const msg = r.json.message || t('assignment_success', 'تم التعيين بنجاح');
          const vehicleInfo = r.json.vehicle || {};
          alert(msg + '\n\n' + t('vehicle_details', 'تفاصيل السيارة:') + '\n' +
                t('vehicle_code', 'رمز المركبة') + ': ' + (vehicleInfo.code || '') + '\n' +
                t('vehicle_type', 'نوع المركبة') + ': ' + (vehicleInfo.type || '') + '\n' +
                t('driver_name', 'اسم السائق') + ': ' + (vehicleInfo.driver_name || '') + '\n' +
                t('driver_phone', 'هاتف السائق') + ': ' + (vehicleInfo.driver_phone || ''));
          loadVehicles(); // إعادة تحميل القائمة
        } else {
          alert(t('assignment_failed', 'فشل السحب العشوائي') + ': ' + (r.json.message || ''));
        }
      } else {
        alert(t('connection_error', 'خطأ في الاتصال بالخادم'));
      }
    });
    
    const filterControls = document.querySelector('.filter-controls');
    if (filterControls) {
      filterControls.appendChild(randomButton);
    }
  }
  
  // Translate vehicle status
  function translateVehicleStatus(status) {
    // استخدام دالة الترجمة بدلاً من الـ hardcoded map
    const key = `status_${status}`;
    const fallbackMap = {
      operational: 'قيد التشغيل',
      maintenance: 'صيانة',
      out_of_service: 'خارج الخدمة'
    };
    return t(key, fallbackMap[status] || status);
  }
  
  // Translate vehicle mode
  function translateVehicleMode(mode) {
    const key = `mode_${mode}`;
    const fallbackMap = {
      private: userLang === 'ar' ? 'خاصة' : 'Private',
      shift: userLang === 'ar' ? 'ورديات' : 'Shift'
    };
    return t(key, fallbackMap[mode] || mode);
  }
  
  // Translate availability status
  function translateAvailabilityStatus(status) {
    const key = `availability_${status}`;
    const fallbackMap = {
      'private_unavailable': userLang === 'ar' ? 'خاصة - غير متاحة' : 'Private - Unavailable',
      'available': userLang === 'ar' ? 'متاحة للاستلام' : 'Available',
      'checked_out_by_me': userLang === 'ar' ? 'مستلمة من قبلك' : 'Checked Out by You',
      'checked_out_by_other': userLang === 'ar' ? 'مستلمة من آخر' : 'Checked Out by Other'
    };
    return t(key, fallbackMap[status] || status);
  }
  
  // Render vehicle cards - استخدام الحقول المحلية department_name, section_name, division_name من السيرفر
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
          html += `<div style="position: absolute; top: 15px; right: 15px; background: #6D28D9; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">${translateVehicleMode('private')}</div>`;
        } else {
          html += `<div style="position: absolute; top: 15px; right: 15px; background: #059669; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">${translateVehicleMode('shift')}</div>`;
        }
        
        html += '<div class="vehicle-info">';
        
        // استخدام أسماء الحقول المترجمة من السيرفر أو fallback
        const fields = [
          { label: t('label_type', 'النوع'), key: 'type' },
          { label: t('label_year', 'سنة الصنع'), key: 'manufacture_year' },
          { label: t('label_driver', 'السائق'), key: 'driver_name' },
          { label: t('label_phone', 'الهاتف'), key: 'driver_phone' },
          { label: t('label_department', 'الإدارة'), key: 'department_name' },
          { label: t('label_section', 'القسم'), key: 'section_name' },
          { label: t('label_division', 'الشعبة'), key: 'division_name' },
          { label: t('label_mode', 'وضع الاستخدام'), key: 'vehicle_mode', translator: translateVehicleMode },
          { label: t('label_status', 'حالة المركبة'), key: 'status', translator: translateVehicleStatus }
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
        
        // تحديد الأزرار المتاحة - استخدام الترجمة
        if (v.can_pickup && !userHasVehicleCheckedOut) {
          html += `<button class="btn btn-pickup" onclick="window.pickupVehicle('${v.vehicle_code}')"><span>🚗</span> ${t('btn_pickup', 'استلام')}</button>`;
        } else if (v.availability_status === 'available' && userHasVehicleCheckedOut && !permissions.can_assign_vehicle) {
          html += `<button class="btn btn-disabled" disabled><span>🚫</span> ${t('btn_already_checked', 'لديك سيارة مستلمة')}</button>`;
        }
        
        if (v.can_return) {
          html += `<button class="btn btn-return" onclick="window.returnVehicle('${v.vehicle_code}')"><span>↩️</span> ${t('btn_return', 'إرجاع')}</button>`;
        }
        
        if (v.can_open_form) {
          html += `<button class="btn btn-form" onclick="window.openMovementForm('${v.vehicle_code}')"><span>📝</span> ${t('btn_movement_form', 'نموذج حركة')}</button>`;
        }
        
        html += '</div>';
        html += '</div>';
      });
      
      if (vehiclesContainer) vehiclesContainer.innerHTML = html;
    } catch (e) {
      console.error("FATAL RENDERING ERROR:", e);
      if (vehiclesContainer) vehiclesContainer.innerHTML = `<div class="empty-state"><h3>${t('error_render', 'خطأ في عرض البيانات')}</h3><p>${t('error_render_desc', 'حدث خطأ أثناء محاولة بناء البطاقات.')}</p></div>`;
    }
  }
  
  // Pickup vehicle - إضافة معامل lang
  window.pickupVehicle = async function(vehicleCode) {
    const confirmMsg = t('pickup_confirm', 'هل تريد استلام المركبة @@؟').replace('@@', vehicleCode);
    if (!confirm(confirmMsg)) return;
    
    const empId = currentSession?.user?.emp_id;
    if (!empId) {
      alert(t('error_no_emp_id', 'خطأ: لا يوجد رمز وظيفي'));
      return;
    }
    
    // التحقق مرة أخرى إذا كان لدى المستخدم سيارة مستلمة
    if (!permissions.can_assign_vehicle && userHasVehicleCheckedOut) {
      alert(t('error_already_checked_out', 'لا يمكنك استلام سيارة جديدة لأن لديك سيارة مستلمة حالياً. يرجى إرجاع السيارة أولاً.'));
      return;
    }
    
    // التحقق من عدم استلام نفس السيارة في آخر 24 ساعة
    if (recentlyAssignedVehicles.includes(vehicleCode) && !permissions.can_assign_vehicle) {
      alert(t('error_recently_assigned', 'لا يمكنك استلام نفس السيارة خلال 24 ساعة من آخر استلام. يرجى اختيار سيارة أخرى.'));
      return;
    }
    
    const fd = new FormData();
    fd.append('vehicle_code', vehicleCode);
    fd.append('operation_type', 'pickup');
    fd.append('performed_by', empId);
    
    // إضافة معامل lang إلى URL
    const r = await fetchJson(`${API_ADD_MOVEMENT}?lang=${userLang}`, { method: 'POST', body: fd });
    if (r.ok && r.json && r.json.success) {
      alert(t('pickup_success', 'تم استلام المركبة بنجاح'));
      loadVehicles();
    } else {
      alert(t('pickup_failed', 'فشل استلام المركبة') + ': ' + (r.json?.message || r.text || t('unknown_error', 'خطأ غير معروف')));
    }
  };
  
  // Return vehicle - إضافة معامل lang
  window.returnVehicle = async function(vehicleCode) {
    const confirmMsg = t('return_confirm', 'هل تريد إرجاع المركبة @@؟').replace('@@', vehicleCode);
    if (!confirm(confirmMsg)) return;
    
    const empId = currentSession?.user?.emp_id;
    if (!empId) {
      alert(t('error_no_emp_id', 'خطأ: لا يوجد رمز وظيفي'));
      return;
    }
    
    const fd = new FormData();
    fd.append('vehicle_code', vehicleCode);
    fd.append('operation_type', 'return');
    fd.append('performed_by', empId);
    
    // إضافة معامل lang إلى URL
    const r = await fetchJson(`${API_ADD_MOVEMENT}?lang=${userLang}`, { method: 'POST', body: fd });
    if (r.ok && r.json && r.json.success) {
      alert(t('return_success', 'تم إرجاع المركبة بنجاح'));
      loadVehicles();
    } else {
      alert(t('return_failed', 'فشل إرجاع المركبة') + ': ' + (r.json?.message || r.text || t('unknown_error', 'خطأ غير معروف')));
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
      populateFilter(sectionFilter, filteredSections, t('all_sections', 'جميع الأقسام'));
      if (divisionFilter) divisionFilter.innerHTML = '<option value="">' + t('all_divisions', 'جميع الشعب') + '</option>';
      loadVehicles();
    });
    
    if (sectionFilter) sectionFilter.addEventListener('change', () => {
      const secId = sectionFilter.value;
      const filteredDivisions = references.divisions.filter(d => String(d.section_id ?? '') === String(secId));
      populateFilter(divisionFilter, filteredDivisions, t('all_divisions', 'جميع الشعب'));
      loadVehicles();
    });
    
    if (divisionFilter) divisionFilter.addEventListener('change', () => loadVehicles());
    if (statusFilter) statusFilter.addEventListener('change', () => loadVehicles());
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
