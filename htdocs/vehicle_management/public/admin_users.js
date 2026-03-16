const AdminUsers=(function(){
const API_BASE='/vehicle_management/api/v1/users';
const ROLES_API='/vehicle_management/api/v1/roles';
const REFS_HELPER='/vehicle_management/api/v1/references';
let currentRoles=[];
let currentLang='ar';
function log(...a){try{console.debug('[AdminUsers]',...a);}catch(e){}}
function escapeHTML(s){if(s==null)return'';return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);}
const ReferencesCascade=(function(){
async function fetchRefs(type,parent_id,lang='ar'){
let url=REFS_HELPER+'?lang='+encodeURIComponent(lang)+'&type='+encodeURIComponent(type);
if(parent_id)url+='&parent_id='+encodeURIComponent(parent_id);
const res=await fetch(url,{credentials:'same-origin'});
try{return await res.json();}catch(e){return{success:false,message:'Invalid response'};}
}
function emptySelect(sel,placeholder='-- اختر --'){
sel.innerHTML='';
const o=document.createElement('option');o.value='';o.textContent=placeholder;sel.appendChild(o);
}
async function fillSelect(sel,items,lang){
emptySelect(sel,lang==='en'?'-- Select --':'-- اختر --');
items.forEach(it=>{
const o=document.createElement('option');
o.value=it.id;
const nameField=lang==='en'?(it.name_en||it.name_ar):(it.name_ar||it.name_en);
o.textContent=nameField||it.name||('#'+it.id);
sel.appendChild(o);
});
}
function bind(depSelQuery,secSelQuery,divSelQuery,opts={}){
const lang=opts.lang||'ar';
const d=document.querySelector(depSelQuery);
const s=document.querySelector(secSelQuery);
const dv=document.querySelector(divSelQuery);
if(!d||!s||!dv)return console.warn('ReferencesCascade: select(s) not found');
(async()=>{
const j=await fetchRefs('departments',null,lang);
const items=j.departments||j.items||[];
await fillSelect(d,items,lang);
if(d.dataset.selected)d.value=d.dataset.selected;
if(d.value)d.dispatchEvent(new Event('change'));
})();
d.addEventListener('change',async function(){
const depId=this.value||null;
emptySelect(dv,lang==='en'?'-- Select --':'-- اختر --');
const j=await fetchRefs('sections',depId,lang);
const items=j.sections||j.items||[];
await fillSelect(s,items,lang);
if(s.dataset.selected){
s.value=s.dataset.selected;
s.removeAttribute('data-selected');
}
if(s.value)s.dispatchEvent(new Event('change'));
});
s.addEventListener('change',async function(){
const secId=this.value||null;
const j=await fetchRefs('divisions',secId,lang);
const items=j.divisions||j.items||[];
await fillSelect(dv,items,lang);
if(dv.dataset.selected){
dv.value=dv.dataset.selected;
dv.removeAttribute('data-selected');
}
});
}
return{bind,fillSelect,emptySelect};
})();
function ensureModalExists(){
if(document.getElementById('modalBackdrop'))return;
const backdrop=document.createElement('div');
backdrop.id='modalBackdrop';
backdrop.className='modal-backdrop';
backdrop.style.display='none';
backdrop.innerHTML=`
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
log('Modal created dynamically');
}
function modalBackdropEl(){return document.getElementById('modalBackdrop');}
function modalBodyEl(){return document.getElementById('modalBody');}
function modalTitleEl(){return document.getElementById('modalTitle');}
function showModal(){const mb=modalBackdropEl();if(mb)mb.style.display='flex';}
function closeModal(){const mb=modalBackdropEl();if(mb)mb.style.display='none';if(modalBodyEl())modalBodyEl().innerHTML='';}
async function api(path,opts={}){
opts.credentials='same-origin';
opts.headers=Object.assign({'Accept':'application/json'},opts.headers||{});
const res=await fetch(API_BASE+'/'+path,opts);
try{return await res.json();}catch(e){return{success:false,message:'Invalid response',status:res.status};}
}
async function fetchRoles(){
try{const res=await fetch(ROLES_API,{credentials:'same-origin'});const j=await res.json();if(j&&j.success)return j.roles||[];}catch(e){log('fetchRoles error',e);}return[];
}
function roleLabel(r){
const rId=parseInt(r,10);
if(rId===1)return'Super Admin';
if(rId===2)return'Admin';
const rr=currentRoles.find(x=>parseInt(x.id,10)===rId);
const name=currentLang==='ar'?(rr?.name_ar||rr?.name_en):(rr?.name_en||rr?.name_ar);
return name||('Role '+rId);
}
function renderUsers(rows){
const tbody=document.querySelector('#usersTable tbody');
if(!tbody)return;
tbody.innerHTML='';
let inactiveCount=0;
rows.forEach(u=>{
const isActive=parseInt(u.is_active||0,10)===1;
if(!isActive)inactiveCount++;
const depName=currentLang==='en'?(u.department_name_en||u.department_name_ar):(u.department_name_ar||u.department_name_en);
const secName=currentLang==='en'?(u.section_name_en||u.section_name_ar):(u.section_name_ar||u.section_name_en);
const divName=currentLang==='en'?(u.division_name_en||u.division_name_ar):(u.division_name_ar||u.division_name_en);
const statusText=currentLang==='en'?(isActive?'Active':'Inactive'):(isActive?'مفعل':'معطّل');
const toggleTitle=currentLang==='en'?(isActive?'Deactivate':'Activate'):(isActive?'تعطيل':'تفعيل');
const tr=document.createElement('tr');
tr.innerHTML=`
<td>${escapeHTML(u.display_name||u.username)}</td>
<td>${escapeHTML(u.emp_id||'')}</td>
<td>${escapeHTML(u.email||'')}</td>
<td>${escapeHTML(u.phone||'')}</td>
<td>${escapeHTML(roleLabel(u.role_id))}</td>
<td>${escapeHTML(depName||u.department_id||'')}</td>
<td>${escapeHTML(secName||u.section_id||'')}</td>
<td>${escapeHTML(divName||u.division_id||'')}</td>
<td><span style="color:${isActive?'green':'red'};font-weight:600;">${statusText}</span></td>
<td class="actions">
<button data-id="${u.id}" class="edit" title="${currentLang==='en'?'Edit':'تعديل'}"><i class="fa fa-edit"></i></button>
<button data-id="${u.id}" class="del" title="${currentLang==='en'?'Delete':'حذف'}"><i class="fa fa-trash"></i></button>
<button data-id="${u.id}" class="toggle" title="${toggleTitle}"><i class="fa fa-user-check"></i></button>
</td>
`;
tbody.appendChild(tr);
});
const inactiveBadge=document.getElementById('inactive-badge');
if(inactiveBadge){
if(inactiveCount>0){
const msg=currentLang==='en'?`${inactiveCount} Inactive User(s)`:`${inactiveCount} مستخدم(ين) غير مفعّل`;
inactiveBadge.textContent=msg;
inactiveBadge.style.display='inline-block';
}else{
inactiveBadge.style.display='none';
}
}
}
function buildForm(user){
ensureModalExists();
const lang=currentLang;
const fields=[
{key:'username',label_ar:'الاسم الظاهر',label_en:'Display Name',type:'text'},
{key:'emp_id',label_ar:'الرمز الوظيفي',label_en:'Employee ID',type:'text'},
{key:'email',label_ar:'البريد الإلكتروني',label_en:'Email',type:'email'},
{key:'phone',label_ar:'الهاتف',label_en:'Phone',type:'text'},
{key:'preferred_language',label_ar:'اللغة المفضلة',label_en:'Preferred Language',type:'select',options:[{v:'ar',t:'عربى'},{v:'en',t:'English'}]},
{key:'role_id',label_ar:'الدور',label_en:'Role',type:'select_roles'},
{key:'is_active',label_ar:'مفعل',label_en:'Is Active',type:'checkbox'},
{key:'department_id',label_ar:'الإدارة',label_en:'Department',type:'select_ref',ref:'departments',id:'depSelect'},
{key:'section_id',label_ar:'القسم',label_en:'Section',type:'select_ref',ref:'sections',id:'secSelect'},
{key:'division_id',label_ar:'الوحدة',label_en:'Division',type:'select_ref',ref:'divisions',id:'divSelect'}
];
const formElement=document.createElement('form');
formElement.id='editForm';
formElement.addEventListener('submit',(e)=>e.preventDefault());
fields.forEach(f=>{
const labelText=lang==='en'?(f.label_en||f.label_ar):(f.label_ar||f.label_en);
const initialValue=user[f.key]??'';
const row=document.createElement('div');row.className='form-row';
const label=document.createElement('label');label.textContent=labelText;
const controlWrap=document.createElement('div');controlWrap.style.flex='1';
if(f.type==='text'||f.type==='email'){
const inp=document.createElement('input');inp.type=(f.type==='email'?'email':'text');inp.name=f.key;
if(f.key==='username'){
inp.value=user.username||user.display_name||'';
}else{
inp.value=initialValue;
}
controlWrap.appendChild(inp);
}else if(f.type==='select'){
const sel=document.createElement('select');sel.name=f.key;
f.options.forEach(op=>{const o=document.createElement('option');o.value=op.v;o.textContent=op.t;if((initialValue+'')===(op.v+''))o.selected=true;sel.appendChild(o);});
controlWrap.appendChild(sel);
}else if(f.type==='select_roles'){
const sel=document.createElement('select');sel.name=f.key;
const emptyText=lang==='en'?'-- Select --':'-- اختر --';
const emptyOpt=document.createElement('option');emptyOpt.value='';emptyOpt.textContent=emptyText;sel.appendChild(emptyOpt);
(currentRoles||[]).forEach(r=>{
const o=document.createElement('option');o.value=r.id;
o.textContent=(lang==='en'?(r.name_en||r.name_ar):(r.name_ar||r.name_en)||('Role '+r.id));
if(parseInt(user.role_id||0,10)===parseInt(r.id,10))o.selected=true;sel.appendChild(o);
});
controlWrap.appendChild(sel);
}else if(f.type==='checkbox'){
const chk=document.createElement('input');chk.type='checkbox';chk.name=f.key;chk.checked=(parseInt(initialValue||0,10)===1);
controlWrap.appendChild(chk);
}else if(f.type==='select_ref'){
const sel=document.createElement('select');
sel.name=f.key;
sel.id=f.id;
sel.setAttribute('data-selected',initialValue);
const emptyOpt=document.createElement('option');emptyOpt.value='';emptyOpt.textContent=lang==='en'?'-- Select --':'-- اختر --';sel.appendChild(emptyOpt);
controlWrap.appendChild(sel);
}
row.appendChild(label);
row.appendChild(controlWrap);
formElement.appendChild(row);
});
const hid=document.createElement('input');hid.type='hidden';hid.name='id';hid.value=user.id;formElement.appendChild(hid);
modalTitleEl().textContent=(lang==='en'?'Edit User: ':'تعديل المستخدم: ')+(user.display_name||user.username||user.id);
modalBodyEl().innerHTML='';
modalBodyEl().appendChild(formElement);
ReferencesCascade.bind('#depSelect','#secSelect','#divSelect',{lang:currentLang});
}
async function submitModalForm(){
try{
const form=document.getElementById('editForm');
if(!form){log('submitModalForm: editForm not found');alert('لم يتم العثور على النموذج');return;}
const fd=new FormData(form);
Array.from(form.querySelectorAll('input[type=checkbox]')).forEach(cb=>{
if(cb.name&&!fd.has(cb.name))fd.append(cb.name,'0');
else if(cb.name)fd.set(cb.name,cb.checked?'1':'0');
});
const url=API_BASE+'/update.php';
log('POST ->',url);
const res=await fetch(url,{method:'POST',credentials:'same-origin',body:fd});
let j;
try{j=await res.json();}catch(e){j={success:false,message:'Invalid JSON or non-200 status',status:res.status};}
log('server response',j);
const successMsg=currentLang==='en'?'Saved successfully':'تم الحفظ بنجاح';
const failureMsg=currentLang==='en'?'Save failed: ':'فشل الحفظ: ';
if(j&&j.success){
alert(successMsg);
closeModal();
load();
}else{
alert(failureMsg+(j.message||(currentLang==='en'?'Check server response log':'راجع سجل استجابة الخادم')));
}
}catch(e){
console.error(e);
alert((currentLang==='en'?'Submission error: ':'خطأ أثناء الإرسال: ')+(e.message||e));
}
}
function setupDelegation(){
document.addEventListener('click',async function(e){
const btn=e.target.closest&&e.target.closest('button');
if(!btn)return;
const id=btn.getAttribute('data-id');
if(!id)return;
if(btn.classList.contains('edit')){
ensureModalExists();
modalBodyEl().innerHTML=`<div class="small">${currentLang==='en'?'Loading…':'جاري التحميل…'}</div>`;
showModal();
try{
currentRoles=await fetchRoles();
const userRes=await api('get.php?id='+encodeURIComponent(id));
if(!userRes||!userRes.success){
const errMsg=currentLang==='en'?(userRes?.message||'Error loading user data'):(userRes?.message||'خطأ في تحميل بيانات المستخدم');
modalBodyEl().innerHTML=`<div class="small" style="color:red">${escapeHTML(errMsg)}</div>`;
return;
}
buildForm(userRes.user);
}catch(err){
const errMsg=currentLang==='en'?'Error: '+(err.message||err):'خطأ: '+(err.message||err);
modalBodyEl().innerHTML=`<div class="small" style="color:red">${escapeHTML(errMsg)}</div>`;
}
return;
}
if(btn.classList.contains('del')){
const confirmMsg=currentLang==='en'?'Are you sure you want to permanently delete this user?':'هل تريد حذف المستخدم نهائياً؟';
if(!confirm(confirmMsg))return;
const fd=new FormData();fd.append('id',id);
const res=await api('delete.php',{method:'POST',body:fd});
const successMsg=currentLang==='en'?'User deleted successfully':'تم الحذف';
const failureMsg=currentLang==='en'?'Deletion failed':'فشل الحذف';
if(res&&res.success){alert(successMsg);load();}else alert(res.message||failureMsg);
return;
}
if(btn.classList.contains('toggle')){
const fd=new FormData();fd.append('id',id);
const res=await api('activate.php',{method:'POST',body:fd});
const successMsg=currentLang==='en'?'Status changed successfully':'تم التغيير';
const failureMsg=currentLang==='en'?'Operation failed':'فشل العملية';
if(res&&res.success){alert(successMsg);load();}else alert(res.message||failureMsg);
return;
}
});
}
async function load(filterEmpId=null){
const desc=document.getElementById('desc');
if(desc)desc.textContent=currentLang==='en'?'Loading…':'جاري التحميل…';
try{
if(typeof window.sessionCheck==='function'){
const s=await window.sessionCheck();
if(s&&s.success&&s.user&&s.user.preferred_language)currentLang=(s.user.preferred_language||'ar').toLowerCase();
}
currentRoles=await fetchRoles();
}catch(e){currentRoles=[];}
const res=await api('list.php');
if(!res||!res.success){
const errMsg=currentLang==='en'?(res?(res.message||'Loading error'):'Loading error'):(res?(res.message||'خطأ في التحميل'):'خطأ في التحميل');
if(desc)desc.textContent=errMsg;
renderUsers([]);
return;
}
let users=res.users||[];
if(filterEmpId){
const filterText=String(filterEmpId).trim().toLowerCase();
users=users.filter(u=>String(u.emp_id||'').toLowerCase().includes(filterText));
}
if(desc){
const total=res.users?res.users.length:0;
const displayed=users.length;
if(currentLang==='en'){
desc.textContent=`Displaying ${displayed} user(s) out of ${total} total.`;
}else{
desc.textContent=`عرض ${displayed} مستخدم(ين) من أصل ${total}.`;
}
}
renderUsers(users);
}
function filterList(empId){
load(empId);
}
function setupModalListeners(){
const backdrop=document.getElementById('modalBackdrop');
const cancelButton=document.getElementById('modalCancel');
const saveButton=document.getElementById('modalSave');
if(backdrop)backdrop.addEventListener('click',(e)=>{if(e.target===backdrop)closeModal();});
if(cancelButton)cancelButton.addEventListener('click',(e)=>{e.preventDefault();closeModal();});
if(saveButton)saveButton.addEventListener('click',(e)=>{e.preventDefault();submitModalForm();});
log('Modal listeners attached successfully in init().');
}
function init(){
ensureModalExists();
setupDelegation();
setupModalListeners();
const refreshBtn=document.getElementById('refreshBtn');
if(refreshBtn)refreshBtn.addEventListener('click',()=>load());
const applyFilterBtn=document.getElementById('applyFilterBtn');
const empIdFilter=document.getElementById('empIdFilter');
if(applyFilterBtn&&empIdFilter){
applyFilterBtn.addEventListener('click',()=>filterList(empIdFilter.value));
}
const clearFilterBtn=document.getElementById('clearFilterBtn');
if(clearFilterBtn&&empIdFilter){
clearFilterBtn.addEventListener('click',()=>{empIdFilter.value='';filterList('');});
}
load();
log('AdminUsers initialized');
}
return{init,load,filterList};
})();