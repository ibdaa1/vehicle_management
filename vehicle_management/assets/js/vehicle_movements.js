(function () {
  'use strict';

  // API Endpoints
  const API_SESSION_INIT = '/vehicle_management/api/config/session.php?init=1';
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
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
  const vehicleCountContainer = document.getElementById('vehicleCountContainer');
  const warningArea = document.getElementById('warningArea');
  const pageTitleEl = document.querySelector('.page-title');
  const pageSubtitleEl = document.querySelector('.page-subtitle');
  const htmlRoot = document.getElementById('htmlRoot');
  const resetFilterBtn = document.querySelector('.filter-reset-btn');

  // State
  let currentSession = null;
  let references = { departments: [], sections: [], divisions: [] };
  let userHasVehicleCheckedOut = false;
  let allVehicles = [];
  let userLang = 'ar';
  let translations = {};
  let translationLoaded = false;
  let userPermissions = {};

  // Default translations (fallback)
  const defaultTranslations = {
    page: {
      title: "لوحة تحكم حركة المركبات",
      subtitle: "إدارة عمليات الاستلام والإرجاع"
    },
    organization: "وزارة الصحة - إدارة المركبات",
    search: {
      placeholder: "ابحث برقم المركبة، السائق، النوع..."
    },
    filters: {
      all_departments: "جميع الإدارات",
      all_sections: "جميع الأقسام",
      all_divisions: "جميع الشعب",
      all_statuses: "جميع الحالات التشغيلية",
      all_checkout_statuses: "جميع حالات الاستلام",
      all_vehicle_types: "جميع أنواع المركبات",
      all_movement_types: "جميع أنواع الحركة"
    },
    buttons: {
      random_assignment: "سحب عشوائي",
      return_vehicle: "إرجاع مركبة",
      reset: "إعادة تعيين"
    },
    label: {
      private: "خاصة",
      shift: "ورديات",
      vehicle_count: "عدد المركبات",
      type: "النوع",
      manufacture_year: "سنة الصنع",
      driver: "السائق",
      phone: "الهاتف",
      department: "الإدارة",
      section: "القسم",
      division: "الشعبة",
      mode: "النمط",
      status: "الحالة التشغيلية",
      checked_out_by: "مستلم بواسطة",
      vehicle_details: "تفاصيل المركبة",
      vehicle_code: "رمز المركبة",
      employee_id: "الرقم الإداري"
    },
    status: {
      operational: "قيد التشغيل",
      maintenance: "صيانة",
      out_of_service: "خارج الخدمة"
    },
    mode: {
      shift: "ورديات",
      private: "خاصة"
    },
    availability: {
      available: "متاحة",
      checked_out: "مستلمة",
      checked_out_by_me: "مستلمة من قبلي",
      private_unavailable: "خاصة غير متاحة"
    },
    actions: {
      pickup: "استلام",
      return: "إرجاع",
      open_form: "فتح النموذج"
    },
    messages: {
      you_have_vehicle: "لديك مركبة مستلمة",
      pickup_success: "تم استلام المركبة بنجاح",
      return_success: "تم إرجاع المركبة بنجاح"
    },
    confirm: {
      pickup: "هل تريد استلام المركبة {{code}}؟",
      return: "هل تريد إرجاع المركبة {{code}}؟",
      random_assignment: "هل تريد سحب مركبة عشوائياً؟"
    },
    warnings: {
      attention: "تنبيه",
      has_active_vehicle: "لديك حالياً مركبة مستلمة. يرجى إرجاعها قبل استلام مركبة أخرى."
    },
    errors: {
      unknown_session: "خطأ في الجلسة",
      load_failed: "فشل تحميل البيانات",
      loading_failed: "فشل التحميل",
      check_console: "تحقق من وحدة التحكم",
      render_failed: "فشل العرض",
      contact_admin: "اتصل بالمسؤول",
      no_emp_id: "رقم الموظف غير موجود",
      cannot_pickup_has_active: "لديك مركبة نشطة",
      cannot_pickup_recent: "تم تخصيصها مؤخراً",
      pickup_failed: "فشل استلام المركبة",
      return_failed: "فشل إرجاع المركبة",
      random_failed: "فشل السحب العشوائي",
      server_unreachable: "خطأ في الخادم"
    },
    empty: {
      no_vehicles: "لم يتم العثور على مركبات",
      check_filters: "تحقق من عوامل التصفية"
    },
    loading: {
      vehicles: "جاري تحميل المركبات..."
    },
    auth: {
      unauthorized: "غير مصرح",
      login_prompt: "يرجى تسجيل الدخول",
      login: "تسجيل الدخول"
    }
  };

  // Enhanced fetch function with better error handling
  async function fetchData(url, options = {}) {
    try {
      console.log('Fetching data from:', url);
      
      const response = await fetch(url, {
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...options.headers
        },
        ...options
      });
      
      console.log('Response status:', response.status, response.statusText);
      
      if (!response.ok) {
        const errorText = await response.text();
        console.error('Error response:', errorText);
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      console.log('Response data received');
      
      return { success: true, data };
    } catch (error) {
      console.error('Fetch error:', error.message, 'URL:', url);
      return { 
        success: false, 
        error: error.message,
        data: null 
      };
    }
  }

  // Load translations
  async function loadTranslations(lang) {
    console.log('Loading translations for language:', lang);
    
    try {
      const url = `/vehicle_management/languages/${lang}_vehicle_movements.json`;
      const response = await fetch(url, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Cache-Control': 'no-cache'
        }
      });
      
      if (response.ok) {
        translations = await response.json();
        console.log('Translations loaded successfully');
      } else {
        throw new Error(`HTTP ${response.status}`);
      }
    } catch (error) {
      console.warn('Could not load translations, using defaults:', error.message);
      translations = defaultTranslations;
    }
    
    translationLoaded = true;
    return translations;
  }

  // Update page direction
  function updatePageDirection(lang) {
    const direction = lang === 'ar' ? 'rtl' : 'ltr';
    
    if (htmlRoot) {
      htmlRoot.setAttribute('lang', lang);
      htmlRoot.setAttribute('dir', direction);
    }
    
    document.body.style.direction = direction;
    document.body.setAttribute('dir', direction);
  }

  // Translation function
  function t(key, vars = {}) {
    if (!translationLoaded) {
      return key.split('.').pop();
    }
    
    const parts = key.split('.');
    let value = translations;
    
    for (const part of parts) {
      if (value && value[part] !== undefined) {
        value = value[part];
      } else {
        // Fallback to default translations
        let defaultValue = defaultTranslations;
        for (const p of parts) {
          if (defaultValue && defaultValue[p] !== undefined) {
            defaultValue = defaultValue[p];
          } else {
            // If not found in defaults, return original key part
            return key.split('.').pop();
          }
        }
        value = defaultValue;
        break;
      }
    }
    
    if (typeof value === 'string') {
      return value.replace(/\{\{(\w+)\}\}/g, (match, varName) => {
        return vars[varName] !== undefined ? vars[varName] : match;
      });
    }
    
    return key.split('.').pop();
  }

  // Update ALL UI texts
  function updateAllUITexts() {
    console.log('Updating ALL UI texts with language:', userLang);
    
    // Page titles
    if (pageTitleEl) pageTitleEl.textContent = t('page.title');
    if (pageSubtitleEl) pageSubtitleEl.textContent = t('page.subtitle');
    
    // Organization name
    if (orgNameEl) {
      const orgText = t('organization') || 'وزارة الصحة - إدارة المركبات';
      orgNameEl.textContent = userLang === 'en' ? 'Ministry of Health - Vehicle Management' : orgText;
    }
    
    // Search placeholder
    if (searchInput) searchInput.placeholder = t('search.placeholder');
    
    // Update ALL filter labels
    updateAllFilterLabels();
    
    // Update ALL filter options
    updateAllFilterOptions();
    
    // Update ALL button texts
    updateAllButtonTexts();
    
    // Update vehicle counter
    updateVehicleCount(0);
    
    // Update loading text
    if (loadingMsg) {
      const loadingText = loadingMsg.querySelector('.loading-text');
      if (loadingText) loadingText.textContent = t('loading.vehicles');
    }
  }

  // Update ALL filter labels
  function updateAllFilterLabels() {
    const filterGroups = document.querySelectorAll('.filter-group');
    
    filterGroups.forEach((group) => {
      const label = group.querySelector('.filter-label');
      if (!label) return;
      
      let translationKey = '';
      const labelText = label.textContent.trim();
      
      switch (labelText) {
        case 'الإدارة':
        case 'Department':
          translationKey = 'label.department';
          break;
        case 'القسم':
        case 'Section':
          translationKey = 'label.section';
          break;
        case 'الشعبة':
        case 'Division':
          translationKey = 'label.division';
          break;
        case 'الحالة التشغيلية':
        case 'Status':
          translationKey = 'label.status';
          break;
        case 'حالة الاستلام':
        case 'Checkout Status':
        case 'Available':
          translationKey = 'availability.available';
          break;
        case 'نوع المركبة':
        case 'Vehicle Type':
        case 'Mode':
          translationKey = 'label.mode';
          break;
        case 'نوع الحركة':
        case 'Movement Type':
        case 'Pickup':
          translationKey = 'actions.pickup';
          break;
      }
      
      if (translationKey) {
        const translated = t(translationKey);
        if (translated && translated !== translationKey) {
          label.textContent = translated;
        }
      }
    });
  }

  // Update ALL filter options
  function updateAllFilterOptions() {
    // Department filter
    if (departmentFilter && departmentFilter.options.length > 0) {
      departmentFilter.options[0].textContent = t('filters.all_departments');
    }
    
    // Section filter
    if (sectionFilter && sectionFilter.options.length > 0) {
      sectionFilter.options[0].textContent = t('filters.all_sections');
    }
    
    // Division filter
    if (divisionFilter && divisionFilter.options.length > 0) {
      divisionFilter.options[0].textContent = t('filters.all_divisions');
    }
    
    // Status filter
    if (statusFilter) {
      const options = statusFilter.options;
      if (options[0]) options[0].textContent = t('filters.all_statuses');
      if (options[1]) options[1].textContent = t('status.operational');
      if (options[2]) options[2].textContent = t('status.maintenance');
      if (options[3]) options[3].textContent = t('status.out_of_service');
    }
    
    // Checkout status filter
    if (checkoutStatusFilter) {
      const options = checkoutStatusFilter.options;
      if (options[0]) options[0].textContent = t('filters.all_checkout_statuses');
      if (options[1]) options[1].textContent = t('availability.available');
      if (options[2]) options[2].textContent = t('availability.checked_out');
    }
    
    // Vehicle type filter
    if (vehicleTypeFilter) {
      const options = vehicleTypeFilter.options;
      if (options[0]) options[0].textContent = t('filters.all_vehicle_types');
      if (options[1]) options[1].textContent = t('mode.shift');
      if (options[2]) options[2].textContent = t('mode.private');
    }
    
    // Movement type filter
    if (movementTypeFilter) {
      const options = movementTypeFilter.options;
      if (options[0]) options[0].textContent = t('filters.all_movement_types');
      if (options[1]) options[1].textContent = t('actions.pickup');
      if (options[2]) options[2].textContent = t('actions.return');
    }
  }

  // Update ALL button texts
  function updateAllButtonTexts() {
    // Random assignment button
    if (randomAssignmentBtn) {
      const btnText = randomAssignmentBtn.querySelector('.btn-text');
      if (btnText) btnText.textContent = t('buttons.random_assignment');
    }
    
    // Admin return button
    if (adminReturnBtn) {
      const btnText = adminReturnBtn.querySelector('.btn-text');
      if (btnText) btnText.textContent = t('buttons.return_vehicle');
    }
    
    // Reset filter button
    if (resetFilterBtn) {
      const resetText = resetFilterBtn.querySelector('span:last-child');
      if (resetText) resetText.textContent = t('buttons.reset');
    }
  }

  // Update vehicle count
  function updateVehicleCount(count) {
    if (vehicleCountEl) {
      vehicleCountEl.textContent = count;
    }
    
    if (vehicleCountContainer) {
      const counterText = vehicleCountContainer.querySelector('.counter-text');
      if (counterText) {
        counterText.textContent = t('label.vehicle_count') + ':';
      }
      
      const counterValue = vehicleCountContainer.querySelector('.counter-value');
      if (counterValue) {
        counterValue.textContent = count;
      }
    }
  }

  // Session check
  async function checkSession() {
    console.log('Checking session...');
    
    const result = await fetchData(API_SESSION);
    
    if (!result.success || !result.data || !result.data.success) {
      console.error('Session check failed:', result);
      showError(t('errors.unknown_session'));
      return null;
    }
    
    currentSession = result.data;
    console.log('Session data loaded successfully');
    
    // Update user info
    if (loggedUserEl && currentSession.user) {
      loggedUserEl.textContent = `${currentSession.user.username || 'User'} (${currentSession.user.emp_id || 'N/A'})`;
    }
    
    // Set language
    userLang = currentSession.user?.preferred_language || 'ar';
    console.log('User language:', userLang);
    
    // Load translations
    await loadTranslations(userLang);
    
    // Update page direction
    updatePageDirection(userLang);
    
    // Update ALL UI texts
    updateAllUITexts();
    
    return currentSession;
  }

  // Load references
  async function loadReferences() {
    console.log('Loading references...');
    
    const result = await fetchData(`${API_REFERENCES}?lang=${userLang}`);
    
    if (result.success && result.data && result.data.success) {
      references = {
        departments: result.data.departments || [],
        sections: result.data.sections || [],
        divisions: result.data.divisions || []
      };
      
      console.log('References loaded successfully:', {
        departments: references.departments.length,
        sections: references.sections.length,
        divisions: references.divisions.length
      });
      
      // Populate ALL filters with translated names
      populateAllFilters();
      
      return references;
    } else {
      console.error('Failed to load references:', result);
      return references;
    }
  }

  // Populate ALL filters
  function populateAllFilters() {
    // Reset section and division filters first
    if (sectionFilter) sectionFilter.innerHTML = '';
    if (divisionFilter) divisionFilter.innerHTML = '';
    
    // Add default options to section filter
    if (sectionFilter) {
      const sectionDefault = document.createElement('option');
      sectionDefault.value = '';
      sectionDefault.textContent = t('filters.all_sections');
      sectionFilter.appendChild(sectionDefault);
    }
    
    // Add default options to division filter
    if (divisionFilter) {
      const divisionDefault = document.createElement('option');
      divisionDefault.value = '';
      divisionDefault.textContent = t('filters.all_divisions');
      divisionFilter.appendChild(divisionDefault);
    }
    
    // Department filter
    if (departmentFilter) {
      departmentFilter.innerHTML = '';
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = t('filters.all_departments');
      departmentFilter.appendChild(defaultOption);
      
      if (references.departments && references.departments.length > 0) {
        references.departments.forEach(dept => {
          const option = document.createElement('option');
          option.value = dept.id;
          option.textContent = userLang === 'ar' ? 
            (dept.name_ar || dept.name_en || dept.name || `Department ${dept.id}`) : 
            (dept.name_en || dept.name_ar || dept.name || `Department ${dept.id}`);
          departmentFilter.appendChild(option);
        });
      }
    }
    
    // Update section filter based on current department
    updateSectionFilter();
  }

  // Update section filter based on selected department
  function updateSectionFilter() {
    if (!sectionFilter) return;
    
    // Save current value
    const currentValue = sectionFilter.value;
    
    // Clear filter
    sectionFilter.innerHTML = '';
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = t('filters.all_sections');
    sectionFilter.appendChild(defaultOption);
    
    // Get selected department
    const deptId = departmentFilter ? departmentFilter.value : null;
    
    // Filter sections based on department
    let sectionsToShow = [];
    if (deptId && references.sections) {
      sectionsToShow = references.sections.filter(s => String(s.department_id) === deptId);
    } else if (references.sections) {
      sectionsToShow = references.sections;
    }
    
    // Populate sections
    if (sectionsToShow.length > 0) {
      sectionsToShow.forEach(section => {
        const option = document.createElement('option');
        option.value = section.id;
        option.textContent = userLang === 'ar' ? 
          (section.name_ar || section.name_en || section.name || `Section ${section.id}`) : 
          (section.name_en || section.name_ar || section.name || `Section ${section.id}`);
        sectionFilter.appendChild(option);
      });
    }
    
    // Restore value if it exists
    if (currentValue && sectionsToShow.some(s => String(s.id) === currentValue)) {
      sectionFilter.value = currentValue;
    }
    
    // Update division filter after updating sections
    updateDivisionFilter();
  }

  // Update division filter based on selected section
  function updateDivisionFilter() {
    if (!divisionFilter) return;
    
    // Save current value
    const currentValue = divisionFilter.value;
    
    // Clear filter
    divisionFilter.innerHTML = '';
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = t('filters.all_divisions');
    divisionFilter.appendChild(defaultOption);
    
    // Get selected section
    const secId = sectionFilter ? sectionFilter.value : null;
    
    // Filter divisions based on section
    let divisionsToShow = [];
    if (secId && references.divisions) {
      divisionsToShow = references.divisions.filter(d => String(d.section_id) === secId);
    } else if (references.divisions) {
      divisionsToShow = references.divisions;
    }
    
    // Populate divisions
    if (divisionsToShow.length > 0) {
      divisionsToShow.forEach(division => {
        const option = document.createElement('option');
        option.value = division.id;
        option.textContent = userLang === 'ar' ? 
          (division.name_ar || division.name_en || division.name || `Division ${division.id}`) : 
          (division.name_en || division.name_ar || division.name || `Division ${division.id}`);
        divisionFilter.appendChild(option);
      });
    }
    
    // Restore value if it exists
    if (currentValue && divisionsToShow.some(d => String(d.id) === currentValue)) {
      divisionFilter.value = currentValue;
    }
  }

  // Load vehicles
  async function loadVehicles() {
    console.log('Loading vehicles...');
    
    // Show loading
    if (loadingMsg) {
      loadingMsg.style.display = 'flex';
    }
    
    if (vehiclesContainer) {
      vehiclesContainer.innerHTML = '';
    }
    
    try {
      // Build query params
      const params = new URLSearchParams();
      params.append('lang', userLang);
      
      if (searchInput && searchInput.value) {
        params.append('q', searchInput.value.trim());
      }
      
      if (departmentFilter && departmentFilter.value) {
        params.append('department_id', departmentFilter.value);
      }
      
      if (sectionFilter && sectionFilter.value) {
        params.append('section_id', sectionFilter.value);
      }
      
      if (divisionFilter && divisionFilter.value) {
        params.append('division_id', divisionFilter.value);
      }
      
      if (statusFilter && statusFilter.value) {
        params.append('status', statusFilter.value);
      }
      
      const url = `${API_VEHICLES}?${params.toString()}`;
      console.log('Fetching vehicles from:', url);
      
      const result = await fetchData(url);
      
      // Hide loading
      if (loadingMsg) {
        loadingMsg.style.display = 'none';
      }
      
      if (!result.success) {
        throw new Error('Failed to fetch vehicles: ' + (result.error || 'Unknown error'));
      }
      
      if (!result.data || !result.data.success) {
        console.error('API returned error:', result.data);
        showError(result.data?.message || t('errors.load_failed'));
        return;
      }
      
      const data = result.data;
      allVehicles = data.vehicles || [];
      userHasVehicleCheckedOut = data.user_has_vehicle_checked_out || false;
      userPermissions = data.permissions || {};
      
      console.log('Vehicles loaded successfully:', allVehicles.length);
      console.log('User has vehicle checked out:', userHasVehicleCheckedOut);
      console.log('User permissions:', userPermissions);
      
      // Update vehicle count
      updateVehicleCount(allVehicles.length);
      
      // Update buttons visibility - زر السحب والرجوع متاح للجميع بدون شروط
      updateButtonsVisibility();
      
      // Apply client-side filters
      applyClientSideFilters();
      
    } catch (error) {
      console.error('Error loading vehicles:', error);
      
      if (loadingMsg) {
        loadingMsg.style.display = 'none';
      }
      
      showError(t('errors.load_failed') + ': ' + error.message);
    }
  }

  // Update buttons visibility
  function updateButtonsVisibility() {
    console.log('Updating buttons visibility - All buttons available to all users');
    
    // Random assignment button - متاح للجميع دائماً
    if (randomAssignmentBtn) {
      randomAssignmentBtn.style.display = 'flex';
      randomAssignmentBtn.classList.remove('hidden');
      console.log('Random assignment button: VISIBLE (available to all users)');
    }
    
    // Admin return button - متاح للجميع دائماً
    if (adminReturnBtn) {
      adminReturnBtn.style.display = 'flex';
      adminReturnBtn.classList.remove('hidden');
      console.log('Admin return button: VISIBLE (available to all users)');
    }
  }

  // Apply client-side filters
  function applyClientSideFilters() {
    if (!allVehicles || allVehicles.length === 0) {
      renderVehicles([]);
      return;
    }
    
    let filtered = [...allVehicles];
    
    // Filter by checkout status
    if (checkoutStatusFilter && checkoutStatusFilter.value) {
      if (checkoutStatusFilter.value === 'available') {
        filtered = filtered.filter(v => v.availability_status === 'available');
      } else if (checkoutStatusFilter.value === 'checked_out') {
        filtered = filtered.filter(v => v.availability_status !== 'available');
      }
    }
    
    // Filter by vehicle type
    if (vehicleTypeFilter && vehicleTypeFilter.value) {
      filtered = filtered.filter(v => v.vehicle_mode === vehicleTypeFilter.value);
    }
    
    // Filter by movement type
    if (movementTypeFilter && movementTypeFilter.value) {
      if (movementTypeFilter.value === 'pickup') {
        filtered = filtered.filter(v => v.can_pickup);
      } else if (movementTypeFilter.value === 'return') {
        filtered = filtered.filter(v => v.can_return);
      }
    }
    
    // Update count
    updateVehicleCount(filtered.length);
    
    // Render
    renderVehicles(filtered);
  }

  // Render vehicles
  function renderVehicles(vehicles) {
    if (!vehiclesContainer) return;
    
    if (!vehicles || vehicles.length === 0) {
      vehiclesContainer.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">🚗</div>
          <h3 class="empty-title">${t('empty.no_vehicles')}</h3>
          <p class="empty-description">${t('empty.check_filters')}</p>
        </div>
      `;
      return;
    }
    
    let html = '';
    
    vehicles.forEach(vehicle => {
      try {
        const statusClass = getStatusClass(vehicle.availability_status);
        const statusText = getStatusText(vehicle.availability_status);
        const modeBadge = vehicle.vehicle_mode === 'private' ? 
          `<span class="vehicle-badge badge-private">${t('label.private')}</span>` :
          `<span class="vehicle-badge badge-shift">${t('label.shift')}</span>`;
        
        // Build vehicle details
        const details = [];
        
        // Basic vehicle info
        if (vehicle.type) details.push(renderVehicleDetail(t('label.type'), vehicle.type));
        if (vehicle.manufacture_year) details.push(renderVehicleDetail(t('label.manufacture_year'), vehicle.manufacture_year));
        if (vehicle.driver_name) details.push(renderVehicleDetail(t('label.driver'), vehicle.driver_name));
        if (vehicle.driver_phone) details.push(renderVehicleDetail(t('label.phone'), vehicle.driver_phone));
        
        // Organization info
        if (vehicle.department_name) details.push(renderVehicleDetail(t('label.department'), vehicle.department_name));
        if (vehicle.section_name) details.push(renderVehicleDetail(t('label.section'), vehicle.section_name));
        if (vehicle.division_name) details.push(renderVehicleDetail(t('label.division'), vehicle.division_name));
        
        // Additional info
        if (vehicle.vehicle_mode) details.push(renderVehicleDetail(t('label.mode'), getModeText(vehicle.vehicle_mode)));
        if (vehicle.status) details.push(renderVehicleDetail(t('label.status'), getOperationalStatusText(vehicle.status)));
        
        // Current checkout info
        if (vehicle.current_checkout_by) details.push(renderVehicleDetail(t('label.checked_out_by'), vehicle.current_checkout_by));
        if (vehicle.current_checkout_phone) details.push(renderVehicleDetail(t('label.phone'), vehicle.current_checkout_phone));
        if (vehicle.employee_id) details.push(renderVehicleDetail(t('label.employee_id'), vehicle.employee_id));
        
        html += `
          <div class="vehicle-card ${statusClass}" data-vehicle-id="${vehicle.id}">
            <div class="vehicle-header">
              <div class="vehicle-badges">${modeBadge}</div>
              <div class="vehicle-code">${vehicle.vehicle_code || 'N/A'}</div>
            </div>
            
            <div class="vehicle-content">
              <div class="vehicle-details">
                ${details.join('')}
              </div>
              
              ${vehicle.is_currently_checked_out && vehicle.current_checkout_by ? `
                <div class="checkout-info">
                  <div class="checkout-label">${t('label.checked_out_by')}</div>
                  <div class="checkout-user">${vehicle.current_checkout_by} ${vehicle.current_checkout_phone ? `- ${vehicle.current_checkout_phone}` : ''}</div>
                </div>
              ` : ''}
              
              <div class="vehicle-status ${getStatusBadgeClass(vehicle.availability_status)}">
                ${statusText}
              </div>
            </div>
            
            <div class="vehicle-actions">
              ${renderVehicleActions(vehicle)}
            </div>
          </div>
        `;
      } catch (error) {
        console.error('Error rendering vehicle card:', error, vehicle);
      }
    });
    
    vehiclesContainer.innerHTML = html;
  }

  // Helper function to render vehicle detail
  function renderVehicleDetail(label, value) {
    if (!value) return '';
    return `
      <div class="detail-row">
        <span class="detail-label">${label}</span>
        <span class="detail-value">${value}</span>
      </div>
    `;
  }

  // Helper function to render vehicle actions - UPDATED with permission check
  function renderVehicleActions(vehicle) {
    if (!vehicle) return '';
    
    let actions = '';
    
    // زر الاستلام: يظهر فقط إذا كان المستخدم لديه allow_registration أو can_view_all_vehicles
    const canShowPickup = (userPermissions.allow_registration || userPermissions.can_view_all_vehicles) && 
                         vehicle.can_pickup && 
                         !userHasVehicleCheckedOut;
    
    // Pickup button - يظهر فقط للمستخدمين الذين لديهم الصلاحيات
    if (canShowPickup) {
      const vehicleCode = vehicle.vehicle_code || vehicle.id || '';
      actions += `
        <button class="action-button btn-pickup" onclick="window.pickupVehicle('${vehicleCode}')">
          <span class="action-icon">🚗</span>
          <span>${t('actions.pickup')}</span>
        </button>
      `;
    } 
    // Return button - متاح للجميع حسب الصلاحيات من الخادم
    else if (vehicle.can_return) {
      const vehicleCode = vehicle.vehicle_code || vehicle.id || '';
      actions += `
        <button class="action-button btn-return" onclick="window.returnVehicle('${vehicleCode}')">
          <span class="action-icon">↩️</span>
          <span>${t('actions.return')}</span>
        </button>
      `;
    } 
    // User already has a vehicle checked out
    else if (vehicle.availability_status === 'available' && userHasVehicleCheckedOut) {
      actions += `
        <button class="action-button btn-disabled" disabled>
          <span class="action-icon">⚠️</span>
          <span>${t('messages.you_have_vehicle')}</span>
        </button>
      `;
    }
    
    // زر عرض التفاصيل - يظهر للحركات النشطة (مستلمة) للمستخدم نفسه أو للمدير
    const isCheckedOut = vehicle.is_currently_checked_out;
    const isOwner = vehicle.availability_status === 'checked_out_by_me';
    const canViewDetails = isCheckedOut && (isOwner || userPermissions.can_view_all_vehicles);
    
    if (canViewDetails) {
      const vehicleCode = vehicle.vehicle_code || vehicle.id || '';
      // Pass vehicle_code; movement_id can be null as API will look it up automatically
      const movementId = vehicle.movement_id || null;
      actions += `
        <button class="action-button btn-details" onclick="window.openMovementModal('${vehicleCode}', ${movementId})">
          <span class="action-icon">📋</span>
          <span>${t('label.details')}</span>
        </button>
      `;
    }
    
    // زر فتح النموذج - يظهر فقط إذا كان المستخدم لديه can_view_all_vehicles
    if (userPermissions.can_view_all_vehicles) {
      const vehicleCode = vehicle.vehicle_code || vehicle.id || '';
      actions += `
        <button class="action-button btn-form" onclick="window.openMovementForm('${vehicleCode}')">
          <span class="action-icon">📝</span>
          <span>${t('actions.open_form')}</span>
        </button>
      `;
    }
    
    // إذا لم يكن هناك أي أزرار، نعرض رسالة للمستخدمين الذين ليس لديهم صلاحيات
    if (!actions && !userPermissions.allow_registration && !userPermissions.can_view_all_vehicles) {
      actions += `
        <div class="no-permissions-message">
          <span class="no-perms-icon">🔒</span>
          <span class="no-perms-text">${t('auth.unauthorized')}</span>
        </div>
      `;
    }
    
    return actions;
  }

  // Status helper functions
  function getStatusClass(availabilityStatus) {
    switch (availabilityStatus) {
      case 'available': return 'available';
      case 'checked_out_by_me': return 'checked-out-by-me';
      case 'private_unavailable': return 'private-unavailable';
      default: return 'checked-out';
    }
  }

  function getStatusText(availabilityStatus) {
    return t(`availability.${availabilityStatus}`) || availabilityStatus;
  }

  function getStatusBadgeClass(availabilityStatus) {
    switch (availabilityStatus) {
      case 'available': return 'status-available';
      case 'checked_out_by_me': return 'status-checked-out-by-me';
      case 'private_unavailable': return 'status-private';
      default: return 'status-checked-out';
    }
  }

  function getModeText(mode) {
    return t(`mode.${mode}`) || mode;
  }

  function getOperationalStatusText(status) {
    return t(`status.${status}`) || status;
  }

  // Vehicle actions (exposed to window)
  window.pickupVehicle = async function(vehicleCode) {
    if (!vehicleCode) {
      alert('رمز المركبة غير صالح');
      return;
    }
    
    // التحقق من الصلاحيات قبل الاستلام
    if (!userPermissions.allow_registration && !userPermissions.can_view_all_vehicles) {
      alert('ليس لديك صلاحية استلام المركبات');
      return;
    }
    
    if (!confirm(t('confirm.pickup', { code: vehicleCode }))) return;
    
    try {
      const formData = new FormData();
      formData.append('vehicle_code', vehicleCode);
      formData.append('operation_type', 'pickup');
      formData.append('performed_by', currentSession?.user?.emp_id || '');
      
      const result = await fetchData(API_ADD_MOVEMENT, {
        method: 'POST',
        body: formData
      });
      
      if (result.success && result.data && result.data.success) {
        alert(t('messages.pickup_success'));
        loadVehicles();
      } else {
        alert(result.data?.message || t('errors.pickup_failed'));
      }
    } catch (error) {
      console.error('Error picking up vehicle:', error);
      alert(t('errors.pickup_failed') + ': ' + error.message);
    }
  };

  window.returnVehicle = async function(vehicleCode) {
    if (!vehicleCode) {
      alert('رمز المركبة غير صالح');
      return;
    }
    
    if (!confirm(t('confirm.return', { code: vehicleCode }))) return;
    
    try {
      const formData = new FormData();
      formData.append('vehicle_code', vehicleCode);
      formData.append('operation_type', 'return');
      formData.append('performed_by', currentSession?.user?.emp_id || '');
      
      const result = await fetchData(API_ADD_MOVEMENT, {
        method: 'POST',
        body: formData
      });
      
      if (result.success && result.data && result.data.success) {
        alert(t('messages.return_success'));
        loadVehicles();
      } else {
        alert(result.data?.message || t('errors.return_failed'));
      }
    } catch (error) {
      console.error('Error returning vehicle:', error);
      alert(t('errors.return_failed') + ': ' + error.message);
    }
  };

  window.openMovementForm = function(vehicleCode) {
    if (!vehicleCode) {
      alert('رمز المركبة غير صالح');
      return;
    }
    
    // التحقق من الصلاحيات قبل فتح النموذج
    if (!userPermissions.can_view_all_vehicles) {
      alert('ليس لديك صلاحية فتح نموذج الحركة');
      return;
    }
    
    const url = `/vehicle_management/public/add_vehicle_movements.html?vehicle_code=${encodeURIComponent(vehicleCode)}`;
    window.open(url, '_blank');
  };

  // Reset filters function
  window.resetFilters = function() {
    console.log('Resetting filters...');
    
    // Reset all filter inputs
    if (searchInput) searchInput.value = '';
    if (departmentFilter) departmentFilter.value = '';
    if (sectionFilter) sectionFilter.value = '';
    if (divisionFilter) divisionFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    if (checkoutStatusFilter) checkoutStatusFilter.value = '';
    if (vehicleTypeFilter) vehicleTypeFilter.value = '';
    if (movementTypeFilter) movementTypeFilter.value = '';
    
    // Reset section and division filters
    updateSectionFilter();
    
    // Reload vehicles
    loadVehicles();
    
    console.log('Filters reset successfully');
  };

  // Show error
  function showError(message) {
    if (vehiclesContainer) {
      vehiclesContainer.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">⚠️</div>
          <h3 class="empty-title">${t('errors.loading_failed')}</h3>
          <p class="empty-description">${message}</p>
        </div>
      `;
    }
  }

  // Initialize event listeners
  function initEventListeners() {
    // Search input
    if (searchInput) {
      searchInput.addEventListener('input', debounce(loadVehicles, 500));
    }
    
    // Department filter
    if (departmentFilter) {
      departmentFilter.addEventListener('change', () => {
        updateSectionFilter();
        loadVehicles();
      });
    }
    
    // Section filter
    if (sectionFilter) {
      sectionFilter.addEventListener('change', () => {
        updateDivisionFilter();
        loadVehicles();
      });
    }
    
    // Division filter
    if (divisionFilter) {
      divisionFilter.addEventListener('change', loadVehicles);
    }
    
    // Status filter
    if (statusFilter) {
      statusFilter.addEventListener('change', loadVehicles);
    }
    
    // Client-side filters
    if (checkoutStatusFilter) {
      checkoutStatusFilter.addEventListener('change', applyClientSideFilters);
    }
    
    if (vehicleTypeFilter) {
      vehicleTypeFilter.addEventListener('change', applyClientSideFilters);
    }
    
    if (movementTypeFilter) {
      movementTypeFilter.addEventListener('change', applyClientSideFilters);
    }
    
    // Random assignment button - متاح للجميع
    if (randomAssignmentBtn) {
      randomAssignmentBtn.addEventListener('click', async function() {
        if (!confirm(t('confirm.random_assignment'))) return;
        
        try {
          const result = await fetchData(API_RANDOM_ASSIGNMENT, { 
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `lang=${userLang}`
          });
          
          console.log('Random assignment result:', result);
          
          if (result.success && result.data && result.data.success) {
            const vehicleCode = result.data.vehicle?.code || result.data.vehicle_code || result.data.vehicle?.vehicle_code || 'N/A';
            alert(`${result.data.message || t('messages.pickup_success')}\n\n${t('label.vehicle_code')}: ${vehicleCode}`);
            loadVehicles();
          } else {
            alert(result.data?.message || t('errors.random_failed'));
          }
        } catch (error) {
          console.error('Error in random assignment:', error);
          alert(t('errors.random_failed') + ': ' + error.message);
        }
      });
    }
    
    // Admin return button - متاح للجميع
    if (adminReturnBtn) {
      adminReturnBtn.addEventListener('click', () => {
        window.open('/vehicle_management/public/add_vehicle_movements.html', '_blank');
      });
    }
    
    // Reset filter button
    const resetFilterBtn = document.querySelector('.filter-reset-btn');
    if (resetFilterBtn) {
      resetFilterBtn.addEventListener('click', window.resetFilters);
    }
  }

  // Debounce helper
  function debounce(func, wait) {
    let timeout;
    return function(...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  // Debug function
  window.systemCheck = function() {
    console.group('System Check');
    console.log('Current session:', currentSession);
    console.log('User language:', userLang);
    console.log('Translation loaded:', translationLoaded);
    console.log('References:', {
      departments: references.departments.length,
      sections: references.sections.length,
      divisions: references.divisions.length
    });
    console.log('All vehicles count:', allVehicles.length);
    console.log('User has vehicle checked out:', userHasVehicleCheckedOut);
    console.log('User permissions:', userPermissions);
    console.log('DOM elements:', {
      searchInput: !!searchInput,
      departmentFilter: !!departmentFilter,
      sectionFilter: !!sectionFilter,
      divisionFilter: !!divisionFilter,
      vehiclesContainer: !!vehiclesContainer,
      randomAssignmentBtn: !!randomAssignmentBtn,
      adminReturnBtn: !!adminReturnBtn,
      vehicleCountEl: !!vehicleCountEl
    });
    console.groupEnd();
  };

  // Main initialization
  async function init() {
    console.log('Initializing Vehicle Movements...');
    
    try {
      // Show loading
      if (loadingMsg) {
        loadingMsg.style.display = 'flex';
      }
      
      // Initialize session
      console.log('Initializing session...');
      await fetchData(API_SESSION_INIT);
      
      // Check session
      console.log('Checking session...');
      const session = await checkSession();
      if (!session) {
        console.error('No valid session found');
        return;
      }
      
      // Load references
      console.log('Loading references...');
      await loadReferences();
      
      // Load vehicles
      console.log('Loading vehicles...');
      await loadVehicles();
      
      // Initialize event listeners
      console.log('Initializing event listeners...');
      initEventListeners();
      
      console.log('Initialization complete');
      
    } catch (error) {
      console.error('Initialization error:', error);
      showError('Initialization failed: ' + error.message);
    } finally {
      // Hide loading
      if (loadingMsg) {
        loadingMsg.style.display = 'none';
      }
    }
  }

  // ========== MODAL FUNCTIONS ==========
  
  // Global state for modal
  let currentMovementData = null;
  let selectedPhotos = [];
  
  // Open movement detail modal
  window.openMovementModal = async function(vehicleCode, movementId) {
    console.log('Opening movement modal for:', vehicleCode, movementId);
    
    const modal = document.getElementById('movementDetailModal');
    if (!modal) {
      console.error('Modal element not found');
      return;
    }
    
    // Show modal
    modal.style.display = 'flex';
    modal.classList.add('active');
    
    // Reset form
    document.getElementById('modalVehicleCode').textContent = vehicleCode || '-';
    document.getElementById('modalOperationType').textContent = '-';
    document.getElementById('modalPerformedBy').textContent = '-';
    document.getElementById('modalDateTime').textContent = '-';
    document.getElementById('modalLatitude').value = '';
    document.getElementById('modalLongitude').value = '';
    document.getElementById('modalNotes').value = '';
    document.getElementById('existingPhotos').innerHTML = '';
    document.getElementById('selectedPhotosPreview').innerHTML = '';
    selectedPhotos = [];
    
    // Store current data
    currentMovementData = {
      vehicle_code: vehicleCode,
      movement_id: movementId
    };
    
    // If we have movement ID, fetch details
    if (movementId) {
      await loadMovementDetails(movementId);
    }
    
    // Show/hide buttons based on permissions
    updateModalButtons();
  };
  
  // Close modal
  window.closeMovementModal = function() {
    const modal = document.getElementById('movementDetailModal');
    if (modal) {
      modal.style.display = 'none';
      modal.classList.remove('active');
    }
    currentMovementData = null;
    selectedPhotos = [];
  };
  
  // Load movement details
  async function loadMovementDetails(movementId) {
    try {
      console.log('Loading movement details for ID:', movementId);
      
      // Note: For now, this is a placeholder. Movement details are managed through
      // the vehicle data already loaded. If a dedicated endpoint is needed in the future,
      // it would be: /vehicle_management/api/vehicle/get_movement_detail.php?id=${movementId}
      // The current implementation works by:
      // 1. Opening modal with vehicle_code
      // 2. Fetching/displaying photos from database by vehicle_code
      // 3. Loading coordinates from the movement if movement_id is provided
      
    } catch (error) {
      console.error('Error loading movement details:', error);
    }
  }
  
  // Update modal buttons based on permissions
  function updateModalButtons() {
    const returnBtn = document.getElementById('returnVehicleBtn');
    const pullCoordsBtn = document.getElementById('pullCoordinatesBtn');
    const saveCoordsBtn = document.getElementById('saveCoordinatesBtn');
    
    // Return button - only for admin/super admin
    if (returnBtn) {
      if (userPermissions.can_view_all_vehicles) {
        returnBtn.style.display = 'inline-flex';
      } else {
        returnBtn.style.display = 'none';
      }
    }
    
    // Coordinates buttons - available for movement owner and admin
    // For now, show them to everyone who has the modal open
    if (pullCoordsBtn) pullCoordsBtn.style.display = 'inline-flex';
    if (saveCoordsBtn) saveCoordsBtn.style.display = 'inline-flex';
  }
  
  // Pull GPS coordinates using geolocation API
  window.pullCoordinates = function() {
    console.log('Pulling GPS coordinates...');
    
    if (!navigator.geolocation) {
      alert(t('errors.geolocation_not_supported'));
      return;
    }
    
    const latInput = document.getElementById('modalLatitude');
    const lngInput = document.getElementById('modalLongitude');
    const pullBtn = document.getElementById('pullCoordinatesBtn');
    
    // Disable button and show loading state
    if (pullBtn) {
      pullBtn.disabled = true;
      pullBtn.innerHTML = '<span class="btn-icon">⏳</span><span>' + t('messages.getting_location') + '</span>';
    }
    
    navigator.geolocation.getCurrentPosition(
      function(position) {
        // Success - update inputs
        if (latInput) latInput.value = position.coords.latitude.toFixed(8);
        if (lngInput) lngInput.value = position.coords.longitude.toFixed(8);
        
        // Reset button
        if (pullBtn) {
          pullBtn.disabled = false;
          pullBtn.innerHTML = '<span class="btn-icon">📍</span><span>' + t('actions.pull_coordinates') + '</span>';
        }
        
        alert(t('messages.location_obtained'));
      },
      function(error) {
        // Error
        console.error('Geolocation error:', error);
        
        // Reset button
        if (pullBtn) {
          pullBtn.disabled = false;
          pullBtn.innerHTML = '<span class="btn-icon">📍</span><span>' + t('actions.pull_coordinates') + '</span>';
        }
        
        let errorMsg = t('errors.geolocation_failed');
        switch(error.code) {
          case error.PERMISSION_DENIED:
            errorMsg = t('errors.geolocation_permission_denied') || 'Permission denied for location access.';
            break;
          case error.POSITION_UNAVAILABLE:
            errorMsg = t('errors.geolocation_unavailable') || 'Location information unavailable.';
            break;
          case error.TIMEOUT:
            errorMsg = t('errors.geolocation_timeout') || 'Request timed out.';
            break;
        }
        alert(errorMsg);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  };
  
  // Save coordinates to server
  window.saveCoordinates = async function() {
    console.log('Saving coordinates...');
    
    if (!currentMovementData || !currentMovementData.vehicle_code) {
      alert('No vehicle code available');
      return;
    }
    
    const latInput = document.getElementById('modalLatitude');
    const lngInput = document.getElementById('modalLongitude');
    
    const latitude = parseFloat(latInput.value);
    const longitude = parseFloat(lngInput.value);
    
    if (isNaN(latitude) || isNaN(longitude)) {
      alert(t('errors.invalid_coordinates'));
      return;
    }
    
    const saveBtn = document.getElementById('saveCoordinatesBtn');
    if (saveBtn) {
      saveBtn.disabled = true;
      saveBtn.innerHTML = '<span class="btn-icon">⏳</span><span>' + t('messages.saving') + '</span>';
    }
    
    try {
      const formData = new FormData();
      // Always send vehicle_code - API will find the movement_id if not provided
      formData.append('vehicle_code', currentMovementData.vehicle_code);
      if (currentMovementData.movement_id) {
        formData.append('movement_id', currentMovementData.movement_id);
      }
      formData.append('latitude', latitude);
      formData.append('longitude', longitude);
      
      const result = await fetchData('/vehicle_management/api/vehicle/update_movement_coords.php', {
        method: 'POST',
        body: formData
      });
      
      if (result.success && result.data && result.data.success) {
        alert(t('messages.coordinates_saved'));
      } else {
        alert(result.data?.message || t('errors.coordinates_save_failed'));
      }
    } catch (error) {
      console.error('Error saving coordinates:', error);
      alert(t('errors.coordinates_save_failed') + ': ' + error.message);
    } finally {
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<span class="btn-icon">💾</span><span>' + t('actions.save_coordinates') + '</span>';
      }
    }
  };
  
  // Handle photo selection
  window.handlePhotoSelection = function() {
    const input = document.getElementById('photoUploadInput');
    const preview = document.getElementById('selectedPhotosPreview');
    const uploadBtn = document.getElementById('uploadPhotosBtn');
    
    if (!input || !input.files || input.files.length === 0) {
      if (uploadBtn) uploadBtn.style.display = 'none';
      if (preview) preview.innerHTML = '';
      selectedPhotos = [];
      return;
    }
    
    selectedPhotos = Array.from(input.files);
    
    // Show upload button
    if (uploadBtn) uploadBtn.style.display = 'inline-flex';
    
    // Show preview
    if (preview) {
      preview.innerHTML = '';
      selectedPhotos.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
          const div = document.createElement('div');
          div.className = 'photo-preview-item';
          div.innerHTML = `
            <img src="${e.target.result}" alt="Preview ${index + 1}" />
            <button class="photo-preview-remove" onclick="removeSelectedPhoto(${index})" type="button">×</button>
          `;
          preview.appendChild(div);
        };
        reader.readAsDataURL(file);
      });
    }
  };
  
  // Remove selected photo
  window.removeSelectedPhoto = function(index) {
    selectedPhotos.splice(index, 1);
    
    // Update file input (create new FileList)
    const input = document.getElementById('photoUploadInput');
    const dt = new DataTransfer();
    selectedPhotos.forEach(file => dt.items.add(file));
    input.files = dt.files;
    
    // Refresh preview
    handlePhotoSelection();
  };
  
  // Upload photos to server
  window.uploadPhotos = async function() {
    console.log('Uploading photos...');
    
    if (!currentMovementData || !currentMovementData.vehicle_code) {
      alert('No vehicle code available');
      return;
    }
    
    if (selectedPhotos.length === 0) {
      alert(t('errors.no_photos_selected'));
      return;
    }
    
    const uploadBtn = document.getElementById('uploadPhotosBtn');
    if (uploadBtn) {
      uploadBtn.disabled = true;
      uploadBtn.innerHTML = '<span class="btn-icon">⏳</span><span>' + t('messages.uploading') + '</span>';
    }
    
    try {
      const formData = new FormData();
      formData.append('vehicle_code', currentMovementData.vehicle_code);
      if (currentMovementData.movement_id) {
        formData.append('movement_id', currentMovementData.movement_id);
      }
      
      const notes = document.getElementById('modalNotes').value;
      if (notes) {
        formData.append('notes', notes);
      }
      
      // Append all photos
      selectedPhotos.forEach((file, index) => {
        formData.append('photos[]', file);
      });
      
      const result = await fetchData('/vehicle_management/api/vehicle/upload.php', {
        method: 'POST',
        body: formData
      });
      
      if (result.success && result.data && result.data.success) {
        alert(t('messages.photos_uploaded') + ': ' + result.data.total_uploaded);
        
        // Clear selection
        selectedPhotos = [];
        document.getElementById('photoUploadInput').value = '';
        document.getElementById('selectedPhotosPreview').innerHTML = '';
        if (uploadBtn) uploadBtn.style.display = 'none';
        
        // Reload existing photos if we have movement ID
        if (currentMovementData.movement_id) {
          await loadMovementDetails(currentMovementData.movement_id);
        }
      } else {
        const errorMsg = result.data?.message || t('errors.photo_upload_failed');
        const errors = result.data?.errors || [];
        let fullMsg = errorMsg;
        if (errors.length > 0) {
          fullMsg += '\n\nErrors:\n' + errors.map(e => e.error).join('\n');
        }
        alert(fullMsg);
      }
    } catch (error) {
      console.error('Error uploading photos:', error);
      alert(t('errors.photo_upload_failed') + ': ' + error.message);
    } finally {
      if (uploadBtn) {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<span class="btn-icon">☁️</span><span>' + t('actions.upload_photos') + '</span>';
      }
    }
  };
  
  // Return vehicle from modal
  window.returnVehicleFromModal = async function() {
    if (!currentMovementData || !currentMovementData.vehicle_code) {
      alert('No vehicle code available');
      return;
    }
    
    if (!confirm(t('confirm.return', { code: currentMovementData.vehicle_code }))) {
      return;
    }
    
    try {
      const formData = new FormData();
      formData.append('vehicle_code', currentMovementData.vehicle_code);
      formData.append('operation_type', 'return');
      formData.append('performed_by', currentSession?.user?.emp_id || '');
      
      const result = await fetchData(API_ADD_MOVEMENT, {
        method: 'POST',
        body: formData
      });
      
      if (result.success && result.data && result.data.success) {
        alert(t('messages.return_success'));
        closeMovementModal();
        loadVehicles();
      } else {
        alert(result.data?.message || t('errors.return_failed'));
      }
    } catch (error) {
      console.error('Error returning vehicle:', error);
      alert(t('errors.return_failed') + ': ' + error.message);
    }
  };
  
  // Close modal when clicking outside
  window.addEventListener('click', function(event) {
    const modal = document.getElementById('movementDetailModal');
    if (event.target === modal) {
      closeMovementModal();
    }
  });

  // Start when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
