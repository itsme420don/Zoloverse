/**
 * ZOLOVERSE MASTER CLIENT ENGINE
 * Integrates GSAP Timelines, ScrollTrigger, Magnetic Physics,
 * and Dynamic Logo & Content Architecture
 */

document.addEventListener('DOMContentLoaded', () => {
  initNavbarScroll();
  initMagneticElements();
  loadBrandSettings();
  initLoadingSequence();
  initSmoothPageTransitions();
  initMobileNav();
});

// Cursor follower removed per user preference - standard OS pointer active
function initCustomCursor() {
  // Custom cursor disabled
}

// ==========================================================================
// 2. DYNAMIC LOGO & SETTINGS SYNC
// ==========================================================================
async function loadBrandSettings() {
  try {
    const res = await fetch('api_content.php?section=settings');
    const data = await res.json();
    if (data.success && data.content) {
      const s = data.content;
      const logoUrl = s.logo_url || 'Logo.jpeg';
      const siteName = s.site_name || 'Zoloverse';

      // Update all brand logos on the page
      document.querySelectorAll('.brand-logo-img').forEach(img => {
        img.src = logoUrl;
        img.alt = siteName;
        img.style.display = 'block';
      });

      // Update brand name text cleanly without duplicating 'verse'
      document.querySelectorAll('.brand-title').forEach(title => {
        if (siteName.toLowerCase().endsWith('verse') && siteName.length > 5) {
          const prefix = siteName.slice(0, -5);
          title.innerHTML = `<span class="brand-text-name">${prefix}</span><span style="color:var(--primary-purple)">verse</span>`;
        } else {
          title.innerHTML = `<span class="brand-text-name">${siteName}</span>`;
        }
      });

      // Hide fallback icon if image is loaded
      document.querySelectorAll('.brand-fallback-icon').forEach(icon => {
        icon.style.display = 'none';
      });

      // Update Public Contact Details across footers, info cards & headers
      if (s.contact_email) {
        document.querySelectorAll('.brand-contact-email').forEach(el => {
          el.textContent = s.contact_email;
          if (el.tagName === 'A') el.href = `mailto:${s.contact_email}`;
        });
      }

      if (s.contact_phone) {
        document.querySelectorAll('.brand-contact-phone').forEach(el => {
          el.textContent = s.contact_phone;
          if (el.tagName === 'A') el.href = `tel:${s.contact_phone.replace(/\s+/g, '')}`;
        });
      }

      if (s.contact_location) {
        document.querySelectorAll('.brand-contact-location').forEach(el => {
          el.textContent = s.contact_location;
        });
      }

      if (s.contact_hours) {
        document.querySelectorAll('.brand-contact-hours').forEach(el => {
          el.textContent = s.contact_hours;
        });
      }

      if (s.whatsapp) {
        document.querySelectorAll('.brand-contact-whatsapp').forEach(el => {
          el.textContent = s.whatsapp;
          if (el.tagName === 'A') el.href = `https://wa.me/${s.whatsapp.replace(/[^0-9]/g, '')}`;
        });
      }

      if (s.linkedin) {
        document.querySelectorAll('.brand-contact-linkedin').forEach(el => {
          if (el.tagName === 'A') el.href = s.linkedin;
        });
      }

      if (s.github) {
        document.querySelectorAll('.brand-contact-github').forEach(el => {
          if (el.tagName === 'A') el.href = s.github;
        });
      }
    }
  } catch (err) {
    console.warn('Using default brand assets:', err);
  }
}

// ==========================================================================
// 3. NAVBAR SCROLL INTERACTION
// ==========================================================================
function initNavbarScroll() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;

  const handleScroll = () => {
    if (window.scrollY > 40) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();
}

function initMobileNav() {
  const toggle = document.getElementById('mobileMenuBtn');
  const links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      links.classList.toggle('open');
      const icon = toggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-times');
      }
    });
  }
}

