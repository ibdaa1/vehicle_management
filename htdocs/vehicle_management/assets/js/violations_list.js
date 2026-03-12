// vehicle_management/assets/js/violations_list.js

document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let currentUser = null;
    let currentLanguage = 'ar';
    let translations = {};
    let csrfToken = '';
    let violationsData = [];
    let filteredData = [];
    let currentPage = 1;
    let itemsPerPage = 25;
    let totalPages = 1;
    
    // DOM Elements
    const usernameElement = document.getElementById('username');
    const langArBtn = document.getElementById('langAr');
    const langEnBtn = document.getElementById('langEn');
    const logoutBtn = document.getElementById('logoutBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const messageModal = document.getElementById('messageModal');
    const violationDetailsModal = document.getElementById('violationDetailsModal');
    const serverTimeElement = document.getElementById('serverTime');
    
    // Stats Elements
    const totalViolationsElement = document.getElementById('totalViolations');
    const unpaidViolationsElement = document.getElementById('unpaidViolations');
    const paidViolationsElement = document.getElementById('paidViolations');
    const totalAmountElement = document.getElementById('totalAmount');
    const tableTotalAmountElement = document.getElementById('tableTotalAmount');
    
    // Filter Elements
    const filterVehicleCode = document.getElementById('filterVehicleCode');
    const filterStatus = document.getElementById('filterStatus');
    const filterStartDate = document.getElementById('filterStartDate');
    const filterEndDate = document.getElementById('filterEndDate');
    const filterDriver = document.getElementById('filterDriver');
    const filterIssuer = document.getElementById('filterIssuer');
    const applyFiltersBtn = document.getElementById('applyFilters');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const exportExcelBtn = document.getElementById('exportExcel');
    const printReportBtn = document.getElementById('printReport');
    
    // Table Elements
    const refreshTableBtn = document.getElementById('refreshTable');
    const entriesPerPageSelect = document.getElementById('entriesPerPage');
    const violationsTableBody = document.querySelector('#violationsTable tbody');
    const pagination = document.getElementById('pagination');
    
    // Modal Elements
    const modalCloseBtns = document.querySelectorAll('.modal-close');
    const modalOkBtn = document.getElementById('modalOk');
    
    // Initialize the application
    initApplication();
    
    // Application Initialization
    async function initApplication() {
        showLoading();
        
        try {
            // Check session and get user info
            await checkSession();
            
            // Get CSRF token
            await getCsrfToken();
            
            // Load translations
            await loadTranslations();
            
            // Apply translations
            applyTranslations();
            
            // Setup event listeners
            setupEventListeners();
            
            // Load violations data
            await loadViolationsData();
            
            // Update server time
            updateServerTime();
            setInterval(updateServerTime, 60000);
            
            // Set date inputs format
            setDateInputsFormat();
            
        } catch (error) {
            console.error('Initialization error:', error);
            showMessage('error', 'خطأ في التهيئة', 'حدث خطأ أثناء تحميل التطبيق. يرجى تحديث الصفحة.');
        } finally {
            hideLoading();
        }
    }
    
    // Check user session
    async function checkSession() {
        try {
            const response = await fetch('/vehicle_management/api/users/session_check.php');
            const data = await response.json();
            
            if (data.success && data.isLoggedIn) {
                currentUser = data.user;
                currentLanguage = currentUser.preferred_language || 'ar';
                
                // Update UI with user info
                usernameElement.textContent = currentUser.username || currentUser.emp_id;
                
                // Update language buttons
                updateLanguageButtons();
                
            } else {
                // Redirect to login if not authenticated
                window.location.href = '/vehicle_management/public/login.html';
            }
        } catch (error) {
            console.error('Session check error:', error);
            throw new Error('فشل في التحقق من الجلسة');
        }
    }
    
    // Get CSRF token
    async function getCsrfToken() {
        try {
            const response = await fetch('/vehicle_management/api/config/session.php');
            const data = await response.json();
            
            if (data.success && data.csrf_token) {
                csrfToken = data.csrf_token;
                const csrfInput = document.getElementById('csrf_token');
                if (csrfInput) {
                    csrfInput.value = csrfToken;
                }
            }
        } catch (error) {
            console.error('CSRF token error:', error);
            csrfToken = 'csrf_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
        }
    }
    
    // Load translations
    async function loadTranslations() {
        const langFile = currentLanguage === 'ar' ? 
            '/vehicle_management/languages/ar_add_vehicle_violations.json' :
            '/vehicle_management/languages/en_add_vehicle_violations.json';
        
        try {
            const response = await fetch(langFile);
            translations = await response.json();
        } catch (error) {
            console.error('Failed to load translations:', error);
            translations = getDefaultTranslations();
        }
    }
    
    // Get default translations (fallback)
    function getDefaultTranslations() {
        return {
            "dashboard": "لوحة التحكم",
            "vehicles": "المركبات",
            "add_violations": "إضافة مخالفات",
            "violations_list": "قائمة المخالفات",
            "reports": "التقارير",
            "logout": "تسجيل الخروج",
            "total_violations": "إجمالي المخالفات",
            "unpaid_violations": "غير مدفوعة",
            "paid_violations": "مدفوعة",
            "total_amount": "إجمالي المبالغ",
            "filters": "تصفية النتائج",
            "reset_filters": "إعادة التعيين",
            "vehicle_code": "رقم المركبة",
            "violation_status": "حالة المخالفة",
            "all": "الكل",
            "unpaid": "غير مدفوعة",
            "paid": "مدفوعة",
            "start_date": "من تاريخ",
            "end_date": "إلى تاريخ",
            "driver_name": "اسم السائق",
            "issued_by": "صادرت بواسطة",
            "apply_filters": "تطبيق الفلاتر",
            "export_excel": "تصدير Excel",
            "print_report": "طباعة التقرير",
            "refresh": "تحديث",
            "show": "عرض",
            "entries": "سجل",
            "violation_id": "رقم المخالفة",
            "violation_date": "تاريخ المخالفة",
            "violation_amount": "مبلغ المخالفة",
            "payment_date": "تاريخ الدفع",
            "notes": "ملاحظات",
            "actions": "الإجراءات",
            "view": "عرض",
            "edit": "تعديل",
            "delete": "حذف",
            "mark_paid": "تحديد كمُدفوع",
            "violation_details": "تفاصيل المخالفة",
            "vehicle_info": "معلومات المركبة",
            "vehicle_type": "نوع المركبة",
            "vehicle_status": "حالة المركبة",
            "issue_date": "تاريخ الإصدار",
            "paid_by": "دفع بواسطة",
            "print": "طباعة",
            "close": "إغلاق",
            "edit_violation": "تعديل المخالفة",
            "cancel": "إلغاء",
            "save": "حفظ",
            "confirm_delete": "تأكيد الحذف",
            "confirm_delete_message": "هل أنت متأكد من حذف هذه المخالفة؟ هذا الإجراء لا يمكن التراجع عنه.",
            "delete": "حذف",
            "loading_data": "جاري تحميل البيانات...",
            "copyright": "© 2024 نظام إدارة المركبات - بلدية الشارقة. جميع الحقوق محفوظة.",
            "server_time": "وقت الخادم",
            "search_vehicle_code": "ابحث برقم المركبة...",
            "search_driver": "اسم السائق...",
            "search_issuer": "اسم المُصدر...",
            "select_date": "dd/mm/yyyy"
        };
    }
    
    // Apply translations to the page
    function applyTranslations() {
        // Translate all elements with data-lang attribute
        document.querySelectorAll('[data-lang]').forEach(element => {
            const key = element.getAttribute('data-lang');
            if (translations[key]) {
                if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.tagName === 'SELECT') {
                    if (element.type !== 'submit' && element.type !== 'button') {
                        element.placeholder = translations[key];
                    }
                } else {
                    element.textContent = translations[key];
                }
            }
        });
        
        // Update page direction based on language
        document.documentElement.dir = currentLanguage === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.lang = currentLanguage;
        
        // Update date placeholders
        if (translations.select_date) {
            filterStartDate.placeholder = translations.select_date;
            filterEndDate.placeholder = translations.select_date;
        }
        
        if (translations.search_vehicle_code) {
            filterVehicleCode.placeholder = translations.search_vehicle_code;
        }
        
        if (translations.search_driver) {
            filterDriver.placeholder = translations.search_driver;
        }
        
        if (translations.search_issuer) {
            filterIssuer.placeholder = translations.search_issuer;
        }
    }
    
    // Update language buttons
    function updateLanguageButtons() {
        if (currentLanguage === 'ar') {
            langArBtn.classList.add('active');
            langEnBtn.classList.remove('active');
        } else {
            langEnBtn.classList.add('active');
            langArBtn.classList.remove('active');
        }
    }
    
    // Set date inputs format
    function setDateInputsFormat() {
        // Set today's date as max for end date
        const today = new Date().toISOString().split('T')[0];
        filterStartDate.max = today;
        filterEndDate.max = today;
        
        // Set min date for end date based on start date
        filterStartDate.addEventListener('change', function() {
            filterEndDate.min = this.value;
            if (filterEndDate.value && filterEndDate.value < this.value) {
                filterEndDate.value = this.value;
            }
        });
        
        filterEndDate.addEventListener('change', function() {
            if (filterStartDate.value && this.value < filterStartDate.value) {
                this.value = filterStartDate.value;
            }
        });
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Language switching
        langArBtn.addEventListener('click', () => switchLanguage('ar'));
        langEnBtn.addEventListener('click', () => switchLanguage('en'));
        
        // Logout
        logoutBtn.addEventListener('click', handleLogout);
        
        // Refresh table
        refreshTableBtn.addEventListener('click', loadViolationsData);
        
        // Entries per page
        entriesPerPageSelect.addEventListener('change', function() {
            itemsPerPage = parseInt(this.value);
            currentPage = 1;
            applyFilters();
        });
        
        // Filter actions
        applyFiltersBtn.addEventListener('click', applyFilters);
        resetFiltersBtn.addEventListener('click', resetFilters);
        exportExcelBtn.addEventListener('click', exportToExcel);
        printReportBtn.addEventListener('click', printReport);
        
        // Modal close buttons
        modalCloseBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
        
        // Message modal OK button
        if (modalOkBtn) {
            modalOkBtn.addEventListener('click', () => {
                messageModal.style.display = 'none';
            });
        }
        
        // Search on Enter key
        [filterVehicleCode, filterDriver, filterIssuer].forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        });
    }
    
    // Switch language
    async function switchLanguage(lang) {
        if (lang === currentLanguage) return;
        
        showLoading();
        currentLanguage = lang;
        
        try {
            await loadTranslations();
            applyTranslations();
            updateLanguageButtons();
            
            // Reload data to get translated statuses
            await loadViolationsData();
            
            // Save language preference
            await saveLanguagePreference(lang);
            
        } catch (error) {
            console.error('Language switch error:', error);
        } finally {
            hideLoading();
        }
    }
    
    // Save language preference
    async function saveLanguagePreference(lang) {
        try {
            const response = await fetch('/vehicle_management/api/users/update_preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    preferred_language: lang,
                    csrf_token: csrfToken
                })
            });
        } catch (error) {
            console.error('Failed to save language preference:', error);
        }
    }
    
    // Handle logout
    async function handleLogout() {
        try {
            const response = await fetch('/vehicle_management/api/users/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ csrf_token: csrfToken })
            });
            
            window.location.href = '/vehicle_management/public/login.html';
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = '/vehicle_management/public/login.html';
        }
    }
    
    // Load violations data from API
    async function loadViolationsData() {
        showLoading();
        
        try {
            // Build query parameters
            const params = new URLSearchParams();
            params.append('limit', 1000); // Get all for local filtering
            params.append('days', 365);
            
            const response = await fetch(`/vehicle_management/api/vehicle/get_recent_violations.php?${params.toString()}`);
            const data = await response.json();
            
            console.log('API Response:', data);
            
            if (data.success) {
                violationsData = data.violations || [];
                filteredData = [...violationsData];
                
                // Update statistics
                updateStatistics(data);
                
                // Apply any active filters
                applyFilters();
                
            } else {
                throw new Error(data.message || 'Failed to load violations');
            }
            
        } catch (error) {
            console.error('Error loading violations:', error);
            showMessage('error', 'خطأ في تحميل البيانات', 'فشل في تحميل قائمة المخالفات. يرجى المحاولة مرة أخرى.');
        } finally {
            hideLoading();
        }
    }
    
    // Update statistics
    function updateStatistics(data) {
        // Calculate statistics from all data
        const total = violationsData.length;
        const unpaid = violationsData.filter(v => v.violation_status === 'unpaid').length;
        const paid = violationsData.filter(v => v.violation_status === 'paid').length;
        
        // Calculate total amount
        const totalAmount = violationsData.reduce((sum, violation) => {
            return sum + (parseFloat(violation.violation_amount) || 0);
        }, 0);
        
        totalViolationsElement.textContent = total.toLocaleString('ar-SA');
        unpaidViolationsElement.textContent = unpaid.toLocaleString('ar-SA');
        paidViolationsElement.textContent = paid.toLocaleString('ar-SA');
        totalAmountElement.textContent = formatCurrency(totalAmount);
    }
    
    // Apply filters to data
    function applyFilters() {
        let filtered = [...violationsData];
        
        // Filter by vehicle code
        if (filterVehicleCode.value.trim()) {
            const searchTerm = filterVehicleCode.value.trim().toLowerCase();
            filtered = filtered.filter(v => 
                v.vehicle_code.toLowerCase().includes(searchTerm)
            );
        }
        
        // Filter by status
        if (filterStatus.value) {
            filtered = filtered.filter(v => v.violation_status === filterStatus.value);
        }
        
        // Filter by start date
        if (filterStartDate.value) {
            const startDate = new Date(filterStartDate.value);
            filtered = filtered.filter(v => {
                const violationDate = new Date(v.violation_datetime);
                return violationDate >= startDate;
            });
        }
        
        // Filter by end date
        if (filterEndDate.value) {
            const endDate = new Date(filterEndDate.value);
            endDate.setHours(23, 59, 59, 999); // End of day
            filtered = filtered.filter(v => {
                const violationDate = new Date(v.violation_datetime);
                return violationDate <= endDate;
            });
        }
        
        // Filter by driver name
        if (filterDriver.value.trim()) {
            const searchTerm = filterDriver.value.trim().toLowerCase();
            filtered = filtered.filter(v => 
                v.driver_name && v.driver_name.toLowerCase().includes(searchTerm)
            );
        }
        
        // Filter by issuer
        if (filterIssuer.value.trim()) {
            const searchTerm = filterIssuer.value.trim().toLowerCase();
            filtered = filtered.filter(v => 
                (v.issued_by_name && v.issued_by_name.toLowerCase().includes(searchTerm)) ||
                (v.issued_by_emp_id && v.issued_by_emp_id.toLowerCase().includes(searchTerm))
            );
        }
        
        filteredData = filtered;
        currentPage = 1;
        updatePagination();
        populateTable();
        updateTableStatistics();
    }
    
    // Reset all filters
    function resetFilters() {
        filterVehicleCode.value = '';
        filterStatus.value = '';
        filterStartDate.value = '';
        filterEndDate.value = '';
        filterDriver.value = '';
        filterIssuer.value = '';
        
        applyFilters();
    }
    
    // Populate table with filtered data
    function populateTable() {
        if (!violationsTableBody) {
            console.error('Table body not found');
            return;
        }
        
        // Clear existing rows
        violationsTableBody.innerHTML = '';
        
        if (filteredData.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="10" style="text-align: center; padding: 40px;">
                    <i class="fas fa-search" style="font-size: 48px; color: #95a5a6; margin-bottom: 16px; display: block;"></i>
                    <h4 style="color: #7f8c8d; margin-bottom: 8px;">لا توجد مخالفات</h4>
                    <p style="color: #95a5a6;">جرب تغيير معايير البحث أو أضف مخالفات جديدة</p>
                </td>
            `;
            violationsTableBody.appendChild(emptyRow);
            return;
        }
        
        // Calculate start and end indices for current page
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, filteredData.length);
        const currentData = filteredData.slice(startIndex, endIndex);
        
        // Add data rows
        currentData.forEach(violation => {
            const row = document.createElement('tr');
            
            // Format dates
            const violationDate = new Date(violation.violation_datetime);
            const violationDateFormatted = formatArabicDate(violationDate);
            
            const paymentDate = violation.payment_datetime ? 
                formatArabicDate(new Date(violation.payment_datetime)) : '-';
            
            // Status badge
            const statusClass = violation.violation_status === 'paid' ? 'status-paid' : 'status-unpaid';
            const statusText = violation.violation_status === 'paid' ? 'مدفوعة' : 'غير مدفوعة';
            
            // Amount formatted
            const amountFormatted = formatCurrency(violation.violation_amount);
            
            row.innerHTML = `
                <td>${violation.id}</td>
                <td><strong>${violation.vehicle_code}</strong></td>
                <td>${violation.driver_name || '-'}</td>
                <td>${violationDateFormatted}</td>
                <td style="font-weight: bold; color: #e74c3c;">${amountFormatted}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>${violation.issued_by_name || violation.issued_by_emp_id}</td>
                <td>${paymentDate}</td>
                <td>${violation.notes ? (violation.notes.length > 30 ? violation.notes.substring(0, 30) + '...' : violation.notes) : '-'}</td>
                <td>
                    <div class="actions-cell">
                        <button class="action-btn view" title="عرض" onclick="viewViolation(${violation.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" title="تعديل" onclick="editViolation(${violation.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" title="حذف" onclick="deleteViolation(${violation.id})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        ${violation.violation_status === 'unpaid' ? `
                        <button class="action-btn pay" title="تحديد كمُدفوع" onclick="markAsPaid(${violation.id})">
                            <i class="fas fa-check-circle"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            `;
            
            violationsTableBody.appendChild(row);
        });
    }
    
    // Update pagination
    function updatePagination() {
        if (!pagination) return;
        
        pagination.innerHTML = '';
        
        totalPages = Math.ceil(filteredData.length / itemsPerPage);
        
        if (totalPages <= 1) {
            pagination.style.display = 'none';
            return;
        }
        
        pagination.style.display = 'flex';
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `
            <a class="page-link" href="#" aria-label="السابق" onclick="changePage(${currentPage - 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        `;
        pagination.appendChild(prevLi);
        
        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        // First page
        if (startPage > 1) {
            const firstLi = document.createElement('li');
            firstLi.className = 'page-item';
            firstLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(1)">1</a>`;
            pagination.appendChild(firstLi);
            
            if (startPage > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = `<span class="page-link">...</span>`;
                pagination.appendChild(ellipsisLi);
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
            pagination.appendChild(pageLi);
        }
        
        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = `<span class="page-link">...</span>`;
                pagination.appendChild(ellipsisLi);
            }
            
            const lastLi = document.createElement('li');
            lastLi.className = 'page-item';
            lastLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${totalPages})">${totalPages}</a>`;
            pagination.appendChild(lastLi);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `
            <a class="page-link" href="#" aria-label="التالي" onclick="changePage(${currentPage + 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        `;
        pagination.appendChild(nextLi);
    }
    
    // Change page (global function)
    window.changePage = function(page) {
        if (page < 1 || page > totalPages || page === currentPage) return;
        currentPage = page;
        populateTable();
        updatePagination();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    // Update table statistics
    function updateTableStatistics() {
        const totalAmount = filteredData.reduce((sum, violation) => {
            return sum + (parseFloat(violation.violation_amount) || 0);
        }, 0);
        
        tableTotalAmountElement.textContent = formatCurrency(totalAmount);
    }
    
    // Export to Excel
    function exportToExcel() {
        if (filteredData.length === 0) {
            showMessage('info', 'لا توجد بيانات', 'لا توجد بيانات للتصدير.');
            return;
        }
        
        // Create CSV content
        let csvContent = "data:text/csv;charset=utf-8,\ufeff"; // BOM for Arabic
        
        // Add headers
        const headers = [
            'رقم المخالفة',
            'رقم المركبة',
            'اسم السائق',
            'تاريخ المخالفة',
            'مبلغ المخالفة',
            'حالة المخالفة',
            'صادرت بواسطة',
            'تاريخ الدفع',
            'ملاحظات'
        ];
        csvContent += headers.join(',') + '\n';
        
        // Add data rows
        filteredData.forEach(violation => {
            const row = [
                violation.id,
                `"${violation.vehicle_code}"`,
                `"${violation.driver_name || ''}"`,
                `"${formatArabicDate(new Date(violation.violation_datetime))}"`,
                violation.violation_amount,
                violation.violation_status === 'paid' ? 'مدفوعة' : 'غير مدفوعة',
                `"${violation.issued_by_name || violation.issued_by_emp_id}"`,
                violation.payment_datetime ? `"${formatArabicDate(new Date(violation.payment_datetime))}"` : '',
                `"${violation.notes || ''}"`
            ];
            csvContent += row.join(',') + '\n';
        });
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `مخالفات_المركبات_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showMessage('success', 'تم التصدير', 'تم تصدير البيانات بنجاح إلى ملف CSV.');
    }
    
    // Print report
    function printReport() {
        if (filteredData.length === 0) {
            showMessage('info', 'لا توجد بيانات', 'لا توجد بيانات للطباعة.');
            return;
        }
        
        const printContent = document.querySelector('.table-container').cloneNode(true);
        
        // Remove action buttons for print
        const actionCells = printContent.querySelectorAll('td:nth-child(10), th:nth-child(10)');
        actionCells.forEach(cell => cell.remove());
        
        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html dir="rtl" lang="ar">
            <head>
                <title>تقرير مخالفات المركبات - ${new Date().toLocaleDateString('ar-SA')}</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap');
                    
                    body {
                        font-family: 'Cairo', sans-serif;
                        padding: 20px;
                        color: #333;
                    }
                    
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #3498db;
                        padding-bottom: 20px;
                    }
                    
                    .header h1 {
                        color: #2c3e50;
                        margin: 0 0 10px 0;
                    }
                    
                    .header .date {
                        color: #7f8c8d;
                        font-size: 14px;
                    }
                    
                    .stats {
                        display: grid;
                        grid-template-columns: repeat(4, 1fr);
                        gap: 15px;
                        margin-bottom: 30px;
                    }
                    
                    .stat-card {
                        background: #f8f9fa;
                        border-radius: 8px;
                        padding: 15px;
                        text-align: center;
                        border-left: 4px solid #3498db;
                    }
                    
                    .stat-card h3 {
                        margin: 0 0 5px 0;
                        font-size: 24px;
                        color: #2c3e50;
                    }
                    
                    .stat-card p {
                        margin: 0;
                        color: #7f8c8d;
                        font-size: 14px;
                    }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    
                    th {
                        background-color: #2c3e50;
                        color: white;
                        padding: 12px;
                        text-align: right;
                        font-weight: 600;
                        border: 1px solid #ddd;
                    }
                    
                    td {
                        padding: 10px;
                        border: 1px solid #ddd;
                        text-align: right;
                    }
                    
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    
                    .status-badge {
                        padding: 4px 12px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                        display: inline-block;
                    }
                    
                    .status-unpaid {
                        background-color: #ffebee;
                        color: #e74c3c;
                    }
                    
                    .status-paid {
                        background-color: #e8f5e9;
                        color: #27ae60;
                    }
                    
                    .footer {
                        margin-top: 40px;
                        text-align: center;
                        color: #7f8c8d;
                        font-size: 12px;
                        border-top: 1px solid #ddd;
                        padding-top: 20px;
                    }
                    
                    @media print {
                        body {
                            padding: 0;
                            margin: 0;
                        }
                        
                        .no-print {
                            display: none !important;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>تقرير مخالفات المركبات</h1>
                    <div class="date">تاريخ التقرير: ${new Date().toLocaleDateString('ar-SA')}</div>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3>${filteredData.length}</h3>
                        <p>إجمالي المخالفات</p>
                    </div>
                    <div class="stat-card">
                        <h3>${filteredData.filter(v => v.violation_status === 'unpaid').length}</h3>
                        <p>غير مدفوعة</p>
                    </div>
                    <div class="stat-card">
                        <h3>${filteredData.filter(v => v.violation_status === 'paid').length}</h3>
                        <p>مدفوعة</p>
                    </div>
                    <div class="stat-card">
                        <h3>${formatCurrency(filteredData.reduce((sum, v) => sum + (parseFloat(v.violation_amount) || 0), 0))}</h3>
                        <p>إجمالي المبالغ</p>
                    </div>
                </div>
                
                ${printContent.innerHTML}
                
                <div class="footer">
                    <p>© 2024 نظام إدارة المركبات - بلدية الشارقة</p>
                    <p>تم إنشاء هذا التقرير تلقائياً بواسطة النظام</p>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 1000);
                    };
                </script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }
    
    // View violation details
    window.viewViolation = function(violationId) {
        showMessage('info', 'عرض التفاصيل', `سيتم عرض تفاصيل المخالفة رقم ${violationId} في نافذة منبثقة.`);
        // TODO: Implement actual view modal
    };
    
    // Edit violation
    window.editViolation = function(violationId) {
        if (confirm('هل تريد تعديل هذه المخالفة؟')) {
            showMessage('info', 'تعديل المخالفة', `سيتم فتح نموذج تعديل المخالفة رقم ${violationId}.`);
            // TODO: Implement actual edit functionality
        }
    };
    
    // Delete violation
    window.deleteViolation = function(violationId) {
        if (confirm('هل أنت متأكد من حذف هذه المخالفة؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            showMessage('info', 'حذف المخالفة', `سيتم حذف المخالفة رقم ${violationId}.`);
            // TODO: Implement actual delete functionality
        }
    };
    
    // Mark as paid
    window.markAsPaid = function(violationId) {
        if (confirm('هل تريد تحديد هذه المخالفة كمُدفوعة؟')) {
            showMessage('info', 'تحديد كمدفوعة', `سيتم تحديد المخالفة رقم ${violationId} كمُدفوعة.`);
            // TODO: Implement actual mark as paid functionality
        }
    };
    
    // Update server time
    function updateServerTime() {
        if (!serverTimeElement) return;
        
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        
        const formattedTime = now.toLocaleDateString(
            currentLanguage === 'ar' ? 'ar-SA' : 'en-US', 
            options
        );
        
        serverTimeElement.textContent = `${translations.server_time || 'وقت الخادم'}: ${formattedTime}`;
    }
    
    // Show message modal
    function showMessage(type, title, message) {
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        
        if (!modalTitle || !modalMessage) {
            alert(message);
            return;
        }
        
        // Set message based on type
        const colors = {
            error: '#e74c3c',
            success: '#27ae60',
            info: '#3498db',
            warning: '#f39c12'
        };
        
        modalTitle.style.color = colors[type] || colors.info;
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        messageModal.style.display = 'flex';
    }
    
    // Format currency in Arabic
    function formatCurrency(amount) {
        const num = parseFloat(amount) || 0;
        return num.toLocaleString('ar-SA', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' درهم';
    }
    
    // Format date in Arabic
    function formatArabicDate(date) {
        if (!date) return '-';
        
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        return date.toLocaleDateString('ar-SA', options);
    }
    
    // Show loading overlay
    function showLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
    }
    
    // Hide loading overlay
    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }
    
    // Global helper functions
    window.formatCurrency = formatCurrency;
    window.formatArabicDate = formatArabicDate;
    
    // Initialize global variables
    window.violationsData = violationsData;
    window.filteredData = filteredData;
});