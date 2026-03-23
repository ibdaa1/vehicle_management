// vehicle_management/assets/js/vehicle_management.js
class VehicleManagementPage {
    constructor() {
        this.API_SESSION = '/vehicle_management/api/v1/auth/check';
        this.API_MANAGEMENTS = '/vehicle_management/api/v1/vehicles';
        this.API_REFERENCES = '/vehicle_management/api/v1/references';
        this.API_EMPLOYEES = '/vehicle_management/api/v1/users';
        this.ADD_MANAGEMENT_PAGE = '/vehicle_management/public/add_vehicle_movements.html';
        
        this.userLanguage = 'ar';
        this.translations = {};
        this.filters = {
            dateRange: null,
            managementType: '',
            vehicleCode: '',
            employeeId: '',
            department: '',
            section: '',
            division: '',
            status: ''
        };
        this.selectedEmployee = null;
        this.dataTable = null;
        
        this.init();
    }

    async init() {
        await this.initializeElements();
        this.setupEventListeners();
        await this.loadInitialData();
        this.setPageDirection();
        this.initializeDataTable();
        this.loadManagements();
    }

    async initializeElements() {
        // UI Elements
        this.addManagementBtn = document.getElementById('add-management-btn');
        this.refreshBtn = document.getElementById('refresh-btn');
        this.resetFiltersBtn = document.getElementById('reset-filters');
        this.applyFiltersBtn = document.getElementById('apply-filters');
        
        // Filter Elements
        this.dateRangeInput = document.getElementById('date-range');
        this.managementTypeSelect = document.getElementById('management-type');
        this.vehicleCodeInput = document.getElementById('vehicle-code');
        this.employeeSearchInput = document.getElementById('employee-search');
        this.departmentSelect = document.getElementById('department');
        this.sectionSelect = document.getElementById('section');
        this.divisionSelect = document.getElementById('division');
        this.statusSelect = document.getElementById('status');
        
        // Modal Elements
        this.employeeModal = document.getElementById('employee-search-modal');
        this.employeeModalSearchInput = document.getElementById('employee-modal-search');
        this.searchEmployeeModalBtn = document.getElementById('search-employee-modal-btn');
        this.employeeModalResults = document.getElementById('employee-modal-results');
        this.closeSearchBtn = document.querySelector('.close-search');
        
        // Statistics Elements
        this.totalManagementsElement = document.getElementById('total-managements');
        this.pickupCountElement = document.getElementById('pickup-count');
        this.returnCountElement = document.getElementById('return-count');
        this.pendingCountElement = document.getElementById('pending-count');
        
        // Loading Overlay
        this.loadingOverlay = document.getElementById('loading-overlay');
    }

