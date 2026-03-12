<?php
/**
 * Settings Fragment — Theme & System Settings Management (Admin)
 * Loaded inside dashboard.php shell.
 *
 * Features:
 * - Theme selector with color palette preview
 * - Color editor modal for theme customization
 * - Design settings editor
 * - System settings editor with editable fields grouped by category
 */
?>
<style>
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
.st-theme-actions{display:flex;gap:8px;margin-top:8px}
/* ---- Color Editor ---- */
.st-color-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
.st-color-item{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-main);border-radius:10px;border:1px solid var(--border-default)}
.st-color-item label{font-size:.8rem;color:var(--text-secondary);flex:1}
.st-color-item .st-color-name{font-size:.85rem;font-weight:600;color:var(--text-primary);display:block}
.st-color-item input[type=color]{width:42px;height:32px;border:1px solid var(--border-default);border-radius:6px;cursor:pointer;padding:2px}
.st-color-item .st-color-hex{font-size:.75rem;color:var(--text-secondary);font-family:monospace}
/* ---- Design Settings ---- */
.st-design-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.st-design-item{background:var(--bg-card);padding:16px;border-radius:10px;border:1px solid var(--border-default)}
.st-design-item label{display:block;font-size:.8rem;font-weight:600;color:var(--text-primary);margin-bottom:6px}
.st-design-item input{width:100%;padding:8px 12px;border:1px solid var(--border-default);border-radius:8px;font-size:.85rem;background:var(--bg-main);color:var(--text-primary)}
.st-design-item .st-design-cat{font-size:.7rem;color:var(--text-secondary);margin-bottom:4px;text-transform:uppercase}
/* ---- System Settings ---- */
.st-settings-group{margin-bottom:24px}
.st-settings-group h4{font-size:.95rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border-default)}
.st-settings-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.st-setting-card{background:var(--bg-card);padding:16px;border-radius:10px;border:1px solid var(--border-default);box-shadow:var(--card-shadow)}
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
.st-font-item{background:var(--bg-card);padding:16px;border-radius:10px;border:1px solid var(--border-default)}
.st-font-item .st-font-name{font-size:.85rem;font-weight:600;color:var(--text-primary)}
.st-font-item .st-font-preview{margin-top:8px;padding:8px;background:var(--bg-main);border-radius:6px;font-size:.85rem;color:var(--text-secondary)}
.st-font-item .st-font-meta{font-size:.7rem;color:var(--text-secondary);margin-top:6px}
.st-btn-grid{display:flex;gap:12px;flex-wrap:wrap}
.st-btn-preview{display:inline-block;padding:8px 18px;border-radius:6px;font-size:.85rem;font-weight:600;border:1px solid transparent;cursor:default;transition:all .2s}
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
    <div class="st-tab active" data-tab="themes" data-label-ar="المظاهر والألوان" data-label-en="Themes & Colors">المظاهر والألوان</div>
    <div class="st-tab" data-tab="design" data-label-ar="تصميم الواجهة" data-label-en="Interface Design">تصميم الواجهة</div>
    <div class="st-tab" data-tab="system" data-label-ar="إعدادات النظام" data-label-en="System Settings">إعدادات النظام</div>
</div>

<!-- Panel: Themes & Colors -->
<div class="st-panel active" id="panelThemes">
    <div class="st-section-title" data-label-ar="اختيار المظهر" data-label-en="Theme Selection">اختيار المظهر</div>
    <div class="st-themes" id="themesList">
        <div class="st-empty">جارٍ التحميل...</div>
    </div>
</div>

<!-- Panel: Design -->
<div class="st-panel" id="panelDesign">
    <div class="st-section-title" data-label-ar="إعدادات التصميم" data-label-en="Design Settings">إعدادات التصميم</div>
    <div id="designGrid" class="st-design-grid"><div class="st-empty">جارٍ التحميل...</div></div>

    <div class="st-section-title" style="margin-top:24px" data-label-ar="الخطوط" data-label-en="Fonts">الخطوط</div>
    <div id="fontsGrid" class="st-font-grid"><div class="st-empty">جارٍ التحميل...</div></div>

    <div class="st-section-title" style="margin-top:24px" data-label-ar="أنماط الأزرار" data-label-en="Button Styles">أنماط الأزرار</div>
    <div id="buttonsGrid" class="st-btn-grid"><div class="st-empty">جارٍ التحميل...</div></div>

    <div class="st-section-title" style="margin-top:24px" data-label-ar="أنماط البطاقات" data-label-en="Card Styles">أنماط البطاقات</div>
    <div id="cardsGrid" class="st-design-grid"><div class="st-empty">جارٍ التحميل...</div></div>
