/* ============================================================
   VOYAGES SORTIR 08 — JS PRINCIPAL
============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    /* === HEADER SCROLL EFFECT === */
    const header = document.getElementById('vs08-header');
    if (header) {
        // Sur les pages sans hero plein écran, header sombre immédiatement
        const hasBigHero = document.querySelector('.vs08-hero');
        if (!hasBigHero) {
            header.classList.add('vs08-dark');
        }

        window.addEventListener('scroll', function () {
            if (window.scrollY > 80) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
                // Remettre dark si page interne
                if (!hasBigHero) header.classList.add('vs08-dark');
            }
        });
    }

    /* === MENU MOBILE TOGGLE === */
    const toggle = document.getElementById('vs08-menu-toggle');
    const navLinks = document.getElementById('vs08-nav-links');
    if (toggle && navLinks) {
        toggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
            document.body.style.overflow = navLinks.classList.contains('open') ? 'hidden' : '';
        });
        // Fermer en cliquant sur un lien
        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('open');
                document.body.style.overflow = '';
            });
        });
    }

    /* === SMOOTH SCROLL pour ancres === */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    /* === ANIMATION ENTRÉE AU SCROLL === */
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('vs08-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll(
        '.vs08-card, .vs08-why-item, .vs08-dest-card, .vs08-testi-card, .vs08-section-header'
    ).forEach(function (el) {
        el.classList.add('vs08-animate');
        observer.observe(el);
    });

});
