// vehicle_management/assets/js/profile.js
// Cascade selects for profile.html: department -> section -> division
// Prefill values from session_check (sess.user).
// Submit form to users/update_user_session.php via fetch(FormData).
// Supports multi-language with translation system.

(function(){
'use strict';

// Global variables
const API_BASE = '/vehicle_management/api';
const REFS_API = API_BASE + '/helper/get_references.php';
const SESSION_CHECK = API_BASE + '/users/session_check.php';
const UPDATE_URL = API_BASE + '/users/update_user_session.php';

// DOM elements
const form = document.getElementById('profileForm');
const deptSel = document.getElementById('department_id');
const sectionSel = document.getElementById('section_id');
const divisionSel = document.getElementById('division_id');
const msgEl = document.getElementById('messages');
const saveBtn = document.getElementById('saveBtn');
const preferredLangSel = document.getElementById('preferred_language');

// Translation variables
let currentLanguage = 'ar';
let translations = {};

// Show/hide loading
function showLoading(show) {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        if (show) {
            spinner.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            spinner.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }
}

// Load translations
async function loadTranslations(lang) {
    try {
        const response = await fetch(`/vehicle_management/languages/${lang}_profile.json`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.warn(`Failed to load ${lang} translations:`, error);
        return getFallbackTranslations(lang);
    }
}

// Fallback translations
function getFallbackTranslations(lang) {
    const fallback = {
        ar: {
            selectDepartment: 'اختر الإدارة',
            selectSection: 'اختر القسم',
            selectDivision: 'اختر الشعبة',
            savingText: 'جارٍ الحفظ...',
            saveSuccess: 'تم حفظ التغييرات بنجاح',
            saveError: 'حدث خطأ أثناء الحفظ',
            networkError: 'خطأ في الاتصال بالشبكة',
            validationError: 'الرجاء ملء جميع الحقول المطلوبة',
            passwordMismatch: 'كلمة المرور الحالية غير صحيحة',
            passwordUpdated: 'تم تحديث كلمة المرور بنجاح',
            profileUpdated: 'تم تحديث الملف الشخصي بنجاح',
            arabicOption: 'العربية',
            englishOption: 'English'
        },
        en: {
            selectDepartment: 'Select Department',
            selectSection: 'Select Section',
            selectDivision: 'Select Division',
            savingText: 'Saving...',
            saveSuccess: 'Changes saved successfully',
            saveError: 'Error saving changes',
            networkError: 'Network connection error',
            validationError: 'Please fill all required fields',
            passwordMismatch: 'Current password is incorrect',
            passwordUpdated: 'Password updated successfully',
            profileUpdated: 'Profile updated successfully',
            arabicOption: 'Arabic',
            englishOption: 'English'
        }
    };
    return fallback[lang] || fallback.ar;
}

// Apply translations to dynamic elements
function applyTranslations() {
    if (!translations) return;
    
    // Update select options for language
    if (preferredLangSel) {
        const arabicOpt = preferredLangSel.querySelector('option[value="ar"]');
        const englishOpt = preferredLangSel.querySelector('option[value="en"]');
        
        if (arabicOpt && translations.arabicOption) {
            arabicOpt.textContent = translations.arabicOption;
        }
        if (englishOpt && translations.englishOption) {
            englishOpt.textContent = translations.englishOption;
        }
    }
}

// Show message with translation support
function showMessage(textKey, isSuccess = true) {
    if (!msgEl) return;
    
    const messageText = translations[textKey] || textKey;
    msgEl.innerHTML = `<div class="msg ${isSuccess ? 'success' : 'error'}">${messageText}</div>`;
    
    setTimeout(() => {
        if (msgEl) msgEl.innerHTML = '';
    }, 6000);
}

// Clear message
function clearMessage() {
    if (msgEl) msgEl.innerHTML = '';
}

// Fetch JSON with error handling
async function fetchJSON(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error('Network error ' + res.status);
    return res.json();
}

// Set placeholder for select with translation
function setPlaceholder(sel, translationKey) {
    if (!sel) return;
    
    // Clear existing options
    while (sel.options.length > 0) {
        sel.remove(0);
    }
    
    // Add placeholder option
    const placeholderText = translations[translationKey] || translationKey;
    const op = document.createElement('option');
    op.value = '';
    op.textContent = placeholderText;
    op.disabled = true;
    op.selected = true;
    sel.appendChild(op);
}

// Fill select with items (supports translation for placeholder)
function fillSelectRobust(sel, items, placeholderKey) {
    if (!sel) return;
    
    // Store current value
    const prev = sel.value;
    
    // Clear and set placeholder
    setPlaceholder(sel, placeholderKey);
    
    if (!Array.isArray(items)) items = [];
    
    // Add items
    items.forEach(it => {
        // items may be {id, name, name_ar, name_en} or {department_id, name_ar, ...}
        const id = it.id ?? it.department_id ?? it.section_id ?? it.division_id ?? null;
        let label = it.name ?? (currentLanguage === 'en' ? 
            (it.name_en || it.name_ar || it.name) : 
            (it.name_ar || it.name_en || it.name));
        
        if (!label) {
            // try other keys
            for (const k of Object.keys(it)) {
                if (typeof it[k] === 'string' && it[k].length > 0 && !k.match(/_id$|id$/i)) {
                    label = it[k];
                    break;
                }
            }
        }
        
        if (id == null) return;
        
        const o = document.createElement('option');
        o.value = id;
        o.textContent = label || String(id);
        sel.appendChild(o);
    });
    
    // Restore previous selection if present
    if (prev) {
        const opt = sel.querySelector(`option[value="${prev}"]`);
        if (opt) sel.value = prev;
        else sel.value = '';
    }
}

// Load departments
async function loadDepartments() {
    const placeholderKey = 'selectDepartment';
    
    try {
        const j = await fetchJSON(`${REFS_API}?type=departments&lang=${encodeURIComponent(currentLanguage)}`);
        fillSelectRobust(deptSel, j.departments || j.items || j.data || [], placeholderKey);
    } catch (e) {
        console.warn('loadDepartments error:', e);
        setPlaceholder(deptSel, placeholderKey);
    }
}

// Load sections for department
async function loadSections(departmentId) {
    const placeholderKey = 'selectSection';
    
    if (!departmentId) {
        setPlaceholder(sectionSel, placeholderKey);
        setPlaceholder(divisionSel, 'selectDivision');
        return;
    }
    
    try {
        const j = await fetchJSON(`${REFS_API}?type=sections&parent_id=${encodeURIComponent(departmentId)}&lang=${encodeURIComponent(currentLanguage)}`);
        fillSelectRobust(sectionSel, j.sections || j.items || j.data || [], placeholderKey);
        
        // Clear divisions
        setPlaceholder(divisionSel, 'selectDivision');
    } catch (e) {
        console.warn('loadSections error:', e);
        setPlaceholder(sectionSel, placeholderKey);
    }
}

// Load divisions for section
async function loadDivisions(sectionId) {
    const placeholderKey = 'selectDivision';
    
    if (!sectionId) {
        setPlaceholder(divisionSel, placeholderKey);
        return;
    }
    
    try {
        const j = await fetchJSON(`${REFS_API}?type=divisions&parent_id=${encodeURIComponent(sectionId)}&lang=${encodeURIComponent(currentLanguage)}`);
        fillSelectRobust(divisionSel, j.divisions || j.items || j.data || [], placeholderKey);
    } catch (e) {
        console.warn('loadDivisions error:', e);
        setPlaceholder(divisionSel, placeholderKey);
    }
}

// Initialize page
async function init() {
    showLoading(true);
    clearMessage();
    
    // Load session
    let sess = null;
    try {
        sess = await fetchJSON(SESSION_CHECK);
    } catch (e) {
        console.error('sessionCheck failed', e);
        window.location.href = '/vehicle_management/public/login.html';
        return;
    }
    
    if (!sess || !sess.success || !sess.user) {
        window.location.href = '/vehicle_management/public/login.html';
        return;
    }
    
    const user = sess.user;
    
    // Set current language from user preference
    currentLanguage = user.preferred_language || 'ar';
    
    // Load translations
    translations = await loadTranslations(currentLanguage);
    applyTranslations();
    
    // Update HTML lang attribute
    document.documentElement.lang = currentLanguage;
    document.documentElement.dir = currentLanguage === 'ar' ? 'rtl' : 'ltr';
    
    // Fill form fields
    if (document.getElementById('emp_id')) {
        document.getElementById('emp_id').value = user.emp_id || '';
    }
    
    if (document.getElementById('username')) {
        document.getElementById('username').value = user.username || '';
    }
    
    if (document.getElementById('email')) {
        document.getElementById('email').value = user.email || '';
    }
    
    if (document.getElementById('phone')) {
        document.getElementById('phone').value = user.phone || '';
    }
    
    if (document.getElementById('display_name')) {
        document.getElementById('display_name').value = user.display_name || user.username || '';
    }
    
    if (preferredLangSel) {
        preferredLangSel.value = user.preferred_language || currentLanguage;
    }
    
    // Load departments
    await loadDepartments();
    
    // Set department selection
    const depId = user.department_id ? String(user.department_id) : (form.getAttribute('data-department-id') || '');
    if (depId && deptSel) {
        const deptOption = deptSel.querySelector(`option[value="${depId}"]`);
        if (deptOption) {
            deptSel.value = depId;
            await loadSections(depId);
            
            // Set section selection
            const secId = user.section_id ? String(user.section_id) : (form.getAttribute('data-section-id') || '');
            if (secId && sectionSel) {
                const secOption = sectionSel.querySelector(`option[value="${secId}"]`);
                if (secOption) {
                    sectionSel.value = secId;
                    await loadDivisions(secId);
                    
                    // Set division selection
                    const divId = user.division_id ? String(user.division_id) : (form.getAttribute('data-division-id') || '');
                    if (divId && divisionSel) {
                        const divOption = divisionSel.querySelector(`option[value="${divId}"]`);
                        if (divOption) {
                            divisionSel.value = divId;
                        }
                    }
                }
            }
        }
    }
    
    // Bind cascade change handlers
    if (deptSel) {
        deptSel.addEventListener('change', async function() {
            const newDep = this.value || '';
            await loadSections(newDep);
        });
    }
    
    if (sectionSel) {
        sectionSel.addEventListener('change', async function() {
            const newSec = this.value || '';
            await loadDivisions(newSec);
        });
    }
    
    // Language change handler
    if (preferredLangSel) {
        preferredLangSel.addEventListener('change', async function() {
            const newLang = this.value;
            if (newLang === currentLanguage) return;
            
            showLoading(true);
            currentLanguage = newLang;
            
            // Load new translations
            translations = await loadTranslations(currentLanguage);
            applyTranslations();
            
            // Update HTML attributes
            document.documentElement.lang = currentLanguage;
            document.documentElement.dir = currentLanguage === 'ar' ? 'rtl' : 'ltr';
            
            // Reload department/section/division data with new language
            await loadDepartments();
            
            const currentDept = deptSel?.value;
            if (currentDept) {
                await loadSections(currentDept);
                
                const currentSection = sectionSel?.value;
                if (currentSection) {
                    await loadDivisions(currentSection);
                }
            }
            
            showLoading(false);
        });
    }
    
    // Form submit handler
    if (form) {
        form.addEventListener('submit', async function(ev) {
            ev.preventDefault();
            clearMessage();
            
            if (saveBtn) {
                saveBtn.disabled = true;
                const originalText = saveBtn.textContent;
                saveBtn.innerHTML = `<i class="fa fa-spinner fa-spin"></i> ${translations.savingText || 'Saving...'}`;
            }
            
            // Show saving message
            showMessage('savingText', true);
            
            try {
                const fd = new FormData(form);
                const res = await fetch(UPDATE_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                
                const j = await res.json().catch(() => null);
                
                if (j && j.success) {
                    showMessage('saveSuccess', true);
                    
                    // Update data attributes for future prefill
                    form.setAttribute('data-department-id', fd.get('department_id') || '');
                    form.setAttribute('data-section-id', fd.get('section_id') || '');
                    form.setAttribute('data-division-id', fd.get('division_id') || '');
                    
                    // Update language if changed
                    const newLang = fd.get('preferred_language');
                    if (newLang && newLang !== currentLanguage) {
                        currentLanguage = newLang;
                        translations = await loadTranslations(currentLanguage);
                        applyTranslations();
                        
                        // Update HTML attributes
                        document.documentElement.lang = currentLanguage;
                        document.documentElement.dir = currentLanguage === 'ar' ? 'rtl' : 'ltr';
                    }
                    
                    // Refresh after delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                    
                } else {
                    const errorKey = j && j.message_key ? j.message_key : 'saveError';
                    showMessage(errorKey, false);
                }
                
            } catch (e) {
                console.error('Profile update error:', e);
                showMessage('networkError', false);
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = `<i class="fa fa-save"></i> ${translations.saveLabel || 'Save'}`;
                }
            }
        });
    }
    
    showLoading(false);
}

// Run initialization
document.addEventListener('DOMContentLoaded', () => {
    init().catch(err => {
        console.error('Profile init error:', err);
        showLoading(false);
    });
});

})();