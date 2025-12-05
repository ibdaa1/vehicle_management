// vehicle_management/assets/js/profile.js
// Cascade selects for profile.html: department -> section -> division
// Prefill values from session_check (sess.user).
// Submit form to users/update_user_session.php via fetch(FormData).
// Non-destructive: لا يغيّر أي حقل آخر من الصفحة.

(function () {
  'use strict';

  const API_BASE = '/vehicle_management/api';
  const REFS_API = API_BASE + '/helper/get_references.php';
  const SESSION_CHECK = API_BASE + '/users/session_check.php';
  const UPDATE_URL = API_BASE + '/users/update_user_session.php';

  // DOM
  const form = document.getElementById('profileForm');
  const deptSel = document.getElementById('department_id');
  const sectionSel = document.getElementById('section_id');
  const divisionSel = document.getElementById('division_id');
  const msgEl = document.getElementById('messages');
  const saveBtn = document.getElementById('saveBtn');
  const preferredLangSel = document.getElementById('preferred_language');

  function showMessage(text, ok = true) {
    if (!msgEl) return;
    msgEl.innerHTML = `<div class="msg ${ok ? 'success' : 'error'}">${text}</div>`;
    setTimeout(()=> { if (msgEl) msgEl.innerHTML = ''; }, 6000);
  }

  function clearMessage() { if (msgEl) msgEl.innerHTML = ''; }

  async function fetchJSON(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error('Network error ' + res.status);
    return res.json();
  }

  function setPlaceholder(sel, text) {
    if (!sel) return;
    sel.innerHTML = '';
    const op = document.createElement('option');
    op.value = '';
    op.textContent = text;
    sel.appendChild(op);
  }

  function fillSelectRobust(sel, items, lang, placeholder) {
    if (!sel) return;
    const prev = sel.value;
    setPlaceholder(sel, placeholder);
    if (!Array.isArray(items)) items = [];
    items.forEach(it => {
      // items may be {id,name,name_ar,name_en} or {department_id,name_ar,...}
      const id = it.id ?? it.department_id ?? it.section_id ?? it.division_id ?? null;
      let label = it.name ?? (lang === 'en' ? (it.name_en || it.name_ar) : (it.name_ar || it.name_en || it.name));
      if (!label) {
        // try other keys
        for (const k of Object.keys(it)) {
          if (typeof it[k] === 'string' && it[k].length > 0 && !k.match(/_id$|id$/i)) { label = it[k]; break; }
        }
      }
      if (id == null) return;
      const o = document.createElement('option');
      o.value = id;
      o.textContent = label || String(id);
      sel.appendChild(o);
    });
    // restore previous if present
    if (prev) {
      const opt = sel.querySelector(`option[value="${prev}"]`);
      if (opt) sel.value = prev;
    }
  }

  // Load departments
  async function loadDepartments(lang='ar') {
    setPlaceholder(deptSel, lang === 'en' ? 'Select department' : 'اختر الإدارة');
    try {
      const j = await fetchJSON(`${REFS_API}?type=departments&lang=${encodeURIComponent(lang)}`);
      fillSelectRobust(deptSel, j.departments || j.items || j.data || [], lang, lang === 'en' ? 'Select department' : 'اختر الإدارة');
    } catch (e) {
      console.warn('loadDepartments', e);
      // leave placeholder
    }
  }

  // Load sections for department
  async function loadSections(departmentId, lang='ar') {
    setPlaceholder(sectionSel, lang === 'en' ? 'Select section' : 'اختر القسم');
    if (!departmentId) return;
    try {
      const j = await fetchJSON(`${REFS_API}?type=sections&parent_id=${encodeURIComponent(departmentId)}&lang=${encodeURIComponent(lang)}`);
      fillSelectRobust(sectionSel, j.sections || j.items || j.data || [], lang, lang === 'en' ? 'Select section' : 'اختر القسم');
    } catch (e) {
      console.warn('loadSections', e);
    }
  }

  // Load divisions for section
  async function loadDivisions(sectionId, lang='ar') {
    setPlaceholder(divisionSel, lang === 'en' ? 'Select division' : 'اختر الشعبة');
    if (!sectionId) return;
    try {
      const j = await fetchJSON(`${REFS_API}?type=divisions&parent_id=${encodeURIComponent(sectionId)}&lang=${encodeURIComponent(lang)}`);
      fillSelectRobust(divisionSel, j.divisions || j.items || j.data || [], lang, lang === 'en' ? 'Select division' : 'اختر الشعبة');
    } catch (e) {
      console.warn('loadDivisions', e);
    }
  }

  // main init: sessionCheck -> load refs -> preselect values -> bind events
  async function init() {
    clearMessage();
    // session check
    let sess = null;
    try {
      sess = await fetchJSON(SESSION_CHECK);
    } catch (e) {
      console.error('sessionCheck failed', e);
      location.href = '/vehicle_management/public/login.html';
      return;
    }
    if (!sess || !sess.success || !sess.user) {
      location.href = '/vehicle_management/public/login.html';
      return;
    }

    const user = sess.user;
    const lang = (user.preferred_language || preferredLangSel?.value || document.documentElement.lang || 'ar').toLowerCase();

    // populate non-cascade fields only if they are empty (non-destructive)
    if (document.getElementById('emp_id') && !document.getElementById('emp_id').value) document.getElementById('emp_id').value = user.emp_id || '';
    if (document.getElementById('username') && !document.getElementById('username').value) document.getElementById('username').value = user.username || '';
    if (document.getElementById('email') && !document.getElementById('email').value) document.getElementById('email').value = user.email || '';
    if (document.getElementById('phone') && !document.getElementById('phone').value) document.getElementById('phone').value = user.phone || '';
    if (preferredLangSel && !preferredLangSel.value) preferredLangSel.value = user.preferred_language || lang;

    // load departments then set selection and load sections/divisions accordingly
    await loadDepartments(lang);
    const depId = user.department_id ? String(user.department_id) : (form.getAttribute('data-department-id') || '');
    if (depId) {
      const opt = deptSel.querySelector(`option[value="${depId}"]`);
      if (opt) {
        deptSel.value = depId;
        await loadSections(depId, lang);
        const secId = user.section_id ? String(user.section_id) : (form.getAttribute('data-section-id') || '');
        if (secId) {
          const optS = sectionSel.querySelector(`option[value="${secId}"]`);
          if (optS) {
            sectionSel.value = secId;
            await loadDivisions(secId, lang);
            const divId = user.division_id ? String(user.division_id) : (form.getAttribute('data-division-id') || '');
            if (divId) {
              const optD = divisionSel.querySelector(`option[value="${divId}"]`);
              if (optD) divisionSel.value = divId;
            }
          } else {
            // section not found in initial set: still attempt to load divisions if user.section_id present
            await loadDivisions(secId, lang);
            const optD = divisionSel.querySelector(`option[value="${user.division_id}"]`);
            if (optD) divisionSel.value = user.division_id;
          }
        }
      } else {
        // department not found; leave selects empty
      }
    }

    // Bind change handlers for cascade
    deptSel && deptSel.addEventListener('change', async function () {
      const newDep = this.value || '';
      // clear downstream
      setPlaceholder(sectionSel, lang === 'en' ? 'Select section' : 'اختر القسم');
      setPlaceholder(divisionSel, lang === 'en' ? 'Select division' : 'اختر الشعبة');
      if (newDep) await loadSections(newDep, lang);
    });

    sectionSel && sectionSel.addEventListener('change', async function () {
      const newSec = this.value || '';
      setPlaceholder(divisionSel, lang === 'en' ? 'Select division' : 'اختر الشعبة');
      if (newSec) await loadDivisions(newSec, lang);
    });

    // Form submit: send FormData to UPDATE_URL and show messages
    form && form.addEventListener('submit', async function (ev) {
      ev.preventDefault();
      clearMessage();
      if (saveBtn) saveBtn.disabled = true;
      showMessage(lang === 'en' ? 'Saving...' : 'جارٍ الحفظ...', true);
      try {
        const fd = new FormData(form);
        const res = await fetch(UPDATE_URL, {
          method: 'POST',
          credentials: 'same-origin',
          body: fd
        });
        const j = await res.json().catch(() => null);
        if (j && j.success) {
          showMessage(j.message || (lang === 'en' ? 'Saved' : 'تم الحفظ'), true);
          // update data-* attributes for future prefill (non-destructive)
          form.setAttribute('data-department-id', fd.get('department_id') || '');
          form.setAttribute('data-section-id', fd.get('section_id') || '');
          form.setAttribute('data-division-id', fd.get('division_id') || '');
          // optional: refresh session or page after small delay
          setTimeout(()=> location.reload(), 700);
        } else {
          showMessage((j && j.message) ? j.message : (lang === 'en' ? 'Update failed' : 'فشل التحديث'), false);
          if (saveBtn) saveBtn.disabled = false;
        }
      } catch (e) {
        console.error('profile update error', e);
        showMessage(lang === 'en' ? 'Network error' : 'خطأ في الاتصال', false);
        if (saveBtn) saveBtn.disabled = false;
      }
    });
  }

  // run init
  init().catch(err => { console.error('profile init error', err); });

})();