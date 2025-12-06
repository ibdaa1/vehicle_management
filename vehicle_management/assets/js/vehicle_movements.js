// vehicle_management/assets/js/vehicle_movements.js
(function () {
  'use strict';

  // API Endpoints
  const API_SESSION = '/vehicle_management/api/users/session_check.php';
  const API_PERMISSIONS = '/vehicle_management/api/permissions/get_permissions.php';
  const API_MOVEMENTS = '/vehicle_management/api/vehicle/get_vehicle_movements.php';
  const API_DELETE = '/vehicle_management/api/vehicle/delete.php';

  // DOM elements
  const searchInput = document.getElementById('searchInput');
  const operationFilter = document.getElementById('operationFilter');
  const perPageSelect = document.getElementById('perPageSelect');
  const movementsList = document.getElementById('movementsList');
  const paginationControls = document.getElementById('paginationControls');
  const loggedUserEl = document.getElementById('loggedUser');
  const orgNameEl = document.getElementById('orgName');
  const photoModal = document.getElementById('photoModal');
  const photoGallery = document.getElementById('photoGallery');

  // State
  let currentSession = null;
  let currentPermissions = null;
  let currentPage = 1;
  let totalPages = 1;
  let totalRecords = 0;

  // Fetch helper with credentials
  async function fetchJson(url, opts = {}) {
    opts = Object.assign({}, opts);
    opts.credentials = 'include';
    if (!opts.headers) opts.headers = {};
    opts.headers['Accept'] = 'application/json';
    opts.headers['X-Requested-With'] = 'XMLHttpRequest';
    
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

  // Session check
  async function sessionCheck() {
    const r = await fetchJson(API_SESSION, { method: 'GET' });
    
    if (!r.ok || !r.json || !r.json.success) {
      movementsList.innerHTML = '<div class="loading">غير مصرح - يرجى <a href="/vehicle_management/public/login.html">تسجيل الدخول</a></div>';
      return null;
    }
    
    currentSession = r.json;
    if (loggedUserEl) loggedUserEl.textContent = `${r.json.user.username || ''} (ID: ${r.json.user.emp_id || ''})`;
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

  // Load movements
  async function loadMovements() {
    const q = searchInput.value.trim();
    const operation = operationFilter.value;
    const per_page = parseInt(perPageSelect.value) || 30;
    
    movementsList.innerHTML = '<div class="loading">جاري التحميل...</div>';
    
    const params = new URLSearchParams({
      page: currentPage,
      per_page: per_page
    });
    
    if (q) params.append('q', q);
    if (operation) params.append('operation_type', operation);
    
    const r = await fetchJson(`${API_MOVEMENTS}?${params.toString()}`, { method: 'GET' });
    
    if (!r.ok || !r.json || !r.json.success) {
      movementsList.innerHTML = '<div class="loading" style="color:#dc2626">فشل تحميل القائمة</div>';
      return;
    }
    
    const data = r.json;
    totalRecords = data.total || 0;
    totalPages = Math.ceil(totalRecords / per_page);
    
    renderMovementsTable(data.movements || []);
    renderPagination();
  }

  // Render movements table
  function renderMovementsTable(movements) {
    if (movements.length === 0) {
      movementsList.innerHTML = '<div class="loading">لا توجد نتائج</div>';
      return;
    }
    
    const canDelete = currentPermissions?.can_delete || false;
    const currentUserId = currentSession?.user?.id;
    
    let html = '<div class="table-container"><table><thead><tr>';
    html += '<th>ID</th>';
    html += '<th>رقم المركبة</th>';
    html += '<th>نوع العملية</th>';
    html += '<th>الموظف</th>';
    html += '<th>التاريخ</th>';
    html += '<th>الموقع</th>';
    html += '<th>الصور</th>';
    html += '<th>الإجراءات</th>';
    html += '</tr></thead><tbody>';
    
    movements.forEach(m => {
      const isOwner = currentUserId && m.user_id && (parseInt(currentUserId) === parseInt(m.user_id));
      const showDelete = canDelete || isOwner;
      
      html += '<tr>';
      html += `<td>${m.id}</td>`;
      html += `<td>${m.vehicle_code || '-'}</td>`;
      html += `<td>${translateOperation(m.operation_type)}</td>`;
      html += `<td>${m.performed_by || '-'}</td>`;
      html += `<td>${m.movement_date || '-'}</td>`;
      html += `<td>${m.location || '-'}</td>`;
      html += '<td>';
      
      if (m.photos && m.photos.length > 0) {
        html += `<button class="btn small ghost" onclick="window.showPhotos(${m.id}, ${JSON.stringify(m.photos).replace(/"/g, '&quot;')})">عرض (${m.photos.length})</button>`;
      } else {
        html += '-';
      }
      
      html += '</td>';
      html += '<td><div class="action-buttons">';
      
      if (showDelete) {
        html += `<button class="btn small danger" onclick="window.deleteMovement(${m.id})">حذف</button>`;
      }
      
      html += '</div></td>';
      html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    movementsList.innerHTML = html;
  }

  // Translate operation type
  function translateOperation(op) {
    const map = {
      pickup: 'استلام',
      return: 'إرجاع'
    };
    return map[op] || op;
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
    loadMovements();
  };

  // Show photos modal
  window.showPhotos = function (movementId, photos) {
    if (!photos || photos.length === 0) {
      alert('لا توجد صور');
      return;
    }
    
    let html = '<div class="photo-grid">';
    photos.forEach(photo => {
      const photoUrl = photo.startsWith('http') ? photo : `/vehicle_management/uploads/${photo}`;
      html += `<div class="photo-item">`;
      html += `<img src="${photoUrl}" alt="Photo" onclick="window.open('${photoUrl}', '_blank')" />`;
      html += `</div>`;
    });
    html += '</div>';
    
    photoGallery.innerHTML = html;
    photoModal.style.display = 'block';
  };

  // Close photo modal
  window.closePhotoModal = function () {
    photoModal.style.display = 'none';
  };

  // Close modal when clicking outside
  window.onclick = function (event) {
    if (event.target === photoModal) {
      photoModal.style.display = 'none';
    }
  };

  // Delete movement
  window.deleteMovement = async function (id) {
    if (!confirm('هل أنت متأكد من حذف هذه الحركة؟')) {
      return;
    }
    
    const fd = new FormData();
    fd.append('id', id);
    fd.append('type', 'movement');
    
    const r = await fetchJson(API_DELETE, {
      method: 'POST',
      body: fd
    });
    
    if (r.ok && r.json && r.json.success) {
      alert('تم الحذف بنجاح');
      loadMovements();
    } else {
      alert(r.json?.message || 'فشل الحذف');
    }
  };

  // Initialize
  async function init() {
    const session = await sessionCheck();
    if (!session) return;
    
    await getPermissions();
    await loadMovements();
    
    // Event listeners
    if (searchInput) {
      searchInput.addEventListener('input', debounce(() => {
        currentPage = 1;
        loadMovements();
      }, 500));
    }
    
    if (operationFilter) {
      operationFilter.addEventListener('change', () => {
        currentPage = 1;
        loadMovements();
      });
    }
    
    if (perPageSelect) {
      perPageSelect.addEventListener('change', () => {
        currentPage = 1;
        loadMovements();
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