    setupEventListeners() {
        // Navigation Buttons
        if (this.addManagementBtn) {
            this.addManagementBtn.addEventListener('click', () => this.openAddManagementForm());
        }
        if (this.refreshBtn) {
            this.refreshBtn.addEventListener('click', () => this.loadManagements());
        }
        
        // Filter Actions
        if (this.resetFiltersBtn) {
            this.resetFiltersBtn.addEventListener('click', () => this.resetFilters());
        }
        if (this.applyFiltersBtn) {
            this.applyFiltersBtn.addEventListener('click', () => this.applyFilters());
        }
        
        // Filter Inputs
        if (this.dateRangeInput) {
            this.dateRangeInput.addEventListener('change', () => this.updateDateRange());
        }
        if (this.managementTypeSelect) {
            this.managementTypeSelect.addEventListener('change', () => this.updateManagementType());
        }
        if (this.vehicleCodeInput) {
            this.vehicleCodeInput.addEventListener('input', () => this.updateVehicleCode());
        }
        if (this.employeeSearchInput) {
            this.employeeSearchInput.addEventListener('focus', () => this.openEmployeeModal());
        }
        if (this.departmentSelect) {
            this.departmentSelect.addEventListener('change', () => this.loadSections());
        }
        if (this.sectionSelect) {
            this.sectionSelect.addEventListener('change', () => this.loadDivisions());
        }
        if (this.statusSelect) {
            this.statusSelect.addEventListener('change', () => this.updateStatus());
        }
        
        // Employee Search Modal
        if (this.employeeModalSearchInput) {
            this.employeeModalSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.searchEmployeesModal();
            });
        }
        if (this.searchEmployeeModalBtn) {
            this.searchEmployeeModalBtn.addEventListener('click', () => this.searchEmployeesModal());
        }
        if (this.closeSearchBtn) {
            this.closeSearchBtn.addEventListener('click', () => this.closeEmployeeModal());
        }
        
        // Close modal on overlay click
        if (this.employeeModal) {
            this.employeeModal.addEventListener('click', (e) => {
                if (e.target === this.employeeModal) this.closeEmployeeModal();
            });
        }
    }

    async loadInitialData() {
        try {
            this.showLoading();
            
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
            
        } catch (error) {
            console.error('خطأ في التحميل:', error);
            this.showMessage('error', `فشل في التحميل: ${error.message}`);
        } finally {
            this.hideLoading();
        }
    }

    setPageDirection() {
        document.documentElement.dir = this.userLanguage === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.lang = this.userLanguage;
        
        // Apply direction to body
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
            this.applyTranslations();
        } catch (error) {
            console.error('خطأ في تحميل الترجمات:', error);
            // Fallback to Arabic
            if (this.userLanguage !== 'ar') {
                try {
                    const arabicResponse = await fetch('/vehicle_management/languages/ar_vehicle_management.json');
                    this.translations = await arabicResponse.json();
                    this.applyTranslations();
                } catch (e) {
                    console.error('فشل في تحميل الترجمات العربية:', e);
                }
            }
        }
    }

    applyTranslations() {
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
    }

    getTranslation(key) {
        if (!this.translations) return key;
        
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
    }

    async loadReferences() {
        try {
            // Load departments
            const deptResponse = await fetch(`${this.API_REFERENCES}?type=departments&lang=${this.userLanguage}`);
            if (!deptResponse.ok) {
                throw new Error(`HTTP ${deptResponse.status}`);
            }
            
            const deptData = await deptResponse.json();
            
            if (deptData.success && deptData.departments && this.departmentSelect) {
                this.populateSelect(this.departmentSelect, deptData.departments, 'all_departments');
            }
            
        } catch (error) {
            console.error('خطأ في تحميل المراجع:', error);
            if (this.departmentSelect) {
                this.departmentSelect.disabled = true;
                this.departmentSelect.innerHTML = `<option value="">${this.getTranslation('all_departments') || 'جميع الإدارات'}</option>`;
            }
        }
    }

    async loadSections() {
        const departmentId = this.departmentSelect?.value;
        
        if (!departmentId) {
            if (this.sectionSelect) {
                this.sectionSelect.disabled = true;
                this.sectionSelect.innerHTML = `<option value="">${this.getTranslation('all_sections') || 'جميع الأقسام'}</option>`;
            }
            if (this.divisionSelect) {
                this.divisionSelect.disabled = true;
                this.divisionSelect.innerHTML = `<option value="">${this.getTranslation('all_divisions') || 'جميع الشعب'}</option>`;
            }
            return;
        }
        
        try {
            const response = await fetch(`${this.API_REFERENCES}?type=sections&parent_id=${departmentId}&lang=${this.userLanguage}`);
            const data = await response.json();
            
            if (data.success && data.sections && this.sectionSelect) {
                this.sectionSelect.disabled = false;
                this.populateSelect(this.sectionSelect, data.sections, 'all_sections');
                
                if (this.divisionSelect) {
                    this.divisionSelect.disabled = true;
                    this.divisionSelect.innerHTML = `<option value="">${this.getTranslation('all_divisions') || 'جميع الشعب'}</option>`;
                }
            }
        } catch (error) {
            console.error('خطأ في تحميل الأقسام:', error);
        }
    }

    async loadDivisions() {
        const sectionId = this.sectionSelect?.value;
        
        if (!sectionId) {
            if (this.divisionSelect) {
                this.divisionSelect.disabled = true;
                this.divisionSelect.innerHTML = `<option value="">${this.getTranslation('all_divisions') || 'جميع الشعب'}</option>`;
            }
            return;
        }
        
        try {
            const response = await fetch(`${this.API_REFERENCES}?type=divisions&parent_id=${sectionId}&lang=${this.userLanguage}`);
            const data = await response.json();
            
            if (data.success && data.divisions && this.divisionSelect) {
                this.divisionSelect.disabled = false;
                this.populateSelect(this.divisionSelect, data.divisions, 'all_divisions');
            }
        } catch (error) {
            console.error('خطأ في تحميل الشعب:', error);
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
        if (!this.dateRangeInput || !$.fn.daterangepicker) return;
        
        const today = new Date();
        const lastWeek = new Date();
        lastWeek.setDate(today.getDate() - 7);
        
        // إعدادات التقويم الميلادي
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
            firstDay: 0 // الأحد كأول يوم في الأسبوع (ميلادي)
        };
        
        $(this.dateRangeInput).daterangepicker({
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
            this.dateRangeInput.value = formatted;
            this.filters.dateRange = { 
                start: start.format('YYYY-MM-DD'), 
                end: end.format('YYYY-MM-DD') 
            };
        });
        
        // تعيين القيمة الأولية للفلاتر
        this.filters.dateRange = {
            start: lastWeek.toISOString().split('T')[0],
            end: today.toISOString().split('T')[0]
        };
    }

    initializeDataTable() {
        if (!$.fn.DataTable) {
            console.error('DataTables library not loaded');
            return;
        }
        
        const table = document.getElementById('managements-table');
        if (!table) {
            console.error('Table element not found');
            return;
        }
        
        this.dataTable = $(table).DataTable({
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
                { targets: 9, width: '120px', orderable: false }
            ],
            initComplete: () => {
                // Custom search input for DataTables
                $('.dataTables_filter input').attr('placeholder', this.getTranslation('search_table') || 'ابحث...');
            }
        });
    }

    async loadManagements() {
        try {
            this.showLoading();
            
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
            
            params.append('lang', this.userLanguage);
            
            // Add timestamp to prevent caching
            params.append('_t', Date.now());
            
            // Log URL for debugging
            console.log('Fetching URL:', `${this.API_MANAGEMENTS}?${params.toString()}`);
            
            const response = await fetch(`${this.API_MANAGEMENTS}?${params.toString()}`);
            
            if (!response.ok) {
                throw new Error(`خطأ في الاتصال: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.updateStatistics(data.statistics);
                this.populateTable(data.managements);
                
                // Show success message
                const message = this.userLanguage === 'ar' 
                    ? `تم تحميل ${data.total_count || 0} حركة`
                    : `Loaded ${data.total_count || 0} movements`;
                this.showMessage('success', message);
            } else {
                throw new Error(data.message || 'فشل في جلب البيانات');
            }
            
        } catch (error) {
            console.error('خطأ في تحميل الحركات:', error);
            
            let errorMessage = this.getTranslation('errors.load_managements_failed') || 'فشل في جلب البيانات';
            if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                errorMessage = 'فشل في الاتصال بالخادم. يرجى التحقق من اتصال الشبكة.';
            }
            
            this.showMessage('error', `${errorMessage}: ${error.message}`);
            
            // Clear table if there's an error
            if (this.dataTable) {
                this.dataTable.clear().draw();
            }
        } finally {
            this.hideLoading();
        }
    }

    updateStatistics(statistics) {
        if (!statistics) return;
        
        if (this.totalManagementsElement) {
            this.totalManagementsElement.textContent = statistics.total_managements || 0;
        }
        if (this.pickupCountElement) {
            this.pickupCountElement.textContent = statistics.pickup_count || 0;
        }
        if (this.returnCountElement) {
            this.returnCountElement.textContent = statistics.return_count || 0;
        }
        if (this.pendingCountElement) {
            this.pendingCountElement.textContent = statistics.pending_vehicles || 0;
        }
    }

    populateTable(managements) {
        if (!this.dataTable) {
            console.error('DataTable not initialized');
            return;
        }
        
        this.dataTable.clear();
        
        if (!managements || managements.length === 0) {
            this.dataTable.draw();
            return;
        }
        
        managements.forEach(management => {
            const row = this.createTableRow(management);
            this.dataTable.row.add(row);
        });
        
        this.dataTable.draw();
    }

    createTableRow(management) {
        const statusClass = management.vehicle_status === 'operational' ? 'operational' : 'out_of_service';
        const statusText = management.vehicle_status === 'operational' ? 
            this.getTranslation('operational') || 'تشغيلية' : 
            this.getTranslation('out_of_service') || 'خارج الخدمة';
        
        const operationTypeClass = management.operation_type;
        const operationTypeText = management.operation_type === 'pickup' ? 
            this.getTranslation('pickup') || 'استلام' : 
            this.getTranslation('return') || 'إرجاع';
        
        const viewBtnText = this.getTranslation('view_details') || 'عرض التفاصيل';
        const followBtnText = this.getTranslation('follow_up') || 'متابعة';
        
        // تنسيق التاريخ الميلادي
        let formattedDate = this.getTranslation('not_specified') || 'غير محدد';
        if (management.movement_datetime) {
            try {
                const dateMoment = moment(management.movement_datetime);
                if (dateMoment.isValid()) {
                    // عرض التاريخ الميلادي
                    formattedDate = dateMoment.format('YYYY-MM-DD HH:mm');
                }
            } catch (e) {
                console.error('Error formatting date:', e);
            }
        }
        
        // Get employee name
        const employeeName = management.employee_display_name || 
                            management.employee_name || 
                            management.performed_by || 
                            this.getTranslation('not_specified') || 'غير محدد';
        
        // Get department/section/division names
        const departmentName = management.department_display_name || 
                             management.department_name || 
                             this.getTranslation('not_specified') || 'غير محدد';
        
        const sectionName = management.section_display_name || 
                          management.section_name || 
                          this.getTranslation('not_specified') || 'غير محدد';
        
        const divisionName = management.division_display_name || 
                           management.division_name || 
                           this.getTranslation('not_specified') || 'غير محدد';
        
        return [
            management.id || '',
            management.vehicle_code || '',
            `<span class="status-badge ${operationTypeClass}">${operationTypeText}</span>`,
            employeeName,
            departmentName,
            sectionName,
            divisionName,
            formattedDate,
            `<span class="status-badge ${statusClass}">${statusText}</span>`,
            `
                <div class="actions-cell">
                    <button class="btn btn-action btn-primary" onclick="window.vehicleManagementPage.viewManagement(${management.id})" 
                            title="${viewBtnText}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-action btn-secondary" onclick="window.vehicleManagementPage.followUpManagement('${management.vehicle_code}')" 
                            title="${followBtnText}">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                </div>
            `
        ];
    }

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

    openEmployeeModal() {
        if (!this.employeeModal) return;
        this.employeeModal.style.display = 'flex';
        if (this.employeeModalSearchInput) {
            this.employeeModalSearchInput.focus();
        }
    }

    closeEmployeeModal() {
        if (!this.employeeModal) return;
        this.employeeModal.style.display = 'none';
        if (this.employeeModalSearchInput) {
            this.employeeModalSearchInput.value = '';
        }
        if (this.employeeModalResults) {
            this.employeeModalResults.innerHTML = '';
        }
    }

    async searchEmployeesModal() {
        const query = this.employeeModalSearchInput ? this.employeeModalSearchInput.value.trim() : '';
        
        if (!query) {
            this.showMessage('warning', this.getTranslation('errors.search_query_empty') || 'يرجى إدخال نص للبحث');
            return;
        }
        
        try {
            const response = await fetch(`${this.API_EMPLOYEES}?q=${encodeURIComponent(query)}&lang=${this.userLanguage}`);
            const data = await response.json();
            
            this.displayEmployeeResults(data.employees || []);
            
        } catch (error) {
            console.error('خطأ في البحث:', error);
            this.showMessage('error', this.getTranslation('errors.search_employees_failed') || 'فشل في البحث عن الموظفين');
        }
    }

    displayEmployeeResults(employees) {
        if (!this.employeeModalResults) return;
        
        this.employeeModalResults.innerHTML = '';
        
        if (!employees || employees.length === 0) {
            this.employeeModalResults.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-search mb-2"></i>
                    <p>${this.getTranslation('no_employees_found') || 'لم يتم العثور على موظفين'}</p>
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
                        <strong>${employee.full_name || this.getTranslation('not_specified') || 'غير محدد'}</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            ${employee.emp_id || this.getTranslation('no_id') || 'بدون رقم'} • 
                            ${employee.department || this.getTranslation('not_specified') || 'غير محدد'}
                        </div>
                    </div>
                    <i class="fas fa-check text-success" style="display: none;"></i>
                </div>
            `;
            
            employeeItem.addEventListener('click', (e) => {
                this.selectEmployee(employee, e);
            });
            
            this.employeeModalResults.appendChild(employeeItem);
        });
    }

    selectEmployee(employee, event) {
        this.selectedEmployee = employee;
        
        if (this.employeeSearchInput) {
            this.employeeSearchInput.value = employee.full_name || employee.emp_id || '';
        }
        
        this.filters.employeeId = employee.emp_id || '';
        
        // Show selected state
        if (this.employeeModalResults) {
            const employeeItems = this.employeeModalResults.querySelectorAll('.employee-item');
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

    updateDateRange() {
        if (!this.dateRangeInput || !this.dateRangeInput.value) {
            this.filters.dateRange = null;
            return;
        }
        
        const dates = this.dateRangeInput.value.split(' - ');
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
        if (!this.managementTypeSelect) {
            this.filters.managementType = '';
            return;
        }
        this.filters.managementType = this.managementTypeSelect.value;
    }

    updateVehicleCode() {
        if (!this.vehicleCodeInput) {
            this.filters.vehicleCode = '';
            return;
        }
        this.filters.vehicleCode = this.vehicleCodeInput.value.trim();
    }

    updateStatus() {
        if (!this.statusSelect) {
            this.filters.status = '';
            return;
        }
        this.filters.status = this.statusSelect.value;
    }

    resetFilters() {
        try {
            // Reset date range picker
            if (this.dateRangeInput && $.fn.daterangepicker) {
                const today = new Date();
                const lastWeek = new Date();
                lastWeek.setDate(today.getDate() - 7);
                
                // استخدام moment للتاريخ الميلادي
                $(this.dateRangeInput).data('daterangepicker').setStartDate(moment(lastWeek));
                $(this.dateRangeInput).data('daterangepicker').setEndDate(moment(today));
                this.dateRangeInput.value = lastWeek.toISOString().split('T')[0] + ' - ' + today.toISOString().split('T')[0];
            }
            
            // Reset select inputs
            if (this.managementTypeSelect) this.managementTypeSelect.value = '';
            if (this.vehicleCodeInput) this.vehicleCodeInput.value = '';
            if (this.employeeSearchInput) this.employeeSearchInput.value = '';
            if (this.departmentSelect) this.departmentSelect.value = '';
            if (this.sectionSelect) {
                this.sectionSelect.value = '';
                this.sectionSelect.disabled = true;
            }
            if (this.divisionSelect) {
                this.divisionSelect.value = '';
                this.divisionSelect.disabled = true;
            }
            if (this.statusSelect) this.statusSelect.value = '';
            
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
                status: ''
            };
            
            this.selectedEmployee = null;
            
            // Reload managements with default filters
            this.loadManagements();
            
            this.showMessage('info', this.getTranslation('filters_reset') || 'تم إعادة تعيين الفلاتر');
            
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
            
            // Update dependent filters
            if (this.departmentSelect) {
                this.filters.department = this.departmentSelect.value;
            }
            if (this.sectionSelect) {
                this.filters.section = this.departmentSelect && this.departmentSelect.value ? this.sectionSelect.value : '';
            }
            if (this.divisionSelect) {
                this.filters.division = this.sectionSelect && this.sectionSelect.value ? this.divisionSelect.value : '';
            }
            
            // Apply filters
            this.loadManagements();
            
        } catch (error) {
            console.error('خطأ في تطبيق الفلاتر:', error);
            this.showMessage('error', 'حدث خطأ في تطبيق الفلاتر');
        }
    }

    showLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'flex';
        }
    }

    hideLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'none';
        }
    }

    showMessage(type, text) {
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
        
        // Auto-remove after 5 seconds
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
        }, 5000);
        
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
    // Check if all required libraries are loaded
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded. Please include jQuery before this script.');
        return;
    }
    
    if (typeof $.fn.DataTable === 'undefined') {
        console.error('DataTables is not loaded. Please include DataTables before this script.');
        return;
    }
    
    if (typeof moment === 'undefined') {
        console.error('Moment.js is not loaded. Please include Moment.js before this script.');
        return;
    }
    
    if (typeof $.fn.daterangepicker === 'undefined') {
        console.warn('DateRangePicker is not loaded. Date range filtering will not work properly.');
    }
    
    try {
        window.vehicleManagementPage = new VehicleManagementPage();
    } catch (error) {
        console.error('Failed to initialize VehicleManagementPage:', error);
        
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