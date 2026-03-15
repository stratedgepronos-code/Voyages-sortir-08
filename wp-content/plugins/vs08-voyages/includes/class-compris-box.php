<?php
/**
 * VS08 Voyages — Box "Compris / Non compris" (mentions légales forfait touristique)
 * Conformité Code du tourisme L.211-1 et suivants
 */
class VS08V_ComprisBox {

    public static function register() {
        add_meta_box('vs08v_compris', '✅ Compris / Non compris — Mentions légales', [__CLASS__, 'render'], 'vs08_voyage', 'normal', 'default');
    }

    public static function render($post) {
        $m  = VS08V_MetaBoxes::get($post->ID);
        $cp = $m['compris'] ?? [];
        $compris_items = [
            'vol'               => ['✈️', 'Vol aller-retour'],
            'transfert_groupe'  => ['🚐', 'Transferts aéroport / hôtel / aéroport groupé'],
            'transfert_prive'   => ['🚐', 'Transferts aéroport / hôtel / aéroport privé'],
            'location_vehicule' => ['🚗', 'Location de véhicule'],  // input type spécial
            'hebergement'       => ['🏨', 'Hébergement'],
            'pension'           => ['🍽️', 'Restauration (selon formule)'],
            'greenfees'         => ['⛳', 'Green fees parcours(s)'],
            'buggy'             => ['🚗', 'Buggy / Chariot'],
            'assurance'         => ['🛡️', 'Assurance annulation/rapatriement'],
            'encadrement'       => ['👨‍🏫', 'Encadrement / Guide francophone'],
            'taxes'             => ['🏛️', 'Taxes et charges incluses'],
            'welcome'           => ['🎁', 'Cocktail de bienvenue'],
            'navette_golfs'     => ['🚌', 'Navette hôtel / golfs / hôtel'],
        ];
        $non_compris_items = [
            'taxes_sejour' => ['🏛️', 'Taxes de séjour'],
            'vol_option'  => ['✈️', 'Vol (en option, non inclus)'],
            'extras'      => ['🛎️', 'Extras et dépenses personnelles'],
            'boissons'    => ['🍷', 'Boissons aux repas'],
            'pourboires'  => ['💶', 'Pourboires'],
            'visa'        => ['🛂', 'Visa et formalités'],
            'assurance_opt'=> ['🛡️', 'Assurance (proposée en option)'],
            'location'    => ['🏌️', 'Location de matériel golf'],
            'lessons'     => ['📋', 'Leçons / cours de golf'],
            'lavage'      => ['🧹', 'Lavage des clubs'],
            'shopping'    => ['🛍️', 'Achats personnels'],
        ];
        ?>
        <style>
        .vs08c-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px}
        .vs08c-col{background:#f9fafb;border-radius:12px;padding:16px}
        .vs08c-col-title{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin:0 0 12px;display:flex;align-items:center;gap:6px}
        .vs08c-col-title.yes{color:#2d8a5a}.vs08c-col-title.no{color:#dc2626}
        .vs08c-items{display:flex;flex-direction:column;gap:6px}
        .vs08c-item{display:flex;align-items:center;gap:8px;font-size:13px;color:#374151}
        .vs08c-item input[type=checkbox]{width:16px;height:16px;accent-color:#59b7b7;cursor:pointer;flex-shrink:0}
        .vs08c-extras{margin-top:12px}
        .vs08c-extras label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;display:block;margin-bottom:4px}
        .vs08c-extras textarea{width:100%;border:1.5px solid #dde1e7;border-radius:8px;padding:8px;font-size:12px;font-family:inherit;resize:vertical;box-sizing:border-box}
        .vs08c-legal{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;margin-top:12px;font-size:12px;color:#78350f;line-height:1.6}
        .vs08c-legal strong{display:block;margin-bottom:4px}
        </style>

        <div class="vs08c-grid">
            <!-- COMPRIS -->
            <div class="vs08c-col">
                <p class="vs08c-col-title yes">✅ Ce qui est compris dans le prix</p>
                <div class="vs08c-items">
                    <?php foreach($compris_items as $key => [$ico, $lbl]):
                        $is_checked = in_array($key, $cp['oui']??[]); ?>
                    <label class="vs08c-item" style="flex-wrap:wrap;gap:6px">
                        <input type="checkbox" name="vs08v[compris][oui][]" value="<?php echo $key; ?>" <?php checked($is_checked); ?>>
                        <span><?php echo $ico; ?></span> <?php echo $lbl; ?>
                        <?php if($key === 'location_vehicule'): ?>
                        <input type="text"
                            name="vs08v[compris][location_vehicule_type]"
                            value="<?php echo esc_attr($cp['location_vehicule_type']??''); ?>"
                            placeholder="Ex : SUV, berline, golf cart…"
                            style="flex:1;min-width:140px;border:1.5px solid #dde1e7;border-radius:6px;padding:4px 8px;font-size:12px;font-family:inherit;background:#fff;box-sizing:border-box">
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="vs08c-extras">
                    <label>Éléments compris supplémentaires (un par ligne)</label>
                    <textarea name="vs08v[compris][oui_extra]" rows="3" placeholder="Ex: Cadeau de bienvenue&#10;Bouteille de vin en chambre"><?php echo esc_textarea($cp['oui_extra']??''); ?></textarea>
                </div>
            </div>

            <!-- NON COMPRIS -->
            <div class="vs08c-col">
                <p class="vs08c-col-title no">❌ Ce qui n'est pas compris</p>
                <div class="vs08c-items">
                    <?php foreach($non_compris_items as $key => [$ico, $lbl]): ?>
                    <label class="vs08c-item">
                        <input type="checkbox" name="vs08v[compris][non][]" value="<?php echo $key; ?>" <?php checked(in_array($key, $cp['non']??[])); ?>>
                        <span><?php echo $ico; ?></span> <?php echo $lbl; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="vs08c-extras">
                    <label>Éléments non compris supplémentaires (un par ligne)</label>
                    <textarea name="vs08v[compris][non_extra]" rows="3" placeholder="Ex: Taxes de séjour locales&#10;Assurance annulation (option)"><?php echo esc_textarea($cp['non_extra']??''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Mentions légales obligatoires -->
        <div class="vs08c-grid" style="grid-template-columns:1fr">
            <div class="vs08c-col">
                <p class="vs08c-col-title" style="color:#1d4ed8">📋 Informations pratiques & formalités</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;display:block;margin-bottom:4px">Documents requis (passeport, visa...)</label>
                        <textarea name="vs08v[compris][formalites]" rows="3" style="width:100%;border:1.5px solid #dde1e7;border-radius:8px;padding:8px;font-size:12px;font-family:inherit;box-sizing:border-box" placeholder="Passeport valide 6 mois après le retour. Aucun visa requis pour les ressortissants UE..."><?php echo esc_textarea($cp['formalites']??''); ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="vs08c-legal">
            <strong>ℹ️ Conformité Code du tourisme (art. L.211-1 et suivants)</strong>
            Ces informations sont obligatoires pour tout forfait touristique. Elles doivent figurer sur la fiche voyage affichée au client conformément à la directive européenne 2015/2302 transposée en droit français. Veillez à ce que toutes les rubriques soient complétées avant publication.
        </div>
        <?php
    }

    public static function render_frontend($cp) {
        if (empty($cp)) return '';
        $compris_labels = [
            'vol'=>['✈️','Vol aller-retour'],
            'transfert'=>['🚐','Transferts groupés'],          // ancien slug de compatibilité
            'transfert_groupe'=>['🚐','Transferts groupés'],
            'transfert_prive'=>['🚐','Transferts privés'],
            'location_vehicule'=>['🚗','Location de véhicule'],
            'hebergement'=>['🏨','Hébergement'],
            'pension'=>['🍽️','Restauration'],'greenfees'=>['⛳','Green fees'],'buggy'=>['🚗','Buggy/Chariot'],
            'assurance'=>['🛡️','Assurance'],'encadrement'=>['👨‍🏫','Encadrement francophone'],
            'taxes'=>['🏛️','Taxes incluses'],'welcome'=>['🎁','Cocktail de bienvenue'],'navette_golfs'=>['🚌','Navette hôtel / golfs / hôtel'],
        ];
        $non_labels = [
            'taxes_sejour'=>['🏛️','Taxes de séjour'],
            'vol_option'=>['✈️','Vol (en option)'],'extras'=>['🛎️','Extras personnels'],'boissons'=>['🍷','Boissons aux repas'],
            'pourboires'=>['💶','Pourboires'],'visa'=>['🛂','Visa et formalités'],
            'assurance_opt'=>['🛡️','Assurance (option)'],'location'=>['🏌️','Location matériel'],
            'lessons'=>['📋','Cours de golf'],'lavage'=>['🧹','Lavage des clubs'],'shopping'=>['🛍️','Achats personnels'],
        ];
        $oui  = $cp['oui']  ?? [];
        $non  = $cp['non']  ?? [];
        $oui_extra = array_filter(array_map('trim', explode("\n", $cp['oui_extra']??'')));
        $non_extra = array_filter(array_map('trim', explode("\n", $cp['non_extra']??'')));
        if (empty($oui) && empty($non) && empty($oui_extra) && empty($non_extra)) return '';
        ob_start(); ?>
        <div class="svc-wrap">
            <div class="svc-grid">
                <div class="svc-col svc-col-yes">
                    <h4 class="svc-title">✅ Compris dans le prix</h4>
                    <ul class="svc-list">
                        <?php foreach($oui as $k): if (!isset($compris_labels[$k])) continue; [$ico,$lbl]=$compris_labels[$k];
                            // Pour location_vehicule, ajouter le type de véhicule si renseigné
                            $extra_txt = '';
                            if($k === 'location_vehicule' && !empty($cp['location_vehicule_type'])) {
                                $extra_txt = ' — ' . esc_html($cp['location_vehicule_type']);
                            }
                        ?>
                        <li><span><?php echo $ico; ?></span><?php echo esc_html($lbl).$extra_txt; ?></li>
                        <?php endforeach; ?>
                        <?php foreach($oui_extra as $e): ?><li><span>✔️</span><?php echo esc_html($e); ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <div class="svc-col svc-col-no">
                    <h4 class="svc-title">❌ Non compris</h4>
                    <ul class="svc-list">
                        <?php foreach($non as $k): if (!isset($non_labels[$k])) continue; [$ico,$lbl]=$non_labels[$k]; ?>
                        <li><span><?php echo $ico; ?></span><?php echo esc_html($lbl); ?></li>
                        <?php endforeach; ?>
                        <?php foreach($non_extra as $e): ?><li><span>➖</span><?php echo esc_html($e); ?></li><?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php if (!empty($cp['formalites'])): ?>
            <div class="svc-info"><h5>📋 Formalités requises</h5><p><?php echo nl2br(esc_html($cp['formalites'])); ?></p></div>
            <?php endif; ?>

        </div>
        <style>
        .svc-wrap{font-family:'Outfit',sans-serif}
        .svc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
        .svc-col{border-radius:12px;padding:16px}
        .svc-col-yes{background:#f0fdf4;border:1px solid #bbf7d0}
        .svc-col-no{background:#fef2f2;border:1px solid #fecaca}
        .svc-title{font-size:13px;font-weight:700;margin:0 0 12px;color:#0f2424}
        .svc-list{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:8px}
        .svc-list li{display:flex;align-items:center;gap:8px;font-size:13px;color:#374151}
        .svc-list li span{font-size:15px;flex-shrink:0}
        .svc-info{background:#f9f6f0;border-radius:10px;padding:14px;margin-top:10px}
        .svc-info h5{font-size:12px;font-weight:700;color:#0f2424;margin:0 0 6px}
        .svc-info p{font-size:13px;color:#4a5568;line-height:1.65;margin:0}
        @media(max-width:600px){.svc-grid{grid-template-columns:1fr}}
        </style>
        <?php return ob_get_clean();
    }
}
