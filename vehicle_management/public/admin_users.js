const AdminUsers = (function(){
  const API_BASE = '/vehicle_management/api/users/admin';
  const ROLES_API = '/vehicle_management/api/permissions/roles/list.php';
  // تم الاحتفاظ بهذا الثابت، لكن المنطق الداخلي لجلب المراجع سيتم عبر ReferencesCascade
  const REFS_HELPER = '/vehicle_management/api/helper/get_references.php'; 
  let currentRoles = [];
  let currentLang = 'ar';

  function log(...a){ try { console.debug('[AdminUsers]', ...a); } catch(e){} }
  function escapeHTML(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

  // START: References Cascade Helper Logic (Provided by user for cascading selects)
  const ReferencesCascade = (function(){
    // Helper to fetch references based on type and parent_id
    async function fetchRefs(type, parent_id, lang='ar'){
      let url = REFS_HELPER + '?lang=' + encodeURIComponent(lang) + '&type=' + encodeURIComponent(type);
      if (parent_id) url += '&parent_id=' + encodeURIComponent(parent_id);
      const res = await fetch(url, { credentials:'same-origin' });
      try { return await res.json(); } catch(e) { return { success: false, message: 'Invalid response' }; }
    }
  
    function emptySelect(sel, placeholder='-- اختر --'){
      sel.innerHTML = '';
      const o = document.createElement('option'); o.value=''; o.textContent = placeholder; sel.appendChild(o);
    }
  
    async function fillSelect(sel, items){
      emptySelect(sel);
      items.forEach(it=>{
        const o = document.createElement('option');
        o.value = it.id;
        // Prefer name_ar/name_en if available, otherwise fallback to generic name or ID
        o.textContent = it.name_ar || it.name_en || it.name || ('#'+it.id); 
        sel.appendChild(o);
      });
    }
  
    function bind(depSelQuery, secSelQuery, divSelQuery, opts = {}) {
      const lang = opts.lang || 'ar';
      const d = document.querySelector(depSelQuery);
      const s = document.querySelector(secSelQuery);
      const dv = document.querySelector(divSelQuery);
      if (!d || !s || !dv) return console.warn('ReferencesCascade: select(s) not found');
  
      // 1. Initial load of departments
      (async ()=>{
        const j = await fetchRefs('departments', null, lang);
        const items = j.departments || [];
        await fillSelect(d, items);
        
        // Re-apply pre-selected value
        if (d.dataset.selected) d.value = d.dataset.selected;
        
        // Trigger change to load sections if department preselected
        if (d.value) d.dispatchEvent(new Event('change'));
      })();
  
      // 2. Department change listener -> loads Sections
      d.addEventListener('change', async function(){
        const depId = this.value || null;
        // Load sections for depId
        const j = await fetchRefs('sections', depId, lang);
        const items = j.sections || [];
        await fillSelect(s, items);
        
        // Re-apply pre-selected value
        if (s.dataset.selected) {
          s.value = s.dataset.selected;
          // بعد الاستخدام، قم بإزالة data-selected لضمان عدم تطبيقه في المرات القادمة
          s.removeAttribute('data-selected'); 
        }
        
        // clear divisions
        emptySelect(dv);
        
        // Trigger change to load divisions if section preselected
        if (s.value) s.dispatchEvent(new Event('change'));
      });
  
      // 3. Section change listener -> loads Divisions
      s.addEventListener('change', async function(){
        const secId = this.value || null;
        const j = await fetchRefs('divisions', secId, lang);
        const items = j.divisions || [];
        await fillSelect(dv, items);
        
        // Re-apply pre-selected value
        if (dv.dataset.selected) {
          dv.value = dv.dataset.selected;
          // بعد الاستخدام، قم بإزالة data-selected لضمان عدم تطبيقه في المرات القادمة
          dv.removeAttribute('data-selected'); 
        }
      });
    }
  
    return { bind, fillSelect, emptySelect };
  })();
  // END: References Cascade Helper Logic

  // --- Ensure modal exists in DOM. If missing, create it dynamically.
  function ensureModalExists() {
    if (document.getElementById('modalBackdrop')) return;
    const backdrop = document.createElement('div');
    backdrop.id = 'modalBackdrop';
    backdrop.className = 'modal-backdrop';
    backdrop.style.display = 'none';
    backdrop.innerHTML = `
      <div class="modal" role="document" aria-labelledby="modalTitle">
        <h3 id="modalTitle"></h3>
        <div id="modalBody"></div>
        <div class="modal-actions" style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
          <button id="modalCancel" class="btn ghost">إلغاء</button>
          <button id="modalSave" class="btn">حفظ</button>
        </div>
      </div>
    `;
    document.body.appendChild(backdrop);

    // basic styles fallback if page didn't include modal CSS (keeps appearance usable)
    if (!document.getElementById('admin-users-modal-style')) {
      const style = document.createElement('style');
      style.id = 'admin-users-modal-style';
      style.textContent = `
        .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;z-index:9999}
        .modal{background:#fff;border-radius:10px;padding:18px;max-width:920px;width:96%;max-height:90vh;overflow:auto;border:1px solid #e6e9eb}
        .modal-actions .btn{min-width:88px}
      `;
      document.head.appendChild(style);
    }

    log('Modal created dynamically');
    // NOTE: Listeners are now attached in init() to cover both static and dynamic creation.
  }

  function modalBackdropEl(){ return document.getElementById('modalBackdrop'); }
  function modalBodyEl(){ return document.getElementById('modalBody'); }
  function modalTitleEl(){ return document.getElementById('modalTitle'); }

  function showModal(){ const mb = modalBackdropEl(); if (mb) mb.style.display = 'flex'; }
  function closeModal(){ const mb = modalBackdropEl(); if (mb) mb.style.display = 'none'; if (modalBodyEl()) modalBodyEl().innerHTML = ''; }

  // fetch helpers
  async function api(path, opts = {}) {
    opts.credentials = 'same-origin';
    opts.headers = Object.assign({'Accept':'application/json'}, opts.headers||{});
    const res = await fetch(API_BASE + '/' + path, opts);
    try { return await res.json(); } catch(e) { return { success:false, message:'Invalid response', status: res.status }; }
  }
  async function fetchRoles() {
    try { const res = await fetch(ROLES_API, { credentials:'same-origin' }); const j = await res.json(); if (j && j.success) return j.roles || []; } catch(e){ log('fetchRoles error', e); } return [];
  }
  // تم حذف fetchRefs القديمة حيث يتم الآن استخدام منطق الجلب داخل ReferencesCascade

  // render users
  function renderUsers(rows) {
    const tbody = document.querySelector('#usersTable tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    rows.forEach(u=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${escapeHTML(u.display_name || u.username)}</td>
        <td>${escapeHTML(u.emp_id||'')}</td>
        <td>${escapeHTML(u.email||'')}</td>
        <td>${escapeHTML(u.phone||'')}</td>
        <td>${escapeHTML(roleLabel(u.role_id))}</td>
        <td>${escapeHTML(u.department_name || u.department_id || '')}</td>
        <td>${escapeHTML(u.section_name || u.section_id || '')}</td>
        <td>${escapeHTML(u.division_name || u.division_id || '')}</td>
        <td>${u.is_active==1 ? '<span style="color:green">مفعل</span>' : '<span style="color:red">معطّل</span>'}</td>
        <td class="actions">
          <button data-id="${u.id}" class="edit" title="تعديل"><i class="fa fa-edit"></i></button>
          <button data-id="${u.id}" class="del" title="حذف"><i class="fa fa-trash"></i></button>
          <button data-id="${u.id}" class="toggle" title="${u.is_active==1 ? 'تعطيل' : 'تفعيل'}"><i class="fa fa-user-check"></i></button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  function roleLabel(r) {
    if (!r) return 'User';
    if (parseInt(r) === 1) return 'سوبر ادمن';
    if (parseInt(r) === 2) return 'ادمن';
    const rr = currentRoles.find(x => parseInt(x.id,10)===parseInt(r,10));
    return rr ? (rr.name_ar || rr.name_en || ('role '+r)) : ('role ' + r);
  }

  // Build edit form
  function buildForm(user){
    ensureModalExists();
    const fields = [
      // تم تغيير key إلى 'username' ليتطابق مع حقل قاعدة البيانات المطلوب
      {key:'username', label:'الاسم الظاهر', type:'text'}, 
      {key:'emp_id', label:'الرمز الوظيفي', type:'text'},
      // تم إزالة حقل 'username' المكرر من القائمة الأصلية
      {key:'email', label:'البريد الإلكتروني', type:'email'},
      {key:'phone', label:'الهاتف', type:'text'},
      {key:'preferred_language', label:'اللغة المفضلة', type:'select', options:[{v:'ar',t:'عربى'},{v:'en',t:'English'}]},
      {key:'role_id', label:'الدور', type:'select_roles'},
      {key:'is_active', label:'مفعل', type:'checkbox'},
      // إضافة معرفات فريدة لحقول المراجع لاستخدامها في ReferencesCascade
      {key:'department_id', label:'الإدارة', type:'select_ref', ref:'departments', id:'depSelect'},
      {key:'section_id', label:'القسم', type:'select_ref', ref:'sections', id:'secSelect'},
      {key:'division_id', label:'الوحدة', type:'select_ref', ref:'divisions', id:'divSelect'}
    ];

    // FIX: استخدام عنصر <form> بدلاً من <div> لحل خطأ FormData
    const formElement = document.createElement('form');
    formElement.id = 'editForm';
    // منع الإرسال الافتراضي للمتصفح
    formElement.addEventListener('submit', (e) => e.preventDefault()); 

    fields.forEach(f=>{
      // التخطي إذا كان المفتاح غير موجود في بيانات المستخدم إلا إذا كان حقل إشارة مرجعية (select_ref) أو حقل username الجديد
      if (!(f.key in user) && f.type !== 'select_ref' && f.key !== 'username') return; 
      const row = document.createElement('div'); row.className='form-row';
      const label = document.createElement('label'); label.textContent = f.label;
      const controlWrap = document.createElement('div'); controlWrap.style.flex='1';

      const initialValue = user[f.key] ?? '';
      
      if (f.type === 'text' || f.type === 'email') {
        const inp = document.createElement('input'); inp.type = (f.type==='email'?'email':'text'); inp.name = f.key; 
        inp.value = initialValue;
        
        // إذا كان المفتاح هو 'username'، نستخدم user.username. نضيف user.display_name كقيمة احتياطية للعرض
        if (f.key === 'username') { 
            inp.value = user.username || user.display_name || ''; // ** التعديل هنا **
        }
        controlWrap.appendChild(inp);
      } else if (f.type === 'select') {
        const sel = document.createElement('select'); sel.name = f.key;
        f.options.forEach(op=>{ const o=document.createElement('option'); o.value=op.v; o.textContent=op.t; if((initialValue+'')===(op.v+'')) o.selected=true; sel.appendChild(o); });
        controlWrap.appendChild(sel);
      } else if (f.type === 'select_roles') {
        const sel=document.createElement('select'); sel.name=f.key;
        const emptyOpt = document.createElement('option'); emptyOpt.value=''; emptyOpt.textContent='-- اختر --'; sel.appendChild(emptyOpt);
        (currentRoles||[]).forEach(r=>{ const o=document.createElement('option'); o.value=r.id; o.textContent=(r.name_ar||r.name_en||('role '+r.id)); if(parseInt(user.role_id||0,10)===parseInt(r.id,10)) o.selected=true; sel.appendChild(o); });
        controlWrap.appendChild(sel);
      } else if (f.type === 'checkbox') {
        const chk=document.createElement('input'); chk.type='checkbox'; chk.name=f.key; chk.checked = (parseInt(initialValue||0,10)===1);
        controlWrap.appendChild(chk);
      } else if (f.type === 'select_ref') {
        // منطق القوائم المنسدلة المتتالية: يتم وضع القيمة الأولية في data-selected
        const sel=document.createElement('select'); 
        sel.name=f.key;
        sel.id=f.id; 
        
        sel.setAttribute('data-selected', initialValue);

        // وضع خيار 'اختر' فقط. ReferencesCascade.bind سيتولى الباقي.
        const emptyOpt = document.createElement('option'); emptyOpt.value=''; emptyOpt.textContent='-- اختر --'; sel.appendChild(emptyOpt);
        
        controlWrap.appendChild(sel);
      }

      row.appendChild(label);
      row.appendChild(controlWrap);
      formElement.appendChild(row); 
    });

    const hid = document.createElement('input'); hid.type='hidden'; hid.name='id'; hid.value=user.id; formElement.appendChild(hid); 

    modalTitleEl().textContent = 'تعديل المستخدم: ' + (user.display_name || user.username || user.id);
    modalBodyEl().innerHTML = '';
    modalBodyEl().appendChild(formElement); 
    
    // ربط المنطق المتتالي بعد إضافة النموذج إلى DOM
    ReferencesCascade.bind('#depSelect', '#secSelect', '#divSelect', { lang: currentLang });
  }

  // Submit function now connects to update.php
  async function submitModalForm() {
    try {
      const form = document.getElementById('editForm');
      if (!form) { log('submitModalForm: editForm not found'); alert('لم يتم العثور على النموذج'); return; }
      
      const fd = new FormData(form); 

      // تأكد من أن حقول checkbox يتم إرسالها (1 أو 0)
      Array.from(form.querySelectorAll('input[type=checkbox]')).forEach(cb=>{
        if (!fd.has(cb.name)) fd.append(cb.name, '0'); else fd.set(cb.name, cb.checked ? '1' : '0');
      });
      
      // حقل الاسم الظاهر يُرسل الآن كـ 'username' وهو حقل إجباري
      // تم التأكد من عدم حذفه في نسخة سابقة

      for (const [k,v] of fd.entries()) log('form', k, '=', v);
      
      const url = API_BASE + '/update.php'; 
      log('POST ->', url);
      
      const res = await fetch(url, { method:'POST', credentials:'same-origin', body: fd });
      let j;
      try { j = await res.json(); } catch(e) { j = { success:false, message:'Invalid JSON or non-200 status', status: res.status }; }
      
      log('server response', j);

      // عرض رسالة النجاح/الفشل وإعادة تحميل القائمة
      if (j && j.success) { 
        alert('تم الحفظ بنجاح'); 
        closeModal(); 
        load(); 
      } else { 
        alert('فشل الحفظ: ' + (j.message || 'راجع سجل استجابة الخادم')); 
      }

    } catch (e) { 
      console.error(e); 
      alert('خطأ أثناء الإرسال: ' + (e.message || e)); 
    }
  }

  // Delegated event handling
  function setupDelegation() {
    document.addEventListener('click', async function(e){
      const btn = e.target.closest && e.target.closest('button');
      if (!btn) return;
      if (btn.classList.contains('edit')) {
        const id = btn.getAttribute('data-id');
        if (!id) return;
        ensureModalExists();
        modalBodyEl().innerHTML = '<div class="small">جاري التحميل…</div>';
        showModal();
        try {
          // يتم جلب الدور فقط، لأن المراجع يتم جلبها الآن عبر ReferencesCascade عند الحاجة
          const rolesRes = await fetchRoles();
          currentRoles = rolesRes || [];
          
          // ثم جلب المستخدم
          const userRes = await api('get.php?id=' + encodeURIComponent(id));
          
          if (!userRes || !userRes.success) {
            modalBodyEl().innerHTML = `<div class="small" style="color:red">${escapeHTML(userRes?.message||'خطأ')}</div>`;
            return;
          }
          buildForm(userRes.user);
        } catch (err) {
          modalBodyEl().innerHTML = `<div class="small" style="color:red">خطأ: ${escapeHTML(err.message||err)}</div>`;
        }
        return;
      }

      if (btn.classList.contains('del')) {
        const id = btn.getAttribute('data-id'); if (!id) return;
        // استخدام modal مخصص بدلاً من confirm()
        if (!confirm('هل تريد حذف المستخدم نهائياً؟')) return; 
        const fd = new FormData(); fd.append('id', id);
        const res = await api('delete.php', { method:'POST', body: fd });
        if (res && res.success) { alert('تم الحذف'); load(); } else alert(res.message || 'فشل الحذف');
        return;
      }

      if (btn.classList.contains('toggle')) {
        const id = btn.getAttribute('data-id'); if (!id) return;
        const fd = new FormData(); fd.append('id', id);
        const res = await api('activate.php', { method:'POST', body: fd });
        if (res && res.success) { alert('تم التغيير'); load(); } else alert(res.message || 'فشل العملية');
        return;
      }
    });
  }

  // load
  async function load(){
    const desc = document.getElementById('desc');
    if (desc) desc.textContent = 'جاري التحميل…';
    try {
      if (typeof window.sessionCheck === 'function') {
        try { const s = await window.sessionCheck(); if (s && s.success && s.user && s.user.preferred_language) currentLang = (s.user.preferred_language || 'ar').toLowerCase(); } catch(e){}
      }
      // جلب الأدوار فقط، حيث المراجع أصبحت متتالية
      currentRoles = await fetchRoles(); 
    } catch(e){ currentRoles = []; }
    const res = await api('list.php');
    if (!res || !res.success) {
      if (desc) desc.textContent = res ? (res.message || 'خطأ في التحميل') : 'خطأ في التحميل';
      return;
    }
    if (desc) desc.textContent = 'عرض ' + (res.users ? res.users.length : 0) + ' مستخدم(ين)';
    renderUsers(res.users || []);
  }

  // **** الوظيفة الجديدة لربط مستمعات الأحداث بالنافذة المنبثقة ****
  function setupModalListeners() {
      const backdrop = document.getElementById('modalBackdrop');
      const cancelButton = document.getElementById('modalCancel');
      const saveButton = document.getElementById('modalSave');

      // ربط أحداث الإلغاء والحفظ مرة واحدة عند التهيئة
      if (backdrop) backdrop.addEventListener('click', (e)=> { if (e.target === backdrop) closeModal(); });
      if (cancelButton) cancelButton.addEventListener('click', (e)=> { e.preventDefault(); closeModal(); });
      if (saveButton) saveButton.addEventListener('click', (e)=> { e.preventDefault(); submitModalForm(); });

      log('Modal listeners attached successfully in init().');
  }

  function init(){
    ensureModalExists();
    setupDelegation();
    setupModalListeners(); // ضمان ربط المستمعات بغض النظر عن طريقة إنشاء النافذة المنبثقة
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) refreshBtn.addEventListener('click', load);
    load();
    log('AdminUsers initialized');
  }

  return { init, load };
})();