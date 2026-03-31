<?php
/**
 * Template Name: Devis Road Trip
 * Slug : devis-road-trip
 * Le meilleur formulaire de devis road trip — circuits individuels sur mesure
 */
$vs08_devis_cfg = [
    'nonce_name'     => 'vs08_devis_nonce_road',
    'nonce_action'   => 'vs08_devis_road_trip',
    'subject_prefix' => '🚗 [Devis Road Trip]',
    'type_label'     => 'Road Trip',
    'hero_emoji'     => '🚗',
    'hero_title'     => 'road trip',
    'hero_desc'      => 'Votre route, votre rythme. Nous construisons votre itinéraire sur mesure avec les meilleurs hébergements et étapes.',
    'hero_bg'        => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1600&q=80',
    'extra_field'    => 'road',
];
$devis_sent = false;
$devis_error = '';

// ── Handler spécifique road trip ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['vs08_devis_nonce_road'])) {
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vs08_devis_nonce_road'])), 'vs08_devis_road_trip')) { return; }
    if (empty($_POST['vs08_consent_rgpd'])) { $devis_error = 'Veuillez accepter le traitement de vos données.'; }
    else {
        $nom         = sanitize_text_field(wp_unslash($_POST['nom'] ?? ''));
        $prenom      = sanitize_text_field(wp_unslash($_POST['prenom'] ?? ''));
        $email       = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $tel         = sanitize_text_field(wp_unslash($_POST['tel'] ?? ''));
        $destination = sanitize_text_field(wp_unslash($_POST['destination'] ?? ''));
        $date_debut  = sanitize_text_field(wp_unslash($_POST['date_debut'] ?? ''));
        $date_fin    = sanitize_text_field(wp_unslash($_POST['date_fin'] ?? ''));
        $nb_nuits    = sanitize_text_field(wp_unslash($_POST['nb_nuits'] ?? ''));
        $nb_adultes  = sanitize_text_field(wp_unslash($_POST['nb_adultes'] ?? ''));
        $nb_enfants  = intval($_POST['nb_enfants'] ?? 0);
        $vehicule    = sanitize_text_field(wp_unslash($_POST['vehicule'] ?? ''));
        $rythme      = sanitize_text_field(wp_unslash($_POST['rythme'] ?? ''));
        $hebergement = sanitize_text_field(wp_unslash($_POST['hebergement'] ?? ''));
        $etapes      = sanitize_text_field(wp_unslash($_POST['nb_etapes'] ?? ''));
        $budget      = sanitize_text_field(wp_unslash($_POST['budget'] ?? ''));
        $message     = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

        // Centres d'intérêt
        $interets_raw = isset($_POST['interets']) && is_array($_POST['interets']) ? $_POST['interets'] : [];
        $interets = implode(', ', array_map('sanitize_text_field', $interets_raw));

        // Étapes souhaitées (texte libre)
        $etapes_detail = sanitize_textarea_field(wp_unslash($_POST['etapes_detail'] ?? ''));

        // Âges enfants
        $ages = [];
        for ($i = 1; $i <= $nb_enfants; $i++) {
            $a = sanitize_text_field(wp_unslash($_POST['age_enfant_' . $i] ?? ''));
            if ($a !== '') $ages[] = $a . ' an' . ($a > 1 ? 's' : '');
        }
        $ages_str = implode(', ', $ages);

        // Dates formatées
        $d1 = $date_debut ? date('d/m/Y', strtotime($date_debut)) : '';
        $d2 = $date_fin ? date('d/m/Y', strtotime($date_fin)) : '';
        $periode = '';
        if ($d1 && $d2) $periode = $d1 . ' → ' . $d2;
        elseif ($d1) $periode = 'À partir du ' . $d1;

        if (is_email($email) && $nom && $prenom) {
            $to = 'sortir08.ag@wanadoo.fr';
            $subject = '🚗 [Devis Road Trip] ' . $prenom . ' ' . strtoupper($nom) . ' — ' . ($destination ?: 'Road Trip');

            $tr = function($icon, $label, $value) {
                if (empty($value) || trim($value) === '') return '';
                return '<tr><td style="padding:10px 14px;font-size:13px;color:#6b7280;font-weight:600;width:40%;border-bottom:1px solid #f0ece4;font-family:Outfit,Arial,sans-serif">' . $icon . ' ' . $label . '</td><td style="padding:10px 14px;font-size:14px;color:#0f2424;font-weight:500;border-bottom:1px solid #f0ece4;font-family:Outfit,Arial,sans-serif">' . esc_html($value) . '</td></tr>';
            };

            $body = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head>'
                . '<body style="margin:0;padding:0;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif">'
                . '<div style="max-width:640px;margin:20px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">'
                . '<div style="background:linear-gradient(135deg,#0f2424,#1a4a4a);padding:28px 32px;text-align:center">'
                . '<div style="font-size:20px;font-weight:700;color:#fff;font-family:Georgia,serif">Voyages Sortir 08</div>'
                . '<div style="font-size:11px;color:rgba(255,255,255,.5);margin-top:4px">SPÉCIALISTE GOLF & VOYAGES</div></div>'
                . '<div style="text-align:center;padding:20px 32px 0"><span style="display:inline-block;background:linear-gradient(135deg,#e8724a,#d4603c);color:#fff;padding:6px 20px;border-radius:100px;font-size:12px;font-weight:700;letter-spacing:1px">🚗 DEVIS ROAD TRIP</span></div>'
                . '<div style="padding:20px 32px 0"><div style="font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px">👤 Client</div>'
                . '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse">'
                . $tr('', 'Nom', strtoupper($nom)) . $tr('', 'Prénom', $prenom)
                . $tr('📧', 'Email', $email) . $tr('📞', 'Téléphone', $tel)
                . '</table></div>'
                . '<div style="padding:20px 32px 0"><div style="font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px">🗺️ Road Trip</div>'
                . '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse">'
                . $tr('🌍', 'Destination', $destination) . $tr('📅', 'Période', $periode) . $tr('🌙', 'Durée', $nb_nuits ? $nb_nuits . ' nuits' : '')
                . $tr('👥', 'Adultes', $nb_adultes)
                . $tr('👶', 'Enfants', $nb_enfants > 0 ? $nb_enfants : '') . $tr('🎂', 'Âges enfants', $ages_str)
                . $tr('🚗', 'Véhicule', $vehicule) . $tr('⏱️', 'Rythme', $rythme)
                . $tr('📍', 'Nb étapes', $etapes) . $tr('🏨', 'Hébergement', $hebergement)
                . $tr('❤️', 'Centres d\'intérêt', $interets) . $tr('💰', 'Budget', $budget)
                . '</table></div>'
                . ($etapes_detail ? '<div style="padding:20px 32px 0"><div style="font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px">📍 Étapes / lieux souhaités</div>'
                    . '<div style="background:#f9f6f0;border-radius:12px;padding:16px;font-size:14px;color:#374151;line-height:1.6;white-space:pre-wrap">' . esc_html($etapes_detail) . '</div></div>' : '')
                . ($message ? '<div style="padding:20px 32px 0"><div style="font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px">💬 Message</div>'
                    . '<div style="background:#f9f6f0;border-radius:12px;padding:16px;font-size:14px;color:#374151;line-height:1.6;white-space:pre-wrap">' . esc_html($message) . '</div></div>' : '')
                . '<div style="padding:24px 32px;text-align:center"><a href="mailto:' . esc_attr($email) . '?subject=' . rawurlencode('Votre devis Road Trip — Voyages Sortir 08') . '" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#0f2424,#1a3a3a);color:#fff;text-decoration:none;border-radius:12px;font-weight:700;font-size:14px">✉️ Répondre au client</a></div>'
                . '<div style="background:#f9f6f0;padding:16px 32px;text-align:center;font-size:11px;color:#9ca3af">Reçu le ' . date('d/m/Y à H:i') . ' · Formulaire devis Road Trip</div>'
                . '</div></body></html>';

            $headers = ['Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $email];
            $ok = vs08_mail_devis_agence($to, $subject, $body, $headers);
            $devis_sent = (bool) $ok;
            if (!$devis_sent) $devis_error = 'Erreur technique lors de l\'envoi.';
        } else {
            $devis_error = 'Veuillez remplir au moins le nom, le prénom et une adresse email valide.';
        }
    }
}

