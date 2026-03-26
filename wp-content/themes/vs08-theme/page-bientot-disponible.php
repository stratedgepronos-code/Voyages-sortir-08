<?php
/**
 * Template Name: Bientôt disponible
 * Slug : bientot-disponible
 * Page d'attente pour les univers en cours de développement.
 */
get_header();

$univers = sanitize_key($_GET['univers'] ?? '');
$data = [
    'sejour' => [
        'icon'  => '☀️',
        'title' => 'Séjours All Inclusive',
        'desc'  => 'Soleil, plage, hôtels-clubs tout compris dans les plus belles destinations. Nos séjours All Inclusive arrivent très bientôt.',
        'img'   => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1920&q=80',
        'color' => '#59b7b7',
    ],
    'road_trip' => [
        'icon'  => '🚗',
        'title' => 'Road-Trips',
        'desc'  => 'Itinéraires sur mesure, étapes triées sur le volet, location de véhicule incluse. Bientôt, vous prendrez la route avec nous.',
        'img'   => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1920&q=80',
        'color' => '#e8724a',
    ],
    'parc' => [
        'icon'  => '🎢',
        'title' => 'Billets Parcs',
        'desc'  => 'Disneyland Paris, Parc Astérix, Europa-Park, Puy du Fou… des billets à prix réduit, bientôt disponibles ici.',
        'img'   => 'https://images.unsplash.com/photo-1560184897-ae75f418493e?w=1920&q=80',
        'color' => '#9b59b6',
    ],
    'city_trip' => [
        'icon'  => '🏙️',
        'title' => 'City Trips',
        'desc'  => 'Week-ends urbains à Barcelone, Lisbonne, Rome, Prague… culture, gastronomie et évasion express.',
        'img'   => 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=1920&q=80',
        'color' => '#3498db',
    ],
];
$d = $data[$univers] ?? [
    'icon'  => '✈️',
    'title' => 'Nouveauté',
    'desc'  => 'Cet univers de voyage arrive très bientôt sur notre site. Restez connecté !',
    'img'   => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1920&q=80',
    'color' => '#59b7b7',
];
$tel = vs08_opt('vs08_tel', '03 26 65 28 63');
$tel_raw = preg_replace('/\s+/', '', $tel);
?>
<style>
.bd-hero{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;overflow:hidden}
.bd-hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;transition:transform 20s linear}
.bd-hero:hover .bd-hero-bg{transform:scale(1.05)}
.bd-hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(15,36,36,.93) 0%,rgba(15,36,36,.8) 40%,rgba(15,36,36,.6) 100%)}
.bd-z{position:relative;z-index:2;max-width:680px;padding:24px}
.bd-icon{font-size:64px;margin-bottom:24px;display:block;animation:bd-float 3s ease-in-out infinite}
@keyframes bd-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.bd-badge{display:inline-flex;align-items:center;gap:8px;font-family:'Outfit',sans-serif;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:2.5px;padding:8px 20px;border-radius:100px;margin-bottom:28px;border:1px solid}
.bd-z h1{font-family:'Playfair Display',serif;font-size:clamp(36px,6vw,56px);color:#fff;margin:0 0 20px;line-height:1.1}
.bd-z h1 em{font-style:italic}
.bd-z p{font-family:'Outfit',sans-serif;font-size:17px;color:rgba(255,255,255,.7);line-height:1.7;margin:0 0 40px;font-weight:300}
.bd-cta{display:flex;flex-wrap:wrap;gap:14px;justify-content:center}
.bd-cta a{display:inline-flex;align-items:center;gap:8px;padding:16px 32px;border-radius:100px;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;font-size:15px;transition:all .3s}
.bd-btn-white{background:#fff;color:#0f2424}
.bd-btn-white:hover{background:#f0f0f0;transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.2)}
.bd-btn-outline{background:transparent;color:#fff;border:2px solid rgba(255,255,255,.3)}
.bd-btn-outline:hover{border-color:#fff;transform:translateY(-3px)}
.bd-footer-note{margin-top:48px;font-family:'Outfit',sans-serif;font-size:13px;color:rgba(255,255,255,.35);display:flex;align-items:center;gap:8px;justify-content:center}
.bd-pulse{width:8px;height:8px;border-radius:50%;animation:bd-pulse-anim 2s ease-in-out infinite}
@keyframes bd-pulse-anim{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.3)}}
</style>

<section class="bd-hero">
    <div class="bd-hero-bg" style="background-image:url('<?php echo esc_url($d['img']); ?>')"></div>
    <div class="bd-z">
        <span class="bd-icon"><?php echo $d['icon']; ?></span>
        <span class="bd-badge" style="color:<?php echo esc_attr($d['color']); ?>;border-color:<?php echo esc_attr($d['color']); ?>40;background:<?php echo esc_attr($d['color']); ?>15">En cours de d&eacute;veloppement</span>
        <h1><em><?php echo esc_html($d['title']); ?></em><br>arrivent bient&ocirc;t</h1>
        <p><?php echo esc_html($d['desc']); ?></p>
        <div class="bd-cta">
            <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>" class="bd-btn-white"><?php echo esc_html($d['icon']); ?> Demander un devis</a>
            <a href="tel:<?php echo esc_attr($tel_raw); ?>" class="bd-btn-outline">&#x1f4de; <?php echo esc_html($tel); ?></a>
        </div>
        <p class="bd-footer-note"><span class="bd-pulse" style="background:<?php echo esc_attr($d['color']); ?>"></span>Notre &eacute;quipe y travaille activement</p>
    </div>
</section>

<?php get_footer(); ?>
