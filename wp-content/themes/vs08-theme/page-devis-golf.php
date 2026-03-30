<?php
/**
 * Template Name: Devis Golf
 * Page : Demande de devis pour séjour golf — envoi à sortir08@wanadoo.fr
 */
get_header();

$devis_sent = false;
$devis_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['vs08_devis_nonce']) && wp_verify_nonce($_POST['vs08_devis_nonce'], 'vs08_devis_golf')) {
    $nom = sanitize_text_field(wp_unslash($_POST['nom'] ?? ''));
    $prenom = sanitize_text_field(wp_unslash($_POST['prenom'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $tel = sanitize_text_field(wp_unslash($_POST['tel'] ?? ''));
    $destination = sanitize_text_field(wp_unslash($_POST['destination'] ?? ''));
    $date_debut = sanitize_text_field(wp_unslash($_POST['date_debut'] ?? ''));
    $date_fin = sanitize_text_field(wp_unslash($_POST['date_fin'] ?? ''));
    $nb_golfeurs = sanitize_text_field(wp_unslash($_POST['nb_golfeurs'] ?? ''));
    $nb_accompagnants = sanitize_text_field(wp_unslash($_POST['nb_accompagnants'] ?? ''));
    $budget = sanitize_text_field(wp_unslash($_POST['budget'] ?? ''));
    $budget = sanitize_text_field(wp_unslash($_POST['budget'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

    if (is_email($email) && $nom && $prenom) {
        $to = 'sortir08@wanadoo.fr';
        $subject = '[Devis Golf] Demande de ' . $prenom . ' ' . $nom;
        $body = "Nouvelle demande de devis séjour golf depuis le site.\n\n";
        $body .= "— Nom : " . $nom . "\n";
        $body .= "— Prénom : " . $prenom . "\n";
        $body .= "— Email : " . $email . "\n";
        $body .= "— Téléphone : " . $tel . "\n\n";
        $body .= "— Destination souhaitée : " . $destination . "\n";
        $body .= "— Dates : du " . $date_debut . " au " . $date_fin . "\n";
        $body .= "— Nombre de golfeurs : " . $nb_golfeurs . "\n";
        $body .= "— Nombre d'accompagnants : " . $nb_accompagnants . "\n";
        $body .= "— Budget : " . $budget . "\n";
        $body .= "— Budget indicatif : " . $budget . "\n\n";
        $body .= "— Message :\n" . $message . "\n";

        $headers = ['Content-Type: text/plain; charset=UTF-8', 'From: Voyages Sortir 08 <noreply@sortirmonde.fr>', 'Reply-To: ' . $email];
        if (wp_mail($to, $subject, $body, $headers)) {
            $devis_sent = true;
        } else {
            $devis_error = 'L\'envoi a échoué. Vous pouvez nous contacter par téléphone.';
        }
    } else {
        $devis_error = 'Veuillez remplir au moins nom, prénom et une adresse email valide.';
    }
}
?>
<style>
.vs08-devis-hero{min-height:42vh;display:flex;align-items:center;background:url('https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=1600&q=80') center/cover no-repeat;position:relative;padding:100px 80px 60px}
.vs08-devis-hero::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,36,36,.88) 0%,rgba(26,74,74,.75) 100%)}
.vs08-devis-hero>*{position:relative;z-index:1}
.vs08-devis-hero h1{font-size:clamp(32px,4vw,48px);color:#fff;font-family:'Playfair Display',serif;margin-bottom:12px}
.vs08-devis-hero p{color:rgba(255,255,255,.8);font-size:16px;max-width:560px;font-family:'Outfit',sans-serif;line-height:1.6}
.vs08-devis-wrap{max-width:720px;margin:0 auto;padding:56px 24px 80px}
.vs08-devis-card{background:#fff;border-radius:20px;padding:40px;box-shadow:0 12px 48px rgba(0,0,0,.08);border:1px solid #f0f2f4}
.vs08-devis-card h2{font-size:22px;color:#0f2424;margin-bottom:24px;font-family:'Playfair Display',serif}
.vs08-devis-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.vs08-devis-field{margin-bottom:20px}
.vs08-devis-field label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;font-family:'Outfit',sans-serif;text-transform:uppercase;letter-spacing:.04em}
.vs08-devis-field input,.vs08-devis-field select,.vs08-devis-field textarea{width:100%;padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;font-size:15px;font-family:'Outfit',sans-serif}
.vs08-devis-field textarea{min-height:120px;resize:vertical}
.vs08-devis-field input:focus,.vs08-devis-field select:focus,.vs08-devis-field textarea:focus{outline:none;border-color:#59b7b7}
.vs08-devis-submit{background:#0f2424;color:#fff;border:none;padding:16px 32px;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;margin-top:10px}
.vs08-devis-submit:hover{background:#59b7b7}
.vs08-devis-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:24px;border-radius:12px;margin-bottom:24px}
.vs08-devis-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:16px;border-radius:12px;margin-bottom:24px}
@media(max-width:640px){.vs08-devis-hero{padding:80px 24px 40px}.vs08-devis-row{grid-template-columns:1fr}}
</style>

<section class="vs08-devis-hero">
    <div>
        <h1>Demande de devis <em style="color:#59b7b7">séjour golf</em></h1>
        <p>Décrivez-nous votre projet : destination, dates, nombre de golfeurs et accompagnants. Un conseiller vous répond sous 24h avec une proposition sur mesure.</p>
    </div>
</section>

<div class="vs08-devis-wrap">
    <?php if ($devis_sent) : ?>
        <div class="vs08-devis-success">
            <strong>Demande envoyée.</strong> Nous vous recontacterons très rapidement à l'adresse indiquée.
        </div>
    <?php else : ?>
        <?php if ($devis_error) : ?>
            <div class="vs08-devis-error"><?php echo esc_html($devis_error); ?></div>
        <?php endif; ?>

        <div class="vs08-devis-card">
            <h2>Votre projet</h2>
            <form method="post" action="">
                <?php wp_nonce_field('vs08_devis_golf', 'vs08_devis_nonce'); ?>
                <div class="vs08-devis-row">
                    <div class="vs08-devis-field">
                        <label for="devis-nom">Nom *</label>
                        <input type="text" id="devis-nom" name="nom" required value="<?php echo esc_attr(wp_unslash($_POST['nom'] ?? '')); ?>">
                    </div>
                    <div class="vs08-devis-field">
                        <label for="devis-prenom">Prénom *</label>
                        <input type="text" id="devis-prenom" name="prenom" required value="<?php echo esc_attr(wp_unslash($_POST['prenom'] ?? '')); ?>">
                    </div>
                </div>
                <div class="vs08-devis-row">
                    <div class="vs08-devis-field">
                        <label for="devis-email">Email *</label>
                        <input type="email" id="devis-email" name="email" required value="<?php echo esc_attr(wp_unslash($_POST['email'] ?? '')); ?>">
                    </div>
                    <div class="vs08-devis-field">
                        <label for="devis-tel">Téléphone</label>
                        <input type="tel" id="devis-tel" name="tel" value="<?php echo esc_attr(wp_unslash($_POST['tel'] ?? '')); ?>">
                    </div>
                </div>
                <div class="vs08-devis-field">
                    <label for="devis-destination">Destination souhaitée</label>
                    <input type="text" id="devis-destination" name="destination" placeholder="Ex. Algarve, Maroc, Espagne..." value="<?php echo esc_attr(wp_unslash($_POST['destination'] ?? '')); ?>">
                </div>
                <div class="vs08-devis-row">
                    <div class="vs08-devis-field">
                        <label>Date de départ</label>
                        <input type="hidden" id="devis-date-debut" name="date_debut" value="<?php echo esc_attr(wp_unslash($_POST['date_debut'] ?? '')); ?>">
                        <div id="devis-cal-debut" style="position:relative"></div>
                    </div>
                    <div class="vs08-devis-field">
                        <label>Date de retour</label>
                        <input type="hidden" id="devis-date-fin" name="date_fin" value="<?php echo esc_attr(wp_unslash($_POST['date_fin'] ?? '')); ?>">
                        <div id="devis-cal-fin" style="position:relative"></div>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    if (typeof VS08Calendar === 'undefined') return;
                    var minD = new Date(); minD.setDate(minD.getDate() + 7);
                    new VS08Calendar({ el:'#devis-cal-debut', input:'#devis-date-debut', mode:'date', inline:false, title:'Date de départ', minDate:minD,
                        onSelect: function(d){ document.getElementById('devis-date-debut').value = d.toISOString().slice(0,10); }
                    });
                    new VS08Calendar({ el:'#devis-cal-fin', input:'#devis-date-fin', mode:'date', inline:false, title:'Date de retour', minDate:minD,
                        onSelect: function(d){ document.getElementById('devis-date-fin').value = d.toISOString().slice(0,10); }
                    });
                });
                </script>
                <div class="vs08-devis-row">
                    <div class="vs08-devis-field">
                        <label for="devis-nb-golfeurs">Nombre de golfeurs</label>
                        <input type="number" id="devis-nb-golfeurs" name="nb_golfeurs" min="1" max="20" placeholder="Ex. 4" value="<?php echo esc_attr(wp_unslash($_POST['nb_golfeurs'] ?? '')); ?>">
                    </div>
                    <div class="vs08-devis-field">
                        <label for="devis-nb-accompagnants">Nombre d'accompagnants (non-golfeurs)</label>
                        <input type="number" id="devis-nb-accompagnants" name="nb_accompagnants" min="0" max="20" placeholder="Ex. 2" value="<?php echo esc_attr(wp_unslash($_POST['nb_accompagnants'] ?? '')); ?>">
                    </div>
                </div>
                <div class="vs08-devis-row">
                    <div class="vs08-devis-field">
                        <label for="devis-budget">Budget indicatif / personne</label>
                        <input type="text" id="devis-budget" name="budget" placeholder="Ex. 1500 €" value="<?php echo esc_attr(wp_unslash($_POST['budget'] ?? '')); ?>">
                    </div>
                </div>
                <div class="vs08-devis-field">
                    <label for="devis-message">Message (parcours souhaités, hébergement, options...)</label>
                    <textarea id="devis-message" name="message" placeholder="Décrivez votre projet en quelques lignes..."><?php echo esc_textarea(wp_unslash($_POST['message'] ?? '')); ?></textarea>
                </div>
                <button type="submit" class="vs08-devis-submit">Envoyer ma demande de devis</button>
            </form>
        </div>
        <p style="text-align:center;margin-top:24px">
            <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', vs08_opt('vs08_tel', '0326652863'))); ?>" style="color:#59b7b7;font-weight:600">Ou nous appeler</a> pour un devis par téléphone.
        </p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
