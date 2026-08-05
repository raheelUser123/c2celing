document.documentElement.classList.add('js');
(() => {
  const header=document.querySelector('#siteHeader');
  const menuBtn=document.querySelector('.menu-toggle');
  const drawer=document.querySelector('.mobile-drawer');
  const closeDrawer=()=>{document.body.classList.remove('drawer-open');drawer?.setAttribute('aria-hidden','true');menuBtn?.setAttribute('aria-expanded','false');};
  addEventListener('scroll',()=>header?.classList.toggle('scrolled',scrollY>24),{passive:true});
  menuBtn?.addEventListener('click',()=>{document.body.classList.add('drawer-open');drawer?.setAttribute('aria-hidden','false');menuBtn.setAttribute('aria-expanded','true');});
  document.querySelectorAll('[data-drawer-close]').forEach(el=>el.addEventListener('click',closeDrawer));
  addEventListener('keydown',e=>{if(e.key==='Escape')closeDrawer();});
  const serviceToggle=document.querySelector('.drawer-services-toggle');
  serviceToggle?.addEventListener('click',()=>{const submenu=document.querySelector('.drawer-submenu');const open=submenu?.classList.toggle('open');serviceToggle.setAttribute('aria-expanded',String(open));});
  document.querySelectorAll('a[href^="#"]').forEach(link=>link.addEventListener('click',e=>{const target=document.querySelector(link.getAttribute('href'));if(target){e.preventDefault();target.scrollIntoView({behavior:'smooth',block:'start'});}}));
  const reveals=document.querySelectorAll('.reveal, .section-heading, .section-head-row, .service-image-card, .process-stack article, .qc-grid article, .milestone-cards article, .lifecycle-detailed article');
  reveals.forEach((el,i)=>{el.classList.add('reveal');if(i%3===1)el.classList.add('reveal-delay-1');if(i%3===2)el.classList.add('reveal-delay-2');});
  if('IntersectionObserver' in window){const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('revealed');observer.unobserve(entry.target);}}),{threshold:.12,rootMargin:'0px 0px -40px'});reveals.forEach(el=>observer.observe(el));}else reveals.forEach(el=>el.classList.add('revealed'));

  document.querySelectorAll('[data-file-input]').forEach(input=>input.addEventListener('change',()=>{const target=input.closest('label')?.querySelector('[data-file-summary]');if(!target)return;const files=[...input.files];target.textContent=files.length?`${files.length} file${files.length===1?'':'s'} selected: ${files.map(f=>f.name).join(', ')}`:'';}));
  document.querySelectorAll('[data-ajax-form]').forEach(form=>form.addEventListener('submit',async e=>{e.preventDefault();const msg=form.querySelector('.form-message');const btn=form.querySelector('button[type="submit"]');msg.textContent='';btn.disabled=true;const old=btn.innerHTML;btn.textContent='Submitting...';try{const res=await fetch(form.action,{method:'POST',body:new FormData(form)});const data=await res.json();if(!res.ok||!data.ok)throw new Error(data.message||'Submission failed.');msg.className='form-message success-text';msg.textContent='Submitted successfully. Redirecting...';location.href=data.redirect;}catch(err){msg.className='form-message error-text';msg.textContent=err.message||'Something went wrong. Please call us.';btn.disabled=false;btn.innerHTML=old;}}));
  document.querySelectorAll('.intent-tabs button').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('.intent-tabs button').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.intent-panel').forEach(x=>x.classList.remove('active'));btn.classList.add('active');document.querySelector(`[data-panel="${btn.dataset.tab}"]`)?.classList.add('active');}));
  document.querySelectorAll('.filter-bar button').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('.filter-bar button').forEach(x=>x.classList.remove('active'));btn.classList.add('active');document.querySelectorAll('.portfolio-grid article').forEach(card=>card.hidden=btn.dataset.filter!=='all'&&card.dataset.category!==btn.dataset.filter);}));
  document.querySelectorAll('input[type="tel"]').forEach(input=>input.addEventListener('input',()=>{let n=input.value.replace(/\D/g,'').slice(0,10);input.value=n.length>6?`(${n.slice(0,3)}) ${n.slice(3,6)}-${n.slice(6)}`:n.length>3?`(${n.slice(0,3)}) ${n.slice(3)}`:n;}));
})();
