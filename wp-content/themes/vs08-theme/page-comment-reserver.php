<?php
/**
 * Template Name: Comment réserver
 * Slug attendu : comment-reserver
 */
get_header();
?>
<style>
.cgv-wrap{background:#f9f6f0;padding:120px 0 80px}.cgv-inner{max-width:860px;margin:0 auto;padding:0 30px}
.cgv-inner h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);color:#0f2424;margin-bottom:16px}
.cgv-inner p,.cgv-inner li{font-size:15px;color:#4a5568;line-height:1.85}
.cgv-inner h2{font-size:20px;color:#0f2424;margin:28px 0 10px;padding-top:20px;border-top:1px solid #e2ddd3}
.step{display:flex;gap:20px;align-items:flex-start;margin-bottom:24px;background:#fff;padding:24px;border-radius:14px;box-shadow:0 2px 16px rgba(0,0,0,.05)}
.step-num{width:44px;height:44px;border-radius:50%;background:#59b7b7;color:#fff;font-weight:800;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.cgv-inner a{color:#3d9a9a;font-weight:600}
</style>
<div class="cgv-wrap"><div class="cgv-inner">
<h1>Comment réserver</h1>
<p>Réserver un séjour golf sur sortirmonde.fr en 3 étapes simples.</p>

<div class="step">
    <span class="step-num">1</span>
    <div>
        <h2 style="margin:0;padding:0;border:none">Choisir votre séjour</h2>
        <p>Parcourez nos <a href="<?php echo esc_url(home_url('/golf')); ?>">séjours golf</a> ou utilisez la recherche (destination, dates, aéroport). Consultez la fiche détaillée du séjour qui vous intéresse.</p>
    </div>
</div>
<div class="step">
    <span class="step-num">2</span>
    <div>
        <h2 style="margin:0;padding:0;border:none">Personnaliser et réserver</h2>
        <p>Sur la page du séjour, indiquez vos dates, le nombre de golfeurs et d'accompagnants, votre aéroport de départ. Sélectionnez vos vols et options. Le prix se met à jour en temps réel. Validez en payant l'acompte (30 % minimum) en ligne par carte bancaire sécurisée (3D Secure).</p>
    </div>
</div>
<div class="step">
    <span class="step-num">3</span>
    <div>
        <h2 style="margin:0;padding:0;border:none">Confirmation et solde</h2>
        <p>Vous recevez une confirmation par email. Le solde est dû 30 jours avant le départ. Votre conseiller dédié reste disponible pour toute question jusqu'au jour du retour.</p>
    </div>
</div>

<h2>Besoin d'aide ?</h2>
<p>Pour un devis sur mesure ou une question : <a href="<?php echo esc_url(home_url('/contact/')); ?>">contactez-nous</a> ou appelez le <a href="tel:0326652863">03 26 65 28 63</a>.</p>
</div>
</div>
<?php get_footer(); ?>
