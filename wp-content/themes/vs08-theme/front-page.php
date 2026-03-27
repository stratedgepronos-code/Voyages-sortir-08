<?php get_header(); ?>

<style>
/* ═══════════════════════════════════════════════════════════════
   FRONT-PAGE.PHP — CSS 100% INLINE
   Voyages Sortir 08 — Mars 2026
   Évite tous les conflits avec les CSS externes (front-page-v2.css,
   section-univers.css, homepage-v3.css).
   ═══════════════════════════════════════════════════════════════ */

/* ─── Variables (rappel — déjà dans main.css, on les re-déclare pour sécurité) ─── */
:root {
    --teal:#59b7b7;--teal-dark:#3d9a9a;--teal-light:#7ecece;--teal-ultra:#edf8f8;
    --coral:#e8724a;--coral-dark:#d4603c;--gold:#c9a84c;--gold-light:#e8c96e;
    --dark:#0f2424;--dark-mid:#1a3a3a;--cream:#f9f6f0;--white:#fff;
    --gray:#6b7280;--gray-light:#f0f2f4;
}

/* ─── Container commun ─── */
body.home,body.page-template-default{background:#fff!important}
.fp-container{max-width:1400px;margin:0 auto;padding:0 80px}

/* ═══════════════════════════════════════════════════════════════
   1. HERO CAROUSEL — préfixe hc-
   ═══════════════════════════════════════════════════════════════ */
.hc-wrap{position:relative;height:calc(100vh - 40px);min-height:700px;max-height:1100px;overflow:hidden;display:flex;align-items:center;background:#0b1120}
.hc-slide{position:absolute;inset:0;opacity:0;transition:opacity 1.2s ease;z-index:0}
.hc-slide.active{opacity:1;z-index:1}
.hc-slide-bg{position:absolute;inset:0;background-size:cover;background-position:center center;transition:transform 8s ease-out;transform:scale(1.05);transform-origin:center center}
.hc-slide.active .hc-slide-bg{transform:scale(1.08)}
.hc-slide-ov{position:absolute;inset:0}
.hc-content{position:relative;z-index:10;max-width:800px;padding:0 80px}
.hc-loc{font-size:13px;font-weight:600;color:var(--teal-light);letter-spacing:3px;text-transform:uppercase;margin-bottom:16px;display:flex;align-items:center;gap:10px}
.hc-loc::before{content:'';width:40px;height:1px;background:var(--teal-light)}
.hc-wrap h1{font-family:'Playfair Display',serif;font-size:clamp(40px,5.5vw,72px);font-weight:700;color:#fff;line-height:1.08;margin:0 0 20px;white-space:pre-line;text-shadow:0 2px 40px rgba(0,0,0,.3)}
.hc-wrap h1 em{font-style:italic;color:var(--teal-light)}
.hc-sub{font-size:20px;color:rgba(255,255,255,.85);margin:0 0 32px;font-weight:300;max-width:520px;line-height:1.6}
.hc-btns{display:flex;gap:16px;flex-wrap:wrap}
.hc-btns a{display:inline-block;padding:16px 36px;border-radius:40px;font-size:16px;font-weight:600;text-decoration:none;transition:transform .2s,box-shadow .2s}
.hc-btn-p{background:linear-gradient(135deg,var(--teal),var(--teal-dark));color:#fff;box-shadow:0 8px 30px rgba(89,183,183,.35)}
.hc-btn-p:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(89,183,183,.45);color:#fff}
.hc-btn-o{background:rgba(255,255,255,.1);backdrop-filter:blur(10px);color:#fff;border:1px solid rgba(255,255,255,.25)}
.hc-btn-o:hover{background:rgba(255,255,255,.2);color:#fff}
.hc-dots{position:absolute;bottom:80px;left:80px;z-index:10;display:flex;gap:8px}
.hc-dot{width:12px;height:4px;background:rgba(255,255,255,.35);border:none;border-radius:2px;cursor:pointer;transition:all .4s;padding:0}
.hc-dot.active{width:40px;background:var(--teal)}
.hc-conf{display:none}
.hc-conf-i{display:flex;align-items:center;gap:10px;font-size:13px;color:rgba(255,255,255,.7);font-weight:500}
.hc-conf-i span:first-child{font-size:18px}
.hc-stats{position:absolute;right:80px;bottom:100px;z-index:10;display:flex;gap:24px}
.hc-stat{text-align:center}
.hc-stat b{display:block;font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#fff}
.hc-stat small{font-size:10px;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:1.5px}
@keyframes hcUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}

/* ═══════════════════════════════════════════════════════════════
   2. BARRE DE RECHERCHE
   ═══════════════════════════════════════════════════════════════ */
.fp-search{position:relative;z-index:20;margin-top:-72px;padding:0 80px 14px}
.fp-search-card{background:#fff;border-radius:22px;padding:28px 36px;box-shadow:0 25px 80px rgba(0,0,0,.13);display:flex;align-items:flex-end;gap:0}
.fp-search-field{flex:1;padding:0 20px;border-right:1px solid var(--gray-light);display:flex;flex-direction:column;justify-content:flex-end}
.fp-search-field:first-child{padding-left:0}
.fp-search-field:last-of-type{border-right:none}
.fp-search-field label{display:block;font-size:10px;font-weight:700;color:var(--teal-dark);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:8px;flex-shrink:0}
.fp-search-field input,.fp-search-field select{width:100%;border:none;border-bottom:2px solid var(--gray-light);padding:10px 0;font-size:14px;line-height:20px;color:var(--dark);background:transparent;outline:none;cursor:pointer;transition:border-color .2s;font-family:'Outfit',sans-serif;height:40px;box-sizing:border-box}
.fp-search-field input:focus,.fp-search-field select:focus{border-bottom-color:var(--teal)}
.fp-search-field #fp-date-wrap{flex-shrink:0;margin:0;padding:0}
.fp-search-date-trigger{width:100%;border:none;border-bottom:2px solid var(--gray-light);padding:0;font-size:14px;line-height:20px;color:#9ca3af;background:transparent;cursor:pointer;transition:border-color .2s;font-family:'Outfit',sans-serif;height:40px;box-sizing:border-box;display:flex;align-items:center}
#fp-date-wrap:focus-within .fp-search-date-trigger{border-bottom-color:var(--teal)}
.fp-btn-search{background:var(--coral);color:#fff;border:none;padding:18px 32px;border-radius:14px;font-size:15px;font-weight:700;cursor:pointer;margin-left:20px;flex-shrink:0;white-space:nowrap;transition:all .3s;box-shadow:0 6px 25px rgba(232,114,74,.35);font-family:'Outfit',sans-serif}
.fp-btn-search:hover{background:var(--coral-dark);transform:translateY(-2px)}

/* ═══════════════════════════════════════════════════════════════
   3. SECTION NOS UNIVERS — Bento Grid
   ═══════════════════════════════════════════════════════════════ */
.fp-univers{margin-top:-100px;padding:8.5rem 0 8rem;position:relative;z-index:1;background:#faf7f2;overflow:hidden}
.fp-univers::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 20% 10%,rgba(89,183,183,.06) 0%,transparent 60%),radial-gradient(ellipse 60% 50% at 85% 80%,rgba(200,164,94,.05) 0%,transparent 60%);pointer-events:none}
.fp-univers::after{content:'';position:absolute;top:-80px;right:-120px;width:500px;height:500px;border-radius:50%;border:1px solid rgba(89,183,183,.08);pointer-events:none;box-shadow:0 0 0 40px rgba(89,183,183,.04),0 0 0 80px rgba(89,183,183,.02)}
.fp-univers .fp-container{position:relative;z-index:1}
.fp-univers-header{text-align:center;margin-bottom:4rem}
.fp-univers-label{display:inline-flex;align-items:center;gap:.5rem;font-family:'Outfit',sans-serif;font-weight:600;font-size:.82rem;text-transform:uppercase;letter-spacing:.18em;color:#59b7b7;margin-bottom:1rem;padding:.45rem 1.2rem;background:rgba(89,183,183,.08);border-radius:9999px;border:1px solid rgba(89,183,183,.12)}
.fp-univers-title{font-family:'Playfair Display',Georgia,serif;font-weight:600;font-size:clamp(2rem,4vw,3rem);line-height:1.15;color:#1a1a2e;margin-bottom:1rem}
.fp-univers-title em{font-style:italic;color:#59b7b7}
.fp-univers-subtitle{font-family:'Outfit',sans-serif;font-size:1.1rem;color:#4a5568;max-width:560px;margin:0 auto;line-height:1.7}

/* ─── Bento Grid ─── */
.fp-bento{display:grid;grid-template-columns:repeat(12,1fr);grid-template-rows:auto;gap:1.25rem;grid-template-areas:"golf golf golf golf golf golf golf sejour sejour sejour sejour sejour" "circuit circuit circuit circuit road road road road parcs parcs parcs parcs"}

/* ─── Card base ─── */
.fp-ucard{position:relative;border-radius:16px;overflow:hidden;cursor:pointer;text-decoration:none;display:flex;flex-direction:column;justify-content:flex-end;min-height:320px;transition:transform .5s cubic-bezier(.23,1,.32,1),box-shadow .5s cubic-bezier(.23,1,.32,1);box-shadow:0 4px 24px rgba(0,0,0,.08);opacity:0;transform:translateY(40px)}
.fp-ucard.visible{opacity:1;transform:translateY(0);transition:opacity .8s cubic-bezier(.23,1,.32,1),transform .8s cubic-bezier(.23,1,.32,1),box-shadow .5s ease}
.fp-ucard:hover{transform:translateY(-6px);box-shadow:0 20px 60px rgba(0,0,0,.18)}

/* ─── Grid areas ─── */
.fp-ucard--golf{grid-area:golf;min-height:420px}
.fp-ucard--sejour{grid-area:sejour;min-height:420px}
.fp-ucard--circuit{grid-area:circuit}
.fp-ucard--road{grid-area:road}
.fp-ucard--parcs{grid-area:parcs}

/* ─── Image de fond ─── */
.fp-ucard__img{position:absolute;inset:0;z-index:0}
.fp-ucard__img img{width:100%;height:100%;object-fit:cover;transition:transform 1.2s cubic-bezier(.23,1,.32,1),filter .6s ease;will-change:transform}
.fp-ucard:hover .fp-ucard__img img{transform:scale(1.08)}
/* Circuit & Road-trip & Parcs : remplir tout l'espace, ancrage haut */
.fp-ucard--circuit .fp-ucard__img img,
.fp-ucard--road .fp-ucard__img img,
.fp-ucard--parcs .fp-ucard__img img{object-position:center top}

/* ─── Overlay gradient ─── */
.fp-ucard__overlay{position:absolute;inset:0;z-index:1;background:linear-gradient(0deg,rgba(11,17,32,.88) 0%,rgba(11,17,32,.55) 35%,rgba(11,17,32,.15) 65%,rgba(11,17,32,.05) 100%);transition:background .5s ease}
.fp-ucard:hover .fp-ucard__overlay{background:linear-gradient(0deg,rgba(11,17,32,.92) 0%,rgba(11,17,32,.60) 40%,rgba(11,17,32,.20) 70%,rgba(11,17,32,.08) 100%)}

/* ─── Contenu texte ─── */
.fp-ucard__content{position:relative;z-index:2;padding:2rem 2rem 2.2rem;display:flex;flex-direction:column;gap:.6rem}
.fp-ucard__badge{display:inline-flex;align-items:center;gap:.4rem;width:fit-content;font-family:'Outfit',sans-serif;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.15em;color:#59b7b7;background:rgba(89,183,183,.15);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(89,183,183,.25);padding:.35rem .85rem;border-radius:9999px;transition:background .3s,border-color .3s}
.fp-ucard:hover .fp-ucard__badge{background:rgba(89,183,183,.25);border-color:rgba(89,183,183,.4)}
.fp-ucard__title{font-family:'Playfair Display',Georgia,serif;font-weight:600;font-size:1.65rem;line-height:1.2;color:#fff;transition:transform .4s cubic-bezier(.23,1,.32,1)}
.fp-ucard--golf .fp-ucard__title{font-size:2.2rem}
.fp-ucard:hover .fp-ucard__title{transform:translateX(4px)}
.fp-ucard__desc{font-family:'Outfit',sans-serif;font-weight:300;font-size:.95rem;line-height:1.55;color:rgba(255,255,255,.7);max-width:90%;opacity:0;transform:translateY(10px);transition:opacity .4s ease .08s,transform .4s cubic-bezier(.23,1,.32,1) .08s;max-height:0;overflow:hidden}
.fp-ucard:hover .fp-ucard__desc{opacity:1;transform:translateY(0);max-height:100px}

/* ─── Flèche CTA ─── */
.fp-ucard__arrow{position:absolute;top:1.5rem;right:1.5rem;z-index:2;width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.1);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;transition:all .4s cubic-bezier(.23,1,.32,1)}
.fp-ucard__arrow svg{width:18px;height:18px;stroke:rgba(255,255,255,.7);stroke-width:2;fill:none;transition:stroke .3s,transform .4s cubic-bezier(.23,1,.32,1)}
.fp-ucard:hover .fp-ucard__arrow{background:#59b7b7;border-color:#59b7b7;transform:scale(1.08)}
.fp-ucard:hover .fp-ucard__arrow svg{stroke:#fff;transform:translateX(2px)}

/* ─── Compteur séjours ─── */
.fp-ucard__count{position:absolute;bottom:2.2rem;right:2rem;z-index:2;font-family:'Outfit',sans-serif;font-weight:500;font-size:.8rem;color:rgba(255,255,255,.5);letter-spacing:.05em;display:flex;align-items:center;gap:.4rem;transition:color .3s}
.fp-ucard:hover .fp-ucard__count{color:rgba(255,255,255,.8)}
.fp-ucard__count::before{content:'';width:24px;height:1px;background:rgba(255,255,255,.3);transition:width .4s,background .3s}
.fp-ucard:hover .fp-ucard__count::before{width:36px;background:#59b7b7}
.fp-ucard__soon{position:absolute;top:1.2rem;right:1.2rem;z-index:3;font-family:'Outfit',sans-serif;font-weight:700;font-size:.7rem;text-transform:uppercase;letter-spacing:.12em;color:#fff;background:linear-gradient(135deg,#c9a84c,#e8724a);padding:.35rem .9rem;border-radius:100px;box-shadow:0 4px 16px rgba(232,114,74,.3)}

/* ─── Ligne déco bas carte ─── */
.fp-ucard__line{position:absolute;bottom:0;left:0;height:3px;width:0;background:linear-gradient(90deg,#59b7b7,#c8a45e);z-index:3;transition:width .6s cubic-bezier(.23,1,.32,1);border-radius:0 3px 0 0}
.fp-ucard:hover .fp-ucard__line{width:100%}

/* ─── Staggered delays ─── */
.fp-ucard:nth-child(1){transition-delay:0s}
.fp-ucard:nth-child(2){transition-delay:.12s}
.fp-ucard:nth-child(3){transition-delay:.24s}
.fp-ucard:nth-child(4){transition-delay:.36s}
.fp-ucard:nth-child(5){transition-delay:.48s}

/* ═══════════════════════════════════════════════════════════════
   4. PONTS VISUELS (bridges)
   ═══════════════════════════════════════════════════════════════ */
.fp-bridge{background:linear-gradient(180deg,#fff 0%,var(--teal-ultra) 50%,#fff 100%);padding:40px 80px;position:relative}
.fp-bridge-inner{max-width:1400px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:28px}
.fp-bridge-inner::before,.fp-bridge-inner::after{content:'';display:block;width:120px;height:1px;background:linear-gradient(90deg,transparent,rgba(89,183,183,.45));flex-shrink:0}
.fp-bridge-inner::after{background:linear-gradient(90deg,rgba(89,183,183,.45),transparent)}
.fp-bridge p{margin:0;font-size:13px;font-weight:600;color:var(--teal);text-transform:uppercase;letter-spacing:2px;flex-shrink:0}

/* ═══════════════════════════════════════════════════════════════
   5. SECTION HEADERS COMMUN
   ═══════════════════════════════════════════════════════════════ */
.fp-section-header{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:56px}
.fp-section-label{font-size:11px;font-weight:700;color:var(--teal);text-transform:uppercase;letter-spacing:2.5px;margin-bottom:12px}
.fp-section-title{font-size:clamp(30px,4vw,48px);font-weight:700;color:var(--dark);line-height:1.2}
.fp-section-title em{font-style:italic;color:var(--teal)}
.fp-section-link{color:var(--teal-dark);font-weight:600;font-size:14px;white-space:nowrap;transition:all .2s;text-decoration:none}
.fp-section-link:hover{color:var(--teal);letter-spacing:.3px}

/* ═══════════════════════════════════════════════════════════════
   6. SÉJOURS COUPS DE CŒUR (cards)
   ═══════════════════════════════════════════════════════════════ */
.fp-sejours{padding:60px 0 80px;background:#fff}
.fp-cards-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:32px;margin-bottom:48px}
/* Force les cards à rester visibles même si .visible pas ajouté */
.fp-sejours .fp-cards-grid .scard.anim{opacity:1!important;transform:none!important}
.scard{background:#fff;border:1.5px solid var(--gray-light);border-radius:22px;overflow:hidden;display:flex;flex-direction:column;cursor:pointer;transition:transform .4s,box-shadow .4s,border-color .3s;position:relative}
.scard:hover{transform:translateY(-8px);box-shadow:0 30px 70px rgba(0,0,0,.14);border-color:var(--teal)}
.scard-featured{grid-column:span 3;flex-direction:row}
.scard-featured .scard-img{width:42%;flex-shrink:0}
.scard-featured .scard-img img{width:100%;height:100%;min-height:100%;object-fit:cover}
.scard-featured .scard-body{padding:30px 36px;display:flex;flex-direction:column;justify-content:center}
.scard-featured .scard-body h3{font-size:28px;margin-bottom:6px}
.scard-featured .scard-desc{-webkit-line-clamp:3}
.scard-img{overflow:hidden;position:relative;flex-shrink:0}
.scard-img img{width:100%;height:280px;object-fit:cover;transition:transform .6s}
.scard:hover .scard-img img{transform:scale(1.06)}
.scard-badges{position:absolute;top:14px;left:14px;display:flex;gap:6px;z-index:2}
.badge{padding:5px 13px;border-radius:100px;font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#fff}
.badge-new{background:var(--teal-dark)}.badge-promo{background:var(--coral)}.badge-best{background:var(--gold)}
.scard-hotel-badge{position:absolute;bottom:14px;left:14px;z-index:2;display:flex;align-items:center;gap:6px;background:rgba(15,36,36,.85);backdrop-filter:blur(8px);padding:6px 14px;border-radius:100px}
.scard-hotel-badge span{font-size:11px;color:#fff;font-weight:600}
.scard-hotel-badge .stars-sm{color:var(--gold);font-size:9px;letter-spacing:1px}
.scard-body{padding:24px;flex:1;display:flex;flex-direction:column}
.scard-country{font-size:12px;color:var(--teal);font-weight:700;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:6px}
.scard-body h3{font-size:21px;font-weight:700;color:var(--dark);margin-bottom:8px;line-height:1.3}
.scard-desc{font-size:14px;color:var(--gray);line-height:1.65;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.scard-highlights{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.scard-chip{display:flex;align-items:center;gap:4px;background:var(--teal-ultra);color:var(--teal-dark);font-size:12px;font-weight:600;padding:5px 12px;border-radius:100px;white-space:nowrap}
.scard-chip.chip-gold{background:#fdf6e3;color:#8a6d1b}
.scard-divider{height:1px;background:var(--gray-light);margin-bottom:14px}
.scard-footer{display:flex;justify-content:space-between;align-items:center;margin-top:auto;flex-shrink:0}
.scard-price{text-align:left}
.scard-price .price-label{font-size:10px;color:var(--gray);text-transform:uppercase;letter-spacing:.5px}
.scard-price .price-amount{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:var(--teal-dark);line-height:1}
.scard-price .price-per{font-size:10px;color:var(--gray)}
.scard-price-hint{font-size:10px;color:var(--gray);line-height:1.45;margin-top:8px;max-width:260px;opacity:.92}
.scard-btn{display:flex;align-items:center;gap:6px;background:var(--dark);color:#fff;padding:12px 24px;border-radius:100px;font-size:14px;font-weight:700;transition:all .3s;white-space:nowrap;text-decoration:none}
.scard-btn:hover{background:var(--teal-dark);transform:translateY(-2px);box-shadow:0 8px 24px rgba(89,183,183,.3);color:#fff}
.scard-golfs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.scard-golf-chip{display:flex;align-items:center;gap:6px;background:var(--cream);border:1px solid var(--gray-light);padding:6px 12px;border-radius:10px;white-space:nowrap;transition:border-color .2s}
.scard-golf-chip:hover{border-color:var(--teal)}
.scard-golf-chip .gchip-icon{font-size:15px}
.scard-golf-chip .gchip-name{font-size:12px;font-weight:600;color:var(--dark)}
.scard-golf-chip .gchip-holes{font-size:11px;color:var(--gray);font-weight:400}

/* ═══════════════════════════════════════════════════════════════
   7. SHOWCASE SECTIONS — préfixe sh-
   ═══════════════════════════════════════════════════════════════ */
.sh-section{padding:80px 0;position:relative;overflow:hidden}
.sh-dark{background:#0f2424}
.sh-gradient{background:linear-gradient(180deg,#0e1a30,#0b1120)}
.sh-glow{position:absolute;border-radius:50%;pointer-events:none}
.sh-head{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:40px;flex-wrap:wrap;gap:20px}
.sh-label{font-size:13px;font-weight:600;letter-spacing:3px;text-transform:uppercase}
.sh-title{font-family:'Playfair Display',serif;font-size:clamp(30px,3.5vw,42px);font-weight:700;color:#fff;margin:12px 0 0}
.sh-sub{font-size:16px;color:rgba(255,255,255,.6);margin:8px 0 0;max-width:500px}
.sh-link{background:transparent;border:1px solid;padding:12px 28px;border-radius:30px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:background .2s}
.sh-link:hover{background:rgba(255,255,255,.08)}
.sh-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.sh-card{border-radius:16px;overflow:hidden;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);cursor:pointer;transition:transform .3s,border-color .3s;text-decoration:none;display:block}
.sh-card:hover{transform:translateY(-6px)}
.sh-card-img{position:relative;height:180px;overflow:hidden}
.sh-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.sh-card:hover img{transform:scale(1.06)}
.sh-badges{position:absolute;top:12px;right:12px;display:flex;gap:6px}
.sh-badge{backdrop-filter:blur(6px);font-size:11px;font-weight:600;padding:4px 10px;border-radius:10px}
.sh-card-body{padding:16px 18px}
.sh-country{font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;margin:0 0 4px}
.sh-name{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#fff;margin:0 0 8px}
.sh-foot{display:flex;justify-content:space-between;align-items:center}
.sh-price{font-size:14px;font-weight:700}
.sh-per{font-size:12px;color:rgba(255,255,255,.4)}

/* ═══════════════════════════════════════════════════════════════
   9. DUAL SECTION (Circuits + Road-Trips) — préfixe dl-
   ═══════════════════════════════════════════════════════════════ */
.dl-section{padding:60px 0;background:var(--cream)}
.dl-grid{display:flex;gap:24px}
.dl-half{flex:1;border-radius:24px;padding:40px 36px;overflow:hidden}
.dl-dark{background:#0f2424}
.dl-light{background:#fff;border:1px solid rgba(11,17,32,.06)}
.dl-item{display:flex;gap:20px;align-items:stretch;border-radius:14px;overflow:hidden;cursor:pointer;transition:transform .2s;margin-bottom:14px;text-decoration:none}
.dl-item:hover{transform:translateX(4px)}
.dl-item-dark{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.06)}
.dl-item-light{background:rgba(11,17,32,.03);border:1px solid rgba(11,17,32,.06)}
.dl-item-photo{width:170px;min-width:170px;flex-shrink:0;overflow:hidden;position:relative}
.dl-item-body{flex:1;display:flex;flex-direction:column;min-width:0;padding:12px 16px 12px 0}
.dl-item-photo img{width:100%;height:100%;object-fit:cover;object-position:center;display:block}
.dl-link{display:block;margin-top:20px;background:transparent;border:1px solid;padding:10px 24px;border-radius:25px;font-size:13px;font-weight:600;cursor:pointer;width:100%;text-align:center;text-decoration:none;transition:background .2s}
.dl-link:hover{background:rgba(255,255,255,.06)}
.sh-chips{display:flex;flex-wrap:wrap;gap:5px;margin:8px 0 10px}.sh-chip{font-size:10px;font-weight:600;padding:3px 9px;border-radius:8px;background:rgba(255,255,255,.08);color:rgba(255,255,255,.55);white-space:nowrap}
.sh-golfs{margin:0 0 10px}.sh-golf-name{display:block;font-size:10.5px;color:rgba(255,255,255,.35);line-height:1.5}
.dl-chips{display:flex;flex-wrap:wrap;gap:3px;margin:4px 0 6px}
.dl-chip{font-size:9px;font-weight:600;padding:2px 7px;border-radius:6px;white-space:nowrap}
.dl-chip-d{background:rgba(255,255,255,.06);color:rgba(255,255,255,.5)}.dl-chip-l{background:rgba(11,17,32,.05);color:rgba(11,17,32,.45)}
.dl-badge{position:absolute;top:8px;left:8px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:6px;z-index:2;letter-spacing:.3px;text-transform:uppercase}

/* ═══════════════════════════════════════════════════════════════
   10. WAVE DIVIDERS
   ═══════════════════════════════════════════════════════════════ */
.fp-wave{position:relative;margin-top:-1px;line-height:0;display:block}
.fp-wave svg{display:block;width:100%;height:60px;vertical-align:bottom}

/* ═══════════════════════════════════════════════════════════════
   11. POURQUOI NOUS FAIRE CONFIANCE
   ═══════════════════════════════════════════════════════════════ */
.fp-why{background:var(--dark);position:relative;overflow:hidden;padding:100px 0}
.fp-why::before{content:'';position:absolute;top:-200px;right:-200px;width:600px;height:600px;background:radial-gradient(circle,rgba(89,183,183,.08) 0%,transparent 70%);pointer-events:none}
.fp-why-glow-wrap{position:absolute;top:0;left:0;right:0;bottom:100px;pointer-events:none;overflow:hidden;z-index:0;-webkit-mask-image:radial-gradient(ellipse 90% 70% at 50% 40%,black 20%,transparent 70%);mask-image:radial-gradient(ellipse 90% 70% at 50% 40%,black 20%,transparent 70%)}
.fp-why-glow{position:absolute;width:360px;height:360px;border-radius:50%;background:radial-gradient(circle,rgba(89,183,183,.14) 0%,rgba(89,183,183,.04) 38%,transparent 65%);pointer-events:none;transform:translate(-50%,-50%);transition:opacity .25s;opacity:0;filter:blur(32px)}
.fp-label-light{color:var(--teal-light)}
.fp-title-white{color:#fff}
.fp-title-white em{color:var(--teal-light)}
.fp-why-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:28px;margin-top:56px}
.fp-why-item{padding:36px 28px;border:1px solid rgba(89,183,183,.12);border-radius:20px;background:rgba(255,255,255,.02);transition:all .35s;/* PAS de .anim — directement visible */}
.fp-why-item:hover{border-color:rgba(89,183,183,.35);background:rgba(89,183,183,.06);transform:translateY(-5px)}
.fp-why-icon{font-size:28px;margin-bottom:22px}
.fp-why-item h3{font-size:20px;color:#fff;margin-bottom:12px}
.fp-why-item p{font-size:13px;color:rgba(255,255,255,.45);line-height:1.75}

/* ═══════════════════════════════════════════════════════════════
   12. DESTINATIONS — CARTE DU MONDE INTERACTIVE
   ═══════════════════════════════════════════════════════════════ */
.fp-dest{padding:80px 0 100px;background:#fff}
.fp-map-box{background:#0b1120;border-radius:24px;overflow:hidden;position:relative;margin:0 auto;max-width:1400px}
.fp-map-box::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 40% at 50% 40%,rgba(89,183,183,.03),transparent);pointer-events:none}
.fp-map-airports{display:flex;justify-content:center;align-items:center;gap:5px;flex-wrap:wrap;padding:20px 24px 10px;position:relative;z-index:2}
.fp-map-apl{font-size:10px;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:1.5px;margin-right:6px;font-family:'Outfit',sans-serif}
.fp-map-ab{padding:5px 12px;border-radius:100px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);color:rgba(255,255,255,.45);transition:all .2s;font-family:'Outfit',sans-serif}
.fp-map-ab:hover{border-color:rgba(89,183,183,.3);color:rgba(255,255,255,.7)}
.fp-map-ab.on{background:rgba(89,183,183,.15);border-color:rgba(89,183,183,.4);color:#7ecece}
.fp-map-svg-wrap svg{cursor:grab}.fp-map-svg-wrap svg:active{cursor:grabbing}
.fp-map-zoom-reset{position:absolute;bottom:16px;right:16px;z-index:10;padding:8px 18px;border-radius:100px;border:1px solid rgba(89,183,183,.3);background:rgba(11,17,32,.85);color:#7ecece;font-family:'Outfit',sans-serif;font-size:12px;font-weight:600;cursor:pointer;backdrop-filter:blur(8px);transition:all .25s}
.fp-map-zoom-reset:hover{background:rgba(89,183,183,.15);border-color:#59b7b7}
.fp-map-svg-wrap{position:relative;width:100%;overflow:hidden}
.fp-map-svg-wrap svg{display:block;width:100%}
.fp-map-tt{position:absolute;pointer-events:none;opacity:0;transform:translateY(6px) scale(.96);transition:opacity .22s,transform .22s cubic-bezier(.22,1,.36,1);z-index:50;font-family:'Outfit',sans-serif}
.fp-map-tt.on{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}
.fp-map-tti{background:rgba(12,18,35,.96);border:1px solid rgba(89,183,183,.18);border-radius:16px;padding:20px 22px;min-width:245px;max-width:280px;backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px)}
.fp-map-tti .fp-tt-iata{font-size:13px;font-weight:700;color:rgba(255,255,255,.25);letter-spacing:2px;margin-bottom:6px}
.fp-map-tti .fp-tt-city{font-weight:700;font-size:18px;color:#fff;margin-bottom:2px}
.fp-map-tti .fp-tt-region{font-size:10px;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:12px}
.fp-map-tti .fp-tt-tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.fp-map-tti .fp-tt-tag{font-size:10px;font-weight:600;padding:4px 10px;border-radius:100px}
.fp-tt-tag-golf{background:rgba(201,168,76,.15);color:#e8c96e;border:1px solid rgba(201,168,76,.25)}
.fp-tt-tag-ai{background:rgba(89,183,183,.12);color:#7ecece;border:1px solid rgba(89,183,183,.2)}
.fp-tt-tag-beach{background:rgba(232,114,74,.12);color:#f0997b;border:1px solid rgba(232,114,74,.2)}
.fp-tt-tag-circuit{background:rgba(160,140,220,.12);color:#c0b0f0;border:1px solid rgba(160,140,220,.2)}
.fp-map-tti .fp-tt-desc{font-size:12px;color:rgba(255,255,255,.45);line-height:1.55;margin-bottom:14px}
.fp-map-tti .fp-tt-price{font-size:15px;font-weight:700;color:#59b7b7;margin-bottom:14px}
.fp-map-tti .fp-tt-btn{display:block;width:100%;text-align:center;background:linear-gradient(135deg,#59b7b7,#3d9a9a);color:#fff;border:none;padding:10px 16px;border-radius:100px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;transition:transform .15s,box-shadow .15s;font-family:'Outfit',sans-serif}
.fp-map-tti .fp-tt-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(89,183,183,.3);color:#fff}
.fp-map-legend{display:flex;justify-content:center;gap:24px;padding:12px 24px 18px;position:relative;z-index:2}
.fp-map-legend span{display:flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.35);font-family:'Outfit',sans-serif}
.fp-map-legend i{width:8px;height:8px;border-radius:50%;display:inline-block}
@keyframes fp-map-pulse{0%,100%{r:6;opacity:.25}50%{r:20;opacity:0}}
@keyframes fp-map-dash{to{stroke-dashoffset:-22}}

/* ═══════════════════════════════════════════════════════════════
   13. TRUST BAR
   ═══════════════════════════════════════════════════════════════ */
.fp-trust{padding:32px 0;background:var(--white);border-top:1px solid var(--gray-light);border-bottom:1px solid var(--gray-light)}
.fp-trust-row{display:flex;align-items:center;justify-content:center;gap:0;flex-wrap:nowrap}
.fp-trust-item{display:flex;align-items:center;gap:12px;white-space:nowrap;padding:0 28px}
.fp-trust-logo{height:36px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.fp-trust-logo svg,.fp-trust-logo img{height:36px;width:auto;max-width:90px;object-fit:contain;opacity:.7;transition:opacity .3s}
.fp-trust-item:hover .fp-trust-logo svg,.fp-trust-item:hover .fp-trust-logo img{opacity:1}
.fp-trust-text{display:flex;flex-direction:column}
.fp-trust-text strong{font-size:12px;color:var(--dark);font-weight:700}
.fp-trust-text span{font-size:10px;color:var(--gray)}
.fp-trust-sep{width:1px;height:32px;background:var(--gray-light);flex-shrink:0}

/* ═══════════════════════════════════════════════════════════════
   14. TÉMOIGNAGES
   ═══════════════════════════════════════════════════════════════ */
.fp-testi{padding:100px 0;background:var(--teal-ultra)}
.fp-testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin-top:56px}
.fp-testi-card{background:#fff;border-radius:22px;padding:36px;position:relative;transition:transform .3s,box-shadow .3s,opacity .6s ease}
.fp-testi-card:hover{transform:translateY(-5px);box-shadow:0 24px 60px rgba(89,183,183,.15)}
.fp-testi-card.fade-out{opacity:0;transform:translateY(10px)}
.fp-testi-card.fade-in{opacity:1;transform:translateY(0)}
.fp-testi-dots{display:flex;justify-content:center;gap:8px;margin-top:32px}
.fp-testi-dot{width:10px;height:10px;border-radius:50%;background:var(--teal-light);opacity:.3;cursor:pointer;transition:all .3s;border:none}
.fp-testi-dot.active{opacity:1;transform:scale(1.3);background:var(--teal-dark)}
.fp-stars{position:absolute;top:34px;right:34px;color:var(--gold);font-size:12px;letter-spacing:2px}
.fp-quote{font-size:52px;color:rgba(89,183,183,.4);font-family:'Playfair Display',serif;line-height:1;margin-bottom:14px}
.fp-testi-card p{font-size:15px;color:var(--dark-mid);line-height:1.75;font-style:italic;margin-bottom:26px}
.fp-testi-author{display:flex;align-items:center;gap:14px}
.fp-avatar{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0}
.fp-author-name{font-weight:700;font-size:14px;color:var(--dark);margin:0;font-style:normal}
.fp-author-trip{font-size:12px;color:var(--teal);margin:2px 0 0;font-style:normal}

/* ═══════════════════════════════════════════════════════════════
   15. NEWSLETTER + CTA
   ═══════════════════════════════════════════════════════════════ */
.fp-nl-cta{padding:60px 80px 100px;background:#fff}
.fp-nl-band{display:flex;align-items:stretch;border-radius:28px;overflow:hidden;min-height:340px;box-shadow:0 24px 60px rgba(15,36,36,.18)}
.fp-nl-band .fp-nl-side{flex:1;position:relative;padding:56px 48px;display:flex;flex-direction:column;justify-content:center;max-width:50%;overflow:hidden}
.fp-nl-band .fp-nl-side::before{content:'';position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1593111774240-d529f12cf4bb?w=800&q=80') center/cover;z-index:0}
.fp-nl-band .fp-nl-side::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,36,36,.92) 0%,rgba(26,58,58,.88) 100%);z-index:0}
.fp-nl-band .fp-nl-side > *{position:relative;z-index:1}
.fp-nl-sep{width:4px;flex-shrink:0;background:linear-gradient(180deg,transparent,var(--teal) 20%,var(--coral) 80%,transparent);opacity:.9}
.fp-nl-band .fp-cta-side{flex:1;position:relative;padding:56px 48px;display:flex;flex-direction:column;justify-content:center;align-items:center;max-width:50%;overflow:hidden}
.fp-nl-band .fp-cta-side::before{content:'';position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=800&q=80') center/cover;z-index:0}
.fp-nl-band .fp-cta-side::after{content:'';position:absolute;inset:0;background:linear-gradient(165deg,rgba(15,36,36,.92) 0%,rgba(26,58,58,.88) 100%);z-index:0}
.fp-nl-band .fp-cta-side > *{position:relative;z-index:1}
.fp-nl-badge{display:inline-block;font-size:11px;font-weight:700;color:var(--teal-light);text-transform:uppercase;letter-spacing:2px;margin-bottom:16px}
.fp-nl-side h2{font-size:28px;color:#fff;margin-bottom:12px;line-height:1.25}
.fp-nl-side h2 em{color:var(--teal-light);font-style:italic}
.fp-nl-side > p{font-size:14px;color:rgba(255,255,255,.55);margin-bottom:24px;line-height:1.7;max-width:380px}
.fp-nl-form{display:flex;gap:12px}
.fp-nl-form input{flex:1;padding:14px 20px;border:2px solid rgba(255,255,255,.15);border-radius:100px;font-size:14px;outline:none;transition:border-color .2s;background:rgba(255,255,255,.08);color:#fff;font-family:'Outfit',sans-serif}
.fp-nl-form input::placeholder{color:rgba(255,255,255,.35)}
.fp-nl-form input:focus{border-color:var(--teal)}
.fp-nl-form button{background:var(--coral);color:#fff;border:none;padding:14px 28px;border-radius:100px;font-size:13px;font-weight:700;cursor:pointer;transition:all .3s;box-shadow:0 6px 25px rgba(232,114,74,.4);white-space:nowrap;font-family:'Outfit',sans-serif}
.fp-nl-form button:hover{background:var(--coral-dark);transform:translateY(-2px)}
.fp-nl-perks{display:flex;gap:20px;margin-top:16px;flex-wrap:wrap}
.fp-nl-perk{font-size:11px;color:rgba(255,255,255,.45);display:flex;align-items:center;gap:6px}
.fp-nl-perk span{color:var(--teal-light);font-size:13px}
.fp-nl-legal{font-size:10px;color:rgba(255,255,255,.3);margin-top:12px}
/* CTA devis */
.fp-cta-wrap{width:100%;max-width:420px}
.fp-cta-box{text-align:center;padding:0}
.fp-cta-eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:700;color:var(--teal-light);text-transform:uppercase;letter-spacing:3px;margin-bottom:28px}
.fp-cta-eyebrow::before,.fp-cta-eyebrow::after{content:'';width:24px;height:1px;background:currentColor;opacity:.6}
.fp-cta-box h2{font-size:clamp(28px,3.2vw,38px);color:#fff;margin-bottom:18px;line-height:1.15;font-family:'Playfair Display',serif;font-weight:700;letter-spacing:-.03em}
.fp-cta-desc{color:rgba(255,255,255,.7);font-size:15px;line-height:1.65;margin-bottom:36px;max-width:320px;margin-left:auto;margin-right:auto}
.fp-cta-trust{display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-bottom:32px;font-size:12px;color:rgba(255,255,255,.5)}
.fp-cta-trust span{display:inline-flex;align-items:center;gap:6px}
.fp-cta-trust span::before{content:'✓';color:var(--teal-light);font-weight:800}
.fp-btn-devis{display:inline-flex;align-items:center;justify-content:center;gap:14px;width:100%;max-width:320px;background:#fff;color:var(--dark);padding:22px 36px;border-radius:100px;font-size:17px;font-weight:800;border:none;cursor:pointer;transition:all .35s;box-shadow:0 20px 50px rgba(0,0,0,.25);font-family:'Outfit',sans-serif;text-decoration:none;letter-spacing:.02em}
.fp-btn-devis:hover{transform:translateY(-4px) scale(1.02);color:var(--dark);box-shadow:0 28px 60px rgba(0,0,0,.35);background:var(--teal-light)}
.fp-btn-devis .btn-arrow{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:var(--coral);color:#fff;font-size:16px;transition:transform .3s}
.fp-btn-devis:hover .btn-arrow{transform:translateX(4px);background:var(--coral-dark)}
.fp-cta-phone{margin-top:32px}
.fp-cta-phone span{font-size:11px;color:rgba(255,255,255,.4);display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:2px}
.fp-cta-phone a{display:inline-flex;align-items:center;gap:10px;color:#fff;font-size:22px;font-weight:800;text-decoration:none;transition:all .25s;letter-spacing:.02em}
.fp-cta-phone a:hover{color:var(--teal-light);transform:scale(1.02)}

/* ═══════════════════════════════════════════════════════════════
   16. WHATSAPP FLOAT
   ═══════════════════════════════════════════════════════════════ */
.fp-wa{position:fixed;bottom:28px;right:28px;z-index:999;width:60px;height:60px;border-radius:50%;background:#25D366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 6px 24px rgba(37,211,102,.4);transition:all .3s;cursor:pointer}
.fp-wa:hover{transform:scale(1.1);box-shadow:0 10px 32px rgba(37,211,102,.5)}
.fp-wa-tip{position:absolute;right:70px;top:50%;transform:translateY(-50%);background:#fff;color:var(--dark);padding:10px 16px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.1);white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .3s}
.fp-wa:hover .fp-wa-tip{opacity:1}

/* ═══════════════════════════════════════════════════════════════
   17. ANIMATIONS
   ═══════════════════════════════════════════════════════════════ */
.fp-anim{opacity:0;transform:translateY(28px);transition:opacity .6s ease,transform .6s ease}
.fp-anim.visible{opacity:1;transform:translateY(0)}

/* ═══════════════════════════════════════════════════════════════
   18. RESPONSIVE
   ═══════════════════════════════════════════════════════════════ */
@media(max-width:1024px){
    .fp-container{padding:0 40px}
    .hc-content{padding:0 40px}
    .hc-dots{left:40px}
    .hc-conf{padding:12px 40px;gap:30px}
    .hc-stats{right:40px}
    .fp-search{padding:0 40px}
    .fp-bridge{padding:40px 40px}
    .fp-cards-grid{grid-template-columns:1fr 1fr}
    .scard-featured{grid-column:span 2}
    .fp-why-grid{grid-template-columns:repeat(2,1fr)}
    .fp-nl-cta{padding:0 40px 80px}
    .fp-trust-row{gap:0;flex-wrap:wrap;justify-content:center}
    .fp-trust-sep{display:none}
    .fp-trust-item{padding:12px 20px}
    .sh-grid{grid-template-columns:1fr 1fr}
    .fp-bento{grid-template-columns:repeat(2,1fr);grid-template-areas:"golf golf" "sejour circuit" "road parcs";gap:1rem}
    .fp-ucard--golf{min-height:340px}
    .fp-ucard{min-height:280px}
    .fp-ucard--golf .fp-ucard__title{font-size:1.8rem}
    .fp-ucard__title{font-size:1.4rem}
}
@media(max-width:768px){
    .hc-wrap{height:68vh;min-height:460px;max-height:620px}
    .hc-content{padding:0 24px}
    .hc-dots{left:24px;bottom:100px}
    .hc-conf{padding:12px 20px;gap:20px;flex-wrap:wrap}
    .hc-stats{display:none}
    .fp-search{margin-top:0;padding:20px 24px}
    .fp-search-card{flex-direction:column;gap:16px}
    .fp-search-field{padding:0;border-right:none;border-bottom:1px solid var(--gray-light);padding-bottom:16px}
    .fp-btn-search{margin-left:0;width:100%}
    .fp-container{padding:0 24px}
    .fp-bridge{padding:32px 24px}
    .fp-cards-grid{grid-template-columns:1fr}
    .scard-featured{flex-direction:column;grid-column:span 1}
    .scard-featured .scard-img{width:100%}
    .fp-why-grid{grid-template-columns:1fr}
    .fp-testi-grid{grid-template-columns:1fr}
    .fp-nl-cta{padding:0 24px 60px}
    .fp-nl-band{flex-direction:column;min-height:auto}
    .fp-nl-sep{width:100%;height:3px;background:linear-gradient(90deg,transparent,var(--teal),var(--coral),transparent)}
    .fp-nl-band .fp-nl-side{max-width:100%;padding:32px 24px}
    .fp-nl-band .fp-cta-side{max-width:100%;padding:32px 24px;align-items:flex-start}
    .fp-nl-form{flex-direction:column}
    .fp-nl-perks{flex-direction:column;gap:8px}
    .fp-cta-wrap{max-width:100%}
    .sh-grid{grid-template-columns:1fr 1fr}
    .dl-grid{flex-direction:column}
    /* Bento 1 col */
    .fp-bento{grid-template-columns:1fr;grid-template-areas:"golf" "sejour" "circuit" "road" "parcs";gap:.85rem}
    .fp-ucard{min-height:240px}
    .fp-ucard--golf{min-height:300px}
    .fp-ucard--golf .fp-ucard__title{font-size:1.7rem}
    .fp-ucard__title{font-size:1.3rem}
    .fp-ucard__content{padding:1.5rem}
    .fp-ucard__arrow{width:38px;height:38px;top:1rem;right:1rem}
    .fp-ucard__arrow svg{width:15px;height:15px}
    .fp-ucard__desc{opacity:1;transform:translateY(0);max-height:100px;font-size:.85rem}
    .fp-ucard__count{bottom:1.5rem;right:1.5rem;font-size:.75rem}
    .fp-univers{padding:3.5rem 0 4.5rem}
    .fp-univers-header{margin-bottom:2.5rem}
    .fp-univers-subtitle{font-size:1rem}
}
@media(max-width:480px){
    .sh-grid{grid-template-columns:1fr}
    .fp-ucard{min-height:210px}
    .fp-ucard--golf{min-height:260px}
}
</style>

<?php
/* ═══════════════════════════════════════════════════════════════
   URLs réelles (archives CPT, pages) — éviter les liens vides ou filtres incohérents
   ═══════════════════════════════════════════════════════════════ */
$fp_url_circuits = get_post_type_archive_link('vs08_circuit');
if (!$fp_url_circuits) {
    $fp_url_circuits = home_url('/circuits/');
}
$fp_url_parcs = get_post_type_archive_link('vs08_parc');
if (!$fp_url_parcs) {
    $fp_url_parcs = home_url('/parcs/');
}
$fp_url_destinations = home_url('/destinations/');
$fp_url_resultats    = home_url('/resultats-recherche');
$fp_url_rgpd         = home_url('/rgpd/');

/* ═══════════════════════════════════════════════════════════════
   DONNÉES HERO CAROUSEL
   ═══════════════════════════════════════════════════════════════ */
$hc_slides = [
    ['title'=>"Jouez sur les plus beaux parcours du monde",'sub'=>'On s\'occupe de tout. Vous n\'avez qu\'à swinguer.','img'=>'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?w=1920&q=80','cta'=>'Explorer nos golfs','url'=>home_url('/resultats-recherche?type=sejour_golf'),'ov'=>'linear-gradient(135deg,rgba(11,17,32,.7),rgba(45,106,79,.4))'],
    ['title'=>"Chaque étape raconte une histoire",'sub'=>'Circuits guidés en Crète, Thaïlande, Costa Rica… rien à organiser.','img'=>'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920&q=80','cta'=>'Voir les circuits','url'=>home_url('/resultats-recherche?type=circuit'),'ov'=>'linear-gradient(135deg,rgba(11,17,32,.7),rgba(106,76,147,.4))'],
    ['title'=>"Vous avez le droit de ne rien faire",'sub'=>'Séjours all inclusive, soleil, plage et farniente — tout est inclus.','img'=>'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1920&q=80','cta'=>'Voir les séjours','url'=>home_url('/bientot-disponible/?univers=sejour'),'ov'=>'linear-gradient(135deg,rgba(11,17,32,.7),rgba(89,183,183,.3))'],
];
?>

<!-- ═══════════════════════════════════════════════════════════════
     1. HERO CAROUSEL
     ═══════════════════════════════════════════════════════════════ -->
<section class="hc-wrap" id="hero-section">
<?php foreach($hc_slides as $i=>$s): ?>
<div class="hc-slide <?php echo $i===0?'active':''; ?>"><div class="hc-slide-bg" style="background-image:url('<?php echo esc_url($s['img']); ?>')"></div><div class="hc-slide-ov" style="background:<?php echo $s['ov']; ?>"></div></div>
<?php endforeach; ?>
<div class="hc-content" style="animation:hcUp .8s ease">
    <p class="hc-loc">Voyages Sortir 08 - Châlons-en-Champagne</p>
    <h1 id="hc-t"><?php echo nl2br(esc_html($hc_slides[0]['title'])); ?></h1>
    <p class="hc-sub" id="hc-s"><?php echo esc_html($hc_slides[0]['sub']); ?></p>
    <div class="hc-btns">
        <a href="<?php echo esc_url($hc_slides[0]['url']); ?>" class="hc-btn-p" id="hc-c"><?php echo esc_html($hc_slides[0]['cta']); ?> →</a>
        <a href="<?php echo esc_url(home_url('/contact')); ?>" class="hc-btn-o">Demander un devis</a>
    </div>
</div>
<div class="hc-stats"><div class="hc-stat"><b>2500+</b><small>Voyageurs par an</small></div><div class="hc-stat"><b>50+</b><small>Pays couverts</small></div><div class="hc-stat"><b>4.9★</b><small>Note clients</small></div></div>
<div class="hc-dots"><?php foreach($hc_slides as $i=>$s): ?><button class="hc-dot <?php echo $i===0?'active':''; ?>" data-i="<?php echo $i; ?>"></button><?php endforeach; ?></div>
<div class="hc-conf"><div class="hc-conf-i"><span>🏆</span><span>Agence de confiance depuis 2001</span></div><div class="hc-conf-i"><span>⭐</span><span>4.8/5 sur Google (200+ avis)</span></div><div class="hc-conf-i"><span>💰</span><span>Libre à vous de payer plus cher !</span></div><div class="hc-conf-i"><span>✈️</span><span>Vols + Hôtels + Activités inclus</span></div></div>
</section>

<!-- Hero JS -->
<script>
(function(){var S=<?php echo wp_json_encode(array_map(function($s){return['t'=>nl2br(esc_html($s['title'])),'s'=>$s['sub'],'c'=>$s['cta'],'u'=>$s['url']];}, $hc_slides)); ?>;var c=0,n=S.length,T=document.getElementById('hc-t'),U=document.getElementById('hc-s'),C=document.getElementById('hc-c'),D=document.querySelectorAll('.hc-dot'),E=document.querySelectorAll('.hc-slide');function go(i){E[c].classList.remove('active');D[c].classList.remove('active');c=i%n;E[c].classList.add('active');D[c].classList.add('active');if(T)T.innerHTML=S[c].t;if(U)U.textContent=S[c].s;if(C){C.textContent=S[c].c+' →';C.href=S[c].u;}}D.forEach(function(d){d.addEventListener('click',function(){go(parseInt(this.dataset.i));clearInterval(t);t=setInterval(function(){go(c+1);},6000);});});var t=setInterval(function(){go(c+1);},6000);})();
</script>

<!-- ═══════════════════════════════════════════════════════════════
     2. BARRE DE RECHERCHE
     ═══════════════════════════════════════════════════════════════ -->
<?php
$vs08_opts = class_exists('VS08V_Search') ? VS08V_Search::get_aggregated_options() : ['types'=>[],'destinations'=>[],'aeroports'=>[],'durees'=>[],'dates'=>[]];

// ── Fallback types (toujours afficher tous les types même sans produits) ──
$fp_all_types = [
    'sejour_golf'  => 'Séjours Golf',
    'sejour'       => 'Séjours All Inclusive',
    'circuit'      => 'Circuits',
    'road_trip'    => 'Road Trip',
    'city_trip'    => 'City Trip',
    'parc'         => 'Billets Parcs',
];
// Fusionner : les types réels + les types manquants
foreach ($fp_all_types as $k => $v) {
    if (!isset($vs08_opts['types'][$k])) {
        $vs08_opts['types'][$k] = $v;
    }
}

// ── Fallback destinations (si la BDD en a moins de 5) ──
if (count($vs08_opts['destinations']) < 5) {
    $fp_fb_dest = [
        ['value'=>'Portugal','label'=>'Portugal','flag'=>'🇵🇹','pays'=>'Portugal','count'=>0,'image'=>''],
        ['value'=>'Espagne','label'=>'Espagne','flag'=>'🇪🇸','pays'=>'Espagne','count'=>0,'image'=>''],
        ['value'=>'Maroc','label'=>'Maroc','flag'=>'🇲🇦','pays'=>'Maroc','count'=>0,'image'=>''],
        ['value'=>'Tunisie','label'=>'Tunisie','flag'=>'🇹🇳','pays'=>'Tunisie','count'=>0,'image'=>''],
        ['value'=>'Turquie','label'=>'Turquie','flag'=>'🇹🇷','pays'=>'Turquie','count'=>0,'image'=>''],
        ['value'=>'Grèce','label'=>'Grèce','flag'=>'🇬🇷','pays'=>'Grèce','count'=>0,'image'=>''],
        ['value'=>'Italie','label'=>'Italie','flag'=>'🇮🇹','pays'=>'Italie','count'=>0,'image'=>''],
        ['value'=>'Irlande','label'=>'Irlande','flag'=>'🇮🇪','pays'=>'Irlande','count'=>0,'image'=>''],
        ['value'=>'Croatie','label'=>'Croatie','flag'=>'🇭🇷','pays'=>'Croatie','count'=>0,'image'=>''],
        ['value'=>'République Dominicaine','label'=>'Rép. Dominicaine','flag'=>'🇩🇴','pays'=>'République Dominicaine','count'=>0,'image'=>''],
        ['value'=>'Thaïlande','label'=>'Thaïlande','flag'=>'🇹🇭','pays'=>'Thaïlande','count'=>0,'image'=>''],
        ['value'=>'Égypte','label'=>'Égypte','flag'=>'🇪🇬','pays'=>'Égypte','count'=>0,'image'=>''],
        ['value'=>'Maurice','label'=>'Île Maurice','flag'=>'🇲🇺','pays'=>'Maurice','count'=>0,'image'=>''],
    ];
    // Ajouter ceux qui n'existent pas déjà
    $existing_values = array_column($vs08_opts['destinations'], 'value');
    foreach ($fp_fb_dest as $d) {
        if (!in_array($d['value'], $existing_values)) {
            $vs08_opts['destinations'][] = $d;
        }
    }
    // Trier alphabétiquement
    usort($vs08_opts['destinations'], function($a, $b) { return strcmp($a['label'], $b['label']); });
}

// ── Fallback aéroports (si la BDD en a moins de 3) ──
if (count($vs08_opts['aeroports']) < 3) {
    $fp_fb_aero = [
        ['code'=>'CDG','ville'=>'Paris Charles de Gaulle','label'=>'CDG — Paris Charles de Gaulle'],
        ['code'=>'ORY','ville'=>'Paris Orly','label'=>'ORY — Paris Orly'],
        ['code'=>'XCR','ville'=>'Paris-Vatry','label'=>'XCR — Paris-Vatry'],
        ['code'=>'LYS','ville'=>'Lyon Saint-Exupéry','label'=>'LYS — Lyon Saint-Exupéry'],
        ['code'=>'MRS','ville'=>'Marseille Provence','label'=>'MRS — Marseille Provence'],
    ];
    $existing_codes = array_column($vs08_opts['aeroports'], 'code');
    foreach ($fp_fb_aero as $a) {
        if (!in_array($a['code'], $existing_codes)) {
            $vs08_opts['aeroports'][] = $a;
        }
    }
}
?>
<section class="fp-search">
    <form class="fp-search-card" action="<?php echo esc_url(home_url('/resultats-recherche')); ?>" method="get">
        <div class="fp-search-field"><label>Type de voyage</label>
            <select name="type">
                <option value="">Tous les types</option>
                <?php foreach ($vs08_opts['types'] as $tv => $tl): ?>
                <option value="<?php echo esc_attr($tv); ?>"><?php echo esc_html($tl); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fp-search-field"><label>Destination</label>
            <select name="dest" id="fp-sel-dest">
                <option value="">Toutes les destinations</option>
                <?php foreach ($vs08_opts['destinations'] as $d): ?>
                <option value="<?php echo esc_attr($d['value']); ?>"><?php echo esc_html($d['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fp-search-field"><label>Aéroport de départ</label>
            <select name="airport" id="fp-sel-airport">
                <option value="">Tous les aéroports</option>
                <?php foreach ($vs08_opts['aeroports'] as $a): ?>
                <option value="<?php echo esc_attr($a['code']); ?>"><?php echo esc_html($a['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fp-search-field"><label>Date de départ</label>
            <div id="fp-date-wrap" style="position:relative">
                <div id="fp-date-trigger" class="fp-search-date-trigger" onclick="window.fpCalDate && window.fpCalDate.toggle()">📅 Départ entre… et…</div>
            </div>
            <input type="hidden" id="fp-date-start" name="date_min">
            <input type="hidden" id="fp-date-end" name="date_max">
        </div>
        <div class="fp-search-field"><label>Durée</label>
            <select name="duree">
                <option value="">Toutes les durées</option>
                <?php foreach ($vs08_opts['durees'] as $dn): ?>
                <option value="<?php echo esc_attr($dn); ?>"><?php echo esc_html($dn . ' nuits'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="fp-btn-search">🔍 Rechercher</button>
    </form>
</section>

<!-- Calendrier barre de recherche -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof VS08Calendar === 'undefined') return;
    var wrap = document.getElementById('fp-date-wrap');
    if (!wrap) return;
    var availDates = <?php echo wp_json_encode($vs08_opts['dates']); ?>;
    window.fpCalDate = new VS08Calendar({
        el:       '#fp-date-wrap',
        mode:     'range',
        inline:   false,
        input:    '#fp-date-start',
        inputEnd: '#fp-date-end',
        title:    '📅 Période de départ',
        subtitle: 'Départ au plus tôt → départ au plus tard',
        minDate:  new Date(),
        yearRange: [new Date().getFullYear(), new Date().getFullYear() + 2],
        highlightDates: availDates,
        onConfirm: function(dep, ret) {
            var opts = { day: 'numeric', month: 'short' };
            var txt = '📅 Entre ' + dep.toLocaleDateString('fr-FR', opts);
            if (ret) txt += ' et ' + ret.toLocaleDateString('fr-FR', opts);
            var trigger = document.getElementById('fp-date-trigger');
            if (trigger) {
                trigger.textContent = txt;
                trigger.style.color = '#0f2424';
                trigger.style.borderBottomColor = 'var(--teal)';
            }
        }
    });
});
</script>

<!-- Filtrage dynamique : aéroport → destinations desservies -->
<script>
(function(){
    var airportSel = document.getElementById('fp-sel-airport');
    var destSel    = document.getElementById('fp-sel-dest');
    if (!airportSel || !destSel) return;

    // Map aéroport → destinations (générée depuis la BDD)
    var airportDestMap = <?php echo wp_json_encode($vs08_opts['airport_dest_map'] ?? new stdClass()); ?>;

    // Sauvegarder toutes les options destination originales
    var allDestOptions = [];
    for (var i = 0; i < destSel.options.length; i++) {
        allDestOptions.push({
            value: destSel.options[i].value,
            text:  destSel.options[i].text
        });
    }

    airportSel.addEventListener('change', function() {
        var code = this.value;
        var currentDest = destSel.value;

        // Vider et re-remplir le select destination
        destSel.innerHTML = '';

        if (!code || !airportDestMap[code]) {
            // Pas d'aéroport sélectionné → toutes les destinations
            allDestOptions.forEach(function(o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.text;
                if (o.value === currentDest) opt.selected = true;
                destSel.appendChild(opt);
            });
        } else {
            // Aéroport sélectionné → filtrer
            var allowed = airportDestMap[code];
            var optAll = document.createElement('option');
            optAll.value = '';
            optAll.textContent = 'Destinations au départ de ' + code;
            destSel.appendChild(optAll);

            var found = false;
            allDestOptions.forEach(function(o) {
                if (o.value === '') return; // skip "Toutes les destinations"
                if (allowed.indexOf(o.value) !== -1) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    if (o.value === currentDest) { opt.selected = true; found = true; }
                    destSel.appendChild(opt);
                }
            });

            // Si aucune destination trouvée dans les options existantes, ajouter les destinations brutes
            if (destSel.options.length <= 1) {
                allowed.forEach(function(d) {
                    var opt = document.createElement('option');
                    opt.value = d;
                    opt.textContent = d;
                    destSel.appendChild(opt);
                });
            }
        }
    });
})();
</script>

<!-- ═══════════════════════════════════════════════════════════════
     3. NOS UNIVERS — Bento Grid
     ═══════════════════════════════════════════════════════════════ -->
<section class="fp-univers" id="univers">
  <div class="fp-container">
    <div class="fp-univers-header">
      <span class="fp-univers-label">✨ Nos univers</span>
      <h2 class="fp-univers-title">Séjours, golf, circuits &amp; <em>aventures</em></h2>
      <p class="fp-univers-subtitle">Chaque voyage est une histoire. Choisissez le premier chapitre de la vôtre parmi nos univers soigneusement conçus.</p>
    </div>
    <div class="fp-bento">
      <a href="<?php echo esc_url(add_query_arg(['type' => 'sejour_golf'], home_url('/resultats-recherche'))); ?>" class="fp-ucard fp-ucard--golf fp-anim">
        <div class="fp-ucard__img"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/bento/golf-bento.png'); ?>" alt="Séjour golfique" loading="lazy"></div>
        <div class="fp-ucard__overlay"></div>
        <div class="fp-ucard__arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        <div class="fp-ucard__content">
          <span class="fp-ucard__badge">⛳ Séjours golf</span>
          <h3 class="fp-ucard__title">Séjours Golf</h3>
          <p class="fp-ucard__desc">Parcours d'exception, hôtels de charme, vols &amp; green fees inclus. Vous n'avez qu'à jouer.</p>
        </div>
        <div class="fp-ucard__line"></div>
      </a>
      <a href="<?php echo esc_url(home_url('/bientot-disponible/?univers=sejour')); ?>" class="fp-ucard fp-ucard--sejour fp-anim">
        <div class="fp-ucard__img"><img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=700&q=80" alt="Séjour All Inclusive" loading="lazy"></div>
        <div class="fp-ucard__overlay"></div>
        <div class="fp-ucard__arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        <div class="fp-ucard__content">
          <span class="fp-ucard__badge">☀️ All Inclusive</span>
          <h3 class="fp-ucard__title">Séjours All Inclusive</h3>
          <p class="fp-ucard__desc">Détente &amp; découverte dans les plus beaux hôtels-clubs, tout compris.</p>
        </div>
        <span class="fp-ucard__soon">Bientôt</span>
        <div class="fp-ucard__line"></div>
      </a>
      <a href="<?php echo esc_url(add_query_arg(['type' => 'circuit'], home_url('/resultats-recherche'))); ?>" class="fp-ucard fp-ucard--circuit fp-anim">
        <div class="fp-ucard__img"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/bento/circuit-bento.png'); ?>" alt="Circuit découverte" loading="lazy"></div>
        <div class="fp-ucard__overlay"></div>
        <div class="fp-ucard__arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        <div class="fp-ucard__content">
          <span class="fp-ucard__badge">🗺️ Découverte</span>
          <h3 class="fp-ucard__title">Circuits</h3>
          <p class="fp-ucard__desc">Itinéraires conçus étape par étape pour ne rien manquer.</p>
        </div>
        <div class="fp-ucard__line"></div>
      </a>
      <a href="<?php echo esc_url(home_url('/bientot-disponible/?univers=road_trip')); ?>" class="fp-ucard fp-ucard--road fp-anim">
        <div class="fp-ucard__img"><img src="https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=900&q=80" alt="Road trip" loading="lazy"></div>
        <div class="fp-ucard__overlay"></div>
        <div class="fp-ucard__arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        <div class="fp-ucard__content">
          <span class="fp-ucard__badge">🚗 Liberté</span>
          <h3 class="fp-ucard__title">Road-Trip</h3>
          <p class="fp-ucard__desc">Votre voiture, votre rythme, nos meilleures routes.</p>
        </div>
        <span class="fp-ucard__soon">Bientôt</span>
        <div class="fp-ucard__line"></div>
      </a>
      <a href="<?php echo esc_url(home_url('/bientot-disponible/?univers=parc')); ?>" class="fp-ucard fp-ucard--parcs fp-anim">
        <div class="fp-ucard__img"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/bento/parcs-bento.png'); ?>" alt="Parcs d'attractions" loading="lazy"></div>
        <div class="fp-ucard__overlay"></div>
        <div class="fp-ucard__arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        <div class="fp-ucard__content">
          <span class="fp-ucard__badge">🎢 Sensations</span>
          <h3 class="fp-ucard__title">Parcs d'attractions</h3>
          <p class="fp-ucard__desc">Billets à prix réduit pour Disneyland, Parc Astérix &amp; plus.</p>
        </div>
        <span class="fp-ucard__soon">Bientôt</span>
        <div class="fp-ucard__line"></div>
      </a>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     4. PONT → SÉJOURS COUPS DE CŒUR
     ═══════════════════════════════════════════════════════════════ -->
<div class="fp-bridge">
    <div class="fp-bridge-inner">
        <p>Nos séjours coups de cœur</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     5. SÉJOURS COUPS DE CŒUR
     ═══════════════════════════════════════════════════════════════ -->
<section class="fp-sejours">
    <div class="fp-container">
        <div class="fp-section-header">
            <div><p class="fp-section-label">⛳ Sélection Golf</p><h2 class="fp-section-title">Nos séjours <em>coups de cœur</em></h2></div>
            <a href="<?php echo esc_url(home_url('/golf')); ?>" class="fp-section-link">Tous les séjours golf →</a>
        </div>
        <div class="fp-cards-grid">
            <?php
            $fp_home_cards_html = class_exists('VS08V_Homepage_Editor') ? VS08V_Homepage_Editor::render_home_cards() : '';
            if ($fp_home_cards_html) :
                echo $fp_home_cards_html;
            else :
                $fp_cdc_badge_map    = ['new'=>'Nouveauté','promo'=>'Promo','best'=>'Best-seller','derniere'=>'Dernières places'];
                $fp_cdc_pension_map  = ['bb'=>'Petit-déj.','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'All inclusive','mixed'=>'Formule mixte'];
                $fp_cdc_transf_map   = ['groupes'=>'🚌 Transferts groupés','prives'=>'🚐 Transferts privés','voiture'=>'🚗 Location voiture'];
                $fp_voyages_cdc = new WP_Query(['post_type'=>'vs08_voyage','post_status'=>'publish','posts_per_page'=>4,'orderby'=>'date','order'=>'DESC']);
                $fp_cdc_first = true;
                while ($fp_voyages_cdc->have_posts()) : $fp_voyages_cdc->the_post();
                    $fp_pid   = get_the_ID();
                    $fp_m     = get_post_meta($fp_pid, 'vs08v_data', true) ?: [];
                    $fp_img   = get_the_post_thumbnail_url($fp_pid, 'large') ?: (!empty($fp_m['galerie'][0]) ? $fp_m['galerie'][0] : 'https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=800&q=80');
                    $fp_prix_data   = class_exists('VS08V_Search') ? VS08V_Search::compute_prix_appel($fp_m, $fp_pid) : ['prix' => 0, 'has_vol' => false, 'vol_estimate' => false];
                    $fp_prix_num    = (int) ($fp_prix_data['prix'] ?? 0);
                    $fp_price_hint  = '';
                    if ($fp_prix_num > 0) {
                        $fp_prix = number_format($fp_prix_num, 0, ',', ' ') . '€';
                        if (!empty($fp_prix_data['has_vol'])) {
                            $fp_price_hint = 'Basé sur le meilleur tarif vol récemment vu sur le site (mis à jour si un client trouve moins cher).';
                        } elseif (!empty($fp_prix_data['vol_estimate'])) {
                            $fp_price_hint = 'Vol estimé agence — une recherche sur la fiche actualise le « dès » avec un tarif réel si plus avantageux.';
                        }
                    } else {
                        $fp_prix = '—';
                        $fp_price_hint = 'Indicatif après choix des dates et de l’aéroport sur la fiche séjour .';
                    }
                    $fp_pays  = trim(($fp_m['flag'] ?? '').' '.($fp_m['pays'] ?? ''));
                    $fp_golfs = $fp_m['golfs'] ?? [];
                    $fp_nn    = (int) ($fp_m['duree'] ?? 0);
                    $fp_nj    = (int) ($fp_m['duree_jours'] ?? 0);
                    if ($fp_nj < 1 && $fp_nn > 0) {
                        $fp_nj = $fp_nn + 1;
                    }
                    $fp_duree_chip = ($fp_nj > 0 && $fp_nn > 0) ? ($fp_nj . 'J / ' . $fp_nn . 'N') : ($fp_nn > 0 ? $fp_nn . 'N' : ($fp_nj > 0 ? $fp_nj . 'J' : ''));
                    $fp_nb_parc = 0;
                    if (!empty($fp_golfs) && is_array($fp_golfs)) {
                        $fp_nb_parc = count($fp_golfs);
                    }
                    if ($fp_nb_parc < 1 && !empty($fp_m['nb_parcours'])) {
                        $fp_nb_parc = (int) $fp_m['nb_parcours'];
                    }
                    $fp_tf_key = (string) ($fp_m['transfert_type'] ?? '');
                    $fp_tf_lbl = $fp_cdc_transf_map[$fp_tf_key] ?? '';
                    $fp_tt     = (string) ($fp_m['transport_type'] ?? 'vol');
                    $fp_vol_lbl = '';
                    if ($fp_tt === 'vol' || $fp_tt === '') {
                        $fp_vol_lbl = '✈️ Vols inclus';
                    } elseif ($fp_tt === 'vol_option') {
                        $fp_vol_lbl = '✈️ Vol en option';
                    } elseif ($fp_tt === 'sans_vol') {
                        $fp_vol_lbl = '🏨 Sans vol (hôtel seul)';
                    } elseif ($fp_tt === 'voiture') {
                        $fp_vol_lbl = '🚗 Accès en voiture';
                    }
                    $fp_desc_c = trim((string) ($fp_m['desc_courte'] ?? ''));
                    $fp_excerpt = $fp_desc_c !== '' ? $fp_desc_c : (has_excerpt($fp_pid) ? get_the_excerpt() : wp_trim_words(strip_tags(get_the_content()), 22));
                    $fp_bd_raw = $fp_m['badge'] ?? '';
                    $fp_bd_key = ($fp_bd_raw !== '' && $fp_bd_raw !== null) ? $fp_bd_raw : ($fp_cdc_first ? 'best' : '');
                    $fp_bd_label = ($fp_bd_key !== '' && isset($fp_cdc_badge_map[$fp_bd_key])) ? $fp_cdc_badge_map[$fp_bd_key] : '';
                    $fp_bd_class = ($fp_bd_key === 'new') ? 'badge-new' : (($fp_bd_key === 'promo' || $fp_bd_key === 'derniere') ? 'badge-promo' : 'badge-best');
                    ?>
                <div class="scard <?php echo $fp_cdc_first ? 'scard-featured ' : ''; ?>anim">
                    <div class="scard-img">
                        <?php if ($fp_bd_label !== '') : ?><div class="scard-badges"><span class="badge <?php echo esc_attr($fp_bd_class); ?>"><?php echo esc_html($fp_bd_label); ?></span></div><?php endif; ?>
                        <img src="<?php echo esc_url($fp_img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                    </div>
                    <div class="scard-body">
                        <?php if($fp_pays): ?><p class="scard-country"><?php echo esc_html($fp_pays); ?></p><?php endif; ?>
                        <h3><?php the_title(); ?></h3>
                        <?php if($fp_excerpt): ?><p class="scard-desc"><?php echo esc_html($fp_excerpt); ?></p><?php endif; ?>
                        <div class="scard-highlights">
                            <?php if ($fp_duree_chip !== '') : ?><span class="scard-chip">🗓️ <?php echo esc_html($fp_duree_chip); ?></span><?php endif; ?>
                            <?php if ($fp_nb_parc > 0) : ?><span class="scard-chip">⛳ <?php echo esc_html((string) $fp_nb_parc); ?> parcours</span><?php endif; ?>
                            <?php if ($fp_tf_lbl !== '') : ?><span class="scard-chip chip-gold"><?php echo esc_html($fp_tf_lbl); ?></span><?php endif; ?>
                            <?php
                            $fp_pen = $fp_m['pension'] ?? '';
                            if ($fp_pen && isset($fp_cdc_pension_map[$fp_pen])) :
                                ?><span class="scard-chip">🍽️ <?php echo esc_html($fp_cdc_pension_map[$fp_pen]); ?></span><?php
                            endif;
                            if ($fp_vol_lbl !== '') :
                                ?><span class="scard-chip"><?php echo esc_html($fp_vol_lbl); ?></span><?php
                            endif;
                            ?><span class="scard-chip">🧳 Bagage soute &amp; sac golf inclus</span><?php
                            if (($fp_m['buggy'] ?? '') === 'inclus') :
                                ?><span class="scard-chip">🛞 Buggy inclus</span><?php
                            endif;
                            ?>
                        </div>
                        <?php if(!empty($fp_golfs)): ?>
                        <div class="scard-golfs">
                            <?php foreach(array_slice($fp_golfs, 0, 3) as $fg): ?>
                            <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name"><?php echo esc_html($fg['nom'] ?? ''); ?></span><?php if(!empty($fg['trous'])): ?><br><span class="gchip-holes"><?php echo esc_html($fg['trous']); ?> trous</span><?php endif; ?></div></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="scard-divider"></div>
                        <div class="scard-footer">
                            <div class="scard-price">
                                <span class="price-label">À partir de</span>
                                <span class="price-amount"><?php echo esc_html($fp_prix); ?></span>
                                <span class="price-per">/personne · tout compris</span>
                                <?php if ($fp_price_hint !== '') : ?><p class="scard-price-hint"><?php echo esc_html($fp_price_hint); ?></p><?php endif; ?>
                            </div>
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="scard-btn"><?php echo $fp_cdc_first ? 'Voir ce séjour' : 'Voir'; ?> →</a>
                        </div>
                    </div>
                </div>
                <?php $fp_cdc_first = false; endwhile; wp_reset_postdata(); ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     6. SÉJOURS GOLF SHOWCASE
     ═══════════════════════════════════════════════════════════════ -->
<section class="sh-section sh-dark">
    <div class="sh-glow" style="top:-100px;right:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(200,164,94,.06),transparent)"></div>
    <div class="fp-container" style="position:relative;z-index:1">
        <div class="sh-head">
            <div><p class="sh-label" style="color:var(--gold)">⛳ NOTRE SPÉCIALITÉ</p><h2 class="sh-title">Séjours Golf clé en main</h2><p class="sh-sub">Vol + hôtel + véhicule + green fees. Vous n'avez qu'à jouer.</p></div>
            <a href="<?php echo esc_url(home_url('/golf')); ?>" class="sh-link" style="color:var(--gold);border-color:var(--gold)">Tous les séjours golf →</a>
        </div>
        <div class="sh-grid">
        <?php
        $fp_badge_map = ['new'=>'Nouveauté','promo'=>'Promo','best'=>'Best-seller','derniere'=>'Dernières places'];
        $fp_pension_map = ['bb'=>'Petit-déj.','dp'=>'Demi-pension','pc'=>'Pension comp.','ai'=>'All inclusive','mixed'=>'Selon prog.'];
        $fp_sh_transf_map = ['groupes'=>'🚌 Transferts groupés','prives'=>'🚐 Transferts privés','voiture'=>'🚗 Location voiture'];
        $fp_sh_trans_circuit = ['bus'=>'🚌 Bus clim.','4x4'=>'🚙 4×4','voiture'=>'🚗 Voiture','train'=>'🚄 Train','mixed'=>'🚐 Mixte'];
        $fp_golf_q_args = null;
        if (class_exists('VS08V_Homepage_Editor')) {
            $fp_sh_slots = VS08V_Homepage_Editor::get_section_slots('golf_showcase', 4);
            $fp_sh_ordered = [];
            for ($fp_si = 1; $fp_si <= 4; $fp_si++) {
                $fp_sid = (int) ($fp_sh_slots[$fp_si] ?? 0);
                if ($fp_sid > 0 && get_post_status($fp_sid) === 'publish') {
                    $fp_sh_ordered[] = $fp_sid;
                }
            }
            if (!empty($fp_sh_ordered)) {
                while (count($fp_sh_ordered) < 4) {
                    $fp_more = get_posts([
                        'post_type'      => 'vs08_voyage',
                        'post_status'    => 'publish',
                        'posts_per_page' => 12,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'post__not_in'   => $fp_sh_ordered,
                        'meta_query'     => [['key' => 'vs08v_data', 'compare' => 'EXISTS']],
                        'fields'         => 'ids',
                    ]);
                    $fp_added = false;
                    foreach ($fp_more as $fp_mid) {
                        $fp_mid = (int) $fp_mid;
                        if (!in_array($fp_mid, $fp_sh_ordered, true)) {
                            $fp_sh_ordered[] = $fp_mid;
                            $fp_added = true;
                        }
                        if (count($fp_sh_ordered) >= 4) {
                            break;
                        }
                    }
                    if (!$fp_added) {
                        break;
                    }
                }
                $fp_golf_q_args = [
                    'post_type'      => ['vs08_voyage', 'vs08_circuit'],
                    'post_status'    => 'publish',
                    'post__in'       => array_slice($fp_sh_ordered, 0, 4),
                    'orderby'        => 'post__in',
                    'posts_per_page' => 4,
                ];
            }
        }
        $fp_golf_q = new WP_Query($fp_golf_q_args !== null ? $fp_golf_q_args : [
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => 4,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [['key' => 'vs08v_data', 'compare' => 'EXISTS']],
        ]);
        while ($fp_golf_q->have_posts()) : $fp_golf_q->the_post();
            $fp_gid     = get_the_ID();
            $fp_golf_pt = get_post_type($fp_gid);
            if ($fp_golf_pt === 'vs08_circuit' && class_exists('VS08C_Meta')) {
                $fp_cm = VS08C_Meta::get($fp_gid);
                $fp_gm = [];
                $fp_gimg = get_the_post_thumbnail_url($fp_gid, 'medium_large') ?: (!empty($fp_cm['galerie'][0]) ? $fp_cm['galerie'][0] : '');
                $fp_gprix_n = 0;
                if (class_exists('VS08C_Search')) {
                    $fp_gprix_n = (int) round((float) VS08C_Search::get_prix_min_for_circuit($fp_cm));
                }
                if ($fp_gprix_n <= 0) {
                    $fp_gprix_n = (int) round((float) get_post_meta($fp_gid, 'vs08c_prix_min', true));
                }
                if ($fp_gprix_n <= 0 && !empty($fp_cm['prix_double'])) {
                    $fp_gprix_n = (int) round((float) $fp_cm['prix_double']);
                }
                $fp_gprix       = $fp_gprix_n > 0 ? number_format($fp_gprix_n, 0, ',', ' ') . '€' : '';
                $fp_gprice_hint = '';
                $fp_fl          = VS08C_Meta::resolve_flag($fp_cm);
                $fp_gpays       = trim($fp_fl . ' ' . ($fp_cm['pays'] ?? ''));
                $fp_gg_nn       = 0;
                $fp_gg_nj       = 0;
                $fp_dj_c        = (int) ($fp_cm['duree_jours'] ?? 0);
                $fp_gdur_badge  = $fp_dj_c > 0 ? ($fp_dj_c . ' jours') : '';
                $fp_ggolfs      = [];
                $fp_hot_c       = $fp_cm['hotels'] ?? [];
                if (is_array($fp_hot_c)) {
                    foreach (array_slice($fp_hot_c, 0, 3) as $fp_th) {
                        if (!is_array($fp_th)) {
                            continue;
                        }
                        $fp_nm = trim((string) ($fp_th['nom'] ?? $fp_th['name'] ?? ''));
                        if ($fp_nm !== '') {
                            $fp_ggolfs[] = ['nom' => $fp_nm, 'trous' => ''];
                        }
                    }
                }
                $fp_gnbgolf   = '';
                $fp_gtfl      = $fp_sh_trans_circuit[$fp_cm['transport'] ?? ''] ?? '';
                $fp_gvol_chip = '✈️ Vol';
                $fp_gpension  = isset($fp_pension_map[$fp_cm['pension'] ?? '']) ? $fp_pension_map[$fp_cm['pension']] : '';
                $fp_gbadge    = $fp_cm['badge'] ?? '';
                $fp_gbuggy    = false;
            } else {
                $fp_gm     = get_post_meta($fp_gid, 'vs08v_data', true) ?: [];
                $fp_gimg   = get_the_post_thumbnail_url($fp_gid, 'medium_large') ?: (!empty($fp_gm['galerie'][0]) ? $fp_gm['galerie'][0] : '');
                $fp_gprix_data = class_exists('VS08V_Search') ? VS08V_Search::compute_prix_appel($fp_gm, $fp_gid) : ['prix' => 0, 'has_vol' => false, 'vol_estimate' => false];
                $fp_gprix_n = (int) ($fp_gprix_data['prix'] ?? 0);
                $fp_gprix  = $fp_gprix_n > 0 ? number_format($fp_gprix_n, 0, ',', ' ') . '€' : '';
                $fp_gprice_hint = '';
                if ($fp_gprix_n > 0) {
                    if (!empty($fp_gprix_data['has_vol'])) {
                        $fp_gprice_hint = 'Vol inclus (dernier meilleur tarif vu sur le site).';
                    } elseif (!empty($fp_gprix_data['vol_estimate'])) {
                        $fp_gprice_hint = 'Vol estimé — actualisé après recherche.';
                    }
                }
                $fp_gpays  = trim(($fp_gm['flag'] ?? '').' '.($fp_gm['pays'] ?? ''));
                $fp_gg_nn = (int) ($fp_gm['duree'] ?? 0);
                $fp_gg_nj = (int) ($fp_gm['duree_jours'] ?? 0);
                if ($fp_gg_nj < 1 && $fp_gg_nn > 0) {
                    $fp_gg_nj = $fp_gg_nn + 1;
                }
                $fp_gdur_badge = ($fp_gg_nj > 0 && $fp_gg_nn > 0) ? ($fp_gg_nj . 'J / ' . $fp_gg_nn . 'N') : ($fp_gg_nn > 0 ? $fp_gg_nn . 'N' : ($fp_gg_nj > 0 ? $fp_gg_nj . 'J' : ''));
                $fp_ggolfs = $fp_gm['golfs'] ?? [];
                $fp_gnbparc = 0;
                if (!empty($fp_ggolfs) && is_array($fp_ggolfs)) {
                    $fp_gnbparc = count($fp_ggolfs);
                }
                if ($fp_gnbparc < 1 && !empty($fp_gm['nb_parcours'])) {
                    $fp_gnbparc = (int) $fp_gm['nb_parcours'];
                }
                $fp_gnbgolf = $fp_gnbparc > 0 ? $fp_gnbparc . ' parcours' : '';
                $fp_gtf = (string) ($fp_gm['transfert_type'] ?? '');
                $fp_gtfl = $fp_sh_transf_map[$fp_gtf] ?? '';
                $fp_gtt = (string) ($fp_gm['transport_type'] ?? 'vol');
                $fp_gvol_chip = '';
                if ($fp_gtt === 'vol' || $fp_gtt === '') {
                    $fp_gvol_chip = '✈️ Vols inclus';
                } elseif ($fp_gtt === 'vol_option') {
                    $fp_gvol_chip = '✈️ Vol en option';
                } elseif ($fp_gtt === 'sans_vol') {
                    $fp_gvol_chip = '🏨 Sans vol';
                } elseif ($fp_gtt === 'voiture') {
                    $fp_gvol_chip = '🚗 Accès voiture';
                }
                $fp_gbadge = $fp_gm['badge'] ?? '';
                $fp_gpension = isset($fp_pension_map[$fp_gm['pension'] ?? '']) ? $fp_pension_map[$fp_gm['pension']] : '';
                $fp_gbuggy = ($fp_gm['buggy'] ?? '') === 'inclus';
            }
        ?>
            <a href="<?php echo esc_url(get_permalink()); ?>" class="sh-card">
                <div class="sh-card-img">
                    <img src="<?php echo esc_url($fp_gimg); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                    <div class="sh-badges">
                        <?php if($fp_gbadge && isset($fp_badge_map[$fp_gbadge])): ?><span class="sh-badge" style="background:rgba(200,164,94,.9);color:#0f2424"><?php echo esc_html($fp_badge_map[$fp_gbadge]); ?></span><?php endif; ?>
                        <?php if($fp_gdur_badge !== ''): ?><span class="sh-badge" style="background:rgba(11,17,32,.7);color:#fff"><?php echo esc_html($fp_gdur_badge); ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="sh-card-body">
                    <?php if($fp_gpays): ?><p class="sh-country" style="color:var(--teal)"><?php echo esc_html($fp_gpays); ?></p><?php endif; ?>
                    <h3 class="sh-name"><?php the_title(); ?></h3>
                    <div class="sh-chips">
                        <?php if($fp_gnbgolf !== ''): ?><span class="sh-chip">⛳ <?php echo esc_html($fp_gnbgolf); ?></span><?php endif; ?>
                        <?php if($fp_gtfl !== ''): ?><span class="sh-chip"><?php echo esc_html($fp_gtfl); ?></span><?php endif; ?>
                        <?php if($fp_gpension): ?><span class="sh-chip">🍽️ <?php echo esc_html($fp_gpension); ?></span><?php endif; ?>
                        <?php if($fp_gvol_chip !== ''): ?><span class="sh-chip"><?php echo esc_html($fp_gvol_chip); ?></span><?php endif; ?>
                        <?php if ($fp_golf_pt === 'vs08_circuit') : ?>
                        <span class="sh-chip">🗺️ Circuit</span>
                        <?php else : ?>
                        <span class="sh-chip">🧳 Soute + sac golf</span>
                        <?php if($fp_gbuggy): ?><span class="sh-chip">🛞 Buggy inclus</span><?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php if(!empty($fp_ggolfs)): ?>
                    <div class="sh-golfs">
                        <?php foreach(array_slice($fp_ggolfs, 0, 2) as $fg): ?>
                        <span class="sh-golf-name"><?php echo $fp_golf_pt === 'vs08_circuit' ? '🏨 ' : '⛳ '; ?><?php echo esc_html($fg['nom'] ?? ''); ?><?php if(!empty($fg['trous'])): ?> · <?php echo esc_html($fg['trous']); ?> trous<?php endif; ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="sh-foot" style="margin-top:auto">
                        <?php if ($fp_gprix !== '') : ?>
                        <span class="sh-price" style="color:var(--gold)">à partir de <?php echo esc_html($fp_gprix); ?></span>
                        <span class="sh-per">/pers. · tout compris</span>
                        <?php if ($fp_gprice_hint !== '') : ?><span class="sh-per" style="display:block;margin-top:6px;font-size:9px;opacity:.75"><?php echo esc_html($fp_gprice_hint); ?></span><?php endif; ?>
                        <?php else : ?>
                        <span class="sh-per" style="color:rgba(255,255,255,.45)">Tarif après dates &amp; aéroport sur la fiche</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     9. CIRCUITS + ROAD-TRIPS CÔTE À CÔTE
     ═══════════════════════════════════════════════════════════════ -->
<section class="dl-section">
    <div class="fp-container">
        <?php
        $dl_pension_map = ['bb'=>'Petit-déj.','dp'=>'Demi-pension','pc'=>'Pension comp.','ai'=>'All inclusive','mixed'=>'Selon prog.'];
        $dl_transport_map = ['bus'=>'Bus clim.','4x4'=>'4×4','voiture'=>'Voiture','train'=>'Train','mixed'=>'Mixte'];
        $dl_transport_icon = ['bus'=>'🚌','4x4'=>'🚙','voiture'=>'🚗','train'=>'🚄','mixed'=>'🚐'];
        $dl_badge_map = ['new'=>'Nouveauté','best'=>'Coup de cœur','promo'=>'Promo','derniere'=>'Dernières places'];
        $dl_badge_colors = ['new'=>'#59b7b7','best'=>'#e3147a','promo'=>'#c9a84c','derniere'=>'#e55d3a'];
        $dl_all = new WP_Query(['post_type'=>'vs08_circuit','post_status'=>'publish','posts_per_page'=>12,'orderby'=>'date','order'=>'DESC']);
        $dl_guided = [];
        $dl_roadtrips = [];
        while ($dl_all->have_posts()) : $dl_all->the_post();
            $dl_id = get_the_ID();
            $dl_m  = get_post_meta($dl_id, '_vs08c_meta', true) ?: [];
            $dl_transport = $dl_m['transport'] ?? 'bus';
            $dl_prix_min = get_post_meta($dl_id, 'vs08c_prix_min', true);
            $dl_prix_val = $dl_prix_min > 0 ? (float)$dl_prix_min : (float)($dl_m['prix_double'] ?? 0);
            $dl_entry = [
                'id'       => $dl_id,
                'title'    => get_the_title(),
                'link'     => get_permalink(),
                'img'      => get_the_post_thumbnail_url($dl_id, 'medium_large') ?: (!empty($dl_m['galerie'][0]) ? $dl_m['galerie'][0] : ''),
                'pays'     => trim(($dl_m['flag'] ?? (class_exists('VS08C_Meta') ? VS08C_Meta::resolve_flag($dl_m) : '')).' '.($dl_m['pays'] ?? '')),
                'prix'     => $dl_prix_val > 0 ? number_format($dl_prix_val, 0, ',', ' ').'€' : '',
                'jours'    => !empty($dl_m['duree_jours']) ? (int)$dl_m['duree_jours'].' jours' : '',
                'desc'     => has_excerpt($dl_id) ? get_the_excerpt() : wp_trim_words(strip_tags(get_the_content()), 20),
                'pension'  => $dl_m['pension'] ?? '',
                'transport'=> $dl_transport,
                'guide'    => $dl_m['guide_lang'] ?? '',
                'badge'    => $dl_m['badge'] ?? '',
                'hotels'   => $dl_m['hotels'] ?? [],
            ];
            if ($dl_transport === 'voiture') {
                $dl_roadtrips[] = $dl_entry;
            } else {
                $dl_guided[] = $dl_entry;
            }
        endwhile;
        wp_reset_postdata();
        $dl_guided = array_slice($dl_guided, 0, 3);
        $dl_roadtrips = array_slice($dl_roadtrips, 0, 3);
        if (class_exists('VS08V_Homepage_Editor')) {
            $dl_circ_slots = VS08V_Homepage_Editor::get_section_slots('circuits', 6);
            for ($dl_oi = 1; $dl_oi <= 3; $dl_oi++) {
                $dl_oid = (int) ($dl_circ_slots[$dl_oi] ?? 0);
                if ($dl_oid > 0) {
                    $dl_orow = VS08V_Homepage_Editor::build_homepage_dl_circuit_entry($dl_oid);
                    if ($dl_orow !== null) {
                        $dl_guided[$dl_oi - 1] = $dl_orow;
                    }
                }
            }
            for ($dl_oi = 4; $dl_oi <= 6; $dl_oi++) {
                $dl_oid = (int) ($dl_circ_slots[$dl_oi] ?? 0);
                if ($dl_oid > 0) {
                    $dl_orow = VS08V_Homepage_Editor::build_homepage_dl_circuit_entry($dl_oid);
                    if ($dl_orow !== null) {
                        $dl_roadtrips[$dl_oi - 4] = $dl_orow;
                    }
                }
            }
            $dl_guided    = array_values($dl_guided);
            $dl_roadtrips = array_values($dl_roadtrips);
        }
        ?>
        <div class="dl-grid">
            <div class="dl-half dl-dark">
                <p class="sh-label" style="color:var(--coral)">🧭 DÉCOUVERTE</p>
                <h3 style="font-family:'Playfair Display',serif;font-size:30px;font-weight:700;color:#fff;margin:8px 0 0">Circuits guidés</h3>
                <p style="font-size:14px;color:rgba(255,255,255,.55);margin:6px 0 24px">Itinéraires organisés, guides francophones, hôtels sélectionnés.</p>
                <?php foreach ($dl_guided as $dlg): ?>
                <a href="<?php echo esc_url($dlg['link']); ?>" class="dl-item dl-item-dark" style="text-decoration:none">
                    <div class="dl-item-photo">
                        <img src="<?php echo esc_url($dlg['img']); ?>" alt="<?php echo esc_attr($dlg['title']); ?>" loading="lazy">
                        <?php if($dlg['badge'] && isset($dl_badge_map[$dlg['badge']])): ?><span class="dl-badge" style="background:<?php echo $dl_badge_colors[$dlg['badge']]; ?>;color:#fff"><?php echo esc_html($dl_badge_map[$dlg['badge']]); ?></span><?php endif; ?>
                    </div>
                    <div class="dl-item-body">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px">
                            <span style="font-size:11px;color:var(--coral);font-weight:600;text-transform:uppercase"><?php echo esc_html($dlg['pays']); ?></span>
                            <span style="font-size:10px;color:rgba(255,255,255,.4);background:rgba(255,255,255,.08);padding:2px 8px;border-radius:8px">Guidé</span>
                        </div>
                        <h4 style="font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:#fff;margin:0 0 3px;line-height:1.3"><?php echo esc_html($dlg['title']); ?></h4>
                        <?php if($dlg['desc']): ?><p style="font-size:11.5px;color:rgba(255,255,255,.45);line-height:1.4;margin:0 0 4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?php echo esc_html($dlg['desc']); ?></p><?php endif; ?>
                        <div class="dl-chips">
                            <span class="dl-chip dl-chip-d">✈️ Vol</span>
                            <?php if($dlg['transport'] && isset($dl_transport_map[$dlg['transport']])): ?><span class="dl-chip dl-chip-d"><?php echo $dl_transport_icon[$dlg['transport']] ?? '🚐'; ?> <?php echo esc_html($dl_transport_map[$dlg['transport']]); ?></span><?php endif; ?>
                            <?php if($dlg['pension'] && isset($dl_pension_map[$dlg['pension']])): ?><span class="dl-chip dl-chip-d">🍽️ <?php echo esc_html($dl_pension_map[$dlg['pension']]); ?></span><?php endif; ?>
                            <?php if($dlg['guide']): ?><span class="dl-chip dl-chip-d">🗣️ <?php echo esc_html($dlg['guide']); ?></span><?php endif; ?>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto">
                            <?php if($dlg['prix']): ?><span style="font-size:13px;color:var(--coral);font-weight:700">dès <?php echo esc_html($dlg['prix']); ?></span><?php endif; ?>
                            <?php if($dlg['jours']): ?><span style="font-size:11px;color:rgba(255,255,255,.35)"><?php echo esc_html($dlg['jours']); ?></span><?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php if(empty($dl_guided)): ?><p style="color:rgba(255,255,255,.4);font-size:13px;text-align:center;padding:30px 0">Prochainement disponible</p><?php endif; ?>
                <a href="<?php echo esc_url(home_url('/circuits')); ?>" class="dl-link" style="color:var(--coral);border-color:var(--coral)">Tout voir →</a>
            </div>
            <div class="dl-half dl-light">
                <p class="sh-label" style="color:var(--teal)">🚗 LIBERTÉ</p>
                <h3 style="font-family:'Playfair Display',serif;font-size:30px;font-weight:700;color:var(--dark);margin:8px 0 0">Road-Trips</h3>
                <p style="font-size:14px;color:rgba(11,17,32,.5);margin:6px 0 24px">Voiture + hébergements. La route, votre rythme.</p>
                <?php foreach ($dl_roadtrips as $dlr): ?>
                <a href="<?php echo esc_url($dlr['link']); ?>" class="dl-item dl-item-light" style="text-decoration:none">
                    <div class="dl-item-photo">
                        <img src="<?php echo esc_url($dlr['img']); ?>" alt="<?php echo esc_attr($dlr['title']); ?>" loading="lazy">
                        <?php if($dlr['badge'] && isset($dl_badge_map[$dlr['badge']])): ?><span class="dl-badge" style="background:<?php echo $dl_badge_colors[$dlr['badge']]; ?>;color:#fff"><?php echo esc_html($dl_badge_map[$dlr['badge']]); ?></span><?php endif; ?>
                    </div>
                    <div class="dl-item-body">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px">
                            <span style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase"><?php echo esc_html($dlr['pays']); ?></span>
                            <span style="font-size:10px;color:rgba(11,17,32,.4);background:rgba(11,17,32,.06);padding:2px 8px;border-radius:8px">Liberté</span>
                        </div>
                        <h4 style="font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:var(--dark);margin:0 0 3px;line-height:1.3"><?php echo esc_html($dlr['title']); ?></h4>
                        <?php if($dlr['desc']): ?><p style="font-size:11.5px;color:rgba(11,17,32,.45);line-height:1.4;margin:0 0 4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?php echo esc_html($dlr['desc']); ?></p><?php endif; ?>
                        <div class="dl-chips">
                            <span class="dl-chip dl-chip-l">✈️ Vol</span>
                            <span class="dl-chip dl-chip-l">🚗 Voiture incluse</span>
                            <?php if($dlr['pension'] && isset($dl_pension_map[$dlr['pension']])): ?><span class="dl-chip dl-chip-l">🍽️ <?php echo esc_html($dl_pension_map[$dlr['pension']]); ?></span><?php endif; ?>
                            <?php if(!empty($dlr['hotels'])): ?><span class="dl-chip dl-chip-l">🏨 <?php echo count($dlr['hotels']); ?> hébergement<?php echo count($dlr['hotels']) > 1 ? 's' : ''; ?></span><?php endif; ?>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto">
                            <?php if($dlr['prix']): ?><span style="font-size:13px;color:var(--teal);font-weight:700">dès <?php echo esc_html($dlr['prix']); ?></span><?php endif; ?>
                            <?php if($dlr['jours']): ?><span style="font-size:11px;color:rgba(11,17,32,.35)"><?php echo esc_html($dlr['jours']); ?></span><?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php if(empty($dl_roadtrips)): ?><p style="color:rgba(11,17,32,.35);font-size:13px;text-align:center;padding:30px 0">Prochainement disponible</p><?php endif; ?>
                <a href="<?php echo esc_url(home_url('/resultats-recherche?type=road_trip')); ?>" class="dl-link" style="color:var(--teal);border-color:var(--teal)">Tout voir →</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     10. SÉJOURS ALL INCLUSIVE SHOWCASE
     ═══════════════════════════════════════════════════════════════ -->
<section class="sh-section sh-gradient">
    <div class="sh-glow" style="top:-80px;left:-80px;width:350px;height:350px;background:radial-gradient(circle,rgba(89,183,183,.06),transparent)"></div>
    <div class="fp-container" style="position:relative;z-index:1">
        <div class="sh-head">
            <div><p class="sh-label" style="color:var(--teal)">🌴 ÉVASION</p><h2 class="sh-title">Séjours All Inclusive</h2><p class="sh-sub">Soleil, plage, farniente. Tout est inclus — vous n'avez qu'à profiter.</p></div>
            <a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour')); ?>" class="sh-link" style="color:var(--teal);border-color:var(--teal)">Tous les séjours →</a>
        </div>
        <div class="sh-grid">
        <?php foreach([
            ['n'=>'Djerba','c'=>'🇹🇳 Tunisie','p'=>'599€','img'=>'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=600&q=80','ni'=>'7N','f'=>'All Inclusive','hotel'=>'Radisson Blu Palace ★★★★★','desc'=>'Plage de sable fin, piscines, spa et buffets à volonté face à la mer.','inc'=>'✈️ Vol · 🏨 All Inclusive · 🚌 Transferts'],
            ['n'=>'Hurghada','c'=>'🇪🇬 Égypte','p'=>'649€','img'=>'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=600&q=80','ni'=>'7N','f'=>'All Inclusive','hotel'=>'Steigenberger Al Dau ★★★★★','desc'=>'Mer Rouge, snorkeling sur récifs coralliens, resort avec aquapark et 5 restaurants.','inc'=>'✈️ Vol · 🏨 All Inclusive · 🚌 Transferts · 🤿 Activités'],
            ['n'=>'Crète','c'=>'🇬🇷 Grèce','p'=>'799€','img'=>'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=600&q=80','ni'=>'7N','f'=>'Demi-pension','hotel'=>'Nana Princess Suites ★★★★★','desc'=>'Suites avec piscine privée, gastronomie crétoise et plage cristalline.','inc'=>'✈️ Vol · 🏨 Demi-pension · 🚌 Transferts'],
            ['n'=>'Fuerteventura','c'=>'🇪🇸 Canaries','p'=>'729€','img'=>'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80','ni'=>'7N','f'=>'All Inclusive','hotel'=>'Barceló Fuerteventura ★★★★','desc'=>'Printemps éternel, plages infinies, resort au bord du lagon avec spa.','inc'=>'✈️ Vol · 🏨 All Inclusive · 🚌 Transferts'],
        ] as $d): ?>
            <a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour')); ?>" class="sh-card">
                <div class="sh-card-img"><img src="<?php echo esc_url($d['img']); ?>" alt="<?php echo esc_attr($d['n']); ?>" loading="lazy"><div class="sh-badges"><span class="sh-badge" style="background:rgba(11,17,32,.7);color:#fff"><?php echo $d['ni']; ?></span><span class="sh-badge" style="background:rgba(89,183,183,.85);color:#fff"><?php echo $d['f']; ?></span></div></div>
                <div class="sh-card-body">
                    <p class="sh-country" style="color:var(--teal)"><?php echo esc_html($d['c']); ?></p>
                    <h3 class="sh-name"><?php echo esc_html($d['n']); ?></h3>
                    <p style="font-size:11px;color:var(--teal);font-weight:600;margin:2px 0 4px"><?php echo esc_html($d['hotel']); ?></p>
                    <p style="font-size:12.5px;color:#fff;line-height:1.5;margin:0 0 8px"><?php echo esc_html($d['desc']); ?></p>
                    <p style="font-size:10.5px;color:rgba(255,255,255,.75);margin:0 0 10px;letter-spacing:.3px"><?php echo $d['inc']; ?></p>
                    <div class="sh-foot"><span class="sh-price" style="color:var(--teal)">à partir de <?php echo $d['p']; ?></span><span class="sh-per">par pers.</span></div>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     11. POURQUOI NOUS FAIRE CONFIANCE
     ═══════════════════════════════════════════════════════════════ -->
<div class="fp-wave"><svg viewBox="0 0 1440 60" preserveAspectRatio="none"><path d="M0,60 C360,0 720,40 1080,10 C1260,0 1380,20 1440,15 L1440,60 Z" fill="#0f2424"/></svg></div>
<section class="fp-why" id="why-section">
    <div class="fp-why-glow-wrap"><div class="fp-why-glow" id="why-glow"></div></div>
    <div class="fp-container" style="position:relative;z-index:1">
        <p class="fp-section-label fp-label-light">✦ Notre différence</p>
        <h2 class="fp-section-title fp-title-white">Pourquoi nous <em>faire confiance ?</em></h2>
        <div class="fp-why-grid">
            <div class="fp-why-item"><div class="fp-why-icon">🌍</div><h3>L'agence qui vous ressemble</h3><p>Golf, circuits, all inclusive, road trips ou parcs d'attractions : quelle que soit votre envie, on monte votre voyage sur mesure, de A à Z.</p></div>
            <div class="fp-why-item"><div class="fp-why-icon">🏷️</div><h3>Le meilleur prix, toujours</h3><p>Notre slogan, c'est une promesse : "Libre à vous de payer plus cher !" Vols, hôtels, activités — tout est négocié au plus juste pour vous.</p></div>
            <div class="fp-why-item"><div class="fp-why-icon">📞</div><h3>Un vrai conseiller dédié</h3><p>Pas de chatbot, pas de centre d'appels. Un interlocuteur unique à Châlons-en-Champagne qui vous accompagne avant, pendant et après votre voyage.</p></div>
            <div class="fp-why-item"><div class="fp-why-icon">🔒</div><h3>Partez l'esprit tranquille</h3><p>Garantie financière APST, immatriculation Atout France, assurance Hiscox, paiement sécurisé 3D Secure. Vos vacances sont entre de bonnes mains.</p></div>
        </div>
    </div>
</section>
<div class="fp-wave"><svg viewBox="0 0 1440 60" preserveAspectRatio="none"><path d="M0,0 C360,50 720,10 1080,40 C1260,50 1380,20 1440,30 L1440,0 Z" fill="#0f2424"/></svg></div>

<!-- ═══════════════════════════════════════════════════════════════
     12. DESTINATIONS — CARTE DU MONDE INTERACTIVE
     ═══════════════════════════════════════════════════════════════ -->
<section class="fp-dest">
    <div class="fp-container">
        <div class="fp-section-header">
            <div><p class="fp-section-label">🌍 Nos destinations</p><h2 class="fp-section-title">Partir <em>partout dans le monde</em></h2></div>
            <a href="<?php echo esc_url($fp_url_destinations); ?>" class="fp-section-link">Toutes les destinations →</a>
        </div>
        <div class="fp-map-box">
            <div class="fp-map-airports" id="fp-map-airports">
                <span class="fp-map-apl">Aéroport de départ :</span>
            </div>
            <div class="fp-map-svg-wrap" id="fp-map-wrap">
                <!-- Tooltip -->
                <div class="fp-map-tt" id="fp-map-tt">
                    <div class="fp-map-tti">
                        <div class="fp-tt-iata" id="fp-tt-iata"></div>
                        <div class="fp-tt-city" id="fp-tt-city"></div>
                        <div class="fp-tt-region" id="fp-tt-region"></div>
                        <div class="fp-tt-tags" id="fp-tt-tags"></div>
                        <div class="fp-tt-desc" id="fp-tt-desc"></div>
                        <div class="fp-tt-price" id="fp-tt-price"></div>
                        <a class="fp-tt-btn" id="fp-tt-btn" href="<?php echo esc_url($fp_url_resultats); ?>">Voir les séjours →</a>
                    </div>
                </div>
            </div>
            <div class="fp-map-legend">
                <span><i style="background:#c9a84c"></i> Séjours Golf</span>
                <span><i style="background:#59b7b7"></i> All Inclusive</span>
                <span><i style="background:#e8724a"></i> Circuits</span>
            </div>
        </div>
    </div>
</section>

<!-- D3.js + TopoJSON pour la carte du monde -->
<?php
// ── Construire les données de la carte directement depuis la BDD ──
$fp_map_destinations = [];
$fp_map_airports_used = [];
$fp_map_coords = [
    'Portugal'=>['lat'=>37.02,'lon'=>-7.93,'city'=>'Algarve','iata'=>'FAO','region'=>'PORTUGAL'],
    'Espagne'=>['lat'=>36.72,'lon'=>-4.42,'city'=>'Marbella','iata'=>'AGP','region'=>'ESPAGNE'],
    'France'=>['lat'=>44.8,'lon'=>2.0,'city'=>'Biarritz','iata'=>'BOD','region'=>'FRANCE'],
    'Maroc'=>['lat'=>31.63,'lon'=>-8.0,'city'=>'Marrakech','iata'=>'RAK','region'=>'MAROC'],
    'Tunisie'=>['lat'=>34.0,'lon'=>9.8,'city'=>'Djerba','iata'=>'DJE','region'=>'TUNISIE'],
    'Égypte'=>['lat'=>27.18,'lon'=>33.8,'city'=>'Hurghada','iata'=>'HRG','region'=>'ÉGYPTE'],
    'Italie'=>['lat'=>40.8,'lon'=>14.5,'city'=>'Sicile','iata'=>'CTA','region'=>'ITALIE'],
    'Grèce'=>['lat'=>35.5,'lon'=>24.5,'city'=>'Crète','iata'=>'HER','region'=>'GRÈCE'],
    'Turquie'=>['lat'=>37.5,'lon'=>30.7,'city'=>'Antalya','iata'=>'AYT','region'=>'TURQUIE'],
    'Irlande'=>['lat'=>52.3,'lon'=>-8.5,'city'=>'Kerry','iata'=>'SNN','region'=>'IRLANDE'],
    'Canaries'=>['lat'=>28.45,'lon'=>-13.86,'city'=>'Fuerteventura','iata'=>'FUE','region'=>'CANARIES'],
    'Thaïlande'=>['lat'=>8.5,'lon'=>98.4,'city'=>'Phuket','iata'=>'HKT','region'=>'THAÏLANDE'],
    'Croatie'=>['lat'=>43.5,'lon'=>16.4,'city'=>'Split','iata'=>'SPU','region'=>'CROATIE'],
    'République Dominicaine'=>['lat'=>18.5,'lon'=>-69.9,'city'=>'Punta Cana','iata'=>'PUJ','region'=>'RÉP. DOMINICAINE'],
    'Maurice'=>['lat'=>-20.3,'lon'=>57.5,'city'=>'Île Maurice','iata'=>'MRU','region'=>'ÎLE MAURICE'],
    'Chypre'=>['lat'=>34.7,'lon'=>33.0,'city'=>'Paphos','iata'=>'PFO','region'=>'CHYPRE'],
    'Vietnam'=>['lat'=>16.05,'lon'=>108.2,'city'=>'Da Nang','iata'=>'DAD','region'=>'VIETNAM'],
    'Costa Rica'=>['lat'=>9.93,'lon'=>-84.08,'city'=>'San José','iata'=>'SJO','region'=>'COSTA RICA'],
    'Malte'=>['lat'=>35.9,'lon'=>14.5,'city'=>'La Valette','iata'=>'MLA','region'=>'MALTE'],
    'Écosse'=>['lat'=>56.5,'lon'=>-3.5,'city'=>'St Andrews','iata'=>'EDI','region'=>'ÉCOSSE'],
];
// Alias : destination → pays (pour regrouper les produits par pays sur la carte)
$fp_dest_to_pays = [
    // Portugal
    'Algarve'=>'Portugal','Lisbonne'=>'Portugal','Madère'=>'Portugal','Madeira'=>'Portugal','Porto'=>'Portugal','Faro'=>'Portugal',
    // Espagne
    'Marbella'=>'Espagne','Costa del Sol'=>'Espagne','Majorque'=>'Espagne','Mallorca'=>'Espagne','Tenerife'=>'Espagne',
    'Lanzarote'=>'Espagne','Fuerteventura'=>'Espagne','Gran Canaria'=>'Espagne','Andalousie'=>'Espagne','Ibiza'=>'Espagne',
    'Minorque'=>'Espagne','Valence'=>'Espagne','Barcelone'=>'Espagne','Séville'=>'Espagne','Malaga'=>'Espagne',
    'Îles Canaries'=>'Canaries','Iles Canaries'=>'Canaries',
    // Maroc
    'Marrakech'=>'Maroc','Agadir'=>'Maroc','Saidia'=>'Maroc','Tanger'=>'Maroc','El Jadida'=>'Maroc','Essaouira'=>'Maroc','Rabat'=>'Maroc',
    // Turquie
    'Antalya'=>'Turquie','Belek'=>'Turquie','Istanbul'=>'Turquie','Bodrum'=>'Turquie','Side'=>'Turquie',
    // Tunisie
    'Djerba'=>'Tunisie','Hammamet'=>'Tunisie','Sousse'=>'Tunisie','Monastir'=>'Tunisie',
    // Grèce
    'Crète'=>'Grèce','Rhodes'=>'Grèce','Corfou'=>'Grèce','Santorin'=>'Grèce','Athènes'=>'Grèce','Costa Navarino'=>'Grèce','Mykonos'=>'Grèce',
    // Italie
    'Sicile'=>'Italie','Sardaigne'=>'Italie','Rome'=>'Italie','Toscane'=>'Italie','Pouilles'=>'Italie','Naples'=>'Italie','Venise'=>'Italie',
    // Irlande
    'Dublin'=>'Irlande','Kerry'=>'Irlande','Cork'=>'Irlande',
    // Égypte
    'Hurghada'=>'Égypte','Sharm el Sheikh'=>'Égypte','Soma Bay'=>'Égypte','El Gouna'=>'Égypte','Louxor'=>'Égypte',
    // Thaïlande
    'Phuket'=>'Thaïlande','Bangkok'=>'Thaïlande','Hua Hin'=>'Thaïlande','Chiang Mai'=>'Thaïlande','Koh Samui'=>'Thaïlande',
    // Croatie
    'Split'=>'Croatie','Dubrovnik'=>'Croatie','Zagreb'=>'Croatie',
    // Rép. Dominicaine
    'Punta Cana'=>'République Dominicaine',
    // Maurice
    'Île Maurice'=>'Maurice','Ile Maurice'=>'Maurice',
    // Chypre
    'Paphos'=>'Chypre','Limassol'=>'Chypre',
    // Vietnam
    'Da Nang'=>'Vietnam','Hanoï'=>'Vietnam','Ho Chi Minh'=>'Vietnam',
    // Écosse
    'St Andrews'=>'Écosse','Édimbourg'=>'Écosse',
    // France
    'Biarritz'=>'France','Côte d\'Azur'=>'France','Provence'=>'France','Normandie'=>'France','Corse'=>'France',
];
// IATA destination → pays (fallback ultime : résout via le code aéroport d'arrivée)
$fp_iata_to_pays = [
    // Portugal
    'FAO'=>'Portugal','LIS'=>'Portugal','OPO'=>'Portugal','FNC'=>'Portugal',
    // Espagne continentale
    'AGP'=>'Espagne','ALC'=>'Espagne','BCN'=>'Espagne','MAD'=>'Espagne','SVQ'=>'Espagne','VLC'=>'Espagne','BIO'=>'Espagne','PMI'=>'Espagne','IBZ'=>'Espagne','MAH'=>'Espagne','GRX'=>'Espagne','REU'=>'Espagne','MJV'=>'Espagne',
    // Canaries
    'TFS'=>'Canaries','LPA'=>'Canaries','FUE'=>'Canaries','ACE'=>'Canaries','TFN'=>'Canaries','SPC'=>'Canaries',
    // Maroc
    'RAK'=>'Maroc','AGA'=>'Maroc','CMN'=>'Maroc','FEZ'=>'Maroc','TNG'=>'Maroc','OUD'=>'Maroc','NDR'=>'Maroc','RBA'=>'Maroc','ESU'=>'Maroc',
    // Turquie
    'AYT'=>'Turquie','IST'=>'Turquie','SAW'=>'Turquie','DLM'=>'Turquie','BJV'=>'Turquie','ADB'=>'Turquie','GZT'=>'Turquie',
    // Tunisie
    'DJE'=>'Tunisie','TUN'=>'Tunisie','NBE'=>'Tunisie','MIR'=>'Tunisie','SFA'=>'Tunisie',
    // Grèce
    'HER'=>'Grèce','ATH'=>'Grèce','RHO'=>'Grèce','CFU'=>'Grèce','JTR'=>'Grèce','JMK'=>'Grèce','CHQ'=>'Grèce','KGS'=>'Grèce','ZTH'=>'Grèce','SKG'=>'Grèce','KLX'=>'Grèce','PVK'=>'Grèce',
    // Italie
    'CTA'=>'Italie','PMO'=>'Italie','FCO'=>'Italie','NAP'=>'Italie','VCE'=>'Italie','MXP'=>'Italie','BLQ'=>'Italie','OLB'=>'Italie','CAG'=>'Italie','PSA'=>'Italie','FLR'=>'Italie','BRI'=>'Italie','SUF'=>'Italie',
    // Croatie
    'SPU'=>'Croatie','DBV'=>'Croatie','ZAG'=>'Croatie','PUY'=>'Croatie',
    // Irlande
    'DUB'=>'Irlande','SNN'=>'Irlande','ORK'=>'Irlande',
    // Écosse
    'EDI'=>'Écosse','GLA'=>'Écosse',
    // Égypte
    'HRG'=>'Égypte','SSH'=>'Égypte','CAI'=>'Égypte','LXR'=>'Égypte','RMF'=>'Égypte',
    // Thaïlande
    'HKT'=>'Thaïlande','BKK'=>'Thaïlande','CNX'=>'Thaïlande','USM'=>'Thaïlande','DMK'=>'Thaïlande',
    // Rép. Dominicaine
    'PUJ'=>'République Dominicaine','SDQ'=>'République Dominicaine',
    // Maurice
    'MRU'=>'Maurice',
    // Chypre
    'PFO'=>'Chypre','LCA'=>'Chypre',
    // Vietnam
    'DAD'=>'Vietnam','HAN'=>'Vietnam','SGN'=>'Vietnam',
    // Costa Rica
    'SJO'=>'Costa Rica','LIR'=>'Costa Rica',
    // Malte
    'MLA'=>'Malte',
    // Autres destinations populaires
    'CUN'=>'Mexique','NAS'=>'Bahamas','MBJ'=>'Jamaïque','HAV'=>'Cuba',
    'DPS'=>'Indonésie','KUL'=>'Malaisie','SIN'=>'Singapour','CMB'=>'Sri Lanka',
    'MLE'=>'Maldives','MCT'=>'Oman','DXB'=>'Émirats arabes unis','AMM'=>'Jordanie',
    'CPT'=>'Afrique du Sud','DSS'=>'Sénégal','SID'=>'Cap-Vert',
    'NRT'=>'Japon','ICN'=>'Corée du Sud','AKL'=>'Nouvelle-Zélande','SYD'=>'Australie',
    'EZE'=>'Argentine','GRU'=>'Brésil','BOG'=>'Colombie','MEX'=>'Mexique',
    'YUL'=>'Canada','JFK'=>'États-Unis','MIA'=>'États-Unis','LAX'=>'États-Unis',
    'PPT'=>'Polynésie','NOU'=>'Nouvelle-Calédonie',
    'TIV'=>'Monténégro','TGD'=>'Monténégro','BOJ'=>'Bulgarie','VAR'=>'Bulgarie',
    'PRG'=>'République Tchèque','BUD'=>'Hongrie','WAW'=>'Pologne','OTP'=>'Roumanie',
    'KEF'=>'Islande','OSL'=>'Norvège','ARN'=>'Suède','HEL'=>'Finlande',
];
// Ajouter les coords pour les pays qui ne sont pas encore dans la table
$fp_map_coords += [
    'Mexique'=>['lat'=>20.6,'lon'=>-87.1,'city'=>'Cancún','iata'=>'CUN','region'=>'MEXIQUE'],
    'Indonésie'=>['lat'=>-8.65,'lon'=>115.2,'city'=>'Bali','iata'=>'DPS','region'=>'INDONÉSIE'],
    'Maldives'=>['lat'=>4.18,'lon'=>73.5,'city'=>'Malé','iata'=>'MLE','region'=>'MALDIVES'],
    'Émirats arabes unis'=>['lat'=>25.25,'lon'=>55.3,'city'=>'Dubaï','iata'=>'DXB','region'=>'ÉMIRATS'],
    'Jordanie'=>['lat'=>31.95,'lon'=>35.9,'city'=>'Amman','iata'=>'AMM','region'=>'JORDANIE'],
    'Afrique du Sud'=>['lat'=>-33.97,'lon'=>18.6,'city'=>'Le Cap','iata'=>'CPT','region'=>'AFRIQUE DU SUD'],
    'Sénégal'=>['lat'=>14.74,'lon'=>-17.5,'city'=>'Dakar','iata'=>'DSS','region'=>'SÉNÉGAL'],
    'Cap-Vert'=>['lat'=>16.73,'lon'=>-22.9,'city'=>'Sal','iata'=>'SID','region'=>'CAP-VERT'],
    'Sri Lanka'=>['lat'=>7.07,'lon'=>79.9,'city'=>'Colombo','iata'=>'CMB','region'=>'SRI LANKA'],
    'Oman'=>['lat'=>23.6,'lon'=>58.3,'city'=>'Mascate','iata'=>'MCT','region'=>'OMAN'],
    'Cuba'=>['lat'=>23.0,'lon'=>-82.4,'city'=>'La Havane','iata'=>'HAV','region'=>'CUBA'],
    'Jamaïque'=>['lat'=>18.5,'lon'=>-77.9,'city'=>'Montego Bay','iata'=>'MBJ','region'=>'JAMAÏQUE'],
    'Islande'=>['lat'=>63.98,'lon'=>-22.6,'city'=>'Reykjavik','iata'=>'KEF','region'=>'ISLANDE'],
    'Monténégro'=>['lat'=>42.4,'lon'=>18.8,'city'=>'Tivat','iata'=>'TIV','region'=>'MONTÉNÉGRO'],
    'Bulgarie'=>['lat'=>42.57,'lon'=>27.5,'city'=>'Bourgas','iata'=>'BOJ','region'=>'BULGARIE'],
    'Polynésie'=>['lat'=>-17.55,'lon'=>-149.6,'city'=>'Tahiti','iata'=>'PPT','region'=>'POLYNÉSIE'],
];
$fp_type_colors = ['sejour_golf'=>'#c9a84c','circuit'=>'#e55d3a','sejour'=>'#59b7b7','road_trip'=>'#8e44ad','city_trip'=>'#3498db','parc'=>'#e74c3c'];

if (class_exists('VS08V_MetaBoxes')) {
    // Scanner les 2 types de produits : voyages ET circuits
    $fp_map_ids_voyages = get_posts(['post_type'=>'vs08_voyage','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']);
    $fp_map_ids_circuits = get_posts(['post_type'=>'vs08_circuit','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']);
    $fp_dest_agg = []; // pays → ['types'=>[], 'airports'=>[], 'count'=>0, 'cities'=>[]]

    // ── Voyages (sejour_golf, sejour, road_trip, city_trip, parc) ──
    foreach ($fp_map_ids_voyages as $pid) {
        $m = VS08V_MetaBoxes::get($pid);
        if (($m['statut'] ?? '') === 'archive') continue;
        $dest = trim($m['destination'] ?? '');
        $pays = trim($m['pays'] ?? '');
        $type = $m['type_voyage'] ?? '';
        $iata = strtoupper(trim($m['iata_dest'] ?? ''));
        if (!$type) continue;

        $map_key = '';
        if ($pays && isset($fp_map_coords[$pays])) { $map_key = $pays; }
        elseif ($dest && isset($fp_dest_to_pays[$dest])) { $map_key = $fp_dest_to_pays[$dest]; }
        elseif ($pays && isset($fp_dest_to_pays[$pays])) { $map_key = $fp_dest_to_pays[$pays]; }
        elseif ($dest && isset($fp_map_coords[$dest])) { $map_key = $dest; }
        elseif ($iata && isset($fp_iata_to_pays[$iata])) { $map_key = $fp_iata_to_pays[$iata]; }
        elseif ($pays) { $map_key = $pays; }
        elseif ($dest) { $map_key = $dest; }
        if (!$map_key) continue;

        if (!isset($fp_dest_agg[$map_key])) $fp_dest_agg[$map_key] = ['types'=>[],'airports'=>[],'count'=>0,'cities'=>[]];
        $fp_dest_agg[$map_key]['count']++;
        if (!in_array($type, $fp_dest_agg[$map_key]['types'])) $fp_dest_agg[$map_key]['types'][] = $type;
        if ($dest && !in_array($dest, $fp_dest_agg[$map_key]['cities'])) $fp_dest_agg[$map_key]['cities'][] = $dest;
        if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
            foreach ($m['aeroports'] as $a) {
                $code = strtoupper(trim($a['code'] ?? ''));
                if ($code && !in_array($code, $fp_dest_agg[$map_key]['airports'])) $fp_dest_agg[$map_key]['airports'][] = $code;
                if ($code) $fp_map_airports_used[$code] = true;
            }
        }
    }

    // ── Circuits (post_type vs08_circuit) ──
    if (class_exists('VS08C_Meta')) {
        foreach ($fp_map_ids_circuits as $pid) {
            $m = VS08C_Meta::get($pid);
            if (($m['statut'] ?? '') === 'archive') continue;
            $dest = trim($m['destination'] ?? '');
            $pays = trim($m['pays'] ?? '');
            $type = 'circuit';
            $iata = strtoupper(trim($m['iata_dest'] ?? ''));

            $map_key = '';
            if ($pays && isset($fp_map_coords[$pays])) { $map_key = $pays; }
            elseif ($dest && isset($fp_dest_to_pays[$dest])) { $map_key = $fp_dest_to_pays[$dest]; }
            elseif ($pays && isset($fp_dest_to_pays[$pays])) { $map_key = $fp_dest_to_pays[$pays]; }
            elseif ($dest && isset($fp_map_coords[$dest])) { $map_key = $dest; }
            elseif ($iata && isset($fp_iata_to_pays[$iata])) { $map_key = $fp_iata_to_pays[$iata]; }
            elseif ($pays) { $map_key = $pays; }
            elseif ($dest) { $map_key = $dest; }
            if (!$map_key) continue;

            if (!isset($fp_dest_agg[$map_key])) $fp_dest_agg[$map_key] = ['types'=>[],'airports'=>[],'count'=>0,'cities'=>[]];
            $fp_dest_agg[$map_key]['count']++;
            if (!in_array($type, $fp_dest_agg[$map_key]['types'])) $fp_dest_agg[$map_key]['types'][] = $type;
            if ($dest && !in_array($dest, $fp_dest_agg[$map_key]['cities'])) $fp_dest_agg[$map_key]['cities'][] = $dest;
            if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                foreach ($m['aeroports'] as $a) {
                    $code = strtoupper(trim($a['code'] ?? ''));
                    if ($code && !in_array($code, $fp_dest_agg[$map_key]['airports'])) $fp_dest_agg[$map_key]['airports'][] = $code;
                    if ($code) $fp_map_airports_used[$code] = true;
                }
            }
        }
    }
    foreach ($fp_dest_agg as $pays => $info) {
        $coords = $fp_map_coords[$pays] ?? null;
        if (!$coords) continue; // Pas de coordonnées connues → skip
        // URL de recherche: utiliser le pays ou la première destination
        $dest_param = $pays;
        $url = home_url('/resultats-recherche') . '?dest=' . rawurlencode($dest_param);
        if (count($info['types']) === 1) $url .= '&type=' . rawurlencode($info['types'][0]);
        $colors = [];
        foreach ($info['types'] as $t) { $colors[] = $fp_type_colors[$t] ?? '#59b7b7'; }
        // Sous-titre: villes si multiples, sinon le nom par défaut
        $city_label = !empty($info['cities']) ? implode(', ', array_slice($info['cities'], 0, 3)) : $coords['city'];
        $fp_map_destinations[] = [
            'id'=>sanitize_title($pays),'pays'=>$pays,'city'=>$city_label,
            'region'=>$coords['region'],'iata'=>$coords['iata'],
            'lat'=>$coords['lat'],'lon'=>$coords['lon'],
            'colors'=>$colors,'types'=>$info['types'],
            'airports'=>$info['airports'],'count'=>$info['count'],'url'=>$url,
        ];
    }
}
$fp_map_missed = [];
foreach (($fp_dest_agg ?? []) as $k => $v) {
    if (!isset($fp_map_coords[$k])) $fp_map_missed[] = $k . '(' . $v['count'] . ')';
}
?>
<!-- DEBUG MAP: <?php echo count($fp_map_destinations); ?> sur carte | <?php echo count($fp_map_ids_voyages ?? []); ?> voyages + <?php echo count($fp_map_ids_circuits ?? []); ?> circuits | OK: <?php echo implode(', ', array_keys(array_filter($fp_dest_agg ?? [], function($v, $k) use ($fp_map_coords) { return isset($fp_map_coords[$k]); }, ARRAY_FILTER_USE_BOTH))); ?> | MANQUE coords: <?php echo $fp_map_missed ? implode(', ', $fp_map_missed) : 'aucun'; ?> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/topojson/3.0.2/topojson.min.js"></script>
<script>
(function(){
    var DS = <?php echo wp_json_encode($fp_map_destinations, JSON_UNESCAPED_UNICODE); ?>;
    var APTS = [
        {code:'CDG',name:'Paris CDG',lat:49.01,lon:2.55},
        {code:'ORY',name:'Paris Orly',lat:48.72,lon:2.36},
        {code:'LYS',name:'Lyon',lat:45.73,lon:5.08},
        {code:'MRS',name:'Marseille',lat:43.44,lon:5.22},
        {code:'NTE',name:'Nantes',lat:47.15,lon:-1.61},
        {code:'BOD',name:'Bordeaux',lat:44.83,lon:-0.72},
        {code:'TLS',name:'Toulouse',lat:43.63,lon:1.37},
        {code:'LIL',name:'Lille',lat:50.57,lon:3.10},
        {code:'NCE',name:'Nice',lat:43.66,lon:7.22},
        {code:'BRU',name:'Bruxelles',lat:50.90,lon:4.48}
    ];
    var TYPE_LABELS = {sejour_golf:'Séjours Golf',circuit:'Circuits',sejour:'All Inclusive',road_trip:'Road Trip',city_trip:'City Trip',parc:'Parcs'};
    var APT_MAP = {}; APTS.forEach(function(a){ APT_MAP[a.code]=a; });

    var sel=null, W=1200, H=580;
    var proj=d3.geoNaturalEarth1().center([20,30]).scale(340).translate([W/2,H/2]);
    var pathG=d3.geoPath(proj);
    var box=document.getElementById('fp-map-wrap');
    if(!box)return;
    var svg=d3.select(box).append('svg').attr('viewBox','0 0 '+W+' '+H).attr('width','100%');
    var defs=svg.append('defs');
    defs.append('clipPath').attr('id','fp-mc').append('rect').attr('width',W).attr('height',H);
    var rg1=defs.append('radialGradient').attr('id','fp-oc');
    rg1.append('stop').attr('offset','0%').attr('stop-color','#14203a');
    rg1.append('stop').attr('offset','100%').attr('stop-color','#0b1120');
    var gc=svg.append('g').attr('clip-path','url(#fp-mc)');
    var gO=gc.append('g'),gGr=gc.append('g'),gL=gc.append('g'),gA=gc.append('g'),gM=gc.append('g');

    /* Zoom & pan */
    var zoom=d3.zoom()
        .scaleExtent([1,8])
        .translateExtent([[0,0],[W,H]])
        .on('zoom',function(e){ gc.attr('transform',e.transform); });
    svg.call(zoom);
    svg.on('dblclick.zoom',null); // désactiver double-clic zoom

    // Bouton reset zoom (apparaît quand zoomé)
    var zBtn=document.createElement('button');
    zBtn.className='fp-map-zoom-reset';
    zBtn.textContent='⟲ Vue globale';
    zBtn.style.display='none';
    zBtn.onclick=function(){ svg.transition().duration(600).call(zoom.transform,d3.zoomIdentity); };
    box.style.position='relative';
    box.appendChild(zBtn);
    svg.on('zoom',null); // clear old
    zoom.on('zoom',function(e){
        gc.attr('transform',e.transform);
        zBtn.style.display=(e.transform.k>1.05)?'block':'none';
    });
    svg.call(zoom);

    /* Boutons aéroports avec "Tous" */
    var ap=document.getElementById('fp-map-airports');
    ap.innerHTML='';
    var bAll=document.createElement('button');
    bAll.className='fp-map-ab on'; bAll.textContent='Tous'; bAll.title='Toutes les destinations';
    bAll.onclick=function(){sel=null;ap.querySelectorAll('.fp-map-ab').forEach(function(x){x.classList.remove('on');});bAll.classList.add('on');update();};
    ap.appendChild(bAll);
    APTS.forEach(function(a){
        var b=document.createElement('button');
        b.className='fp-map-ab'; b.textContent=a.code; b.title=a.name;
        b.onclick=function(){sel=a;ap.querySelectorAll('.fp-map-ab').forEach(function(x){x.classList.remove('on');});b.classList.add('on');update();};
        ap.appendChild(b);
    });

    /* Fond */
    gO.append('path').datum({type:'Sphere'}).attr('d',pathG).attr('fill','url(#fp-oc)').attr('stroke','rgba(89,183,183,.08)').attr('stroke-width',.5);
    gGr.append('path').datum(d3.geoGraticule().step([20,20])()).attr('d',pathG).attr('fill','none').attr('stroke','rgba(89,183,183,.04)').attr('stroke-width',.3);

    /* Charger le monde */
    d3.json('https://cdn.jsdelivr.net/npm/world-atlas@2/countries-110m.json').then(function(w){
        var land=topojson.feature(w,w.objects.countries);
        var bord=topojson.mesh(w,w.objects.countries,function(a,b){return a!==b;});
        gL.selectAll('path').data(land.features).join('path').attr('d',pathG).attr('fill','#1a2640').attr('stroke','none');
        gL.append('path').datum(bord).attr('d',pathG).attr('fill','none').attr('stroke','rgba(89,183,183,.07)').attr('stroke-width',.3);
        update();
    });

    function getFiltered(){
        if(!sel) return DS;
        var f=DS.filter(function(d){return d.airports&&d.airports.indexOf(sel.code)!==-1;});
        return f.length>0?f:DS; // jamais vide
    }

    function update(){ drawArcs(); drawMarkers(); }

    /* Arcs de vol */
    function drawArcs(){
        gA.selectAll('*').remove();
        if(!sel) return; // mode Tous = pas d'arcs
        var o=[sel.lon,sel.lat],op=proj(o);
        var fd=getFiltered();
        fd.forEach(function(d,i){
            var mainCol=d.colors&&d.colors[0]?d.colors[0]:'#59b7b7';
            gA.append('path').datum({type:'LineString',coordinates:[o,[d.lon,d.lat]]})
                .attr('d',pathG).attr('fill','none').attr('stroke',mainCol)
                .attr('stroke-width',1.2).attr('stroke-dasharray','6 5').attr('opacity',.3)
                .style('animation','fp-map-dash '+(1.6+i*.1)+'s linear infinite');
        });
        if(op){
            gA.append('circle').attr('cx',op[0]).attr('cy',op[1]).attr('r',6).attr('fill','#fff');
            gA.append('circle').attr('cx',op[0]).attr('cy',op[1]).attr('r',6).attr('fill','none').attr('stroke','#fff').attr('stroke-width',1.5).attr('opacity',.25).style('animation','fp-map-pulse 3s ease infinite');
            gA.append('text').attr('x',op[0]).attr('y',op[1]-14).attr('text-anchor','middle').attr('fill','rgba(255,255,255,.7)').attr('font-size','11px').attr('font-weight','700').attr('letter-spacing','1.5px').attr('font-family','Outfit,system-ui,sans-serif').text(sel.code);
        }
    }

    /* Marqueurs destinations — camembert multi-couleur */
    function drawMarkers(){
        gM.selectAll('*').remove();
        var tt=document.getElementById('fp-map-tt');
        var tiata=document.getElementById('fp-tt-iata'),tcity=document.getElementById('fp-tt-city'),
            tregion=document.getElementById('fp-tt-region'),ttags=document.getElementById('fp-tt-tags'),
            tdesc=document.getElementById('fp-tt-desc'),tprice=document.getElementById('fp-tt-price'),
            tbtn=document.getElementById('fp-tt-btn');
        var hT=null, R=11;
        var arc=d3.arc();
        var fd=getFiltered();

        fd.forEach(function(d){
            var p=proj([d.lon,d.lat]); if(!p) return;
            if(p[0]<-20||p[0]>W+20||p[1]<-20||p[1]>H+20) return;
            var g=gM.append('g').style('cursor','pointer').attr('transform','translate('+p[0]+','+p[1]+')');

            // Halo
            g.append('circle').attr('r',26).attr('fill',d.colors[0]).attr('opacity',.08);

            // Fond noir du marqueur
            g.append('circle').attr('r',R).attr('fill','#0e1528');

            // Segments camembert (divisé par type)
            var nc=d.colors.length;
            if(nc===1){
                // Un seul type : cercle plein
                g.append('circle').attr('r',R).attr('fill','none').attr('stroke',d.colors[0]).attr('stroke-width',3);
                g.append('circle').attr('r',4).attr('fill',d.colors[0]);
            } else {
                // Multi-types : arcs divisés
                var anglePerSlice=2*Math.PI/nc;
                d.colors.forEach(function(col,i){
                    g.append('path')
                        .attr('d',arc({innerRadius:R-3,outerRadius:R,startAngle:i*anglePerSlice,endAngle:(i+1)*anglePerSlice}))
                        .attr('fill',col);
                });
                // Point central = première couleur
                g.append('circle').attr('r',3.5).attr('fill',d.colors[0]);
                // Bord extérieur pour la lisibilité
                g.append('circle').attr('r',R).attr('fill','none').attr('stroke','rgba(255,255,255,.15)').attr('stroke-width',.5);
            }

            // Pulsation
            var rg=g.append('circle').attr('r',R).attr('fill','none').attr('stroke',d.colors[0]).attr('stroke-width',.7);
            var dur=(2.4+Math.random()*1)+'s';
            rg.append('animate').attr('attributeName','r').attr('values','12;26;12').attr('dur',dur).attr('repeatCount','indefinite');
            rg.append('animate').attr('attributeName','opacity').attr('values','.3;0;.3').attr('dur',dur).attr('repeatCount','indefinite');

            // Tooltip
            g.on('mouseenter',function(){
                clearTimeout(hT);
                tiata.textContent=d.iata;
                tcity.textContent=d.pays;
                tregion.textContent=d.region;
                ttags.innerHTML='';
                (d.types||[]).forEach(function(t){
                    var lbl=TYPE_LABELS[t]||t;
                    ttags.innerHTML+='<span class="fp-tt-tag" style="background:rgba(255,255,255,.1);color:#fff">'+lbl+'</span>';
                });
                tdesc.textContent=d.city+' — '+d.count+' séjour'+(d.count>1?'s':'');
                tprice.textContent=d.count+' séjour'+(d.count>1?'s':'')+' disponible'+(d.count>1?'s':'');
                tbtn.textContent='Voir les séjours \u2192';
                tbtn.href=d.url;
                var br=box.getBoundingClientRect(),svgE=box.querySelector('svg'),sr=svgE.getBoundingClientRect();
                var sx=sr.width/W,sy=sr.height/H;
                var px=sr.left-br.left+p[0]*sx,py=sr.top-br.top+p[1]*sy;
                tt.style.left=(px+26)+'px';tt.style.top=(py-60)+'px';
                if(px+290>br.width) tt.style.left=(px-275)+'px';
                if(py-60<0) tt.style.top=(py+26)+'px';
                tt.classList.add('on');
                gM.selectAll('g').style('opacity',function(){return this===g.node()?1:.12;});
            });
            g.on('mouseleave',function(){hT=setTimeout(function(){tt.classList.remove('on');gM.selectAll('g').style('opacity',1);},200);});
            g.on('click',function(){window.location.href=d.url;});
            g.on('touchstart',function(e){
                e.preventDefault();
                if(tt.classList.contains('on')&&tcity.textContent.indexOf(d.pays)>-1){window.location.href=d.url;}
                else{g.dispatch('mouseenter');}
            },{passive:false});
        });
        if(tt){
            tt.addEventListener('mouseenter',function(){clearTimeout(hT);});
            tt.addEventListener('mouseleave',function(){hT=setTimeout(function(){tt.classList.remove('on');gM.selectAll('g').style('opacity',1);},200);});
        }
    }
})();
</script>

<!-- ═══════════════════════════════════════════════════════════════
     13. TRUST BAR
     ═══════════════════════════════════════════════════════════════ -->
<section class="fp-trust">
    <div class="fp-container">
        <div class="fp-trust-row">
            <div class="fp-trust-item">
                <div class="fp-trust-logo"><img src="https://apst.travel/wp-content/uploads/2025/12/Logo-Full-Ladybug-1.png" alt="APST Garantie" loading="lazy"></div>
                <div class="fp-trust-text"><strong>Garantie APST</strong><span>Protection financière voyageurs</span></div>
            </div>
            <div class="fp-trust-sep"></div>
            <div class="fp-trust-item">
                <div class="fp-trust-logo"><svg viewBox="0 0 140 44" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="140" height="44" rx="6" fill="#002654"/><rect x="0" width="8" height="44" rx="0" fill="#002654"/><rect x="4" width="4" height="44" fill="#fff"/><rect x="8" width="4" height="44" fill="#ED2939"/><text x="78" y="20" text-anchor="middle" fill="#fff" font-family="Georgia,serif" font-weight="700" font-size="13" letter-spacing=".5">Atout France</text><text x="78" y="34" text-anchor="middle" fill="rgba(255,255,255,.8)" font-family="Outfit,Arial,sans-serif" font-weight="600" font-size="8" letter-spacing=".8">IM051100014</text></svg></div>
                <div class="fp-trust-text"><strong>Atout France</strong><span>Immatriculation IM051100014</span></div>
            </div>
            <div class="fp-trust-sep"></div>
            <div class="fp-trust-item">
                <div class="fp-trust-logo"><svg viewBox="0 0 110 44" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="110" height="44" rx="6" fill="#0a2540"/><path d="M35 8 L55 4 L75 8 L75 24 C75 32 65 38 55 40 C45 38 35 32 35 24Z" fill="none" stroke="#00d4aa" stroke-width="1.5"/><path d="M46 22 L52 28 L64 16" fill="none" stroke="#00d4aa" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><text x="55" y="17" text-anchor="middle" fill="rgba(255,255,255,.25)" font-family="Outfit,sans-serif" font-size="5" letter-spacing="1">SSL</text></svg></div>
                <div class="fp-trust-text"><strong>Paiement sécurisé</strong><span>3D Secure · SSL · Paybox</span></div>
            </div>
            <div class="fp-trust-sep"></div>
            <div class="fp-trust-item">
                <div class="fp-trust-logo"><svg viewBox="0 0 110 44" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="110" height="44" rx="6" fill="#fff" stroke="#e5e7eb" stroke-width="1"/><text x="55" y="24" text-anchor="middle" fill="#c41230" font-family="Georgia,serif" font-weight="700" font-size="18" letter-spacing="2">HISCOX</text><text x="55" y="36" text-anchor="middle" fill="#9ca3af" font-family="Outfit,sans-serif" font-weight="600" font-size="7" letter-spacing=".5">RC Professionnelle</text></svg></div>
                <div class="fp-trust-text"><strong>Assurance Hiscox</strong><span>Responsabilité civile pro</span></div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     14. TÉMOIGNAGES
     ═══════════════════════════════════════════════════════════════ -->
<section class="fp-testi">
    <div class="fp-container">
        <p class="fp-section-label">⭐ Avis clients</p>
        <h2 class="fp-section-title">Ils sont partis, <em>ils en parlent</em></h2>
        <p style="text-align:center;color:var(--gray);font-size:14px;margin-bottom:24px">Avis Google 5 étoiles avec texte</p>
        <div class="fp-testi-grid" id="testi-grid"></div>
        <div class="fp-testi-dots" id="testi-dots"></div>
    </div>
</section>
<?php
$vs08_google_reviews = get_option('vs08v_google_reviews', []);
if (!is_array($vs08_google_reviews) || empty($vs08_google_reviews)) {
    $vs08_google_reviews = [
        ['initials' => 'MR', 'name' => 'Michel R.', 'trip' => 'Séjour Golf — Portugal Algarve', 'text' => 'Séjour parfait au Portugal. Parcours magnifiques, hôtel de rêve. L\'équipe a tout organisé, on n\'avait qu\'à jouer. On repart l\'an prochain !'],
        ['initials' => 'SL', 'name' => 'Sophie L.', 'trip' => 'Circuit — Italie du Sud', 'text' => 'Notre circuit en Italie était exceptionnel. Chaque étape était une découverte. Le conseiller a adapté le programme à nos envies. Merci !'],
        ['initials' => 'JP', 'name' => 'Jean-Pierre V.', 'trip' => 'Séjour Golf — Espagne Marbella', 'text' => 'On était 4 amis golfeurs, tout était parfaitement coordonné. Tee-times, transferts, dîner de groupe... Un vrai service premium à prix honnête.'],
        ['initials' => 'CG', 'name' => 'Catherine G.', 'trip' => 'Circuit — Grèce Crète', 'text' => 'Premier voyage organisé par une agence et quelle réussite ! Les hôtels, les visites, le rythme... tout était pensé pour nous. On recommande à 200 %.'],
        ['initials' => 'AD', 'name' => 'Alain D.', 'trip' => 'Séjour Golf — Maroc Agadir', 'text' => 'Prix vraiment transparent et service au top. Le conseiller était disponible même depuis le Maroc. Premier voyage golf en agence, je ne m\'en passerai plus.'],
        ['initials' => 'PB', 'name' => 'Philippe B.', 'trip' => 'Séjour Golf — Turquie Belek', 'text' => '3ème séjour avec Sortir 08 : Algarve, Maroc et maintenant la Turquie. On ne change pas une équipe qui gagne, toujours au-delà de nos attentes.'],
    ];
}
?>
<script>window.VS08_REVIEWS = <?php echo wp_json_encode(array_values($vs08_google_reviews)); ?>;</script>

<!-- ═══════════════════════════════════════════════════════════════
     15. NEWSLETTER + CTA DEVIS
     ═══════════════════════════════════════════════════════════════ -->
<section class="fp-nl-cta">
    <div class="fp-nl-band">
        <div class="fp-nl-side">
            <p class="fp-nl-badge">📧 Newsletter exclusive</p>
            <h2>Offres privées & <em>bons plans voyage</em></h2>
            <p>Ventes flash, nouvelles destinations, conseils d'expert : recevez le meilleur de nos offres directement dans votre boîte mail.</p>
            <form class="fp-nl-form" id="vs08-newsletter-form">
                <input type="email" name="email" placeholder="Votre adresse email..." required>
                <button type="submit">S'inscrire →</button>
            </form>
            <p id="vs08-newsletter-msg" style="display:none;margin-top:10px;font-size:14px;"></p>
            <div class="fp-nl-perks">
                <div class="fp-nl-perk"><span>✓</span> 1 email / semaine max</div>
                <div class="fp-nl-perk"><span>✓</span> Offres avant tout le monde</div>
                <div class="fp-nl-perk"><span>✓</span> Désinscription en 1 clic</div>
            </div>
            <p class="fp-nl-legal">En vous inscrivant, vous acceptez notre <a href="<?php echo esc_url($fp_url_rgpd); ?>" style="color:inherit;text-decoration:underline">politique de confidentialité</a>.</p>
        </div>
        <div class="fp-nl-sep" aria-hidden="true"></div>
        <div class="fp-cta-side">
            <div class="fp-cta-wrap">
                <div class="fp-cta-box">
                    <p class="fp-cta-eyebrow">Devis gratuit</p>
                    <h2>Votre voyage sur mesure</h2>
                    <p class="fp-cta-desc">Dites-nous destination, budget et envies. Un conseiller vous envoie une proposition sous 24-48h, sans engagement.</p>
                    <div class="fp-cta-trust">
                        <span>Réponse sous 24-48h</span><span>Sans engagement</span><span>Devis personnalisé</span>
                    </div>
                    <a href="<?php echo esc_url(home_url('/devis-gratuit')); ?>" class="fp-btn-devis">Demander mon devis <span class="btn-arrow">→</span></a>
                    <div class="fp-cta-phone">
                        <span>Ou par téléphone</span>
                        <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', vs08_opt('vs08_tel', '0326652863'))); ?>"><span>📞</span> <?php echo esc_html(vs08_opt('vs08_tel', '03 26 65 28 63')); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     16. WHATSAPP FLOAT
     ═══════════════════════════════════════════════════════════════ -->
<a href="<?php echo esc_url(home_url('/contact')); ?>" class="fp-wa" aria-label="Contact — besoin d'aide">
    <span style="line-height:1">💬</span>
    <div class="fp-wa-tip">Besoin d'aide ? Contactez-nous</div>
</a>

<!-- ═══════════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ─── IntersectionObserver — anime .fp-anim ET .fp-ucard ─── */
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fp-anim, .fp-ucard').forEach(function(el) { obs.observe(el); });

    /* ─── Header scroll ─── */
    var header = document.getElementById('header');
    window.addEventListener('scroll', function() {
        if (header) header.classList.toggle('scrolled', window.scrollY > 80);
    }, { passive: true });

    /* ─── Glow follow cursor (section Pourquoi) ─── */
    var whySec = document.getElementById('why-section');
    var whyGlow = document.getElementById('why-glow');
    if (whySec && whyGlow) {
        whySec.addEventListener('mousemove', function(e) {
            var rect = whySec.getBoundingClientRect();
            whyGlow.style.left = (e.clientX - rect.left) + 'px';
            whyGlow.style.top = (e.clientY - rect.top) + 'px';
            whyGlow.style.opacity = '1';
        });
        whySec.addEventListener('mouseleave', function() { whyGlow.style.opacity = '0'; });
    }

    /* ─── Témoignages carrousel ─── */
    var testiGrid = document.getElementById('testi-grid');
    var testiDotsWrap = document.getElementById('testi-dots');
    var allTestis = window.VS08_REVIEWS || [];
    if (testiGrid && testiDotsWrap && allTestis.length > 0) {
        var testiPage = 0;
        var testiPages = Math.ceil(allTestis.length / 3);
        var testiAutoTimer;
        for (var ti = 0; ti < testiPages; ti++) {
            var tdot = document.createElement('button');
            tdot.className = 'fp-testi-dot' + (ti === 0 ? ' active' : '');
            tdot.setAttribute('data-idx', ti);
            testiDotsWrap.appendChild(tdot);
        }
        function buildCard(t) {
            return '<div class="fp-testi-card"><div class="fp-stars">★★★★★</div><div class="fp-quote">"</div><p>' + t.text + '</p><div class="fp-testi-author"><div class="fp-avatar">' + t.initials + '</div><div><p class="fp-author-name">' + t.name + '</p><p class="fp-author-trip">' + t.trip + '</p></div></div></div>';
        }
        function showTestiPage(idx) {
            testiPage = idx;
            var cards = testiGrid.querySelectorAll('.fp-testi-card');
            cards.forEach(function(c) { c.classList.add('fade-out'); });
            setTimeout(function() {
                var start = testiPage * 3;
                var slice = allTestis.slice(start, start + 3);
                testiGrid.innerHTML = slice.map(buildCard).join('');
                setTimeout(function() {
                    testiGrid.querySelectorAll('.fp-testi-card').forEach(function(c) { c.classList.add('fade-in'); });
                }, 50);
            }, 400);
            testiDotsWrap.querySelectorAll('.fp-testi-dot').forEach(function(d, i) { d.classList.toggle('active', i === idx); });
        }
        showTestiPage(0);
        testiDotsWrap.addEventListener('click', function(e) {
            if (e.target.classList.contains('fp-testi-dot')) {
                showTestiPage(parseInt(e.target.getAttribute('data-idx')));
                clearInterval(testiAutoTimer); testiAutoRun();
            }
        });
        function testiAutoRun() {
            testiAutoTimer = setInterval(function() { showTestiPage((testiPage + 1) % testiPages); }, 6000);
        }
        testiAutoRun();
    }

    /* ─── Newsletter AJAX ─── */
    var newsletterForm = document.getElementById('vs08-newsletter-form');
    var newsletterMsg = document.getElementById('vs08-newsletter-msg');
    if (newsletterForm && newsletterMsg) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var email = newsletterForm.querySelector('input[name="email"]');
            if (!email || !email.value) return;
            var btn = newsletterForm.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
            var fd = new FormData();
            fd.append('action', 'vs08v_newsletter_subscribe');
            fd.append('email', email.value);
            fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    newsletterMsg.style.display = 'block';
                    newsletterMsg.textContent = (res && res.data && res.data.message) ? res.data.message : (res && res.success ? 'Merci pour votre inscription.' : 'Une erreur est survenue.');
                    newsletterMsg.style.color = (res && res.success) ? '#0f766e' : '#b91c1c';
                    if (res && res.success) email.value = '';
                })
                .finally(function() { if (btn) btn.disabled = false; });
        });
    }
});
</script>

<?php get_footer(); ?>
