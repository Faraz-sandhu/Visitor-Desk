'use strict';
(() => {
 const paths = {edit:'M15 5l4 4 M4 20l4-1L20 7l-4-4L4 15z',trash:'M3 6h18 M9 6V3h6v3 M5 6l1 15h12l1-15 M10 10v7 M14 10v7',eye:'M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12 M15 12a3 3 0 1 0-6 0 3 3 0 0 0 6 0',arrow:'M5 12h14 M14 7l5 5-5 5',grid:'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',visits:'M8 3h8v4H8z M8 5H5v16h14V5h-3 M8 12h8 M8 16h5',users:'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M17 4a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-4',company:'M4 21V3h12v18 M16 10h4v11 M2 21h20 M8 7h4 M8 11h4 M8 15h4',plus:'M12 5v14 M5 12h14',logout:'M9 4H4v16h5 M10 12h11 M17 8l4 4-4 4',menu:'M4 6h16 M4 12h16 M4 18h16'};
 function enhance() {
  document.querySelectorAll('[data-icon]').forEach(el => {el.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'+(paths[el.dataset.icon]||paths.grid)+'"/></svg>';});
  document.querySelectorAll('input[type="email"]').forEach(el=>{if(!el.autocomplete)el.autocomplete='email';});
  document.querySelectorAll('input[name*="phone"]').forEach(el=>{el.type='tel';el.autocomplete='tel';});
 }
 function filterCompanies(picker) {
  const query=picker.querySelector('[data-company-search]').value.trim().toLocaleLowerCase();
  const rows=[...picker.querySelectorAll('[data-company-row]')];
  rows.forEach(row=>row.hidden=!row.dataset.name.toLocaleLowerCase().includes(query));
  const add=picker.querySelector('[data-add-company]');
  add.hidden=!query||rows.some(row=>row.dataset.name.toLocaleLowerCase()===query);
  add.querySelector('span').textContent='Add "'+picker.querySelector('[data-company-search]').value.trim()+'"';
 }
 document.addEventListener('input',e=>{if(e.target.matches('[data-company-search]'))filterCompanies(e.target.closest('[data-company-picker]'));});
 document.addEventListener('keydown',e=>{if(e.target.matches('[data-company-search]')&&e.key==='Enter'){e.preventDefault();const p=e.target.closest('[data-company-picker]');const add=p.querySelector('[data-add-company]');if(!add.hidden)add.click();else p.querySelector('[data-company-row]:not([hidden]) [data-select-company]')?.click();}});
 document.addEventListener('click',async e=>{
  const toggle=e.target.closest('[data-toggle-password]');
  if(toggle){const input=document.querySelector('#password');const show=input.type==='password';input.type=show?'text':'password';toggle.setAttribute('aria-label',show?'Hide password':'Show password');toggle.title=show?'Hide password':'Show password';}
  const button=e.target.closest('[data-select-company],[data-add-company],[data-delete-company]');if(!button)return;
  const picker=button.closest('[data-company-picker]'),select=picker.querySelector('[data-company-select]'),feedback=picker.querySelector('[data-company-feedback]');
  if(button.hasAttribute('data-select-company')){select.value=button.dataset.selectCompany;select.dispatchEvent(new Event('change',{bubbles:true}));feedback.textContent='Selected '+button.closest('[data-company-row]').dataset.name;return;}
  const deleting=button.hasAttribute('data-delete-company');
  if(deleting&&!confirm('Delete '+button.closest('[data-company-row]').dataset.name+'?'))return;
  button.disabled=true;feedback.textContent=deleting?'Deleting company...':'Adding company...';
  try {
   const response=await fetch(deleting?button.dataset.deleteCompany:picker.dataset.createUrl,{method:deleting?'DELETE':'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},...(deleting?{}:{body:JSON.stringify({name:picker.querySelector('[data-company-search]').value.trim(),is_active:true})})});
   const data=await response.json();if(!response.ok)throw Error(Object.values(data.errors||{}).flat()[0]||data.message||'Unable to save company.');
   if(deleting){const row=button.closest('[data-company-row]');const option=[...select.options].find(o=>o.value===row.dataset.id);if(select.value===row.dataset.id)select.value='';option?.remove();row.remove();feedback.textContent='Company deleted.';}
   else {
    const company=data.company;select.add(new Option(company.name,company.id,true,true));
    const row=document.createElement('div');row.className='company-option';row.dataset.companyRow='';row.dataset.name=company.name;row.dataset.id=String(company.id);
    const choice=document.createElement('button');choice.type='button';choice.className='company-choice';choice.dataset.selectCompany=company.id;choice.textContent=company.name;
    const del=document.createElement('button');del.type='button';del.className='icon-btn danger';del.dataset.deleteCompany=picker.dataset.createUrl+'/'+company.id;del.title='Delete company';del.setAttribute('aria-label','Delete '+company.name);del.innerHTML='<i data-icon="trash"></i>';row.append(choice);if(picker.dataset.canDelete==='1')row.append(del);picker.querySelector('.company-options').append(row);
    picker.querySelector('[data-company-search]').value='';feedback.textContent=company.name+' added and selected.';enhance();
   }
   filterCompanies(picker);
  }catch(error){feedback.textContent=error.message;}finally{button.disabled=false;}
 });
 document.addEventListener('click',e=>{
  const link=e.target.closest('[data-view-photo]');
  if(link){e.preventDefault();const dialog=document.querySelector('#photo-viewer');dialog.querySelector('[data-full-photo]').src=link.href;dialog.querySelector('[data-photo-original]').href=link.href;dialog.showModal();}
  if(e.target.id==='photo-viewer'){const r=e.target.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)e.target.close();}
 });
 function closeNav(){document.body.classList.remove('nav-open');document.querySelector('[data-toggle-nav]')?.setAttribute('aria-expanded','false');}
 document.addEventListener('click',e=>{
  if(e.target.closest('[data-toggle-nav]')){const open=document.body.classList.toggle('nav-open');document.querySelector('[data-toggle-nav]').setAttribute('aria-expanded',String(open));}
  if(e.target.closest('[data-close-nav]'))closeNav();
 });
 document.addEventListener('keydown',e=>{if(e.key==='Escape')closeNav();});
 document.addEventListener('submit',e=>{const f=e.target;if(f.dataset.confirm&&!confirm(f.dataset.confirm)){e.preventDefault();return;}if(f.method.toLowerCase()==='post'){f.querySelectorAll('button[type="submit"],button:not([type])').forEach(b=>{b.disabled=true;b.setAttribute('aria-busy','true');});}});
 let previewUrl;
 document.addEventListener('change',e=>{if(e.target.id!=='photo')return;const img=document.querySelector('[data-photo-preview]');if(!img)return;if(previewUrl)URL.revokeObjectURL(previewUrl);const file=e.target.files[0];img.hidden=!file;if(file){previewUrl=URL.createObjectURL(file);img.src=previewUrl;}});
 // Swap same-origin pages while keeping the shell and assets in memory.
 let navigation;
 async function visit(url,push=true){
  navigation?.abort(); const controller=new AbortController();navigation=controller;document.body.classList.add('loading');
  try {const response=await fetch(url,{signal:controller.signal,headers:{'X-Requested-With':'XMLHttpRequest'}});if(!response.ok)throw Error('Navigation failed');const html=new DOMParser().parseFromString(await response.text(),'text/html');if(!html.querySelector('#page-content')){location.href=response.url;return;}document.querySelector('main').replaceWith(html.querySelector('main'));document.querySelector('.sidebar').replaceWith(html.querySelector('.sidebar'));document.title=html.title;const token=html.querySelector('meta[name="csrf-token"]');if(token)document.querySelector('meta[name="csrf-token"]').content=token.content;if(push)history.pushState({},'',response.url);closeNav();enhance();window.scrollTo(0,0);document.querySelector('#page-content').focus({preventScroll:true});}
  catch(error){if(error.name!=='AbortError')location.href=url;}finally{if(navigation===controller)document.body.classList.remove('loading');}
 }
 document.addEventListener('click',e=>{const a=e.target.closest('a');if(!a||e.defaultPrevented||e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey||a.target||a.hasAttribute('download')||a.hasAttribute('data-view-photo'))return;const url=new URL(a.href);if(url.origin!==location.origin||url.hash||!document.querySelector('#page-content'))return;e.preventDefault();visit(url.href);});
 window.addEventListener('popstate',()=>visit(location.href,false));
 window.addEventListener('pageshow',()=>document.querySelectorAll('button[aria-busy]').forEach(b=>{b.disabled=false;b.removeAttribute('aria-busy');}));
 enhance();
})();
