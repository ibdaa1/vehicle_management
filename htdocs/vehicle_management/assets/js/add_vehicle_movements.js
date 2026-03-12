class VehicleMovementForm {
    constructor() {
        this.API_SESSION = '/vehicle_management/api/users/session_check.php';
        this.API_TIME = '/vehicle_management/api/helper/get_time_uae.php';
        this.API_UPLOAD = '/vehicle_management/api/vehicle/upload.php';
        this.API_SUBMIT = '/vehicle_management/api/vehicle/add_vehicle_movements.php';
        this.API_EMPLOYEES = '/vehicle_management/api/users/search_employees.php';
        this.API_MOVEMENT = '/vehicle_management/api/vehicle/get_movement.php';
        this.API_VEHICLE_MOVEMENTS = '/vehicle_management/api/vehicle/get_vehicle_movements_by_vehicle.php';
        this.API_MOVEMENT_PHOTOS = '/vehicle_management/api/vehicle/get_movement_photos.php';
      
        this.init();
    }
    async init() {
        this.form = document.getElementById('vehicle-movement-form');
        this.initializeElements();
        this.setupEventListeners();
        await this.loadInitialData();
        await this.checkUrlParams();
    }
    initializeElements() {
        // Form elements
        this.photosInput = document.getElementById('photos');
        this.photosPreview = document.getElementById('photos-preview');
        this.getLocationBtn = document.getElementById('get-location-btn');
        this.openMapBtn = document.getElementById('open-map-btn');
        this.clearLocationBtn = document.getElementById('clear-location-btn');
        this.latitudeInput = document.getElementById('latitude');
        this.longitudeInput = document.getElementById('longitude');
        this.locationStatus = document.getElementById('location-status');
        this.cancelBtn = document.getElementById('cancel-btn');
        this.saveDraftBtn = document.getElementById('save-draft');
        this.closeModalBtn = document.getElementById('close-modal');
        this.submitBtn = document.querySelector('.primary-btn');
      
        // User permission elements
        this.performedByGroup = document.getElementById('performed-by-group');
        this.adminPerformedByGroup = document.getElementById('admin-performed-by-group');
        this.performedByInput = document.getElementById('performed_by');
        this.adminPerformedByInput = document.getElementById('admin_performed_by');
        this.userRoleInput = document.getElementById('user_role');
      
        // Search modal
        this.searchModal = document.getElementById('employee-search-modal');
        this.employeeSearchBtn = document.getElementById('employee-search');
        this.closeSearchBtn = document.querySelector('.close-search');
        this.employeeSearchInput = document.getElementById('employee-search-input');
        this.searchEmployeeBtn = document.getElementById('search-employee-btn');
        this.employeeResults = document.getElementById('employee-results');
      
        // Navigation elements (for vehicle movements list)
        this.navigationContainer = document.getElementById('navigation-container');
        this.prevMovementBtn = document.getElementById('prev-movement');
        this.nextMovementBtn = document.getElementById('next-movement');
        this.movementCounter = document.getElementById('movement-counter');
        this.vehicleMovementsList = document.getElementById('vehicle-movements-list');
      
        // Drop zone
        this.dropArea = document.getElementById('drop-area');
      
        // Notification container
        this.notificationContainer = document.getElementById('notification-container');
        if (!this.notificationContainer) {
            this.notificationContainer = document.createElement('div');
            this.notificationContainer.id = 'notification-container';
            this.notificationContainer.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
            document.body.appendChild(this.notificationContainer);
        }
      
        // State
        this.uploadedPhotos = [];
        this.storedPhotos = [];
        this.currentLocation = null;
        this.isGettingLocation = false;
        this.userRoleId = null;
        this.isAdmin = false;
        this.currentMovementId = null;
        this.currentVehicleCode = null;
        this.translations = {};
        this.userLanguage = 'ar';
        this.vehicleMovements = [];
        this.currentMovementIndex = -1;
        this.photoMap = new Map(); // New: Map for handling photos with unique IDs
        // New: Camera button
        this.cameraBtn = null; // We'll create it
    }
    setupEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
      
        // Location buttons
        this.getLocationBtn.addEventListener('click', () => this.getCurrentLocation());
        this.openMapBtn.addEventListener('click', () => this.openGoogleMaps());
        this.clearLocationBtn.addEventListener('click', () => this.clearLocation());
      
        // Photos
        this.photosInput.addEventListener('change', () => this.handlePhotos());
        this.setupDragAndDrop();
      
        // Buttons
        this.cancelBtn.addEventListener('click', () => this.cancelForm());
        this.saveDraftBtn.addEventListener('click', () => this.saveAsDraft());
        this.closeModalBtn.addEventListener('click', () => this.closeModal());
      
        // Navigation buttons
        if (this.prevMovementBtn) {
            this.prevMovementBtn.addEventListener('click', () => this.navigateToPreviousMovement());
        }
        if (this.nextMovementBtn) {
            this.nextMovementBtn.addEventListener('click', () => this.navigateToNextMovement());
        }
      
        // Admin features
        if (this.employeeSearchBtn) {
            this.employeeSearchBtn.addEventListener('click', () => this.openSearchModal());
        }
        if (this.closeSearchBtn) {
            this.closeSearchBtn.addEventListener('click', () => this.closeSearchModal());
        }
        if (this.searchEmployeeBtn) {
            this.searchEmployeeBtn.addEventListener('click', () => this.searchEmployees());
        }
        if (this.employeeSearchInput) {
            this.employeeSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.searchEmployees();
            });
        }
      
        // Close modal on overlay click
        if (this.searchModal) {
            this.searchModal.addEventListener('click', (e) => {
                if (e.target === this.searchModal) this.closeSearchModal();
            });
        }
        // New: Add camera button dynamically
        this.addCameraButton();
    }
    addCameraButton() {
    // تأكد من وجود drop-area
    if (!this.dropArea) return;

    // إزالة أي زر سابق لتجنب التكرار
    const existingBtn = this.dropArea.querySelector('.camera-btn');
    if (existingBtn) existingBtn.remove();

    this.cameraBtn = document.createElement('button');
    this.cameraBtn.type = 'button';
    this.cameraBtn.className = 'camera-btn'; // فقط class واضح
    this.cameraBtn.innerHTML = `
        <i class="fas fa-camera"></i>
        <span data-lang-key="camera_button">التقاط صورة</span>
    `;

    this.cameraBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation(); // مهم: منع تفعيل السحب
        this.openCamera();
    });

    // إضافة الزر في الأعلى داخل drop-area
    this.dropArea.prepend(this.cameraBtn); // أو append إذا أردت في الأسفل
}
    openCamera() {
    const cameraInput = document.createElement('input');
    cameraInput.type = 'file';
    cameraInput.accept = 'image/*';
    cameraInput.capture = 'environment'; // كاميرا خلفية

    // إخفاء بطريقة آمنة للموبايل
    cameraInput.style.position = 'fixed';
    cameraInput.style.left = '-9999px';
    cameraInput.style.opacity = '0';

    document.body.appendChild(cameraInput);

    cameraInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length > 0) {
            this.processPhotos(e.target.files);
        }
        document.body.removeChild(cameraInput);
    });

    // تأخير بسيط لضمان الإلحاق
    setTimeout(() => cameraInput.click(), 100);
}
    async loadInitialData() {
        try {
            // Load session data
            const sessionResponse = await fetch(this.API_SESSION, {
                credentials: 'include'
            });
          
            if (!sessionResponse.ok) {
                throw new Error(this.getTranslation('errors.session_failed'));
            }
          
            const session = await sessionResponse.json();
          
            if (!session.success || !session.user?.emp_id) {
                throw new Error(session.message || this.getTranslation('errors.not_logged_in'));
            }
            const user = session.user;
            this.userLanguage = user.preferred_language || 'ar';
          
            // Set page direction and language
            this.setPageDirection();
          
            // Load translations first
            await this.loadLanguage();
          
            // Update UI based on user role
            this.userRoleId = user.role_id;
            this.isAdmin = this.userRoleId === 1 || this.userRoleId === 2;
          
            // Update form with user data
            this.performedByInput.value = user.emp_id;
            document.getElementById('created_by').value = user.emp_id;
            document.getElementById('updated_by').value = user.emp_id;
            document.getElementById('user_language').value = this.userLanguage;
            this.userRoleInput.value = this.userRoleId;
          
            // Show/hide admin features based on role_id
            this.toggleAdminFeatures();
          
            // Load current time
            const timeResponse = await fetch(this.API_TIME);
            if (!timeResponse.ok) throw new Error('Failed to get time');
          
            const time = await timeResponse.json();
            if (time.success && time.datetime) {
                document.getElementById('movement_datetime').value = time.datetime;
            }
          
        } catch (error) {
            console.error('Load error:', error);
            this.showNotification('error', `${this.getTranslation('errors.load_failed')}: ${error.message}`);
        }
    }
    setPageDirection() {
        document.documentElement.dir = this.userLanguage === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.lang = this.userLanguage;
      
        if (this.form) {
            this.form.style.direction = this.userLanguage === 'ar' ? 'rtl' : 'ltr';
            this.form.style.textAlign = this.userLanguage === 'ar' ? 'right' : 'left';
        }
      
        const modal = document.getElementById('movement-modal');
        if (modal) {
            modal.style.direction = this.userLanguage === 'ar' ? 'rtl' : 'ltr';
        }
    }
    async loadLanguage() {
        try {
            const response = await fetch(`/vehicle_management/languages/${this.userLanguage}_add_vehicle_movements.json`);
            this.translations = await response.json();
            this.applyTranslations();
        } catch (error) {
            console.error('Translation load error:', error);
            if (this.userLanguage !== 'ar') {
                const arabicResponse = await fetch('/vehicle_management/languages/ar_add_vehicle_movements.json');
                this.translations = await arabicResponse.json();
                this.applyTranslations();
            }
        }
    }
    applyTranslations() {
        document.title = this.getTranslation('page_title');
      
        document.querySelectorAll('[data-lang-key]').forEach(element => {
            const key = element.getAttribute('data-lang-key');
            const translation = this.getTranslation(key);
          
            if (translation && element.tagName !== 'INPUT' && element.tagName !== 'TEXTAREA' && element.tagName !== 'SELECT') {
                element.textContent = translation;
            }
        });
      
        document.querySelectorAll('[data-lang-key-placeholder]').forEach(element => {
            const key = element.getAttribute('data-lang-key-placeholder');
            const translation = this.getTranslation(key);
          
            if (translation && (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA')) {
                element.placeholder = translation;
            }
        });
      
        const operationTypeSelect = document.getElementById('operation_type');
        if (operationTypeSelect) {
            Array.from(operationTypeSelect.options).forEach(option => {
                const key = option.getAttribute('data-lang-key');
                if (key) {
                    const translation = this.getTranslation(key);
                    if (translation) {
                        option.textContent = translation;
                    }
                }
            });
        }
      
        this.clearLocation();
    }
    getTranslation(key) {
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
    async checkUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const movementId = urlParams.get('id');
        const vehicleCode = urlParams.get('vehicle');
      
        if (movementId) {
            this.currentMovementId = movementId;
            document.getElementById('movement_id').value = movementId;
            await this.loadMovementData(movementId);
        } else if (vehicleCode) {
            this.currentVehicleCode = vehicleCode;
            await this.loadVehicleMovements(vehicleCode);
        }
    }
    async loadMovementData(id) {
        try {
            const response = await fetch(`${this.API_MOVEMENT}?id=${id}&lang=${this.userLanguage}`);
            const data = await response.json();
          
            if (data.success && data.movement) {
                this.populateForm(data.movement);
                await this.loadMovementPhotos(id);
                this.showNotification('success', this.getTranslation('errors.movement_loaded'));
            }
        } catch (error) {
            console.error('Error loading movement:', error);
            this.showNotification('error', this.getTranslation('errors.load_movement_failed'));
        }
    }
    async loadMovementPhotos(movementId) {
        try {
            const response = await fetch(`${this.API_MOVEMENT_PHOTOS}?movement_id=${movementId}`);
            const data = await response.json();
          
            if (data.success && data.photos && data.photos.length > 0) {
                this.storedPhotos = data.photos;
                this.displayStoredPhotos();
                this.updatePhotoCountInList();
            }
        } catch (error) {
            console.error('Error loading movement photos:', error);
        }
    }
    displayStoredPhotos() {
        if (this.storedPhotos.length === 0) return;
      
        const existingSeparator = document.querySelector('.stored-photos-separator');
        if (existingSeparator) {
            existingSeparator.remove();
        }
      
        const separator = document.createElement('div');
        separator.className = 'stored-photos-separator';
        separator.innerHTML = `<h6><i class="fas fa-images"></i> ${this.userLanguage === 'ar' ? 'الصور المخزنة' : 'Stored Photos'} (${this.storedPhotos.length})</h6>`;
        this.photosPreview.appendChild(separator);
      
        this.storedPhotos.forEach(photo => {
            const photoItem = document.createElement('div');
            photoItem.className = 'photo-item fade-in stored-photo';
            photoItem.setAttribute('data-photo-url', photo.url);
            photoItem.setAttribute('data-filename', photo.filename);
          
            let dateDisplay = '';
            if (photo.taken_at) {
                const date = new Date(photo.taken_at);
                dateDisplay = date.toLocaleDateString(this.userLanguage === 'ar' ? 'ar-SA' : 'en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
          
            photoItem.innerHTML = `
                <img src="${photo.url}"
                     alt="${this.userLanguage === 'ar' ? 'صورة الحركة' : 'Movement Photo'}"
                     loading="lazy"
                     onerror="this.src='/vehicle_management/assets/images/no-image.png'">
                <div class="photo-info">
                    <small>${this.userLanguage === 'ar' ? 'مخزنة' : 'Stored'}</small>
                    <small title="${photo.filename}">${photo.filename.length > 20 ? photo.filename.substring(0, 20) + '...' : photo.filename}</small>
                </div>
                <button type="button" class="view-photo-btn" title="${this.userLanguage === 'ar' ? 'عرض الصورة' : 'View Photo'}">
                    <i class="fas fa-expand"></i>
                </button>
                <button type="button" class="remove-stored-photo" data-filename="${photo.filename}">
                    <i class="fas fa-times"></i>
                </button>
            `;
          
            this.photosPreview.appendChild(photoItem);
          
            const viewBtn = photoItem.querySelector('.view-photo-btn');
            viewBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.viewPhotoInModal(photo.url, photo.filename, photo.taken_at, photo.taken_by);
            });
          
            const removeBtn = photoItem.querySelector('.remove-stored-photo');
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.removeStoredPhoto(photo.filename, photoItem);
            });
        });
    }
    updatePhotoCountInList() {
        if (this.currentMovementIndex >= 0 && this.vehicleMovements[this.currentMovementIndex]) {
            const movement = this.vehicleMovements[this.currentMovementIndex];
            movement.photos_count = this.storedPhotos.length;
            this.updateMovementsList();
        }
    }
    removeStoredPhoto(filename, photoElement) {
        const confirmMessage = this.userLanguage === 'ar'
            ? 'هل تريد إزالة هذه الصورة من القائمة؟ (لن يتم حذفها من السيرفر)'
            : 'Do you want to remove this photo from the list? (It will not be deleted from the server)';
      
        if (!confirm(confirmMessage)) return;
      
        let deletedPhotosInput = document.getElementById('deleted_filenames');
        if (!deletedPhotosInput) {
            deletedPhotosInput = document.createElement('input');
            deletedPhotosInput.type = 'hidden';
            deletedPhotosInput.id = 'deleted_filenames';
            deletedPhotosInput.name = 'deleted_filenames';
            this.form.appendChild(deletedPhotosInput);
        }
      
        let deletedFiles = deletedPhotosInput.value ? deletedPhotosInput.value.split(',') : [];
        if (!deletedFiles.includes(filename)) {
            deletedFiles.push(filename);
            deletedPhotosInput.value = deletedFiles.join(',');
        }
      
        photoElement.remove();
        this.storedPhotos = this.storedPhotos.filter(p => p.filename !== filename);
      
        const separator = document.querySelector('.stored-photos-separator');
        if (separator) {
            separator.innerHTML = `<h6><i class="fas fa-images"></i> ${this.userLanguage === 'ar' ? 'الصور المخزنة' : 'Stored Photos'} (${this.storedPhotos.length})</h6>`;
        }
      
        this.showNotification('info', this.userLanguage === 'ar'
            ? 'تم إزالة الصورة من القائمة'
            : 'Photo removed from list');
    }
    viewPhotoInModal(photoUrl, filename, takenAt = null, takenBy = null) {
        const modal = document.createElement('div');
        modal.className = 'photo-view-modal fade-in';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            animation: fadeIn 0.3s ease;
        `;
      
        let infoHtml = '';
        if (takenAt) {
            const date = new Date(takenAt);
            const formattedDate = date.toLocaleDateString(this.userLanguage === 'ar' ? 'ar-SA' : 'en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            infoHtml += `<p><i class="far fa-calendar-alt"></i> ${formattedDate}</p>`;
        }
        if (takenBy) {
            infoHtml += `<p><i class="fas fa-user"></i> ${takenBy}</p>`;
        }
      
        modal.innerHTML = `
            <div style="position: absolute; top: 20px; right: 20px;">
                <button class="btn btn-light btn-sm close-photo-view" style="border-radius: 50%; width: 40px; height: 40px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="max-width: 90%; max-height: 70%; margin-bottom: 20px;">
                <img src="${photoUrl}"
                     alt="${filename}"
                     style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px;"
                     onerror="this.src='/vehicle_management/assets/images/no-image.png'">
            </div>
            <div style="color: white; text-align: center; padding: 20px; max-width: 80%;">
                <h5 style="margin-bottom: 10px;">${filename}</h5>
                ${infoHtml}
                <div style="margin-top: 20px;">
                    <a href="${photoUrl}"
                       target="_blank"
                       class="btn btn-primary btn-sm mr-2">
                        <i class="fas fa-external-link-alt"></i>
                        ${this.userLanguage === 'ar' ? 'فتح في نافذة جديدة' : 'Open in new window'}
                    </a>
                    <a href="${photoUrl}"
                       download="${filename}"
                       class="btn btn-success btn-sm">
                        <i class="fas fa-download"></i>
                        ${this.userLanguage === 'ar' ? 'تحميل' : 'Download'}
                    </a>
                </div>
            </div>
        `;
      
        document.body.appendChild(modal);
      
        const closeModal = () => {
            modal.style.opacity = '0';
            setTimeout(() => modal.remove(), 300);
        };
      
        const closeBtn = modal.querySelector('.close-photo-view');
        closeBtn.addEventListener('click', closeModal);
      
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
      
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    }
    async loadVehicleMovements(vehicleCode) {
        try {
            this.showLoading();
          
            const response = await fetch(`${this.API_VEHICLE_MOVEMENTS}?vehicle_code=${vehicleCode}&lang=${this.userLanguage}`);
            const data = await response.json();
          
            if (data.success && data.movements && data.movements.length > 0) {
                this.vehicleMovements = data.movements;
                this.currentMovementIndex = this.vehicleMovements.length - 1;
              
                await this.loadMovementFromList(this.currentMovementIndex);
              
                this.showNavigationControls();
              
                document.getElementById('vehicle_code').value = vehicleCode;
                document.getElementById('vehicle_code').readOnly = true;
              
                this.showNotification('success',
                    this.translateMessage('vehicle_movements_loaded', {
                        count: data.movements.length,
                        vehicle: vehicleCode
                    })
                );
            } else {
                document.getElementById('vehicle_code').value = vehicleCode;
                document.getElementById('vehicle_code').readOnly = true;
                this.showNotification('info', this.translateMessage('no_movements_found', { vehicle: vehicleCode }));
            }
        } catch (error) {
            console.error('Error loading vehicle movements:', error);
            this.showNotification('error', this.getTranslation('errors.load_vehicle_movements_failed'));
        } finally {
            this.hideLoading();
        }
    }
    async loadMovementFromList(index) {
        if (index < 0 || index >= this.vehicleMovements.length) return;
      
        const movement = this.vehicleMovements[index];
        this.currentMovementId = movement.id;
        this.currentMovementIndex = index;
      
        document.getElementById('movement_id').value = movement.id;
        this.populateForm(movement);
      
        await this.loadMovementPhotos(movement.id);
      
        this.updateNavigationButtons();
        this.updateMovementsList();
    }
    populateForm(data) {
        if (this.photosPreview) {
            this.photosPreview.innerHTML = '';
            this.storedPhotos = [];
        }
      
        document.getElementById('vehicle_code').value = data.vehicle_code || '';
        document.getElementById('operation_type').value = data.operation_type || '';
        document.getElementById('performed_by').value = data.performed_by || '';
        document.getElementById('notes').value = data.notes || '';
        document.getElementById('latitude').value = data.latitude || '';
        document.getElementById('longitude').value = data.longitude || '';
      
        if (data.latitude && data.longitude) {
            this.currentLocation = {
                lat: parseFloat(data.latitude),
                lng: parseFloat(data.longitude)
            };
            this.updateMapButtonState();
        }
      
        if (data.movement_datetime) {
            document.getElementById('movement_datetime').value = data.movement_datetime;
        }
    }
    showNavigationControls() {
        if (this.navigationContainer) {
            this.navigationContainer.style.display = 'block';
        }
      
        this.updateNavigationButtons();
    }
    updateNavigationButtons() {
        if (!this.prevMovementBtn || !this.nextMovementBtn || !this.movementCounter) return;
      
        this.movementCounter.textContent = `${this.currentMovementIndex + 1} / ${this.vehicleMovements.length}`;
      
        this.prevMovementBtn.disabled = this.currentMovementIndex <= 0;
        this.nextMovementBtn.disabled = this.currentMovementIndex >= this.vehicleMovements.length - 1;
      
        const prevText = this.userLanguage === 'ar' ? 'السابق' : 'Previous';
        const nextText = this.userLanguage === 'ar' ? 'التالي' : 'Next';
      
        this.prevMovementBtn.innerHTML = `<i class="fas fa-chevron-${this.userLanguage === 'ar' ? 'right' : 'left'}"></i> ${prevText}`;
        this.nextMovementBtn.innerHTML = `${nextText} <i class="fas fa-chevron-${this.userLanguage === 'ar' ? 'left' : 'right'}"></i>`;
    }
    updateMovementsList() {
        if (!this.vehicleMovementsList || this.vehicleMovements.length === 0) return;
      
        this.vehicleMovementsList.innerHTML = '';
      
        this.vehicleMovements.forEach((movement, index) => {
            const item = document.createElement('li');
            item.className = `movement-item ${index === this.currentMovementIndex ? 'active' : ''}`;
          
            const date = new Date(movement.movement_datetime).toLocaleDateString(this.userLanguage === 'ar' ? 'ar-SA' : 'en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
          
            const typeText = movement.operation_type === 'pickup'
                ? (this.userLanguage === 'ar' ? 'استلام' : 'Pickup')
                : (movement.operation_type === 'return'
                    ? (this.userLanguage === 'ar' ? 'إرجاع' : 'Return')
                    : movement.operation_type || '');
          
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${date}</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            ${typeText} - ${movement.performed_by || ''}
                            ${movement.photos_count ? `<br><small><i class="fas fa-camera"></i> ${movement.photos_count} ${this.userLanguage === 'ar' ? 'صورة' : 'photos'}</small>` : ''}
                        </div>
                    </div>
                    <span class="badge ${index === this.currentMovementIndex ? 'badge-primary' : 'badge-secondary'}">
                        #${movement.id}
                    </span>
                </div>
            `;
          
            item.addEventListener('click', () => {
                this.loadMovementFromList(index);
            });
          
            this.vehicleMovementsList.appendChild(item);
        });
    }
    navigateToPreviousMovement() {
        if (this.currentMovementIndex > 0) {
            this.loadMovementFromList(this.currentMovementIndex - 1);
        }
    }
    navigateToNextMovement() {
        if (this.currentMovementIndex < this.vehicleMovements.length - 1) {
            this.loadMovementFromList(this.currentMovementIndex + 1);
        }
    }
    toggleAdminFeatures() {
        const hintElement = document.getElementById('performed-by-hint');
      
        if (this.isAdmin) {
            if (this.adminPerformedByGroup) {
                this.adminPerformedByGroup.style.display = 'block';
            }
          
            if (this.performedByInput) {
                this.performedByInput.readOnly = false;
                this.performedByInput.placeholder = this.userLanguage === 'ar'
                    ? 'رقم الموظف الحالي أو اختر موظفاً آخر'
                    : 'Current employee number or select another';
            }
          
            if (hintElement) {
                hintElement.textContent = this.getTranslation('performed_by_hint_admin');
            }
        } else {
            if (this.adminPerformedByGroup) {
                this.adminPerformedByGroup.style.display = 'none';
            }
          
            if (this.performedByInput) {
                this.performedByInput.readOnly = true;
                this.performedByInput.placeholder = this.getTranslation('performed_by_placeholder');
            }
          
            if (hintElement) {
                hintElement.textContent = this.getTranslation('performed_by_hint_employee');
            }
        }
    }
    async getCurrentLocation() {
        if (this.isGettingLocation) return;
      
        this.isGettingLocation = true;
        const originalContent = this.getLocationBtn.innerHTML;
        this.getLocationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        this.getLocationBtn.classList.add('loading');
      
        try {
            if (!navigator.geolocation) {
                throw new Error(this.getTranslation('errors.location_not_supported'));
            }
          
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            });
          
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
          
            this.latitudeInput.value = lat.toFixed(8);
            this.longitudeInput.value = lng.toFixed(8);
            this.currentLocation = { lat, lng };
          
            const accuracy = position.coords.accuracy.toFixed(1);
            this.locationStatus.innerHTML = `
                <i class="fas fa-check-circle" style="color: #28a745"></i>
                <span>${this.translateMessage('location_acquired', { accuracy })}</span>
            `;
          
            this.openMapBtn.disabled = false;
            this.showNotification('success', this.getTranslation('errors.location_success'));
          
        } catch (error) {
            console.error('Location error:', error);
            await this.getLocationByIP();
        } finally {
            this.isGettingLocation = false;
            this.getLocationBtn.innerHTML = originalContent;
            this.getLocationBtn.classList.remove('loading');
        }
    }
    async getLocationByIP() {
        try {
            const response = await fetch('https://ipapi.co/json/');
            const data = await response.json();
          
            if (data.latitude && data.longitude) {
                this.latitudeInput.value = parseFloat(data.latitude).toFixed(8);
                this.longitudeInput.value = parseFloat(data.longitude).toFixed(8);
                this.currentLocation = {
                    lat: parseFloat(data.latitude),
                    lng: parseFloat(data.longitude)
                };
              
                this.locationStatus.innerHTML = `
                    <i class="fas fa-info-circle" style="color: #ffc107"></i>
                    <span>${this.translateMessage('location_ip', {
                        city: data.city,
                        country: data.country_name
                    })}</span>
                `;
              
                this.openMapBtn.disabled = false;
                this.showNotification('warning', this.getTranslation('errors.location_warning'));
            }
        } catch (error) {
            console.error('IP location error:', error);
            this.showNotification('error', this.getTranslation('errors.location_failed'));
        }
    }
    openGoogleMaps() {
        if (!this.currentLocation) return;
      
        const { lat, lng } = this.currentLocation;
        const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}&z=17`;
        window.open(mapsUrl, '_blank');
    }
    clearLocation() {
        this.latitudeInput.value = '';
        this.longitudeInput.value = '';
        this.currentLocation = null;
        this.openMapBtn.disabled = true;
      
        this.locationStatus.innerHTML = `
            <i class="fas fa-info-circle"></i>
            <span>${this.getTranslation('location_instructions')}</span>
        `;
    }
    updateMapButtonState() {
        const hasCoords = this.latitudeInput.value && this.longitudeInput.value;
        this.openMapBtn.disabled = !hasCoords;
      
        if (hasCoords) {
            this.currentLocation = {
                lat: parseFloat(this.latitudeInput.value),
                lng: parseFloat(this.longitudeInput.value)
            };
        }
    }
    setupDragAndDrop() {
        if (!this.dropArea) return;
      
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.dropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            this.dropArea.addEventListener(eventName, () => {
                this.dropArea.style.borderColor = 'var(--accent-gold)';
                this.dropArea.style.background = 'rgba(212, 175, 55, 0.05)';
            });
        });
        ['dragleave', 'drop'].forEach(eventName => {
            this.dropArea.addEventListener(eventName, () => {
                this.dropArea.style.borderColor = 'var(--border-color)';
                this.dropArea.style.background = 'var(--white)';
            });
        });
        this.dropArea.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            this.handleDroppedFiles(files);
        });
    }
    async handlePhotos() {
        const files = this.photosInput.files;
        if (files.length === 0) return;
        await this.processPhotos(files);
    }
    async handleDroppedFiles(files) {
        const imageFiles = Array.from(files).filter(file =>
            file.type.startsWith('image/')
        );
      
        if (imageFiles.length === 0) {
            this.showNotification('error', this.getTranslation('errors.invalid_file_type'));
            return;
        }
      
        const limitedFiles = imageFiles.slice(0, 6);
        await this.processPhotos(limitedFiles);
      
        const dataTransfer = new DataTransfer();
        limitedFiles.forEach(file => dataTransfer.items.add(file));
        this.photosInput.files = dataTransfer.files;
    }
    
    
    
    
    
    
    
 async processPhotos(files) {
    // إيقاف رفع الصور للمستخدمين غير السوبر أدمن
    if (this.userRoleId !== 1) {
        this.showNotification('error', 'رفع الصور متوقف مؤقتاً من الإدارة!');
        return;
    }

    // الكود الأصلي للسوبر أدمن
    const newFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
    // الحد الأقصى 6 صور إجمالي
    const totalFiles = this.photoMap.size + newFiles.length;
    if (totalFiles > 6) {
        this.showNotification('error', this.getTranslation('errors.max_total_files'));
        return;
    }
    // إضافة الصور الجديدة إلى الخريطة مع معرفات فريدة
    const newPhotoIds = [];
    for (const file of newFiles) {
        const photoId = `photo-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;
        newPhotoIds.push(photoId);
        this.photoMap.set(photoId, file);
    }
    // إعادة بناء FileList من الخريطة بأكملها
    const allFiles = Array.from(this.photoMap.values());
    const dataTransfer = new DataTransfer();
    allFiles.forEach(file => dataTransfer.items.add(file));
    this.photosInput.files = dataTransfer.files;
    // عرض الصور الجديدة في المعاينة
    if (newFiles.length > 0) {
        const existingSeparator = document.querySelector('.new-photos-separator');
        if (this.storedPhotos.length > 0 && !existingSeparator) {
            const separator = document.createElement('div');
            separator.className = 'new-photos-separator';
            separator.innerHTML = `<h6><i class="fas fa-cloud-upload-alt"></i> ${this.userLanguage === 'ar' ? 'صور جديدة' : 'New Photos'}</h6>`;
            this.photosPreview.appendChild(separator);
        }
    }
    newFiles.forEach((file, j) => {
        const photoId = newPhotoIds[j];
        const reader = new FileReader();
      
        reader.onload = (e) => {
            const photoItem = document.createElement('div');
            photoItem.className = 'photo-item fade-in new-photo';
            photoItem.setAttribute('data-photo-id', photoId);
            photoItem.setAttribute('data-filename', file.name);
          
            photoItem.innerHTML = `
                <img src="${e.target.result}" alt="${this.userLanguage === 'ar' ? 'صورة جديدة' : 'New Photo'}">
                <div class="photo-info">
                    <small>${this.userLanguage === 'ar' ? 'جديد' : 'New'}</small>
                    <small title="${file.name}">${file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name}</small>
                </div>
                <button type="button" class="view-photo-btn" title="${this.userLanguage === 'ar' ? 'معاينة' : 'Preview'}">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="remove-photo">
                    <i class="fas fa-times"></i>
                </button>
            `;
          
            this.photosPreview.appendChild(photoItem);
          
            const viewBtn = photoItem.querySelector('.view-photo-btn');
            viewBtn.addEventListener('click', (ev) => {
                ev.preventDefault();
                this.viewPhotoInModal(e.target.result, file.name);
            });
          
            const removeBtn = photoItem.querySelector('.remove-photo');
            removeBtn.addEventListener('click', () => this.removePhoto(photoId, photoItem));
        };
      
        reader.readAsDataURL(file);
    });
}
    
    
    
    
    
    removePhoto(photoId, photoElement) {
        if (!this.photoMap.has(photoId)) return;
      
        photoElement.remove();
        this.photoMap.delete(photoId);
      
        const updatedFiles = Array.from(this.photoMap.values());
      
        const dataTransfer = new DataTransfer();
        updatedFiles.forEach(file => dataTransfer.items.add(file));
        this.photosInput.files = dataTransfer.files;
      
        const newPhotos = document.querySelectorAll('.new-photo');
        if (newPhotos.length === 0) {
            const separator = document.querySelector('.new-photos-separator');
            if (separator) {
                separator.remove();
            }
        }
    }
    openSearchModal() {
        if (!this.isAdmin) {
            this.showNotification('warning', this.userLanguage === 'ar'
                ? 'هذه الميزة متاحة للمسؤولين فقط'
                : 'This feature is available for admins only');
            return;
        }
      
        if (this.searchModal) {
            this.searchModal.style.display = 'flex';
            if (this.employeeSearchInput) {
                this.employeeSearchInput.focus();
            }
        }
    }
    closeSearchModal() {
        if (this.searchModal) {
            this.searchModal.style.display = 'none';
            if (this.employeeSearchInput) {
                this.employeeSearchInput.value = '';
            }
            if (this.employeeResults) {
                this.employeeResults.innerHTML = '';
            }
        }
    }
    async searchEmployees() {
        if (!this.isAdmin) {
            this.showNotification('warning', this.userLanguage === 'ar'
                ? 'غير مصرح لك بالبحث عن الموظفين'
                : 'Not authorized to search employees');
            return;
        }
      
        const query = this.employeeSearchInput?.value.trim();
      
        if (!query) {
            this.showNotification('warning', this.getTranslation('errors.search_query_empty'));
            return;
        }
      
        try {
            const response = await fetch(`${this.API_EMPLOYEES}?q=${encodeURIComponent(query)}&lang=${this.userLanguage}`);
            const data = await response.json();
          
            this.displayEmployeeResults(data.employees || []);
          
        } catch (error) {
            console.error('Search error:', error);
            this.showNotification('error', this.getTranslation('errors.search_employees_failed'));
        }
    }
    displayEmployeeResults(employees) {
        if (!this.employeeResults) return;
      
        this.employeeResults.innerHTML = '';
      
        if (employees.length === 0) {
            this.employeeResults.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-search mb-2"></i>
                    <p>${this.getTranslation('no_employees_found')}</p>
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
                        <strong>${employee.full_name || (this.userLanguage === 'ar' ? 'غير محدد' : 'Not specified')}</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            ${employee.emp_id || (this.userLanguage === 'ar' ? 'بدون رقم' : 'No ID')} •
                            ${employee.department || (this.userLanguage === 'ar' ? 'غير محدد' : 'Not specified')}
                        </div>
                    </div>
                    <i class="fas fa-check text-success" style="display: none;"></i>
                </div>
            `;
          
            employeeItem.addEventListener('click', (e) => {
                this.selectEmployee(employee, e);
            });
          
            this.employeeResults.appendChild(employeeItem);
        });
    }
    selectEmployee(employee, event) {
        if (!this.isAdmin) return;
      
        if (this.adminPerformedByInput) {
            this.adminPerformedByInput.value = employee.emp_id || '';
        }
      
        if (this.performedByInput) {
            this.performedByInput.value = employee.emp_id || '';
        }
      
        document.querySelectorAll('.employee-item').forEach(item => {
            item.classList.remove('active');
            const checkIcon = item.querySelector('.fa-check');
            if (checkIcon) {
                checkIcon.style.display = 'none';
            }
        });
      
        const targetElement = event.currentTarget;
        targetElement.classList.add('active');
        const checkIcon = targetElement.querySelector('.fa-check');
        if (checkIcon) {
            checkIcon.style.display = 'inline-block';
        }
      
        setTimeout(() => {
            this.closeSearchModal();
        }, 500);
      
        const message = this.userLanguage === 'ar'
            ? `تم اختيار الموظف: ${employee.full_name || 'غير معروف'}`
            : `Employee selected: ${employee.full_name || 'Unknown'}`;
        this.showNotification('success', message);
    }
    async handleSubmit(e) {
        e.preventDefault();
      
        if (!this.validateForm()) {
            return;
        }
      
        const originalContent = this.submitBtn.innerHTML;
        const loadingText = this.userLanguage === 'ar' ? 'جاري الحفظ...' : 'Saving...';
        this.submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${loadingText}`;
        this.submitBtn.disabled = true;
      
        try {
            const formData = new FormData(this.form);
          
            if (this.isAdmin && this.adminPerformedByInput && this.adminPerformedByInput.value) {
                formData.set('performed_by', this.adminPerformedByInput.value);
            } else {
                const currentEmpId = this.performedByInput.value;
                formData.set('performed_by', currentEmpId);
            }
          
            if (this.storedPhotos.length > 0) {
                const storedPhotoIds = this.storedPhotos.map(p => p.id).join(',');
                formData.set('stored_photo_ids', storedPhotoIds);
            }
          
            const photoFiles = this.photosInput.files;
            if (photoFiles.length > 0) {
                await this.uploadPhotos(formData, photoFiles);
            }
          
            const response = await this.submitForm(formData);
          
            if (response.success) {
                this.showSuccessNotification(response);
                setTimeout(() => {
                    this.resetForm();
                    this.loadCurrentTime();
                }, 2000);
            } else {
                throw new Error(response.message);
            }
          
        } catch (error) {
            console.error('Submit error:', error);
            this.showNotification('error', `${this.getTranslation('errors.save_failed')}: ${error.message}`);
        } finally {
            this.submitBtn.innerHTML = originalContent;
            this.submitBtn.disabled = false;
        }
    }
async uploadPhotos(formData, photoFiles) {
    const photoData = new FormData();
    Array.from(photoFiles).forEach(file => photoData.append('photos[]', file));
    
    const uploadRes = await fetch(this.API_UPLOAD, {
        method: 'POST',
        body: photoData
    });
    
    if (!uploadRes.ok) throw new Error(this.getTranslation('errors.upload_failed'));
    const upload = await uploadRes.json();
    
    if (!upload.success) throw new Error(upload.message);
    
    // إصلاح: إرسال urlObj.url بدلاً من الكائن كاملاً
upload.uploaded_files.forEach((urlObj, idx) => {
    formData.append(`photo_url_${idx}`, urlObj.url);  // يجب أن يكون urlObj.url وليس urlObj
});
}
    async submitForm(formData) {
        const submitRes = await fetch(this.API_SUBMIT, {
            method: 'POST',
            body: formData
        });
      
        return await submitRes.json();
    }
    validateForm() {
        const vehicleCode = document.getElementById('vehicle_code')?.value.trim();
        const operationType = document.getElementById('operation_type')?.value;
        const performedBy = document.getElementById('performed_by')?.value.trim();
      
        if (!vehicleCode) {
            this.showNotification('error', this.getTranslation('errors.vehicle_code_required'));
            return false;
        }
      
        if (!operationType) {
            this.showNotification('error', this.getTranslation('errors.operation_type_required'));
            return false;
        }
      
        if (!performedBy) {
            this.showNotification('error', this.getTranslation('errors.performed_by_required'));
            return false;
        }
      
        const newPhotosCount = this.photosInput.files.length;
        const storedPhotosCount = this.storedPhotos.length;
        const deletedFilesInput = document.getElementById('deleted_filenames');
        let deletedCount = 0;
        if (deletedFilesInput && deletedFilesInput.value) {
            deletedCount = deletedFilesInput.value.split(',').filter(f => f.trim()).length;
        }
        const totalPhotos = storedPhotosCount - deletedCount + newPhotosCount;
      
        if (totalPhotos > 6) {
            this.showNotification('error', this.getTranslation('errors.max_total_files'));
            return false;
        }
      
        return true;
    }
    showSuccessNotification(response) {
        let photosCount = 0;
        if (response.photos_inserted) {
            photosCount += response.photos_inserted;
        }
        if (this.storedPhotos.length > 0) {
            const deletedFilesInput = document.getElementById('deleted_filenames');
            let deletedCount = 0;
            if (deletedFilesInput && deletedFilesInput.value) {
                deletedCount = deletedFilesInput.value.split(',').filter(f => f.trim()).length;
            }
            const keptCount = this.storedPhotos.length - deletedCount;
            photosCount += keptCount;
        }
      
        let statusText = '';
        if (response.vehicle_status) {
            statusText = response.vehicle_status === 'operational'
                ? (this.userLanguage === 'ar' ? 'حالة المركبة: تشغيلية' : 'Vehicle status: Operational')
                : (this.userLanguage === 'ar' ? 'حالة المركبة: خارج الخدمة' : 'Vehicle status: Out of Service');
        }
      
        let message = '';
        if (response.action === 'update') {
            message = this.userLanguage === 'ar'
                ? `✓ تم تحديث الحركة #${response.movement_id} بنجاح`
                : `✓ Movement #${response.movement_id} updated successfully`;
        } else {
            message = this.userLanguage === 'ar'
                ? `✓ تم تسجيل الحركة الجديدة #${response.movement_id} بنجاح`
                : `✓ New movement #${response.movement_id} recorded successfully`;
        }
      
        if (photosCount > 0) {
            message += this.userLanguage === 'ar'
                ? ` (${photosCount} صورة)`
                : ` (${photosCount} photos)`;
        }
      
        if (statusText) {
            message += `<br>• ${statusText}`;
        }
      
        if (response.has_coordinates) {
            message += this.userLanguage === 'ar'
                ? `<br>• تم حفظ الإحداثيات`
                : `<br>• Coordinates saved`;
        }
      
        this.showNotification('success', message);
    }
    async loadCurrentTime() {
        try {
            const timeResponse = await fetch(this.API_TIME);
            if (!timeResponse.ok) throw new Error('Failed to get time');
          
            const time = await timeResponse.json();
            if (time.success && time.datetime) {
                document.getElementById('movement_datetime').value = time.datetime;
            }
        } catch (error) {
            console.error('Error loading time:', error);
        }
    }
    resetForm() {
        // تفريغ النموذج
        this.form.reset();
      
        // تفريغ معاينة الصور
        if (this.photosPreview) {
            this.photosPreview.innerHTML = '';
            this.storedPhotos = [];
        }
      
        // تفريغ المتغيرات
        this.uploadedPhotos = [];
        this.storedPhotos = [];
      
        // تفريغ الإحداثيات
        this.clearLocation();
      
        // إزالة معرّف الحركة
        document.getElementById('movement_id').value = '';
      
        // إعادة تعيين المستخدم الحالي
        if (this.performedByInput) {
            this.performedByInput.value = document.getElementById('created_by')?.value || '';
        }
      
        // تفريغ حقل المسؤول
        if (this.adminPerformedByInput) {
            this.adminPerformedByInput.value = '';
        }
      
        // تفريغ قائمة الحركات
        this.vehicleMovements = [];
        this.currentMovementIndex = -1;
        this.currentMovementId = null;
        this.currentVehicleCode = null;
      
        // إخفاء عناصر التنقل
        if (this.navigationContainer) {
            this.navigationContainer.style.display = 'none';
        }
      
        if (this.vehicleMovementsList) {
            this.vehicleMovementsList.innerHTML = '';
        }
      
        // تفريغ ملفات الصور
        if (this.photosInput) {
            this.photosInput.value = '';
        }
      
        // إزالة حذف الصور
        const deletedPhotosInput = document.getElementById('deleted_filenames');
        if (deletedPhotosInput) {
            deletedPhotosInput.remove();
        }
      
        // تفريغ حقل رمز المركبة
        document.getElementById('vehicle_code').readOnly = false;
      
        // إعادة تحميل الوقت الحالي
        this.loadCurrentTime();
      
        // تفريغ خريطة الصور
        this.photoMap.clear();
      
        // إشعار نجاح التفريغ
        this.showNotification('success', this.userLanguage === 'ar'
            ? '✓ تم تفريغ النموذج بنجاح'
            : '✓ Form cleared successfully');
    }
    async saveAsDraft() {
        this.showNotification('info', this.getTranslation('errors.draft_saved'));
    }
    cancelForm() {
        const message = this.getTranslation('errors.cancel_confirmation');
        if (confirm(message)) {
            this.resetForm();
            window.history.back();
        }
    }
    closeModal() {
        const message = this.getTranslation('errors.close_confirmation');
        if (confirm(message)) {
            window.close();
        }
    }
    showLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
    }
    hideLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }
    translateMessage(messageKey, params = {}) {
        let message = this.getTranslation(messageKey);
      
        Object.keys(params).forEach(key => {
            message = message.replace(`{${key}}`, params[key]);
        });
      
        return message;
    }
    showNotification(type, text) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} fade-in`;
      
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
      
        notification.style.cssText = `
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
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
        `;
      
        notification.innerHTML = `
            <i class="fas ${icons[type]}" style="color: ${colors[type]}; font-size: 18px;"></i>
            <span style="flex: 1; font-size: 14px; line-height: 1.4;">${text}</span>
            <button type="button" class="close-notification" style="background: none; border: none; color: inherit; cursor: pointer; padding: 4px; opacity: 0.7; transition: opacity 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        `;
      
        this.notificationContainer.appendChild(notification);
      
        const closeBtn = notification.querySelector('.close-notification');
        closeBtn.addEventListener('click', () => notification.remove());
      
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(20px)';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
}
// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new VehicleMovementForm();
});
// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    .notification {
        transition: all 0.3s ease;
    }
  
    .fade-in {
        animation: fadeIn 0.3s ease;
    }
  
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
  
    .notification-success {
        background: #d4edda !important;
        border-color: #c3e6cb !important;
        color: #155724 !important;
    }
  
    .notification-error {
        background: #f8d7da !important;
        border-color: #f5c6cb !important;
        color: #721c24 !important;
    }
  
    .notification-warning {
        background: #fff3cd !important;
        border-color: #ffeaa7 !important;
        color: #856404 !important;
    }
  
    .notification-info {
        background: #d1ecf1 !important;
        border-color: #bee5eb !important;
        color: #0c5460 !important;
    }
`;
document.head.appendChild(style);