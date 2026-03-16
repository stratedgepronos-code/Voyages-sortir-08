<?php
/**
 * VS08 Voyages — Module Hôtel complet
 * Meta box dédiée avec tous les champs + rendu frontend
 */
class VS08V_HotelBox {

    public static function register() {
        add_meta_box(
            'vs08v_hotel',
            '🏨 Hôtel & Hébergement',
            [__CLASS__, 'render'],
            'vs08_voyage',
            'normal',
            'high'
        );
    }

    public static function render($post) {
        $m = VS08V_MetaBoxes::get($post->ID);
        $h = $m['hotel'] ?? [];

        // Équipements disponibles avec icônes
        $equip_list = [
            'piscine_ext'   => ['🏊', 'Piscine extérieure'],
            'piscine_int'   => ['🏊', 'Piscine intérieure'],
            'piscine_chauffee' => ['♨️', 'Piscine chauffée'],
            'spa'           => ['💆', 'Spa / Thalasso'],
            'hammam'        => ['🧖', 'Hammam'],
            'fitness'       => ['💪', 'Salle de fitness'],
            'restaurant'    => ['🍽️', 'Restaurant'],
            'bar'           => ['🍷', 'Bar / Lounge'],
            'room_service'  => ['🛎️', 'Room service'],
            'wifi'          => ['📶', 'Wi-Fi gratuit'],
            'clim'          => ['❄️', 'Climatisation'],
            'terrasse'      => ['🌅', 'Terrasse / Balcon'],
            'vue_golf'      => ['⛳', 'Vue sur le parcours'],
            'vue_mer'       => ['🌊', 'Vue mer / lac'],
            'kids_club'     => ['👶', 'Kids Club'],
            'tennis'        => ['🎾', 'Court de tennis'],
            'beach'         => ['🏖️', 'Accès plage'],
            'navette'       => ['🚐', 'Navette aéroport'],
            'navette_centre' => ['🚌', 'Navette centre-ville'],
            'parking'       => ['🅿️', 'Parking gratuit'],
            'velo'          => ['🚴', 'Location vélos'],
            'boutique'      => ['🛍️', 'Boutique / Pro-shop'],
            'seminaire'     => ['📊', 'Salles de conférence'],
        ];

        $checked = $h['equipements'] ?? [];
        $nonce = wp_create_nonce('vs08v_scan_hotel');
        ?>

        <style>
        /* ====== SCANNER IA ====== */
        .vs08h-scanner-wrap{background:linear-gradient(135deg,#0f2424 0%,#1a4a4a 60%,#0f3535 100%);border-radius:14px;padding:22px 24px;margin-bottom:24px;position:relative;overflow:hidden}
        .vs08h-scanner-wrap::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(89,183,183,.25) 0%,transparent 70%);pointer-events:none}
        .vs08h-scanner-title{display:flex;align-items:center;gap:10px;margin:0 0 6px}
        .vs08h-scanner-title span{font-size:22px}
        .vs08h-scanner-title h3{font-size:16px;font-weight:700;color:#fff;margin:0;font-family:inherit}
        .vs08h-scanner-subtitle{font-size:12px;color:rgba(255,255,255,.65);margin:0 0 16px;line-height:1.5}
        .vs08h-scanner-row{display:flex;gap:10px;align-items:center}
        .vs08h-scanner-input{flex:1;background:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:10px;padding:11px 16px;font-size:14px;color:#1a3a3a!important;font-family:inherit;transition:all .2s;outline:none}
        .vs08h-scanner-input::placeholder{color:#6b7280}
        .vs08h-scanner-input:focus{border-color:#59b7b7;background:#fff;color:#1a3a3a!important}
        .vs08h-scanner-btn{background:#59b7b7;color:#fff;border:none;border-radius:10px;padding:11px 20px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;transition:all .2s;font-family:inherit;display:flex;align-items:center;gap:7px}
        .vs08h-scanner-btn:hover{background:#3d9a9a;transform:translateY(-1px)}
        .vs08h-scanner-btn:disabled{background:#3d7070;cursor:not-allowed;transform:none}
        .vs08h-scanner-status{margin-top:12px;font-size:12px;border-radius:8px;padding:10px 14px;display:none}
        .vs08h-scanner-status.loading{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.1);color:rgba(255,255,255,.85)}
        .vs08h-scanner-status.success{display:flex;align-items:center;gap:10px;background:rgba(89,183,183,.25);color:#9ee8e8}
        .vs08h-scanner-status.error{display:flex;align-items:center;gap:10px;background:rgba(239,68,68,.2);color:#fca5a5}
        .vs08h-spinner{width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#59b7b7;border-radius:50%;animation:vs08spin .7s linear infinite;flex-shrink:0}
        @keyframes vs08spin{to{transform:rotate(360deg)}}
        .vs08h-tab{background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);border:1.5px solid rgba(255,255,255,.15);border-radius:8px;padding:7px 16px;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;font-family:inherit}
        .vs08h-tab:hover{background:rgba(255,255,255,.2)}
        .vs08h-tab-active{background:#59b7b7!important;color:#fff!important;border-color:#59b7b7!important}
        .vs08h-pdf-drop{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:rgba(255,255,255,.07);border:2px dashed rgba(255,255,255,.25);border-radius:12px;padding:24px;cursor:pointer;transition:all .2s;text-align:center}
        .vs08h-pdf-drop:hover{background:rgba(255,255,255,.12);border-color:#59b7b7}
        .vs08h-pdf-drop.has-file{border-color:#59b7b7;background:rgba(89,183,183,.15)}
        .vs08h-pdf-icon{font-size:28px}
        #vs08h-pdf-txt{font-size:12px;color:rgba(255,255,255,.7)}
        .vs08h-field-highlight{animation:vs08highlight 1.5s ease}
        @keyframes vs08highlight{0%{background:#edf8f8;border-color:#59b7b7}100%{background:#fafafa;border-color:#dde1e7}}
        </style>

        <!-- 🤖 SCANNER IA -->
        <?php
        $claude_ok = defined('VS08V_CLAUDE_KEY') && is_string(VS08V_CLAUDE_KEY) && strlen(trim(VS08V_CLAUDE_KEY)) > 10;
        $config_exists = file_exists(defined('VS08V_PATH') ? VS08V_PATH . 'config.cfg' : '');
        ?>
        <div class="vs08h-scanner-wrap">
            <div class="vs08h-scanner-title">
                <span>🤖</span>
                <h3>Remplissage automatique par IA</h3>
            </div>
            <p class="vs08h-scanner-subtitle">Entrez le nom de l'hôtel : Claude (Sonnet 4, recherche web) remplit les champs automatiquement.</p>
            <p style="margin:-6px 0 10px;font-size:11px;color:rgba(255,255,255,.7);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <?php if ($claude_ok): ?>
                <span style="color:#86efac">✅ Clé Claude chargée</span> — les requêtes partent vers l’API (crédits consommés sur votre compte Anthropic).
                <?php else: ?>
                <span style="color:#fca5a5">❌ Clé Claude non chargée sur ce serveur</span>
                <?php if (!$config_exists): ?>
                    — Fichier <code>config.cfg</code> absent dans <code>wp-content/plugins/vs08-voyages/</code>. Uploadez-le par FTP avec la ligne <code>CLAUDE_API_KEY=sk-ant-...</code>.
                <?php else: ?>
                    — Le fichier <code>config.cfg</code> existe mais <code>CLAUDE_API_KEY</code> est absent ou vide. Ajoutez <code>CLAUDE_API_KEY=votre_clé</code> dans config.cfg sur le serveur.
                <?php endif; ?>
                <?php endif; ?>
            </p>
            <!-- Recherche par nom -->
            <div id="vs08h-panel-by-name" class="vs08h-scanner-row" style="flex-wrap:wrap;gap:10px;align-items:flex-end">
                <div style="flex:1;min-width:180px">
                    <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.8);margin-bottom:4px">Nom de l'hôtel *</label>
                    <input type="text" id="vs08h-hotel-name" class="vs08h-scanner-input" placeholder="Ex: Kenzi Club Agdal Resort" value="<?php echo esc_attr($h['nom']??''); ?>">
                </div>
                <div style="flex:1;min-width:140px">
                    <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.8);margin-bottom:4px">Ville / Pays (optionnel)</label>
                    <input type="text" id="vs08h-hotel-location" class="vs08h-scanner-input" placeholder="Ex: Marrakech, Maroc">
                </div>
                <button type="button" id="vs08h-by-name-btn" class="vs08h-scanner-btn">
                    <span id="vs08h-by-name-icon">🔍</span>
                    <span id="vs08h-by-name-text">Rechercher et remplir avec l'IA</span>
                </button>
            </div>
            <p style="margin:14px 0 0;font-size:12px;color:rgba(255,255,255,.5)">Ou importez une fiche PDF :</p>
            <div id="vs08h-panel-pdf" style="margin-top:8px">
                <label id="vs08h-pdf-label" class="vs08h-pdf-drop" for="vs08h-pdf-input">
                    <span class="vs08h-pdf-icon">📄</span>
                    <span id="vs08h-pdf-txt">Glissez votre PDF ici ou <u>cliquez pour choisir</u></span>
                    <input type="file" id="vs08h-pdf-input" accept=".pdf" style="display:none">
                </label>
                <button type="button" id="vs08h-pdf-btn" class="vs08h-scanner-btn" style="margin-top:10px;display:none">
                    <span id="vs08h-pdf-btn-icon">🤖</span>
                    <span id="vs08h-pdf-btn-text">Analyser le PDF</span>
                </button>
            </div>
            <div id="vs08h-status" class="vs08h-scanner-status" style="display:none">
                <div class="vs08h-spinner" id="vs08h-spinner" style="display:none"></div>
                <span id="vs08h-status-text"></span>
            </div>
            <!-- Barre de progression -->
            <div id="vs08h-progress-wrap" style="display:none;margin-top:12px">
                <div style="background:rgba(255,255,255,.1);border-radius:100px;height:6px;overflow:hidden">
                    <div id="vs08h-progress-bar" style="height:100%;background:linear-gradient(90deg,#59b7b7,#9ee8e8);border-radius:100px;width:0%;transition:width .4s ease"></div>
                </div>
                <div id="vs08h-progress-steps" style="display:flex;justify-content:space-between;margin-top:8px">
                    <span class="vs08h-step" id="step1" style="font-size:11px;color:rgba(255,255,255,.4)">🔍 Recherche web</span>
                    <span class="vs08h-step" id="step2" style="font-size:11px;color:rgba(255,255,255,.4)">📄 Analyse des sources</span>
                    <span class="vs08h-step" id="step3" style="font-size:11px;color:rgba(255,255,255,.4)">🤖 Extraction IA</span>
                    <span class="vs08h-step" id="step4" style="font-size:11px;color:rgba(255,255,255,.4)">✍️ Remplissage</span>
                </div>
            </div>
        </div>

        <style>
        .vs08h-section{margin-bottom:20px}
        .vs08h-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:#59b7b7;border-bottom:2px solid #edf8f8;padding-bottom:6px;margin:0 0 12px}
        .vs08h-equip-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:6px}
        .vs08h-equip-item{display:flex;align-items:center;gap:7px;background:#fafbfc;border:1.5px solid #edf0f4;border-radius:8px;padding:8px 10px;cursor:pointer;transition:all .2s;user-select:none}
        .vs08h-equip-item:hover{border-color:#59b7b7;background:#edf8f8}
        .vs08h-equip-item input[type=checkbox]{position:absolute;opacity:0;pointer-events:none;width:0;height:0}
        .vs08h-equip-item:has(input:checked){border-color:#59b7b7!important;background:#edf8f8!important}
        .vs08h-equip-icon{font-size:16px;flex-shrink:0}
        .vs08h-equip-label{font-size:12px;color:#1a3a3a;font-family:inherit;line-height:1.3}
        .vs08h-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;align-items:flex-start}
        .vs08h-field{display:flex;flex-direction:column;flex:1 1 140px;min-width:0}
        .vs08h-field label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:4px}
        .vs08h-field input,.vs08h-field select,.vs08h-field textarea{width:100%;box-sizing:border-box;border:1.5px solid #dde1e7;border-radius:6px;padding:8px 10px;font-size:13px;font-family:inherit;background:#fafafa;transition:border-color .2s}
        .vs08h-field input:focus,.vs08h-field select:focus,.vs08h-field textarea:focus{border-color:#59b7b7;outline:none;background:#fff}
        .vs08h-field-full{flex:1 1 100%}
        .vs08h-field-2{flex:2 1 240px}
        .vs08h-types-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
        .vs08h-type-row{background:#fafbfc;border:1.5px solid #edf0f4;border-radius:8px;padding:10px}
        .vs08h-type-row label.title{font-size:11px;font-weight:700;color:#1a3a3a;display:block;margin-bottom:8px}
        </style>

        <!-- ① INFOS GÉNÉRALES HÔTEL -->
        <div class="vs08h-section">
            <p class="vs08h-section-title">📋 Informations générales</p>
            <div class="vs08h-row">
                <div class="vs08h-field vs08h-field-2">
                    <label>Nom de l'hôtel</label>
                    <input type="text" name="vs08v[hotel][nom]" value="<?php echo esc_attr($h['nom']??''); ?>" placeholder="Kenzi Club Agdal Resort">
                </div>
                <div class="vs08h-field" style="flex:0 0 100px">
                    <label>Étoiles</label>
                    <select name="vs08v[hotel][etoiles]">
                        <?php for($i=3;$i<=5;$i++): ?>
                        <option value="<?php echo $i;?>" <?php selected($h['etoiles']??5,$i);?>><?php echo $i;?> ★</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="vs08h-field">
                    <label>Label / Certification</label>
                    <select name="vs08v[hotel][label]">
                        <option value="">Aucun</option>
                        <?php foreach(['luxe'=>'🏆 Luxe','eco'=>'🌿 Éco-responsable','boutique'=>'✨ Boutique Hotel','resort'=>'🌴 Resort','golf_resort'=>'⛳ Golf Resort','spa_resort'=>'💆 Spa Resort'] as $v=>$l): ?>
                        <option value="<?php echo $v;?>" <?php selected($h['label']??'',$v);?>><?php echo $l;?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="vs08h-row">
                <div class="vs08h-field">
                    <label>🦉 Note TripAdvisor</label>
                    <input type="number" name="vs08v[hotel][tripadvisor_note]" value="<?php echo esc_attr($h['tripadvisor_note']??''); ?>" placeholder="4.6" step="0.1" min="0" max="5" style="width:100px">
                </div>
                <div class="vs08h-field vs08h-field-2">
                    <label>🔗 Lien TripAdvisor</label>
                    <input type="url" name="vs08v[hotel][tripadvisor_url]" value="<?php echo esc_attr($h['tripadvisor_url']??''); ?>" placeholder="https://www.tripadvisor.fr/Hotel_Review-...">
                </div>
            </div>
            <div class="vs08h-row">
                <div class="vs08h-field vs08h-field-full">
                    <label>Description principale — accroche commerciale</label>
                    <textarea name="vs08v[hotel][desc]" rows="3" placeholder="Niché au cœur des palmeraies de Marrakech, le Kenzi Club Agdal Resort est un havre de luxe à 10 minutes du centre-ville..."><?php echo esc_textarea($h['desc']??''); ?></textarea>
                </div>
            </div>
            <div class="vs08h-row">
                <div class="vs08h-field">
                    <label>Type de pension</label>
                    <select name="vs08v[hotel][pension]">
                        <?php foreach(['bb'=>'🍳 Petit-déjeuner (BB)','dp'=>'🍽️ Demi-pension (DP)','pc'=>'🍽️ Pension complète (PC)','ai'=>'🌟 Tout inclus (AI)'] as $v=>$l): ?>
                        <option value="<?php echo $v;?>" <?php selected($h['pension']??'bb',$v);?>><?php echo $l;?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs08h-field">
                    <label>Type d'établissement</label>
                    <select name="vs08v[hotel][type_etab]">
                        <?php foreach(['hotel'=>'Hôtel','resort'=>'Resort','villa'=>'Villa / Domaine','chalet'=>'Chalet','riad'=>'Riad','chateau'=>'Château'] as $v=>$l): ?>
                        <option value="<?php echo $v;?>" <?php selected($h['type_etab']??'hotel',$v);?>><?php echo $l;?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="vs08h-field">
                    <label>Nb de chambres total</label>
                    <input type="number" name="vs08v[hotel][nb_chambres]" value="<?php echo esc_attr($h['nb_chambres']??''); ?>" placeholder="156">
                </div>
            </div>
        </div>

        <!-- ② ÉQUIPEMENTS (checkboxes visuelles) -->
        <div class="vs08h-section">
            <p class="vs08h-section-title">✅ Équipements & services — cochez ce qui est disponible</p>
            <div class="vs08h-equip-grid" id="vs08h-equip">
            <?php foreach($equip_list as $key => [$icon, $label]): ?>
                <label class="vs08h-equip-item <?php echo in_array($key,$checked)?'checked':''; ?>">
                    <input type="checkbox" name="vs08v[hotel][equipements][]" value="<?php echo $key;?>" <?php checked(in_array($key,$checked)); ?>>
                    <span class="vs08h-equip-icon"><?php echo $icon; ?></span>
                    <span class="vs08h-equip-label"><?php echo $label; ?></span>
                </label>
            <?php endforeach; ?>
            </div>
            <!-- Navette centre-ville : tarif -->
            <?php $nc_checked = in_array('navette_centre', $checked);
                  $nc_tarif   = $h['navette_centre_tarif'] ?? 'gratuite'; ?>
            <div id="vs08h-nc-tarif" style="margin-top:8px;padding:10px 14px;background:#edf8f8;border-radius:8px;border:1.5px solid #b2dfdf;<?php echo $nc_checked?'':'display:none;'; ?>">
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#1a3a3a;margin-right:12px">🚌 Navette centre-ville :</span>
                <label style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;font-size:13px;margin-right:14px">
                    <input type="radio" name="vs08v[hotel][navette_centre_tarif]" value="gratuite" <?php checked($nc_tarif,'gratuite');?> required> 🟢 Gratuite
                </label>
                <label style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;font-size:13px;margin-right:14px">
                    <input type="radio" name="vs08v[hotel][navette_centre_tarif]" value="payante" <?php checked($nc_tarif,'payante');?>> 💶 Payante
                </label>
                <label style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;font-size:13px">
                    <input type="radio" name="vs08v[hotel][navette_centre_tarif]" value="non" <?php checked($nc_tarif,'non');?>> ❌ Non
                </label>
            </div>
            <script>
            (function(){
                var grid = document.getElementById('vs08h-equip');
                var tarif = document.getElementById('vs08h-nc-tarif');
                if(!grid||!tarif) return;
                function sync(){
                    var cb = grid.querySelector('input[value="navette_centre"]');
                    if(cb) tarif.style.display = cb.checked ? '' : 'none';
                }
                grid.addEventListener('change', sync);
                sync();
            })();
            </script>
        </div>

        <!-- ③ TYPES DE CHAMBRES -->
        <div class="vs08h-section">
            <p class="vs08h-section-title">🛏️ Types de chambres disponibles</p>
            <div class="vs08h-types-grid">
                <?php
                $types = [
                    'double'  => ['🛏️', 'Chambre Double',  'double'],
                    'simple'  => ['🛏️', 'Chambre Simple',  'simple'],
                    'triple'  => ['🛌', 'Chambre Triple',  'triple'],
                ];
                foreach($types as $key => [$icon, $name, $field]): ?>
                <div class="vs08h-type-row">
                    <label class="title"><?php echo $icon; ?> <?php echo $name; ?></label>
                    <div class="vs08h-row" style="gap:8px">
                        <div class="vs08h-field">
                            <label>Disponible</label>
                            <select name="vs08v[hotel][chambres][<?php echo $key;?>][dispo]">
                                <option value="1" <?php selected($h['chambres'][$key]['dispo']??'1','1');?>>✅ Oui</option>
                                <option value="0" <?php selected($h['chambres'][$key]['dispo']??'1','0');?>>❌ Non</option>
                            </select>
                        </div>
                        <div class="vs08h-field">
                            <label>Superficie m²</label>
                            <input type="text" name="vs08v[hotel][chambres][<?php echo $key;?>][superficie]" value="<?php echo esc_attr($h['chambres'][$key]['superficie']??''); ?>" placeholder="28">
                        </div>
                    </div>
                    <div class="vs08h-field" style="margin-top:6px">
                        <label>Description courte</label>
                        <input type="text" name="vs08v[hotel][chambres][<?php echo $key;?>][desc]" value="<?php echo esc_attr($h['chambres'][$key]['desc']??''); ?>" placeholder="Vue jardin, balcon privé...">
                    </div>
                    <div class="vs08h-field" style="margin-top:6px">
                        <label>📷 Photo de la chambre</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="hidden" name="vs08v[hotel][chambres][<?php echo $key;?>][photo]"
                                id="vs08h-room-photo-<?php echo $key;?>"
                                value="<?php echo esc_attr($h['chambres'][$key]['photo']??''); ?>">
                            <button type="button" class="button vs08h-room-photo-btn"
                                data-target="vs08h-room-photo-<?php echo $key;?>"
                                data-preview="vs08h-room-preview-<?php echo $key;?>"
                                style="font-size:11px">
                                🖼️ Choisir une photo
                            </button>
                            <?php if(!empty($h['chambres'][$key]['photo'])):?>
                            <img id="vs08h-room-preview-<?php echo $key;?>"
                                src="<?php echo esc_url($h['chambres'][$key]['photo']); ?>"
                                style="height:36px;border-radius:4px;border:1px solid #dde;object-fit:cover">
                            <?php else:?>
                            <img id="vs08h-room-preview-<?php echo $key;?>" src="" style="height:36px;border-radius:4px;border:1px solid #dde;object-fit:cover;display:none">
                            <?php endif;?>
                        </div>
                    </div>
                </div>
                <?php endforeach;

                $fixed_keys = ['double', 'simple', 'triple'];
                $chambres = $h['chambres'] ?? [];
                $custom_keys = array_values(array_filter(array_keys($chambres), function($k) use ($fixed_keys) { return !in_array($k, $fixed_keys, true); }));
                foreach ($custom_keys as $i => $ck):
                    $c = $chambres[$ck];
                    $safe_id = preg_replace('/[^a-z0-9_]/', '_', $ck);
                ?>
                <div class="vs08h-type-row vs08h-type-row-custom" data-custom-key="<?php echo esc_attr($ck); ?>">
                    <div class="vs08h-row" style="align-items:center;gap:8px;margin-bottom:6px">
                        <label class="title" style="margin:0;flex:1">🛏️ <input type="text" name="vs08v[hotel][chambres][<?php echo esc_attr($ck); ?>][label]" value="<?php echo esc_attr($c['label'] ?? ucfirst(str_replace('custom_','', $ck))); ?>" placeholder="Nom du type (ex: Suite, Villa…)"></label>
                        <button type="button" class="button vs08h-remove-custom-room" style="flex-shrink:0" title="Supprimer ce type">✕</button>
                    </div>
                    <div class="vs08h-row" style="gap:8px">
                        <div class="vs08h-field">
                            <label>Disponible</label>
                            <select name="vs08v[hotel][chambres][<?php echo esc_attr($ck); ?>][dispo]">
                                <option value="1" <?php selected($c['dispo']??'1','1');?>>✅ Oui</option>
                                <option value="0" <?php selected($c['dispo']??'1','0');?>>❌ Non</option>
                            </select>
                        </div>
                        <div class="vs08h-field">
                            <label>Superficie m²</label>
                            <input type="text" name="vs08v[hotel][chambres][<?php echo esc_attr($ck); ?>][superficie]" value="<?php echo esc_attr($c['superficie']??''); ?>" placeholder="28">
                        </div>
                    </div>
                    <div class="vs08h-field" style="margin-top:6px">
                        <label>Description courte</label>
                        <input type="text" name="vs08v[hotel][chambres][<?php echo esc_attr($ck); ?>][desc]" value="<?php echo esc_attr($c['desc']??''); ?>" placeholder="Vue jardin, balcon privé...">
                    </div>
                    <div class="vs08h-field" style="margin-top:6px">
                        <label>📷 Photo</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="hidden" name="vs08v[hotel][chambres][<?php echo esc_attr($ck); ?>][photo]" id="vs08h-room-photo-<?php echo $safe_id;?>" value="<?php echo esc_attr($c['photo']??''); ?>">
                            <button type="button" class="button vs08h-room-photo-btn" data-target="vs08h-room-photo-<?php echo $safe_id;?>" data-preview="vs08h-room-preview-<?php echo $safe_id;?>" style="font-size:11px">🖼️ Choisir</button>
                            <?php if(!empty($c['photo'])):?>
                            <img id="vs08h-room-preview-<?php echo $safe_id;?>" src="<?php echo esc_url($c['photo']); ?>" style="height:36px;border-radius:4px;border:1px solid #dde;object-fit:cover">
                            <?php else:?>
                            <img id="vs08h-room-preview-<?php echo $safe_id;?>" src="" style="height:36px;border-radius:4px;border:1px solid #dde;object-fit:cover;display:none">
                            <?php endif;?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="margin-top:12px">
                <button type="button" id="vs08h-add-custom-room" class="button">+ Ajouter un type de chambre</button>
            </p>
            <template id="vs08h-custom-room-tpl">
                <div class="vs08h-type-row vs08h-type-row-custom" data-custom-key="">
                    <div class="vs08h-row" style="align-items:center;gap:8px;margin-bottom:6px">
                        <label class="title" style="margin:0;flex:1">🛏️ <input type="text" name="vs08v[hotel][chambres][__KEY__][label]" value="" placeholder="Nom du type (ex: Suite, Villa…)"></label>
                        <button type="button" class="button vs08h-remove-custom-room" style="flex-shrink:0" title="Supprimer">✕</button>
                    </div>
                    <div class="vs08h-row" style="gap:8px">
                        <div class="vs08h-field"><label>Disponible</label><select name="vs08v[hotel][chambres][__KEY__][dispo]"><option value="1">✅ Oui</option><option value="0">❌ Non</option></select></div>
                        <div class="vs08h-field"><label>Superficie m²</label><input type="text" name="vs08v[hotel][chambres][__KEY__][superficie]" placeholder="28"></div>
                    </div>
                    <div class="vs08h-field" style="margin-top:6px"><label>Description courte</label><input type="text" name="vs08v[hotel][chambres][__KEY__][desc]" placeholder="Vue jardin..."></div>
                    <div class="vs08h-field" style="margin-top:6px"><label>📷 Photo</label><div style="display:flex;align-items:center;gap:8px"><input type="hidden" name="vs08v[hotel][chambres][__KEY__][photo]" value=""><span style="font-size:11px;color:#888">Optionnel</span></div></div>
                </div>
            </template>
            <script>
            (function(){
                var grid = document.querySelector('.vs08h-types-grid');
                var tpl = document.getElementById('vs08h-custom-room-tpl');
                var nextIdx = <?php echo count($custom_keys); ?>;
                function nextKey(){ return 'custom_' + (nextIdx++); }
                document.getElementById('vs08h-add-custom-room').addEventListener('click', function(){
                    if (!grid || !tpl) return;
                    var key = nextKey();
                    var html = tpl.innerHTML.replace(/__KEY__/g, key);
                    var wrap = document.createElement('div');
                    wrap.innerHTML = html;
                    grid.appendChild(wrap.firstElementChild);
                });
                grid.addEventListener('click', function(e){
                    if (e.target.classList.contains('vs08h-remove-custom-room')) e.target.closest('.vs08h-type-row-custom')?.remove();
                });
            })();
            </script>
        </div>

        <!-- ④ RESTAURATION -->
        <div class="vs08h-section">
            <p class="vs08h-section-title">🍽️ Restauration</p>
            <div class="vs08h-row">
                <div class="vs08h-field">
                    <label>Nombre de restaurants</label>
                    <input type="number" name="vs08v[hotel][resto_nb]" value="<?php echo esc_attr($h['resto_nb']??''); ?>" placeholder="2" min="0">
                </div>
                <div class="vs08h-field vs08h-field-2">
                    <label>Types de cuisine</label>
                    <input type="text" name="vs08v[hotel][resto_cuisine]" value="<?php echo esc_attr($h['resto_cuisine']??''); ?>" placeholder="Gastronomique, Méditerranéen, Snack bord de piscine...">
                </div>
            </div>
            <div class="vs08h-row">
                <div class="vs08h-field vs08h-field-full">
                    <label>Description restauration</label>
                    <textarea name="vs08v[hotel][resto_desc]" rows="2" placeholder="Savourez une cuisine marocaine raffinée au restaurant Al Bahia, avec vue sur les jardins..."><?php echo esc_textarea($h['resto_desc']??''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- ⑦ LOCALISATION & ACCÈS -->
        <div class="vs08h-section">
            <p class="vs08h-section-title">📍 Localisation & Accès</p>
            <div class="vs08h-row">
                <div class="vs08h-field vs08h-field-2">
                    <label>Adresse / Quartier</label>
                    <input type="text" id="vs08h-adresse" name="vs08v[hotel][adresse]" value="<?php echo esc_attr($h['adresse']??''); ?>" placeholder="Route de l'Ourika, Marrakech Palmeraie">
                </div>
                <div class="vs08h-field">
                    <label>Distance aéroport (km)</label>
                    <input type="text" name="vs08v[hotel][dist_aero]" value="<?php echo esc_attr($h['dist_aero']??''); ?>" placeholder="12">
                </div>
                <div class="vs08h-field">
                    <label>Distance centre-ville (km)</label>
                    <input type="text" name="vs08v[hotel][dist_centre]" value="<?php echo esc_attr($h['dist_centre']??''); ?>" placeholder="5">
                </div>
            </div>
            <div class="vs08h-row" style="align-items:flex-end">
                <div class="vs08h-field vs08h-field-full">
                    <label>Description localisation (texte client)</label>
                    <textarea name="vs08v[hotel][loc_desc]" rows="2" placeholder="Idéalement situé en bordure de la palmeraie..."><?php echo esc_textarea($h['loc_desc']??''); ?></textarea>
                </div>
                <div class="vs08h-field" style="flex:0 0 auto">
                    <label>&nbsp;</label>
                    <button type="button" id="vs08h-geo-btn"
                        style="background:linear-gradient(135deg,#59b7b7,#3d9a9a);color:#fff;border:none;border-radius:8px;padding:9px 14px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;font-family:inherit">
                        🤖 Géolocalisation par l'IA
                    </button>
                    <div id="vs08h-geo-status" style="font-size:11px;margin-top:4px;display:none"></div>
                    <input type="hidden" name="vs08v[hotel][map_embed_url]" id="vs08h-map-url" value="<?php echo esc_attr($h['map_embed_url']??''); ?>">
                </div>
            </div>
        </div>
        <script>
        (function(){
            document.getElementById('vs08h-geo-btn').addEventListener('click', function(){
                var btn = this;
                var nomHotel = document.querySelector('input[name="vs08v[hotel][nom]"]')?.value || '';
                var adresse  = document.getElementById('vs08h-adresse')?.value || '';
                var query    = [nomHotel, adresse].filter(Boolean).join(', ');
                if(!query){ alert('Renseignez d\'abord le nom de l\'hôtel dans la section "Informations hôtel".'); return; }

                var st = document.getElementById('vs08h-geo-status');
                btn.disabled = true;
                btn.innerHTML = '⏳ L\'IA recherche l\'hôtel…';
                st.style.display = 'block';
                st.style.color   = '#b85c1a';
                st.style.background = '#fff8f0';
                st.style.padding = '6px 10px';
                st.style.borderRadius = '6px';
                st.textContent = '🔍 Claude cherche "' + query + '" sur le web…';

                fetch(vs08AjaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'vs08v_geo_hotel',
                        nonce:  vs08Nonce,
                        query:  query
                    })
                })
                .then(function(r){ return r.json(); })
                .then(function(res){
                    btn.disabled = false;
                    btn.innerHTML = '🤖 Géolocalisation par l\'IA';

                    if(res.success && res.data.embed_url){
                        document.getElementById('vs08h-map-url').value = res.data.embed_url;
                        st.style.color      = '#2d8a5a';
                        st.style.background = '#edfaf3';
                        st.innerHTML = '✅ <strong>Hôtel localisé !</strong><br>'
                            + '<small style="color:#6b7280">'
                            + (res.data.label || query)
                            + '<br>Lat: '+res.data.lat+' · Lon: '+res.data.lon
                            + '</small>';

                        // Miniature carte
                        var prev = document.getElementById('vs08h-map-preview');
                        if(!prev){
                            prev = document.createElement('div');
                            prev.id = 'vs08h-map-preview';
                            prev.style.cssText = 'margin-top:10px;border-radius:8px;overflow:hidden;height:160px;border:1px solid #c6f0db';
                            st.parentNode.appendChild(prev);
                        }
                        prev.innerHTML = '<iframe width="100%" height="160" frameborder="0" '
                            + 'style="border:0;display:block" loading="lazy" '
                            + 'src="' + res.data.embed_url + '&output=embed"></iframe>';

                    } else {
                        st.style.color      = '#dc2626';
                        st.style.background = '#fef2f2';
                        st.innerHTML = '❌ ' + (res.data?.message || 'Hôtel non trouvé')
                            + '<br><small style="color:#9ca3af">Essayez avec le nom exact + la ville, ex: "Hotel Algarve Palace, Faro, Portugal"</small>';
                    }
                })
                .catch(function(err){
                    btn.disabled = false;
                    btn.innerHTML = '🤖 Géolocalisation par l\'IA';
                    st.style.color = '#dc2626';
                    st.style.background = '#fef2f2';
                    st.textContent = '❌ Erreur réseau — ' + err.message;
                });
            });
        })();
        </script>

        <style>
        .vs08h-golf-block{background:#f4fbfb;border:1.5px solid #b2dfdf;border-radius:10px;padding:14px;margin-bottom:10px}
        .vs08h-golf-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
        .vs08h-golf-num{font-size:12px;font-weight:700;color:#3d9a9a;text-transform:uppercase;letter-spacing:.8px}
        </style>
        <?php
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <script>
        var vs08AjaxUrl = '<?php echo esc_js($ajax_url); ?>';
        var vs08Nonce   = '<?php echo esc_js($nonce); ?>';
        </script>
        <script>
        // Media uploader pour photos de chambres
        (function(){
            jQuery(document).ready(function($){
                $(document).on('click', '.vs08h-room-photo-btn', function(e){
                    e.preventDefault();
                    var btn = $(this);
                    var targetId  = btn.data('target');
                    var previewId = btn.data('preview');
                    var frame = wp.media({title:'Choisir une photo de chambre', button:{text:'Utiliser cette photo'}, multiple:false});
                    frame.on('select', function(){
                        var att = frame.state().get('selection').first().toJSON();
                        $('#'+targetId).val(att.url);
                        var prev = $('#'+previewId);
                        prev.attr('src', att.url).show();
                    });
                    frame.open();
                });
            });
        })();
        </script>
        <script>
        // Plus besoin de JS pour les équipements — CSS :has() natif



        // ===== SCANNER PDF =====
        (function() {
            var pdfInput  = document.getElementById('vs08h-pdf-input');
            var pdfLabel  = document.getElementById('vs08h-pdf-label');
            var pdfTxt    = document.getElementById('vs08h-pdf-txt');
            var pdfBtn    = document.getElementById('vs08h-pdf-btn');
            var status    = document.getElementById('vs08h-status');
            var stTxt     = document.getElementById('vs08h-status-text');
            var progBar   = document.getElementById('vs08h-progress-bar');
            var selectedFile = null;

            pdfInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    selectedFile = this.files[0];
                    pdfLabel.classList.add('has-file');
                    pdfTxt.innerHTML = '✅ <strong>' + selectedFile.name + '</strong> — prêt à analyser';
                    pdfBtn.style.display = 'flex';
                }
            });
            pdfLabel.addEventListener('dragover',  function(e) { e.preventDefault(); this.style.borderColor='#59b7b7'; });
            pdfLabel.addEventListener('dragleave', function()  { this.style.borderColor=''; });
            pdfLabel.addEventListener('drop', function(e) {
                e.preventDefault(); this.style.borderColor='';
                var f = e.dataTransfer.files[0];
                if (f && f.type === 'application/pdf') {
                    selectedFile = f;
                    pdfLabel.classList.add('has-file');
                    pdfTxt.innerHTML = '✅ <strong>' + f.name + '</strong> — prêt à analyser';
                    pdfBtn.style.display = 'flex';
                }
            });
            // ── Remplit le formulaire hôtel avec les données renvoyées par l'IA ──
            function fillData(d) {
                function setVal(sel, val) {
                    var el = document.querySelector(sel);
                    if (el && val !== undefined && val !== null && val !== '') {
                        el.value = val;
                        el.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                }
                function setCheck(sel, val) {
                    var el = document.querySelector(sel);
                    if (el) { el.checked = !!val; el.dispatchEvent(new Event('change', {bubbles: true})); }
                }

                setVal('input[name="vs08v[hotel][nom]"]',          d.nom);
                setVal('select[name="vs08v[hotel][etoiles]"]',     d.etoiles);
                setVal('select[name="vs08v[hotel][label]"]',       d.label);
                setVal('textarea[name="vs08v[hotel][desc]"]',      d.desc);
                setVal('select[name="vs08v[hotel][pension]"]',     d.pension);
                setVal('select[name="vs08v[hotel][type_etab]"]',   d.type_etab);
                setVal('input[name="vs08v[hotel][nb_chambres]"]',  d.nb_chambres);
                setVal('input[name="vs08v[hotel][adresse]"]',      d.adresse);
                setVal('input[name="vs08v[hotel][dist_aero]"]',    d.dist_aero);
                setVal('input[name="vs08v[hotel][dist_centre]"]',  d.dist_centre);
                setVal('textarea[name="vs08v[hotel][loc_desc]"]',  d.loc_desc);
                setVal('input[name="vs08v[hotel][tripadvisor_note]"]', d.tripadvisor_note);
                setVal('input[name="vs08v[hotel][tripadvisor_url]"]',  d.tripadvisor_url);
                setVal('input[name="vs08v[hotel][resto_nb]"]',     d.resto_nb);
                setVal('input[name="vs08v[hotel][resto_cuisine]"]',d.resto_cuisine);
                setVal('textarea[name="vs08v[hotel][resto_desc]"]',d.resto_desc);

                // Equipements checkboxes
                if (Array.isArray(d.equipements)) {
                    document.querySelectorAll('input[name="vs08v[hotel][equipements][]"]').forEach(function(cb) {
                        cb.checked = d.equipements.indexOf(cb.value) !== -1;
                    });
                }

                // Navette centre tarif
                if (d.navette_centre_tarif) {
                    var r = document.querySelector('input[name="vs08v[hotel][navette_centre_tarif]"][value="' + d.navette_centre_tarif + '"]');
                    if (r) { r.checked = true; r.dispatchEvent(new Event('change', {bubbles: true})); }
                }

                // Chambres : dispo + superficie + desc par type
                if (d.chambres && typeof d.chambres === 'object') {
                    Object.keys(d.chambres).forEach(function(key) {
                        setVal('select[name="vs08v[hotel][chambres][' + key + '][dispo]"]',  d.chambres[key].dispo);
                        setVal('input[name="vs08v[hotel][chambres][' + key + '][superficie]"]', d.chambres[key].superficie);
                        setVal('input[name="vs08v[hotel][chambres][' + key + '][desc]"]',       d.chambres[key].desc);
                    });
                }
            }

            // ── Bouton « Rechercher par nom » : Claude cherche sur le web et remplit ──
            var byNameBtn = document.getElementById('vs08h-by-name-btn');
            if (byNameBtn) {
                byNameBtn.addEventListener('click', function() {
                    if (typeof vs08AjaxUrl === 'undefined' || typeof vs08Nonce === 'undefined') {
                        alert('Configuration manquante. Rechargez la page d\'édition.');
                        return;
                    }
                    var nameInput = document.getElementById('vs08h-hotel-name');
                    var locInput = document.getElementById('vs08h-hotel-location');
                    var hotelName = (nameInput && nameInput.value) ? nameInput.value.trim() : '';
                    if (!hotelName) {
                        if (status) { status.className = 'vs08h-scanner-status error'; status.style.display = 'flex'; }
                        if (stTxt) stTxt.textContent = '❌ Entrez le nom de l\'hôtel.';
                        return;
                    }
                    var location = (locInput && locInput.value) ? locInput.value.trim() : '';
                    byNameBtn.disabled = true;
                    var btnText = byNameBtn.querySelector('#vs08h-by-name-text');
                    if (btnText) btnText.textContent = 'Recherche en cours...';
                    var progWrap = document.getElementById('vs08h-progress-wrap');
                    if (progWrap) progWrap.style.display = 'block';
                    if (status) status.style.display = 'none';
                    if (progBar) progBar.style.width = '25%';

                    var formData = new FormData();
                    formData.append('action', 'vs08v_scan_hotel_by_name');
                    formData.append('nonce', vs08Nonce);
                    formData.append('hotel_name', hotelName);
                    formData.append('location', location);

                    fetch(vs08AjaxUrl, { method: 'POST', body: formData })
                    .then(function(r) {
                        if (!r.ok) {
                            return r.text().then(function(t) {
                                var msg = t && t.length > 150 ? t.substring(0, 150) + '…' : t;
                                throw new Error('Serveur ' + r.status + (msg ? ': ' + msg : ''));
                            });
                        }
                        return r.json();
                    })
                    .then(function(res) {
                        byNameBtn.disabled = false;
                        if (btnText) btnText.textContent = 'Rechercher et remplir avec l\'IA';
                        if (progWrap) progWrap.style.display = 'none';
                        if (!res || typeof res.success === 'undefined') {
                            if (status) { status.className = 'vs08h-scanner-status error'; status.style.display = 'flex'; }
                            if (stTxt) stTxt.textContent = '❌ Réponse invalide du serveur. Rechargez la page et réessayez.';
                            return;
                        }
                        if (!res.success) {
                            if (status) { status.className = 'vs08h-scanner-status error'; status.style.display = 'flex'; }
                            if (stTxt) stTxt.textContent = '❌ ' + (res.data || 'Erreur inconnue');
                            return;
                        }
                        if (progBar) progBar.style.width = '100%';
                        fillData(res.data);
                        if (status) { status.className = 'vs08h-scanner-status success'; status.style.display = 'flex'; }
                        if (stTxt) stTxt.textContent = '✅ Données trouvées et remplies ! Vérifiez avant de sauvegarder.';
                    })
                    .catch(function(err) {
                        byNameBtn.disabled = false;
                        if (btnText) btnText.textContent = 'Rechercher et remplir avec l\'IA';
                        if (progWrap) progWrap.style.display = 'none';
                        if (status) { status.className = 'vs08h-scanner-status error'; status.style.display = 'flex'; }
                        if (stTxt) stTxt.textContent = '❌ ' + (err && err.message ? err.message : 'Erreur réseau. Réessayez ou rechargez la page.');
                    });
                });
            }

            pdfBtn.addEventListener('click', function() {
                if (!selectedFile) return;
                pdfBtn.disabled = true;
                pdfBtn.querySelector('span:last-child').textContent = 'Analyse en cours...';
                document.getElementById('vs08h-progress-wrap').style.display = 'block';
                status.style.display = 'none';
                progBar.style.width = '30%';

                var reader = new FileReader();
                reader.onload = function(e) {
                    progBar.style.width = '60%';
                    var base64 = e.target.result.split(',')[1];
                    var formData = new FormData();
                    formData.append('action',   'vs08v_scan_hotel_pdf');
                    formData.append('nonce',    vs08Nonce);
                    formData.append('pdf_b64',  base64);
                    formData.append('pdf_name', selectedFile.name);

                    fetch(vs08AjaxUrl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        pdfBtn.disabled = false;
                        pdfBtn.querySelector('span:last-child').textContent = 'Analyser le PDF';
                        document.getElementById('vs08h-progress-wrap').style.display = 'none';
                        if (!res.success) {
                            status.className = 'vs08h-scanner-status error';
                            status.style.display = 'flex';
                            stTxt.textContent = '❌ ' + (res.data || 'Erreur inconnue');
                            return;
                        }
                        progBar.style.width = '100%';
                        fillData(res.data);
                        status.className = 'vs08h-scanner-status success';
                        status.style.display = 'flex';
                        stTxt.textContent = '✅ PDF analysé ! Vérifiez les infos avant de sauvegarder.';
                    })
                    .catch(function(err) {
                        pdfBtn.disabled = false;
                        pdfBtn.querySelector('span:last-child').textContent = 'Analyser le PDF';
                        document.getElementById('vs08h-progress-wrap').style.display = 'none';
                        status.className = 'vs08h-scanner-status error';
                        status.style.display = 'flex';
                        stTxt.textContent = '❌ Erreur : ' + err.message;
                    });
                };
                reader.readAsDataURL(selectedFile);
            });
        })();
        </script>
        <?php
    }

    public static function save($post_id, $data) {
        // Géré par class-meta-boxes save() — les données sont dans vs08v[hotel]
    }

    /**
     * Génère le HTML frontend du bloc hôtel
     */
    public static function render_frontend($h, $pension_labels = []) {
        if (empty($h) || empty($h['nom'])) return '';

        $equip_icons = [
            'piscine_ext'   => ['🏊', 'Piscine extérieure'],
            'piscine_int'   => ['🏊', 'Piscine intérieure'],
            'piscine_chauffee' => ['♨️', 'Piscine chauffée'],
            'spa'           => ['💆', 'Spa / Thalasso'],
            'hammam'        => ['🧖', 'Hammam'],
            'fitness'       => ['💪', 'Fitness'],
            'restaurant'    => ['🍽️', 'Restaurant'],
            'bar'           => ['🍷', 'Bar'],
            'room_service'  => ['🛎️', 'Room service'],
            'wifi'          => ['📶', 'Wi-Fi'],
            'clim'          => ['❄️', 'Climatisation'],
            'terrasse'      => ['🌅', 'Terrasse'],
            'vue_golf'      => ['⛳', 'Vue parcours'],
            'vue_mer'       => ['🌊', 'Vue mer'],
            'kids_club'     => ['👶', 'Kids Club'],
            'tennis'        => ['🎾', 'Tennis'],
            'beach'         => ['🏖️', 'Plage'],
            'navette'       => ['🚐', 'Navette aéro.'],
            'navette_centre' => ['🚌', 'Navette ville'],
            'parking'       => ['🅿️', 'Parking'],
            'velo'          => ['🚴', 'Vélos'],
            'boutique'      => ['🛍️', 'Pro-shop'],
            'seminaire'     => ['📊', 'Séminaires'],
        ];

        $checked   = $h['equipements'] ?? [];
        $etoiles   = intval($h['etoiles'] ?? 5);
        $pension   = $pension_labels[$h['pension']??'bb'] ?? 'Petit-déjeuner';
        $label     = $h['label'] ?? '';
        $label_names = ['luxe'=>'Luxe','eco'=>'Éco-responsable','boutique'=>'Boutique Hotel','resort'=>'Resort','golf_resort'=>'Golf Resort','spa_resort'=>'Spa Resort'];

        ob_start(); ?>
        <div class="svh-wrap">

            <!-- En-tête hôtel -->
            <div class="svh-header">
                <div class="svh-title-row">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <h3 class="svh-name" style="margin:0"><?php echo esc_html($h['nom']); ?></h3>
                            <div class="svh-stars" style="margin:0;font-size:16px;color:#f59e0b"><?php echo str_repeat('★', $etoiles); ?></div>
                        </div>
                        <?php
                        $ta_note = floatval($h['tripadvisor_note'] ?? 0);
                        $ta_url  = $h['tripadvisor_url'] ?? '';
                        if ($ta_note > 0):
                            $ta_full    = floor($ta_note);
                            $ta_half    = ($ta_note - $ta_full) >= 0.3 ? 1 : 0;
                            $ta_empty   = 5 - $ta_full - $ta_half;
                            $ta_tag     = $ta_url ? 'a' : 'span';
                            $ta_href    = $ta_url ? ' href="'.esc_url($ta_url).'" target="_blank" rel="noopener"' : '';
                        ?>
                        <div style="margin-top:8px">
                            <<?php echo $ta_tag; ?><?php echo $ta_href; ?> style="display:inline-flex;align-items:center;gap:0;text-decoration:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);transition:all .25s;border:1px solid #e5e7eb" <?php if($ta_url): ?>onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 14px rgba(0,170,108,.2)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'"<?php endif; ?>>
                                <span style="display:flex;align-items:center;gap:6px;background:#34e0a1;padding:6px 12px">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="7" cy="14" r="3.2" stroke="#fff" stroke-width="1.4"/><circle cx="7" cy="14" r="1.1" fill="#fff"/>
                                        <circle cx="17" cy="14" r="3.2" stroke="#fff" stroke-width="1.4"/><circle cx="17" cy="14" r="1.1" fill="#fff"/>
                                        <path d="M12 6.5C8.8 6.5 5.5 8.3 4 11h1.8c1.2-1.6 3.5-2.8 6.2-2.8s5 1.2 6.2 2.8H20c-1.5-2.7-4.8-4.5-8-4.5z" fill="#fff"/>
                                        <polygon points="12,3.5 10.8,6 13.2,6" fill="#fff"/>
                                    </svg>
                                    <span style="font-size:16px;font-weight:800;color:#fff;font-family:'Outfit',sans-serif;letter-spacing:-.3px"><?php echo number_format($ta_note, 1, ',', ''); ?></span>
                                </span>
                                <span style="display:flex;align-items:center;gap:5px;background:#fff;padding:6px 14px 6px 10px">
                                    <span style="display:inline-flex;gap:3px">
                                        <?php for ($b = 0; $b < $ta_full; $b++): ?>
                                        <svg width="13" height="13" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7.5" fill="#00aa6c"/></svg>
                                        <?php endfor; ?>
                                        <?php if ($ta_half): ?>
                                        <svg width="13" height="13" viewBox="0 0 16 16"><defs><clipPath id="ta-h"><rect x="0" y="0" width="8" height="16"/></clipPath></defs><circle cx="8" cy="8" r="7.5" fill="#dce8e3"/><circle cx="8" cy="8" r="7.5" fill="#00aa6c" clip-path="url(#ta-h)"/></svg>
                                        <?php endif; ?>
                                        <?php for ($b = 0; $b < $ta_empty; $b++): ?>
                                        <svg width="13" height="13" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7.5" fill="#dce8e3"/></svg>
                                        <?php endfor; ?>
                                    </span>
                                    <span style="font-size:11px;font-weight:600;color:#00aa6c;font-family:'Outfit',sans-serif;letter-spacing:.3px;text-transform:uppercase">Tripadvisor</span>
                                </span>
                            </<?php echo $ta_tag; ?>>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="svh-badges">
                        <span class="svh-badge svh-badge-pension">🍳 <?php echo esc_html($pension); ?></span>
                        <?php if ($label): ?>
                        <span class="svh-badge svh-badge-label"><?php echo esc_html($label_names[$label]??$label); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($h['desc'])): ?>
                <p class="svh-desc"><?php echo nl2br(esc_html($h['desc'])); ?></p>
                <?php endif; ?>
            </div>

            <!-- Localisation -->
            <?php $has_navette_centre = in_array('navette_centre', $checked);
if (!empty($h['dist_aero']) || !empty($h['dist_centre']) || !empty($h['distance_golf']) || $has_navette_centre || !empty($h['loc_desc'])): ?>
            <div class="svh-loc">
                <div class="svh-loc-cards">
                    <?php if (!empty($h['dist_aero'])): ?>
                    <div class="svh-loc-card">
                        <span class="svh-loc-card-icon">✈️</span>
                        <div class="svh-loc-card-body">
                            <span class="svh-loc-card-label">Aéroport</span>
                            <span class="svh-loc-card-value"><?php echo esc_html($h['dist_aero']); ?> km</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($h['dist_centre'])): ?>
                    <div class="svh-loc-card">
                        <span class="svh-loc-card-icon">🏙️</span>
                        <div class="svh-loc-card-body">
                            <span class="svh-loc-card-label">Centre-ville</span>
                            <span class="svh-loc-card-value"><?php echo esc_html($h['dist_centre']); ?> km</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($h['distance_golf'])): ?>
                    <div class="svh-loc-card">
                        <span class="svh-loc-card-icon">⛳</span>
                        <div class="svh-loc-card-body">
                            <span class="svh-loc-card-label">Parcours golf</span>
                            <span class="svh-loc-card-value"><?php echo esc_html($h['distance_golf']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($has_navette_centre):
                        $nc_tarif = $h['navette_centre_tarif'] ?? 'gratuite';
                        if($nc_tarif !== 'non'):
                        $nc_badge = $nc_tarif === 'gratuite'
                            ? '<span style="font-size:10px;background:#dcfce7;color:#166534;border-radius:100px;padding:1px 7px;font-weight:700;margin-left:4px">GRATUITE</span>'
                            : '<span style="font-size:10px;background:#fef9c3;color:#713f12;border-radius:100px;padding:1px 7px;font-weight:700;margin-left:4px">PAYANTE</span>';
                    ?>
                    <div class="svh-loc-card">
                        <span class="svh-loc-card-icon">🚌</span>
                        <div class="svh-loc-card-body">
                            <span class="svh-loc-card-label">Navette centre-ville</span>
                            <span class="svh-loc-card-value" style="display:flex;align-items:center"><?php echo $nc_badge; ?></span>
                        </div>
                    </div>
                    <?php endif; endif; ?>
                </div>
                <?php if (!empty($h['loc_desc'])): ?><p class="svh-loc-text"><?php echo esc_html($h['loc_desc']); ?></p><?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Équipements -->
            <?php if (!empty($checked)): ?>
            <div class="svh-equip-section">
                <h4 class="svh-sub-title">Services & équipements</h4>
                <div class="svh-equip-grid">
                    <?php foreach($checked as $key):
                        if (!isset($equip_icons[$key])) continue;
                        [$icon, $lbl] = $equip_icons[$key]; ?>
                    <div class="svh-equip-chip"><span><?php echo $icon; ?></span><?php echo esc_html($lbl); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Chambres disponibles -->
            <?php
            $chambres = $h['chambres'] ?? [];
            $dispo_chambres = array_filter($chambres, fn($c) => ($c['dispo']??'1') === '1');
            if (!empty($dispo_chambres)): ?>
            <div class="svh-rooms-section">
                <h4 class="svh-sub-title">Types de chambres</h4>
                <div class="svh-rooms-grid">
                    <?php
                    $room_icons = ['double'=>'🛏️','simple'=>'🛏️','triple'=>'🛌'];
                    $room_names = ['double'=>'Double','simple'=>'Simple','triple'=>'Triple'];
                    foreach($dispo_chambres as $type => $c): ?>
                    <div class="svh-room-card" <?php if(!empty($c['photo'])):?>data-room-photo="<?php echo esc_url($c['photo']); ?>" onmouseenter="svhRoomHover(event,this)" onmousemove="svhRoomMove(event)" onmouseleave="svhRoomLeave()"<?php endif;?>>
                        <span class="svh-room-icon"><?php echo $room_icons[$type]??'🛏️'; ?></span>
                        <div class="svh-room-meta">
                            <div class="svh-room-top">
                                <span class="svh-room-name"><?php echo esc_html($c['label'] ?? $room_names[$type] ?? ucfirst($type)); ?></span>
                                <?php if (!empty($c['superficie'])): ?><span class="svh-room-size">~<?php echo esc_html($c['superficie']); ?> m²</span><?php endif; ?>
                            </div>
                            <?php if (!empty($c['desc'])): ?><p class="svh-room-desc"><?php echo esc_html($c['desc']); ?></p><?php endif; ?>
                        </div>
                        <?php if(!empty($c['photo'])):?><span style="font-size:11px;color:#9ca3af;font-family:'Outfit',sans-serif;margin-left:auto;flex-shrink:0">📷 photo</span><?php endif;?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Restauration + Spa côte à côte -->
            <div class="svh-details-grid">
                <?php if (!empty($h['resto_desc']) || !empty($h['resto_cuisine'])): ?>
                <div class="svh-detail-card">
                    <h4 class="svh-detail-title">🍽️ Restauration</h4>
                    <?php if (!empty($h['resto_nb'])): ?><p class="svh-detail-meta"><?php echo esc_html($h['resto_nb']); ?> restaurant(s)</p><?php endif; ?>
                    <?php if (!empty($h['resto_cuisine'])): ?><p class="svh-detail-meta">🍴 <?php echo esc_html($h['resto_cuisine']); ?></p><?php endif; ?>
                    <?php if (!empty($h['resto_desc'])): ?><p class="svh-detail-text"><?php echo esc_html($h['resto_desc']); ?></p><?php endif; ?>
                </div>
                <?php endif; ?>

            </div>



        </div>

        <style>
        .svh-wrap{font-family:'Outfit',sans-serif}
        .svh-header{margin-bottom:18px}
        .svh-title-row{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:10px}
        .svh-name{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#0f2424;margin:0 0 4px}
        .svh-stars{color:#f59e0b;font-size:16px;letter-spacing:2px}
        .svh-badges{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start}
        .svh-badge{padding:5px 12px;border-radius:100px;font-size:11px;font-weight:700;white-space:nowrap}
        .svh-badge-pension{background:#edf8f8;color:#1a3a3a}
        .svh-badge-label{background:#fef3c7;color:#92400e}
        .svh-desc{font-size:14px;color:#4a5568;line-height:1.75;margin:0}
        .svh-sub-title{font-size:13px;font-weight:700;color:#0f2424;margin:0 0 10px;display:flex;align-items:center;gap:6px}
        .svh-loc{background:#f9f6f0;border-radius:10px;padding:14px 16px;margin-bottom:16px}
        .svh-loc-cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:8px}
        .svh-loc-card{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #e8e4dd;border-radius:10px;padding:10px 14px;min-width:140px}
        .svh-loc-card-icon{font-size:20px;flex-shrink:0}
        .svh-loc-card-body{display:flex;flex-direction:column;gap:2px}
        .svh-loc-card-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#9ca3af}
        .svh-loc-card-value{font-size:13px;font-weight:700;color:#0f2424}
        .svh-loc-text{font-size:13px;color:#6b7280;margin:6px 0 0;line-height:1.6}
        .svh-equip-section{margin-bottom:18px}
        .svh-equip-grid{display:flex;gap:8px;flex-wrap:wrap}
        .svh-equip-chip{display:flex;align-items:center;gap:6px;background:#f9f6f0;border:1px solid #e8e4dd;border-radius:8px;padding:6px 12px;font-size:12px;color:#1a3a3a}
        .svh-rooms-section{margin-bottom:18px}
        .svh-rooms-grid{display:flex;flex-direction:column;gap:10px}
        .svh-room-card{background:#fff;border:1.5px solid #edf0f4;border-radius:14px;padding:16px 20px;display:flex;align-items:center;gap:16px;width:100%;box-shadow:0 1px 4px rgba(0,0,0,.04);position:relative;cursor:default}
.svh-room-thumb-tip{position:fixed;z-index:9999;pointer-events:none;display:none;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.22);border:2px solid #fff;width:220px;height:165px}
.svh-room-thumb-tip img{width:100%;height:100%;object-fit:cover}
        .svh-room-icon{font-size:26px;flex-shrink:0;width:40px;text-align:center}
        .svh-room-meta{flex:1}
        .svh-room-top{display:flex;align-items:center;gap:10px;margin-bottom:4px}
        .svh-room-name{font-weight:700;font-size:15px;color:#0f2424}
        .svh-room-size{font-size:11px;color:#59b7b7;font-weight:700;background:#edf8f8;padding:2px 10px;border-radius:100px;white-space:nowrap}
        .svh-room-desc{font-size:13px;color:#6b7280;line-height:1.55;margin:0}
        .svh-details-grid{display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:18px}
        .svh-detail-card{background:#f9f6f0;border-radius:12px;padding:16px}
        .svh-detail-title{font-size:14px;font-weight:700;color:#0f2424;margin:0 0 8px}
        .svh-detail-meta{font-size:12px;color:#59b7b7;font-weight:600;margin:0 0 4px}
        .svh-detail-text{font-size:13px;color:#4a5568;line-height:1.65;margin:8px 0 0}
        .svh-golf-section{background:#edf8f8;border-radius:12px;padding:16px}
        .svh-golf-meta{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
        .svh-golf-chip{background:#fff;border:1px solid #b2dfdf;border-radius:100px;padding:4px 12px;font-size:12px;color:#1a3a3a;font-weight:600}
        .svh-golf-card{padding:14px 0}
.svh-golf-card--sep{border-top:1px solid #d4efef;margin-top:4px}
.svh-golf-card-header{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:8px}
.svh-golf-name{font-size:15px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif}
.svh-golf-chips{display:flex;gap:6px;flex-wrap:wrap}
.svh-golf-chip{background:#fff;border:1px solid #b2dfdf;border-radius:100px;padding:3px 10px;font-size:11px;color:#1a3a3a;font-weight:600}
.svh-chip-green{background:#e8f8f0;border-color:#6fcf97;color:#1a6640}
.svh-golf-desc{font-size:13px;color:#4a5568;line-height:1.65;margin:0}
        @media(max-width:768px){.svh-details-grid{grid-template-columns:1fr}.svh-title-row{flex-direction:column}}
        </style>
        <!-- Tooltip photo chambre -->
        <div class="svh-room-thumb-tip" id="svh-room-tip"><img src="" alt="" id="svh-room-tip-img"></div>
        <script>
        function svhRoomHover(e,el){
            var tip=document.getElementById('svh-room-tip');
            var img=document.getElementById('svh-room-tip-img');
            if(!tip||!img) return;
            img.src = el.dataset.roomPhoto;
            tip.style.display='block';
        }
        function svhRoomMove(e){
            var tip=document.getElementById('svh-room-tip');
            if(!tip) return;
            var x=e.clientX+20, y=e.clientY+20;
            if(x+230>window.innerWidth) x=e.clientX-240;
            if(y+175>window.innerHeight) y=e.clientY-180;
            tip.style.left=x+'px'; tip.style.top=y+'px';
        }
        function svhRoomLeave(){
            var tip=document.getElementById('svh-room-tip');
            if(tip) tip.style.display='none';
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