get_header();
?>
<style>
/* ═══ ROAD TRIP DEVIS — Premium immersive form ═══ */
.rt-hero{min-height:42vh;display:flex;align-items:flex-end;background-size:cover;background-position:center;position:relative;padding:0}
.rt-hero::before{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(15,36,36,.95) 0%,rgba(15,36,36,.4) 50%,transparent 100%)}
.rt-hero-in{position:relative;z-index:1;padding:80px 80px 48px;max-width:800px}
.rt-hero-in .rt-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(232,114,74,.2);border:1px solid rgba(232,114,74,.4);color:#e8724a;padding:5px 14px;border-radius:100px;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;font-family:'Outfit',sans-serif;margin-bottom:16px}
.rt-hero-in h1{font-size:clamp(28px,4.5vw,48px);color:#fff;font-family:'Playfair Display',serif;margin:0 0 12px;line-height:1.15}
.rt-hero-in h1 em{color:#e8724a;font-style:italic}
.rt-hero-in p{color:rgba(255,255,255,.7);font-size:16px;line-height:1.65;font-family:'Outfit',sans-serif;margin:0;max-width:560px}

.rt-wrap{max-width:780px;margin:0 auto;padding:48px 24px 80px}
.rt-card{background:#fff;border-radius:22px;padding:40px 36px;box-shadow:0 16px 56px rgba(15,36,36,.1);border:1px solid #f0f2f4}
.rt-card h2{font-size:22px;color:#0f2424;margin:0 0 4px;font-family:'Playfair Display',serif}
.rt-card .sub{font-size:13px;color:#6b7280;margin:0 0 28px;font-family:'Outfit',sans-serif}

/* Section headers in the form */
.rt-section{font-size:12px;font-weight:700;color:#e8724a;text-transform:uppercase;letter-spacing:1.5px;margin:28px 0 14px;padding-top:20px;border-top:1px solid #f0ece4;font-family:'Outfit',sans-serif;display:flex;align-items:center;gap:8px}
.rt-section:first-of-type{border-top:none;margin-top:0;padding-top:0}

.rt-row{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.rt-field{margin-bottom:18px}
.rt-field label{display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;font-family:'Outfit',sans-serif}
.rt-field input,.rt-field select,.rt-field textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:15px;font-family:'Outfit',sans-serif;box-sizing:border-box;transition:border-color .2s}
.rt-field input:focus,.rt-field select:focus,.rt-field textarea:focus{outline:none;border-color:#e8724a}
.rt-field textarea{min-height:100px;resize:vertical}

/* Checkboxes interests */
.rt-interests{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px}
.rt-interest-tag{display:flex;align-items:center;gap:6px;padding:8px 14px;border:1.5px solid #e5e7eb;border-radius:100px;cursor:pointer;font-size:13px;font-family:'Outfit',sans-serif;color:#374151;transition:all .2s;user-select:none}
.rt-interest-tag:hover{border-color:#e8724a;background:rgba(232,114,74,.04)}
.rt-interest-tag input{display:none}
.rt-interest-tag:has(input:checked){background:#e8724a;color:#fff;border-color:#e8724a}
.rt-interest-tag .rt-emoji{font-size:15px}

/* Child ages */
.rt-enfants-ages{margin-top:8px;display:flex;flex-wrap:wrap;gap:10px}
.rt-enfant-age{background:#faf7f2;border:1.5px solid #e5e7eb;border-radius:10px;padding:8px 12px;display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;font-family:'Outfit',sans-serif}
.rt-enfant-age select{padding:6px 10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;font-family:'Outfit',sans-serif;background:#fff;min-width:70px}

/* Calendar trigger */
.rt-date-trigger{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:15px;font-family:'Outfit',sans-serif;color:#9ca3af;background:#fff;cursor:pointer;transition:all .2s;box-sizing:border-box}
.rt-date-trigger:hover{border-color:#e8724a;background:#fffbf7}

.rt-submit{background:linear-gradient(135deg,#e8724a,#d4603c);color:#fff;border:none;padding:18px 36px;border-radius:14px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;margin-top:12px;width:100%;transition:transform .2s,box-shadow .2s;letter-spacing:.3px}
.rt-submit:hover{transform:translateY(-2px);box-shadow:0 12px 36px rgba(232,114,74,.35)}

.rt-success{background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #6ee7b7;color:#065f46;padding:24px;border-radius:16px;margin-bottom:24px;font-family:'Outfit',sans-serif}
.rt-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:16px;border-radius:12px;margin-bottom:20px;font-family:'Outfit',sans-serif}
.rt-consent{font-size:13px;color:#4b5563;line-height:1.5}
.rt-consent input{width:auto;margin-right:8px}

@media(max-width:640px){
    .rt-hero-in{padding:90px 20px 36px}
    .rt-card{padding:24px 18px}
    .rt-row{grid-template-columns:1fr}
    .rt-interests{gap:6px}
    .rt-interest-tag{padding:7px 12px;font-size:12px}
    .rt-enfants-ages{flex-direction:column}
}
</style>

<section class="rt-hero" style="background-image:url('https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1600&q=80')">
    <div class="rt-hero-in">
        <div class="rt-badge">🚗 Sur mesure</div>
        <h1>Votre <em>road trip</em> idéal</h1>
        <p>Circuit individuel en voiture, à votre rythme. Décrivez-nous votre rêve — nous construisons l'itinéraire, les hébergements et les étapes incontournables.</p>
    </div>
</section>

<div class="rt-wrap">
    <?php if ($devis_sent) : ?>
        <div class="rt-success"><strong>Votre demande a été transmise.</strong> Un conseiller spécialiste road trip vous répondra sous 24 à 48h avec une proposition d'itinéraire personnalisé.</div>
        <p style="text-align:center;font-family:'Outfit',sans-serif"><a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>" style="color:#e8724a;font-weight:600">← Autre type de devis</a></p>
    <?php else : ?>
        <?php if ($devis_error) : ?>
            <div class="rt-error"><?php echo esc_html($devis_error); ?></div>
        <?php endif; ?>
        <div class="rt-card">
            <h2>Construisons votre route</h2>
            <p class="sub">Quelques informations pour imaginer l'itinéraire parfait. Plus vous êtes précis, plus notre proposition sera sur mesure.</p>

            <form method="post" action="">
                <?php wp_nonce_field('vs08_devis_road_trip', 'vs08_devis_nonce_road'); ?>

                <!-- ═══ VOUS ═══ -->
                <div class="rt-section">👤 Vous</div>
                <div class="rt-row">
                    <div class="rt-field"><label for="rt-nom">Nom *</label><input type="text" id="rt-nom" name="nom" required value="<?php echo esc_attr(wp_unslash($_POST['nom'] ?? '')); ?>"></div>
                    <div class="rt-field"><label for="rt-prenom">Prénom *</label><input type="text" id="rt-prenom" name="prenom" required value="<?php echo esc_attr(wp_unslash($_POST['prenom'] ?? '')); ?>"></div>
                </div>
                <div class="rt-row">
                    <div class="rt-field"><label for="rt-email">Email *</label><input type="email" id="rt-email" name="email" required value="<?php echo esc_attr(wp_unslash($_POST['email'] ?? '')); ?>"></div>
                    <div class="rt-field"><label for="rt-tel">Téléphone</label><input type="tel" id="rt-tel" name="tel" value="<?php echo esc_attr(wp_unslash($_POST['tel'] ?? '')); ?>"></div>
                </div>

                <!-- ═══ DESTINATION & DATES ═══ -->
                <div class="rt-section">🗺️ Destination & dates</div>
                <div class="rt-field">
                    <label for="rt-dest">Pays / région du road trip *</label>
                    <input type="text" id="rt-dest" name="destination" required placeholder="Ex. Ouest américain, Écosse, Portugal côtier, Islande…" value="<?php echo esc_attr(wp_unslash($_POST['destination'] ?? '')); ?>">
                </div>
                <div class="rt-row">
                    <div class="rt-field">
                        <label>Période de départ</label>
                        <input type="hidden" id="rt-d1" name="date_debut" value="<?php echo esc_attr(wp_unslash($_POST['date_debut'] ?? '')); ?>">
                        <input type="hidden" id="rt-d2" name="date_fin" value="<?php echo esc_attr(wp_unslash($_POST['date_fin'] ?? '')); ?>">
                        <div id="rt-cal-wrap" style="position:relative">
                            <div id="rt-cal-trigger" class="rt-date-trigger" onclick="window.rtCalRange && window.rtCalRange.toggle()">📅 Départ entre… et…</div>
                        </div>
                    </div>
                    <div class="rt-field">
                        <label for="rt-nuits">Nombre de nuits</label>
                        <select id="rt-nuits" name="nb_nuits">
                            <?php for ($n = 5; $n <= 21; $n++): ?>
                            <option value="<?php echo $n; ?>" <?php selected(wp_unslash($_POST['nb_nuits'] ?? '10'), $n); ?>><?php echo $n; ?> nuits</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    if (typeof VS08Calendar === 'undefined') return;
                    var minD = new Date(); minD.setDate(minD.getDate() + 14);
                    var yr = new Date().getFullYear();
                    var fmtOpts = { day:'numeric', month:'short' };
                    window.rtCalRange = new VS08Calendar({
                        el:'#rt-cal-wrap', input:'#rt-d1', inputEnd:'#rt-d2',
                        mode:'range', inline:false,
                        title:'📅 Période de départ', subtitle:'Départ au plus tôt → au plus tard',
                        yearRange:[yr, yr+2], minDate:minD,
                        onConfirm: function(dep, ret){
                            var trigger = document.getElementById('rt-cal-trigger');
                            if(!trigger) return;
                            var txt = '📅 ' + dep.toLocaleDateString('fr-FR', fmtOpts);
                            if(ret) txt += ' → ' + ret.toLocaleDateString('fr-FR', fmtOpts);
                            trigger.textContent = txt;
                            trigger.style.color = '#0f2424';
                            trigger.style.borderColor = '#e8724a';
                        }
                    });
                });
                </script>

                <!-- ═══ VOYAGEURS ═══ -->
                <div class="rt-section">👥 Voyageurs</div>
                <div class="rt-row">
                    <div class="rt-field"><label for="rt-adl">Adultes *</label><input type="number" id="rt-adl" name="nb_adultes" min="1" max="12" required value="<?php echo esc_attr(wp_unslash($_POST['nb_adultes'] ?? '2')); ?>"></div>
                    <div class="rt-field"><label for="rt-enf">Enfants (- de 18 ans)</label><input type="number" id="rt-enf" name="nb_enfants" min="0" max="10" value="<?php echo esc_attr(wp_unslash($_POST['nb_enfants'] ?? '0')); ?>"></div>
                </div>
                <div id="rt-enfants-wrap"></div>
                <script>
                (function(){
                    var enfInput = document.getElementById('rt-enf');
                    var wrap = document.getElementById('rt-enfants-wrap');
                    if (!enfInput || !wrap) return;
                    function build() {
                        var nb = parseInt(enfInput.value) || 0;
                        if (nb <= 0 || nb > 10) { wrap.innerHTML = ''; return; }
                        var html = '<div class="rt-field" style="margin-top:0"><label>Âge de chaque enfant au moment du retour</label><div class="rt-enfants-ages">';
                        for (var i = 1; i <= nb; i++) {
                            html += '<div class="rt-enfant-age"><span>Enfant ' + i + '</span><select name="age_enfant_' + i + '"><option value="">—</option>';
                            for (var a = 0; a <= 17; a++) html += '<option value="' + a + '">' + a + ' an' + (a > 1 ? 's' : '') + '</option>';
                            html += '</select></div>';
                        }
                        html += '</div></div>';
                        wrap.innerHTML = html;
                    }
                    enfInput.addEventListener('change', build);
                    enfInput.addEventListener('input', build);
                    build();
                })();
                </script>

                <!-- ═══ VOTRE ROAD TRIP ═══ -->
                <div class="rt-section">🚗 Votre road trip</div>
                <div class="rt-row">
                    <div class="rt-field">
                        <label for="rt-vehic">Type de véhicule</label>
                        <select id="rt-vehic" name="vehicule">
                            <option value="">— Indifférent —</option>
                            <option value="Citadine / compacte" <?php selected(wp_unslash($_POST['vehicule'] ?? ''), 'Citadine / compacte'); ?>>🚗 Citadine / compacte</option>
                            <option value="SUV / crossover" <?php selected(wp_unslash($_POST['vehicule'] ?? ''), 'SUV / crossover'); ?>>🚙 SUV / crossover</option>
                            <option value="Berline confort" <?php selected(wp_unslash($_POST['vehicule'] ?? ''), 'Berline confort'); ?>>🚘 Berline confort</option>
                            <option value="Cabriolet" <?php selected(wp_unslash($_POST['vehicule'] ?? ''), 'Cabriolet'); ?>>🏎️ Cabriolet</option>
                            <option value="Van / campervan" <?php selected(wp_unslash($_POST['vehicule'] ?? ''), 'Van / campervan'); ?>>🚐 Van / campervan</option>
                            <option value="4x4" <?php selected(wp_unslash($_POST['vehicule'] ?? ''), '4x4'); ?>>🛻 4x4</option>
                        </select>
                    </div>
                    <div class="rt-field">
                        <label for="rt-rythme">Rythme du voyage</label>
                        <select id="rt-rythme" name="rythme">
                            <option value="Équilibré" <?php selected(wp_unslash($_POST['rythme'] ?? ''), 'Équilibré'); ?>>⚖️ Équilibré — visites + détente</option>
                            <option value="Intensif" <?php selected(wp_unslash($_POST['rythme'] ?? ''), 'Intensif'); ?>>🏃 Intensif — voir un max de choses</option>
                            <option value="Relaxé" <?php selected(wp_unslash($_POST['rythme'] ?? ''), 'Relaxé'); ?>>🧘 Relaxé — prendre son temps</option>
                        </select>
                    </div>
                </div>
                <div class="rt-row">
                    <div class="rt-field">
                        <label for="rt-etapes">Nombre d'étapes souhaitées</label>
                        <select id="rt-etapes" name="nb_etapes">
                            <option value="">— À vous de proposer —</option>
                            <option value="2-3 étapes" <?php selected(wp_unslash($_POST['nb_etapes'] ?? ''), '2-3 étapes'); ?>>2–3 étapes (posé)</option>
                            <option value="4-5 étapes" <?php selected(wp_unslash($_POST['nb_etapes'] ?? ''), '4-5 étapes'); ?>>4–5 étapes (classique)</option>
                            <option value="6-8 étapes" <?php selected(wp_unslash($_POST['nb_etapes'] ?? ''), '6-8 étapes'); ?>>6–8 étapes (complet)</option>
                            <option value="9+ étapes" <?php selected(wp_unslash($_POST['nb_etapes'] ?? ''), '9+ étapes'); ?>>9+ étapes (intensif)</option>
                        </select>
                    </div>
                    <div class="rt-field">
                        <label for="rt-heb">Hébergement</label>
                        <select id="rt-heb" name="hebergement">
                            <option value="">— Indifférent —</option>
                            <option value="Hôtel 3*" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Hôtel 3*'); ?>>Hôtel 3*</option>
                            <option value="Hôtel 4*" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Hôtel 4*'); ?>>Hôtel 4*</option>
                            <option value="Hôtel 5* / boutique" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Hôtel 5* / boutique'); ?>>Hôtel 5* / boutique</option>
                            <option value="Charme / B&B" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Charme / B&B'); ?>>Charme / B&B</option>
                            <option value="Mix hôtel + charme" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Mix hôtel + charme'); ?>>Mix hôtel + charme</option>
                            <option value="Lodges / éco" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Lodges / éco'); ?>>Lodges / éco</option>
                            <option value="Camping / campervan" <?php selected(wp_unslash($_POST['hebergement'] ?? ''), 'Camping / campervan'); ?>>Camping / campervan</option>
                        </select>
                    </div>
                </div>

                <!-- Centres d'intérêt -->
                <div class="rt-field">
                    <label>Centres d'intérêt (cochez tout ce qui vous plaît)</label>
                    <div class="rt-interests">
                        <?php
                        $interests = [
                            'nature' => ['🌿', 'Nature & paysages'],
                            'culture' => ['🏛️', 'Culture & patrimoine'],
                            'gastro' => ['🍷', 'Gastronomie & vins'],
                            'plage' => ['🏖️', 'Plage & mer'],
                            'sport' => ['🚴', 'Sport & outdoor'],
                            'photo' => ['📸', 'Photo & instagram'],
                            'famille' => ['👨‍👩‍👧', 'Famille & kids'],
                            'aventure' => ['🧗', 'Aventure & sensations'],
                            'detente' => ['🧖', 'Détente & wellness'],
                            'animaux' => ['🐾', 'Faune & safari'],
                        ];
                        foreach ($interests as $key => [$emoji, $label]):
                            $checked = in_array($key, (array)($_POST['interets'] ?? [])) ? 'checked' : '';
                        ?>
                        <label class="rt-interest-tag"><input type="checkbox" name="interets[]" value="<?php echo esc_attr($key); ?>" <?php echo $checked; ?>><span class="rt-emoji"><?php echo $emoji; ?></span> <?php echo esc_html($label); ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Étapes détaillées -->
                <div class="rt-field">
                    <label for="rt-etapes-detail">Lieux ou étapes que vous aimeriez inclure</label>
                    <textarea id="rt-etapes-detail" name="etapes_detail" placeholder="Ex. San Francisco → Yosemite → Death Valley → Las Vegas → Grand Canyon → Monument Valley → Antelope Canyon → Page → Bryce Canyon → Zion → Los Angeles"><?php echo esc_textarea(wp_unslash($_POST['etapes_detail'] ?? '')); ?></textarea>
                </div>

                <!-- Budget -->
                <div class="rt-section">💰 Budget</div>
                <div class="rt-field">
                    <label for="rt-bud">Budget indicatif (total ou par personne)</label>
                    <input type="text" id="rt-bud" name="budget" placeholder="Ex. 3 000 € / pers. vols inclus" value="<?php echo esc_attr(wp_unslash($_POST['budget'] ?? '')); ?>">
                </div>

                <!-- Message -->
                <div class="rt-field">
                    <label for="rt-msg">Remarques, envies, contraintes…</label>
                    <textarea id="rt-msg" name="message" placeholder="Ce qui compte pour vous : une plage au milieu du trip, un match de baseball, un survol en hélico…"><?php echo esc_textarea(wp_unslash($_POST['message'] ?? '')); ?></textarea>
                </div>

                <!-- RGPD -->
                <div class="rt-field rt-consent">
                    <label style="text-transform:none;font-weight:500;display:flex;align-items:flex-start;gap:10px">
                        <input type="checkbox" name="vs08_consent_rgpd" value="1" <?php checked(!empty($_POST['vs08_consent_rgpd'])); ?> required>
                        <span>J'accepte que mes données soient utilisées pour traiter ma demande, dans le cadre de la <a href="<?php echo esc_url(home_url('/rgpd/')); ?>" target="_blank" rel="noopener" style="color:#e8724a">politique de confidentialité</a>.</span>
                    </label>
                </div>

                <button type="submit" class="rt-submit">🚗 Recevoir ma proposition d'itinéraire</button>
            </form>
        </div>
        <p style="text-align:center;margin-top:28px;font-family:'Outfit',sans-serif;font-size:14px">
            <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>" style="color:#e8724a;font-weight:600">← Retour au choix du type de devis</a>
        </p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
