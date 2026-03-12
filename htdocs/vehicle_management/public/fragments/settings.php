<?php
/**
 * Settings Fragment — System Settings (Admin)
 */
?>
<style>
.settings-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.setting-card{background:var(--bg-card);border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);padding:20px}
.setting-card h4{font-size:.95rem;font-weight:600;margin-bottom:8px;color:var(--text-primary)}
.setting-card p{font-size:.8rem;color:var(--text-secondary);margin-bottom:12px}
.theme-preview{display:flex;gap:12px;flex-wrap:wrap}
.theme-option{padding:16px;border-radius:12px;border:2px solid var(--border-default);cursor:pointer;text-align:center;min-width:120px;transition:all .3s}
.theme-option:hover{border-color:var(--primary-light)}
.theme-option.active{border-color:var(--primary-main);box-shadow:0 0 0 3px rgba(58,81,58,.2)}
.theme-option .theme-name{font-size:.85rem;font-weight:600;margin-top:8px}
.color-dot{width:24px;height:24px;border-radius:50%;display:inline-block;margin:2px}
</style>

<div class="page-header"><h2>الإعدادات</h2></div>

<!-- Theme Selection -->
<div class="section-card">
    <div class="section-header"><h3>المظهر والألوان</h3></div>
    <div class="theme-preview" id="themesList">
        <div class="loading-placeholder"><div class="spinner spinner-sm"></div><span>جارٍ التحميل...</span></div>
    </div>
</div>

<!-- System Settings -->
<div class="section-card" style="margin-top:20px">
    <div class="section-header"><h3>إعدادات النظام</h3></div>
    <div class="settings-grid" id="settingsGrid">
        <div class="loading-placeholder"><div class="spinner spinner-sm"></div><span>جارٍ التحميل...</span></div>
    </div>
</div>

<?php
$pageScripts = <<<'SCRIPT'
<script>
(function(){
    'use strict';
    const $=id=>document.getElementById(id);

    async function loadThemes(){
        const c=$('themesList');
        try{
            const res=await API.get('/settings/themes');
            const themes=res.data||[];
            if(!themes.length){c.innerHTML='<p>لا توجد مظاهر متاحة</p>';return;}
            let h='';
            themes.forEach(t=>{
                h+='<div class="theme-option'+(t.is_active?' active':'')+'" data-slug="'+t.slug+'" onclick="SPage.switchTheme(\''+t.slug+'\')">';
                h+='<div class="theme-name">'+UI._escapeHtml(t.name)+'</div>';
                h+='<div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px">'+UI._escapeHtml(t.description||'')+'</div>';
                h+='</div>';
            });
            c.innerHTML=h;
        }catch(e){c.innerHTML='<p style="color:var(--status-danger)">تعذر تحميل المظاهر</p>';}
    }

    async function loadSettings(){
        const c=$('settingsGrid');
        try{
            const res=await API.get('/settings');
            const settings=res.data||[];
            if(!Array.isArray(settings)||!settings.length){c.innerHTML='<p>لا توجد إعدادات</p>';return;}
            let h='';
            settings.forEach(s=>{
                h+='<div class="setting-card">';
                h+='<h4>'+UI._escapeHtml(s.setting_key)+'</h4>';
                h+='<p>'+UI._escapeHtml(s.setting_value||'—')+'</p>';
                h+='</div>';
            });
            c.innerHTML=h;
        }catch(e){c.innerHTML='<p style="color:var(--status-danger)">تعذر تحميل الإعدادات</p>';}
    }

    window.SPage={
        async switchTheme(slug){
            try{
                await API.put('/settings/theme/'+slug,{});
                UI.showToast('تم تغيير المظهر بنجاح','success');
                setTimeout(()=>location.reload(),500);
            }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
        }
    };

    document.addEventListener('DOMContentLoaded',async()=>{
        await new Promise(r=>setTimeout(r,200));
        loadThemes();
        loadSettings();
    });
})();
</script>
SCRIPT;
?>
