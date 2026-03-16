/**
 * نظام إدارة مخالفات المركبات - النسخة المحسنة
 * يدعم الترجمة التلقائية والتجاوب الكامل مع جميع الشاشات
 */
class VehicleViolationsManager {
    constructor() {
        this.currentViolationId = null;
        this.isEditMode = false;
        this.userRole = null;
        this.isAdmin = false;
        this.currentLanguage = 'ar';
        this.translations = {};
        this.userEmpId = null;
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.filteredData = [];
        this.totalItems = 0;
        this.userData = null;
        this.isMobile = false;
        this.isTablet = false;
        this.debounceTimers = {};
        this.vehicleSearchData = [];
        this.selectedVehicle = null;
        this.isLoading = false;
        
        // تحديد نوع الجهاز
        this.detectDevice();
        
        this.init();
    }
    
    detectDevice() {
        const width = window.innerWidth;
        this.isMobile = width < 768;
        this.isTablet = width >= 768 && width < 992;
    }
    
    async init() {
        try {
            // إخفاء التحميل الأولي
            this.hidePreloader();
            
            // تحميل بيانات المستخدم أولاً
            await this.loadUserData();
            
            // تحديد لغة المستخدم
            await this.setUserLanguage();
            
            // تحميل الترجمة
            await this.loadTranslations();
            
            // إعداد واجهة المستخدم
            this.setupUI();
            
            // إعداد مستمعي الأحداث
            this.setupEventListeners();
            
            // تحميل البيانات الأولية
            await this.loadInitialData();
            
            // تحميل المخالفات
            await this.loadViolations();
            
            // تحديث الواجهة بناءً على نوع الجهاز
            this.updateUIForDevice();
            
            // إظهار رسالة الترحيب
            this.showWelcomeMessage();
            
        } catch (error) {
            console.error('خطأ في تهيئة النظام:', error);
            this.showAlert('error', this.translate('systemError'));
        }
    }
    
