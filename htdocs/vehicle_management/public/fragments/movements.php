<?php
/**
 * Movements Fragment — Vehicle Movement Tracking
 * Full CRUD with geolocation, photos, vehicle condition & fuel tracking.
 */
?>
<style>
.mv-stats{display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.mv-stat{flex:1;min-width:140px;background:var(--bg-card,#fff);border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.mv-stat .num{font-size:1.8rem;font-weight:700;color:var(--primary-main,#1a5276)}
.mv-stat .lbl{font-size:.85rem;color:var(--text-secondary,#666);margin-top:4px}
.mv-toolbar{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.mv-toolbar .search-box{flex:1;min-width:200px;position:relative}
.mv-toolbar .search-box input{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem}
.mv-toolbar .search-box .ico{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#999}
.mv-toolbar select{padding:10px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.9rem}
.mv-toolbar .btn-add{margin-inline-start:auto}
.mv-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--bg-card,#fff);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.mv-table th{background:var(--primary-dark,#1a5276);color:#fff;padding:12px 14px;font-size:.85rem;white-space:nowrap}
.mv-table td{padding:10px 14px;border-bottom:1px solid var(--border-default,#eee);font-size:.9rem}
.mv-table tr:hover td{background:rgba(26,82,118,.04)}
.mv-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600}
.mv-badge.pickup{background:#d4edda;color:#155724}
.mv-badge.return{background:#fff3cd;color:#856404}
.mv-badge.clean{background:#d4edda;color:#155724}
.mv-badge.acceptable{background:#fff3cd;color:#856404}
.mv-badge.damaged{background:#f8d7da;color:#721c24}
.mv-actions button{background:none;border:none;cursor:pointer;font-size:1.1rem;padding:4px}
.mv-empty{text-align:center;padding:60px 20px;color:#999}
.mv-empty .ico{font-size:3rem;margin-bottom:12px}
/* Modal */
.mv-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.mv-modal-bg.show{display:flex}
.mv-modal{background:var(--bg-card,#fff);border-radius:16px;width:95%;max-width:640px;max-height:90vh;overflow-y:auto;padding:0}
.mv-modal .modal-hd{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border-default,#eee);position:sticky;top:0;background:var(--bg-card,#fff);z-index:1}
.mv-modal .modal-hd h3{margin:0;font-size:1.1rem}
.mv-modal .modal-hd .close{background:none;border:none;font-size:1.5rem;cursor:pointer;color:#999}
.mv-modal .modal-bd{padding:20px}
.mv-form .fg{margin-bottom:16px}
.mv-form label{display:block;font-weight:600;font-size:.85rem;margin-bottom:6px;color:var(--text-secondary,#555)}
.mv-form input,.mv-form select,.mv-form textarea{width:100%;padding:10px 12px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem;box-sizing:border-box}
.mv-form textarea{resize:vertical;min-height:70px}
.mv-form .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mv-form .loc-btns{display:flex;gap:8px;margin-bottom:8px}
.mv-form .loc-btns button{padding:8px 14px;border:none;border-radius:8px;cursor:pointer;font-size:.85rem}
.mv-form .loc-btns .get-loc{background:var(--primary-main,#1a5276);color:#fff}
.mv-form .loc-btns .open-map{background:var(--accent-gold,#c9a961);color:#fff}
.mv-form .loc-btns .clear-loc{background:#e74c3c;color:#fff}
#mvPhotosPreview{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
#mvPhotosPreview .thumb{width:80px;height:80px;border-radius:8px;object-fit:cover;border:2px solid var(--border-default,#ddd)}
#mvPhotoInput{display:none}
.mv-form .photo-area{border:2px dashed var(--border-default,#ccc);border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:border-color .2s}
.mv-form .photo-area:hover{border-color:var(--primary-main,#1a5276)}
.mv-form .modal-ft{display:flex;gap:12px;padding:16px 20px;border-top:1px solid var(--border-default,#eee);justify-content:flex-end;position:sticky;bottom:0;background:var(--bg-card,#fff)}
/* Detail modal */
.mv-detail .d-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-default,#f0f0f0)}
.mv-detail .d-row .d-lbl{color:var(--text-secondary,#777);font-size:.85rem}
.mv-detail .d-row .d-val{font-weight:600}
.mv-detail .d-photos{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.mv-detail .d-photos img{width:100px;height:80px;border-radius:8px;object-fit:cover;cursor:pointer}
.mv-page{display:flex;justify-content:center;gap:8px;margin-top:16px}
.mv-page button{padding:6px 14px;border:1px solid var(--border-default,#ddd);border-radius:6px;background:#fff;cursor:pointer}
.mv-page button.active{background:var(--primary-main,#1a5276);color:#fff;border-color:var(--primary-main)}
.loc-status{font-size:.8rem;color:var(--text-secondary,#777);margin-top:4px}
</style>

<div class="page-header"><h2 data-lang-key="movements">Vehicle Movements</h2></div>

<!-- Stats -->
<div class="mv-stats">
    <div class="mv-stat"><div class="num" id="mvStatTotal">0</div><div class="lbl" data-lang-key="total">Total</div></div>
    <div class="mv-stat"><div class="num" id="mvStatPickup">0</div><div class="lbl" data-lang-key="operation_type_pickup">Pickup</div></div>
    <div class="mv-stat"><div class="num" id="mvStatReturn">0</div><div class="lbl" data-lang-key="operation_type_return">Return</div></div>
</div>

<!-- Toolbar -->
<div class="mv-toolbar">
    <div class="search-box">
        <span class="ico">🔍</span>
        <input type="text" id="mvSearch" placeholder="Search by vehicle code or employee...">
    </div>
    <select id="mvFilterType">
        <option value="">All Types</option>
        <option value="pickup">Pickup</option>
        <option value="return">Return</option>
    </select>
    <select id="mvFilterCondition">
        <option value="">All Conditions</option>
        <option value="clean">Clean</option>
        <option value="acceptable">Acceptable</option>
        <option value="damaged">Damaged</option>
    </select>
    <button class="btn btn-primary btn-sm btn-add" id="mvBtnAdd">➕ Add Movement</button>
</div>

<!-- Table -->
<div style="overflow-x:auto">
<table class="mv-table">
    <thead><tr>
        <th>#</th>
        <th data-lang-key="vehicle_code_label">Vehicle Code</th>
        <th data-lang-key="operation_type_label">Type</th>
        <th data-lang-key="performed_by_label">Performed By</th>
        <th>Date/Time</th>
        <th>Condition</th>
        <th>Fuel</th>
        <th>📍</th>
        <th data-lang-key="actions">Actions</th>
    </tr></thead>
    <tbody id="mvTableBody"></tbody>
</table>
</div>
<div class="mv-page" id="mvPagination"></div>

<!-- Add/Edit Modal -->
<div class="mv-modal-bg" id="mvModal">
    <div class="mv-modal">
        <div class="modal-hd">
            <h3 id="mvModalTitle">➕ Add Movement</h3>
            <button class="close" id="mvModalClose">&times;</button>
        </div>
        <div class="modal-bd">
            <form class="mv-form" id="mvForm">
                <input type="hidden" id="mvId">
                <div class="row2">
                    <div class="fg">
                        <label data-lang-key="vehicle_code_label">Vehicle Code *</label>
                        <input type="text" id="mvVehicleCode" required>
                    </div>
                    <div class="fg">
                        <label data-lang-key="operation_type_label">Operation Type *</label>
                        <select id="mvOperationType" required>
                            <option value="">-- Select --</option>
                            <option value="pickup">Pickup</option>
                            <option value="return">Return</option>
                        </select>
                    </div>
                </div>
                <div class="fg">
                    <label data-lang-key="performed_by_label">Performed By *</label>
                    <input type="text" id="mvPerformedBy" required>
                </div>
                <div class="row2">
                    <div class="fg">
                        <label>Vehicle Condition</label>
                        <select id="mvCondition">
                            <option value="">-- Select --</option>
                            <option value="clean">Clean</option>
                            <option value="acceptable">Acceptable</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Fuel Level</label>
                        <select id="mvFuel">
                            <option value="">-- Select --</option>
                            <option value="full">Full</option>
                            <option value="three_quarter">3/4</option>
                            <option value="half">Half</option>
                            <option value="quarter">1/4</option>
                            <option value="empty">Empty</option>
                        </select>
                    </div>
                </div>
                <!-- Location -->
                <div class="fg">
                    <label>📍 Location</label>
                    <div class="loc-btns">
                        <button type="button" class="get-loc" id="mvGetLoc">📍 Get Location</button>
                        <button type="button" class="open-map" id="mvOpenMap">🗺️ Map</button>
                        <button type="button" class="clear-loc" id="mvClearLoc">🗑️ Clear</button>
                    </div>
                    <div class="row2">
                        <div><input type="number" id="mvLat" step="0.00000001" placeholder="Latitude"></div>
                        <div><input type="number" id="mvLng" step="0.00000001" placeholder="Longitude"></div>
                    </div>
                    <div class="loc-status" id="mvLocStatus"></div>
                </div>
                <div class="fg">
                    <label data-lang-key="notes_label">Notes</label>
                    <textarea id="mvNotes" rows="3"></textarea>
                </div>
                <!-- Photos -->
                <div class="fg">
                    <label>📷 Photos (max 6)</label>
                    <div class="photo-area" id="mvPhotoArea">
                        <p>Click or drag photos here</p>
                        <input type="file" id="mvPhotoInput" accept="image/*" multiple>
                    </div>
                    <div id="mvPhotosPreview"></div>
                </div>
            </form>
        </div>
        <div class="modal-ft">
            <button class="btn btn-ghost" id="mvCancelBtn">Cancel</button>
            <button class="btn btn-primary" id="mvSaveBtn">💾 Save</button>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="mv-modal-bg" id="mvDetailModal">
    <div class="mv-modal">
        <div class="modal-hd">
            <h3 id="mvDetailTitle">Movement Details</h3>
            <button class="close" id="mvDetailClose">&times;</button>
        </div>
        <div class="modal-bd mv-detail" id="mvDetailBody"></div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    const $=id=>document.getElementById(id);
    const esc=s=>{const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;};
    let allMovements=[], filteredMovements=[], currentPage=1, perPage=15, pendingPhotos=[];

    /* ---- Load ---- */
    async function loadMovements(){
        try{
            const res=await API.get('/movements');
            allMovements=(res.data||res)||[];
        }catch(e){allMovements=[];}
        applyFilters();
        updateStats();
    }

    function updateStats(){
        const d=allMovements;
        $('mvStatTotal').textContent=d.length;
        $('mvStatPickup').textContent=d.filter(m=>m.operation_type==='pickup').length;
        $('mvStatReturn').textContent=d.filter(m=>m.operation_type==='return').length;
    }

    function applyFilters(){
        const q=($('mvSearch').value||'').toLowerCase();
        const t=$('mvFilterType').value;
        const c=$('mvFilterCondition').value;
        filteredMovements=allMovements.filter(m=>{
            if(t && m.operation_type!==t) return false;
            if(c && m.vehicle_condition!==c) return false;
            if(q && !((m.vehicle_code||'').toLowerCase().includes(q)||(m.performed_by||'').toLowerCase().includes(q))) return false;
            return true;
        });
        currentPage=1;
        render();
    }

    function render(){
        const start=(currentPage-1)*perPage;
        const page=filteredMovements.slice(start,start+perPage);
        const tbody=$('mvTableBody');
        if(!page.length){
            tbody.innerHTML='<tr><td colspan="9"><div class="mv-empty"><div class="ico">🔄</div><p>No movements recorded yet</p></div></td></tr>';
            $('mvPagination').innerHTML='';
            return;
        }
        const condLabel=c=>c==='clean'?'Clean':c==='acceptable'?'Acceptable':c==='damaged'?'Damaged':'—';
        const fuelLabel=f=>{const m={full:'Full',three_quarter:'3/4',half:'Half',quarter:'1/4',empty:'Empty'};return m[f]||'—';};
        const typeLabel=t=>t==='pickup'?'Pickup':'Return';
        let h='';
        page.forEach((m,i)=>{
            const hasLoc=m.latitude&&m.longitude;
            h+='<tr>';
            h+='<td>'+(start+i+1)+'</td>';
            h+='<td><strong>'+esc(m.vehicle_code)+'</strong></td>';
            h+='<td><span class="mv-badge '+m.operation_type+'">'+typeLabel(m.operation_type)+'</span></td>';
            h+='<td>'+esc(m.performed_by)+'</td>';
            h+='<td>'+esc((m.movement_datetime||'').replace('T',' ').substring(0,16))+'</td>';
            h+='<td>'+(m.vehicle_condition?'<span class="mv-badge '+m.vehicle_condition+'">'+condLabel(m.vehicle_condition)+'</span>':'—')+'</td>';
            h+='<td>'+fuelLabel(m.fuel_level)+'</td>';
            h+='<td>'+(hasLoc?'<a href="https://www.google.com/maps?q='+m.latitude+','+m.longitude+'" target="_blank" title="Open Map">📍</a>':'—')+'</td>';
            h+='<td class="mv-actions">';
            h+='<button onclick="MvPage.view('+m.id+')" title="View">👁</button>';
            h+='<button onclick="MvPage.edit('+m.id+')" title="Edit">✏️</button>';
            h+='<button onclick="MvPage.del('+m.id+')" title="Delete">🗑️</button>';
            h+='</td></tr>';
        });
        tbody.innerHTML=h;
        renderPagination();
    }

    function renderPagination(){
        const total=Math.ceil(filteredMovements.length/perPage);
        if(total<=1){$('mvPagination').innerHTML='';return;}
        let h='';
        for(let i=1;i<=total;i++){
            h+='<button class="'+(i===currentPage?'active':'')+'" onclick="MvPage.goPage('+i+')">'+i+'</button>';
        }
        $('mvPagination').innerHTML=h;
    }

    /* ---- Modal ---- */
    function openModal(title){
        $('mvModalTitle').textContent=title||'Add Movement';
        $('mvModal').classList.add('show');
        pendingPhotos=[];
        $('mvPhotosPreview').innerHTML='';
    }
    function closeModal(){$('mvModal').classList.remove('show');}

    $('mvBtnAdd').addEventListener('click',()=>{
        $('mvForm').reset();$('mvId').value='';
        openModal('➕ Add Movement');
    });
    $('mvModalClose').addEventListener('click',closeModal);
    $('mvCancelBtn').addEventListener('click',closeModal);

    /* ---- Geolocation ---- */
    $('mvGetLoc').addEventListener('click',()=>{
        if(!navigator.geolocation){$('mvLocStatus').textContent='Geolocation not supported';return;}
        $('mvLocStatus').textContent='Getting location...';
        navigator.geolocation.getCurrentPosition(pos=>{
            $('mvLat').value=pos.coords.latitude.toFixed(8);
            $('mvLng').value=pos.coords.longitude.toFixed(8);
            $('mvLocStatus').textContent='✅ Location acquired';
        },err=>{
            $('mvLocStatus').textContent='❌ '+err.message;
        },{enableHighAccuracy:true,timeout:15000});
    });
    $('mvOpenMap').addEventListener('click',()=>{
        const lat=$('mvLat').value,lng=$('mvLng').value;
        if(lat&&lng) window.open('https://www.google.com/maps?q='+lat+','+lng,'_blank');
    });
    $('mvClearLoc').addEventListener('click',()=>{$('mvLat').value='';$('mvLng').value='';$('mvLocStatus').textContent='';});

    /* ---- Photos ---- */
    $('mvPhotoArea').addEventListener('click',()=>$('mvPhotoInput').click());
    $('mvPhotoArea').addEventListener('dragover',e=>{e.preventDefault();e.currentTarget.style.borderColor='var(--primary-main,#1a5276)';});
    $('mvPhotoArea').addEventListener('dragleave',e=>{e.currentTarget.style.borderColor='';});
    $('mvPhotoArea').addEventListener('drop',e=>{
        e.preventDefault();e.currentTarget.style.borderColor='';
        handleFiles(e.dataTransfer.files);
    });
    $('mvPhotoInput').addEventListener('change',e=>handleFiles(e.target.files));

    function handleFiles(files){
        const remaining=6-pendingPhotos.length;
        const arr=Array.from(files).slice(0,remaining);
        arr.forEach(f=>{
            if(!f.type.startsWith('image/'))return;
            const reader=new FileReader();
            reader.onload=e=>{
                pendingPhotos.push(e.target.result);
                renderPhotosPrev();
            };
            reader.readAsDataURL(f);
        });
    }
    function renderPhotosPrev(){
        $('mvPhotosPreview').innerHTML=pendingPhotos.map((p,i)=>{
            var safe=p.replace(/[^a-zA-Z0-9+/=:;,]/g,'');
            return '<img src="'+safe+'" class="thumb" onclick="MvPage.removePhoto('+i+')">';
        }).join('');
    }

    /* ---- Filters ---- */
    $('mvSearch').addEventListener('input',applyFilters);
    $('mvFilterType').addEventListener('change',applyFilters);
    $('mvFilterCondition').addEventListener('change',applyFilters);

    /* ---- Save ---- */
    $('mvSaveBtn').addEventListener('click',async()=>{
        const id=$('mvId').value;
        const data={
            vehicle_code:$('mvVehicleCode').value.trim(),
            operation_type:$('mvOperationType').value,
            performed_by:$('mvPerformedBy').value.trim(),
            vehicle_condition:$('mvCondition').value||null,
            fuel_level:$('mvFuel').value||null,
            latitude:$('mvLat').value||null,
            longitude:$('mvLng').value||null,
            notes:$('mvNotes').value.trim(),
        };
        if(!data.vehicle_code||!data.operation_type||!data.performed_by){
            UI.showToast('Please fill required fields','error');return;
        }
        try{
            let res;
            if(id){
                res=await API.put('/movements/'+id,data);
                UI.showToast('Movement updated','success');
            }else{
                res=await API.post('/movements',data);
                UI.showToast('Movement created','success');
                // Upload photos if any
                const newId=(res.data||res).id;
                if(pendingPhotos.length&&newId){
                    try{await API.post('/movements/'+newId+'/photos',{photos:pendingPhotos});}catch(pe){console.error(pe);}
                }
            }
            closeModal();
            loadMovements();
        }catch(e){UI.showToast(e.message||'Error','error');}
    });

    /* ---- Global methods ---- */
    window.MvPage={
        goPage(p){currentPage=p;render();window.scrollTo({top:0,behavior:'smooth'});},
        removePhoto(i){pendingPhotos.splice(i,1);renderPhotosPrev();},
        async view(id){
            try{
                const res=await API.get('/movements/'+id);
                const m=res.data||res;
                const condL=c=>c==='clean'?'Clean':c==='acceptable'?'Acceptable':c==='damaged'?'Damaged':'—';
                const fuelL=f=>{const mp={full:'Full',three_quarter:'3/4',half:'Half',quarter:'1/4',empty:'Empty'};return mp[f]||'—';};
                let h='<div class="d-row"><span class="d-lbl">Vehicle Code</span><span class="d-val">'+esc(m.vehicle_code)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">Operation</span><span class="d-val"><span class="mv-badge '+m.operation_type+'">'+(m.operation_type==='pickup'?'Pickup':'Return')+'</span></span></div>';
                h+='<div class="d-row"><span class="d-lbl">Performed By</span><span class="d-val">'+esc(m.performed_by)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">Date/Time</span><span class="d-val">'+esc(m.movement_datetime)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">Condition</span><span class="d-val">'+condL(m.vehicle_condition)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">Fuel</span><span class="d-val">'+fuelL(m.fuel_level)+'</span></div>';
                if(m.latitude&&m.longitude){
                    h+='<div class="d-row"><span class="d-lbl">Location</span><span class="d-val"><a href="https://www.google.com/maps?q='+m.latitude+','+m.longitude+'" target="_blank">📍 '+m.latitude+', '+m.longitude+'</a></span></div>';
                }
                h+='<div class="d-row"><span class="d-lbl">Notes</span><span class="d-val">'+esc(m.notes||'—')+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">Created By</span><span class="d-val">'+esc(m.created_by||'—')+'</span></div>';
                // Photos
                const photos=m.photos||[];
                if(photos.length){
                    h+='<h4 style="margin-top:16px">📷 Photos</h4><div class="d-photos">';
                    photos.forEach(p=>{h+='<img src="'+esc(p.photo_url)+'" onclick="window.open(this.src,\'_blank\')">';});
                    h+='</div>';
                }
                $('mvDetailBody').innerHTML=h;
                $('mvDetailTitle').textContent='Movement #'+id;
                $('mvDetailModal').classList.add('show');
            }catch(e){UI.showToast('Failed to load details','error');}
        },
        async edit(id){
            try{
                const res=await API.get('/movements/'+id);
                const m=res.data||res;
                $('mvId').value=m.id;
                $('mvVehicleCode').value=m.vehicle_code||'';
                $('mvOperationType').value=m.operation_type||'';
                $('mvPerformedBy').value=m.performed_by||'';
                $('mvCondition').value=m.vehicle_condition||'';
                $('mvFuel').value=m.fuel_level||'';
                $('mvLat').value=m.latitude||'';
                $('mvLng').value=m.longitude||'';
                $('mvNotes').value=m.notes||'';
                pendingPhotos=[];renderPhotosPrev();
                openModal('✏️ Edit Movement #'+id);
            }catch(e){UI.showToast('Failed to load','error');}
        },
        async del(id){
            if(!confirm('Delete this movement?'))return;
            try{
                await API.delete('/movements/'+id);
                UI.showToast('Movement deleted','success');
                loadMovements();
            }catch(e){UI.showToast(e.message||'Error','error');}
        }
    };

    $('mvDetailClose').addEventListener('click',()=>$('mvDetailModal').classList.remove('show'));

    // Init
    loadMovements();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
