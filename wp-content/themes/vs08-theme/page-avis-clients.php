<?php
/**
 * Template Name: Avis clients
 * Slug attendu : avis-clients
 */
get_header();
$reviews = [
    ['name'=>'Laurent B.','stars'=>5,'trip'=>'Séjour Golf — Canaries','text'=>"C'est le deuxième voyage Golf que l'agence m'organise. Après l'Andalousie à 6 golfeurs, je reviens des Canaries avec 7 amis golfeurs où toute l'organisation était au Top. Hôtel, minibus, green fees sur 3 golfs. Je conseille cette agence les yeux fermés. Merci et à très bientôt !"],
    ['name'=>'Nathalie M.','stars'=>5,'trip'=>'Circuit — Séville','text'=>"Voyage à Séville au top. Tout était bien organisé. Hôtel America super, à proximité de la ville. Pour une première expérience avec Sortir 08 je ne suis pas déçue. Je recommande vivement cette agence. Le soleil était avec nous !"],
    ['name'=>'Christelle D.','stars'=>5,'trip'=>'Voyages multiples','text'=>"Plusieurs vacances réservées avec Sortir 08, toujours top ! De très bons conseils, très gentils et à l'écoute. Je recommande."],
    ['name'=>'Marc P.','stars'=>5,'trip'=>'Agence','text'=>"L'agence est superbe et la personne qui vous reçoit et vous conseille pour les voyages, il est super. Je le recommande vivement, merci à lui et son agence !"],
    ['name'=>'Amandine R.','stars'=>5,'trip'=>'Billets Disneyland','text'=>"Service parfait ! Nous avons reçu les places le jour de la réservation ! Prix imbattable, je recommande. Merci à Voyages Sortir 08, je rachèterai chez vous, c'est sûr !"],
    ['name'=>'François T.','stars'=>5,'trip'=>'Agence','text'=>"Agence à l'écoute et trouvant la meilleure solution pour ses clients. Je recommande sans hésiter."],
    ['name'=>'Samira K.','stars'=>5,'trip'=>'Séjour — Marrakech','text'=>"Super séjour, nous sommes très contents de l'accueil et très professionnels. Nous étions à Marrakech, grâce au professionnalisme de Sortir 08, nous sommes rentrés en France sans problème. Nous recommandons cette agence à 100 %."],
    ['name'=>'Patrick L.','stars'=>5,'trip'=>'Séjour Golf — Portugal','text'=>"Parfait ! Bel hôtel, pas cher, Portugal très beau. Merci à l'équipe de Sortir 08."],
    ['name'=>'Didier C.','stars'=>5,'trip'=>'Billets Disneyland','text'=>"J'ai commandé 3 tickets 1 jour/1 parc pour 64 euros au lieu de 89 euros sur le site Disney. 2 heures après la réservation mes tickets étaient dans ma boîte email. Aucun problème. Top !"],
    ['name'=>'Caroline V.','stars'=>5,'trip'=>'Circuit — Porto','text'=>"Voyage à Porto effectué en octobre. Équipe super, voyage inoubliable ! Merci !"],
    ['name'=>'Julien G.','stars'=>5,'trip'=>'Agence','text'=>"Super... Prestations nickel... Sympa, disponible et de bon conseil."],
    ['name'=>'Isabelle F.','stars'=>5,'trip'=>'Agence','text'=>"Agence à l'écoute du client et toujours disponible même quand vous êtes à l'autre bout du monde. À recommander !"],
];
?>
<style>
.av-hero{background:#0f2424;padding:140px 0 60px;text-align:center}
.av-hero h1{font-family:'Playfair Display',serif;font-size:clamp(30px,4vw,44px);color:#fff;margin:0 0 12px}
.av-hero h1 em{color:#7ecece;font-style:italic}
.av-hero p{color:rgba(255,255,255,.6);font-family:'Outfit',sans-serif;font-size:15px;margin:0 0 24px}
.av-hero-stats{display:flex;gap:40px;justify-content:center}
.av-hero-stat b{font-size:28px;color:#7ecece;font-family:'Playfair Display',serif}
.av-hero-stat span{display:block;font-size:11px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:1px;margin-top:4px;font-family:'Outfit',sans-serif}
.av-wrap{background:#f9f6f0;padding:60px 0 80px}
.av-inner{max-width:1100px;margin:0 auto;padding:0 30px}
.av-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px}
.av-card{background:#fff;border-radius:20px;padding:28px;box-shadow:0 4px 24px rgba(0,0,0,.05);transition:transform .3s,box-shadow .3s}
.av-card:hover{transform:translateY(-4px);box-shadow:0 16px 48px rgba(0,0,0,.1)}
.av-card-head{display:flex;align-items:center;gap:14px;margin-bottom:14px}
.av-card-avatar{width:44px;height:44px;border-radius:50%;background:#59b7b7;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;font-family:'Outfit',sans-serif;flex-shrink:0}
.av-card-info{flex:1}
.av-card-name{font-weight:700;color:#0f2424;font-size:14px;font-family:'Outfit',sans-serif}
.av-card-trip{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif}
.av-stars{color:#c9a84c;font-size:14px;letter-spacing:2px}
.av-card p{font-size:14px;color:#4a5568;line-height:1.7;margin:0;font-family:'Outfit',sans-serif;font-style:italic}
.av-google{display:flex;align-items:center;gap:6px;margin-top:14px;font-size:11px;color:#9ca3af;font-family:'Outfit',sans-serif}
.av-cta{text-align:center;margin-top:48px}
.av-cta a{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:100px;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;font-size:14px;transition:all .25s}
.av-cta .av-btn-g{background:#fff;color:#0f2424;border:2px solid #e5e7eb}
.av-cta .av-btn-g:hover{border-color:#59b7b7;transform:translateY(-2px)}
.av-cta .av-btn-d{background:#59b7b7;color:#fff;margin-left:12px}
.av-cta .av-btn-d:hover{background:#3d9a9a;transform:translateY(-2px)}
@media(max-width:768px){.av-hero{padding:120px 24px 48px}.av-hero-stats{gap:24px}.av-grid{grid-template-columns:1fr}}
</style>

<section class="av-hero">
    <h1>Ce que nos clients <em>en disent</em></h1>
    <p>Avis v&eacute;rifi&eacute;s collect&eacute;s sur Google.</p>
    <div class="av-hero-stats">
        <div class="av-hero-stat"><b>4.8/5</b><span>Note moyenne</span></div>
        <div class="av-hero-stat"><b>76+</b><span>Avis Google</span></div>
        <div class="av-hero-stat"><b>96%</b><span>Recommandent</span></div>
    </div>
</section>

<div class="av-wrap"><div class="av-inner">
<div class="av-grid">
<?php foreach ($reviews as $r) :
    $initials = '';
    $parts = explode(' ', $r['name']);
    foreach ($parts as $p) { $initials .= mb_substr($p, 0, 1); }
    $stars = str_repeat('★', $r['stars']);
?>
<div class="av-card">
    <div class="av-card-head">
        <div class="av-card-avatar"><?php echo esc_html($initials); ?></div>
        <div class="av-card-info">
            <div class="av-card-name"><?php echo esc_html($r['name']); ?></div>
            <div class="av-card-trip"><?php echo esc_html($r['trip']); ?></div>
        </div>
        <div class="av-stars"><?php echo $stars; ?></div>
    </div>
    <p>&laquo; <?php echo esc_html($r['text']); ?> &raquo;</p>
    <div class="av-google"><svg width="14" height="14" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg> Avis Google</div>
</div>
<?php endforeach; ?>
</div>

<div class="av-cta">
    <a href="https://g.page/r/CdFb27W4gnRWEBM/review" target="_blank" rel="noopener" class="av-btn-g">&#x2B50; Laisser un avis Google</a>
    <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>" class="av-btn-d">Demander un devis &rarr;</a>
</div>
</div></div>
<?php get_footer(); ?>