// ==========================================================================
// 4. INTERACTIVE MAGNETIC PHYSICS
// ==========================================================================
function initMagneticElements() {
  const magneticEls = document.querySelectorAll('.magnetic');
  magneticEls.forEach(el => {
    el.addEventListener('mousemove', (e) => {
      const rect = el.getBoundingClientRect();
      const deltaX = (e.clientX - rect.left - rect.width / 2) * 0.3;
      const deltaY = (e.clientY - rect.top - rect.height / 2) * 0.3;
      el.style.transform = `translate(${deltaX}px, ${deltaY}px)`;
    });

    el.addEventListener('mouseleave', () => {
      el.style.transform = 'translate(0px, 0px)';
      el.style.transition = 'transform 0.4s ease';
      setTimeout(() => { el.style.transition = ''; }, 400);
    });
  });
}

// ==========================================================================
// 5. ORCHESTRATED GSAP TIMELINES, ONE-TIME INTRO & PAGE ENTRANCE
// ==========================================================================
const ZOLO_SESSION_INTRO_KEY = 'zoloverse_session_intro_completed';

function initLoadingSequence() {
  const loadingScreen = document.getElementById('loadingScreen');
  const hasIntroPlayed = sessionStorage.getItem(ZOLO_SESSION_INTRO_KEY);
  const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // If intro already played in this browser session, or reduced motion, or no loader element
  if (hasIntroPlayed || prefersReducedMotion || !loadingScreen) {
    if (loadingScreen) {
      loadingScreen.style.display = 'none';
    }
    document.documentElement.classList.add('intro-done');
    initPageEntrance();
    initScrollAnimations();
    initAmbientMotion();
    return;
  }

  // First visit in this browser session: play the branded Zoloverse intro animation
  if (typeof gsap === 'undefined') {
    loadingScreen.style.display = 'none';
    sessionStorage.setItem(ZOLO_SESSION_INTRO_KEY, 'true');
    document.documentElement.classList.add('intro-done');
    initPageEntrance();
    initScrollAnimations();
    initAmbientMotion();
    return;
  }

  const loadTL = gsap.timeline({
    onComplete: () => {
      sessionStorage.setItem(ZOLO_SESSION_INTRO_KEY, 'true');
      document.documentElement.classList.add('intro-done');
      if (loadingScreen) {
        loadingScreen.style.display = 'none';
      }
      initPageEntrance();
      initScrollAnimations();
      initAmbientMotion();
    }
  });

  loadTL
    .to('.loading-brand', {
      y: 0,
      duration: 0.9,
      ease: 'power3.out',
      delay: 0.15
    })
    .to('.loading-tagline', {
      opacity: 1,
      duration: 0.7,
      ease: 'power2.out'
    }, '-=0.4')
    .to(loadingScreen, {
      y: '-100%',
      duration: 0.85,
      ease: 'power4.inOut',
      delay: 0.6
    });
}

function initPageEntrance() {
  if (typeof gsap === 'undefined') return;
  const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const heroTL = gsap.timeline({ defaults: { ease: 'power3.out' } });

  // 1. Badge / Category Tag
  const badges = document.querySelectorAll('.hero-category-label, .section-badge');
  if (badges.length > 0) {
    heroTL.fromTo(badges[0], { opacity: 0, y: -12 }, { opacity: 1, y: 0, duration: 0.65 }, 0.05);
  }

  // 2. Heading Lines (Home masked split lines) or General Page Title (Services, Projects, About, Contact, Register)
  const lineInners = document.querySelectorAll('.hero-heading .line-inner');
  if (lineInners.length > 0) {
    heroTL.to(lineInners, { y: 0, duration: 0.85, stagger: 0.12 }, 0.15);
  } else {
    const pageTitle = document.querySelector('.page-hero .section-title, .hero-section h1');
    if (pageTitle) {
      heroTL.fromTo(pageTitle, { opacity: 0, y: 22 }, { opacity: 1, y: 0, duration: 0.75 }, 0.15);
    }
  }

  // 3. Subtitle / Subtext
  const subtext = document.querySelector('.hero-subtext, .page-hero .section-subtitle');
  if (subtext) {
    heroTL.fromTo(subtext, { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 0.7 }, 0.28);
  }

  // 4. Action Buttons / Filter Pill Tracks
  const actions = document.querySelectorAll('.hero-actions, .filter-buttons');
  if (actions.length > 0) {
    heroTL.fromTo(actions, { opacity: 0, y: 16, scale: 0.98 }, { opacity: 1, y: 0, scale: 1, duration: 0.65 }, 0.38);
  }

  // 5. Hero Media Frame (Home)
  const heroVisual = document.querySelector('.hero-visual');
  if (heroVisual) {
    heroTL.fromTo(heroVisual, { opacity: 0, scale: 0.96 }, { opacity: 1, scale: 1, duration: 1.1 }, 0.25);
  }

  // 6. Floating Card (Home)
  const floatingCard = document.querySelector('.floating-card');
  if (floatingCard) {
    heroTL.fromTo(floatingCard, { opacity: 0, y: 20 }, { opacity: 1, y: 0, duration: 0.8 }, 0.5);
  }

  // 7. Initial Cards Stagger (Contact info cards, stat cards)
  const introCards = document.querySelectorAll('.contact-cards-grid > .contact-info-card, .cards-grid-2 > .stat-card');
  if (introCards.length > 0) {
    heroTL.fromTo(
      introCards,
      { opacity: 0, y: 24, scale: 0.97 },
      { opacity: 1, y: 0, scale: 1, duration: 0.6, stagger: 0.08, ease: 'power2.out' },
      0.45
    );
  }

  // 8. Form Shell (Contact & Register pages)
  const formShell = document.querySelector('.form-shell');
  if (formShell) {
    heroTL.fromTo(formShell, { opacity: 0, y: 26 }, { opacity: 1, y: 0, duration: 0.7, ease: 'power2.out' }, 0.5);
  }
}