    hidePreloader() {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            setTimeout(() => {
                preloader.style.opacity = '0';
                preloader.style.visibility = 'hidden';
                setTimeout(() => preloader.remove(), 300);
            }, 500);
        }
    }
    
    async loadUserData() {
        try {
            const response = await fetch('../api/v1/auth/check', {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.user) {
                this.userData = data.user;
                this.userRole = data.user.role_id;
                this.isAdmin = [1, 2].includes(this.userRole);
                this.userEmpId = data.user.emp_id;
                
                // تحديث معلومات المستخدم في الواجهة
                this.updateUserInfo();
                
                return data;
            } else {
                // إذا لم يكن المستخدم مسجلاً، توجيهه للصفحة الرئيسية
                setTimeout(() => {
                    window.location.href = '../public/index.html';
                }, 1500);
                throw new Error('يجب تسجيل الدخول أولاً');
            }
        } catch (error) {
            console.error('Error loading user data:', error);
            this.showAlert('error', this.translate('userDataError'));
            throw error;
        }
    }
    
    updateUserInfo() {
        if (this.userData) {
            const userNameElement = document.getElementById('userName');
            const userRoleElement = document.getElementById('userRole');
            
            if (userNameElement) {
                userNameElement.textContent = this.userData.username || 'مستخدم';
            }
            
            if (userRoleElement) {
                userRoleElement.textContent = this.isAdmin ? 
                    (this.currentLanguage === 'ar' ? 'مدير النظام' : 'System Administrator') : 
                    (this.currentLanguage === 'ar' ? 'موظف' : 'Employee');
            }
        }
    }
    
    async setUserLanguage() {
        try {
            // الأولوية: لغة المستخدم من قاعدة البيانات
            if (this.userData && this.userData.preferred_language) {
                this.currentLanguage = this.userData.preferred_language;
            } 
            // الثاني: اللغة المحفوظة في المتصفح
            else if (localStorage.getItem('preferred_language')) {
                this.currentLanguage = localStorage.getItem('preferred_language');
            }
            // الثالث: لغة المتصفح
            else {
                const browserLang = navigator.language || navigator.userLanguage;
                this.currentLanguage = browserLang.startsWith('ar') ? 'ar' : 'en';
            }
            
            // حفظ اللغة في localStorage
            localStorage.setItem('preferred_language', this.currentLanguage);
            
            // تحديث سمة HTML
            document.documentElement.lang = this.currentLanguage;
            document.documentElement.dir = this.currentLanguage === 'ar' ? 'rtl' : 'ltr';
            
        } catch (error) {
            console.error('Error setting user language:', error);
            this.currentLanguage = 'ar';
        }
    }
    
    async loadTranslations() {
        try {
            // تحديث مؤشر التحميل
            this.showLoading('translations');
            
            const response = await fetch(`../languages/${this.currentLanguage}_add_vehicle_violations.json?v=${Date.now()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            this.translations = await response.json();
            console.log('Translations loaded successfully for:', this.currentLanguage);
            
            // تطبيق الترجمات
            this.applyTranslations();
            
        } catch (error) {
            console.error('Error loading translations:', error);
            
            // محاولة تحميل اللغة الافتراضية
            try {
                console.log('Trying to load fallback Arabic translation...');
                const fallbackResponse = await fetch('../languages/ar_add_vehicle_violations.json');
                if (fallbackResponse.ok) {
                    this.translations = await fallbackResponse.json();
                    this.applyTranslations();
                    console.log('Fallback translations loaded successfully');
                }
            } catch (fallbackError) {
                console.error('Error loading fallback translations:', fallbackError);
                this.translations = {};
            }
        } finally {
            this.hideLoading('translations');
        }
    }
    
    applyTranslations() {
        if (!this.translations || Object.keys(this.translations).length === 0) {
            console.warn('No translations available');
            return;
        }
        
        // ترجمة جميع العناصر التي تحتوي على data-i18n
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            const translation = this.translations[key];
            
            if (translation) {
                if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                    const placeholderKey = element.getAttribute('data-i18n-placeholder');
                    if (placeholderKey) {
                        const placeholderTranslation = this.translations[placeholderKey] || translation;
                        element.placeholder = placeholderTranslation;
                    }
                } else if (element.tagName === 'OPTION') {
                    // تحديث نص الخيارات في الـ select
                    element.textContent = translation;
                } else if (element.hasAttribute('title')) {
                    element.title = translation;
                } else {
                    element.textContent = translation;
                }
            }
        });
        
        // تحديث النصوص الديناميكية
        this.updateDynamicTexts();
        
        // تحديث أي نصوص أخرى تحتاج ترجمة
        this.updateAdditionalTexts();
    }
    
    updateDynamicTexts() {
        // تحديث عنوان النموذج
        const formTitle = document.getElementById('formTitle');
        if (formTitle) {
            formTitle.textContent = this.isEditMode 
                ? this.translate('editViolation') 
                : this.translate('addViolation');
        }
        
        // تحديث نص زر الحفظ
        const saveBtn = document.getElementById('saveBtn');
        if (saveBtn) {
            const icon = this.isEditMode ? 'fa-save' : 'fa-plus';
            const text = this.isEditMode ? this.translate('save') : this.translate('addViolation');
            saveBtn.innerHTML = `<i class="fas ${icon}"></i> ${text}`;
        }
        
        // تحديث تسميات الإحصائيات
        this.updateStatisticsLabels();
    }
    
    updateAdditionalTexts() {
        // تحديث تسميات الصفحات
        const pageSizeSelect = document.getElementById('pageSize');
        if (pageSizeSelect) {
            const pageSizeText = this.translate('rowsPerPage') || 'صفوف لكل صفحة';
            pageSizeSelect.querySelectorAll('option').forEach((option, index) => {
                if (index === 0) return; // تخطي الخيار الأول
                const value = option.value;
                option.textContent = `${value} ${pageSizeText}`;
            });
        }
    }
    
    updateStatisticsLabels() {
        // تحديث تسميات بطاقات الإحصائيات
        document.querySelectorAll('.stat-label').forEach(label => {
            const key = label.getAttribute('data-i18n');
            if (key) {
                const translation = this.translations[key];
                if (translation) {
                    label.textContent = translation;
                }
            }
        });
    }
    
    translate(key) {
        return this.translations[key] || key;
    }
    
    setupUI() {
        // تحديث أزرار اللغة
        this.updateLanguageButtons();
        
        // إعداد القوائم القابلة للطي للهواتف
        this.setupCollapsibleSections();
        
        // إضافة تأثيرات للواجهة
        this.addUIEffects();
    }
    
    updateLanguageButtons() {
        const btnAr = document.getElementById('langAr');
        const btnEn = document.getElementById('langEn');
        
        if (btnAr && btnEn) {
            // إزالة الكلاس النشط من جميع الأزرار
            btnAr.classList.remove('active');
            btnEn.classList.remove('active');
            
            // إضافة الكلاس النشط للزر المناسب
            if (this.currentLanguage === 'ar') {
                btnAr.classList.add('active');
                btnAr.textContent = 'العربية';
                btnEn.textContent = 'English';
            } else {
                btnEn.classList.add('active');
                btnAr.textContent = 'العربية';
                btnEn.textContent = 'English';
            }
        }
    }
    
    setupCollapsibleSections() {
        // إعداد القوائم القابلة للطي للهواتف
        if (this.isMobile) {
            const filterToggle = document.getElementById('filterToggle');
            const formToggle = document.getElementById('formToggle');
            const filterContent = document.getElementById('filterContent');
            const formContent = document.getElementById('formContent');
            
            if (filterToggle && filterContent) {
                filterToggle.addEventListener('click', () => {
                    filterContent.classList.toggle('collapsed');
                    filterToggle.classList.toggle('rotated');
                });
            }
            
            if (formToggle && formContent) {
                formToggle.addEventListener('click', () => {
                    formContent.classList.toggle('collapsed');
                    formToggle.classList.toggle('rotated');
                });
            }
        }
    }
    
    addUIEffects() {
        // إضافة تأثيرات hover للأجهزة التي تدعمها
        if (!this.isMobile) {
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px)';
                    card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                    card.style.boxShadow = '';
                });
            });
        }
    }
    
    setupEventListeners() {
        // الأحداث الأساسية
        this.setupBasicEvents();
        
        // أحداث الفلترة
        this.setupFilterEvents();
        
        // أحداث النموذج
        this.setupFormEvents();
        
        // أحداث الجدول
        this.setupTableEvents();
        
        // أحداث الهواتف
        this.setupMobileEvents();
        
        // أحداث البحث عن المركبات
        this.setupVehicleSearchEvents();
    }
    
    setupBasicEvents() {
        // تبديل اللغة
        document.getElementById('langAr')?.addEventListener('click', () => this.changeLanguage('ar'));
        document.getElementById('langEn')?.addEventListener('click', () => this.changeLanguage('en'));
        
        // الطباعة والتصدير
        document.getElementById('printBtn')?.addEventListener('click', () => this.printReport());
        document.getElementById('exportBtn')?.addEventListener('click', () => this.exportToExcel());
        
        // تغيير عدد الصفوف
        document.getElementById('pageSize')?.addEventListener('change', (e) => {
            this.itemsPerPage = parseInt(e.target.value);
            this.currentPage = 1;
            this.renderViolationsTable();
            this.renderPagination();
        });
    }
    
    setupFilterEvents() {
        const searchBtn = document.getElementById('searchBtn');
        const resetFilterBtn = document.getElementById('resetFilterBtn');
        const departmentFilter = document.getElementById('departmentFilter');
        const sectionFilter = document.getElementById('sectionFilter');
        
        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.loadViolations());
        }
        
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', () => this.resetFilters());
        }
        
        if (departmentFilter) {
            departmentFilter.addEventListener('change', (e) => {
                this.loadSections(e.target.value);
                this.debouncedLoadViolations();
            });
        }
        
        if (sectionFilter) {
            sectionFilter.addEventListener('change', (e) => {
                this.loadDivisions(e.target.value);
                this.debouncedLoadViolations();
            });
        }
        
        // الفلترة الفورية
        ['divisionFilter', 'statusFilter', 'startDate', 'endDate'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => this.debouncedLoadViolations());
            }
        });
    }
    
    setupVehicleSearchEvents() {
        const vehicleCodeFilter = document.getElementById('vehicleCodeFilter');
        
        if (vehicleCodeFilter) {
            // البحث أثناء الكتابة
            vehicleCodeFilter.addEventListener('input', (e) => {
                const searchTerm = e.target.value.trim();
                if (searchTerm.length >= 2) {
                    this.debouncedSearchVehicles(searchTerm);
                } else {
                    this.hideVehicleSearchDropdown();
                    this.selectedVehicle = null;
                }
            });
            
            // عند التركيز على الحقل
            vehicleCodeFilter.addEventListener('focus', (e) => {
                const searchTerm = e.target.value.trim();
                if (searchTerm.length >= 2) {
                    this.searchVehicles(searchTerm);
                }
            });
            
            // عند الضغط على مفتاح Enter
            vehicleCodeFilter.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.loadViolations();
                    this.hideVehicleSearchDropdown();
                }
            });
            
            // إخفاء القائمة المنسدلة عند فقدان التركيز
            vehicleCodeFilter.addEventListener('blur', () => {
                setTimeout(() => {
                    this.hideVehicleSearchDropdown();
                }, 200);
            });
        }
    }
    
    setupFormEvents() {
        const violationForm = document.getElementById('violationForm');
        const vehicleSelect = document.getElementById('vehicleSelect');
        const paymentAttachment = document.getElementById('paymentAttachment');
        const violationStatus = document.getElementById('violationStatus');
        const resetBtn = document.getElementById('resetBtn');
        
        if (violationForm) {
            violationForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveViolation();
            });
        }
        
        if (vehicleSelect) {
            vehicleSelect.addEventListener('change', (e) => {
                this.updateVehicleDetails(e.target.value);
            });
        }
        
        if (paymentAttachment) {
            paymentAttachment.addEventListener('change', (e) => {
                this.previewFile(e.target.files[0]);
            });
        }
        
        if (violationStatus) {
            violationStatus.addEventListener('change', (e) => {
                this.toggleAttachmentField(e.target.value);
            });
        }
        
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.resetForm();
            });
        }
    }
    
    setupTableEvents() {
        // إضافة تأثيرات للجدول
        const tableBody = document.getElementById('violationsTableBody');
        if (tableBody && !this.isMobile) {
            tableBody.addEventListener('mouseover', (e) => {
                const row = e.target.closest('tr');
                if (row) {
                    row.style.backgroundColor = 'rgba(181, 158, 90, 0.05)';
                }
            });
            
            tableBody.addEventListener('mouseout', (e) => {
                const row = e.target.closest('tr');
                if (row) {
                    row.style.backgroundColor = '';
                }
            });
        }
    }
    
    setupMobileEvents() {
        // القائمة الجانبية للهواتف
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileCloseBtn = document.getElementById('mobileCloseBtn');
        const userActions = document.getElementById('userActions');
        const sidebarClose = document.getElementById('sidebarClose');
        const mobileSidebar = document.getElementById('mobileSidebar');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                userActions?.classList.add('active');
            });
        }
        
        if (mobileCloseBtn) {
            mobileCloseBtn.addEventListener('click', () => {
                userActions?.classList.remove('active');
            });
        }
        
        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => {
                mobileSidebar?.classList.remove('active');
            });
        }
        
        // فتح القائمة الجانبية بالتمرير
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        document.addEventListener('touchend', (e) => {
            if (this.isMobile) {
                touchEndX = e.changedTouches[0].screenX;
                const swipeDistance = touchEndX - touchStartX;
                
                // سحب من الحافة اليمنى لليسار لفتح القائمة
                if (swipeDistance < -50 && touchStartX > window.innerWidth - 50) {
                    mobileSidebar?.classList.add('active');
                }
                
                // سحب من اليسار لليمين لإغلاق القائمة
                if (swipeDistance > 50 && touchStartX < 50) {
                    mobileSidebar?.classList.remove('active');
                    userActions?.classList.remove('active');
                }
            }
        }, { passive: true });
    }
    
    updateUIForDevice() {
        // إخفاء العناصر غير الضرورية على الهواتف
        if (this.isMobile) {
            document.querySelectorAll('.control-text').forEach(el => {
                el.style.display = 'none';
            });
        }
        
        // تحديث حجم الخط للهواتف
        if (this.isMobile || this.isTablet) {
            document.documentElement.style.setProperty('--font-sm', '14px');
            document.documentElement.style.setProperty('--font-md', '16px');
        }
    }
    
    debouncedLoadViolations = this.debounce(() => {
        this.loadViolations();
    }, 300);
    
    debouncedSearchVehicles = this.debounce((searchTerm) => {
        this.searchVehicles(searchTerm);
    }, 300);
    
    debounce(func, wait) {
        return (...args) => {
            clearTimeout(this.debounceTimers[func.name]);
            this.debounceTimers[func.name] = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    async loadInitialData() {
        try {
            await Promise.all([
                this.loadVehicles(),
                this.loadDepartments(),
                this.loadCurrentTime()
            ]);
            
            // تحميل الإحصائيات بعد البيانات الأولية
            await this.loadStatistics();
            
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.showAlert('error', this.translate('initialDataError'));
        }
    }
    
    async loadVehicles() {
        try {
            const response = await fetch('../api/v1/violations?action=vehicles');
            const data = await response.json();
            
            if (data.success) {
                const select = document.getElementById('vehicleSelect');
                if (select) {
                    const currentValue = select.value;
                    select.innerHTML = `<option value="">${this.translate('selectVehicle')}</option>`;
                    
                    data.data.forEach(vehicle => {
                        const option = document.createElement('option');
                        option.value = vehicle.id;
                        option.textContent = `${vehicle.vehicle_code} - ${vehicle.driver_name || (this.currentLanguage === 'ar' ? 'بدون سائق' : 'No Driver')}`;
                        option.setAttribute('data-code', vehicle.vehicle_code);
                        select.appendChild(option);
                    });
                    
                    if (currentValue) {
                        select.value = currentValue;
                        this.updateVehicleDetails(currentValue);
                    }
                }
            }
        } catch (error) {
            console.error('Error loading vehicles:', error);
        }
    }
    
    async loadAllVehiclesForSearch() {
        try {
            const response = await fetch('../api/v1/violations?action=all_vehicles');
            const data = await response.json();
            
            if (data.success && data.data) {
                this.vehicleSearchData = data.data.map(vehicle => ({
                    id: vehicle.id,
                    vehicle_code: vehicle.vehicle_code,
                    driver_name: vehicle.driver_name || (this.currentLanguage === 'ar' ? 'بدون سائق' : 'No Driver'),
                    department_name: vehicle.department_name || '',
                    section_name: vehicle.section_name || ''
                }));
            }
        } catch (error) {
            console.error('Error loading vehicles for search:', error);
        }
    }
    
    async searchVehicles(searchTerm) {
        try {
            // تحميل جميع المركبات إذا لم تكن محملة مسبقاً
            if (this.vehicleSearchData.length === 0) {
                await this.loadAllVehiclesForSearch();
            }
            
            // تصفية النتائج
            const results = this.vehicleSearchData.filter(vehicle => {
                const searchLower = searchTerm.toLowerCase();
                return vehicle.vehicle_code.toLowerCase().includes(searchLower) ||
                       vehicle.driver_name.toLowerCase().includes(searchLower);
            });
            
            this.displayVehicleSearchResults(results, searchTerm);
            
        } catch (error) {
            console.error('Error searching vehicles:', error);
        }
    }
    
    displayVehicleSearchResults(results, searchTerm) {
        const dropdown = document.getElementById('vehicleSearchDropdown');
        if (!dropdown) return;
        
        if (results.length === 0) {
            dropdown.innerHTML = `
                <div class="dropdown-item no-results">
                    <i class="fas fa-search"></i>
                    <span>${this.translate('noVehiclesFound')}</span>
                </div>
            `;
            dropdown.style.display = 'block';
            return;
        }
        
        let html = '';
        
        // إذا كان البحث مطابقاً لرقم معين (مثل 46031)
        if (/^\d+$/.test(searchTerm)) {
            // تجميع المركبات المتشابهة
            const groupedResults = {};
            results.forEach(vehicle => {
                const baseCode = vehicle.vehicle_code.split('/')[0];
                if (!groupedResults[baseCode]) {
                    groupedResults[baseCode] = [];
                }
                groupedResults[baseCode].push(vehicle);
            });
            
            Object.entries(groupedResults).forEach(([baseCode, vehicles]) => {
                html += `
                    <div class="dropdown-group">
                        <div class="group-header">${baseCode}</div>
                `;
                
                vehicles.forEach(vehicle => {
                    html += this.createVehicleSearchItem(vehicle);
                });
                
                html += `</div>`;
            });
        } else {
            // عرض جميع النتائج
            results.slice(0, 10).forEach(vehicle => {
                html += this.createVehicleSearchItem(vehicle);
            });
            
            if (results.length > 10) {
                html += `
                    <div class="dropdown-item more-results">
                        <span>+ ${results.length - 10} ${this.translate('moreResults')}</span>
                    </div>
                `;
            }
        }
        
        dropdown.innerHTML = html;
        dropdown.style.display = 'block';
        
        // إضافة مستمعي الأحداث للعناصر
        dropdown.querySelectorAll('.vehicle-search-item').forEach(item => {
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const vehicleCode = item.dataset.vehicleCode;
                const vehicleText = item.querySelector('.vehicle-code').textContent;
                
                document.getElementById('vehicleCodeFilter').value = vehicleCode;
                this.selectedVehicle = {
                    code: vehicleCode,
                    text: vehicleText
                };
                
                this.hideVehicleSearchDropdown();
                
                // تطبيق الفلتر تلقائياً
                this.debouncedLoadViolations();
            });
        });
    }
    
    createVehicleSearchItem(vehicle) {
        return `
            <div class="dropdown-item vehicle-search-item"
                 data-vehicle-code="${vehicle.vehicle_code}">
                <div class="vehicle-code">${vehicle.vehicle_code}</div>
                <div class="vehicle-info">
                    <span class="driver-name">${vehicle.driver_name}</span>
                    <span class="vehicle-location">${vehicle.department_name || ''} ${vehicle.section_name ? '/ ' + vehicle.section_name : ''}</span>
                </div>
            </div>
        `;
    }
    
    hideVehicleSearchDropdown() {
        const dropdown = document.getElementById('vehicleSearchDropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    }
    
    async loadDepartments() {
        try {
            const response = await fetch(`../api/v1/references?type=departments&lang=${this.currentLanguage}`);
            const data = await response.json();
            
            if (data.success && data.departments) {
                const filterSelect = document.getElementById('departmentFilter');
                if (filterSelect) {
                    filterSelect.innerHTML = `<option value="">${this.translate('allDepartments')}</option>`;
                    
                    data.departments.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        filterSelect.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading departments:', error);
        }
    }
    
    async loadSections(departmentId) {
        try {
            const response = await fetch(`../api/v1/references?type=sections&lang=${this.currentLanguage}&parent_id=${departmentId}`);
            const data = await response.json();
            
            const select = document.getElementById('sectionFilter');
            if (select) {
                select.innerHTML = `<option value="">${this.translate('allSections')}</option>`;
                
                if (data.success && data.sections) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        select.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading sections:', error);
        }
    }
    
    async loadDivisions(sectionId) {
        try {
            const response = await fetch(`../api/v1/references?type=divisions&lang=${this.currentLanguage}&parent_id=${sectionId}`);
            const data = await response.json();
            
            const select = document.getElementById('divisionFilter');
            if (select) {
                select.innerHTML = `<option value="">${this.translate('allDivisions')}</option>`;
                
                if (data.success && data.divisions) {
                    data.divisions.forEach(division => {
                        const option = document.createElement('option');
                        option.value = division.id;
                        option.textContent = division.name;
                        select.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading divisions:', error);
        }
    }
    
    async loadStatistics() {
        try {
            const filters = this.getCurrentFilters();
            const params = new URLSearchParams();
            
            // إضافة الفلترات للإحصائيات
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== null && value !== '') {
                    params.append(key, value);
                }
            });
            
            const response = await fetch(`../api/v1/violations?action=statistics&${params.toString()}`);
            const data = await response.json();
            
            if (data.success) {
                const stats = data.statistics;
                
                // تحديث الإحصائيات
                this.updateElementText('totalViolations', this.formatNumber(stats.total_count));
                this.updateElementText('totalAmount', this.formatCurrency(stats.total_amount));
                this.updateElementText('paidViolations', this.formatNumber(stats.paid_count));
                this.updateElementText('paidAmount', this.formatCurrency(stats.paid_amount));
                this.updateElementText('unpaidViolations', this.formatNumber(stats.unpaid_count));
                this.updateElementText('unpaidAmount', this.formatCurrency(stats.unpaid_amount));
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }
    
    async loadCurrentTime() {
        try {
            const datetimeInput = document.getElementById('violationDatetime');
            if (datetimeInput && !datetimeInput.value) {
                const now = new Date();
                // توقيت الإمارات (UTC+4)
                now.setHours(now.getHours() + 4);
                const localDatetime = now.toISOString().slice(0, 16);
                datetimeInput.value = localDatetime;
            }
        } catch (error) {
            console.error('Error setting current time:', error);
        }
    }
    
    async loadViolations() {
        if (this.isLoading) return;
        
        try {
            this.isLoading = true;
            this.showLoading('violationsTableBody');
            
            const filters = this.getCurrentFilters();
            const params = new URLSearchParams();
            
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== null && value !== '') {
                    params.append(key, value);
                }
            });
            
            // إضافة معلمات الصفحة
            params.append('page', this.currentPage);
            params.append('per_page', this.itemsPerPage);
            
            const response = await fetch(`../api/v1/violations?${params.toString()}`);
            const data = await response.json();
            
            if (data.success) {
                this.filteredData = data.data || [];
                this.totalItems = data.count || data.data.length;
                
                // إعادة تعيين الصفحة الحالية إذا لزم الأمر
                if (this.currentPage > Math.ceil(this.filteredData.length / this.itemsPerPage)) {
                    this.currentPage = 1;
                }
                
                this.renderViolationsTable();
                this.renderPagination();
                
                // تحميل الإحصائيات بناءً على نفس الفلترات
                await this.loadStatistics();
            } else {
                this.showAlert('error', data.message || this.translate('loadDataError'));
            }
        } catch (error) {
            console.error('Error loading violations:', error);
            this.showAlert('error', this.translate('connectionError'));
        } finally {
            this.isLoading = false;
            this.hideLoading('violationsTableBody');
        }
    }
    
    getCurrentFilters() {
        const vehicleFilter = document.getElementById('vehicleCodeFilter');
        let vehicleCode = vehicleFilter?.value || '';
        
        // إذا كان هناك مركبة محددة من البحث الذكي
        if (this.selectedVehicle && this.selectedVehicle.code) {
            vehicleCode = this.selectedVehicle.code;
        }
        
        return {
            department_id: document.getElementById('departmentFilter')?.value || '',
            section_id: document.getElementById('sectionFilter')?.value || '',
            division_id: document.getElementById('divisionFilter')?.value || '',
            violation_status: document.getElementById('statusFilter')?.value || '',
            vehicle_code: vehicleCode,
            start_date: document.getElementById('startDate')?.value || '',
            end_date: document.getElementById('endDate')?.value || ''
        };
    }
    
    renderViolationsTable() {
        const tbody = document.getElementById('violationsTableBody');
        if (!tbody) return;
        
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const pageData = this.filteredData.slice(startIndex, endIndex);
        
        tbody.innerHTML = '';
        
        if (pageData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>${this.translate('noData')}</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        pageData.forEach((violation, index) => {
            const row = document.createElement('tr');
            row.dataset.id = violation.id;
            
            // التحقق من الصلاحيات
            const canEdit = this.isAdmin || violation.issued_by_emp_id === this.userEmpId;
            const canDelete = this.isAdmin;
            
            // البيانات
            const driverName = violation.driver_name || this.translate('notSpecified');
            const issuedByName = violation.issued_by_name || violation.issued_by_emp_id || this.translate('unknown');
            const paymentDate = violation.payment_datetime ? this.formatDate(violation.payment_datetime) : '-';
            
            // المرفق
            const attachmentLink = violation.attachment_url ? 
                `<a href="${violation.attachment_url}" target="_blank" class="attachment-link">
                    <i class="fas fa-paperclip"></i>
                    <span>${this.translate('view')}</span>
                </a>` : 
                '<span class="text-muted">-</span>';
            
            // أزرار الإجراءات
            const actions = [];
            
            if (canEdit) {
                actions.push(`
                    <button class="btn btn-sm btn-warning" onclick="violationsManager.editViolation(${violation.id})" 
                            title="${this.translate('edit')}">
                        <i class="fas fa-edit"></i>
                    </button>
                `);
            }
            
            if (canDelete) {
                actions.push(`
                    <button class="btn btn-sm btn-danger" onclick="violationsManager.deleteViolation(${violation.id})" 
                            title="${this.translate('delete')}">
                        <i class="fas fa-trash"></i>
                    </button>
                `);
            }
            
            row.innerHTML = `
                <td>${this.escapeHtml(violation.vehicle_code)}</td>
                <td>${this.escapeHtml(driverName)}</td>
                <td>${this.formatDate(violation.violation_datetime)}</td>
                <td class="text-bold">${this.formatCurrency(violation.violation_amount)}</td>
                <td>
                    <span class="status-badge status-${violation.violation_status}">
                        ${violation.violation_status === 'paid' ? this.translate('paid') : this.translate('unpaid')}
                    </span>
                </td>
                <td>${this.escapeHtml(violation.department_name || '')}</td>
                <td>${this.escapeHtml(issuedByName)}</td>
                <td>${paymentDate}</td>
                <td>${this.escapeHtml(violation.notes || '-')}</td>
                <td>${attachmentLink}</td>
                <td>
                    <div class="action-buttons">
                        ${actions.join('')}
                    </div>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }
    
    renderPagination() {
        const totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
        const paginationContainer = document.getElementById('paginationContainer');
        
        if (!paginationContainer) return;
        
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        const startItem = ((this.currentPage - 1) * this.itemsPerPage) + 1;
        const endItem = Math.min(this.currentPage * this.itemsPerPage, this.filteredData.length);
        
        let pageButtons = '';
        const maxVisibleButtons = this.isMobile ? 3 : 5;
        
        if (totalPages <= maxVisibleButtons) {
            for (let i = 1; i <= totalPages; i++) {
                pageButtons += this.createPageButton(i);
            }
        } else {
            pageButtons = this.createComplexPagination(totalPages, maxVisibleButtons);
        }
        
        paginationContainer.innerHTML = `
            <div class="pagination-controls">
                <div class="pagination-info">
                    ${this.translate('showing')} ${startItem}-${endItem} ${this.translate('of')} ${this.filteredData.length}
                </div>
                <div class="page-buttons">
                    <button class="page-btn ${this.currentPage === 1 ? 'disabled' : ''}" 
                            onclick="violationsManager.changePage(1)" ${this.currentPage === 1 ? 'disabled' : ''} 
                            title="${this.translate('firstPage')}">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                    <button class="page-btn ${this.currentPage === 1 ? 'disabled' : ''}" 
                            onclick="violationsManager.changePage(${this.currentPage - 1})" ${this.currentPage === 1 ? 'disabled' : ''}
                            title="${this.translate('previousPage')}">
                        <i class="fas fa-angle-right"></i>
                    </button>
                    
                    ${pageButtons}
                    
                    <button class="page-btn ${this.currentPage === totalPages ? 'disabled' : ''}" 
                            onclick="violationsManager.changePage(${this.currentPage + 1})" ${this.currentPage === totalPages ? 'disabled' : ''}
                            title="${this.translate('nextPage')}">
                        <i class="fas fa-angle-left"></i>
                    </button>
                    <button class="page-btn ${this.currentPage === totalPages ? 'disabled' : ''}" 
                            onclick="violationsManager.changePage(${totalPages})" ${this.currentPage === totalPages ? 'disabled' : ''}
                            title="${this.translate('lastPage')}">
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    createPageButton(page) {
        return `
            <button class="page-btn ${this.currentPage === page ? 'active' : ''}" 
                    onclick="violationsManager.changePage(${page})">
                ${page}
            </button>
        `;
    }
    
    createComplexPagination(totalPages, maxVisibleButtons) {
        let pageButtons = '';
        const halfVisible = Math.floor(maxVisibleButtons / 2);
        
        if (this.currentPage <= halfVisible + 1) {
            // البداية
            for (let i = 1; i <= maxVisibleButtons - 1; i++) {
                pageButtons += this.createPageButton(i);
            }
            pageButtons += '<span class="text-muted">...</span>';
            pageButtons += this.createPageButton(totalPages);
        } else if (this.currentPage >= totalPages - halfVisible) {
            // النهاية
            pageButtons += this.createPageButton(1);
            pageButtons += '<span class="text-muted">...</span>';
            for (let i = totalPages - maxVisibleButtons + 2; i <= totalPages; i++) {
                pageButtons += this.createPageButton(i);
            }
        } else {
            // الوسط
            pageButtons += this.createPageButton(1);
            pageButtons += '<span class="text-muted">...</span>';
            for (let i = this.currentPage - Math.floor((maxVisibleButtons - 2) / 2); 
                 i <= this.currentPage + Math.floor((maxVisibleButtons - 2) / 2); i++) {
                pageButtons += this.createPageButton(i);
            }
            pageButtons += '<span class="text-muted">...</span>';
            pageButtons += this.createPageButton(totalPages);
        }
        
        return pageButtons;
    }
    
    changePage(page) {
        if (page < 1 || page > Math.ceil(this.filteredData.length / this.itemsPerPage)) return;
        
        this.currentPage = page;
        this.renderViolationsTable();
        this.renderPagination();
        
        // التمرير إلى الجدول على الهواتف
        if (this.isMobile) {
            const tableSection = document.getElementById('tableSection');
            if (tableSection) {
                tableSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }
    
    updateVehicleDetails(vehicleId) {
        const select = document.getElementById('vehicleSelect');
        const vehicleCodeInput = document.getElementById('vehicleCode');
        
        if (!select || !vehicleCodeInput) return;
        
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const vehicleCode = selectedOption.getAttribute('data-code');
            vehicleCodeInput.value = vehicleCode || '';
        }
    }
    
    previewFile(file) {
        if (!file) return;
        
        const filePreview = document.getElementById('filePreview');
        if (!filePreview) return;
        
        // التحقق من حجم الملف (5MB كحد أقصى)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            this.showAlert('error', this.translate('fileTooLarge'));
            return;
        }
        
        const reader = new FileReader();
        const fileType = file.type;
        
        reader.onload = (e) => {
            const isImage = fileType.startsWith('image/');
            const isPDF = fileType === 'application/pdf';
            const icon = isImage ? 'fa-image' : isPDF ? 'fa-file-pdf' : 'fa-file';
            
            filePreview.innerHTML = `
                <div class="file-preview-item">
                    <i class="fas ${icon} file-preview-icon"></i>
                    <div class="file-preview-info">
                        <div class="file-preview-name">${file.name}</div>
                        <div class="file-preview-size">${this.formatFileSize(file.size)}</div>
                    </div>
                    <button class="file-preview-remove" onclick="this.closest('.file-preview-item').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            filePreview.classList.add('has-file');
        };
        
        reader.readAsDataURL(file);
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = this.currentLanguage === 'ar' ? ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت'] : ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    toggleAttachmentField(status) {
        const attachmentSection = document.getElementById('attachmentSection');
        
        if (attachmentSection) {
            attachmentSection.style.display = 'grid';
        }
        
        // إزالة العلامة الإجبارية من تسمية المرفق
        const attachmentLabel = document.querySelector('label[for="paymentAttachment"]');
        if (attachmentLabel) {
            attachmentLabel.innerHTML = this.translate('attachment');
        }
    }
    
    async saveViolation() {
        try {
            this.showLoading('saveBtn');
            
            // التحقق من الحقول المطلوبة
            if (!this.validateForm()) {
                this.hideLoading('saveBtn');
                return;
            }
            
            if (this.isEditMode && this.currentViolationId) {
                await this.updateViolation();
            } else {
                await this.createViolation();
            }
        } catch (error) {
            console.error('Error saving violation:', error);
            this.showAlert('error', error.message || this.translate('saveError'));
            this.hideLoading('saveBtn');
        }
    }
    
    validateForm() {
        const vehicleSelect = document.getElementById('vehicleSelect');
        const violationDatetime = document.getElementById('violationDatetime');
        const violationAmount = document.getElementById('violationAmount');
        
        if (!vehicleSelect?.value) {
            this.showAlert('error', this.translate('selectVehicleRequired'));
            return false;
        }
        
        if (!violationDatetime?.value) {
            this.showAlert('error', this.translate('dateRequired'));
            return false;
        }
        
        const amount = parseFloat(violationAmount?.value || 0);
        if (!violationAmount?.value || isNaN(amount) || amount <= 0) {
            this.showAlert('error', this.translate('amountRequired'));
            return false;
        }
        
        // تم إزالة شرط المرفق الإجباري - المرفق اختياري
        return true;
    }
    
    async createViolation() {
        const formData = new FormData();
        const vehicleSelect = document.getElementById('vehicleSelect');
        const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
        const vehicleCode = selectedOption.getAttribute('data-code');
        
        if (!vehicleCode) {
            this.showAlert('error', 'لم يتم العثور على رقم المركبة');
            this.hideLoading('saveBtn');
            return;
        }
        
        formData.append('vehicle_id', vehicleSelect.value);
        formData.append('vehicle_code', vehicleCode);
        formData.append('violation_datetime', document.getElementById('violationDatetime').value);
        formData.append('violation_amount', document.getElementById('violationAmount').value);
        
        const notes = document.getElementById('notes')?.value;
        if (notes) formData.append('notes', notes);
        
        const response = await fetch('../api/v1/violations', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            this.showAlert('success', this.translate('savedSuccessfully'));
            await this.loadViolations();
            await this.loadStatistics();
            this.resetForm();
        } else {
            throw new Error(data.message || this.translate('saveError'));
        }
        
        this.hideLoading('saveBtn');
    }
    
    async updateViolation() {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('id', this.currentViolationId);
        formData.append('violation_amount', document.getElementById('violationAmount').value);
        formData.append('violation_datetime', document.getElementById('violationDatetime').value);
        
        const notes = document.getElementById('notes')?.value;
        if (notes) formData.append('notes', notes);
        
        const violationStatus = document.getElementById('violationStatus')?.value;
        if (violationStatus) formData.append('violation_status', violationStatus);
        
        // المرفق اختياري
        const attachmentInput = document.getElementById('paymentAttachment');
        if (attachmentInput?.files.length > 0) {
            formData.append('payment_attachment', attachmentInput.files[0]);
        }
        
        const response = await fetch('../api/v1/violations', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            this.showAlert('success', this.translate('updatedSuccessfully'));
            await this.loadViolations();
            await this.loadStatistics();
            this.resetForm();
        } else {
            throw new Error(data.message || this.translate('updateError'));
        }
        
        this.hideLoading('saveBtn');
    }
    
    async editViolation(violationId) {
        try {
            this.showLoading('violationsTableBody');
            
            const response = await fetch(`../api/v1/violations?action=single&id=${violationId}`);
            const data = await response.json();
            
            if (data.success) {
                const violation = data.data;
                this.fillFormForEdit(violation);
                
                // التمرير إلى النموذج
                this.scrollToForm();
            } else {
                this.showAlert('error', data.message || this.translate('loadError'));
            }
        } catch (error) {
            console.error('Error loading violation for edit:', error);
            this.showAlert('error', this.translate('loadError'));
        } finally {
            this.hideLoading('violationsTableBody');
        }
    }
    
    fillFormForEdit(violation) {
        // تحديث بيانات المركبة
        const vehicleSelect = document.getElementById('vehicleSelect');
        if (vehicleSelect) {
            for (let i = 0; i < vehicleSelect.options.length; i++) {
                if (vehicleSelect.options[i].value == violation.vehicle_id) {
                    vehicleSelect.selectedIndex = i;
                    break;
                }
            }
        }
        
        this.updateVehicleDetails(violation.vehicle_id);
        
        // تحديث الحقول الأخرى
        this.setFormValue('violationDatetime', violation.violation_datetime);
        this.setFormValue('violationAmount', violation.violation_amount);
        this.setFormValue('violationStatus', violation.violation_status || 'unpaid');
        this.setFormValue('notes', violation.notes);
        
        // إدارة المرفقات
        this.showCurrentAttachment(violation);
        
        // تحديث الوضع
        this.isEditMode = true;
        this.currentViolationId = violation.id;
        this.updateDynamicTexts();
        
        // إظهار قسم المرفقات
        const attachmentSection = document.getElementById('attachmentSection');
        if (attachmentSection) {
            attachmentSection.style.display = 'grid';
        }
        
        // إظهار زر الإلغاء
        const resetBtn = document.getElementById('resetBtn');
        if (resetBtn) {
            resetBtn.style.display = 'inline-flex';
        }
    }
    
    setFormValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            if (element.type === 'datetime-local' && value) {
                const date = new Date(value);
                element.value = date.toISOString().slice(0, 16);
            } else {
                element.value = value || '';
            }
        }
    }
    
    showCurrentAttachment(violation) {
        const filePreview = document.getElementById('filePreview');
        if (!filePreview) return;
        
        if (violation.attachment_url) {
            filePreview.innerHTML = `
                <div class="file-preview-item">
                    <i class="fas fa-paperclip file-preview-icon"></i>
                    <div class="file-preview-info">
                        <div class="file-preview-name">${violation.attachment_name || this.translate('currentAttachment')}</div>
                        <div class="text-muted">${this.translate('currentAttachment')}</div>
                    </div>
                    <a href="${violation.attachment_url}" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            `;
            filePreview.classList.add('has-file');
        } else {
            filePreview.innerHTML = '';
            filePreview.classList.remove('has-file');
        }
    }
    
    scrollToForm() {
        const formSection = document.getElementById('formSection');
        if (formSection) {
            const offset = this.isMobile ? 70 : 100;
            const top = formSection.offsetTop - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    }
    
    async deleteViolation(violationId) {
        if (!confirm(this.translate('confirmDelete'))) {
            return;
        }
        
        try {
            const response = await fetch('../api/v1/violations', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: violationId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('success', this.translate('deletedSuccessfully'));
                await this.loadViolations();
                await this.loadStatistics();
            } else {
                this.showAlert('error', data.message || this.translate('deleteError'));
            }
        } catch (error) {
            console.error('Error deleting violation:', error);
            this.showAlert('error', this.translate('deleteError'));
        }
    }
    
    resetForm() {
        const form = document.getElementById('violationForm');
        if (form) {
            form.reset();
            document.getElementById('filePreview').innerHTML = '';
            document.getElementById('filePreview').classList.remove('has-file');
        }
        
        this.isEditMode = false;
        this.currentViolationId = null;
        
        // إخفاء قسم المرفقات
        const attachmentSection = document.getElementById('attachmentSection');
        if (attachmentSection) {
            attachmentSection.style.display = 'none';
        }
        
        // إخفاء زر الإلغاء
        const resetBtn = document.getElementById('resetBtn');
        if (resetBtn) {
            resetBtn.style.display = 'none';
        }
        
        // تحديث النصوص
        this.updateDynamicTexts();
        
        // إعادة تعيين الوقت الحالي
        this.loadCurrentTime();
    }
    
    resetFilters() {
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.reset();
            document.getElementById('sectionFilter').innerHTML = `<option value="">${this.translate('allSections')}</option>`;
            document.getElementById('divisionFilter').innerHTML = `<option value="">${this.translate('allDivisions')}</option>`;
            this.selectedVehicle = null;
            this.hideVehicleSearchDropdown();
            this.loadViolations();
        }
    }
    
    async changeLanguage(lang) {
        this.currentLanguage = lang;
        localStorage.setItem('preferred_language', lang);
        document.documentElement.lang = lang;
        document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
        
        this.updateLanguageButtons();
        await this.loadTranslations();
        await this.loadInitialData();
        await this.loadViolations();
    }
    
    printReport() {
        const printContent = this.generatePrintContent();
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
    
    generatePrintContent() {
        const today = new Date().toLocaleDateString(this.currentLanguage === 'ar' ? 'ar-EG' : 'en-US');
        const user = this.userData?.username || (this.currentLanguage === 'ar' ? 'مستخدم' : 'User');
        
        let tableRows = '';
        this.filteredData.forEach((violation, index) => {
            tableRows += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${violation.vehicle_code}</td>
                    <td>${violation.driver_name || ''}</td>
                    <td>${this.formatDate(violation.violation_datetime)}</td>
                    <td>${this.formatCurrency(violation.violation_amount)}</td>
                    <td>${violation.violation_status === 'paid' ? this.translate('paid') : this.translate('unpaid')}</td>
                    <td>${violation.department_name || ''}</td>
                    <td>${violation.notes || ''}</td>
                </tr>
            `;
        });
        
        return `
            <!DOCTYPE html>
            <html dir="${this.currentLanguage === 'ar' ? 'rtl' : 'ltr'}" lang="${this.currentLanguage}">
            <head>
                <meta charset="UTF-8">
                <title>${this.translate('reportTitle')}</title>
                <style>
                    body { font-family: '${this.currentLanguage === 'ar' ? 'Cairo' : 'Arial'}, sans-serif; font-size: 12px; padding: 20px; }
                    h1 { color: #1a472a; text-align: center; margin-bottom: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .info { margin-bottom: 20px; display: flex; justify-content: space-between; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; text-align: center; }
                    td { padding: 8px; border: 1px solid #ddd; text-align: center; }
                    .total { font-weight: bold; text-align: right; padding: 10px; }
                    @media print {
                        @page { size: landscape; margin: 10mm; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>${this.translate('reportTitle')}</h1>
                    <p>${this.translate('reportDate')}: ${today}</p>
                </div>
                <div class="info">
                    <div>${this.translate('user')}: ${user}</div>
                    <div>${this.translate('totalRecords')}: ${this.filteredData.length}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>${this.translate('vehicleCode')}</th>
                            <th>${this.translate('driverName')}</th>
                            <th>${this.translate('violationDate')}</th>
                            <th>${this.translate('amount')}</th>
                            <th>${this.translate('status')}</th>
                            <th>${this.translate('department')}</th>
                            <th>${this.translate('notes')}</th>
                        </tr>
                    </thead>
                    <tbody>${tableRows}</tbody>
                </table>
                <div class="total">
                    ${this.translate('totalAmount')}: ${this.formatCurrency(
                        this.filteredData.reduce((sum, v) => sum + parseFloat(v.violation_amount || 0), 0)
                    )}
                </div>
            </body>
            </html>
        `;
    }
    
    exportToExcel() {
        if (this.filteredData.length === 0) {
            this.showAlert('warning', this.translate('noDataToExport'));
            return;
        }
        
        const headers = [
            this.translate('vehicleCode'),
            this.translate('driverName'),
            this.translate('violationDate'),
            this.translate('amount'),
            this.translate('status'),
            this.translate('department'),
            this.translate('section'),
            this.translate('division'),
            this.translate('issuedBy'),
            this.translate('paymentDate'),
            this.translate('notes')
        ];
        
        const csvData = this.filteredData.map(violation => [
            violation.vehicle_code,
            violation.driver_name || '',
            this.formatDate(violation.violation_datetime),
            violation.violation_amount,
            violation.violation_status === 'paid' ? this.translate('paid') : this.translate('unpaid'),
            violation.department_name || '',
            violation.section_name || '',
            violation.division_name || '',
            violation.issued_by_name || '',
            violation.payment_datetime ? this.formatDate(violation.payment_datetime) : '',
            violation.notes || ''
        ]);
        
        const csvContent = [
            '\uFEFF' + headers.join(','),
            ...csvData.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        const today = new Date().toISOString().slice(0, 10);
        
        link.href = url;
        link.download = `violations_${today}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        this.showAlert('success', this.translate('exportSuccess'));
    }
    
    showWelcomeMessage() {
        if (this.userData) {
            setTimeout(() => {
                this.showAlert('success', 
                    `${this.translate('welcomeMessage')}, ${this.userData.username}!`
                );
            }, 1000);
        }
    }
    
    // ===== دوال مساعدة =====
    updateElementText(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) element.textContent = text;
    }
    
    formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            if (this.currentLanguage === 'ar') {
                return date.toLocaleDateString('ar-EG', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } else {
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
            }
        } catch (error) {
            return dateString;
        }
    }
    
    formatCurrency(amount) {
        try {
            const num = parseFloat(amount || 0);
            if (this.currentLanguage === 'ar') {
                return new Intl.NumberFormat('ar-EG', {
                    style: 'currency',
                    currency: 'AED',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(num);
            } else {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'AED',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(num);
            }
        } catch (error) {
            return `${amount || 0} AED`;
        }
    }
    
    formatNumber(number) {
        try {
            if (this.currentLanguage === 'ar') {
                return new Intl.NumberFormat('ar-EG').format(number || 0);
            } else {
                return new Intl.NumberFormat('en-US').format(number || 0);
            }
        } catch (error) {
            return number || 0;
        }
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showAlert(type, message) {
        const alertDiv = document.getElementById('alertMessage');
        if (!alertDiv) return;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <div class="alert-content">
                <i class="fas ${icons[type] || 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        `;
        alertDiv.style.display = 'flex';
        
        // إخفاء التنبيه تلقائياً
        setTimeout(() => {
            if (alertDiv.style.display === 'flex') {
                alertDiv.style.display = 'none';
            }
        }, 5000);
    }
    
    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            if (elementId === 'violationsTableBody') {
                element.innerHTML = `
                    <tr>
                        <td colspan="11" class="empty-state">
                            <div class="loading-spinner"></div>
                            <p>${this.translate('loading')}</p>
                        </td>
                    </tr>
                `;
            } else if (element.tagName === 'BUTTON') {
                const originalHTML = element.innerHTML;
                element.setAttribute('data-original-html', originalHTML);
                element.innerHTML = '<div class="loading-spinner"></div>';
                element.disabled = true;
            }
        }
    }
    
    hideLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            if (elementId === 'violationsTableBody') {
                // يتم تحديث المحتوى في renderViolationsTable
            } else if (element.tagName === 'BUTTON') {
                const originalHTML = element.getAttribute('data-original-html');
                if (originalHTML) {
                    element.innerHTML = originalHTML;
                }
                element.disabled = false;
            }
        }
    }
}

// تهيئة النظام مع معالجة الأخطاء
document.addEventListener('DOMContentLoaded', () => {
    try {
        window.violationsManager = new VehicleViolationsManager();
        
        // إضافة تحسينات للأداء
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js').catch(() => {});
        }
        
        // تحديث نوع الجهاز عند تغيير حجم النافذة
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.violationsManager) {
                    window.violationsManager.detectDevice();
                    window.violationsManager.updateUIForDevice();
                }
            }, 250);
        });
        
    } catch (error) {
        console.error('Failed to initialize system:', error);
        alert('حدث خطأ في تحميل النظام. يرجى تحديث الصفحة.');
    }
});

// دعم الإكمال التلقائي على الهواتف
if ('virtualKeyboard' in navigator) {
    navigator.virtualKeyboard.overlaysContent = true;
}