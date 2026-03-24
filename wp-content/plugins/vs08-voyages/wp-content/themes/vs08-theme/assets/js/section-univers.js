/**
 * VS08 — Section Univers : scroll animations + parallax souris
 * Fichier : assets/js/section-univers.js
 */
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.vs08-univers-card');
  if (!cards.length) return;

  // ─── Apparition au scroll ───
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.15,
    rootMargin: '0px 0px -60px 0px'
  });

  cards.forEach(card => observer.observe(card));

  // ─── Parallax subtil au mouvement de souris (desktop only) ───
  const bentoGrid = document.querySelector('.vs08-bento');
  if (!bentoGrid) return;

  if (window.matchMedia('(min-width: 1024px)').matches) {
    bentoGrid.addEventListener('mousemove', (e) => {
      const rect = bentoGrid.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width - 0.5;
      const y = (e.clientY - rect.top) / rect.height - 0.5;

      cards.forEach(card => {
        const img = card.querySelector('.vs08-univers-card__img img');
        if (img && !card.matches(':hover')) {
          img.style.transform = `scale(1) translate(${x * -4}px, ${y * -4}px)`;
        }
      });
    });

    bentoGrid.addEventListener('mouseleave', () => {
      cards.forEach(card => {
        const img = card.querySelector('.vs08-univers-card__img img');
        if (img) {
          img.style.transform = '';
        }
      });
    });
  }
});