// ==========================================================================
// 6. SHARED PAGE TRANSITION SYSTEM
// ==========================================================================
function initSmoothPageTransitions() {
  // Inject top neon progress bar if not present
  let bar = document.getElementById('pageTransitionBar');
  if (!bar) {
    bar = document.createElement('div');
    bar.id = 'pageTransitionBar';
    bar.className = 'page-transition-bar';
    document.body.prepend(bar);
  }

  const main = document.querySelector('main') || document.querySelector('.main') || document.body;
  if (main) {
    main.classList.add('page-smooth-enter');
  }

  // Smooth link transitions across all internal routes
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href) return;

    // Ignore anchors, external links, protocols, downloads, or modifier keys
    if (
      href.startsWith('#') ||
      href.startsWith('mailto:') ||
      href.startsWith('tel:') ||
      href.startsWith('javascript:') ||
      link.hasAttribute('download') ||
      link.getAttribute('target') === '_blank' ||
      e.ctrlKey ||
      e.metaKey ||
      e.shiftKey ||
      e.altKey
    ) {
      return;
    }

    try {
      const destUrl = new URL(link.href, window.location.href);
      if (destUrl.origin !== window.location.origin) return;

      // Same page anchor or query
      if (destUrl.pathname === window.location.pathname && destUrl.search === window.location.search) {
        return;
      }

      e.preventDefault();
      if (bar) bar.classList.add('active');
      if (main) {
        main.classList.remove('page-smooth-enter');
        main.classList.add('page-smooth-exit');
      }

      setTimeout(() => {
        window.location.href = destUrl.href;
      }, 200);
    } catch {
      // Fallback to normal navigation on any URL parsing issue
    }
  });

  // Handle BFCache (Back/Forward button) restore
  window.addEventListener('pageshow', (e) => {
    if (bar) bar.classList.remove('active');
    if (main) {
      main.classList.remove('page-smooth-exit');
      main.classList.add('page-smooth-enter');
    }
  });
}

function initAmbientMotion() {
  if (typeof gsap === 'undefined') return;

  // Continuous Ambient Floating Motion
  const floatingCards = document.querySelectorAll('.floating-card');
  if (floatingCards.length > 0) {
    gsap.to('.floating-card', {
      y: 8,
      duration: 2,
      ease: 'sine.inOut',
      yoyo: true,
      repeat: -1
    });
  }
}

