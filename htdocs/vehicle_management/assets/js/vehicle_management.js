// vehicle_management/assets/js/vehicle_management.js
class VehicleManagementPage {
    constructor() {
        // API endpoints
        this.API_SESSION = '/vehicle_management/api/v1/auth/check';
        this.API_MANAGEMENTS = '/vehicle_management/api/v1/vehicles';
        this.API_REFERENCES = '/vehicle_management/api/v1/references';
        this.API_EMPLOYEES = '/vehicle_management/api/v1/users';
        this.ADD_MANAGEMENT_PAGE = '/vehicle_management/public/add_vehicle_movements.html';
       
        // System settings
        this.userLanguage = 'ar';
        this.translations = {};
       
        // Filters state
        this.filters = {
            dateRange: null,
            managementType: '',
            vehicleCode: '',
            employeeId: '',
            department: '',
            section: '',
            division: '',
            status: '',
            vehicleMode: ''
        };
       
        // Data state
        this.selectedEmployee = null;
        this.dataTable = null;
        this.pendingTable = null;
        this.usedTable = null;
        this.currentView = 'all';
        this.currentReportType = 'all';
       
        // Cached data
        this.currentStatistics = {};
        this.currentManagements = [];
        this.currentPendingVehicles = [];
       
        this.init();
    }
    async init() {
        try {
            console.log('🚀 Starting Vehicle Management System...');
            await this.initializeElements();
            this.setupEventListeners();
            await this.loadInitialData();
            this.setPageDirection();
            this.initializeDataTable();
            this.initializePendingTable();
            this.initializeUsedTable();
            await this.loadManagements();
            this.applyTranslations();
            console.log('✅ System initialized successfully');
        } catch (error) {
            console.error('❌ Failed to initialize system:', error);
            this.showMessage('error', `فشل في تهيئة النظام: ${error.message}`, 10000);
        }
    }
    async initializeElements() {
        console.log('🔄 Initializing UI elements...');
       
        // UI Elements
        this.elements = {
            // Navigation Buttons
            addManagementBtn: document.getElementById('add-management-btn'),
            refreshBtn: document.getElementById('refresh-btn'),
            resetFiltersBtn: document.getElementById('reset-filters'),
            applyFiltersBtn: document.getElementById('apply-filters'),
            printReportBtn: document.getElementById('print-report-btn'),
            exportReportBtn: document.getElementById('export-report-btn'),
            unusedReportBtn: document.getElementById('unused-report-btn'),
           
            // Filter Elements
            dateRangeInput: document.getElementById('date-range'),
            managementTypeSelect: document.getElementById('management-type'),
            vehicleCodeInput: document.getElementById('vehicle-code'),
            employeeSearchInput: document.getElementById('employee-search'),
            departmentSelect: document.getElementById('department'),
            sectionSelect: document.getElementById('section'),
            divisionSelect: document.getElementById('division'),
            statusSelect: document.getElementById('status'),
            vehicleModeSelect: document.getElementById('vehicle-mode'),
           
            // Modal Elements
            employeeModal: document.getElementById('employee-search-modal'),
            employeeModalSearchInput: document.getElementById('employee-modal-search'),
            searchEmployeeModalBtn: document.getElementById('search-employee-modal-btn'),
            employeeModalResults: document.getElementById('employee-modal-results'),
            closeSearchBtn: document.querySelector('.close-search'),
           
            // Statistics Elements
            totalManagementsElement: document.getElementById('total-managements'),
            pickupCountElement: document.getElementById('pickup-count'),
            returnCountElement: document.getElementById('return-count'),
            pendingCountElement: document.getElementById('pending-count'),
            usedVehiclesElement: document.getElementById('used-vehicles'),
            unusedVehiclesElement: document.getElementById('unused-vehicles'),
            totalPrivateVehiclesElement: document.getElementById('total-private-vehicles'),
            totalShiftVehiclesElement: document.getElementById('total-shift-vehicles'),
            operationalVehiclesElement: document.getElementById('operational-vehicles'),
            outOfServiceVehiclesElement: document.getElementById('out-of-service-vehicles'),
            maintenanceVehiclesElement: document.getElementById('maintenance-vehicles'),
            usedPrivateVehiclesElement: document.getElementById('used-private-vehicles'),
            usedShiftVehiclesElement: document.getElementById('used-shift-vehicles'),
           
            // View Tabs
            tabAll: document.getElementById('tab-all'),
            tabPending: document.getElementById('tab-pending'),
            tabUsed: document.getElementById('tab-used'),
            pendingBadge: document.getElementById('pending-badge'),
           
            // Report Options
            reportOptions: document.querySelectorAll('.report-option-btn'),
           
            // Loading Overlay
            loadingOverlay: document.getElementById('loading-overlay'),
           
            // Table Elements
            mainTable: document.getElementById('managements-table'),
            tableContainer: document.querySelector('.table-container')
        };
       
        // Verify all critical elements exist
        if (!this.elements.mainTable) {
            throw new Error('Table element not found');
        }
       
        console.log('✅ UI elements initialized');
    }
    setupEventListeners() {
        console.log('🔧 Setting up event listeners...');

        // Navigation Buttons
        if (this.elements.addManagementBtn) this.elements.addManagementBtn.addEventListener('click', () => this.openAddManagementForm());
        if (this.elements.refreshBtn) this.elements.refreshBtn.addEventListener('click', () => this.refreshData());
        if (this.elements.printReportBtn) this.elements.printReportBtn.addEventListener('click', () => this.printReport());
        if (this.elements.exportReportBtn) this.elements.exportReportBtn.addEventListener('click', () => this.exportReport());
        if (this.elements.unusedReportBtn) this.elements.unusedReportBtn.addEventListener('click', () => this.generateUnusedVehiclesReport());

        // Filter Actions
        if (this.elements.resetFiltersBtn) this.elements.resetFiltersBtn.addEventListener('click', () => this.resetFilters());
        if (this.elements.applyFiltersBtn) this.elements.applyFiltersBtn.addEventListener('click', () => this.applyFilters());

        // Filter Inputs
        if (this.elements.dateRangeInput) this.elements.dateRangeInput.addEventListener('change', () => this.updateDateRange());
        if (this.elements.managementTypeSelect) this.elements.managementTypeSelect.addEventListener('change', () => this.updateManagementType());
        if (this.elements.vehicleCodeInput) this.elements.vehicleCodeInput.addEventListener('input', () => this.updateVehicleCode());
        if (this.elements.employeeSearchInput) this.elements.employeeSearchInput.addEventListener('focus', () => this.openEmployeeModal());
        if (this.elements.departmentSelect) this.elements.departmentSelect.addEventListener('change', () => this.loadSections());
        if (this.elements.sectionSelect) this.elements.sectionSelect.addEventListener('change', () => this.loadDivisions());
        if (this.elements.statusSelect) this.elements.statusSelect.addEventListener('change', () => this.updateStatus());
        if (this.elements.vehicleModeSelect) this.elements.vehicleModeSelect.addEventListener('change', () => this.updateVehicleMode());

        // View Tabs
        if (this.elements.tabAll) this.elements.tabAll.addEventListener('click', () => this.switchView('all'));
        if (this.elements.tabPending) this.elements.tabPending.addEventListener('click', () => this.switchView('pending'));
        if (this.elements.tabUsed) this.elements.tabUsed.addEventListener('click', () => this.switchView('used'));

        // Report Options
        this.elements.reportOptions.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const reportType = e.currentTarget.dataset.report;
                this.switchReportType(reportType);
            });
        });

        // Employee Search Modal
        if (this.elements.employeeModalSearchInput) {
            this.elements.employeeModalSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.searchEmployeesModal();
            });
        }
        if (this.elements.searchEmployeeModalBtn) this.elements.searchEmployeeModalBtn.addEventListener('click', () => this.searchEmployeesModal());
        if (this.elements.closeSearchBtn) this.elements.closeSearchBtn.addEventListener('click', () => this.closeEmployeeModal());

        // Close modal on overlay click
        if (this.elements.employeeModal) {
            this.elements.employeeModal.addEventListener('click', (e) => {
                if (e.target === this.elements.employeeModal) this.closeEmployeeModal();
            });
        }

        // === الحل النهائي والمضمون 100%: استخدام getAttribute لتجنب مشكلة dataset ===
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            // زر عرض الصور
            if (btn.classList.contains('show-photos-btn')) {
                const managementId = btn.getAttribute('data-management-id');
                const vehicleCode = btn.getAttribute('data-vehicle-code');
                if (managementId && vehicleCode) {
                    console.log('Opening photos for:', managementId, vehicleCode); // للتشخيص
                    this.showPhotosModal(managementId, vehicleCode);
                }
            }
            // زر عرض التفاصيل
            else if (btn.classList.contains('view-management-btn')) {
                const managementId = btn.getAttribute('data-management-id');
                if (managementId) {
                    this.viewManagement(managementId);
                }
            }
            // زر المتابعة
            else if (btn.classList.contains('follow-up-btn')) {
                const vehicleCode = btn.getAttribute('data-vehicle-code');
                if (vehicleCode) {
                    this.followUpManagement(vehicleCode);
                }
            }
            // زر تسليم السيارة (Pending) - الآن سيعمل
            else if (btn.classList.contains('return-vehicle-btn')) {
                const vehicleCode = btn.getAttribute('data-vehicle-code');
                if (vehicleCode) {
                    console.log('Returning vehicle:', vehicleCode); // للتشخيص
                    this.returnVehicle(vehicleCode);
                }
            }
            // زر عرض السجل (Used)
            else if (btn.classList.contains('view-history-btn')) {
                const vehicleCode = btn.getAttribute('data-vehicle-code');
                if (vehicleCode) {
                    this.viewVehicleHistory(vehicleCode);
                }
            }
        });
    }
    async loadInitialData() {
        try {
            this.showLoading();
            console.log('📥 Loading initial data...');
           
            // Load session data
            const sessionResponse = await fetch(this.API_SESSION, {
                credentials: 'include'
            });
           
            if (!sessionResponse.ok) {
                throw new Error('فشل في تحميل الجلسة');
            }
           
            const session = await sessionResponse.json();
           
            if (!session.success || !session.user?.emp_id) {
                throw new Error('غير مسجل الدخول');
            }
            const user = session.user;
            this.userLanguage = user.preferred_language || 'ar';
           
            // Load translations
            await this.loadTranslations();
           
            // Load references (departments, sections, divisions)
            await this.loadReferences();
           
            // Initialize date range picker
            this.initializeDateRangePicker();
           
            console.log('✅ Initial data loaded successfully');
        } catch (error) {
            console.error('❌ Error loading initial data:', error);
            this.showMessage('error', `فشل في التحميل: ${error.message}`);
        } finally {
            this.hideLoading();
        }
    }
    setPageDirection() {
        document.documentElement.dir = this.userLanguage === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.lang = this.userLanguage;
       
        document.body.style.direction = this.userLanguage === 'ar' ? 'rtl' : 'ltr';
        document.body.style.textAlign = this.userLanguage === 'ar' ? 'right' : 'left';
    }
    async loadTranslations() {
        try {
            const response = await fetch(`/vehicle_management/languages/${this.userLanguage}_vehicle_management.json`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            this.translations = await response.json();
            console.log('✅ Translations loaded successfully');
        } catch (error) {
            console.error('❌ Error loading translations:', error);
            this.translations = {};
        }
    }
    applyTranslations() {
        try {
            // Update page title
            document.title = this.getTranslation('page_title') || 'إدارة حركات المركبات';
           
            // Update all elements with data-lang-key attribute
            document.querySelectorAll('[data-lang-key]').forEach(element => {
                const key = element.getAttribute('data-lang-key');
                const translation = this.getTranslation(key);
               
                if (translation && element.tagName !== 'INPUT' && element.tagName !== 'TEXTAREA' && element.tagName !== 'SELECT' && element.tagName !== 'OPTION') {
                    element.textContent = translation;
                }
            });
           
            // Update placeholders
            document.querySelectorAll('[data-lang-key-placeholder]').forEach(element => {
                const key = element.getAttribute('data-lang-key-placeholder');
                const translation = this.getTranslation(key);
               
                if (translation && (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA')) {
                    element.placeholder = translation;
                }
            });
           
            // Update select options
            document.querySelectorAll('option[data-lang-key]').forEach(option => {
                const key = option.getAttribute('data-lang-key');
                const translation = this.getTranslation(key);
               
                if (translation) {
                    option.textContent = translation;
                }
            });
           
            console.log('✅ Translations applied');
        } catch (error) {
            console.error('❌ Error applying translations:', error);
        }
    }
    getTranslation(key) {
        if (!this.translations) return key;
       
        if (key.includes('.')) {
            const keys = key.split('.');
            let value = this.translations;
           
            for (const k of keys) {
                if (value && value[k] !== undefined) {
                    value = value[k];
                } else {
                    return key;
                }
            }
           
            return value;
        } else {
            return this.translations[key] || key;
        }
    }
    async loadReferences() {
        try {
            // Load departments
            const deptResponse = await fetch(`${this.API_REFERENCES}?type=departments&lang=${this.userLanguage}`);
            if (!deptResponse.ok) {
                throw new Error(`HTTP ${deptResponse.status}`);
            }
           
            const deptData = await deptResponse.json();
           
            if (deptData.success && deptData.departments && this.elements.departmentSelect) {
                this.populateSelect(this.elements.departmentSelect, deptData.departments, 'all_departments');
                console.log('✅ Departments loaded');
            }
        } catch (error) {
            console.error('❌ Error loading references:', error);
            if (this.elements.departmentSelect) {
                this.elements.departmentSelect.disabled = true;
                this.elements.departmentSelect.innerHTML = `<option value="">${this.getTranslation('all_departments') || 'جميع الإدارات'}</option>`;
            }
        }
    }
    async loadSections() {
        const departmentId = this.elements.departmentSelect?.value;
       
        if (!departmentId) {
            if (this.elements.sectionSelect) {
                this.elements.sectionSelect.disabled = true;
                this.elements.sectionSelect.innerHTML = `<option value="">${this.getTranslation('all_sections') || 'جميع الأقسام'}</option>`;
            }
            if (this.elements.divisionSelect) {
                this.elements.divisionSelect.disabled = true;
                this.elements.divisionSelect.innerHTML = `<option value="">${this.getTranslation('all_divisions') || 'جميع الشعب'}</option>`;
            }
            return;
        }
       
        try {
            const response = await fetch(`${this.API_REFERENCES}?type=sections&parent_id=${departmentId}&lang=${this.userLanguage}`);
            const data = await response.json();
           
            if (data.success && data.sections && this.elements.sectionSelect) {
                this.elements.sectionSelect.disabled = false;
                this.populateSelect(this.elements.sectionSelect, data.sections, 'all_sections');
               
                if (this.elements.divisionSelect) {
                    this.elements.divisionSelect.disabled = true;
                    this.elements.divisionSelect.innerHTML = `<option value="">${this.getTranslation('all_divisions') || 'جميع الشعب'}</option>`;
                }
                console.log('✅ Sections loaded');
            }
        } catch (error) {
            console.error('❌ Error loading sections:', error);
        }
    }
    async loadDivisions() {
        const sectionId = this.elements.sectionSelect?.value;
       
        if (!sectionId) {
            if (this.elements.divisionSelect) {
                this.elements.divisionSelect.disabled = true;
                this.elements.divisionSelect.innerHTML = `<option value="">${this.getTranslation('all_divisions') || 'جميع الشعب'}</option>`;
            }
            return;
        }
       
        try {
            const response = await fetch(`${this.API_REFERENCES}?type=divisions&parent_id=${sectionId}&lang=${this.userLanguage}`);
            const data = await response.json();
           
            if (data.success && data.divisions && this.elements.divisionSelect) {
                this.elements.divisionSelect.disabled = false;
                this.populateSelect(this.elements.divisionSelect, data.divisions, 'all_divisions');
                console.log('✅ Divisions loaded');
            }
        } catch (error) {
            console.error('❌ Error loading divisions:', error);
        }
    }
    populateSelect(selectElement, items, placeholderKey) {
        if (!selectElement) return;
       
        const currentValue = selectElement.value;
        const placeholder = this.getTranslation(placeholderKey) || 'الكل';
       
        selectElement.innerHTML = `<option value="">${placeholder}</option>`;
       
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id || item.department_id || item.section_id || item.division_id;
            option.textContent = item.name || item.name_ar || item.name_en || 'غير معروف';
            selectElement.appendChild(option);
        });
       
        if (currentValue) {
            selectElement.value = currentValue;
        }
    }
    initializeDateRangePicker() {
        if (!this.elements.dateRangeInput || !$.fn.daterangepicker) {
            console.warn('DateRangePicker not available');
            return;
        }
       
        const today = new Date();
        const lastWeek = new Date();
        lastWeek.setDate(today.getDate() - 7);
       
        const locale = {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: this.getTranslation('apply') || 'تطبيق',
            cancelLabel: this.getTranslation('cancel') || 'إلغاء',
            fromLabel: this.getTranslation('from') || 'من',
            toLabel: this.getTranslation('to') || 'إلى',
            customRangeLabel: this.getTranslation('custom_range') || 'مخصص',
            weekLabel: this.getTranslation('week') || 'أسبوع',
            daysOfWeek: moment.weekdaysMin(),
            monthNames: moment.months(),
            firstDay: 0
        };
       
        $(this.elements.dateRangeInput).daterangepicker({
            autoUpdateInput: true,
            startDate: moment(lastWeek),
            endDate: moment(today),
            locale: locale,
            ranges: {
                [this.getTranslation('today') || 'اليوم']: [moment(), moment()],
                [this.getTranslation('yesterday') || 'أمس']: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                [this.getTranslation('last_7_days') || 'آخر 7 أيام']: [moment().subtract(6, 'days'), moment()],
                [this.getTranslation('last_30_days') || 'آخر 30 يوم']: [moment().subtract(29, 'days'), moment()],
                [this.getTranslation('this_month') || 'هذا الشهر']: [moment().startOf('month'), moment().endOf('month')],
                [this.getTranslation('last_month') || 'الشهر الماضي']: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, (start, end, label) => {
            const formatted = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
            this.elements.dateRangeInput.value = formatted;
            this.filters.dateRange = {
                start: start.format('YYYY-MM-DD'),
                end: end.format('YYYY-MM-DD')
            };
        });
       
        // Set initial filter values
        this.filters.dateRange = {
            start: lastWeek.toISOString().split('T')[0],
            end: today.toISOString().split('T')[0]
        };
       
        console.log('✅ Date range picker initialized');
    }
    initializeDataTable() {
        if (!$.fn.DataTable) {
            console.error('DataTables library not loaded');
            return;
        }
       
        if (!this.elements.mainTable) {
            console.error('Table element not found');
            return;
        }
       
        // Destroy existing DataTable if any
        if ($.fn.DataTable.isDataTable(this.elements.mainTable)) {
            $(this.elements.mainTable).DataTable().destroy();
        }
       
        this.dataTable = $(this.elements.mainTable).DataTable({
            language: {
                url: this.userLanguage === 'ar' ?
                    '//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json' :
                    '//cdn.datatables.net/plug-ins/1.13.4/i18n/en-GB.json'
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'الكل']],
            responsive: true,
            order: [[0, 'desc']],
            columnDefs: [
                { targets: 0, width: '80px' },
                { targets: 1, width: '100px' },
                { targets: 2, width: '100px' },
                { targets: 7, width: '120px' },
                { targets: 8, width: '100px' },
                { targets: 9, width: '100px' },
                { targets: 10, width: '120px', orderable: false }
            ],
            initComplete: () => {
                console.log('✅ Main DataTable initialized');
            }
        });
    }
    initializePendingTable() {
        // Create wrapper for pending table if it doesn't exist
        const existingWrapper = document.getElementById('pending-table_wrapper');
        if (existingWrapper) {
            existingWrapper.remove();
        }
       
        if (!this.elements.tableContainer) return;
       
        const pendingTableHTML = `
            <div id="pending-table_wrapper" style="display: none; margin-top: 20px;">
                <div class="table-header">
                    <h3 data-lang-key="pending_vehicles_list">قائمة السيارات التي تحتاج تسليم</h3>
                    <button class="btn btn-secondary" id="export-pending-btn">
                        <i class="fas fa-download"></i>
                        <span data-lang-key="export_list">تصدير القائمة</span>
                    </button>
                </div>
                <table id="pending-table" class="managements-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-lang-key="vehicle_code">رمز المركبة</th>
                            <th data-lang-key="vehicle_mode">نوع السيارة</th>
                            <th data-lang-key="vehicle_type">نوع المركبة</th>
                            <th data-lang-key="manufacture_year">سنة الصنع</th>
                            <th data-lang-key="driver_name">اسم السائق</th>
                            <th data-lang-key="driver_phone">هاتف السائق</th>
                            <th data-lang-key="last_employee">آخر موظف استلم</th>
                            <th data-lang-key="last_pickup_date">تاريخ آخر استلام</th>
                            <th data-lang-key="department">الإدارة</th>
                            <th data-lang-key="status">الحالة</th>
                            <th data-lang-key="actions">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        `;
       
        this.elements.tableContainer.insertAdjacentHTML('beforeend', pendingTableHTML);
       
        // Apply translations to new table
        this.applyTranslations();
       
        // Initialize DataTable for pending vehicles
        const pendingTable = document.getElementById('pending-table');
        if (pendingTable && $.fn.DataTable) {
            // Destroy existing DataTable if any
            if ($.fn.DataTable.isDataTable(pendingTable)) {
                $(pendingTable).DataTable().destroy();
            }
           
            this.pendingTable = $(pendingTable).DataTable({
                language: {
                    url: this.userLanguage === 'ar' ?
                        '//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json' :
                        '//cdn.datatables.net/plug-ins/1.13.4/i18n/en-GB.json'
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'الكل']],
                responsive: true,
                columnDefs: [
                    { targets: 0, width: '100px' },
                    { targets: 1, width: '100px' },
                    { targets: 7, width: '120px' },
                    { targets: 9, width: '100px' },
                    { targets: 10, width: '120px', orderable: false }
                ]
            });
        }
       
        // Add event listener for export button
        const exportBtn = document.getElementById('export-pending-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportPendingVehicles());
        }
       
        console.log('✅ Pending table initialized');
    }
    initializeUsedTable() {
        // Create wrapper for used table if it doesn't exist
        const existingWrapper = document.getElementById('used-table_wrapper');
        if (existingWrapper) {
            existingWrapper.remove();
        }
       
        if (!this.elements.tableContainer) return;
       
        const usedTableHTML = `
            <div id="used-table_wrapper" style="display: none; margin-top: 20px;">
                <div class="table-header">
                    <h3 data-lang-key="used_vehicles_list">قائمة السيارات المستخدمة</h3>
                    <button class="btn btn-secondary" id="export-used-btn">
                        <i class="fas fa-download"></i>
                        <span data-lang-key="export_list">تصدير القائمة</span>
                    </button>
                </div>
                <table id="used-table" class="managements-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-lang-key="vehicle_code">رمز المركبة</th>
                            <th data-lang-key="vehicle_mode">نوع السيارة</th>
                            <th data-lang-key="vehicle_type">نوع المركبة</th>
                            <th data-lang-key="employee_name">اسم الموظف</th>
                            <th data-lang-key="department">الإدارة</th>
                            <th data-lang-key="section">القسم</th>
                            <th data-lang-key="division">الشعبة</th>
                            <th data-lang-key="last_movement_date">تاريخ آخر حركة</th>
                            <th data-lang-key="status">الحالة</th>
                            <th data-lang-key="actions">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        `;
       
        this.elements.tableContainer.insertAdjacentHTML('beforeend', usedTableHTML);
       
        // Apply translations to new table
        this.applyTranslations();
       
        // Initialize DataTable for used vehicles
        const usedTable = document.getElementById('used-table');
        if (usedTable && $.fn.DataTable) {
            // Destroy existing DataTable if any
            if ($.fn.DataTable.isDataTable(usedTable)) {
                $(usedTable).DataTable().destroy();
            }
           
            this.usedTable = $(usedTable).DataTable({
                language: {
                    url: this.userLanguage === 'ar' ?
                        '//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json' :
                        '//cdn.datatables.net/plug-ins/1.13.4/i18n/en-GB.json'
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'الكل']],
                responsive: true,
                columnDefs: [
                    { targets: 0, width: '100px' },
                    { targets: 1, width: '100px' },
                    { targets: 7, width: '120px' },
                    { targets: 8, width: '100px' },
                    { targets: 9, width: '120px', orderable: false }
                ]
            });
        }
       
        // Add event listener for export button
        const exportBtn = document.getElementById('export-used-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportUsedVehicles());
        }
       
        console.log('✅ Used table initialized');
    }
    // ==================== DATA LOADING METHODS ====================
    async loadManagements() {
        try {
            this.showLoading();
            console.log('📊 Loading managements with filters:', this.filters);
           
            const params = this.buildQueryParams();
            const url = `${this.API_MANAGEMENTS}?${params.toString()}`;
           
            console.log('🌐 Fetching URL:', url);
           
            const response = await fetch(url);
           
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
           
            const data = await response.json();
            console.log('📦 API Response:', data);
           
            if (data.success) {
                // Cache data
                this.currentStatistics = data.statistics || {};
                this.currentManagements = data.managements || [];
                this.currentPendingVehicles = data.pending_vehicles_list || [];
               
                // Update UI
                this.updateStatistics(this.currentStatistics);
                this.populateTable(this.currentManagements);
               
                // Update pending badge
                if (this.elements.pendingBadge) {
                    this.elements.pendingBadge.textContent = this.currentStatistics.pending_vehicles || 0;
                }
               
                const message = this.userLanguage === 'ar'
                    ? `تم تحميل ${data.total_count || 0} حركة`
                    : `Loaded ${data.total_count || 0} movements`;
                this.showMessage('success', message, 3000);
               
                console.log('✅ Managements loaded successfully');
            } else {
                throw new Error(data.message || 'فشل في جلب البيانات');
            }
           
        } catch (error) {
            console.error('❌ Error loading managements:', error);
            this.showMessage('error', `فشل في جلب البيانات: ${error.message}`, 5000);
           
            if (this.dataTable) {
                this.dataTable.clear().draw();
            }
        } finally {
            this.hideLoading();
        }
    }
    buildQueryParams() {
        const params = new URLSearchParams();
       
        // Add filters to params
        if (this.filters.dateRange) {
            params.append('start_date', this.filters.dateRange.start);
            params.append('end_date', this.filters.dateRange.end);
        }
       
        if (this.filters.managementType) {
            params.append('operation_type', this.filters.managementType);
        }
       
        if (this.filters.vehicleCode) {
            params.append('vehicle_code', this.filters.vehicleCode);
        }
       
        if (this.filters.employeeId) {
            params.append('employee_id', this.filters.employeeId);
        }
       
        if (this.filters.department) {
            params.append('department_id', this.filters.department);
        }
       
        if (this.filters.section) {
            params.append('section_id', this.filters.section);
        }
       
        if (this.filters.division) {
            params.append('division_id', this.filters.division);
        }
       
        if (this.filters.status) {
            params.append('status', this.filters.status);
        }
       
        if (this.filters.vehicleMode) {
            params.append('vehicle_mode', this.filters.vehicleMode);
        }
       
        params.append('lang', this.userLanguage);
        params.append('_t', Date.now());
       
        return params;
    }
    updateStatistics(statistics) {
        if (!statistics) return;
       
        console.log('📈 Updating statistics:', statistics);
       
        const updateElement = (element, value) => {
            if (element) element.textContent = value || 0;
        };
       
        updateElement(this.elements.totalManagementsElement, statistics.total_managements);
        updateElement(this.elements.pickupCountElement, statistics.pickup_count);
        updateElement(this.elements.returnCountElement, statistics.return_count);
        updateElement(this.elements.pendingCountElement, statistics.pending_vehicles);
        updateElement(this.elements.usedVehiclesElement, statistics.used_vehicles);
        updateElement(this.elements.unusedVehiclesElement, statistics.unused_vehicles);
        updateElement(this.elements.totalPrivateVehiclesElement, statistics.total_private_vehicles);
        updateElement(this.elements.totalShiftVehiclesElement, statistics.total_shift_vehicles);
        updateElement(this.elements.operationalVehiclesElement, statistics.operational_vehicles);
        updateElement(this.elements.outOfServiceVehiclesElement, statistics.out_of_service_vehicles);
        updateElement(this.elements.maintenanceVehiclesElement, statistics.maintenance_vehicles);
        updateElement(this.elements.usedPrivateVehiclesElement, statistics.used_private_vehicles);
        updateElement(this.elements.usedShiftVehiclesElement, statistics.used_shift_vehicles);
    }
    populateTable(managements) {
        if (!this.dataTable) {
            console.error('DataTable not initialized');
            return;
        }
       
        this.dataTable.clear();
       
        if (!managements || managements.length === 0) {
            this.dataTable.draw();
            console.log('ℹ️ No managements to display');
            return;
        }
       
        managements.forEach(management => {
            const row = this.createTableRow(management);
            this.dataTable.row.add(row);
        });
       
        this.dataTable.draw();
        console.log(`✅ Displayed ${managements.length} managements`);
    }
    // ==================== TABLE ROW CREATION METHODS ====================
    createTableRow(management) {
        const statusClass = management.vehicle_status === 'operational' ? 'operational' :
                          management.vehicle_status === 'out_of_service' ? 'out_of_service' : 'maintenance';
       
        const statusText = this.getStatusText(management.vehicle_status);
        const operationTypeText = management.operation_type === 'pickup' ?
            this.getTranslation('pickup') || 'استلام' :
            this.getTranslation('return') || 'إرجاع';
       
        const vehicleModeText = management.vehicle_mode === 'private' ?
            this.getTranslation('private') || 'خاصة' :
            this.getTranslation('shift') || 'ورديات';
       
        // Format date
        let formattedDate = this.getTranslation('not_specified') || 'غير محدد';
        if (management.movement_datetime) {
            try {
                const dateMoment = moment(management.movement_datetime);
                if (dateMoment.isValid()) {
                    formattedDate = dateMoment.format('YYYY-MM-DD HH:mm');
                }
            } catch (e) {
                console.error('Error formatting date:', e);
            }
        }
       
        // Check for photos
        const hasPhotos = management.photos_array && management.photos_array.length > 0;
        const photosCount = hasPhotos ? management.photos_array.length : 0;
       
        return [
            management.id || '',
            management.vehicle_code || '',
            `<span class="status-badge ${management.operation_type}">${operationTypeText}</span>`,
            management.employee_name || this.getTranslation('not_specified') || 'غير محدد',
            management.department_name || this.getTranslation('not_specified') || 'غير محدد',
            management.section_name || this.getTranslation('not_specified') || 'غير محدد',
            management.division_name || this.getTranslation('not_specified') || 'غير محدد',
            formattedDate,
            `<span class="status-badge vehicle-mode">${vehicleModeText}</span>`,
            `<span class="status-badge ${statusClass}">${statusText}</span>`,
            this.createActionButtons(management, hasPhotos, photosCount)
        ];
    }
        createPendingTableRow(vehicle) {
        const statusClass = vehicle.status === 'operational' ? 'operational' :
                          vehicle.status === 'out_of_service' ? 'out_of_service' : 'maintenance';

        const statusText = this.getStatusText(vehicle.status);
        const vehicleModeText = vehicle.vehicle_mode === 'private' ?
            this.getTranslation('private') || 'خاصة' :
            this.getTranslation('shift') || 'ورديات';

        // Format last pickup date
        let lastPickupDate = this.getTranslation('not_specified') || 'غير محدد';
        if (vehicle.last_pickup_date) {
            try {
                const dateMoment = moment(vehicle.last_pickup_date);
                if (dateMoment.isValid()) {
                    lastPickupDate = dateMoment.format('YYYY-MM-DD HH:mm');
                }
            } catch (e) {
                console.error('Error formatting date:', e);
            }
        }

        return [
            vehicle.vehicle_code || '',
            `<span class="status-badge vehicle-mode">${vehicleModeText}</span>`,
            vehicle.type || this.getTranslation('not_specified') || 'غير محدد',
            vehicle.manufacture_year || '',
            vehicle.driver_name || this.getTranslation('not_specified') || 'غير محدد',
            vehicle.driver_phone || '',
            vehicle.last_employee_name || this.getTranslation('not_specified') || 'غير محدد',
            lastPickupDate,
            vehicle.department_name || this.getTranslation('not_specified') || 'غير محدد',
            `<span class="status-badge ${statusClass}">${statusText}</span>`,
            `
                <div class="actions-cell">
                    <button class="btn btn-action btn-success return-vehicle-btn" 
                            data-vehicle-code="${vehicle.vehicle_code}"
                            title="${this.getTranslation('return_vehicle') || 'تسليم المركبة'}">
                        <i class="fas fa-check-circle"></i>
                        <span>${this.getTranslation('return_vehicle') || 'تسليم المركبة'}</span>
                    </button>
                </div>
            `
        ];
    }

    createActionButtons(management, hasPhotos, photosCount) {
        const viewBtnText = this.getTranslation('view_details') || 'عرض التفاصيل';
        const photosBtnText = this.getTranslation('view_photos') || 'عرض الصور';
        const followBtnText = this.getTranslation('follow_up') || 'متابعة';

        let photosButton = '';
        if (hasPhotos) {
            photosButton = `
                <button class="btn btn-action btn-info show-photos-btn"
                        data-management-id="${management.id}"
                        data-vehicle-code="${management.vehicle_code}"
                        title="${photosBtnText}">
                    <i class="fas fa-images"></i>
                    <span class="photo-count">${photosCount}</span>
                </button>
            `;
        }

        return `
            <div class="actions-cell">
                <button class="btn btn-action btn-primary view-management-btn"
                        data-management-id="${management.id}"
                        title="${viewBtnText}">
                    <i class="fas fa-eye"></i>
                </button>
                ${photosButton}
                <button class="btn btn-action btn-secondary follow-up-btn"
                        data-vehicle-code="${management.vehicle_code}"
                        title="${followBtnText}">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
        `;
    }

    getStatusText(status) {
        switch(status) {
            case 'operational':
                return this.getTranslation('operational') || 'تشغيلية';
            case 'out_of_service':
                return this.getTranslation('out_of_service') || 'خارج الخدمة';
            case 'maintenance':
                return this.getTranslation('maintenance') || 'صيانة';
            default:
                return this.getTranslation('not_specified') || 'غير محدد';
        }
    }
    // ==================== VIEW MANAGEMENT ====================
    switchView(viewType) {
        this.currentView = viewType;
       
        // Update tab styles
        const tabs = [
            this.elements.tabAll,
            this.elements.tabPending,
            this.elements.tabUsed
        ];
       
        tabs.forEach(tab => {
            if (tab) tab.classList.remove('active');
        });
       
        if (viewType === 'all' && this.elements.tabAll) this.elements.tabAll.classList.add('active');
        if (viewType === 'pending' && this.elements.tabPending) this.elements.tabPending.classList.add('active');
        if (viewType === 'used' && this.elements.tabUsed) this.elements.tabUsed.classList.add('active');
       
        // Show/hide tables
        const allTable = document.getElementById('managements-table_wrapper');
        const pendingTable = document.getElementById('pending-table_wrapper');
        const usedTable = document.getElementById('used-table_wrapper');
       
        if (allTable) allTable.style.display = viewType === 'all' ? 'block' : 'none';
        if (pendingTable) pendingTable.style.display = viewType === 'pending' ? 'block' : 'none';
        if (usedTable) usedTable.style.display = viewType === 'used' ? 'block' : 'none';
       
        // Load data for the selected view
        if (viewType === 'pending') {
            this.showPendingVehicles();
        } else if (viewType === 'used') {
            this.showUsedVehicles();
        } else {
            this.loadManagements();
        }
    }
    switchReportType(reportType) {
        this.currentReportType = reportType;
       
        // Update report option styles
        this.elements.reportOptions.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.report === reportType) {
                btn.classList.add('active');
            }
        });
    }
    // ==================== ACTION METHODS ====================
    openAddManagementForm() {
        window.open(this.ADD_MANAGEMENT_PAGE, '_blank');
    }
    viewManagement(managementId) {
        if (!managementId) {
            this.showMessage('error', 'معرف الحركة غير صالح');
            return;
        }
        window.open(`${this.ADD_MANAGEMENT_PAGE}?id=${managementId}`, '_blank');
    }
    followUpManagement(vehicleCode) {
        if (!vehicleCode) {
            this.showMessage('error', 'رمز المركبة غير صالح');
            return;
        }
        window.open(`${this.ADD_MANAGEMENT_PAGE}?vehicle=${vehicleCode}`, '_blank');
    }
    returnVehicle(vehicleCode) {
        if (!vehicleCode) {
            this.showMessage('error', 'رمز المركبة غير صالح');
            return;
        }
        window.open(`${this.ADD_MANAGEMENT_PAGE}?vehicle=${vehicleCode}&operation=return`, '_blank');
    }
    async refreshData() {
        this.showMessage('info', this.getTranslation('refreshing_data') || 'جاري تحديث البيانات...', 2000);
        await this.loadManagements();
    }
    // ==================== EMPLOYEE SEARCH MODAL ====================
    openEmployeeModal() {
        if (!this.elements.employeeModal) return;
        this.elements.employeeModal.style.display = 'flex';
        if (this.elements.employeeModalSearchInput) {
            this.elements.employeeModalSearchInput.focus();
        }
    }
    closeEmployeeModal() {
        if (!this.elements.employeeModal) return;
        this.elements.employeeModal.style.display = 'none';
        if (this.elements.employeeModalSearchInput) {
            this.elements.employeeModalSearchInput.value = '';
        }
        if (this.elements.employeeModalResults) {
            this.elements.employeeModalResults.innerHTML = '';
        }
    }
    async searchEmployeesModal() {
        const query = this.elements.employeeModalSearchInput ? this.elements.employeeModalSearchInput.value.trim() : '';
       
        if (!query) {
            this.showMessage('warning', 'يرجى إدخال نص للبحث');
            return;
        }
       
        try {
            const response = await fetch(`${this.API_EMPLOYEES}?q=${encodeURIComponent(query)}&lang=${this.userLanguage}`);
            const data = await response.json();
           
            this.displayEmployeeResults(data.employees || []);
           
        } catch (error) {
            console.error('خطأ في البحث:', error);
            this.showMessage('error', 'فشل في البحث عن الموظفين');
        }
    }
    displayEmployeeResults(employees) {
        if (!this.elements.employeeModalResults) return;
       
        this.elements.employeeModalResults.innerHTML = '';
       
        if (!employees || employees.length === 0) {
            this.elements.employeeModalResults.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-search mb-2"></i>
                    <p>لم يتم العثور على موظفين</p>
                </div>
            `;
            return;
        }
       
        employees.forEach(employee => {
            const employeeItem = document.createElement('div');
            employeeItem.className = 'employee-item';
            employeeItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${employee.full_name || 'غير محدد'}</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            ${employee.emp_id || 'بدون رقم'} •
                            ${employee.department || 'غير محدد'}
                        </div>
                    </div>
                    <i class="fas fa-check text-success" style="display: none;"></i>
                </div>
            `;
           
            employeeItem.addEventListener('click', (e) => {
                this.selectEmployee(employee, e);
            });
           
            this.elements.employeeModalResults.appendChild(employeeItem);
        });
    }
    selectEmployee(employee, event) {
        this.selectedEmployee = employee;
       
        if (this.elements.employeeSearchInput) {
            this.elements.employeeSearchInput.value = employee.full_name || employee.emp_id || '';
        }
       
        this.filters.employeeId = employee.emp_id || '';
       
        // Show selected state
        if (this.elements.employeeModalResults) {
            const employeeItems = this.elements.employeeModalResults.querySelectorAll('.employee-item');
            employeeItems.forEach(item => {
                item.classList.remove('active');
                const checkIcon = item.querySelector('.fa-check');
                if (checkIcon) {
                    checkIcon.style.display = 'none';
                }
            });
        }
       
        if (event && event.currentTarget) {
            const targetElement = event.currentTarget;
            targetElement.classList.add('active');
            const checkIcon = targetElement.querySelector('.fa-check');
            if (checkIcon) {
                checkIcon.style.display = 'inline-block';
            }
        }
       
        setTimeout(() => {
            this.closeEmployeeModal();
        }, 500);
    }
    // ==================== FILTER METHODS ====================
    updateDateRange() {
        if (!this.elements.dateRangeInput || !this.elements.dateRangeInput.value) {
            this.filters.dateRange = null;
            return;
        }
       
        const dates = this.elements.dateRangeInput.value.split(' - ');
        if (dates.length === 2) {
            this.filters.dateRange = {
                start: dates[0].trim(),
                end: dates[1].trim()
            };
        } else {
            this.filters.dateRange = null;
        }
    }
    updateManagementType() {
        if (!this.elements.managementTypeSelect) {
            this.filters.managementType = '';
            return;
        }
        this.filters.managementType = this.elements.managementTypeSelect.value;
    }
    updateVehicleCode() {
        if (!this.elements.vehicleCodeInput) {
            this.filters.vehicleCode = '';
            return;
        }
        this.filters.vehicleCode = this.elements.vehicleCodeInput.value.trim();
    }
    updateStatus() {
        if (!this.elements.statusSelect) {
            this.filters.status = '';
            return;
        }
        this.filters.status = this.elements.statusSelect.value;
    }
    updateVehicleMode() {
        if (!this.elements.vehicleModeSelect) {
            this.filters.vehicleMode = '';
            return;
        }
        this.filters.vehicleMode = this.elements.vehicleModeSelect.value;
    }
    resetFilters() {
        try {
            // Reset date range picker
            if (this.elements.dateRangeInput && $.fn.daterangepicker) {
                const today = new Date();
                const lastWeek = new Date();
                lastWeek.setDate(today.getDate() - 7);
               
                $(this.elements.dateRangeInput).data('daterangepicker').setStartDate(moment(lastWeek));
                $(this.elements.dateRangeInput).data('daterangepicker').setEndDate(moment(today));
                this.elements.dateRangeInput.value = lastWeek.toISOString().split('T')[0] + ' - ' + today.toISOString().split('T')[0];
            }
           
            // Reset select inputs
            if (this.elements.managementTypeSelect) this.elements.managementTypeSelect.value = '';
            if (this.elements.vehicleCodeInput) this.elements.vehicleCodeInput.value = '';
            if (this.elements.employeeSearchInput) this.elements.employeeSearchInput.value = '';
            if (this.elements.departmentSelect) this.elements.departmentSelect.value = '';
            if (this.elements.sectionSelect) {
                this.elements.sectionSelect.value = '';
                this.elements.sectionSelect.disabled = true;
            }
            if (this.elements.divisionSelect) {
                this.elements.divisionSelect.value = '';
                this.elements.divisionSelect.disabled = true;
            }
            if (this.elements.statusSelect) this.elements.statusSelect.value = '';
            if (this.elements.vehicleModeSelect) this.elements.vehicleModeSelect.value = '';
           
            // Reset filter state
            const today = new Date();
            const lastWeek = new Date();
            lastWeek.setDate(today.getDate() - 7);
           
            this.filters = {
                dateRange: {
                    start: lastWeek.toISOString().split('T')[0],
                    end: today.toISOString().split('T')[0]
                },
                managementType: '',
                vehicleCode: '',
                employeeId: '',
                department: '',
                section: '',
                division: '',
                status: '',
                vehicleMode: ''
            };
           
            this.selectedEmployee = null;
           
            // Reload managements with default filters
            this.loadManagements();
           
            this.showMessage('info', 'تم إعادة تعيين الفلاتر', 3000);
           
        } catch (error) {
            console.error('خطأ في إعادة تعيين الفلاتر:', error);
            this.showMessage('error', 'حدث خطأ في إعادة تعيين الفلاتر');
        }
    }
    applyFilters() {
        try {
            // Update all filter values
            this.updateDateRange();
            this.updateManagementType();
            this.updateVehicleCode();
            this.updateStatus();
            this.updateVehicleMode();
           
            // Update dependent filters
            if (this.elements.departmentSelect) {
                this.filters.department = this.elements.departmentSelect.value;
            }
            if (this.elements.sectionSelect) {
                this.filters.section = this.elements.departmentSelect && this.elements.departmentSelect.value ?
                    this.elements.sectionSelect.value : '';
            }
            if (this.elements.divisionSelect) {
                this.filters.division = this.elements.sectionSelect && this.elements.sectionSelect.value ?
                    this.elements.divisionSelect.value : '';
            }
           
            // Apply filters based on current view
            if (this.currentView === 'pending') {
                this.showPendingVehicles();
            } else if (this.currentView === 'used') {
                this.showUsedVehicles();
            } else {
                this.loadManagements();
            }
           
        } catch (error) {
            console.error('خطأ في تطبيق الفلاتر:', error);
            this.showMessage('error', 'حدث خطأ في تطبيق الفلاتر');
        }
    }
    // ==================== PHOTO GALLERY ====================
