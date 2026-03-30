<?php
if (!defined('ABSPATH')) {
    exit;
}
$c = $vs08_devis_cfg ?? [];
$nonce_name = $c['nonce_name'] ?? 'vs08_devis_nonce';
$nonce_action = $c['nonce_action'] ?? '';
$hero_emoji = $c['hero_emoji'] ?? '✈️';
$hero_title = $c['hero_title'] ?? 'Devis';
$hero_desc = $c['hero_desc'] ?? '';
$hero_bg = $c['hero_bg'] ?? 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1600&q=80';
$extra_field = $c['extra_field'] ?? ''; // 'city' | 'road' | 'circuit' | ''
?>
<style>
.vs08-da-hero{min-height:38vh;display:flex;align-items:center;background-size:cover;background-position:center;position:relative;padding:100px 80px 48px}
.vs08-da-hero::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,36,36,.92) 0%,rgba(26,58,58,.82) 100%)}
.vs08-da-hero-in{position:relative;z-index:1;max-width:720px}
.vs08-da-hero-in .em{font-size:42px;display:block;margin-bottom:12px}
.vs08-da-hero-in h1{font-size:clamp(28px,4vw,44px);color:#fff;font-family:'Playfair Display',serif;margin:0 0 12px}
.vs08-da-hero-in h1 em{color:#59b7b7;font-style:italic}
.vs08-da-hero-in p{color:rgba(255,255,255,.78);font-size:16px;line-height:1.65;font-family:'Outfit',sans-serif;margin:0}
.vs08-da-wrap{max-width:760px;margin:0 auto;padding:48px 24px 80px}
.vs08-da-card{background:#fff;border-radius:22px;padding:36px 32px;box-shadow:0 16px 56px rgba(15,36,36,.1);border:1px solid #f0f2f4}
.vs08-da-card h2{font-size:20px;color:#0f2424;margin:0 0 8px;font-family:'Playfair Display',serif}
.vs08-da-card .sub{font-size:13px;color:#6b7280;margin:0 0 24px;font-family:'Outfit',sans-serif}
.vs08-da-row{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.vs08-da-field{margin-bottom:18px}
.vs08-da-field label{display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;font-family:'Outfit',sans-serif}
.vs08-da-field input,.vs08-da-field select,.vs08-da-field textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:15px;font-family:'Outfit',sans-serif;box-sizing:border-box}
.vs08-da-field textarea{min-height:110px;resize:vertical}
.vs08-da-field input:focus,.vs08-da-field select:focus,.vs08-da-field textarea:focus{outline:none;border-color:#59b7b7}
.vs08-da-date-trigger{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:15px;font-family:'Outfit',sans-serif;color:#9ca3af;background:#fff;cursor:pointer;transition:all .2s;box-sizing:border-box}
.vs08-da-date-trigger:hover{border-color:#59b7b7;background:#f9fffe}
.vs08-da-submit{background:linear-gradient(135deg,#0f2424,#1a3a3a);color:#fff;border:none;padding:16px 36px;border-radius:14px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;margin-top:8px;width:100%;transition:transform .2s,box-shadow .2s}
.vs08-da-submit:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(15,36,36,.25)}
.vs08-da-success{background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #6ee7b7;color:#065f46;padding:24px;border-radius:16px;margin-bottom:24px;font-family:'Outfit',sans-serif}
.vs08-da-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:16px;border-radius:12px;margin-bottom:20px;font-family:'Outfit',sans-serif}
.vs08-da-consent{font-size:13px;color:#4b5563;line-height:1.5}
.vs08-da-consent input{width:auto;margin-right:8px}
@media(max-width:640px){.vs08-da-hero{padding:88px 24px 40px}.vs08-da-row{grid-template-columns:1fr}}
</style>

<section class="vs08-da-hero" style="background-image:url('<?php echo esc_url($hero_bg); ?>')">
    <div class="vs08-da-hero-in">
        <span class="em" aria-hidden="true"><?php echo esc_html($hero_emoji); ?></span>
        <h1>Devis <em><?php echo esc_html($hero_title); ?></em></h1>
        <p><?php echo esc_html($hero_desc); ?></p>
    </div>
</section>

<div class="vs08-da-wrap">
    <?php if (!empty($devis_sent)) : ?>
        <div class="vs08-da-success">
            <strong>Merci, votre demande a bien été transmise.</strong> Un conseiller vous répondra sous 24 à 48h ouvrés à l’adresse email indiquée.
        </div>
        <p style="text-align:center;font-family:'Outfit',sans-serif"><a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>" style="color:#59b7b7;font-weight:600">← Autre type de devis</a></p>
    <?php else : ?>
        <?php if (!empty($devis_error)) : ?>
            <div class="vs08-da-error"><?php echo esc_html($devis_error); ?></div>
        <?php endif; ?>
        <div class="vs08-da-card">
            <h2>Votre demande</h2>
            <p class="sub">Les champs marqués * sont obligatoires. Ces informations nous permettent de vous préparer une proposition personnalisée (comme sur une fiche « demande de cotation » en agence de voyages).</p>
            <form method="post" action="">
                <?php wp_nonce_field($nonce_action, $nonce_name); ?>
                <div class="vs08-da-row">
                    <div class="vs08-da-field">
                        <label for="da-civ">Civilité</label>
                        <select id="da-civ" name="civilite">
                            <option value="">—</option>
                            <option value="M." <?php selected(wp_unslash($_POST['civilite'] ?? ''), 'M.'); ?>>M.</option>
                            <option value="Mme" <?php selected(wp_unslash($_POST['civilite'] ?? ''), 'Mme'); ?>>Mme</option>
                            <option value="Mx" <?php selected(wp_unslash($_POST['civilite'] ?? ''), 'Mx'); ?>>Mx</option>
                        </select>
                    </div>
                    <div class="vs08-da-field"></div>
                </div>
                <div class="vs08-da-row">
                    <div class="vs08-da-field">
                        <label for="da-nom">Nom *</label>
                        <input type="text" id="da-nom" name="nom" required value="<?php echo esc_attr(wp_unslash($_POST['nom'] ?? '')); ?>">
                    </div>
                    <div class="vs08-da-field">
                        <label for="da-prenom">Prénom *</label>
                        <input type="text" id="da-prenom" name="prenom" required value="<?php echo esc_attr(wp_unslash($_POST['prenom'] ?? '')); ?>">
                    </div>
                </div>
                <div class="vs08-da-row">
                    <div class="vs08-da-field">
                        <label for="da-email">Email *</label>
                        <input type="email" id="da-email" name="email" required value="<?php echo esc_attr(wp_unslash($_POST['email'] ?? '')); ?>">
                    </div>
                    <div class="vs08-da-field">
                        <label for="da-tel">Téléphone</label>
                        <input type="tel" id="da-tel" name="tel" value="<?php echo esc_attr(wp_unslash($_POST['tel'] ?? '')); ?>">
                    </div>
                </div>
                <div class="vs08-da-row">
                    <div class="vs08-da-field">
                        <label for="da-cp">Code postal</label>
                        <input type="text" id="da-cp" name="cp" value="<?php echo esc_attr(wp_unslash($_POST['cp'] ?? '')); ?>">
                    </div>
                    <div class="vs08-da-field">
                        <label for="da-ville">Ville</label>
                        <input type="text" id="da-ville" name="ville" value="<?php echo esc_attr(wp_unslash($_POST['ville'] ?? '')); ?>">
                    </div>
                </div>
                <div class="vs08-da-field">
                    <label for="da-pays">Pays de résidence</label>
                    <input type="text" id="da-pays" name="pays_res" placeholder="France" value="<?php echo esc_attr(wp_unslash($_POST['pays_res'] ?? '')); ?>">
                </div>
                <?php if ($extra_field === 'city') : ?>
                <div class="vs08-da-field">
                    <label for="da-ville-trip">Ville ou pays du city trip *</label>
                    <input type="text" id="da-ville-trip" name="destination" required placeholder="Ex. Lisbonne, New York, Barcelone…" value="<?php echo esc_attr(wp_unslash($_POST['destination'] ?? '')); ?>">
                </div>
                <?php elseif ($extra_field === 'road') : ?>
                <div class="vs08-da-field">
                    <label for="da-road">Région / pays du road trip *</label>
                    <input type="text" id="da-road" name="destination" required placeholder="Ex. Ouest américain, Écosse, Portugal côtier…" value="<?php echo esc_attr(wp_unslash($_POST['destination'] ?? '')); ?>">
                </div>
                <?php elseif ($extra_field === 'circuit') : ?>
                <div class="vs08-da-field">
                    <label for="da-circ">Destination(s) ou thème du circuit *</label>
                    <input type="text" id="da-circ" name="destination" required placeholder="Ex. Andalousie, Japon, safari Afrique du Sud…" value="<?php echo esc_attr(wp_unslash($_POST['destination'] ?? '')); ?>">
                </div>
                <?php else : ?>
                <div class="vs08-da-field">
                    <label for="da-dest">Destination ou type de séjour souhaité *</label>
                    <input type="text" id="da-dest" name="destination" required placeholder="Mer, montagne, île, pays…" value="<?php echo esc_attr(wp_unslash($_POST['destination'] ?? '')); ?>">
                </div>
                <?php endif; ?>
                <div class="vs08-da-row">
                    <div class="vs08-da-field">
                        <label>Date de départ souhaitée</label>
                        <input type="hidden" id="da-d1" name="date_debut" value="<?php echo esc_attr(wp_unslash($_POST['date_debut'] ?? '')); ?>">
                        <div id="da-cal-d1-wrap" style="position:relative">
                            <div id="da-cal-d1-trigger" class="vs08-da-date-trigger" onclick="window.daCalD1 && window.daCalD1.toggle()">📅 Choisir une date</div>
                        </div>
                    </div>
                    <div class="vs08-da-field">
                        <label>Date de retour souhaitée</label>
                        <input type="hidden" id="da-d2" name="date_fin" value="<?php echo esc_attr(wp_unslash($_POST['date_fin'] ?? '')); ?>">
                        <div id="da-cal-d2-wrap" style="position:relative">
                            <div id="da-cal-d2-trigger" class="vs08-da-date-trigger" onclick="window.daCalD2 && window.daCalD2.toggle()">📅 Choisir une date</div>
                        </div>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    if (typeof VS08Calendar === 'undefined') { console.warn('VS08Calendar not loaded'); return; }
                    var minD = new Date(); minD.setDate(minD.getDate() + 7);
                    var yr = new Date().getFullYear();
                    var fmtOpts = { day:'numeric', month:'short', year:'numeric' };

                    window.daCalD1 = new VS08Calendar({
                        el:'#da-cal-d1-wrap', input:'#da-d1', mode:'date', inline:false,
                        title:'📅 Date de départ souhaitée', subtitle:'Cliquez sur le jour souhaité',
                        yearRange:[yr, yr+2], minDate:minD,
                        onSelect: function(d){
                            if(!d) return;
                            var ds = d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
                            document.getElementById('da-d1').value = ds;
                            var trigger = document.getElementById('da-cal-d1-trigger');
                            if(trigger){ trigger.textContent = '📅 ' + d.toLocaleDateString('fr-FR', fmtOpts); trigger.style.color = '#0f2424'; trigger.style.borderColor = '#59b7b7'; }
                        }
                    });

                    window.daCalD2 = new VS08Calendar({
                        el:'#da-cal-d2-wrap', input:'#da-d2', mode:'date', inline:false,
                        title:'📅 Date de retour souhaitée', subtitle:'Cliquez sur le jour souhaité',
                        yearRange:[yr, yr+2], minDate:minD,
                        onSelect: function(d){
                            if(!d) return;
                            var ds = d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
                            document.getElementById('da-d2').value = ds;
                            var trigger = document.getElementById('da-cal-d2-trigger');
                            if(trigger){ trigger.textContent = '📅 ' + d.toLocaleDateString('fr-FR', fmtOpts); trigger.style.color = '#0f2424'; trigger.style.borderColor = '#59b7b7'; }
                        }
                    });
                });
                </script>
                <div class="vs08-da-field">
                    <label for="da-flex">Flexibilité sur les dates</label>
                    <select id="da-flex" name="dates_flex">
                        <option value="Je suis flexible" <?php selected(wp_unslash($_POST['dates_flex'] ?? ''), 'Je suis flexible'); ?>>Je suis flexible</option>
                        <option value="Dates fixes" <?php selected(wp_unslash($_POST['dates_flex'] ?? ''), 'Dates fixes'); ?>>Dates fixes</option>
                        <option value="± 2-3 jours" <?php selected(wp_unslash($_POST['dates_flex'] ?? ''), '± 2-3 jours'); ?>>± 2-3 jours</option>
                    </select>
                </div>
                <div class="vs08-da-row">
                    <div class="vs08-da-field">
                        <label for="da-adl">Nombre d’adultes *</label>
                        <input type="number" id="da-adl" name="nb_adultes" min="1" max="30" required value="<?php echo esc_attr(wp_unslash($_POST['nb_adultes'] ?? '2')); ?>">
                    </div>
                    <div class="vs08-da-field">
                        <label for="da-enf">Nombre d’enfants</label>
                        <input type="number" id="da-enf" name="nb_enfants" min="0" max="20" value="<?php echo esc_attr(wp_unslash($_POST['nb_enfants'] ?? '0')); ?>">
                    </div>
                </div>
                <div class="vs08-da-field">
                    <label for="da-heb">Hébergement envisagé</label>
                    <select id="da-heb" name="hebergement">
                        <option value="">— Indifférent —</option>
                        <option value="Hôtel 3*" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Hôtel 3*'); ?>>Hôtel 3*</option>
                        <option value="Hôtel 4*" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Hôtel 4*'); ?>>Hôtel 4*</option>
                        <option value="Hôtel 5*" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Hôtel 5*'); ?>>Hôtel 5*</option>
                        <option value="Appartement / résidence" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Appartement / résidence'); ?>>Appartement / résidence</option>
                        <option value="Villa" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Villa'); ?>>Villa</option>
                        <option value="Autre" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Autre'); ?>>Autre</option>
                    </select>
                </div>
                <div class="vs08-da-field">
                    <label for="da-bud">Budget indicatif (total ou par personne)</label>
                    <input type="text" id="da-bud" name="budget" placeholder="Ex. 2 500 € pour 2 pers." value="<?php echo esc_attr(wp_unslash($_POST['budget'] ?? '')); ?>">
                </div>
                <div class="vs08-da-field">
                    <label for="da-src">Comment nous avez-vous connus ?</label>
                    <select id="da-src" name="comment_connu">
                        <option value="">—</option>
                        <option value="Recherche Google" <?php selected(wp_unslash($_POST['comment_connu'] ?? ''), 'Recherche Google'); ?>>Recherche Google</option>
                        <option value="Réseaux sociaux" <?php selected(wp_unslash($_POST['comment_connu'] ?? ''), 'Réseaux sociaux'); ?>>Réseaux sociaux</option>
                        <option value="Bouche à oreille" <?php selected(wp_unslash($_POST['comment_connu'] ?? ''), 'Bouche à oreille'); ?>>Bouche à oreille</option>
                        <option value="Ancien client" <?php selected(wp_unslash($_POST['comment_connu'] ?? ''), 'Ancien client'); ?>>Ancien client</option>
                        <option value="Presse / partenaire" <?php selected(wp_unslash($_POST['comment_connu'] ?? ''), 'Presse / partenaire'); ?>>Presse / partenaire</option>
                        <option value="Autre" <?php selected(wp_unslash($_POST['comment_connu'] ?? ''), 'Autre'); ?>>Autre</option>
                    </select>
                </div>
                <div class="vs08-da-field">
                    <label for="da-msg">Précisions (activités, vols, contraintes…)</label>
                    <textarea id="da-msg" name="message" placeholder="Plus vous êtes précis, plus notre proposition sera adaptée."><?php echo esc_textarea(wp_unslash($_POST['message'] ?? '')); ?></textarea>
                </div>
                <div class="vs08-da-field vs08-da-consent">
                    <label style="text-transform:none;font-weight:500;display:flex;align-items:flex-start;gap:10px">
                        <input type="checkbox" name="vs08_consent_rgpd" value="1" <?php checked(!empty($_POST['vs08_consent_rgpd'])); ?> required>
                        <span>J’accepte que mes données soient utilisées pour traiter ma demande de devis, dans le cadre de la <a href="<?php echo esc_url(home_url('/rgpd/')); ?>" target="_blank" rel="noopener" style="color:#59b7b7">politique de confidentialité</a>.</span>
                    </label>
                </div>
                <button type="submit" class="vs08-da-submit">Envoyer ma demande</button>
            </form>
        </div>
        <p style="text-align:center;margin-top:28px;font-family:'Outfit',sans-serif;font-size:14px">
            <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>" style="color:#59b7b7;font-weight:600">← Retour au choix du type de devis</a>
        </p>
    <?php endif; ?>
</div>
