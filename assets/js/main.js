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

  // Multi-Step Form Wizard Logic
  const stepTitles = { 1: 'Step 1 of 3: Project Scope', 2: 'Step 2 of 3: Contact & Location', 3: 'Step 3 of 3: Photos & Details' };
  const stepPercents = { 1: '33%', 2: '66%', 3: '100%' };

  document.querySelectorAll('[data-multi-step-form]').forEach(form => {
    let currentStep = 1;
    const totalSteps = 3;
    const panels = form.querySelectorAll('.step-panel');
    const nodes = form.querySelectorAll('.step-node');
    const fill = form.querySelector('[data-step-fill]');
    const label = form.querySelector('[data-step-label]');
    const percent = form.querySelector('[data-step-percent]');

    const updateStep = (step) => {
      currentStep = step;
      panels.forEach(p => p.classList.toggle('active', parseInt(p.dataset.step) === step));
      nodes.forEach(n => {
        const nStep = parseInt(n.dataset.node);
        n.classList.toggle('active', nStep === step);
        n.classList.toggle('completed', nStep < step);
      });
      if (fill) fill.style.width = stepPercents[step];
      if (label) label.textContent = stepTitles[step];
      if (percent) percent.textContent = stepPercents[step];
    };

    const validateStep = (step) => {
      const activePanel = form.querySelector(`.step-panel[data-step="${step}"]`);
      if (!activePanel) return true;
      const inputs = activePanel.querySelectorAll('input[required], select[required], textarea[required]');
      let valid = true;
      inputs.forEach(input => {
        if (!input.checkValidity()) {
          input.reportValidity();
          valid = false;
        }
      });
      return valid;
    };

    form.querySelectorAll('[data-next-step]').forEach(btn => {
      btn.addEventListener('click', () => {
        if (validateStep(currentStep) && currentStep < totalSteps) {
          updateStep(currentStep + 1);
        }
      });
    });

    form.querySelectorAll('[data-prev-step]').forEach(btn => {
      btn.addEventListener('click', () => {
        if (currentStep > 1) {
          updateStep(currentStep - 1);
        }
      });
    });

    // Prevent submission on enter unless on final step
    form.addEventListener('keydown', e => {
      if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        if (currentStep < totalSteps) {
          e.preventDefault();
          if (validateStep(currentStep)) updateStep(currentStep + 1);
        }
      }
    });
  });

  // Scroll Reveal Animations System
  const selectors = '.reveal, .reveal-left, .reveal-right, .reveal-zoom, .section-heading, .section-head-row, .service-image-card, .process-stack article, .qc-grid article, .milestone-cards article, .lifecycle-detailed article, .values-grid article, .fit-grid article, .article-card, .mini-cards article';
  const reveals = document.querySelectorAll(selectors);
  reveals.forEach((el, i) => {
    if (!el.classList.contains('reveal') && !el.classList.contains('reveal-left') && !el.classList.contains('reveal-right') && !el.classList.contains('reveal-zoom')) {
      el.classList.add('reveal');
    }
    if (i % 3 === 1) el.classList.add('reveal-delay-1');
    if (i % 3 === 2) el.classList.add('reveal-delay-2');
  });

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
        observer.unobserve(entry.target);
      }
    }), { threshold: .1, rootMargin: '0px 0px -30px' });
    reveals.forEach(el => observer.observe(el));
  } else {
    reveals.forEach(el => el.classList.add('revealed'));
  }

  document.querySelectorAll('[data-file-input]').forEach(input=>input.addEventListener('change',()=>{const target=input.closest('label')?.querySelector('[data-file-summary]');if(!target)return;const files=[...input.files];target.textContent=files.length?`${files.length} file${files.length===1?'':'s'} selected: ${files.map(f=>f.name).join(', ')}`:'';}));
  // Forms submit natively to PHP. This avoids fetch/cache/proxy issues on shared hosting.
  document.querySelectorAll('.c2c-lead-form').forEach(form => {
    form.addEventListener('submit', e => {
      const btn = form.querySelector('button[type="submit"]');
      const msg = form.querySelector('.form-message');
      if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
        if (msg) {
          msg.className = 'form-message error-text';
          msg.textContent = 'Please complete all required fields.';
        }
        return;
      }
      if (btn) {
        btn.disabled = true;
        btn.dataset.originalText = btn.textContent || '';
        btn.textContent = 'Submitting...';
      }
      if (msg) {
        msg.className = 'form-message';
        msg.textContent = 'Submitting your request securely...';
      }
    });
  });
  document.querySelectorAll('.intent-tabs button').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('.intent-tabs button').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.intent-panel').forEach(x=>x.classList.remove('active'));btn.classList.add('active');document.querySelector(`[data-panel="${btn.dataset.tab}"]`)?.classList.add('active');}));
  // Portfolio tabs (built from assets/images/projects folders)
  document.querySelectorAll('.portfolio-tabs').forEach(tabList=>{
    const panels=document.querySelectorAll('.portfolio-panel');
    const activate=(btn)=>{
      tabList.querySelectorAll('.portfolio-tab').forEach(x=>{const on=x===btn;x.classList.toggle('active',on);x.setAttribute('aria-selected',String(on));});
      panels.forEach(p=>{p.hidden=p.id!==btn.dataset.tab;});
    };
    tabList.querySelectorAll('.portfolio-tab').forEach(btn=>btn.addEventListener('click',()=>activate(btn)));
    tabList.addEventListener('keydown',e=>{
      const tabs=Array.from(tabList.querySelectorAll('.portfolio-tab'));
      const idx=tabs.indexOf(document.activeElement);
      if(idx===-1||!['ArrowRight','ArrowLeft','Home','End'].includes(e.key))return;
      e.preventDefault();
      let next=idx;
      if(e.key==='ArrowRight')next=(idx+1)%tabs.length;
      if(e.key==='ArrowLeft')next=(idx-1+tabs.length)%tabs.length;
      if(e.key==='Home')next=0;
      if(e.key==='End')next=tabs.length-1;
      tabs[next].focus();activate(tabs[next]);
    });
  });
  document.querySelectorAll('input[type="tel"]').forEach(input=>input.addEventListener('input',()=>{let n=input.value.replace(/\D/g,'').slice(0,10);input.value=n.length>6?`(${n.slice(0,3)}) ${n.slice(3,6)}-${n.slice(6)}`:n.length>3?`(${n.slice(0,3)}) ${n.slice(3)}`:n;}));

  // Reviews Carousel Logic
  const initReviewsCarousel = () => {
    const wrapper = document.querySelector('#reviewsCarousel');
    if (!wrapper) return;

    const track = wrapper.querySelector('.reviews-carousel-track');
    const cards = track ? Array.from(track.querySelectorAll('.review-card')) : [];
    const prevBtn = wrapper.querySelector('.carousel-btn.prev');
    const nextBtn = wrapper.querySelector('.carousel-btn.next');
    const dotsContainer = wrapper.querySelector('.carousel-dots');

    if (!track || cards.length === 0) return;

    let currentIndex = 0;
    let autoplayTimer = null;

    const getVisibleCount = () => {
      if (window.innerWidth <= 640) return 1;
      if (window.innerWidth <= 992) return 2;
      return 3;
    };

    const getMaxIndex = () => {
      return Math.max(0, cards.length - getVisibleCount());
    };

    const updateButtons = () => {
      if (prevBtn) prevBtn.disabled = currentIndex === 0;
      if (nextBtn) nextBtn.disabled = currentIndex >= getMaxIndex();
    };

    const renderDots = () => {
      if (!dotsContainer) return;
      dotsContainer.innerHTML = '';
      const maxIdx = getMaxIndex();
      for (let i = 0; i <= maxIdx; i++) {
        const dot = document.createElement('button');
        dot.className = `carousel-dot ${i === currentIndex ? 'active' : ''}`;
        dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
        dot.addEventListener('click', () => {
          goToSlide(i);
          resetAutoplay();
        });
        dotsContainer.appendChild(dot);
      }
    };

    const goToSlide = (index) => {
      const maxIdx = getMaxIndex();
      currentIndex = Math.max(0, Math.min(index, maxIdx));
      
      const cardNode = cards[0];
      const gap = 24;
      const cardWidth = cardNode.getBoundingClientRect().width;
      const offset = currentIndex * (cardWidth + gap);

      track.style.transform = `translateX(-${offset}px)`;

      if (dotsContainer) {
        const dots = Array.from(dotsContainer.children);
        dots.forEach((dot, idx) => {
          dot.classList.toggle('active', idx === currentIndex);
        });
      }
      updateButtons();
    };

    prevBtn?.addEventListener('click', () => {
      if (currentIndex > 0) {
        goToSlide(currentIndex - 1);
      } else {
        goToSlide(getMaxIndex());
      }
      resetAutoplay();
    });

    nextBtn?.addEventListener('click', () => {
      if (currentIndex < getMaxIndex()) {
        goToSlide(currentIndex + 1);
      } else {
        goToSlide(0);
      }
      resetAutoplay();
    });

    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    track.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
      isDragging = true;
    }, { passive: true });

    track.addEventListener('touchmove', (e) => {
      if (!isDragging) return;
      currentX = e.touches[0].clientX;
    }, { passive: true });

    track.addEventListener('touchend', () => {
      if (!isDragging) return;
      isDragging = false;
      const diffX = startX - currentX;
      if (Math.abs(diffX) > 40) {
        if (diffX > 0) {
          if (currentIndex < getMaxIndex()) goToSlide(currentIndex + 1);
        } else {
          if (currentIndex > 0) goToSlide(currentIndex - 1);
        }
        resetAutoplay();
      }
    });

    const startAutoplay = () => {
      autoplayTimer = setInterval(() => {
        const maxIdx = getMaxIndex();
        if (currentIndex >= maxIdx) {
          goToSlide(0);
        } else {
          goToSlide(currentIndex + 1);
        }
      }, 5000);
    };

    const resetAutoplay = () => {
      clearInterval(autoplayTimer);
      startAutoplay();
    };

    wrapper.addEventListener('mouseenter', () => clearInterval(autoplayTimer));
    wrapper.addEventListener('mouseleave', () => startAutoplay());

    renderDots();
    updateButtons();
    startAutoplay();

    window.addEventListener('resize', () => {
      renderDots();
      goToSlide(Math.min(currentIndex, getMaxIndex()));
    });
  };

  initReviewsCarousel();
})();

