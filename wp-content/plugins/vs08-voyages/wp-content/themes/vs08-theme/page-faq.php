<?php
/**
 * Template Name: FAQ
 * Slug attendu : faq
 */
get_header();
?>
<style>
.cgv-wrap{background:#f9f6f0;padding:120px 0 80px}.cgv-inner{max-width:860px;margin:0 auto;padding:0 30px}
.cgv-inner h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);color:#0f2424;margin-bottom:24px}
.faq-item{background:#fff;border-radius:14px;padding:20px 24px;margin-bottom:12px;box-shadow:0 2px 16px rgba(0,0,0,.05)}
.faq-item h3{font-size:16px;color:#0f2424;margin:0 0 8px}
.faq-item p{font-size:14px;color:#4a5568;line-height:1.75;margin:0}
.cgv-inner a{color:#3d9a9a;font-weight:600}
</style>
<div class="cgv-wrap"><div class="cgv-inner">
<h1>Foire aux questions</h1>

<div class="faq-item">
    <h3>Comment réserver un séjour golf ?</h3>
    <p>Choisissez votre séjour sur la page <a href="<?php echo esc_url(home_url('/golf')); ?>">Séjours Golf</a>, personnalisez dates et voyageurs, puis validez en payant l'acompte en ligne. Voir aussi <a href="<?php echo esc_url(home_url('/comment-reserver/')); ?>">Comment réserver</a>.</p>
</div>
<div class="faq-item">
    <h3>Quel est le montant de l'acompte ?</h3>
    <p>L'acompte est de 30 % minimum du prix total (il ne peut pas être inférieur au coût des billets d'avion). Le solde est dû 30 jours avant le départ.</p>
</div>
<div class="faq-item">
    <h3>Puis-je annuler ou modifier ma réservation ?</h3>
    <p>Les conditions d'annulation et de modification sont détaillées dans nos <a href="<?php echo esc_url(home_url('/conditions/')); ?>">Conditions Générales de Vente</a>. Une assurance annulation peut couvrir certains frais.</p>
</div>
<div class="faq-item">
    <h3>Les vols sont-ils inclus ?</h3>
    <p>Oui, nos forfaits sont « tout compris » : vols, hébergement, green fees, et selon les séjours transferts ou location de voiture. Le prix affiché sur la fiche séjour correspond au prix payé (hors options facultatives).</p>
</div>
<div class="faq-item">
    <h3>Comment vous contacter ?</h3>
    <p>Téléphone : <a href="tel:0326652863">03 26 65 28 63</a> (Lun–Ven 9h–18h). Email : contact@sortirmonde.fr. <a href="<?php echo esc_url(home_url('/contact/')); ?>">Page contact</a>.</p>
</div>

<p style="margin-top:28px"><a href="<?php echo esc_url(home_url('/contact/')); ?>" style="color:#3d9a9a;font-weight:600">→ Demander un devis ou être rappelé</a></p>
</div>
</div>
<?php get_footer(); ?>