</div>

<!-- Panel: System Settings -->
<div class="st-panel" id="panelSystem">
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

<?php
ob_start();
?>
<script>
(function(){
    'use strict';
    const $=id=>document.getElementById(id);
    const esc=s=>UI._escapeHtml(s||'');
    let allThemes=[], activeThemeData=null, editThemeId=null;

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

    /* ---- Load Themes ---- */
    async function loadThemes(){
        const c=$('themesList');
        try{
            const res=await API.get('/settings/themes');
            allThemes=res.data||[];
            if(!allThemes.length){c.innerHTML='<div class="st-empty">لا توجد مظاهر متاحة</div>';return;}
            /* Also get active theme detail */
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
            const isActive=t.is_active?true:false;
            h+='<div class="st-theme-card'+(isActive?' active':'')+'" data-id="'+t.id+'">';
            if(isActive) h+='<div class="st-active-badge" data-label-ar="مفعّل" data-label-en="Active">مفعّل</div>';
            h+='<div class="st-theme-name">'+esc(t.name)+'</div>';
            h+='<div class="st-theme-desc">'+esc(t.description||'')+'</div>';
            /* Color dots preview - load from detail if available */
            h+='<div class="st-theme-colors" id="themeColors'+t.id+'"></div>';
            h+='<div class="st-theme-actions">';
            if(!isActive) h+='<button class="btn-save" style="background:var(--primary-main);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem" onclick="SettingsPage.switchTheme(\''+esc(t.slug)+'\')" data-label-ar="تفعيل" data-label-en="Activate">تفعيل</button>';
            h+='<button class="btn-save" style="background:var(--accent-gold,#c69c3f);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem" onclick="SettingsPage.editColors('+t.id+')" data-label-ar="🎨 الألوان" data-label-en="🎨 Colors">🎨 الألوان</button>';
            h+='<button class="btn-save" style="background:var(--status-info);color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.8rem" onclick="SettingsPage.editDesign('+t.id+')" data-label-ar="⚙️ التصميم" data-label-en="⚙️ Design">⚙️ التصميم</button>';
            h+='</div></div>';
        });
        c.innerHTML=h;
        /* Load color previews for each theme */
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

    /* ---- Load Active Theme Design Details ---- */
    async function loadActiveDesign(){
        const activeTheme=allThemes.find(t=>t.is_active);
        if(!activeTheme) return;
        try{
            const res=await API.get('/settings/themes/'+activeTheme.id);
            const data=res.data;
            renderDesignSettings(data.design||[]);
            renderFonts(data.fonts||[]);
            renderButtons(data.buttons||[]);
            renderCards(data.cards||[]);
        }catch(e){
            $('designGrid').innerHTML='<div class="st-empty">تعذر تحميل إعدادات التصميم</div>';
        }
    }

    function renderDesignSettings(settings){
        const c=$('designGrid');
        if(!settings.length){c.innerHTML='<div class="st-empty">لا توجد إعدادات تصميم</div>';return;}
        let h='';
        settings.forEach(s=>{
            h+='<div class="st-design-item">';
            h+='<div class="st-design-cat">'+esc(s.category)+'</div>';
            h+='<label>'+esc(s.setting_name)+'</label>';
            h+='<input type="text" value="'+esc(s.setting_value)+'" disabled>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    function renderFonts(fonts){
        const c=$('fontsGrid');
        if(!fonts.length){c.innerHTML='<div class="st-empty">لا توجد إعدادات خطوط</div>';return;}
        let h='';
        fonts.forEach(f=>{
            h+='<div class="st-font-item">';
            h+='<div class="st-font-name">'+esc(f.setting_name)+'</div>';
            h+='<div class="st-font-preview" style="font-family:'+esc(f.font_family)+'">نص عربي تجريبي — Sample Text</div>';
            h+='<div class="st-font-meta">'+esc(f.font_family)+' | '+esc(f.font_size||'-')+' | '+esc(f.font_weight||'-')+'</div>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    function renderButtons(buttons){
        const c=$('buttonsGrid');
        if(!buttons.length){c.innerHTML='<div class="st-empty">لا توجد أنماط أزرار</div>';return;}
        let h='';
        buttons.forEach(b=>{
            const hoverBg=b.hover_background_color||b.background_color;
            h+='<span class="st-btn-preview" style="background:'+esc(b.background_color)+';color:'+esc(b.text_color);
            if(b.border_color) h+=';border-color:'+esc(b.border_color)+';border-width:1px;border-style:solid';
            h+=';border-radius:'+(b.border_radius||4)+'px;padding:'+esc(b.padding||'8px 16px')+'">';
            h+=esc(b.name)+'</span>';
        });
        c.innerHTML=h;
    }

    function renderCards(cards){
        const c=$('cardsGrid');
        if(!cards.length){c.innerHTML='<div class="st-empty">لا توجد أنماط بطاقات</div>';return;}
        let h='';
        cards.forEach(card=>{
            h+='<div class="st-design-item" style="background:'+esc(card.background_color||'#fff')+';border-color:'+esc(card.border_color||'#e0e0e0');
            h+=';border-radius:'+(card.border_radius||8)+'px;padding:'+esc(card.padding||'16px')+'">';
            h+='<label style="color:var(--text-primary)">'+esc(card.name)+'</label>';
            h+='<div class="st-font-meta">'+esc(card.card_type)+' | shadow: '+esc(card.shadow_style||'none')+' | hover: '+esc(card.hover_effect||'none')+'</div>';
            h+='</div>';
        });
        c.innerHTML=h;
    }

    /* ---- Load System Settings ---- */
    async function loadSettings(){
        const c=$('systemSettings');
        try{
            const res=await API.get('/settings');
            const settings=res.data||[];
            if(!Array.isArray(settings)||!settings.length){c.innerHTML='<div class="st-empty">لا توجد إعدادات</div>';return;}
            /* Group by category */
            const groups={};
            settings.forEach(s=>{
                const cat=s.category||'عام';
                if(!groups[cat]) groups[cat]=[];
                groups[cat].push(s);
            });
            let h='';
            const catLabels={general:'عام',security:'الأمان',appearance:'المظهر',system:'النظام',contact:'التواصل'};
            for(const[cat,items]of Object.entries(groups)){
                h+='<div class="st-settings-group">';
                h+='<h4>📁 '+(catLabels[cat]||esc(cat))+'</h4>';
                h+='<div class="st-settings-grid">';
                items.forEach(s=>{
                    const editable=s.is_editable!==undefined?parseInt(s.is_editable):1;
                    h+='<div class="st-setting-card">';
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

    /* ---- Color Modal ---- */
    window.SettingsPage={
        async switchTheme(slug){
            try{
                await API.put('/settings/theme/'+slug,{});
                UI.showToast('تم تغيير المظهر بنجاح','success');
                setTimeout(()=>location.reload(),600);
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

        async editColors(themeId){
            editThemeId=themeId;
            const theme=allThemes.find(t=>t.id===themeId||t.id===String(themeId));
            $('colorModalTitle').textContent='🎨 ألوان: '+(theme?theme.name:'');
            const grid=$('colorGrid');
            grid.innerHTML='<div class="st-empty">جارٍ التحميل...</div>';
            $('colorModal').classList.add('active');
            try{
                const res=await API.get('/settings/themes/'+themeId);
                const colors=res.data?.colors||[];
                $('statColors').textContent=colors.length;
                if(!colors.length){grid.innerHTML='<div class="st-empty">لا توجد ألوان</div>';return;}
                let h='';
                colors.forEach(c=>{
                    h+='<div class="st-color-item" data-id="'+c.id+'">';
                    h+='<input type="color" value="'+esc(c.color_value)+'" onchange="this.nextElementSibling.querySelector(\'.st-color-hex\').textContent=this.value">';
                    h+='<label><span class="st-color-name">'+esc(c.setting_name)+'</span><span class="st-color-hex">'+esc(c.color_value)+'</span></label>';
                    h+='</div>';
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
                /* Reload if editing active theme */
                const active=allThemes.find(t=>t.is_active);
                if(active&&(active.id==editThemeId||active.id===String(editThemeId))){
                    setTimeout(()=>location.reload(),600);
                }else{
                    loadThemes();
                }
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        },

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
        }
    };

    /* ---- Init ---- */
    document.addEventListener('DOMContentLoaded',async()=>{
        await new Promise(r=>setTimeout(r,200));
        await loadThemes();
        loadActiveDesign();
        loadSettings();
    });
})();
</script>
<?php
$pageScripts = ob_get_clean();
?>
