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
.st-panel{display:none !important}.st-panel.active{display:block !important}
/* ---- Themes ---- */
.st-active-summary{display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,var(--primary-main),var(--primary-light,#4a8a4a));color:#fff;border-radius:12px;padding:16px 20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.12)}
.st-active-summary-icon{font-size:2rem}
.st-active-summary-info{flex:1}
.st-active-summary-label{font-size:.75rem;opacity:.85;margin-bottom:2px}
.st-active-summary-name{font-size:1.15rem;font-weight:700}
.st-active-summary-default{background:rgba(255,255,255,.2);padding:4px 14px;border-radius:20px;font-size:.75rem;font-weight:600}
.st-themes{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.st-theme-card{background:var(--bg-card);border-radius:12px;border:2px solid var(--border-default);padding:20px;min-width:240px;max-width:320px;flex:1;cursor:pointer;transition:all .3s;position:relative}
.st-theme-card:hover{border-color:var(--primary-light);box-shadow:0 4px 12px rgba(0,0,0,.1)}
.st-theme-card.active{border-color:var(--primary-main);box-shadow:0 0 0 3px rgba(58,81,58,.15);background:linear-gradient(135deg,var(--bg-card),rgba(58,81,58,.04))}
.st-theme-card .st-active-badge{position:absolute;top:10px;inset-inline-end:10px;background:var(--status-success);color:#fff;font-size:.75rem;padding:4px 12px;border-radius:20px;font-weight:700;box-shadow:0 2px 6px rgba(0,0,0,.15)}
.st-theme-card .st-default-badge{position:absolute;top:10px;inset-inline-start:10px;background:var(--status-info);color:#fff;font-size:.75rem;padding:4px 12px;border-radius:20px;font-weight:700;box-shadow:0 2px 6px rgba(0,0,0,.15)}
.st-theme-name{font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:4px}
.st-theme-desc{font-size:.8rem;color:var(--text-secondary);margin-bottom:12px}
.st-theme-colors{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:12px}
.st-color-dot{width:28px;height:28px;border-radius:50%;border:2px solid var(--border-default);display:inline-block}
.st-theme-actions{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
/* ---- Theme layout preview strips ---- */
.st-layout-preview{display:flex;flex-direction:column;gap:2px;border-radius:6px;overflow:hidden;margin-bottom:10px;border:1px solid var(--border-default)}
.st-layout-preview-row{display:flex;align-items:center;padding:4px 10px;font-size:.7rem;font-weight:600}
/* ---- Layout Colors Section ---- */
.st-layout-section{background:var(--bg-card);border-radius:12px;padding:20px;border:1px solid var(--border-default);margin-bottom:24px;box-shadow:var(--card-shadow)}
.st-layout-section-title{font-size:.95rem;font-weight:700;color:var(--text-primary);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.st-layout-live-preview{display:flex;flex-direction:column;gap:3px;border-radius:10px;overflow:hidden;margin-bottom:18px;border:1px solid var(--border-default);box-shadow:0 2px 8px rgba(0,0,0,.06)}
.st-layout-live-row{display:flex;align-items:center;justify-content:center;padding:10px 16px;font-size:.85rem;font-weight:600;transition:all .3s}
.st-layout-color-groups{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.st-layout-color-group{background:var(--bg-main);border-radius:10px;padding:16px;border:1px solid var(--border-default)}
.st-layout-color-group-title{font-size:.85rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;display:flex;align-items:center;gap:6px}
.st-layout-color-row{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.st-layout-color-row label{flex:1;font-size:.8rem;color:var(--text-secondary)}
.st-layout-color-row input[type=color]{width:42px;height:32px;border:1px solid var(--border-default);border-radius:6px;cursor:pointer;padding:2px}
.st-layout-color-row .st-lc-hex{font-size:.72rem;color:var(--text-secondary);font-family:monospace;min-width:65px}
.st-layout-save-bar{display:flex;justify-content:center;margin-top:16px}
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

<h2 data-label-ar="إدارة الإعدادات" data-label-en="Settings Management">Settings Management</h2>

<!-- Stats -->
<div class="st-stats" id="stStats">
    <div class="st-stat"><div class="st-val" id="statThemes">-</div><div class="st-lbl" data-label-ar="المظاهر" data-label-en="Themes">Themes</div></div>
    <div class="st-stat"><div class="st-val" id="statColors">-</div><div class="st-lbl" data-label-ar="الألوان" data-label-en="Colors">Colors</div></div>
    <div class="st-stat"><div class="st-val" id="statSettings">-</div><div class="st-lbl" data-label-ar="إعدادات النظام" data-label-en="System Settings">System Settings</div></div>
    <div class="st-stat"><div class="st-val" id="statActive">-</div><div class="st-lbl" data-label-ar="المظهر الحالي" data-label-en="Active Theme">Active Theme</div></div>
</div>

<!-- Tabs -->
<div class="st-tabs">
    <div class="st-tab active" data-tab="themes" data-label-ar="المظاهر" data-label-en="Themes">Themes</div>
    <div class="st-tab" data-tab="colors" data-label-ar="الألوان" data-label-en="Colors">Colors</div>
    <div class="st-tab" data-tab="fonts" data-label-ar="الخطوط" data-label-en="Fonts">Fonts</div>
    <div class="st-tab" data-tab="design" data-label-ar="إعدادات التصميم" data-label-en="Design Settings">Design Settings</div>
    <div class="st-tab" data-tab="buttons" data-label-ar="أنماط الأزرار" data-label-en="Button Styles">Button Styles</div>
    <div class="st-tab" data-tab="cards" data-label-ar="أنماط البطاقات" data-label-en="Card Styles">Card Styles</div>
    <div class="st-tab" data-tab="system" data-label-ar="إعدادات النظام" data-label-en="System Settings">System Settings</div>
</div>

<!-- Panel 1: Themes -->
<div class="st-panel active" id="panelThemes">
    <!-- Current Active Theme Summary -->
    <div class="st-active-summary" id="activeThemeSummary" style="display:none">
        <div class="st-active-summary-icon">🎨</div>
        <div class="st-active-summary-info">
            <div class="st-active-summary-label" data-label-ar="المظهر النشط حالياً" data-label-en="Currently Active Theme">Currently Active Theme</div>
            <div class="st-active-summary-name" id="activeThemeSummaryName">—</div>
        </div>
        <div class="st-active-summary-default" id="activeThemeDefaultBadge" style="display:none" data-label-ar="⭐ افتراضي" data-label-en="⭐ Default">⭐ Default</div>
    </div>
    <!-- Theme count & navigation -->
    <div class="st-section-header">
        <div style="display:flex;align-items:center;gap:12px">
            <div class="st-section-title" data-label-ar="اختيار المظهر" data-label-en="Theme Selection">Theme Selection</div>
            <span class="st-theme-count" id="themeCount" style="font-size:.8rem;color:var(--text-secondary);background:var(--bg-card);padding:2px 10px;border-radius:12px;border:1px solid var(--border-default)"></span>
        </div>
        <button class="btn-add st-theme-admin" onclick="SettingsPage.openItemModal('theme','add')" data-label-ar="➕ إضافة مظهر" data-label-en="➕ Add Theme">➕ Add Theme</button>
    </div>
    <div class="st-themes" id="themesList">
        <div class="st-empty">Loading...</div>
    </div>
</div>

<!-- Panel 2: Colors -->
<div class="st-panel" id="panelColors">
    <!-- Layout Colors Sub-Section (Header / Footer / Sidebar) -->
    <div class="st-layout-section" id="layoutColorsSection">
        <div class="st-layout-section-title">
            <span>🎨</span>
            <span data-label-ar="ألوان الهيدر والفوتر والسايدبار" data-label-en="Header, Footer &amp; Sidebar Colors">Header, Footer &amp; Sidebar Colors</span>
        </div>
        <!-- Live preview -->
        <div class="st-layout-live-preview" id="layoutLivePreview">
            <div class="st-layout-live-row" id="previewHeader" data-label-ar="شريط الهيدر" data-label-en="Header Bar">Header Bar</div>
            <div style="display:flex;min-height:50px">
                <div id="previewSidebar" style="min-width:80px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:600;padding:8px" data-label-ar="السايدبار" data-label-en="Sidebar">Sidebar</div>
                <div style="flex:1;display:flex;align-items:center;justify-content:center;background:var(--bg-main);color:var(--text-secondary);font-size:.75rem" data-label-ar="المحتوى" data-label-en="Content">Content</div>
            </div>
            <div class="st-layout-live-row" id="previewFooter" data-label-ar="شريط الفوتر" data-label-en="Footer Bar">Footer Bar</div>
        </div>
        <!-- Color pickers grouped -->
        <div class="st-layout-color-groups" id="layoutColorGroups">
            <div class="st-layout-color-group">
                <div class="st-layout-color-group-title"><span>📌</span> <span data-label-ar="الهيدر (الشريط العلوي)" data-label-en="Header (Top Bar)">Header (Top Bar)</span></div>
                <div class="st-layout-color-row">
                    <label data-label-ar="لون خلفية الهيدر" data-label-en="Header Background">Header Background</label>
                    <input type="color" id="lc_header_bg" value="#2c3e2c" onchange="SettingsPage.updateLayoutPreview()">
                    <span class="st-lc-hex" id="lc_header_bg_hex">#2c3e2c</span>
                </div>
                <div class="st-layout-color-row">
                    <label data-label-ar="لون نص الهيدر" data-label-en="Header Text">Header Text</label>
                    <input type="color" id="lc_header_text" value="#ffffff" onchange="SettingsPage.updateLayoutPreview()">
                    <span class="st-lc-hex" id="lc_header_text_hex">#ffffff</span>
                </div>
            </div>
            <div class="st-layout-color-group">
                <div class="st-layout-color-group-title"><span>📎</span> <span data-label-ar="الفوتر (الشريط السفلي)" data-label-en="Footer (Bottom Bar)">Footer (Bottom Bar)</span></div>
                <div class="st-layout-color-row">
                    <label data-label-ar="لون خلفية الفوتر" data-label-en="Footer Background">Footer Background</label>
                    <input type="color" id="lc_footer_bg" value="#ffffff" onchange="SettingsPage.updateLayoutPreview()">
                    <span class="st-lc-hex" id="lc_footer_bg_hex">#ffffff</span>
                </div>
                <div class="st-layout-color-row">
                    <label data-label-ar="لون نص الفوتر" data-label-en="Footer Text">Footer Text</label>
                    <input type="color" id="lc_footer_text" value="#6b7280" onchange="SettingsPage.updateLayoutPreview()">
                    <span class="st-lc-hex" id="lc_footer_text_hex">#6b7280</span>
                </div>
            </div>
            <div class="st-layout-color-group">
                <div class="st-layout-color-group-title"><span>📂</span> <span data-label-ar="السايدبار (القائمة الجانبية)" data-label-en="Sidebar (Side Menu)">Sidebar (Side Menu)</span></div>
                <div class="st-layout-color-row">
                    <label data-label-ar="لون نص السايدبار" data-label-en="Sidebar Text">Sidebar Text</label>
                    <input type="color" id="lc_sidebar_text" value="#ffffff" onchange="SettingsPage.updateLayoutPreview()">
                    <span class="st-lc-hex" id="lc_sidebar_text_hex">#ffffff</span>
                </div>
                <div class="st-layout-color-row">
                    <label data-label-ar="لون خلفية العنصر النشط" data-label-en="Active Item Background">Active Item Background</label>
                    <input type="color" id="lc_sidebar_active_bg" value="#3a513a" onchange="SettingsPage.updateLayoutPreview()">
                    <span class="st-lc-hex" id="lc_sidebar_active_bg_hex">#3a513a</span>
                </div>
            </div>
        </div>
        <div class="st-layout-save-bar">
            <button class="btn-save" style="background:var(--primary-main);color:#fff;border:none;padding:10px 28px;border-radius:10px;cursor:pointer;font-size:.9rem;font-weight:600" onclick="SettingsPage.saveLayoutColors()" data-label-ar="💾 حفظ ألوان التخطيط" data-label-en="💾 Save Layout Colors">💾 Save Layout Colors</button>
        </div>
    </div>

    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="ألوان المظهر النشط" data-label-en="Active Theme Colors">Active Theme Colors</div>
        <button class="btn-add" onclick="SettingsPage.addColorForActive()" data-label-ar="➕ إضافة لون" data-label-en="➕ Add Color">➕ Add Color</button>
    </div>
    <div id="colorsTabGrid" class="st-color-grid"><div class="st-empty">Loading...</div></div>
    <div style="margin-top:16px;text-align:center">
        <button class="btn-save" onclick="SettingsPage.saveColorsTab()" data-label-ar="💾 حفظ جميع الألوان" data-label-en="💾 Save All Colors">💾 Save All Colors</button>
    </div>
</div>

<!-- Panel 3: Fonts -->
<div class="st-panel" id="panelFonts">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="الخطوط" data-label-en="Fonts">Fonts</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('font','add')" data-label-ar="➕ إضافة" data-label-en="➕ Add">➕ Add</button>
    </div>
    <div id="fontsGrid" class="st-font-grid"><div class="st-empty">Loading...</div></div>
</div>

<!-- Panel 4: Design Settings -->
<div class="st-panel" id="panelDesign">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="إعدادات التصميم" data-label-en="Design Settings">Design Settings</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('design','add')" data-label-ar="➕ إضافة" data-label-en="➕ Add">➕ Add</button>
    </div>
    <div id="designGrid" class="st-design-grid"><div class="st-empty">Loading...</div></div>
</div>

<!-- Panel 5: Button Styles -->
<div class="st-panel" id="panelButtons">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="أنماط الأزرار" data-label-en="Button Styles">Button Styles</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('button','add')" data-label-ar="➕ إضافة" data-label-en="➕ Add">➕ Add</button>
    </div>
    <div id="buttonsGrid" class="st-btn-grid"><div class="st-empty">Loading...</div></div>
</div>

<!-- Panel 6: Card Styles -->
<div class="st-panel" id="panelCards">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="أنماط البطاقات" data-label-en="Card Styles">Card Styles</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('card','add')" data-label-ar="➕ إضافة" data-label-en="➕ Add">➕ Add</button>
    </div>
    <div id="cardsGrid" class="st-design-grid"><div class="st-empty">Loading...</div></div>
</div>

<!-- Panel 7: System Settings -->
<div class="st-panel" id="panelSystem">
    <div class="st-section-header">
        <div class="st-section-title" data-label-ar="إعدادات النظام" data-label-en="System Settings">System Settings</div>
        <button class="btn-add" onclick="SettingsPage.openItemModal('system','add')" data-label-ar="➕ إضافة إعداد" data-label-en="➕ Add Setting">➕ Add Setting</button>
    </div>
    <div id="systemSettings"><div class="st-empty">Loading...</div></div>
</div>

<!-- Color Editor Modal -->
<div class="st-modal" id="colorModal">
    <div class="st-modal-content">
        <div class="st-modal-header">
            <h3 id="colorModalTitle">🎨 Edit Colors</h3>
            <button class="st-modal-close" onclick="SettingsPage.closeColorModal()">✕</button>
        </div>
        <div class="st-modal-body">
            <div style="margin-bottom:16px;text-align:end">
                <button class="btn-add" id="btnAddColor" data-label-ar="➕ إضافة لون" data-label-en="➕ Add Color">➕ Add Color</button>
            </div>
            <div class="st-color-grid" id="colorGrid"></div>
        </div>
        <div class="st-modal-footer">
            <button class="btn btn-secondary" onclick="SettingsPage.closeColorModal()" data-label-ar="إغلاق" data-label-en="Close">Close</button>
            <button class="btn btn-primary" onclick="SettingsPage.saveColors()" data-label-ar="💾 حفظ الألوان" data-label-en="💾 Save Colors">💾 Save Colors</button>
        </div>
    </div>
</div>

<!-- Design Editor Modal -->
<div class="st-modal" id="designModal">
    <div class="st-modal-content">
        <div class="st-modal-header">
            <h3 id="designModalTitle">⚙️ Edit Design</h3>
            <button class="st-modal-close" onclick="SettingsPage.closeDesignModal()">✕</button>
        </div>
        <div class="st-modal-body">
            <div class="st-design-grid" id="designEditGrid"></div>
        </div>
        <div class="st-modal-footer">
            <button class="btn btn-secondary" onclick="SettingsPage.closeDesignModal()" data-label-ar="إغلاق" data-label-en="Close">Close</button>
            <button class="btn btn-primary" onclick="SettingsPage.saveDesign()" data-label-ar="💾 حفظ التصميم" data-label-en="💾 Save Design">💾 Save Design</button>
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
            <button class="btn btn-secondary" onclick="SettingsPage.closeItemModal()" data-label-ar="إلغاء" data-label-en="Cancel">Cancel</button>
            <button class="btn btn-primary" id="stItemSaveBtn" onclick="SettingsPage.saveItem()" data-label-ar="💾 حفظ" data-label-en="💾 Save">💾 Save</button>
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
            h+=buildField('name','Name','text',d.name,true);
            h+=buildField('slug','Slug','text',d.slug,true);
            h+='</div>';
            h+=buildField('description','Description','textarea',d.description);
            h+='<div class="row2">';
            h+=buildField('version','Version','text',d.version);
            h+=buildField('author','Author','text',d.author);
            h+='</div>';
            break;
        case 'color':
            h+='<div class="row2">';
            h+=buildField('setting_key','Key','text',d.setting_key,true);
            h+=buildField('setting_name','Name','text',d.setting_name,true);
            h+='</div><div class="row2">';
            h+=buildField('color_value','Color','color',d.color_value,true);
            h+=buildField('category','Category','text',d.category);
            h+='</div>';
            break;
        case 'font':
            h+='<div class="row2">';
            h+=buildField('setting_key','Key','text',d.setting_key,true);
            h+=buildField('setting_name','Name','text',d.setting_name,true);
            h+='</div><div class="row2">';
            h+=buildField('font_family','Font Family','text',d.font_family,true);
            h+=buildField('font_size','Font Size','text',d.font_size);
            h+='</div><div class="row2">';
            h+=buildField('font_weight','Font Weight','text',d.font_weight);
            h+=buildField('line_height','Line Height','text',d.line_height);
            h+='</div>';
            h+=buildField('category','Category','select',d.category||'heading',[
                {value:'heading',label:'Heading'},{value:'body',label:'Body'},{value:'ui',label:'UI'},{value:'other',label:'Other'}
            ]);
            break;
        case 'button':
            h+='<div class="row2">';
            h+=buildField('name','Name','text',d.name,true);
            h+=buildField('slug','Slug','text',d.slug,true);
            h+='</div>';
            h+=buildField('button_type','Type','select',d.button_type||'primary',[
                {value:'primary',label:'Primary'},{value:'secondary',label:'Secondary'},
                {value:'outline',label:'Outline'},{value:'ghost',label:'Ghost'},{value:'danger',label:'Danger'}
            ]);
            h+='<div class="row2">';
            h+=buildField('background_color','Background Color','color',d.background_color||'#3a513a');
            h+=buildField('text_color','Text Color','color',d.text_color||'#ffffff');
            h+='</div><div class="row2">';
            h+=buildField('border_color','Border Color','color',d.border_color||'#3a513a');
            h+=buildField('border_radius','Border Radius','text',d.border_radius||'8');
            h+='</div><div class="row2">';
            h+=buildField('padding','Padding','text',d.padding||'8px 16px');
            h+=buildField('font_size','Font Size','text',d.font_size||'14px');
            h+='</div><div class="row2">';
            h+=buildField('font_weight','Font Weight','text',d.font_weight||'600');
            h+=buildField('hover_background_color','Hover Background Color','color',d.hover_background_color||'#2c3e2c');
            h+='</div>';
            h+=buildField('hover_text_color','Hover Text Color','color',d.hover_text_color||'#ffffff');
            break;
        case 'card':
            h+='<div class="row2">';
            h+=buildField('name','Name','text',d.name,true);
            h+=buildField('slug','Slug','text',d.slug,true);
            h+='</div>';
            h+=buildField('card_type','Type','text',d.card_type||'default');
            h+='<div class="row2">';
            h+=buildField('background_color','Background Color','color',d.background_color||'#ffffff');
            h+=buildField('border_color','Border Color','color',d.border_color||'#e5e7eb');
            h+='</div><div class="row2">';
            h+=buildField('border_width','Border Width','text',d.border_width||'1');
            h+=buildField('border_radius','Border Radius','text',d.border_radius||'12');
            h+='</div><div class="row2">';
            h+=buildField('shadow_style','Shadow','text',d.shadow_style||'0 2px 8px rgba(0,0,0,0.08)');
            h+=buildField('padding','Padding','text',d.padding||'16px');
            h+='</div><div class="row2">';
            h+=buildField('hover_effect','Hover Effect','select',d.hover_effect||'none',[
                {value:'none',label:'None'},{value:'lift',label:'Lift'},{value:'glow',label:'Glow'},{value:'scale',label:'Scale'}
            ]);
            h+=buildField('text_align','Text Align','select',d.text_align||'right',[
                {value:'right',label:'Right'},{value:'left',label:'Left'},{value:'center',label:'Center'}
            ]);
            h+='</div>';
            break;
        case 'design':
            h+='<div class="row2">';
            h+=buildField('setting_key','Key','text',d.setting_key,true);
            h+=buildField('setting_name','Name','text',d.setting_name,true);
            h+='</div>';
            h+=buildField('setting_value','Value','text',d.setting_value,true);
            h+='<div class="row2">';
            h+=buildField('setting_type','Type','select',d.setting_type||'text',[
                {value:'text',label:'Text'},{value:'color',label:'Color'},{value:'number',label:'Number'},
                {value:'boolean',label:'Boolean'},{value:'json',label:'JSON'}
            ]);
            h+=buildField('category','Category','select',d.category||'layout',[
                {value:'layout',label:'Layout'},{value:'spacing',label:'Spacing'},{value:'border',label:'Border'},
                {value:'shadow',label:'Shadow'},{value:'animation',label:'Animation'},{value:'other',label:'Other'}
            ]);
            h+='</div>';
            break;
        case 'system':
            h+='<div class="row2">';
            h+=buildField('setting_key','Key','text',d.setting_key,true);
            h+=buildField('setting_value','Value','text',d.setting_value,true);
            h+='</div><div class="row2">';
            h+=buildField('setting_type','Type','select',d.setting_type||'text',[
                {value:'text',label:'Text'},{value:'color',label:'Color'},{value:'number',label:'Number'},
                {value:'boolean',label:'Boolean'},{value:'json',label:'JSON'},{value:'url',label:'URL'}
            ]);
            h+=buildField('category','Category','select',d.category||'general',[
                {value:'general',label:'General'},{value:'security',label:'Security'},{value:'appearance',label:'Appearance'},
                {value:'system',label:'System'},{value:'contact',label:'Contact'},{value:'branding',label:'Branding'}
            ]);
            h+='</div>';
            h+=buildField('description','Description','textarea',d.description);
            h+='<div class="row2">';
            h+=buildField('is_public','Public','select',d.is_public!=null?d.is_public:'0',[
                {value:'1',label:'Yes'},{value:'0',label:'No'}
            ]);
            h+=buildField('is_editable','Editable','select',d.is_editable!=null?d.is_editable:'1',[
                {value:'1',label:'Yes'},{value:'0',label:'No'}
            ]);
            h+='</div>';
            break;
        }
        return h;
    }

    function getModalTitle(type, mode){
        const titles={
            theme:   {add:i18n.t('st_add_theme_title'),     edit:i18n.t('st_edit_theme_title')},
            color:   {add:i18n.t('st_add_color_title'),     edit:i18n.t('st_edit_color_title')},
            font:    {add:i18n.t('st_add_font_title'),      edit:i18n.t('st_edit_font_title')},
            button:  {add:i18n.t('st_add_button_title'),    edit:i18n.t('st_edit_button_title')},
            card:    {add:i18n.t('st_add_card_title'),      edit:i18n.t('st_edit_card_title')},
            design:  {add:i18n.t('st_add_design_title'),    edit:i18n.t('st_edit_design_title')},
            system:  {add:i18n.t('st_add_system_title'),    edit:i18n.t('st_edit_system_title')}
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
            if(!allThemes.length){c.innerHTML='<div class="st-empty">'+i18n.t('st_no_themes')+'</div>';return;}
            const activeTheme=allThemes.find(t=>t.is_active);
            if(activeTheme){
                try{
                    const det=await API.get('/settings/themes/'+activeTheme.id);
                    activeThemeData=det.data;
                }catch(e){activeThemeData=null;}
            }
            renderThemes();
            updateStats();
        }catch(e){c.innerHTML='<div class="st-empty" style="color:var(--status-danger)">'+i18n.t('st_load_themes_error')+'</div>';}
    }

    function renderThemes(){
        const c=$('themesList');
        let h='';
        // Update active theme summary
        const activeTheme=allThemes.find(t=>t.is_active);
        const summaryEl=$('activeThemeSummary');
        if(summaryEl){
            if(activeTheme){
                summaryEl.style.display='flex';
                $('activeThemeSummaryName').textContent=activeTheme.name||'—';
                const isDefaultTheme=(allThemes.indexOf(activeTheme)===0||parseInt(activeTheme.id)===1);
                var defBadge=$('activeThemeDefaultBadge');
                if(defBadge) defBadge.style.display=isDefaultTheme?'inline-block':'none';
            }else{
                summaryEl.style.display='none';
            }
        }
        // Update theme count
        var countEl=$('themeCount');
        if(countEl){
            var activeIdx=activeTheme?allThemes.indexOf(activeTheme)+1:0;
            countEl.textContent=allThemes.length+(activeTheme?' — Active: '+activeIdx+'/'+allThemes.length:'');
        }
        allThemes.forEach((t,idx)=>{
            const isActive=!!t.is_active;
            const isDefault=(idx===0||parseInt(t.id)===1);
            h+='<div class="st-theme-card'+(isActive?' active':'')+'" data-id="'+t.id+'">';
            if(isActive) h+='<div class="st-active-badge" data-label-ar="✅ مفعّل" data-label-en="✅ Active">✅ Active</div>';
            if(isDefault) h+='<div class="st-default-badge" data-label-ar="افتراضي" data-label-en="Default">Default</div>';
            if(canManageThemes){
                h+='<div class="st-item-actions" style="top:'+(isActive||isDefault?'36':'8')+'px">';
                h+='<button class="btn-crud" onclick="event.stopPropagation();SettingsPage.editTheme('+t.id+')" title="Edit">✏️</button>';
                if(!isActive) h+='<button class="btn-crud del" onclick="event.stopPropagation();SettingsPage.deleteTheme('+t.id+')" title="Delete">🗑️</button>';
                h+='</div>';
            }
            h+='<div class="st-theme-name">'+esc(t.name)+'</div>';
            h+='<div class="st-theme-meta">'+esc(t.slug)+(t.version?' v'+esc(t.version):'')+(t.author?' — '+esc(t.author):'')+'</div>';
            h+='<div class="st-theme-desc">'+esc(t.description||'')+'</div>';
            /* Layout preview strip */
            h+='<div class="st-layout-preview" id="themeLayoutPreview'+t.id+'"></div>';
            h+='<div class="st-theme-colors" id="themeColors'+t.id+'"></div>';
            h+='<div class="st-theme-actions">';
            if(canManageThemes){
                if(!isActive) h+='<button class="btn-save" style="background:var(--primary-main);color:#fff;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:.85rem;font-weight:600" onclick="event.stopPropagation();SettingsPage.switchTheme(\''+esc(t.slug)+'\')" data-label-ar="🔄 تفعيل هذا المظهر" data-label-en="🔄 Activate This Theme">🔄 Activate This Theme</button>';
                h+='<button class="btn-save" style="background:var(--accent-gold,#c69c3f);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem" onclick="event.stopPropagation();SettingsPage.editColors('+t.id+')" data-label-ar="🎨 الألوان" data-label-en="🎨 Colors">🎨 Colors</button>';
                h+='<button class="btn-save" style="background:var(--status-info);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem" onclick="event.stopPropagation();SettingsPage.editDesign('+t.id+')" data-label-ar="⚙️ التصميم" data-label-en="⚙️ Design">⚙️ Design</button>';
            }
            h+='</div></div>';
        });
        c.innerHTML=h;
        /* Add click-to-activate on theme cards */
        if(canManageThemes){
            c.querySelectorAll('.st-theme-card').forEach(card=>{
                card.addEventListener('click',function(){
                    const id=this.dataset.id;
                    const t=allThemes.find(th=>String(th.id)===String(id));
                    if(t&&!t.is_active) SettingsPage.switchTheme(t.slug);
                });
            });
        }
        allThemes.forEach(t=>loadThemeColors(t.id));
    }

    async function loadThemeColors(themeId){
        const container=$('themeColors'+themeId);
        const previewContainer=$('themeLayoutPreview'+themeId);
        if(!container) return;
        try{
            const res=await API.get('/settings/themes/'+themeId);
            const colors=res.data?.colors||[];
            let h='';
            /* Build color dots */
            colors.forEach(c=>{
                h+='<span class="st-color-dot" style="background:'+esc(c.color_value)+'" title="'+esc(c.setting_name)+' ('+esc(c.color_value)+')"></span>';
            });
            container.innerHTML=h;
            /* Build layout preview strip from header/footer/sidebar colors */
            if(previewContainer){
                const colorMap={};
                colors.forEach(c=>{colorMap[c.setting_key]=c.color_value;});
                const hdrBg=colorMap['header_bg']||'#2c3e2c';
                const hdrTxt=colorMap['header_text']||'#ffffff';
                const ftrBg=colorMap['footer_bg']||'#ffffff';
                const ftrTxt=colorMap['footer_text']||'#6b7280';
                const sidebarBg=colorMap['bg_sidebar']||'#1a2e1a';
                const sidebarTxt=colorMap['sidebar_text']||'#ffffff';
                let ph='<div class="st-layout-preview-row" style="background:'+esc(hdrBg)+';color:'+esc(hdrTxt)+'" data-label-ar="هيدر" data-label-en="Header">Header</div>';
                ph+='<div style="display:flex;min-height:20px">';
                ph+='<div style="min-width:40px;background:'+esc(sidebarBg)+';color:'+esc(sidebarTxt)+';display:flex;align-items:center;justify-content:center;font-size:.6rem">☰</div>';
                ph+='<div style="flex:1;background:#f5f6f8"></div></div>';
                ph+='<div class="st-layout-preview-row" style="background:'+esc(ftrBg)+';color:'+esc(ftrTxt)+'" data-label-ar="فوتر" data-label-en="Footer">Footer</div>';
                previewContainer.innerHTML=ph;
            }
        }catch(e){container.innerHTML='';}
    }

    /* ================================================================
       LOAD COLORS TAB (standalone panel for active theme colors)
       ================================================================ */
    async function loadColorsTab(){
        const activeTheme=allThemes.find(t=>t.is_active);
        const grid=$('colorsTabGrid');
        if(!activeTheme){grid.innerHTML='<div class="st-empty">'+i18n.t('st_no_active_theme')+'</div>';return;}
        grid.innerHTML='<div class="st-empty">'+i18n.t('loading')+'</div>';
        try{
            const res=await API.get('/settings/themes/'+activeTheme.id);
            const colors=res.data?.colors||[];
            if(!colors.length){grid.innerHTML='<div class="st-empty">'+i18n.t('st_no_colors')+'</div>';return;}
            let h='';
            colors.forEach(c=>{
                h+='<div class="st-color-item" data-id="'+c.id+'">';
                h+='<input type="color" value="'+esc(c.color_value)+'" onchange="this.closest(\'.st-color-item\').querySelector(\'.st-color-hex\').textContent=this.value">';
                h+='<label><span class="st-color-name">'+esc(c.setting_name)+'</span><span class="st-color-hex">'+esc(c.color_value)+'</span></label>';
                h+='<div style="display:flex;gap:4px;flex-shrink:0">';
                h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'color\',\'edit\','+esc(JSON.stringify(c))+','+activeTheme.id+')" title="Edit">✏️</button>';
                h+='<button class="btn-crud del" onclick="SettingsPage.deleteColor('+c.id+','+activeTheme.id+')" title="Delete">🗑️</button>';
                h+='</div></div>';
            });
            grid.innerHTML=h;
        }catch(e){grid.innerHTML='<div class="st-empty" style="color:var(--status-danger)">'+i18n.t('st_load_colors_error')+'</div>';}
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
            $('designGrid').innerHTML='<div class="st-empty">'+i18n.t('st_load_design_error')+'</div>';
        }
    }

    function renderDesignSettings(settings, themeId){
        const c=$('designGrid');
        if(!settings.length){c.innerHTML='<div class="st-empty">'+i18n.t('st_no_design_settings')+'</div>';return;}
        let h='';
        settings.forEach(s=>{
            h+='<div class="st-design-item">';
            h+='<div class="st-item-actions">';
            h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'design\',\'edit\','+esc(JSON.stringify(s))+','+themeId+')" title="Edit">✏️</button>';
            h+='<button class="btn-crud del" onclick="SettingsPage.deleteDesign('+s.id+','+themeId+')" title="Delete">🗑️</button>';
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
        if(!fonts.length){c.innerHTML='<div class="st-empty">'+i18n.t('st_no_fonts')+'</div>';return;}
        let h='';
        fonts.forEach(f=>{
            h+='<div class="st-font-item">';
            h+='<div class="st-item-actions">';
            h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'font\',\'edit\','+esc(JSON.stringify(f))+','+themeId+')" title="Edit">✏️</button>';
            h+='<button class="btn-crud del" onclick="SettingsPage.deleteFont('+f.id+','+themeId+')" title="Delete">🗑️</button>';
            h+='</div>';
            h+='<div class="st-font-name">'+esc(f.setting_name)+'</div>';
            h+='<div class="st-font-preview" style="font-family:'+esc(f.font_family)+'">Sample Text</div>';
            h+='<div class="st-font-meta">'+esc(f.font_family)+' | '+esc(f.font_size||'-')+' | '+esc(f.font_weight||'-')+'</div>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    function renderButtons(buttons, themeId){
        const c=$('buttonsGrid');
        if(!buttons.length){c.innerHTML='<div class="st-empty">'+i18n.t('st_no_buttons')+'</div>';return;}
        let h='';
        buttons.forEach(b=>{
            h+='<div class="st-btn-item">';
            h+='<span class="st-btn-preview" style="background:'+esc(b.background_color)+';color:'+esc(b.text_color);
            if(b.border_color) h+=';border-color:'+esc(b.border_color)+';border-width:1px;border-style:solid';
            h+=';border-radius:'+(b.border_radius||4)+'px;padding:'+esc(b.padding||'8px 16px')+'">';
            h+=esc(b.name)+'</span>';
            h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'button\',\'edit\','+esc(JSON.stringify(b))+','+themeId+')" title="Edit">✏️</button>';
            h+='<button class="btn-crud del" onclick="SettingsPage.deleteButton('+b.id+','+themeId+')" title="Delete">🗑️</button>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    function renderCards(cards, themeId){
        const c=$('cardsGrid');
        if(!cards.length){c.innerHTML='<div class="st-empty">'+i18n.t('st_no_cards')+'</div>';return;}
        let h='';
        cards.forEach(card=>{
            h+='<div class="st-design-item" style="background:'+esc(card.background_color||'#fff')+';border-color:'+esc(card.border_color||'#e0e0e0');
            h+=';border-radius:'+(card.border_radius||8)+'px;padding:'+esc(card.padding||'16px')+'">';
            h+='<div class="st-item-actions">';
            h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'card\',\'edit\','+esc(JSON.stringify(card))+','+themeId+')" title="Edit">✏️</button>';
            h+='<button class="btn-crud del" onclick="SettingsPage.deleteCard('+card.id+','+themeId+')" title="Delete">🗑️</button>';
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
            if(!Array.isArray(settings)||!settings.length){c.innerHTML='<div class="st-empty">'+i18n.t('st_no_settings')+'</div>';return;}
            const groups={};
            settings.forEach(s=>{
                const cat=s.category||'general';
                if(!groups[cat]) groups[cat]=[];
                groups[cat].push(s);
            });
            let h='';
            const catLabels={general:'General',security:'Security',appearance:'Appearance',system:'System',contact:'Contact',branding:'Branding'};
            for(const[cat,items]of Object.entries(groups)){
                h+='<div class="st-settings-group">';
                h+='<h4>�� '+(catLabels[cat]||esc(cat))+'</h4>';
                h+='<div class="st-settings-grid">';
                items.forEach(s=>{
                    const editable=s.is_editable!==undefined?parseInt(s.is_editable):1;
                    h+='<div class="st-setting-card">';
                    h+='<div class="st-item-actions">';
                    h+='<button class="btn-crud del" onclick="SettingsPage.deleteSystemSetting('+s.id+')" title="Delete">🗑️</button>';
                    h+='</div>';
                    h+='<div class="st-set-key">'+esc(s.setting_key)+'</div>';
                    if(s.description) h+='<div class="st-set-desc">'+esc(s.description)+'</div>';
                    h+='<div class="st-set-val">';
                    if(s.setting_type==='boolean'){
                        h+='<select data-key="'+esc(s.setting_key)+'"'+(editable?'':' disabled')+'>';
                        h+='<option value="1"'+(s.setting_value==='1'?' selected':'')+'>Yes</option>';
                        h+='<option value="0"'+(s.setting_value==='0'?' selected':'')+'>No</option>';
                        h+='</select>';
                    }else if(s.setting_type==='color'){
                        h+='<input type="color" value="'+esc(s.setting_value)+'" data-key="'+esc(s.setting_key)+'"'+(editable?'':' disabled')+'>';
                    }else{
                        h+='<input type="text" value="'+esc(s.setting_value||'')+'" data-key="'+esc(s.setting_key)+'"'+(editable?'':' disabled')+'>';
                    }
                    if(editable) h+='<button class="btn-save" onclick="SettingsPage.saveSetting(this)" data-label-ar="حفظ" data-label-en="Save">Save</button>';
                    h+='</div></div>';
                });
                h+='</div></div>';
            }
            c.innerHTML=h;
            $('statSettings').textContent=settings.length;
        }catch(e){c.innerHTML='<div class="st-empty" style="color:var(--status-danger)">'+i18n.t('st_load_settings_error')+'</div>';}
    }

    function updateStats(){
        $('statThemes').textContent=allThemes.length;
        const active=allThemes.find(t=>t.is_active);
        $('statActive').textContent=active?active.name:'—';
    }

    /* ================================================================
       LAYOUT COLORS (Header / Footer / Sidebar)
       ================================================================ */
    const layoutColorKeys=['header_bg','header_text','footer_bg','footer_text','sidebar_text','sidebar_active_bg'];
    let layoutColorIds={};

    async function loadLayoutColors(){
        const active=allThemes.find(t=>t.is_active);
        if(!active||!activeThemeData) return;
        const colors=activeThemeData.colors||[];
        layoutColorIds={};
        colors.forEach(c=>{
            if(layoutColorKeys.includes(c.setting_key)){
                const el=$('lc_'+c.setting_key);
                const hex=$('lc_'+c.setting_key+'_hex');
                if(el){el.value=c.color_value;layoutColorIds[c.setting_key]=c.id;}
                if(hex) hex.textContent=c.color_value;
            }
        });
        updateLayoutPreviewInternal();
    }

    function updateLayoutPreviewInternal(){
        const hdrBg=($('lc_header_bg')||{}).value||'#2c3e2c';
        const hdrTxt=($('lc_header_text')||{}).value||'#ffffff';
        const ftrBg=($('lc_footer_bg')||{}).value||'#ffffff';
        const ftrTxt=($('lc_footer_text')||{}).value||'#6b7280';
        const sidTxt=($('lc_sidebar_text')||{}).value||'#ffffff';
        const sidActBg=($('lc_sidebar_active_bg')||{}).value||'#3a513a';
        const ph=$('previewHeader');
        const pf=$('previewFooter');
        const ps=$('previewSidebar');
        if(ph){ph.style.background=hdrBg;ph.style.color=hdrTxt;}
        if(pf){pf.style.background=ftrBg;pf.style.color=ftrTxt;}
        if(ps){ps.style.background=sidActBg;ps.style.color=sidTxt;}
        /* Update hex labels */
        layoutColorKeys.forEach(k=>{
            const el=$('lc_'+k);
            const hex=$('lc_'+k+'_hex');
            if(el&&hex) hex.textContent=el.value;
        });
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
                        UI.showToast(i18n.t('st_theme_created'),'success');
                    }else{
                        await API.put('/settings/themes/'+data.id,fd);
                        UI.showToast(i18n.t('st_theme_updated'),'success');
                    }
                    this.closeItemModal();
                    await loadThemes();
                }else if(type==='color'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/colors',fd);
                        UI.showToast(i18n.t('st_color_added'),'success');
                    }else{
                        await API.put('/settings/colors/'+data.id,fd);
                        UI.showToast(i18n.t('st_color_updated'),'success');
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
                        UI.showToast(i18n.t('st_font_added'),'success');
                    }else{
                        await API.put('/settings/fonts/'+data.id,fd);
                        UI.showToast(i18n.t('st_font_updated'),'success');
                    }
                    this.closeItemModal();
                    loadActiveDesign();
                }else if(type==='button'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/buttons',fd);
                        UI.showToast(i18n.t('st_button_added'),'success');
                    }else{
                        await API.put('/settings/buttons/'+data.id,fd);
                        UI.showToast(i18n.t('st_button_updated'),'success');
                    }
                    this.closeItemModal();
                    loadActiveDesign();
                }else if(type==='card'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/cards',fd);
                        UI.showToast(i18n.t('st_card_added'),'success');
                    }else{
                        await API.put('/settings/cards/'+data.id,fd);
                        UI.showToast(i18n.t('st_card_updated'),'success');
                    }
                    this.closeItemModal();
                    loadActiveDesign();
                }else if(type==='design'){
                    if(mode==='add'){
                        await API.post('/settings/themes/'+themeId+'/design',fd);
                        UI.showToast(i18n.t('st_design_added'),'success');
                    }else{
                        await API.put('/settings/design/'+data.id,fd);
                        UI.showToast(i18n.t('st_design_updated'),'success');
                    }
                    this.closeItemModal();
                    loadActiveDesign();
                }else if(type==='system'){
                    if(mode==='add'){
                        await API.post('/settings',fd);
                        UI.showToast(i18n.t('st_setting_added'),'success');
                    }
                    this.closeItemModal();
                    loadSettings();
                }
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        /* ---- Theme Operations ---- */
        editTheme(id){
            const t=allThemes.find(th=>th.id===id||th.id===String(id));
            if(!t){UI.showToast(i18n.t('st_theme_not_found'),'error');return;}
            this.openItemModal('theme','edit',{id:t.id,name:t.name,slug:t.slug,description:t.description,version:t.version,author:t.author});
        },

        async switchTheme(slug){
            var themeName=(allThemes.find(t=>t.slug===slug)||{}).name||slug;
            if(!confirm(i18n.t('st_activate_theme')+' "'+themeName+'"?')) return;
            try{
                await API.put('/settings/theme/'+slug,{});
                UI.showToast(i18n.t('st_theme_changed'),'success');
                setTimeout(()=>location.reload(),600);
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        async deleteTheme(id){
            if(!confirm(i18n.t('confirm_delete'))) return;
            try{
                await API.del('/settings/themes/'+id);
                UI.showToast(i18n.t('st_theme_deleted'),'success');
                await loadThemes();
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        /* ---- Color Modal ---- */
        async editColors(themeId){
            editThemeId=themeId;
            const theme=allThemes.find(t=>t.id===themeId||t.id===String(themeId));
            $('colorModalTitle').textContent='🎨 '+i18n.t('st_colors_of')+' '+(theme?theme.name:'');
            const grid=$('colorGrid');
            grid.innerHTML='<div class="st-empty">'+i18n.t('loading')+'</div>';
            $('colorModal').classList.add('active');
            /* Wire add-color button */
            $('btnAddColor').onclick=()=>this.openItemModal('color','add',null,themeId);
            try{
                const res=await API.get('/settings/themes/'+themeId);
                const colors=res.data?.colors||[];
                $('statColors').textContent=colors.length;
                if(!colors.length){grid.innerHTML='<div class="st-empty">'+i18n.t('st_no_colors')+'</div>';return;}
                let h='';
                colors.forEach(c=>{
                    h+='<div class="st-color-item" data-id="'+c.id+'">';
                    h+='<input type="color" value="'+esc(c.color_value)+'" onchange="this.closest(\'.st-color-item\').querySelector(\'.st-color-hex\').textContent=this.value">';
                    h+='<label><span class="st-color-name">'+esc(c.setting_name)+'</span><span class="st-color-hex">'+esc(c.color_value)+'</span></label>';
                    h+='<div style="display:flex;gap:4px;flex-shrink:0">';
                    h+='<button class="btn-crud" onclick="SettingsPage.openItemModal(\'color\',\'edit\','+esc(JSON.stringify(c))+','+themeId+')" title="Edit">✏️</button>';
                    h+='<button class="btn-crud del" onclick="SettingsPage.deleteColor('+c.id+','+themeId+')" title="Delete">🗑️</button>';
                    h+='</div></div>';
                });
                grid.innerHTML=h;
            }catch(e){grid.innerHTML='<div class="st-empty" style="color:var(--status-danger)">'+i18n.t('st_load_colors_error')+'</div>';}
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
                UI.showToast(i18n.t('st_colors_saved'),'success');
                this.closeColorModal();
                const active=allThemes.find(t=>t.is_active);
                if(active&&(active.id==editThemeId||active.id===String(editThemeId))){
                    setTimeout(()=>location.reload(),600);
                }else{
                    loadThemes();
                }
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        /* Colors Tab helpers */
        addColorForActive(){
            const active=allThemes.find(t=>t.is_active);
            if(!active){UI.showToast(i18n.t('st_no_active_theme'),'error');return;}
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
                UI.showToast(i18n.t('st_colors_saved'),'success');
                setTimeout(()=>location.reload(),600);
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        async deleteColor(colorId, themeId){
            if(!confirm(i18n.t('confirm_delete'))) return;
            try{
                await API.del('/settings/colors/'+colorId);
                UI.showToast(i18n.t('st_color_deleted'),'success');
                this.editColors(themeId);
                loadColorsTab();
                loadThemes();
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        /* ---- Design Modal ---- */
        async editDesign(themeId){
            editThemeId=themeId;
            const theme=allThemes.find(t=>t.id===themeId||t.id===String(themeId));
            $('designModalTitle').textContent='⚙️ '+i18n.t('st_design_of')+' '+(theme?theme.name:'');
            const grid=$('designEditGrid');
            grid.innerHTML='<div class="st-empty">'+i18n.t('loading')+'</div>';
            $('designModal').classList.add('active');
            try{
                const res=await API.get('/settings/themes/'+themeId);
                const settings=res.data?.design||[];
                if(!settings.length){grid.innerHTML='<div class="st-empty">'+i18n.t('st_no_design_settings')+'</div>';return;}
                let h='';
                settings.forEach(s=>{
                    h+='<div class="st-design-item" data-id="'+s.id+'">';
                    h+='<div class="st-design-cat">'+esc(s.category)+'</div>';
                    h+='<label>'+esc(s.setting_name)+'</label>';
                    h+='<input type="text" value="'+esc(s.setting_value)+'">';
                    h+='</div>';
                });
                grid.innerHTML=h;
            }catch(e){grid.innerHTML='<div class="st-empty" style="color:var(--status-danger)">'+i18n.t('st_load_design_error')+'</div>';}
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
                UI.showToast(i18n.t('st_design_saved'),'success');
                this.closeDesignModal();
                const active=allThemes.find(t=>t.is_active);
                if(active&&(active.id==editThemeId||active.id===String(editThemeId))){
                    setTimeout(()=>location.reload(),600);
                }else{
                    loadActiveDesign();
                }
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        /* ---- Delete helpers for design-tab items ---- */
        async deleteFont(fontId, themeId){
            if(!confirm(i18n.t('confirm_delete'))) return;
            try{
                await API.del('/settings/fonts/'+fontId);
                UI.showToast(i18n.t('st_font_deleted'),'success');
                loadActiveDesign();
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        async deleteButton(buttonId, themeId){
            if(!confirm(i18n.t('confirm_delete'))) return;
            try{
                await API.del('/settings/buttons/'+buttonId);
                UI.showToast(i18n.t('st_button_deleted'),'success');
                loadActiveDesign();
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        async deleteCard(cardId, themeId){
            if(!confirm(i18n.t('confirm_delete'))) return;
            try{
                await API.del('/settings/cards/'+cardId);
                UI.showToast(i18n.t('st_card_deleted'),'success');
                loadActiveDesign();
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        async deleteDesign(designId, themeId){
            if(!confirm(i18n.t('confirm_delete'))) return;
            try{
                await API.del('/settings/design/'+designId);
                UI.showToast(i18n.t('st_design_deleted'),'success');
                loadActiveDesign();
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
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
                UI.showToast(i18n.t('st_setting_saved'),'success');
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        async deleteSystemSetting(id){
            if(!confirm(i18n.t('confirm_delete'))) return;
            try{
                await API.del('/settings/'+id);
                UI.showToast(i18n.t('st_setting_deleted'),'success');
                loadSettings();
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        },

        /* ---- Layout Colors (Header/Footer/Sidebar) ---- */
        updateLayoutPreview(){
            updateLayoutPreviewInternal();
        },

        async saveLayoutColors(){
            const active=allThemes.find(t=>t.is_active);
            if(!active){UI.showToast(i18n.t('st_no_active_theme'),'error');return;}
            const colors=[];
            layoutColorKeys.forEach(k=>{
                const el=$('lc_'+k);
                if(el&&layoutColorIds[k]){
                    colors.push({id:layoutColorIds[k],color_value:el.value});
                }
            });
            if(!colors.length){UI.showToast(i18n.t('st_no_colors_to_save'),'error');return;}
            try{
                await API.put('/settings/themes/'+active.id+'/colors',{colors});
                UI.showToast(i18n.t('st_layout_colors_saved'),'success');
                setTimeout(()=>location.reload(),600);
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        }
    };

    /* ---- Init ---- */
    document.addEventListener('DOMContentLoaded',async()=>{
        await new Promise(r=>setTimeout(r,200));
        if(window.__pageDenied) return;
        checkThemePermission();
        await loadThemes();
        loadLayoutColors();
        loadColorsTab();
        loadActiveDesign();
        loadSettings();
    });
})();
</script>
<?php
$pageScripts = ob_get_clean();
?>