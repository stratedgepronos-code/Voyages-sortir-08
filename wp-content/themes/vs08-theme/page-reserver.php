<?php
/**
 * Template Name: Page Réserver
 */
get_header(); ?>

<style>
.rv-hero{position:relative;min-height:38vh;display:flex;align-items:center;background:linear-gradient(135deg,#0f2424 0%,#1a3a3a 60%,rgba(89,183,183,.2) 100%);padding-top:80px}
.rv-hero-content{max-width:1400px;margin:0 auto;padding:60px 80px;display:flex;justify-content:space-between;align-items:center;width:100%;gap:40px}
.rv-hero-left h1{font-size:clamp(32px,4vw,52px);color:#fff;font-family:'Playfair Display',serif;line-height:1.15;margin-bottom:14px}
.rv-hero-left h1 em{color:#7ecece;font-style:italic}
.rv-hero-left p{color:rgba(255,255,255,.6);font-size:16px;font-family:'Outfit',sans-serif;line-height:1.7;max-width:460px}
.rv-hero-trust{display:flex;flex-direction:column;gap:12px;flex-shrink:0}
.rv-trust-item{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:14px 20px;min-width:260px}
.rv-trust-icon{font-size:22px;flex-shrink:0}
.rv-trust-text strong{display:block;color:#fff;font-size:13px;font-family:'Outfit',sans-serif;font-weight:700}
.rv-trust-text span{color:rgba(255,255,255,.5);font-size:12px;font-family:'Outfit',sans-serif}

/* PAGE BODY */
.rv-page{background:#f9f6f0;padding:60px 0 80px}
.rv-page-inner{max-width:1100px;margin:0 auto;padding:0 40px}

/* STEPPER */
.rv-stepper{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:50px}
.rv-step{display:flex;align-items:center;gap:12px;position:relative}
.rv-step-num{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;font-family:'Outfit',sans-serif;transition:all .3s;flex-shrink:0;border:2px solid #d1d5db;background:#fff;color:#9ca3af}
.rv-step-label{font-size:13px;font-weight:600;color:#9ca3af;font-family:'Outfit',sans-serif;white-space:nowrap}
.rv-step.active .rv-step-num{background:#59b7b7;border-color:#59b7b7;color:#fff;box-shadow:0 0 0 4px rgba(89,183,183,.2)}
.rv-step.active .rv-step-label{color:#0f2424}
.rv-step.done .rv-step-num{background:#2d8a5a;border-color:#2d8a5a;color:#fff}
.rv-step.done .rv-step-label{color:#2d8a5a}
.rv-step-line{width:80px;height:2px;background:#e5e7eb;margin:0 4px;flex-shrink:0}
.rv-step.done + .rv-step-line,
.rv-step-line.done{background:#2d8a5a}

/* FORM CARD */
.rv-form-card{background:#fff;border-radius:24px;padding:44px;box-shadow:0 8px 40px rgba(0,0,0,.08)}
.rv-step-content{display:none}
.rv-step-content.active{display:block}
.rv-step-title{font-size:26px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:6px}
.rv-step-subtitle{font-size:14px;color:#6b7280;font-family:'Outfit',sans-serif;margin-bottom:32px}

/* GRILLE SÉJOURS */
.rv-sejours-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
.rv-sejour-option{border:2px solid #f0f2f4;border-radius:16px;overflow:hidden;cursor:pointer;transition:all .25s;position:relative}
.rv-sejour-option:hover{border-color:#59b7b7;transform:translateY(-3px);box-shadow:0 10px 30px rgba(0,0,0,.1)}
.rv-sejour-option.selected{border-color:#59b7b7;box-shadow:0 0 0 3px rgba(89,183,183,.2)}
.rv-sejour-option.selected::after{content:'✓';position:absolute;top:10px;right:10px;width:26px;height:26px;background:#59b7b7;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;line-height:26px;text-align:center}
.rv-sejour-img{height:120px;overflow:hidden}
.rv-sejour-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.rv-sejour-option:hover .rv-sejour-img img{transform:scale(1.06)}
.rv-sejour-info{padding:14px}
.rv-sejour-dest{font-size:10px;color:#59b7b7;font-weight:700;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif;margin-bottom:4px}
.rv-sejour-name{font-size:14px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;line-height:1.3;margin-bottom:6px}
.rv-sejour-price{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:#3d9a9a}
.rv-sejour-price small{font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif;font-weight:400}

/* FORM FIELDS */
.rv-fields-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.rv-fields-grid.cols-3{grid-template-columns:repeat(3,1fr)}
.rv-field{display:flex;flex-direction:column}
.rv-field.full{grid-column:span 2}
.rv-field label{font-size:11px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:1px;margin-bottom:7px;font-family:'Outfit',sans-serif}
.rv-field input,.rv-field select,.rv-field textarea{border:1.5px solid #e5e7eb;border-radius:12px;padding:13px 16px;font-size:14px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fafafa;outline:none;transition:all .2s;-webkit-appearance:none;resize:vertical}
.rv-field input:focus,.rv-field select:focus,.rv-field textarea:focus{border-color:#59b7b7;background:#fff;box-shadow:0 0 0 3px rgba(89,183,183,.1)}
.rv-field textarea{min-height:100px}

/* OPTIONS */
.rv-options-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:24px}
.rv-option-item{border:1.5px solid #e5e7eb;border-radius:14px;padding:16px;cursor:pointer;transition:all .2s;display:flex;align-items:flex-start;gap:12px}
.rv-option-item:hover{border-color:#59b7b7}
.rv-option-item.selected{border-color:#59b7b7;background:#edf8f8}
.rv-option-checkbox{width:20px;height:20px;border-radius:6px;border:2px solid #d1d5db;flex-shrink:0;margin-top:2px;display:flex;align-items:center;justify-content:center;transition:all .2s}
.rv-option-item.selected .rv-option-checkbox{background:#59b7b7;border-color:#59b7b7;color:#fff}
.rv-option-icon{font-size:20px;flex-shrink:0}
.rv-option-name{font-size:14px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;margin-bottom:2px}
.rv-option-desc{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif}
.rv-option-price{font-size:12px;font-weight:700;color:#3d9a9a;font-family:'Outfit',sans-serif;margin-top:4px}

/* RÉCAP */
.rv-recap{background:#f9f6f0;border-radius:18px;padding:28px;margin-bottom:24px}
.rv-recap-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#59b7b7;font-family:'Outfit',sans-serif;margin-bottom:18px}
.rv-recap-row{display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:1px solid #ede9e0;font-family:'Outfit',sans-serif}
.rv-recap-row:last-child{border-bottom:none}
.rv-recap-lbl{font-size:13px;color:#6b7280}
.rv-recap-val{font-size:13px;font-weight:600;color:#0f2424;text-align:right;max-width:60%}
.rv-recap-total{display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding-top:16px;border-top:2px solid #3d9a9a}
.rv-recap-total-lbl{font-size:15px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.rv-recap-total-val{font-family:'Playfair Display',serif;font-size:32px;font-weight:700;color:#3d9a9a}

.rv-cgu{display:flex;align-items:flex-start;gap:12px;margin-bottom:24px;cursor:pointer}
.rv-cgu input[type=checkbox]{width:18px;height:18px;flex-shrink:0;margin-top:2px;accent-color:#59b7b7;cursor:pointer}
.rv-cgu-text{font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;line-height:1.6}
.rv-cgu-text a{color:#59b7b7}

/* NAVIGATION BOUTONS */
.rv-form-nav{display:flex;justify-content:space-between;align-items:center;margin-top:32px;padding-top:24px;border-top:1px solid #f0f2f4}
.rv-btn-prev{background:none;border:1.5px solid #e5e7eb;color:#6b7280;padding:14px 28px;border-radius:100px;font-size:14px;font-weight:600;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .2s}
.rv-btn-prev:hover{border-color:#59b7b7;color:#3d9a9a}
.rv-btn-next{background:#59b7b7;color:#fff;border:none;padding:14px 36px;border-radius:100px;font-size:15px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .3s;box-shadow:0 6px 20px rgba(89,183,183,.35)}
.rv-btn-next:hover{background:#3d9a9a;transform:translateY(-2px)}
.rv-btn-submit{background:#e8724a;color:#fff;border:none;padding:16px 40px;border-radius:100px;font-size:16px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .3s;box-shadow:0 8px 25px rgba(232,114,74,.4)}
.rv-btn-submit:hover{background:#d4603c;transform:translateY(-2px)}

/* SUCCESS */
.rv-success{text-align:center;padding:40px 20px;display:none}
.rv-success-icon{font-size:64px;margin-bottom:24px}
.rv-success h2{font-size:32px;font-family:'Playfair Display',serif;color:#0f2424;margin-bottom:12px}
.rv-success p{font-size:16px;color:#6b7280;font-family:'Outfit',sans-serif;max-width:500px;margin:0 auto 28px}
.rv-success-ref{background:#edf8f8;border-radius:14px;padding:18px 28px;display:inline-block;font-family:'Outfit',sans-serif}
.rv-success-ref strong{color:#3d9a9a;font-size:18px}

/* SIDEBAR CONTACT */
.rv-contact-sidebar{background:#0f2424;border-radius:20px;padding:28px;margin-top:28px}
.rv-contact-sidebar h4{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#7ecece;margin-bottom:18px;font-family:'Outfit',sans-serif}
.rv-contact-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:16px}
.rv-contact-item:last-child{margin-bottom:0}
.rv-contact-item-icon{font-size:18px;flex-shrink:0;margin-top:2px}
.rv-contact-item-text strong{display:block;color:#fff;font-size:14px;font-family:'Outfit',sans-serif}
.rv-contact-item-text span{color:rgba(255,255,255,.5);font-size:12px;font-family:'Outfit',sans-serif;line-height:1.6}

@media(max-width:900px){
    .rv-hero-content{flex-direction:column;padding:50px 24px;gap:24px}
    .rv-hero-trust{flex-direction:row;flex-wrap:wrap}
    .rv-trust-item{min-width:auto;flex:1}
    .rv-page-inner{padding:0 20px}
    .rv-sejours-grid{grid-template-columns:repeat(2,1fr)}
    .rv-fields-grid{grid-template-columns:1fr}
    .rv-field.full{grid-column:span 1}
    .rv-options-grid{grid-template-columns:1fr}
    .rv-stepper{gap:0;overflow-x:auto;padding-bottom:8px}
    .rv-step-line{width:40px}
    .rv-step-label{display:none}
    .rv-form-card{padding:24px 20px}
}
</style>

<!-- HERO -->
<section class="rv-hero">
    <div class="rv-hero-content">
        <div class="rv-hero-left">
            <h1>Réservez votre<br><em>séjour golf</em></h1>
            <p>Remplissez le formulaire en 3 étapes. Votre conseiller vous confirme la disponibilité et vous envoie la facture sous 24h.</p>
        </div>
        <div class="rv-hero-trust">
            <div class="rv-trust-item">
                <div class="rv-trust-icon">🔒</div>
                <div class="rv-trust-text"><strong>Paiement sécurisé</strong><span>Acompte 30% · Solde à J-30</span></div>
            </div>
            <div class="rv-trust-item">
                <div class="rv-trust-icon">🔄</div>
                <div class="rv-trust-text"><strong>Annulation flexible</strong><span>Remboursement jusqu'à J-30</span></div>
            </div>
            <div class="rv-trust-item">
                <div class="rv-trust-icon">👤</div>
                <div class="rv-trust-text"><strong>Conseiller dédié</strong><span>Réponse sous 2h</span></div>
            </div>
        </div>
    </div>
</section>

<!-- FORMULAIRE -->
<section class="rv-page">
    <div class="rv-page-inner">

        <!-- STEPPER -->
        <div class="rv-stepper">
            <div class="rv-step active" id="step-indicator-1">
                <div class="rv-step-num">1</div>
                <div class="rv-step-label">Votre séjour</div>
            </div>
            <div class="rv-step-line" id="line-1"></div>
            <div class="rv-step" id="step-indicator-2">
                <div class="rv-step-num">2</div>
                <div class="rv-step-label">Vos infos</div>
            </div>
            <div class="rv-step-line" id="line-2"></div>
            <div class="rv-step" id="step-indicator-3">
                <div class="rv-step-num">3</div>
                <div class="rv-step-label">Confirmation</div>
            </div>
        </div>

        <div class="rv-form-card">

            <!-- ÉTAPE 1 — CHOIX DU SÉJOUR -->
            <div class="rv-step-content active" id="step-1">
                <h2 class="rv-step-title">Quel séjour vous intéresse ?</h2>
                <p class="rv-step-subtitle">Sélectionnez un séjour ou choisissez "Sur mesure" pour nous décrire votre projet.</p>

                <div class="rv-sejours-grid">
                    <?php
                    $sejours = [
                        ['slug'=>'portugal-algarve','flag'=>'🇵🇹','dest'=>'Portugal — Algarve','name'=>'Golf & Soleil Costa Vicentina','prix'=>'1 290€','img'=>'https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=500&q=70'],
                        ['slug'=>'maroc-agadir','flag'=>'🇲🇦','dest'=>'Maroc — Agadir','name'=>'Golf Royal & Riad Marocain','prix'=>'890€','img'=>'https://images.unsplash.com/photo-1553603227-2358aabe821e?w=500&q=70'],
                        ['slug'=>'espagne-marbella','flag'=>'🇪🇸','dest'=>'Espagne — Marbella','name'=>'Costa del Sol Luxury Golf','prix'=>'1 590€','img'=>'https://images.unsplash.com/photo-1593111774240-d529f12cf4bb?w=500&q=70'],
                        ['slug'=>'irlande-kerry','flag'=>'🇮🇪','dest'=>'Irlande — Kerry','name'=>'Wild Atlantic Golf Links','prix'=>'1 190€','img'=>'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=500&q=70'],
                        ['slug'=>'thailande-phuket','flag'=>'🇹🇭','dest'=>'Thaïlande — Phuket','name'=>'Golf & Temples Phuket','prix'=>'2 190€','img'=>'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=500&q=70'],
                        ['slug'=>'sur-mesure','flag'=>'✨','dest'=>'Votre projet','name'=>'Séjour sur mesure','prix'=>'Devis gratuit','img'=>'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?w=500&q=70'],
                    ];
                    foreach ($sejours as $s) : ?>
                    <div class="rv-sejour-option" onclick="rvSelectSejour(this, '<?php echo esc_js($s['name']); ?>', '<?php echo esc_js($s['prix']); ?>')">
                        <div class="rv-sejour-img"><img src="<?php echo esc_url($s['img']); ?>" alt="<?php echo esc_attr($s['name']); ?>" loading="lazy"></div>
                        <div class="rv-sejour-info">
                            <p class="rv-sejour-dest"><?php echo esc_html($s['flag'] . ' ' . $s['dest']); ?></p>
                            <p class="rv-sejour-name"><?php echo esc_html($s['name']); ?></p>
                            <p class="rv-sejour-price"><?php echo esc_html($s['prix']); ?> <small>/pers.</small></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="rv-fields-grid">
                    <div class="rv-field">
                        <label>Date souhaitée</label>
                        <input type="text" id="rv-date" placeholder="ex: Juin 2025, semaine du 14">
                    </div>
                    <div class="rv-field">
                        <label>Durée souhaitée</label>
                        <select id="rv-duree">
                            <option>7 nuits</option>
                            <option>10 nuits</option>
                            <option>14 nuits</option>
                            <option>Flexible</option>
                        </select>
                    </div>
                    <div class="rv-field">
                        <label>Nombre de joueurs</label>
                        <select id="rv-joueurs">
                            <option>1 joueur</option>
                            <option selected>2 joueurs</option>
                            <option>3 joueurs</option>
                            <option>4 joueurs</option>
                            <option>5 joueurs et +</option>
                        </select>
                    </div>
                    <div class="rv-field">
                        <label>Ville de départ</label>
                        <select id="rv-depart">
                            <option>Paris (CDG / Orly)</option>
                            <option>Lyon (LYS)</option>
                            <option>Lille (LIL)</option>
                            <option>Autre ville</option>
                        </select>
                    </div>
                </div>

                <div class="rv-form-nav">
                    <span></span>
                    <button class="rv-btn-next" onclick="rvGoStep(2)">Étape suivante →</button>
                </div>
            </div>

            <!-- ÉTAPE 2 — INFOS PERSONNELLES + OPTIONS -->
            <div class="rv-step-content" id="step-2">
                <h2 class="rv-step-title">Vos coordonnées</h2>
                <p class="rv-step-subtitle">Ces informations permettront à votre conseiller de vous contacter rapidement.</p>

                <div class="rv-fields-grid">
                    <div class="rv-field">
                        <label>Prénom *</label>
                        <input type="text" id="rv-prenom" placeholder="Jean">
                    </div>
                    <div class="rv-field">
                        <label>Nom *</label>
                        <input type="text" id="rv-nom" placeholder="Dupont">
                    </div>
                    <div class="rv-field">
                        <label>Email *</label>
                        <input type="email" id="rv-email" placeholder="jean.dupont@email.com">
                    </div>
                    <div class="rv-field">
                        <label>Téléphone *</label>
                        <input type="tel" id="rv-tel" placeholder="06 XX XX XX XX">
                    </div>
                    <div class="rv-field">
                        <label>Index golf</label>
                        <input type="text" id="rv-index" placeholder="ex: 12.4 (optionnel)">
                    </div>
                    <div class="rv-field">
                        <label>Comment nous avez-vous connu ?</label>
                        <select id="rv-source">
                            <option>Google</option>
                            <option>Bouche à oreille</option>
                            <option>Réseaux sociaux</option>
                            <option>Club de golf</option>
                            <option>Autre</option>
                        </select>
                    </div>
                    <div class="rv-field full">
                        <label>Demandes particulières</label>
                        <textarea id="rv-message" placeholder="Chambre accessible, anniversaire, groupe de niveau différent, demande de cours…"></textarea>
                    </div>
                </div>

                <h3 style="font-family:'Playfair Display',serif;font-size:19px;color:#0f2424;margin-bottom:8px;">Options supplémentaires</h3>
                <p style="font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;margin-bottom:18px;">Sélectionnez les options souhaitées (indicatif, sera confirmé par votre conseiller)</p>

                <div class="rv-options-grid">
                    <div class="rv-option-item" onclick="this.classList.toggle('selected')">
                        <div class="rv-option-checkbox">✓</div>
                        <div class="rv-option-icon">🛡️</div>
                        <div><p class="rv-option-name">Assurance annulation</p><p class="rv-option-desc">Remboursement intégral en cas d'imprévu</p><p class="rv-option-price">~35€/pers.</p></div>
                    </div>
                    <div class="rv-option-item" onclick="this.classList.toggle('selected')">
                        <div class="rv-option-checkbox">✓</div>
                        <div class="rv-option-icon">🏌️</div>
                        <div><p class="rv-option-name">Location de clubs</p><p class="rv-option-desc">Set complet disponible sur place</p><p class="rv-option-price">~40€/sem.</p></div>
                    </div>
                    <div class="rv-option-item" onclick="this.classList.toggle('selected')">
                        <div class="rv-option-checkbox">✓</div>
                        <div class="rv-option-icon">🎓</div>
                        <div><p class="rv-option-name">Cours de golf</p><p class="rv-option-desc">1h avec un pro certifié sur place</p><p class="rv-option-price">~60€/heure</p></div>
                    </div>
                    <div class="rv-option-item" onclick="this.classList.toggle('selected')">
                        <div class="rv-option-checkbox">✓</div>
                        <div class="rv-option-icon">🍽️</div>
                        <div><p class="rv-option-name">Demi-pension</p><p class="rv-option-desc">Dîner inclus chaque soir à l'hôtel</p><p class="rv-option-price">~35€/pers./nuit</p></div>
                    </div>
                </div>

                <div class="rv-form-nav">
                    <button class="rv-btn-prev" onclick="rvGoStep(1)">← Retour</button>
                    <button class="rv-btn-next" onclick="rvGoStep(3)">Vérifier ma demande →</button>
                </div>
            </div>

            <!-- ÉTAPE 3 — RÉCAPITULATIF + CONFIRMATION -->
            <div class="rv-step-content" id="step-3">
                <h2 class="rv-step-title">Récapitulatif de votre demande</h2>
                <p class="rv-step-subtitle">Vérifiez les informations avant d'envoyer. Votre conseiller vous contacte sous 2h.</p>

                <div class="rv-recap">
                    <p class="rv-recap-title">Votre séjour</p>
                    <div class="rv-recap-row"><span class="rv-recap-lbl">Séjour sélectionné</span><span class="rv-recap-val" id="recap-sejour">—</span></div>
                    <div class="rv-recap-row"><span class="rv-recap-lbl">Date souhaitée</span><span class="rv-recap-val" id="recap-date">—</span></div>
                    <div class="rv-recap-row"><span class="rv-recap-lbl">Durée</span><span class="rv-recap-val" id="recap-duree">—</span></div>
                    <div class="rv-recap-row"><span class="rv-recap-lbl">Joueurs</span><span class="rv-recap-val" id="recap-joueurs">—</span></div>
                    <div class="rv-recap-row"><span class="rv-recap-lbl">Départ depuis</span><span class="rv-recap-val" id="recap-depart">—</span></div>
                </div>

                <div class="rv-recap">
                    <p class="rv-recap-title">Vos coordonnées</p>
                    <div class="rv-recap-row"><span class="rv-recap-lbl">Nom</span><span class="rv-recap-val" id="recap-nom">—</span></div>
                    <div class="rv-recap-row"><span class="rv-recap-lbl">Email</span><span class="rv-recap-val" id="recap-email">—</span></div>
                    <div class="rv-recap-row"><span class="rv-recap-lbl">Téléphone</span><span class="rv-recap-val" id="recap-tel">—</span></div>
                </div>

                <label class="rv-cgu">
                    <input type="checkbox" id="rv-cgu">
                    <span class="rv-cgu-text">J'accepte les <a href="<?php echo home_url('/conditions'); ?>">conditions générales de vente</a> et la <a href="<?php echo home_url('/rgpd'); ?>">politique de confidentialité</a> de Voyages Sortir 08.</span>
                </label>

                <div class="rv-form-nav">
                    <button class="rv-btn-prev" onclick="rvGoStep(2)">← Retour</button>
                    <button class="rv-btn-submit" onclick="rvSubmit()">🚀 Envoyer ma demande</button>
                </div>
            </div>

            <!-- SUCCESS -->
            <div class="rv-success" id="rv-success">
                <div class="rv-success-icon">🎉</div>
                <h2>Demande envoyée !</h2>
                <p>Votre conseiller vous contacte sous <strong>2 heures</strong> pour confirmer les disponibilités et vous envoyer un devis personnalisé.</p>
                <div class="rv-success-ref">
                    Référence : <strong id="rv-ref">VS08-2025-???</strong>
                </div>
                <p style="margin-top:20px;font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;">Un email de confirmation a été envoyé à <strong id="rv-success-email"></strong></p>
                <div style="margin-top:28px;display:flex;gap:12px;justify-content:center;">
                    <a href="<?php echo home_url('/golf'); ?>" style="background:#edf8f8;color:#3d9a9a;padding:12px 24px;border-radius:100px;font-weight:700;font-size:14px;font-family:'Outfit',sans-serif;text-decoration:none;">← Voir d'autres séjours</a>
                    <a href="<?php echo home_url('/'); ?>" style="background:#0f2424;color:#fff;padding:12px 24px;border-radius:100px;font-weight:700;font-size:14px;font-family:'Outfit',sans-serif;text-decoration:none;">Retour à l'accueil</a>
                </div>
            </div>

        </div><!-- /form card -->

        <!-- CONTACT SIDEBAR -->
        <div class="rv-contact-sidebar">
            <h4>Besoin d'aide pour réserver ?</h4>
            <div class="rv-contact-item">
                <div class="rv-contact-item-icon">📞</div>
                <div class="rv-contact-item-text">
                    <strong>03 26 65 28 63</strong>
                    <span>Lun–Ven : 09h–12h / 14h–18h30<br>Samedi : 09h–12h / 14h–18h00</span>
                </div>
            </div>
            <div class="rv-contact-item">
                <div class="rv-contact-item-icon">✉️</div>
                <div class="rv-contact-item-text">
                    <strong>resa@voyagessortir08.fr</strong>
                    <span>Réponse sous 2h en jours ouvrés</span>
                </div>
            </div>
            <div class="rv-contact-item">
                <div class="rv-contact-item-icon">💬</div>
                <div class="rv-contact-item-text">
                    <strong>WhatsApp disponible</strong>
                    <span>Envoyez-nous un message direct</span>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
var rvData = { sejour: '', prix: '' };

function rvSelectSejour(el, name, prix) {
    document.querySelectorAll('.rv-sejour-option').forEach(function(o){ o.classList.remove('selected'); });
    el.classList.add('selected');
    rvData.sejour = name;
    rvData.prix   = prix;
}

function rvGoStep(n) {
    // Validation étape 1
    if (n === 2 && !rvData.sejour) {
        alert('Veuillez sélectionner un séjour.');
        return;
    }
    // Validation étape 3
    if (n === 3) {
        var prenom = document.getElementById('rv-prenom').value;
        var nom    = document.getElementById('rv-nom').value;
        var email  = document.getElementById('rv-email').value;
        var tel    = document.getElementById('rv-tel').value;
        if (!prenom || !nom || !email || !tel) {
            alert('Veuillez remplir tous les champs obligatoires (*).');
            return;
        }
        // Remplir le récap
        document.getElementById('recap-sejour').textContent  = rvData.sejour;
        document.getElementById('recap-date').textContent    = document.getElementById('rv-date').value || '—';
        document.getElementById('recap-duree').textContent   = document.getElementById('rv-duree').value;
        document.getElementById('recap-joueurs').textContent = document.getElementById('rv-joueurs').value;
        document.getElementById('recap-depart').textContent  = document.getElementById('rv-depart').value;
        document.getElementById('recap-nom').textContent     = prenom + ' ' + nom;
        document.getElementById('recap-email').textContent   = email;
        document.getElementById('recap-tel').textContent     = tel;
    }

    // Mise à jour du stepper
    for (var i = 1; i <= 3; i++) {
        var ind = document.getElementById('step-indicator-' + i);
        ind.classList.remove('active','done');
        if (i < n)       ind.classList.add('done');
        else if (i === n) ind.classList.add('active');

        if (i < 3) {
            var line = document.getElementById('line-' + i);
            line.classList.toggle('done', i < n);
        }
    }

    // Afficher le bon step
    document.querySelectorAll('.rv-step-content').forEach(function(s){ s.classList.remove('active'); });
    document.getElementById('step-' + n).classList.add('active');

    window.scrollTo({ top: document.querySelector('.rv-page').offsetTop - 20, behavior: 'smooth' });
}

function rvSubmit() {
    if (!document.getElementById('rv-cgu').checked) {
        alert('Veuillez accepter les conditions générales.');
        return;
    }
    // Masquer le formulaire et afficher le succès
    document.querySelectorAll('.rv-step-content').forEach(function(s){ s.style.display='none'; });
    document.querySelector('.rv-stepper').style.display = 'none';
    var success = document.getElementById('rv-success');
    success.style.display = 'block';
    // Référence aléatoire
    var ref = 'VS08-2025-' + Math.floor(1000 + Math.random() * 9000);
    document.getElementById('rv-ref').textContent = ref;
    document.getElementById('rv-success-email').textContent = document.getElementById('rv-email').value;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<?php get_footer(); ?>
