// ============================================================
// assets/js/main.js — Global JavaScript
// ============================================================
'use strict';

document.addEventListener('DOMContentLoaded', function () {

  // ── Navbar scroll effect ────────────────────────────────
  const navbar = document.querySelector('.navbar-catering');
  if (navbar) {
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 60);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ── Mobile nav toggle (public landing) ─────────────────
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks  = document.querySelector('.navbar-catering .nav-links');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      const open = navLinks.classList.toggle('open');
      navToggle.innerHTML = open
        ? '<i class="fas fa-times"></i>'
        : '<i class="fas fa-bars"></i>';
      document.body.style.overflow = open ? 'hidden' : '';
    });
    navLinks.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        navLinks.classList.remove('open');
        navToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.style.overflow = '';
      });
    });
  }

  // ── Sidebar toggle (dashboard, mobile) ─────────────────
  const sidebarToggle   = document.querySelector('.sidebar-toggle');
  const sidebar         = document.getElementById('sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');

  function openSidebar() {
    sidebar?.classList.add('open');
    sidebarBackdrop?.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar?.classList.remove('open');
    sidebarBackdrop?.classList.remove('show');
    document.body.style.overflow = '';
  }
  sidebarToggle?.addEventListener('click', openSidebar);
  sidebarBackdrop?.addEventListener('click', closeSidebar);

  // ── Modal system ─────────────────────────────────────────
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.modalOpen);
      if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  function closeModal(overlay) {
    overlay.classList.remove('open');
    if (!document.querySelector('.modal-overlay.open')) {
      document.body.style.overflow = '';
    }
  }

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) closeModal(overlay);
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const overlay = btn.closest('.modal-overlay');
      if (overlay) closeModal(overlay);
    });
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(closeModal);
    }
  });

  // ── Flash message auto-dismiss ──────────────────────────
  document.querySelectorAll('.alert-flash').forEach(flash => {
    setTimeout(() => {
      flash.style.transition = 'opacity 0.5s ease, transform 0.5s ease, max-height 0.5s ease, margin 0.5s ease, padding 0.5s ease';
      flash.style.opacity      = '0';
      flash.style.transform    = 'translateY(-6px)';
      flash.style.maxHeight    = '0';
      flash.style.marginBottom = '0';
      flash.style.paddingTop   = '0';
      flash.style.paddingBottom = '0';
      setTimeout(() => flash.remove(), 520);
    }, 4500);
  });

  // ── Password visibility toggle ──────────────────────────
  document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.previousElementSibling;
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = show
        ? '<i class="fas fa-eye-slash"></i>'
        : '<i class="fas fa-eye"></i>';
    });
  });

  // ── Animated number counter ─────────────────────────────
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });
    counters.forEach(c => obs.observe(c));
  }

  function animateCount(el) {
    const target    = parseInt(el.dataset.count, 10);
    const suffix    = el.dataset.suffix || '';
    const steps     = Math.round(1600 / (1000 / 60));
    let   current   = 0;
    const increment = target / steps;
    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        el.textContent = target.toLocaleString() + suffix;
        clearInterval(timer);
      } else {
        el.textContent = Math.floor(current).toLocaleString() + suffix;
      }
    }, 1000 / 60);
  }

  // ── Scroll reveal ───────────────────────────────────────
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    const revObs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const siblings = entry.target.parentElement?.querySelectorAll('.reveal');
          let delay = 0;
          if (siblings && siblings.length > 1) {
            Array.from(siblings).forEach((sib, idx) => {
              if (sib === entry.target) delay = idx * 60;
            });
          }
          setTimeout(() => entry.target.classList.add('revealed'), delay);
          revObs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
    revealEls.forEach(el => revObs.observe(el));
  }

  // ── Confirm delete ──────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  // ── Client-side table search ────────────────────────────
  const tableSearch = document.getElementById('table-search');
  if (tableSearch) {
    tableSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase().trim();
      document.querySelectorAll('.searchable-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

});
