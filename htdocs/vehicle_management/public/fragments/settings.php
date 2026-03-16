<?php
/**
 * Settings Fragment — Theme & System Settings Management (Admin)
 * Loaded inside dashboard.php shell.
 *
 * Features:
 * - Full CRUD for Themes, Colors, Fonts, Buttons, Cards, Design, System Settings
 * - Theme selector with color palette preview
 * - Color editor modal for theme customization
 * - Design settings editor
 * - System settings editor with editable fields grouped by category
 * - Single generic modal for all add/edit operations
 */
?>
<style>
/* Fix LTR layout flash: html[dir] is set before CSS renders, body[dir] after */
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}
/* ---- Stats ---- */
.st-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.st-stat{background:var(--bg-card);padding:16px;border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);text-align:center}
.st-stat .st-val{font-size:1.5rem;font-weight:700;color:var(--text-primary)}
.st-stat .st-lbl{font-size:.8rem;color:var(--text-secondary);margin-top:4px}
/* ---- Tabs ---- */
.st-tabs{display:flex;gap:0;margin-bottom:24px;border-bottom:2px solid var(--border-default);overflow-x:auto}
.st-tab{padding:12px 22px;cursor:pointer;font-size:.9rem;font-weight:600;color:var(--text-secondary);border-bottom:3px solid transparent;margin-bottom:-2px;white-space:nowrap;transition:all .2s}
.st-tab:hover{color:var(--primary-main)}
.st-tab.active{color:var(--primary-main);border-bottom-color:var(--primary-main)}
.st-panel{display:none}.st-panel.active{display:block}
/* ---- Themes ---- */
.st-themes{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.st-theme-card{background:var(--bg-card);border-radius:12px;border:2px solid var(--border-default);padding:20px;min-width:240px;max-width:320px;flex:1;cursor:pointer;transition:all .3s;position:relative}
.st-theme-card:hover{border-color:var(--primary-light);box-shadow:0 4px 12px rgba(0,0,0,.1)}
.st-theme-card.active{border-color:var(--primary-main);box-shadow:0 0 0 3px rgba(58,81,58,.15)}
.st-theme-card .st-active-badge{position:absolute;top:10px;inset-inline-end:10px;background:var(--status-success);color:#fff;font-size:.7rem;padding:2px 10px;border-radius:20px;font-weight:600}
.st-theme-name{font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:4px}
.st-theme-desc{font-size:.8rem;color:var(--text-secondary);margin-bottom:12px}
.st-theme-colors{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:12px}
.st-color-dot{width:28px;height:28px;border-radius:50%;border:2px solid var(--border-default);display:inline-block}
.st-theme-actions{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
/* ---- Color Editor ---- */
.st-color-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
.st-color-item{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-main);border-radius:10px;border:1px solid var(--border-default);position:relative}
.st-color-item label{font-size:.8rem;color:var(--text-secondary);flex:1}
.st-color-item .st-color-name{font-size:.85rem;font-weight:600;color:var(--text-primary);display:block}
.st-color-item input[type=color]{width:42px;height:32px;border:1px solid var(--border-default);border-radius:6px;cursor:pointer;padding:2px}
.st-color-item .st-color-hex{font-size:.75rem;color:var(--text-secondary);font-family:monospace}
/* ---- Design Settings ---- */
.st-design-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.st-design-item{background:var(--bg-card);padding:16px;border-radius:10px;border:1px solid var(--border-default);position:relative}
.st-design-item label{display:block;font-size:.8rem;font-weight:600;color:var(--text-primary);margin-bottom:6px}
.st-design-item input{width:100%;padding:8px 12px;border:1px solid var(--border-default);border-radius:8px;font-size:.85rem;background:var(--bg-main);color:var(--text-primary)}
.st-design-item .st-design-cat{font-size:.7rem;color:var(--text-secondary);margin-bottom:4px;text-transform:uppercase}
/* ---- System Settings ---- */
.st-settings-group{margin-bottom:24px}
.st-settings-group h4{font-size:.95rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border-default)}
.st-settings-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.st-setting-card{background:var(--bg-card);padding:16px;border-radius:10px;border:1px solid var(--border-default);box-shadow:var(--card-shadow);position:relative}
.st-setting-card .st-set-key{font-size:.7rem;color:var(--text-secondary);font-family:monospace;margin-bottom:4px}
.st-setting-card .st-set-desc{font-size:.75rem;color:var(--text-secondary);margin-bottom:8px}
.st-setting-card .st-set-val{display:flex;gap:8px;align-items:center}
.st-setting-card .st-set-val input,.st-setting-card .st-set-val select{flex:1;padding:8px 12px;border:1px solid var(--border-default);border-radius:8px;font-size:.85rem;background:var(--bg-main);color:var(--text-primary)}
.st-setting-card .st-set-val .btn-save{background:var(--primary-main);color:#fff;border:none;padding:8px 14px;border-radius:8px;cursor:pointer;font-size:.8rem;white-space:nowrap}
.st-setting-card .st-set-val .btn-save:hover{opacity:.85}
/* ---- Shared ---- */
.st-section-title{font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:16px}
.st-empty{text-align:center;padding:30px;color:var(--text-secondary);font-size:.9rem}
/* ---- Modal ---- */
.st-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center}
.st-modal.active{display:flex}
.st-modal-content{background:var(--bg-card);border-radius:16px;width:90%;max-width:820px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.st-modal-header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid var(--border-default)}
.st-modal-header h3{margin:0;font-size:1.1rem;color:var(--text-primary)}
.st-modal-close{background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--text-secondary);padding:4px 8px;border-radius:6px}
.st-modal-close:hover{background:var(--bg-main)}
.st-modal-body{padding:24px}
.st-modal-footer{display:flex;gap:12px;justify-content:flex-end;padding:16px 24px;border-top:1px solid var(--border-default)}
.st-modal-footer .btn{padding:10px 22px;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;border:none;transition:all .2s}
.st-modal-footer .btn-secondary{background:var(--bg-main);color:var(--text-primary);border:1px solid var(--border-default)}
.st-modal-footer .btn-primary{background:var(--primary-main);color:#fff}
.st-modal-footer .btn:hover{opacity:.85}
/* ---- Font/Button preview ---- */
.st-font-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.st-font-item{background:var(--bg-card);padding:16px;border-radius:10px;border:1px solid var(--border-default);position:relative}
.st-font-item .st-font-name{font-size:.85rem;font-weight:600;color:var(--text-primary)}
.st-font-item .st-font-preview{margin-top:8px;padding:8px;background:var(--bg-main);border-radius:6px;font-size:.85rem;color:var(--text-secondary)}
.st-font-item .st-font-meta{font-size:.7rem;color:var(--text-secondary);margin-top:6px}
.st-btn-grid{display:flex;gap:12px;flex-wrap:wrap}
.st-btn-item{position:relative;display:inline-flex;align-items:center;gap:6px}
.st-btn-preview{display:inline-block;padding:8px 18px;border-radius:6px;font-size:.85rem;font-weight:600;border:1px solid transparent;cursor:default;transition:all .2s}
/* ---- CRUD Action Buttons ---- */
.st-section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.st-section-header .st-section-title{margin-bottom:0}
.btn-add{background:var(--primary-main);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;transition:opacity .2s}
.btn-add:hover{opacity:.85}
.btn-crud{background:none;border:1px solid var(--border-default);padding:3px 8px;border-radius:6px;cursor:pointer;font-size:.75rem;transition:all .2s;line-height:1}
.btn-crud:hover{background:var(--bg-main)}
.btn-crud.del:hover{background:#fee2e2;border-color:var(--status-danger)}
.st-item-actions{position:absolute;top:8px;inset-inline-end:8px;display:flex;gap:4px}
.st-theme-meta{font-size:.7rem;color:var(--text-secondary);margin-bottom:6px;font-family:monospace}
/* ---- Form inside generic modal ---- */
.st-form .fg{margin-bottom:14px}
.st-form .fg label{display:block;font-size:.8rem;font-weight:600;color:var(--text-primary);margin-bottom:5px}
.st-form .fg input,.st-form .fg select,.st-form .fg textarea{width:100%;padding:9px 12px;border:1px solid var(--border-default);border-radius:8px;font-size:.85rem;background:var(--bg-main);color:var(--text-primary);box-sizing:border-box}
.st-form .fg input[type=color]{width:60px;height:36px;padding:2px;cursor:pointer}
.st-form .fg textarea{min-height:60px;resize:vertical}
.st-form .fg .req{color:var(--status-danger)}
.st-form .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
</style>

<h2 data-label-ar="إدارة الإعدادات" data-label-en="Settings Management">إدارة الإعدادات</h2>

<!-- Stats -->
<div class="st-stats" id="stStats">
    <div class="st-stat"><div class="st-val" id="statThemes">-</div><div class="st-lbl" data-label-ar="المظاهر" data-label-en="Themes">المظاهر</div></div>
    <div class="st-stat"><div class="st-val" id="statColors">-</div><div class="st-lbl" data-label-ar="الألوان" data-label-en="Colors">الألوان</div></div>
    <div class="st-stat"><div class="st-val" id="statSettings">-</div><div class="st-lbl" data-label-ar="إعدادات النظام" data-label-en="System Settings">إعدادات النظام</div></div>
    <div class="st-stat"><div class="st-val" id="statActive">-</div><div class="st-lbl" data-label-ar="المظهر الحالي" data-label-en="Active Theme">المظهر الحالي</div></div>
</div>

<!-- Tabs -->
<div class="st-tabs">
    <div class="st-tab active" data-tab="themes" data-label-ar="المظاهر" data-label-en="Themes">المظاهر</div>
    <div class="st-tab" data-tab="colors" data-label-ar="الألوان" data-label-en="Colors">الألوان</div>
    <div class="st-tab" data-tab="fonts" data-label-ar="الخطوط" data-label-en="Fonts">الخطوط</div>
    <div class="st-tab" data-tab="design" data-label-ar="إعدادات التصميم" data-label-en="Design Settings">إعدادات التصميم</div>
    <div class="st-tab" data-tab="buttons" data-label-ar="أنماط الأزرار" data-label-en="Button Styles">أنماط الأزرار</div>
    <div class="st-tab" data-tab="cards" data-label-ar="أنماط البطاقات" data-label-en="Card Styles">أنماط البطاقات</div>
    <div class="st-tab" data-tab="system" data-label-ar="إعدادات النظام" data-label-en="System Settings">إعدادات النظام</div>
</div>

<!-- Panel 1: Themes -->
<div class="st-panel active" id="panelThemes">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="اختيار المظهر" data-label-en="Theme Selection">اختيار المظهر</div>
        <button class="btn-add st-theme-admin" onclick="SettingsPage.openItemModal('theme','add')" data-label-ar="➕ إضافة مظهر" data-label-en="➕ Add Theme">➕ إضافة مظهر</button>
    </div>
    <div class="st-themes" id="themesList">
        <div class="st-empty">جارٍ التحميل...</div>
    </div>
</div>

<!-- Panel 2: Colors -->
<div class="st-panel" id="panelColors">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="ألوان المظهر النشط" data-label-en="Active Theme Colors">ألوان المظهر النشط</div>
        <button class="btn-add" onclick="SettingsPage.addColorForActive()" data-label-ar="➕ إضافة لون" data-label-en="➕ Add Color">➕ إضافة لون</button>
    </div>
    <div id="colorsTabGrid" class="st-color-grid"><div class="st-empty">جارٍ التحميل...</div></div>
    <div style="margin-top:16px;text-align:center">
        <button class="btn-save" onclick="SettingsPage.saveColorsTab()" data-label-ar="💾 حفظ جميع الألوان" data-label-en="💾 Save All Colors">💾 حفظ جميع الألوان</button>
    </div>
</div>

<!-- Panel 3: Fonts -->
<div class="st-panel" id="panelFonts">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="الخطوط" data-label-en="Fonts">الخطوط</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('font','add')" data-label-ar="➕ إضافة" data-label-en="➕ Add">➕ إضافة</button>
    </div>
    <div id="fontsGrid" class="st-font-grid"><div class="st-empty">جارٍ التحميل...</div></div>
</div>

<!-- Panel 4: Design Settings -->
<div class="st-panel" id="panelDesign">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="إعدادات التصميم" data-label-en="Design Settings">إعدادات التصميم</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('design','add')" data-label-ar="➕ إضافة" data-label-en="➕ Add">➕ إضافة</button>
    </div>
    <div id="designGrid" class="st-design-grid"><div class="st-empty">جارٍ التحميل...</div></div>
</div>

<!-- Panel 5: Button Styles -->
<div class="st-panel" id="panelButtons">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="أنماط الأزرار" data-label-en="Button Styles">أنماط الأزرار</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('button','add')" data-label-ar="➕ إضافة" data-label-en="➕ Add">➕ إضافة</button>
    </div>
    <div id="buttonsGrid" class="st-btn-grid"><div class="st-empty">جارٍ التحميل...</div></div>
</div>

<!-- Panel 6: Card Styles -->
<div class="st-panel" id="panelCards">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="أنماط البطاقات" data-label-en="Card Styles">أنماط البطاقات</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('card','add')" data-label-ar="➕ إضافة" data-label-en="➕ Add">➕ إضافة</button>
    </div>
    <div id="cardsGrid" class="st-design-grid"><div class="st-empty">جارٍ التحميل...</div></div>
</div>

<!-- Panel 7: System Settings -->
<div class="st-panel" id="panelSystem">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="إعدادات النظام" data-label-en="System Settings">إعدادات النظام</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('system','add')" data-label-ar="➕ إضافة إعداد" data-label-en="➕ Add Setting">➕ إضافة إعداد</button>
    </div>
    <div id="systemSettings"><div class="st-empty">جارٍ التحميل...</div></div>
</div>

<!-- Color Editor Modal -->
<div class="st-modal" id="colorModal">
    <div class="st-modal-content">
        <div class="st-modal-header">
            <h3 id="colorModalTitle">🎨 تعديل الألوان</h3>
            <button class="st-modal-close" onclick="SettingsPage.closeColorModal()">✕</button>
        </div>
        <div class="st-modal-body">
            <div style="margin-bottom:16px;text-align:end">
                <button class="btn-add" id="btnAddColor" data-label-ar="➕ إضافة لون" data-label-en="➕ Add Color">➕ إضافة لون</button>
            </div>
            <div class="st-color-grid" id="colorGrid"></div>
        </div>
        <div class="st-modal-footer">
            <button class="btn btn-secondary" onclick="SettingsPage.closeColorModal()" data-label-ar="إغلاق" data-label-en="Close">إغلاق</button>
            <button class="btn btn-primary" onclick="SettingsPage.saveColors()" data-label-ar="💾 حفظ الألوان" data-label-en="💾 Save Colors">💾 حفظ الألوان</button>
        </div>
    </div>
</div>

<!-- Design Editor Modal -->
<div class="st-modal" id="designModal">
    <div class="st-modal-content">
        <div class="st-modal-header">
            <h3 id="designModalTitle">⚙️ تعديل التصميم</h3>
            <button class="st-modal-close" onclick="SettingsPage.closeDesignModal()">✕</button>
        </div>
        <div class="st-modal-body">
            <div class="st-design-grid" id="designEditGrid"></div>
        </div>
        <div class="st-modal-footer">
            <button class="btn btn-secondary" onclick="SettingsPage.closeDesignModal()" data-label-ar="إغلاق" data-label-en="Close">إغلاق</button>
            <button class="btn btn-primary" onclick="SettingsPage.saveDesign()" data-label-ar="💾 حفظ التصميم" data-label-en="💾 Save Design">💾 حفظ التصميم</button>
        </div>
    </div>
</div>

<!-- Generic Item Modal (single modal for all CRUD add/edit) -->
<div class="st-modal" id="stItemModal">
    <div class="st-modal-content">
        <div class="st-modal-header">
            <h3 id="stItemModalTitle"></h3>
            <button class="st-modal-close" onclick="SettingsPage.closeItemModal()">✕</button>
        </div>
        <div class="st-modal-body">
            <form class="st-form" id="stItemForm" onsubmit="return false;"></form>
        </div>
        <div class="st-modal-footer">
            <button class="btn btn-secondary" onclick="SettingsPage.closeItemModal()" data-label-ar="إلغاء" data-label-en="Cancel">إلغاء</button>
            <button class="btn btn-primary" id="stItemSaveBtn" onclick="SettingsPage.saveItem()" data-label-ar="💾 حفظ" data-label-en="💾 Save">💾 حفظ</button>
        </div>
    </div>
</div>

<?php
ob_start();
?>
<script>
(function(){
    'use strict';
    const $=id=>document.getElementById(id);
    const esc=s=>UI._escapeHtml(s==null?'':String(s));
    let allThemes=[], activeThemeData=null, editThemeId=null;
    /* Current modal context */
    let modalCtx={type:null,mode:null,data:null,themeId:null};
    /* Super admin check: only role_id=1 can create/edit/delete themes */
    let canManageThemes=false;
    function checkThemePermission(){
        try{
            var u=Auth.getUser();
            if(u&&(parseInt(u.role_id)===1||(u.permissions&&(u.permissions.includes('manage_themes')||u.permissions.includes('*'))))) canManageThemes=true;
        }catch(e){}
        /* Hide add buttons if not super admin */
        if(!canManageThemes){
            document.querySelectorAll('.st-theme-admin').forEach(function(el){el.style.display='none';});
        }
    }

    /* ---- Helpers ---- */
    function getActiveThemeId(){
        const t=allThemes.find(t=>t.is_active);
        return t?t.id:null;
    }

    /* ---- Tabs ---- */
    document.querySelectorAll('.st-tab').forEach(tab=>{
        tab.addEventListener('click',()=>{
            document.querySelectorAll('.st-tab').forEach(t=>t.classList.remove('active'));
            document.querySelectorAll('.st-panel').forEach(p=>p.classList.remove('active'));
            tab.classList.add('active');
            const panel=$('panel'+tab.dataset.tab.charAt(0).toUpperCase()+tab.dataset.tab.slice(1));
            if(panel) panel.classList.add('active');
        });
    });

    /* ================================================================
       GENERIC ITEM MODAL — single modal for all types
       ================================================================ */
    function buildField(name, label, type, value, required, opts){
        /* If required is an array, caller passed options without required flag */
        if(Array.isArray(required)){opts=required;required=false;}
        let h='<div class="fg">';
        h+='<label>'+esc(label)+(required?' <span class="req">*</span>':'')+'</label>';
        if(type==='select'&&opts){
            h+='<select name="'+esc(name)+'">';
            opts.forEach(o=>{
                const v=typeof o==='object'?o.value:o;
                const t=typeof o==='object'?o.label:o;
                h+='<option value="'+esc(v)+'"'+(String(value)===String(v)?' selected':'')+'>'+esc(t)+'</option>';
            });
            h+='</select>';
        }else if(type==='color'){
            h+='<input type="color" name="'+esc(name)+'" value="'+esc(value||'#000000')+'">';
        }else if(type==='textarea'){
            h+='<textarea name="'+esc(name)+'">'+esc(value||'')+'</textarea>';
        }else{
            h+='<input type="'+esc(type||'text')+'" name="'+esc(name)+'" value="'+esc(value||'')+'"'+(required?' required':'')+'>';
        }
        h+='</div>';
        return h;
    }

    function buildFormFields(type, data){
        const d=data||{};
        let h='';
        switch(type){
        case 'theme':
            h+='<div class="row2">';
            h+=buildField('name','الاسم / Name','text',d.name,true);
            h+=buildField('slug','المعرّف / Slug','text',d.slug,true);
            h+='</div>';
            h+=buildField('description','الوصف / Description','textarea',d.description);
            h+='<div class="row2">';
            h+=buildField('version','الإصدار / Version','text',d.version);
            h+=buildField('author','المؤلف / Author','text',d.author);
            h+='</div>';
            break;
        case 'color':
            h+='<div class="row2">';
            h+=buildField('setting_key','المفتاح / Key','text',d.setting_key,true);
            h+=buildField('setting_name','الاسم / Name','text',d.setting_name,true);
            h+='</div><div class="row2">';
            h+=buildField('color_value','اللون / Color','color',d.color_value,true);
            h+=buildField('category','التصنيف / Category','text',d.category);
            h+='</div>';
            break;
        case 'font':
            h+='<div class="row2">';
            h+=buildField('setting_key','المفتاح / Key','text',d.setting_key,true);
            h+=buildField('setting_name','الاسم / Name','text',d.setting_name,true);
            h+='</div><div class="row2">';
            h+=buildField('font_family','عائلة الخط / Font Family','text',d.font_family,true);
            h+=buildField('font_size','الحجم / Font Size','text',d.font_size);
            h+='</div><div class="row2">';
            h+=buildField('font_weight','الوزن / Font Weight','text',d.font_weight);
            h+=buildField('line_height','ارتفاع السطر / Line Height','text',d.line_height);
            h+='</div>';
            h+=buildField('category','التصنيف / Category','select',d.category||'heading',[
                {value:'heading',label:'عناوين / Heading'},{value:'body',label:'نص / Body'},{value:'ui',label:'واجهة / UI'},{value:'other',label:'أخرى / Other'}
            ]);
            break;
        case 'button':
            h+='<div class="row2">';
            h+=buildField('name','الاسم / Name','text',d.name,true);
            h+=buildField('slug','المعرّف / Slug','text',d.slug,true);
            h+='</div>';
            h+=buildField('button_type','النوع / Type','select',d.button_type||'primary',[
                {value:'primary',label:'أساسي / Primary'},{value:'secondary',label:'ثانوي / Secondary'},
                {value:'outline',label:'محيط / Outline'},{value:'ghost',label:'شبح / Ghost'},{value:'danger',label:'خطر / Danger'}
            ]);
            h+='<div class="row2">';
            h+=buildField('background_color','لون الخلفية','color',d.background_color||'#3a513a');
            h+=buildField('text_color','لون النص','color',d.text_color||'#ffffff');
            h+='</div><div class="row2">';
            h+=buildField('border_color','لون الحدود','color',d.border_color||'#3a513a');
            h+=buildField('border_radius','تدوير الحدود / Border Radius','text',d.border_radius||'8');
            h+='</div><div class="row2">';
            h+=buildField('padding','الحشو / Padding','text',d.padding||'8px 16px');
            h+=buildField('font_size','حجم الخط','text',d.font_size||'14px');
            h+='</div><div class="row2">';
            h+=buildField('font_weight','وزن الخط','text',d.font_weight||'600');
            h+=buildField('hover_background_color','لون خلفية التمرير','color',d.hover_background_color||'#2c3e2c');
            h+='</div>';
            h+=buildField('hover_text_color','لون نص التمرير','color',d.hover_text_color||'#ffffff');
            break;
        case 'card':
            h+='<div class="row2">';
            h+=buildField('name','الاسم / Name','text',d.name,true);
            h+=buildField('slug','المعرّف / Slug','text',d.slug,true);
            h+='</div>';
            h+=buildField('card_type','النوع / Type','text',d.card_type||'default');
            h+='<div class="row2">';
            h+=buildField('background_color','لون الخلفية','color',d.background_color||'#ffffff');
            h+=buildField('border_color','لون الحدود','color',d.border_color||'#e5e7eb');
            h+='</div><div class="row2">';
            h+=buildField('border_width','عرض الحدود','text',d.border_width||'1');
            h+=buildField('border_radius','تدوير الحدود','text',d.border_radius||'12');
            h+='</div><div class="row2">';
            h+=buildField('shadow_style','الظل / Shadow','text',d.shadow_style||'0 2px 8px rgba(0,0,0,0.08)');
            h+=buildField('padding','الحشو / Padding','text',d.padding||'16px');
            h+='</div><div class="row2">';
            h+=buildField('hover_effect','تأثير التمرير','select',d.hover_effect||'none',[
                {value:'none',label:'بدون / None'},{value:'lift',label:'رفع / Lift'},{value:'glow',label:'توهج / Glow'},{value:'scale',label:'تكبير / Scale'}
            ]);
            h+=buildField('text_align','محاذاة النص','select',d.text_align||'right',[
                {value:'right',label:'يمين / Right'},{value:'left',label:'يسار / Left'},{value:'center',label:'وسط / Center'}
            ]);
            h+='</div>';
            break;
        case 'design':
            h+='<div class="row2">';
            h+=buildField('setting_key','المفتاح / Key','text',d.setting_key,true);
            h+=buildField('setting_name','الاسم / Name','text',d.setting_name,true);
            h+='</div>';
            h+=buildField('setting_value','القيمة / Value','text',d.setting_value,true);
            h+='<div class="row2">';
            h+=buildField('setting_type','النوع / Type','select',d.setting_type||'text',[
                {value:'text',label:'نص / Text'},{value:'color',label:'لون / Color'},{value:'number',label:'رقم / Number'},
                {value:'boolean',label:'منطقي / Boolean'},{value:'json',label:'JSON'}
            ]);
            h+=buildField('category','التصنيف / Category','select',d.category||'layout',[
                {value:'layout',label:'تخطيط / Layout'},{value:'spacing',label:'تباعد / Spacing'},{value:'border',label:'حدود / Border'},
                {value:'shadow',label:'ظل / Shadow'},{value:'animation',label:'حركة / Animation'},{value:'other',label:'أخرى / Other'}
            ]);
            h+='</div>';
            break;
        case 'system':
            h+='<div class="row2">';
            h+=buildField('setting_key','المفتاح / Key','text',d.setting_key,true);
            h+=buildField('setting_value','القيمة / Value','text',d.setting_value,true);
            h+='</div><div class="row2">';
            h+=buildField('setting_type','النوع / Type','select',d.setting_type||'text',[
                {value:'text',label:'نص / Text'},{value:'color',label:'لون / Color'},{value:'number',label:'رقم / Number'},
                {value:'boolean',label:'منطقي / Boolean'},{value:'json',label:'JSON'},{value:'url',label:'رابط / URL'}
            ]);
            h+=buildField('category','التصنيف / Category','select',d.category||'general',[
                {value:'general',label:'عام / General'},{value:'security',label:'الأمان / Security'},{value:'appearance',label:'المظهر / Appearance'},
                {value:'system',label:'النظام / System'},{value:'contact',label:'التواصل / Contact'},{value:'branding',label:'العلامة التجارية / Branding'}
            ]);
            h+='</div>';
            h+=buildField('description','الوصف / Description','textarea',d.description);
            h+='<div class="row2">';
            h+=buildField('is_public','عام / Public','select',d.is_public!=null?d.is_public:'0',[
                {value:'1',label:'نعم / Yes'},{value:'0',label:'لا / No'}
            ]);
            h+=buildField('is_editable','قابل للتعديل / Editable','select',d.is_editable!=null?d.is_editable:'1',[
                {value:'1',label:'نعم / Yes'},{value:'0',label:'لا / No'}
            ]);
            h+='</div>';
            break;
        }
        return h;
    }

    function getModalTitle(type, mode){
        const titles={
            theme:   {add:'➕ إضافة مظهر',     edit:'✏️ تعديل المظهر'},
            color:   {add:'➕ إضافة لون',       edit:'✏️ تعديل اللون'},
            font:    {add:'➕ إضافة خط',        edit:'✏️ تعديل الخط'},
            button:  {add:'➕ إضافة زر',        edit:'✏️ تعديل الزر'},
            card:    {add:'➕ إضافة بطاقة',     edit:'✏️ تعديل البطاقة'},
            design:  {add:'➕ إضافة إعداد تصميم',edit:'✏️ تعديل إعداد التصميم'},
            system:  {add:'➕ إضافة إعداد نظام', edit:'✏️ تعديل إعداد النظام'}
        };
        return (titles[type]&&titles[type][mode])||'';
    }

    function collectFormData(form){
        const fd={};
        form.querySelectorAll('input,select,textarea').forEach(el=>{
            if(el.name) fd[el.name]=el.value;
        });
        return fd;
    }

    /* ================================================================
       LOAD THEMES
       ================================================================ */
    async function loadThemes(){
        const c=$('themesList');
        try{
            const res=await API.get('/settings/themes');
            allThemes=res.data||[];
            if(!allThemes.length){c.innerHTML='<div class="st-empty">لا توجد مظاهر متاحة</div>';return;}
            const activeTheme=allThemes.find(t=>t.is_active);
            if(activeTheme){
                try{
                    const det=await API.get('/settings/themes/'+activeTheme.id);
                    activeThemeData=det.data;
                }catch(e){activeThemeData=null;}
            }
            renderThemes();
            updateStats();
        }catch(e){c.innerHTML='<div class="st-empty" style="color:var(--status-danger)">تعذر تحميل المظاهر</div>';}
    }

    function renderThemes(){
        const c=$('themesList');
        let h='';
        allThemes.forEach(t=>{
            const isActive=!!t.is_active;
            h+='<div class="st-theme-card'+(isActive?' active':'')+'" data-id="'+t.id+'">';
            if(isActive) h+='<div class="st-active-badge" data-label-ar="مفعّل" data-label-en="Active">مفعّل</div>';
            if(canManageThemes){
                h+='<div class="st-item-actions" style="top:'+( isActive?'36':'8')+'px">';
                h+='<button class="btn-crud" onclick="SettingsPage.editTheme('+t.id+')" title="تعديل">✏️</button>';
                if(!isActive) h+='<button class="btn-crud del" onclick="SettingsPage.deleteTheme('+t.id+')" title="حذف">🗑️</button>';
                h+='</div>';
            }
            h+='<div class="st-theme-name">'+esc(t.name)+'</div>';
            h+='<div class="st-theme-meta">'+esc(t.slug)+(t.version?' v'+esc(t.version):'')+(t.author?' — '+esc(t.author):'')+'</div>';
            h+='<div class="st-theme-desc">'+esc(t.description||'')+'</div>';
            h+='<div class="st-theme-colors" id="themeColors'+t.id+'"></div>';
            h+='<div class="st-theme-actions">';
            if(canManageThemes){
                if(!isActive) h+='<button class="btn-save" style="background:var(--primary-main);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem" onclick="SettingsPage.switchTheme(\''+esc(t.slug)+'\')" data-label-ar="تفعيل" data-label-en="Activate">تفعيل</button>';
                h+='<button class="btn-save" style="background:var(--accent-gold,#c69c3f);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem" onclick="SettingsPage.editColors('+t.id+')" data-label-ar="🎨 الألوان" data-label-en="🎨 Colors">🎨 الألوان</button>';
                h+='<button class="btn-save" style="background:var(--status-info);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem" onclick="SettingsPage.editDesign('+t.id+')" data-label-ar="⚙️ التصميم" data-label-en="⚙️ Design">⚙️ التصميم</button>';
            }
            h+='</div></div>';
        });
        c.innerHTML=h;
        allThemes.forEach(t=>loadThemeColors(t.id));
    }

    async function loadThemeColors(themeId){
        const container=$('themeColors'+themeId);
        if(!container) return;
        try{
            const res=await API.get('/settings/themes/'+themeId);
            const colors=res.data?.colors||[];
            let h='';
            colors.forEach(c=>{
                h+='<span class="st-color-dot" style="background:'+esc(c.color_value)+'" title="'+esc(c.setting_name)+' ('+esc(c.color_value)+')"></span>';
            });
            container.innerHTML=h;
        }catch(e){container.innerHTML='';}
    }

    /* ================================================================
       LOAD COLORS TAB (standalone panel for active theme colors)
       ================================================================ */
    async function loadColorsTab(){
        const activeTheme=allThemes.find(t=>t.is_active);
        const grid=$('colorsTabGrid');
        if(!activeTheme){grid.innerHTML='<div class="st-empty">لا يوجد مظهر نشط</div>';return;}
        grid.innerHTML='<div class="st-empty">جارٍ التحميل...</div>';
        try{
            const res=await API.get('/settings/themes/'+activeTheme.id);
            const colors=res.data?.colors||[];
            if(!colors.length){grid.innerHTML='<div class="st-empty">لا توجد ألوان</div>';return;}
            let h='';
            colors.forEach(c=>{
                h+='<div class="st-color-item" data-id="'+c.id+'">';
                h+='<input type="color" value="'+esc(c.color_value)+'" onchange="this.closest(\'.st-color-item\').querySelector(\'.st-color-hex\').textContent=this.value">';
                h+='<label><span class="st-color-name">'+esc(c.setting_name)+'</span><span class="st-color-hex">'+esc(c.color_value)+'</span></label>';
                h+='<div style="display:flex;gap:4px;flex-shrink:0">';
                h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'color\',\'edit\','+esc(JSON.stringify(c))+','+activeTheme.id+')" title="تعديل">✏️</button>';
                h+='<button class="btn-crud del" onclick="SettingsPage.deleteColor('+c.id+','+activeTheme.id+')" title="حذف">🗑️</button>';
                h+='</div></div>';
            });
            grid.innerHTML=h;
        }catch(e){grid.innerHTML='<div class="st-empty" style="color:var(--status-danger)">تعذر تحميل الألوان</div>';}
    }

    /* ================================================================
       LOAD ACTIVE THEME DESIGN DETAILS
       ================================================================ */
    async function loadActiveDesign(){
        const activeTheme=allThemes.find(t=>t.is_active);
        if(!activeTheme) return;
        try{
            const res=await API.get('/settings/themes/'+activeTheme.id);
            const data=res.data;
            renderDesignSettings(data.design||[], activeTheme.id);
            renderFonts(data.fonts||[], activeTheme.id);
            renderButtons(data.buttons||[], activeTheme.id);
            renderCards(data.cards||[], activeTheme.id);
        }catch(e){
            $('designGrid').innerHTML='<div class="st-empty">تعذر تحميل إعدادات التصميم</div>';
        }
    }

    function renderDesignSettings(settings, themeId){
        const c=$('designGrid');
        if(!settings.length){c.innerHTML='<div class="st-empty">لا توجد إعدادات تصميم</div>';return;}
        let h='';
        settings.forEach(s=>{
            h+='<div class="st-design-item">';
            h+='<div class="st-item-actions">';
            h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'design\',\'edit\','+esc(JSON.stringify(s))+','+themeId+')" title="تعديل">✏️</button>';
            h+='<button class="btn-crud del" onclick="SettingsPage.deleteDesign('+s.id+','+themeId+')" title="حذف">🗑️</button>';
            h+='</div>';
            h+='<div class="st-design-cat">'+esc(s.category)+'</div>';
            h+='<label>'+esc(s.setting_name)+'</label>';
            h+='<input type="text" value="'+esc(s.setting_value)+'" disabled>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    function renderFonts(fonts, themeId){
        const c=$('fontsGrid');
        if(!fonts.length){c.innerHTML='<div class="st-empty">لا توجد إعدادات خطوط</div>';return;}
        let h='';
        fonts.forEach(f=>{
            h+='<div class="st-font-item">';
            h+='<div class="st-item-actions">';
            h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'font\',\'edit\','+esc(JSON.stringify(f))+','+themeId+')" title="تعديل">✏️</button>';
            h+='<button class="btn-crud del" onclick="SettingsPage.deleteFont('+f.id+','+themeId+')" title="حذف">🗑️</button>';
            h+='</div>';
            h+='<div class="st-font-name">'+esc(f.setting_name)+'</div>';
            h+='<div class="st-font-preview" style="font-family:'+esc(f.font_family)+'">نص عربي تجريبي — Sample Text</div>';
            h+='<div class="st-font-meta">'+esc(f.font_family)+' | '+esc(f.font_size||'-')+' | '+esc(f.font_weight||'-')+'</div>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    function renderButtons(buttons, themeId){
        const c=$('buttonsGrid');
        if(!buttons.length){c.innerHTML='<div class="st-empty">لا توجد أنماط أزرار</div>';return;}
        let h='';
        buttons.forEach(b=>{
            h+='<div class="st-btn-item">';
            h+='<span class="st-btn-preview" style="background:'+esc(b.background_color)+';color:'+esc(b.text_color);
            if(b.border_color) h+=';border-color:'+esc(b.border_color)+';border-width:1px;border-style:solid';
            h+=';border-radius:'+(b.border_radius||4)+'px;padding:'+esc(b.padding||'8px 16px')+'">';
            h+=esc(b.name)+'</span>';
            h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'button\',\'edit\','+esc(JSON.stringify(b))+','+themeId+')" title="تعديل">✏️</button>';
            h+='<button class="btn-crud del" onclick="SettingsPage.deleteButton('+b.id+','+themeId+')" title="حذف">🗑️</button>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    function renderCards(cards, themeId){
        const c=$('cardsGrid');
        if(!cards.length){c.innerHTML='<div class="st-empty">لا توجد أنماط بطاقات</div>';return;}
        let h='';
        cards.forEach(card=>{
            h+='<div class="st-design-item" style="background:'+esc(card.background_color||'#fff')+';border-color:'+esc(card.border_color||'#e0e0e0');
            h+=';border-radius:'+(card.border_radius||8)+'px;padding:'+esc(card.padding||'16px')+'">';
            h+='<div class="st-item-actions">';
            h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'card\',\'edit\','+esc(JSON.stringify(card))+','+themeId+')" title="تعديل">✏️</button>';
            h+='<button class="btn-crud del" onclick="SettingsPage.deleteCard('+card.id+','+themeId+')" title="حذف">🗑️</button>';
            h+='</div>';
            h+='<label style="color:var(--text-primary)">'+esc(card.name)+'</label>';
            h+='<div class="st-font-meta">'+esc(card.card_type)+' | shadow: '+esc(card.shadow_style||'none')+' | hover: '+esc(card.hover_effect||'none')+'</div>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    /* ================================================================
       LOAD SYSTEM SETTINGS
       ================================================================ */
    async function loadSettings(){
        const c=$('systemSettings');
        try{
            const res=await API.get('/settings');
            const settings=res.data||[];
            if(!Array.isArray(settings)||!settings.length){c.innerHTML='<div class="st-empty">لا توجد إعدادات</div>';return;}
            const groups={};
            settings.forEach(s=>{
                const cat=s.category||'عام';
                if(!groups[cat]) groups[cat]=[];
                groups[cat].push(s);
            });
            let h='';
            const catLabels={general:{ar:'عام',en:'General'},security:{ar:'الأمان',en:'Security'},appearance:{ar:'المظهر',en:'Appearance'},system:{ar:'النظام',en:'System'},contact:{ar:'التواصل',en:'Contact'},branding:{ar:'العلامة التجارية',en:'Branding'}};
            const lang=localStorage.getItem('lang')||'ar';
            for(const[cat,items]of Object.entries(groups)){
                h+='<div class="st-settings-group">';
                const lbl=catLabels[cat];
                h+='<h4>📁 '+(lbl?lbl[lang]:esc(cat))+'</h4>';
                h+='<div class="st-settings-grid">';
                items.forEach(s=>{
                    const editable=s.is_editable!==undefined?parseInt(s.is_editable):1;
                    h+='<div class="st-setting-card">';
                    h+='<div class="st-item-actions">';
                    h+='<button class="btn-crud del" onclick="SettingsPage.deleteSystemSetting('+s.id+')" title="حذف">🗑️</button>';
                    h+='</div>';
                    h+='<div class="st-set-key">'+esc(s.setting_key)+'</div>';
                    if(s.description) h+='<div class="st-set-desc">'+esc(s.description)+'</div>';
                    h+='<div class="st-set-val">';
                    if(s.setting_type==='boolean'){
                        h+='<select data-key="'+esc(s.setting_key)+'"'+(editable?'':' disabled')+'>';
                        h+='<option value="1"'+(s.setting_value==='1'?' selected':'')+'>نعم / Yes</option>';
                        h+='<option value="0"'+(s.setting_value==='0'?' selected':'')+'>لا / No</option>';
                        h+='</select>';
                    }else if(s.setting_type==='color'){
                        h+='<input type="color" value="'+esc(s.setting_value)+'" data-key="'+esc(s.setting_key)+'"'+(editable?'':' disabled')+'>';
                    }else{
                        h+='<input type="text" value="'+esc(s.setting_value||'')+'" data-key="'+esc(s.setting_key)+'"'+(editable?'':' disabled')+'>';
                    }
                    if(editable) h+='<button class="btn-save" onclick="SettingsPage.saveSetting(this)" data-label-ar="حفظ" data-label-en="Save">حفظ</button>';
                    h+='</div></div>';
                });
                h+='</div></div>';
            }
            c.innerHTML=h;
            $('statSettings').textContent=settings.length;
        }catch(e){c.innerHTML='<div class="st-empty" style="color:var(--status-danger)">تعذر تحميل الإعدادات</div>';}
    }

    function updateStats(){
        $('statThemes').textContent=allThemes.length;
        const active=allThemes.find(t=>t.is_active);
        $('statActive').textContent=active?active.name:'—';
    }

    /* ================================================================
       PUBLIC API — SettingsPage
       ================================================================ */
    window.SettingsPage={

        /* ---- Generic Item Modal ---- */
        openItemModal(type, mode, data, themeId){
            if(typeof data==='string'){try{data=JSON.parse(data);}catch(e){data=null;}}
            const tid=themeId||getActiveThemeId();
            modalCtx={type,mode,data:data||null,themeId:tid};
            $('stItemModalTitle').textContent=getModalTitle(type,mode);
            $('stItemForm').innerHTML=buildFormFields(type, data);
            $('stItemModal').classList.add('active');
        },

        closeItemModal(){
            $('stItemModal').classList.remove('active');
            modalCtx={type:null,mode:null,data:null,themeId:null};
        },

        async saveItem(){
            const {type,mode,data,themeId}=modalCtx;
            if(!type) return;
            const fd=collectFormData($('stItemForm'));
            try{
                if(type==='theme'){
                    if(mode==='add'){
                        await API.post('/settings/themes',fd);
                        UI.showToast('تم إنشاء المظهر بنجاح','success');
                    }else{
                        await API.put('/settings/themes/'+data.id,fd);
                        UI.showToast('تم تحديث المظهر بنجاح','success');
                    }
                    this.closeItemModal();
                    await loadThemes();
                }else if(type==='color'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/colors',fd);
                        UI.showToast('تم إضافة اللون بنجاح','success');
                    }else{
                        await API.put('/settings/colors/'+data.id,fd);
                        UI.showToast('تم تحديث اللون بنجاح','success');
                    }
                    this.closeItemModal();
                    /* Refresh color modal if open */
                    if($('colorModal').classList.contains('active')){
                        this.editColors(themeId);
                    }
                    loadColorsTab();
                    loadThemes();
                }else if(type==='font'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/fonts',fd);
                        UI.showToast('تم إضافة الخط بنجاح','success');
                    }else{
                        await API.put('/settings/fonts/'+data.id,fd);
                        UI.showToast('تم تحديث الخط بنجاح','success');
                    }
                    this.closeItemModal();
                    loadActiveDesign();
                }else if(type==='button'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/buttons',fd);
                        UI.showToast('تم إضافة الزر بنجاح','success');
                    }else{
                        await API.put('/settings/buttons/'+data.id,fd);
                        UI.showToast('تم تحديث الزر بنجاح','success');
                    }
                    this.closeItemModal();
                    loadActiveDesign();
                }else if(type==='card'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/cards',fd);
                        UI.showToast('تم إضافة البطاقة بنجاح','success');
                    }else{
                        await API.put('/settings/cards/'+data.id,fd);
                        UI.showToast('تم تحديث البطاقة بنجاح','success');
                    }
                    this.closeItemModal();
                    loadActiveDesign();
                }else if(type==='design'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/design',fd);
                        UI.showToast('تم إضافة إعداد التصميم بنجاح','success');
                    }else{
                        await API.put('/settings/design/'+data.id,fd);
                        UI.showToast('تم تحديث إعداد التصميم بنجاح','success');
                    }
                    this.closeItemModal();
                    loadActiveDesign();
                }else if(type==='system'){
                    if(mode==='add'){
                        await API.post('/settings',fd);
                        UI.showToast('تم إضافة الإعداد بنجاح','success');
                    }
                    this.closeItemModal();
                    loadSettings();
                }
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        /* ---- Theme Operations ---- */
        editTheme(id){
            const t=allThemes.find(th=>th.id===id||th.id===String(id));
            if(!t){UI.showToast('لم يتم العثور على المظهر','error');return;}
            this.openItemModal('theme','edit',{id:t.id,name:t.name,slug:t.slug,description:t.description,version:t.version,author:t.author});
        },

        async switchTheme(slug){
            try{
                await API.put('/settings/theme/'+slug,{});
                UI.showToast('تم تغيير المظهر بنجاح','success');
                setTimeout(()=>location.reload(),600);
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        async deleteTheme(id){
            if(!confirm('هل أنت متأكد من الحذف؟')) return;
            try{
                await API.del('/settings/themes/'+id);
                UI.showToast('تم حذف المظهر بنجاح','success');
                await loadThemes();
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        /* ---- Color Modal ---- */
        async editColors(themeId){
            editThemeId=themeId;
            const theme=allThemes.find(t=>t.id===themeId||t.id===String(themeId));
            $('colorModalTitle').textContent='🎨 ألوان: '+(theme?theme.name:'');
            const grid=$('colorGrid');
            grid.innerHTML='<div class="st-empty">جارٍ التحميل...</div>';
            $('colorModal').classList.add('active');
            /* Wire add-color button */
            $('btnAddColor').onclick=()=>this.openItemModal('color','add',null,themeId);
            try{
                const res=await API.get('/settings/themes/'+themeId);
                const colors=res.data?.colors||[];
                $('statColors').textContent=colors.length;
                if(!colors.length){grid.innerHTML='<div class="st-empty">لا توجد ألوان</div>';return;}
                let h='';
                colors.forEach(c=>{
                    h+='<div class="st-color-item" data-id="'+c.id+'">';
                    h+='<input type="color" value="'+esc(c.color_value)+'" onchange="this.closest(\'.st-color-item\').querySelector(\'.st-color-hex\').textContent=this.value">';
                    h+='<label><span class="st-color-name">'+esc(c.setting_name)+'</span><span class="st-color-hex">'+esc(c.color_value)+'</span></label>';
                    h+='<div style="display:flex;gap:4px;flex-shrink:0">';
                    h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'color\',\'edit\','+esc(JSON.stringify(c))+','+themeId+')" title="تعديل">✏️</button>';
                    h+='<button class="btn-crud del" onclick="SettingsPage.deleteColor('+c.id+','+themeId+')" title="حذف">🗑️</button>';
                    h+='</div></div>';
                });
                grid.innerHTML=h;
            }catch(e){grid.innerHTML='<div class="st-empty" style="color:var(--status-danger)">تعذر تحميل الألوان</div>';}
        },

        closeColorModal(){$('colorModal').classList.remove('active');},

        async saveColors(){
            const items=$('colorGrid').querySelectorAll('.st-color-item');
            const colors=[];
            items.forEach(item=>{
                const id=parseInt(item.dataset.id);
                const val=item.querySelector('input[type=color]').value;
                if(id) colors.push({id,color_value:val});
            });
            try{
                await API.put('/settings/themes/'+editThemeId+'/colors',{colors});
                UI.showToast('تم حفظ الألوان بنجاح','success');
                this.closeColorModal();
                const active=allThemes.find(t=>t.is_active);
                if(active&&(active.id==editThemeId||active.id===String(editThemeId))){
                    setTimeout(()=>location.reload(),600);
                }else{
                    loadThemes();
                }
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        /* Colors Tab helpers */
        addColorForActive(){
            const active=allThemes.find(t=>t.is_active);
            if(!active){UI.showToast('لا يوجد مظهر نشط','error');return;}
            this.openItemModal('color','add',null,active.id);
        },

        async saveColorsTab(){
            const active=allThemes.find(t=>t.is_active);
            if(!active) return;
            const items=$('colorsTabGrid').querySelectorAll('.st-color-item');
            const colors=[];
            items.forEach(item=>{
                const id=parseInt(item.dataset.id);
                const val=item.querySelector('input[type=color]').value;
                if(id) colors.push({id,color_value:val});
            });
            try{
                await API.put('/settings/themes/'+active.id+'/colors',{colors});
                UI.showToast('تم حفظ الألوان بنجاح','success');
                setTimeout(()=>location.reload(),600);
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        async deleteColor(colorId, themeId){
            if(!confirm('هل أنت متأكد من الحذف؟')) return;
            try{
                await API.del('/settings/colors/'+colorId);
                UI.showToast('تم حذف اللون بنجاح','success');
                this.editColors(themeId);
                loadColorsTab();
                loadThemes();
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        /* ---- Design Modal ---- */
        async editDesign(themeId){
            editThemeId=themeId;
            const theme=allThemes.find(t=>t.id===themeId||t.id===String(themeId));
            $('designModalTitle').textContent='⚙️ تصميم: '+(theme?theme.name:'');
            const grid=$('designEditGrid');
            grid.innerHTML='<div class="st-empty">جارٍ التحميل...</div>';
            $('designModal').classList.add('active');
            try{
                const res=await API.get('/settings/themes/'+themeId);
                const settings=res.data?.design||[];
                if(!settings.length){grid.innerHTML='<div class="st-empty">لا توجد إعدادات تصميم</div>';return;}
                let h='';
                settings.forEach(s=>{
                    h+='<div class="st-design-item" data-id="'+s.id+'">';
                    h+='<div class="st-design-cat">'+esc(s.category)+'</div>';
                    h+='<label>'+esc(s.setting_name)+'</label>';
                    h+='<input type="text" value="'+esc(s.setting_value)+'">';
                    h+='</div>';
                });
                grid.innerHTML=h;
            }catch(e){grid.innerHTML='<div class="st-empty" style="color:var(--status-danger)">تعذر تحميل إعدادات التصميم</div>';}
        },

        closeDesignModal(){$('designModal').classList.remove('active');},

        async saveDesign(){
            const items=$('designEditGrid').querySelectorAll('.st-design-item');
            const settings=[];
            items.forEach(item=>{
                const id=parseInt(item.dataset.id);
                const val=item.querySelector('input').value;
                if(id) settings.push({id,setting_value:val});
            });
            try{
                await API.put('/settings/themes/'+editThemeId+'/design',{settings});
                UI.showToast('تم حفظ إعدادات التصميم بنجاح','success');
                this.closeDesignModal();
                const active=allThemes.find(t=>t.is_active);
                if(active&&(active.id==editThemeId||active.id===String(editThemeId))){
                    setTimeout(()=>location.reload(),600);
                }else{
                    loadActiveDesign();
                }
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        /* ---- Delete helpers for design-tab items ---- */
        async deleteFont(fontId, themeId){
            if(!confirm('هل أنت متأكد من الحذف؟')) return;
            try{
                await API.del('/settings/fonts/'+fontId);
                UI.showToast('تم حذف الخط بنجاح','success');
                loadActiveDesign();
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        async deleteButton(buttonId, themeId){
            if(!confirm('هل أنت متأكد من الحذف؟')) return;
            try{
                await API.del('/settings/buttons/'+buttonId);
                UI.showToast('تم حذف الزر بنجاح','success');
                loadActiveDesign();
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        async deleteCard(cardId, themeId){
            if(!confirm('هل أنت متأكد من الحذف؟')) return;
            try{
                await API.del('/settings/cards/'+cardId);
                UI.showToast('تم حذف البطاقة بنجاح','success');
                loadActiveDesign();
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        async deleteDesign(designId, themeId){
            if(!confirm('هل أنت متأكد من الحذف؟')) return;
            try{
                await API.del('/settings/design/'+designId);
                UI.showToast('تم حذف إعداد التصميم بنجاح','success');
                loadActiveDesign();
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        /* ---- System Settings ---- */
        async saveSetting(btn){
            const container=btn.closest('.st-set-val');
            const input=container.querySelector('input,select');
            if(!input) return;
            const key=input.dataset.key;
            const value=input.value;
            try{
                await API.put('/settings/'+encodeURIComponent(key),{value});
                UI.showToast('تم حفظ الإعداد بنجاح','success');
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        async deleteSystemSetting(id){
            if(!confirm('هل أنت متأكد من الحذف؟')) return;
            try{
                await API.del('/settings/'+id);
                UI.showToast('تم حذف الإعداد بنجاح','success');
                loadSettings();
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        }
    };

    /* ---- Init ---- */
    document.addEventListener('DOMContentLoaded',async()=>{
        await new Promise(r=>setTimeout(r,200));
        if(window.__pageDenied) return;
        checkThemePermission();
        await loadThemes();
        loadColorsTab();
        loadActiveDesign();
        loadSettings();
    });
})();
</script>
<?php
$pageScripts = ob_get_clean();
?>