function initScrollAnimations() {
  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
  gsap.registerPlugin(ScrollTrigger);

  // Who We Are Number & Content
  if (document.querySelector('.who-we-are')) {
    gsap.from('.wwa-number', {
      scrollTrigger: {
        trigger: '.who-we-are',
        start: 'top 80%'
      },
      opacity: 0,
      x: -100,
      duration: 1.5,
      ease: 'power3.out'
    });

    gsap.from('.wwa-content > *', {
      scrollTrigger: {
        trigger: '.who-we-are',
        start: 'top 70%'
      },
      opacity: 0,
      y: 30,
      duration: 1,
      stagger: 0.2,
      ease: 'power3.out'
    });
  }

  // What Drives Us Drive Items
  if (document.querySelector('.what-drives-us')) {
    gsap.from('.drive-item', {
      scrollTrigger: {
        trigger: '.what-drives-us',
        start: 'top 70%'
      },
      opacity: 0,
      y: 30,
      duration: 1,
      stagger: 0.15,
      ease: 'power2.out'
    });
  }

  // Our Vision Huge Text & Animated Line
  if (document.querySelector('.our-vision')) {
    gsap.from('.huge-text', {
      scrollTrigger: {
        trigger: '.our-vision',
        start: 'top 60%'
      },
      opacity: 0,
      scale: 0.9,
      duration: 1.5,
      ease: 'power3.out'
    });

    gsap.to('.animated-line', {
      scrollTrigger: {
        trigger: '.our-vision',
        start: 'top 50%',
        end: 'bottom top',
        scrub: 1
      },
      width: '100%',
      ease: 'none'
    });
  }

  // Our Impact Stats
  if (document.querySelector('.our-impact')) {
    gsap.from('.stat-item, .stat-card', {
      scrollTrigger: {
        trigger: '.our-impact',
        start: 'top 80%'
      },
      opacity: 0,
      y: 50,
      duration: 1,
      stagger: 0.2,
      ease: 'power2.out'
    });
  }

  // Leadership Profile Cards
  if (document.querySelector('.leadership')) {
    gsap.from('.profile-card', {
      scrollTrigger: {
        trigger: '.leadership',
        start: 'top 75%'
      },
      opacity: 0,
      y: 40,
      duration: 1,
      stagger: 0.3,
      ease: 'power3.out'
    });
  }

  // Note From Us Visual & Content
  if (document.querySelector('.note-from-us')) {
    gsap.from('.note-visual', {
      scrollTrigger: {
        trigger: '.note-from-us',
        start: 'top 75%'
      },
      opacity: 0,
      x: -50,
      duration: 1,
      ease: 'power2.out'
    });

    gsap.from('.note-content > *', {
      scrollTrigger: {
        trigger: '.note-from-us',
        start: 'top 75%'
      },
      opacity: 0,
      x: 50,
      duration: 1,
      stagger: 0.2,
      ease: 'power2.out'
    });
  }

  // Horizontal Scroll Engine
  const hsContainer = document.querySelector('.horizontal-scroll-container');
  const hsPanels = gsap.utils.toArray('.hs-panel');
  if (hsContainer && hsPanels.length > 0 && window.innerWidth > 992) {
    gsap.to(hsPanels, {
      xPercent: -100 * (hsPanels.length - 1),
      ease: 'none',
      scrollTrigger: {
        trigger: '.global-mindset',
        pin: true,
        scrub: 1,
        snap: 1 / (hsPanels.length - 1),
        end: () => '+=' + hsContainer.offsetWidth
      }
    });
  }
}

// Utility Toast Alert
function showToast(msg, isError = false) {
  let toast = document.getElementById('publicToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'publicToast';
    toast.style.cssText = 'position:fixed; bottom:24px; right:24px; z-index:99999; padding:14px 24px; border-radius:8px; font-weight:600; font-size:0.92rem; display:flex; align-items:center; gap:10px; box-shadow:0 10px 30px rgba(0,0,0,0.6); transition:all 0.3s;';
    document.body.appendChild(toast);
  }

  toast.style.background = isError ? '#991B1B' : '#0F172A';
  toast.style.color = '#FFFFFF';
  toast.style.border = isError ? '1px solid #EF4444' : '1px solid #C8A951';
  toast.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}" style="color:${isError ? '#FCA5A5' : '#C8A951'}"></i> ${msg}`;
  toast.style.display = 'flex';
  setTimeout(() => { toast.style.display = 'none'; }, 4000);
}
