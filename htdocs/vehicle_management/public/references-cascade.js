// small helper to bind cascading selects:
// departmentSelect -> sectionSelect -> divisionSelect
// usage: ReferencesCascade.bind('#department',' #section','#division',{lang:'ar'});

const ReferencesCascade = (function(){
  async function fetchRefs(type, parent_id, lang='ar'){
    let url;
    if (type === 'departments') {
      url = '/vehicle_management/api/v1/references/departments';
    } else if (type === 'sections' && parent_id) {
      url = '/vehicle_management/api/v1/references/sections/' + encodeURIComponent(parent_id);
    } else if (type === 'divisions' && parent_id) {
      url = '/vehicle_management/api/v1/references/divisions/' + encodeURIComponent(parent_id);
    } else {
      url = '/vehicle_management/api/v1/references';
    }
    const res = await fetch(url, { credentials:'same-origin' });
    return await res.json();
  }

  function emptySelect(sel, placeholder='-- اختر --'){
    sel.innerHTML = '';
    const o = document.createElement('option'); o.value=''; o.textContent = placeholder; sel.appendChild(o);
  }

  async function fillSelect(sel, items){
    emptySelect(sel);
    items.forEach(it=>{
      const o = document.createElement('option');
      o.value = it.id ?? it.department_id ?? it.section_id ?? it.division_id;
      o.textContent = it.name || (it.name_ar || it.name_en || ('#'+(it.id ?? it.department_id ?? it.section_id ?? it.division_id)));
      sel.appendChild(o);
    });
  }

  function bind(depSel, secSel, divSel, opts = {}) {
    const lang = opts.lang || 'ar';
    const d = document.querySelector(depSel);
    const s = document.querySelector(secSel);
    const dv = document.querySelector(divSel);
    if (!d || !s || !dv) return console.warn('ReferencesCascade: select(s) not found');

    // initial load of departments
    (async ()=>{
      const j = await fetch('/vehicle_management/api/v1/references/departments', { credentials:'same-origin' }).then(r=>r.json());
      const items = j.data || j.departments || [];
      fillSelect(d, items);
      if (d.dataset.selected) d.value = d.dataset.selected;
      if (d.value) d.dispatchEvent(new Event('change'));
    })();

    d.addEventListener('change', async function(){
      const depId = this.value || null;
      if (!depId) { emptySelect(s); emptySelect(dv); return; }
      const j = await fetch('/vehicle_management/api/v1/references/sections/' + encodeURIComponent(depId), { credentials:'same-origin' }).then(r=>r.json());
      const items = j.data || j.sections || [];
      await fillSelect(s, items);
      if (s.dataset.selected) s.value = s.dataset.selected;
      emptySelect(dv);
      if (s.value) s.dispatchEvent(new Event('change'));
    });

    s.addEventListener('change', async function(){
      const secId = this.value || null;
      if (!secId) { emptySelect(dv); return; }
      const j = await fetch('/vehicle_management/api/v1/references/divisions/' + encodeURIComponent(secId), { credentials:'same-origin' }).then(r=>r.json());
      const items = j.data || j.divisions || [];
      await fillSelect(dv, items);
      if (dv.dataset.selected) dv.value = dv.dataset.selected;
    });
  }

  return { bind, fillSelect, emptySelect };
})();