showPhotosModal(managementId, vehicleCode) {
    const management = this.currentManagements.find(m => m.id == managementId);
    if (!management || !management.photos_array || management.photos_array.length === 0) {
        this.showMessage('warning', 'لا توجد صور متاحة لهذه الحركة');
        return;
    }

    const modalHTML = `
        <div id="photos-modal" class="modal-overlay">
            <div class="modal-container wide-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-images"></i> <span>صور حركة المركبة: ${vehicleCode}</span></h3>
                    <button type="button" class="icon-btn close-btn close-photos-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="photos-gallery" class="photos-gallery">
                        ${management.photos_array.map((photoPath, index) => {
                            const fullUrl = this.cleanPhotoPath(photoPath);
                            return `
                                <div class="photo-item">
                                    <div class="photo-card">
                                        <img src="${fullUrl}"
                                             alt="صورة الحركة ${index + 1}"
                                             onerror="this.src='/vehicle_management/assets/img/no-image.jpg'">
                                        <div class="photo-overlay">
                                            <button class="btn btn-primary view-fullsize" data-photo="${fullUrl}">
                                                <i class="fas fa-expand"></i>
                                            </button>
                                            <a href="${fullUrl}" class="btn btn-secondary" target="_blank" download>
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;

    // باقي الكود...
        // Remove existing modal
        const existingModal = document.getElementById('photos-modal');
        if (existingModal) existingModal.remove();
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        const modal = document.getElementById('photos-modal');
        const closeBtn = modal.querySelector('.close-photos-modal');
        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        const viewButtons = modal.querySelectorAll('.view-fullsize');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const photoUrl = e.currentTarget.getAttribute('data-photo');
                this.showFullSizeImage(photoUrl);
            });
        });
    }
    showFullSizeImage(photoUrl) {
        const fullSizeHTML = `
            <div id="fullsize-modal" class="modal-overlay">
                <div class="modal-container fullsize-modal">
                    <div class="modal-header">
                        <button type="button" class="icon-btn close-btn close-fullsize">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <img src="${photoUrl}" alt="صورة كاملة الحجم" class="fullsize-image">
                    </div>
                </div>
            </div>
        `;
           
            // Remove existing modal
            const existingModal = document.getElementById('fullsize-modal');
            if (existingModal) existingModal.remove();
           
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', fullSizeHTML);
           
            // Add event listeners
            const modal = document.getElementById('fullsize-modal');
            const closeBtn = modal.querySelector('.close-fullsize');
           
            closeBtn.addEventListener('click', () => modal.remove());
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.remove();
            });
        }
    // ==================== REPORT METHODS ====================
    printReport() {
        if (!this.currentStatistics || Object.keys(this.currentStatistics).length === 0) {
            this.showMessage('warning', 'لا توجد بيانات متاحة للطباعة');
            return;
        }
       
        // Create a new window for printing
        const printWindow = window.open('', '_blank');
       
        // Get filter information
        const filterInfo = this.getFilterInfo();
       
        // Get report title based on current report type
        const reportTitles = {
            'all': 'تقرير كامل - جميع الحركات',
            'used': 'تقرير السيارات المستخدمة',
            'pending': 'تقرير السيارات التي تحتاج تسليم',
            'summary': 'تقرير إحصائي'
        };
       
        const reportTitle = reportTitles[this.currentReportType] || 'تقرير';
       
        // Get statistics HTML
        const statsHTML = this.getStatisticsHTML();
       
        // Get table HTML based on current view
        let tableHTML = '';
        if (this.currentReportType === 'summary') {
            tableHTML = this.getSummaryTableHTML();
        } else if (this.currentReportType === 'pending') {
            tableHTML = this.getPendingTableHTML();
        } else {
            tableHTML = this.getManagementsTableHTML();
        }
       
        // Build print content
        const printContent = `
            <html>
            <head>
                <title>${reportTitle}</title>
                <style>
                    body { font-family: 'Noto Sans Arabic', Arial, sans-serif; direction: rtl; }
                    h1 { text-align: center; color: #333; }
                    .print-header { margin-bottom: 20px; }
                    .filter-info {
                        margin: 15px 0;
                        padding: 10px;
                        background: #f8f9fa;
                        border: 1px solid #dee2e6;
                        border-radius: 5px;
                    }
                    .statistics-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                        gap: 15px;
                        margin: 20px 0;
                    }
                    .stat-card {
                        padding: 15px;
                        border-radius: 8px;
                        color: white;
                        text-align: center;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                        font-size: 12px;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: right;
                    }
                    th {
                        background-color: #f2f2f2;
                        font-weight: bold;
                    }
                    .status-badge {
                        padding: 3px 8px;
                        border-radius: 12px;
                        font-size: 11px;
                        display: inline-block;
                    }
                    @media print {
                        .no-print { display: none; }
                        body { font-size: 10pt; }
                        table { font-size: 9pt; }
                    }
                    .print-footer {
                        margin-top: 30px;
                        padding-top: 10px;
                        border-top: 1px solid #ddd;
                        text-align: center;
                        font-size: 11px;
                        color: #666;
                    }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h1>${reportTitle}</h1>
                    <div class="filter-info">
                        <strong>الفلاتر المطبقة:</strong><br>
                        ${filterInfo}
                        <br>
                        <strong>تاريخ الطباعة:</strong> ${new Date().toLocaleString('ar-SA')}
                    </div>
                    ${this.currentReportType === 'summary' ? statsHTML : ''}
                </div>
               
                ${tableHTML}
               
                <div class="print-footer">
                    <p>تم إنشاء التقرير بواسطة نظام إدارة حركات المركبات</p>
                </div>
            </body>
            </html>
        `;
       
        printWindow.document.write(printContent);
        printWindow.document.close();
       
        // Print after page loads
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }
    exportReport() {
        let data = [];
        let fileName = '';
       
        switch(this.currentReportType) {
            case 'all':
                data = this.prepareAllDataForExport();
                fileName = 'تقرير_كامل_جميع_البيانات';
                break;
            case 'pending':
                data = this.preparePendingDataForExport();
                fileName = 'تقرير_السيارات_التي_تحتاج_تسليم';
                break;
            case 'summary':
                data = this.prepareSummaryDataForExport();
                fileName = 'تقرير_إحصائي';
                break;
            default:
                this.showMessage('warning', 'هذا النوع من التقرير غير مدعوم للتصدير');
                return;
        }
       
        if (data.length === 0) {
            this.showMessage('warning', 'لا توجد بيانات للتصدير');
            return;
        }
       
        // Check if XLSX library is available
        if (typeof XLSX === 'undefined') {
            this.showMessage('error', 'مكتبة Excel غير متاحة. يرجى إعادة تحميل الصفحة.');
            return;
        }
       
        try {
            // Create Excel workbook
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.json_to_sheet(data);
           
            // Add worksheet to workbook
            XLSX.utils.book_append_sheet(wb, ws, 'تقرير');
           
            // Generate file name with date
            const dateStr = new Date().toISOString().split('T')[0];
            const fullFileName = `${fileName}_${dateStr}.xlsx`;
           
            // Save file
            XLSX.writeFile(wb, fullFileName);
           
            this.showMessage('success', 'تم تصدير التقرير بنجاح', 3000);
        } catch (error) {
            console.error('Error exporting report:', error);
            this.showMessage('error', `فشل في تصدير التقرير: ${error.message}`);
        }
    }
    // تقرير السيارات غير المستخدمة
    async generateUnusedVehiclesReport() {
        try {
            this.showLoading();
            
            // جلب بيانات السيارات غير المستخدمة من API
            const params = new URLSearchParams({
                status: 'unused',
                lang: this.userLanguage,
                _t: Date.now()
            });
            
            const response = await fetch(`${this.API_MANAGEMENTS}?${params.toString()}`);
            const data = await response.json();
            
            if (data.success && data.vehicles) {
                // إنشاء تقرير Excel
                this.exportUnusedVehiclesReport(data.vehicles);
            } else {
                throw new Error(data.message || 'فشل في جلب بيانات السيارات غير المستخدمة');
            }
            
        } catch (error) {
            console.error('❌ Error generating unused vehicles report:', error);
            this.showMessage('error', `فشل في إنشاء التقرير: ${error.message}`);
        } finally {
            this.hideLoading();
        }
    }
    
    exportUnusedVehiclesReport(vehicles) {
        if (!vehicles || vehicles.length === 0) {
            this.showMessage('warning', 'لا توجد سيارات غير مستخدمة للتصدير');
            return;
        }
        
        try {
            const data = vehicles.map(vehicle => ({
                'رمز المركبة': vehicle.vehicle_code,
                'نوع السيارة': vehicle.vehicle_mode === 'private' ? 'خاصة' : 'ورديات',
                'نوع المركبة': vehicle.type,
                'سنة الصنع': vehicle.manufacture_year,
                'اسم السائق': vehicle.driver_name,
                'هاتف السائق': vehicle.driver_phone,
                'حالة السيارة': this.getStatusText(vehicle.status),
                'ملاحظات': vehicle.notes || ''
            }));
            
            // إنشاء ملف Excel
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.json_to_sheet(data);
            
            // تنسيق الأعمدة
            const wscols = [
                { wch: 15 },  // رمز المركبة
                { wch: 12 },  // نوع السيارة
                { wch: 20 },  // نوع المركبة
                { wch: 10 },  // سنة الصنع
                { wch: 20 },  // اسم السائق
                { wch: 15 },  // هاتف السائق
                { wch: 15 },  // حالة السيارة
                { wch: 30 }   // ملاحظات
            ];
            ws['!cols'] = wscols;
            
            XLSX.utils.book_append_sheet(wb, ws, 'السيارات غير المستخدمة');
            
            // حفظ الملف
            const dateStr = new Date().toISOString().split('T')[0];
            XLSX.writeFile(wb, `تقرير_السيارات_غير_المستخدمة_${dateStr}.xlsx`);
            
            this.showMessage('success', 'تم إنشاء تقرير السيارات غير المستخدمة بنجاح', 3000);
            
        } catch (error) {
            console.error('Error exporting unused vehicles report:', error);
            this.showMessage('error', `فشل في تصدير التقرير: ${error.message}`);
        }
    }
    
    getFilterInfo() {
        let info = [];
       
        if (this.filters.dateRange) {
            info.push(`الفترة: من ${this.filters.dateRange.start} إلى ${this.filters.dateRange.end}`);
        }
       
        if (this.filters.managementType) {
            const typeText = this.elements.managementTypeSelect ?
                this.elements.managementTypeSelect.options[this.elements.managementTypeSelect.selectedIndex].text :
                this.filters.managementType;
            info.push(`نوع الحركة: ${typeText}`);
        }
       
        if (this.filters.vehicleCode) {
            info.push(`رمز المركبة: ${this.filters.vehicleCode}`);
        }
       
        if (this.filters.employeeId && this.selectedEmployee) {
            info.push(`الموظف: ${this.selectedEmployee.full_name || this.selectedEmployee.emp_id}`);
        }
       
        if (this.filters.department) {
            const deptText = this.elements.departmentSelect ?
                this.elements.departmentSelect.options[this.elements.departmentSelect.selectedIndex].text :
                this.filters.department;
            info.push(`الإدارة: ${deptText}`);
        }
       
        if (this.filters.section) {
            const sectionText = this.elements.sectionSelect ?
                this.elements.sectionSelect.options[this.elements.sectionSelect.selectedIndex].text :
                this.filters.section;
            info.push(`القسم: ${sectionText}`);
        }
       
        if (this.filters.division) {
            const divisionText = this.elements.divisionSelect ?
                this.elements.divisionSelect.options[this.elements.divisionSelect.selectedIndex].text :
                this.filters.division;
            info.push(`الشعبة: ${divisionText}`);
        }
       
        if (this.filters.status) {
            const statusText = this.elements.statusSelect ?
                this.elements.statusSelect.options[this.elements.statusSelect.selectedIndex].text :
                this.filters.status;
            info.push(`حالة السيارة: ${statusText}`);
        }
       
        if (this.filters.vehicleMode) {
            const modeText = this.elements.vehicleModeSelect ?
                this.elements.vehicleModeSelect.options[this.elements.vehicleModeSelect.selectedIndex].text :
                this.filters.vehicleMode;
            info.push(`نوع السيارة: ${modeText}`);
        }
       
        return info.length > 0 ? info.join(' | ') : 'لا توجد فلاتر مطبقة';
    }
    getStatisticsHTML() {
        const stats = this.currentStatistics;
       
        return `
            <div class="statistics-grid">
                <div class="stat-card" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                    <h3>${stats.total_managements || 0}</h3>
                    <p>إجمالي الحركات</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);">
                    <h3>${stats.pickup_count || 0}</h3>
                    <p>عمليات الاستلام</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);">
                    <h3>${stats.return_count || 0}</h3>
                    <p>عمليات الإرجاع</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%);">
                    <h3>${stats.pending_vehicles || 0}</h3>
                    <p>مركبات تحتاج تسليم</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3>${stats.used_vehicles || 0}</h3>
                    <p>السيارات المستخدمة</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                    <h3>${stats.unused_vehicles || 0}</h3>
                    <p>السيارات غير المستخدمة</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #6610f2 0%, #6f42c1 100%);">
                    <h3>${stats.total_private_vehicles || 0}</h3>
                    <p>السيارات الخاصة</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #e83e8c 0%, #d63384 100%);">
                    <h3>${stats.total_shift_vehicles || 0}</h3>
                    <p>السيارات بالورديات</p>
                </div>
            </div>
        `;
    }
    getManagementsTableHTML() {
        if (!this.currentManagements || this.currentManagements.length === 0) {
            return '<p style="text-align:center; color:#666; padding:20px;">لا توجد حركات للعرض</p>';
        }
       
        let tableHTML = '<table>';
        tableHTML += `
            <thead>
                <tr>
                    <th>رقم الحركة</th>
                    <th>رمز المركبة</th>
                    <th>نوع العملية</th>
                    <th>اسم الموظف</th>
                    <th>الإدارة</th>
                    <th>القسم</th>
                    <th>الشعبة</th>
                    <th>تاريخ الحركة</th>
                    <th>نوع السيارة</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
        `;
       
        this.currentManagements.forEach(management => {
            const operationTypeText = management.operation_type === 'pickup' ? 'استلام' : 'إرجاع';
            const vehicleModeText = management.vehicle_mode === 'private' ? 'خاصة' : 'ورديات';
            const statusText = this.getStatusText(management.vehicle_status);
           
            let formattedDate = 'غير محدد';
            if (management.movement_datetime) {
                try {
                    const dateMoment = moment(management.movement_datetime);
                    if (dateMoment.isValid()) {
                        formattedDate = dateMoment.format('YYYY-MM-DD HH:mm');
                    }
                } catch (e) {}
            }
           
            tableHTML += `
                <tr>
                    <td>${management.id || ''}</td>
                    <td>${management.vehicle_code || ''}</td>
                    <td>${operationTypeText}</td>
                    <td>${management.employee_name || 'غير محدد'}</td>
                    <td>${management.department_name || 'غير محدد'}</td>
                    <td>${management.section_name || 'غير محدد'}</td>
                    <td>${management.division_name || 'غير محدد'}</td>
                    <td>${formattedDate}</td>
                    <td>${vehicleModeText}</td>
                    <td>${statusText}</td>
                </tr>
            `;
        });
       
        tableHTML += '</tbody></table>';
        return tableHTML;
    }
    getPendingTableHTML() {
        if (!this.currentPendingVehicles || this.currentPendingVehicles.length === 0) {
            return '<p style="text-align:center; color:#666; padding:20px;">لا توجد سيارات تحتاج تسليم</p>';
        }
       
        let tableHTML = '<table>';
        tableHTML += `
            <thead>
                <tr>
                    <th>رمز المركبة</th>
                    <th>نوع السيارة</th>
                    <th>نوع المركبة</th>
                    <th>سنة الصنع</th>
                    <th>اسم السائق</th>
                    <th>هاتف السائق</th>
                    <th>آخر موظف استلم</th>
                    <th>تاريخ آخر استلام</th>
                    <th>الإدارة</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
        `;
       
        this.currentPendingVehicles.forEach(vehicle => {
            const vehicleModeText = vehicle.vehicle_mode === 'private' ? 'خاصة' : 'ورديات';
            const statusText = this.getStatusText(vehicle.status);
           
            let lastPickupDate = 'غير محدد';
            if (vehicle.last_pickup_date) {
                try {
                    const dateMoment = moment(vehicle.last_pickup_date);
                    if (dateMoment.isValid()) {
                        lastPickupDate = dateMoment.format('YYYY-MM-DD HH:mm');
                    }
                } catch (e) {}
            }
           
            tableHTML += `
                <tr>
                    <td>${vehicle.vehicle_code || ''}</td>
                    <td>${vehicleModeText}</td>
                    <td>${vehicle.type || 'غير محدد'}</td>
                    <td>${vehicle.manufacture_year || ''}</td>
                    <td>${vehicle.driver_name || 'غير محدد'}</td>
                    <td>${vehicle.driver_phone || ''}</td>
                    <td>${vehicle.last_employee_name || 'غير محدد'}</td>
                    <td>${lastPickupDate}</td>
                    <td>${vehicle.department_name || 'غير محدد'}</td>
                    <td>${statusText}</td>
                </tr>
            `;
        });
       
        tableHTML += '</tbody></table>';
        return tableHTML;
    }
    getSummaryTableHTML() {
        const stats = this.currentStatistics;
       
        const totalVehicles = stats.total_vehicles || 0;
        const usedPercentage = totalVehicles > 0 ? ((stats.used_vehicles || 0) / totalVehicles * 100).toFixed(1) : 0;
        const unusedPercentage = totalVehicles > 0 ? ((stats.unused_vehicles || 0) / totalVehicles * 100).toFixed(1) : 0;
        const privatePercentage = totalVehicles > 0 ? ((stats.total_private_vehicles || 0) / totalVehicles * 100).toFixed(1) : 0;
        const shiftPercentage = totalVehicles > 0 ? ((stats.total_shift_vehicles || 0) / totalVehicles * 100).toFixed(1) : 0;
        const operationalPercentage = totalVehicles > 0 ? ((stats.operational_vehicles || 0) / totalVehicles * 100).toFixed(1) : 0;
        const outOfServicePercentage = totalVehicles > 0 ? ((stats.out_of_service_vehicles || 0) / totalVehicles * 100).toFixed(1) : 0;
        const maintenancePercentage = totalVehicles > 0 ? ((stats.maintenance_vehicles || 0) / totalVehicles * 100).toFixed(1) : 0;
       
        return `
            <table>
                <thead>
                    <tr>
                        <th>المؤشر</th>
                        <th>القيمة</th>
                        <th>النسبة</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>إجمالي السيارات</td>
                        <td>${totalVehicles}</td>
                        <td>100%</td>
                    </tr>
                    <tr>
                        <td>السيارات المستخدمة</td>
                        <td>${stats.used_vehicles || 0}</td>
                        <td>${usedPercentage}%</td>
                    </tr>
                    <tr>
                        <td>السيارات غير المستخدمة</td>
                        <td>${stats.unused_vehicles || 0}</td>
                        <td>${unusedPercentage}%</td>
                    </tr>
                    <tr>
                        <td>السيارات الخاصة</td>
                        <td>${stats.total_private_vehicles || 0}</td>
                        <td>${privatePercentage}%</td>
                    </tr>
                    <tr>
                        <td>السيارات بالورديات</td>
                        <td>${stats.total_shift_vehicles || 0}</td>
                        <td>${shiftPercentage}%</td>
                    </tr>
                    <tr>
                        <td>السيارات التشغيلية</td>
                        <td>${stats.operational_vehicles || 0}</td>
                        <td>${operationalPercentage}%</td>
                    </tr>
                    <tr>
                        <td>السيارات خارج الخدمة</td>
                        <td>${stats.out_of_service_vehicles || 0}</td>
                        <td>${outOfServicePercentage}%</td>
                    </tr>
                    <tr>
                        <td>السيارات تحت الصيانة</td>
                        <td>${stats.maintenance_vehicles || 0}</td>
                        <td>${maintenancePercentage}%</td>
                    </tr>
                </tbody>
            </table>
        `;
    }
    prepareAllDataForExport() {
        const data = [];
       
        if (this.currentManagements && this.currentManagements.length > 0) {
            this.currentManagements.forEach(item => {
                data.push({
                    'رقم الحركة': item.id,
                    'رمز المركبة': item.vehicle_code,
                    'نوع العملية': item.operation_type === 'pickup' ? 'استلام' : 'إرجاع',
                    'اسم الموظف': item.employee_name,
                    'الإدارة': item.department_name,
                    'القسم': item.section_name,
                    'الشعبة': item.division_name,
                    'تاريخ الحركة': moment(item.movement_datetime).format('YYYY-MM-DD HH:mm'),
                    'نوع السيارة': item.vehicle_mode === 'private' ? 'خاصة' : 'ورديات',
                    'حالة السيارة': this.getStatusText(item.vehicle_status),
                    'ملاحظات': item.notes || ''
                });
            });
        }
       
        return data;
    }
    preparePendingDataForExport() {
        const data = [];
       
        if (this.currentPendingVehicles && this.currentPendingVehicles.length > 0) {
            this.currentPendingVehicles.forEach(vehicle => {
                data.push({
                    'رمز المركبة': vehicle.vehicle_code,
                    'نوع السيارة': vehicle.vehicle_mode === 'private' ? 'خاصة' : 'ورديات',
                    'نوع المركبة': vehicle.type,
                    'سنة الصنع': vehicle.manufacture_year,
                    'اسم السائق': vehicle.driver_name,
                    'هاتف السائق': vehicle.driver_phone,
                    'آخر موظف استلم': vehicle.last_employee_name,
                    'تاريخ آخر استلام': moment(vehicle.last_pickup_date).format('YYYY-MM-DD HH:mm'),
                    'الإدارة': vehicle.department_name,
                    'حالة السيارة': this.getStatusText(vehicle.status)
                });
            });
        }
       
        return data;
    }
    prepareSummaryDataForExport() {
        const stats = this.currentStatistics;
        const totalVehicles = stats.total_vehicles || 0;
       
        const data = [{
            'إجمالي الحركات': stats.total_managements || 0,
            'عمليات الاستلام': stats.pickup_count || 0,
            'عمليات الإرجاع': stats.return_count || 0,
            'السيارات تحتاج تسليم': stats.pending_vehicles || 0,
            'السيارات المستخدمة': stats.used_vehicles || 0,
            'السيارات غير المستخدمة': stats.unused_vehicles || 0,
            'السيارات الخاصة': stats.total_private_vehicles || 0,
            'السيارات بالورديات': stats.total_shift_vehicles || 0,
            'السيارات التشغيلية': stats.operational_vehicles || 0,
            'السيارات خارج الخدمة': stats.out_of_service_vehicles || 0,
            'السيارات تحت الصيانة': stats.maintenance_vehicles || 0,
            'السيارات الخاصة المستخدمة': stats.used_private_vehicles || 0,
            'السيارات بالورديات المستخدمة': stats.used_shift_vehicles || 0,
            'إجمالي السيارات': totalVehicles,
            'نسبة المستخدمة': totalVehicles > 0 ? ((stats.used_vehicles || 0) / totalVehicles * 100).toFixed(1) + '%' : '0%',
            'نسبة غير المستخدمة': totalVehicles > 0 ? ((stats.unused_vehicles || 0) / totalVehicles * 100).toFixed(1) + '%' : '0%'
        }];
       
        return data;
    }
    // ==================== VIEW SPECIFIC METHODS ====================
    showPendingVehicles() {
        if (this.pendingTable) {
            this.pendingTable.clear();
           
            if (this.currentPendingVehicles && this.currentPendingVehicles.length > 0) {
                this.currentPendingVehicles.forEach(vehicle => {
                    const row = this.createPendingTableRow(vehicle);
                    this.pendingTable.row.add(row);
                });
            }
           
            this.pendingTable.draw();
        }
    }
    showUsedVehicles() {
        if (this.usedTable) {
            this.usedTable.clear();
           
            // Extract unique vehicles from managements
            const usedVehiclesMap = new Map();
           
            if (this.currentManagements && this.currentManagements.length > 0) {
                this.currentManagements.forEach(management => {
                    if (!usedVehiclesMap.has(management.vehicle_code)) {
                        usedVehiclesMap.set(management.vehicle_code, {
                            vehicle_code: management.vehicle_code,
                            vehicle_type: management.vehicle_type || this.getTranslation('not_specified'),
                            vehicle_mode: management.vehicle_mode,
                            vehicle_status: management.vehicle_status,
                            employee_name: management.employee_name,
                            department_name: management.department_name,
                            section_name: management.section_name,
                            division_name: management.division_name,
                            last_movement: management.movement_datetime
                        });
                    }
                });
               
                usedVehiclesMap.forEach(vehicle => {
                    const row = this.createUsedVehicleRow(vehicle);
                    this.usedTable.row.add(row);
                });
            }
           
            this.usedTable.draw();
        }
    }
    createUsedVehicleRow(vehicle) {
        const statusClass = vehicle.vehicle_status === 'operational' ? 'operational' :
                          vehicle.vehicle_status === 'out_of_service' ? 'out_of_service' : 'maintenance';
       
        const statusText = this.getStatusText(vehicle.vehicle_status);
        const vehicleModeText = vehicle.vehicle_mode === 'private' ?
            this.getTranslation('private') || 'خاصة' :
            this.getTranslation('shift') || 'ورديات';
       
        let lastMovementDate = this.getTranslation('not_specified') || 'غير محدد';
        if (vehicle.last_movement) {
            try {
                const dateMoment = moment(vehicle.last_movement);
                if (dateMoment.isValid()) {
                    lastMovementDate = dateMoment.format('YYYY-MM-DD HH:mm');
                }
            } catch (e) {
                console.error('Error formatting date:', e);
            }
        }
       
        return [
            vehicle.vehicle_code || '',
            `<span class="status-badge vehicle-mode">${vehicleModeText}</span>`,
            vehicle.vehicle_type || this.getTranslation('not_specified') || 'غير محدد',
            vehicle.employee_name || this.getTranslation('not_specified') || 'غير محدد',
            vehicle.department_name || this.getTranslation('not_specified') || 'غير محدد',
            vehicle.section_name || this.getTranslation('not_specified') || 'غير محدد',
            vehicle.division_name || this.getTranslation('not_specified') || 'غير محدد',
            lastMovementDate,
            `<span class="status-badge ${statusClass}">${statusText}</span>`,
            `
                <div class="actions-cell">
                    <button class="btn btn-action btn-primary view-history-btn" data-vehicle-code="${vehicle.vehicle_code}"
                            title="${this.getTranslation('view_history') || 'عرض السجل'}">
                        <i class="fas fa-history"></i>
                    </button>
                    <button class="btn btn-action btn-secondary follow-up-btn" data-vehicle-code="${vehicle.vehicle_code}"
                            title="${this.getTranslation('follow_up') || 'متابعة'}">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                </div>
            `
        ];
    }
    viewVehicleHistory(vehicleCode) {
        if (!vehicleCode) {
            this.showMessage('error', 'رمز المركبة غير صالح');
            return;
        }
        window.open(`${this.ADD_MANAGEMENT_PAGE}?vehicle=${vehicleCode}&view=history`, '_blank');
    }
    exportPendingVehicles() {
        if (!this.currentPendingVehicles || this.currentPendingVehicles.length === 0) {
            this.showMessage('warning', 'لا توجد بيانات للتصدير');
            return;
        }
       
        // Create CSV content
        let csv = 'رمز المركبة,نوع السيارة,نوع المركبة,سنة الصنع,اسم السائق,هاتف السائق,آخر موظف استلم,تاريخ آخر استلام,الإدارة,حالة السيارة\n';
       
        this.currentPendingVehicles.forEach(vehicle => {
            const row = [
                vehicle.vehicle_code || '',
                vehicle.vehicle_mode === 'private' ? 'خاصة' : 'ورديات',
                vehicle.type || '',
                vehicle.manufacture_year || '',
                vehicle.driver_name || '',
                vehicle.driver_phone || '',
                vehicle.last_employee_name || '',
                moment(vehicle.last_pickup_date).format('YYYY-MM-DD HH:mm'),
                vehicle.department_name || '',
                this.getStatusText(vehicle.status)
            ];
           
            // Escape quotes and wrap in quotes if contains comma
            const escapedRow = row.map(cell => {
                const cellStr = String(cell);
                if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                    return '"' + cellStr.replace(/"/g, '""') + '"';
                }
                return cellStr;
            });
           
            csv += escapedRow.join(',') + '\n';
        });
       
        // Create download link
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `السيارات_المحتاجة_تسليم_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
       
        this.showMessage('success', 'تم تصدير القائمة بنجاح');
    }
    exportUsedVehicles() {
        // Implement similar to exportPendingVehicles if needed
        this.showMessage('info', 'تصدير السيارات المستخدمة قيد التطوير');
    }
    // ==================== UTILITY METHODS ====================
    showLoading() {
        if (this.elements.loadingOverlay) {
            this.elements.loadingOverlay.style.display = 'flex';
        }
    }
    hideLoading() {
        if (this.elements.loadingOverlay) {
            this.elements.loadingOverlay.style.display = 'none';
        }
    }
    showMessage(type, text, duration = 5000) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.alert-message');
        existingMessages.forEach(msg => {
            if (msg.parentNode) {
                msg.parentNode.removeChild(msg);
            }
        });
       
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert-message alert-${type} fade-in`;
       
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
       
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
       
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            left: 20px;
            max-width: 500px;
            margin: 0 auto;
            z-index: 9999;
            background: ${type === 'success' ? '#d4edda' :
                       type === 'error' ? '#f8d7da' :
                       type === 'warning' ? '#fff3cd' : '#d1ecf1'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' :
                               type === 'error' ? '#f5c6cb' :
                               type === 'warning' ? '#ffeaa7' : '#bee5eb'};
            color: ${type === 'success' ? '#155724' :
                    type === 'error' ? '#721c24' :
                    type === 'warning' ? '#856404' : '#0c5460'};
            padding: 12px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
       
        messageDiv.innerHTML = `
            <i class="fas ${icons[type] || icons.info}" style="color: ${colors[type] || colors.info}; font-size: 18px;"></i>
            <span style="flex: 1; font-size: 14px;">${text}</span>
            <button type="button" class="close-message" style="background: none; border: none; color: inherit; cursor: pointer; padding: 4px; opacity: 0.7; transition: opacity 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        `;
       
        document.body.appendChild(messageDiv);
       
        // Auto-remove after duration
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.style.opacity = '0';
                messageDiv.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 300);
            }
        }, duration);
       
        // Close button
        const closeBtn = messageDiv.querySelector('.close-message');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            });
        }
    }
}
// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('📄 DOM loaded, initializing VehicleManagementPage...');
   
    // Check if all required libraries are loaded
    const missingLibraries = [];
   
    if (typeof $ === 'undefined') {
        missingLibraries.push('jQuery');
    }
   
    if (typeof $.fn.DataTable === 'undefined') {
        missingLibraries.push('DataTables');
    }
   
    if (typeof moment === 'undefined') {
        missingLibraries.push('Moment.js');
    }
   
    if (typeof XLSX === 'undefined') {
        console.warn('SheetJS is not loaded. Excel export will not work properly.');
    }
   
    if (typeof $.fn.daterangepicker === 'undefined') {
        console.warn('DateRangePicker is not loaded. Date range filtering will not work properly.');
    }
   
    if (missingLibraries.length > 0) {
        console.error('❌ Missing required libraries:', missingLibraries.join(', '));
        alert(`المكتبات التالية غير محملة: ${missingLibraries.join(', ')}. يرجى إعادة تحميل الصفحة.`);
        return;
    }
   
    try {
        window.vehicleManagementPage = new VehicleManagementPage();
    } catch (error) {
        console.error('❌ Failed to initialize VehicleManagementPage:', error);
       
        // Show error message to user
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #dc3545;
            color: white;
            padding: 15px;
            text-align: center;
            z-index: 9999;
            font-family: Arial, sans-serif;
        `;
        errorDiv.innerHTML = `
            <strong>خطأ في تحميل الصفحة:</strong> ${error.message}
            <br>
            <small>يرجى تحديث الصفحة أو الاتصال بالدعم الفني.</small>
        `;
        document.body.appendChild(errorDiv);
    }
});