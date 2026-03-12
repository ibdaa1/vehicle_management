// small helper to bind cascading selects:
// departmentSelect -> sectionSelect -> divisionSelect
// usage: ReferencesCascade.bind('#department',' #section','#division',{lang:'ar'});

const ReferencesCascade = (function(){
  async function fetchRefs(type, parent_id, lang='ar'){
    let url = '/vehicle_management/api/helper/get_references.php?lang=' + encodeURIComponent(lang) + '&type=' + encodeURIComponent(type);
    if (parent_id) url += '&parent_id=' + encodeURIComponent(parent_id);
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
      o.value = it.id;
      o.textContent = it.name || (it.name_ar || it.name_en || ('#'+it.id));
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
      const j = await fetch('/vehicle_management/api/helper/get_references.php?lang=' + encodeURIComponent(lang) + '&type=departments', { credentials:'same-origin' }).then(r=>r.json());
      const items = j.departments || [];
      fillSelect(d, items);
      // if presence of pre-selected values in data-selected attributes, set them
      if (d.dataset.selected) d.value = d.dataset.selected;
      // trigger change to load sections if department preselected
      if (d.value) d.dispatchEvent(new Event('change'));
    })();

    d.addEventListener('change', async function(){
      const depId = this.value || null;
      // load sections for depId
      const j = await fetch('/vehicle_management/api/helper/get_references.php?lang=' + encodeURIComponent(lang) + '&type=sections' + (depId ? '&parent_id=' + depId : ''), { credentials:'same-origin' }).then(r=>r.json());
      const items = j.sections || [];
      await fillSelect(s, items);
      if (s.dataset.selected) s.value = s.dataset.selected;
      // clear divisions
      emptySelect(dv);
      if (s.value) s.dispatchEvent(new Event('change'));
    });

    s.addEventListener('change', async function(){
      const secId = this.value || null;
      const j = await fetch('/vehicle_management/api/helper/get_references.php?lang=' + encodeURIComponent(lang) + '&type=divisions' + (secId ? '&parent_id=' + secId : ''), { credentials:'same-origin' }).then(r=>r.json());
      const items = j.divisions || [];
      await fillSelect(dv, items);
      if (dv.dataset.selected) dv.value = dv.dataset.selected;
    });
  }

  return { bind, fillSelect, emptySelect };
})();