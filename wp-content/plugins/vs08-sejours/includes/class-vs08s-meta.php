<?php
if (!defined('ABSPATH')) exit;

class VS08S_Meta {

    public static function register() {
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save'], 10, 2);
    }

    public static function add_meta_boxes() {
        add_meta_box('vs08s_main', '🏖️ Configuration du séjour', [__CLASS__, 'render'], 'vs08_sejour', 'normal', 'high');
    }

    public static function get($post_id) {
        $m = get_post_meta($post_id, '_vs08s_meta', true);
        return is_array($m) ? $m : [];
    }

    public static function render($post) {
        $m = self::get($post->ID);
        wp_nonce_field('vs08s_save', 'vs08s_nonce');

        // Valeurs par défaut
        $destination   = $m['destination'] ?? '';
        $pays          = $m['pays'] ?? '';
        $flag          = $m['flag'] ?? '';
        $duree         = $m['duree'] ?? 7;
        $duree_jours   = $m['duree_jours'] ?? 8;
        $hotel_nom     = $m['hotel_nom'] ?? '';
        $hotel_etoiles = $m['hotel_etoiles'] ?? 5;
        $hotel_code    = $m['hotel_code'] ?? '';       // Code Bedsonline
        $hotel_codes   = $m['hotel_codes'] ?? [];      // Codes multiples
        $pension       = $m['pension'] ?? 'ai';
        $iata_dest     = $m['iata_dest'] ?? '';
        $transfert_prix = $m['transfert_prix'] ?? 0;
        $transfert_type = $m['transfert_type'] ?? 'groupes';
        $marge_type    = $m['marge_type'] ?? 'pourcentage';
        $marge_valeur  = $m['marge_valeur'] ?? 15;
        $acompte_pct   = $m['acompte_pct'] ?? 30;
        $delai_solde   = $m['delai_solde'] ?? 30;
        $statut        = $m['statut'] ?? 'actif';
        $badge         = $m['badge'] ?? '';
        $galerie       = $m['galerie'] ?? [];
        $inclus        = $m['inclus'] ?? "Vol aller-retour\nHébergement\nPension selon formule\nTransferts aéroport-hôtel\nAssistance sur place";
        $non_inclus    = $m['non_inclus'] ?? "Dépenses personnelles\nExcursions optionnelles\nAssurance voyage";
        $annulation    = $m['annulation'] ?? [];
        $annulation_texte = $m['annulation_texte'] ?? '';
        $aeroports     = $m['aeroports'] ?? [];
        $prix_bagage_soute = $m['prix_bagage_soute'] ?? 0;
        $prix_bagage_cabine = $m['prix_bagage_cabine'] ?? 0;
        $description_courte = $m['description_courte'] ?? '';

        // Pensions
        $pensions_map = [
            'ai' => 'All inclusive',
            'pc' => 'Pension complète',
            'dp' => 'Demi-pension',
            'bb' => 'Petit-déjeuner',
            'lo' => 'Logement seul',
        ];

        // Badges
        $badges_map = ['' => '— Aucun —', 'new' => 'Nouveauté', 'promo' => 'Promo', 'best' => 'Best-seller', 'derniere' => 'Dernières places'];
        ?>
        <style>
        .vs08s-tabs{display:flex;gap:0;border-bottom:3px solid #59b7b7;margin-bottom:20px}
        .vs08s-tab{padding:10px 20px;cursor:pointer;font-weight:600;font-size:13px;color:#6b7280;border-radius:8px 8px 0 0;transition:all .2s}
        .vs08s-tab:hover{color:#0f2424;background:#f0f9f9}
        .vs08s-tab.active{background:#59b7b7;color:#fff}
        .vs08s-panel{display:none}
        .vs08s-panel.active{display:block}
        .vs08s-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
        .vs08s-row.cols-3{grid-template-columns:1fr 1fr 1fr}
        .vs08s-row.cols-4{grid-template-columns:1fr 1fr 1fr 1fr}
        .vs08s-field{margin-bottom:14px}
        .vs08s-field label{display:block;font-weight:700;font-size:12px;color:#374151;margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px}
        .vs08s-field input,.vs08s-field select,.vs08s-field textarea{width:100%;padding:10px 12px;border:1.5px solid #d1d5db;border-radius:10px;font-size:14px;font-family:inherit}
        .vs08s-field textarea{min-height:100px}
        .vs08s-field input:focus,.vs08s-field select:focus,.vs08s-field textarea:focus{border-color:#59b7b7;outline:none;box-shadow:0 0 0 3px rgba(89,183,183,.15)}
        .vs08s-section{background:#f9f6f0;border-radius:14px;padding:20px;margin-bottom:20px}
        .vs08s-section h3{margin:0 0 14px;font-size:15px;color:#0f2424}
        .vs08s-hint{font-size:11px;color:#9ca3af;margin-top:2px}
        .vs08s-aeroport-block{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:10px;position:relative}
        .vs08s-aeroport-remove{position:absolute;top:8px;right:8px;background:none;border:none;color:#dc2626;cursor:pointer;font-size:18px}
        .vs08s-btn{padding:8px 18px;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px}
        .vs08s-btn-add{background:#59b7b7;color:#fff}
        .vs08s-btn-add:hover{background:#3d9a9a}
        .vs08s-hotel-code-item{display:flex;gap:8px;align-items:center;margin-bottom:6px}
        .vs08s-hotel-code-item input{flex:1}
        .vs08s-hotel-code-remove{background:none;border:none;color:#dc2626;cursor:pointer;font-size:16px}
        </style>

        <div class="vs08s-tabs">
            <div class="vs08s-tab active" data-panel="general">📋 Général</div>
            <div class="vs08s-tab" data-panel="hotel">🏨 Hôtel & Bedsonline</div>
            <div class="vs08s-tab" data-panel="vols">✈️ Vols & Aéroports</div>
            <div class="vs08s-tab" data-panel="prix">💰 Prix & Marge</div>
            <div class="vs08s-tab" data-panel="contenu">📝 Contenu</div>
            <div class="vs08s-tab" data-panel="conditions">📋 Conditions</div>
        </div>

        <!-- ═══ GÉNÉRAL ═══ -->
        <div class="vs08s-panel active" id="panel-general">
            <div class="vs08s-row cols-3">
                <div class="vs08s-field">
                    <label>Destination</label>
                    <input type="text" name="vs08s[destination]" value="<?php echo esc_attr($destination); ?>" placeholder="Ex: Djerba, Hurghada...">
                </div>
                <div class="vs08s-field">
                    <label>Pays</label>
                    <input type="text" name="vs08s[pays]" value="<?php echo esc_attr($pays); ?>" placeholder="Ex: Tunisie">
                </div>
                <div class="vs08s-field">
                    <label>Drapeau emoji</label>
                    <input type="text" name="vs08s[flag]" value="<?php echo esc_attr($flag); ?>" placeholder="🇹🇳" style="font-size:20px">
                </div>
            </div>
            <div class="vs08s-row cols-4">
                <div class="vs08s-field">
                    <label>Durée (nuits)</label>
                    <input type="number" name="vs08s[duree]" value="<?php echo esc_attr($duree); ?>" min="1">
                </div>
                <div class="vs08s-field">
                    <label>Durée (jours)</label>
                    <input type="number" name="vs08s[duree_jours]" value="<?php echo esc_attr($duree_jours); ?>" min="1">
                </div>
                <div class="vs08s-field">
                    <label>Badge</label>
                    <select name="vs08s[badge]">
                        <?php foreach ($badges_map as $k => $v): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($badge, $k); ?>><?php echo esc_html($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs08s-field">
                    <label>Statut</label>
                    <select name="vs08s[statut]">
                        <option value="actif" <?php selected($statut, 'actif'); ?>>Actif</option>
                        <option value="archive" <?php selected($statut, 'archive'); ?>>Archivé</option>
                    </select>
                </div>
            </div>
            <div class="vs08s-field">
                <label>Description courte (accroche)</label>
                <textarea name="vs08s[description_courte]" rows="2" placeholder="Ex: Séjour all inclusive en bord de mer..."><?php echo esc_textarea($description_courte); ?></textarea>
            </div>
            <div class="vs08s-field">
                <label>Galerie d'images (URLs, une par ligne)</label>
                <textarea name="vs08s[galerie_raw]" rows="4" placeholder="https://example.com/photo1.jpg"><?php echo esc_textarea(implode("\n", $galerie)); ?></textarea>
                <p class="vs08s-hint">Ou utilisez l'image à la une de WordPress pour la photo principale.</p>
            </div>
        </div>

        <!-- ═══ HÔTEL & BEDSONLINE ═══ -->
        <div class="vs08s-panel" id="panel-hotel">
            <div class="vs08s-section">
                <h3>🏨 Hôtel principal</h3>
                <div class="vs08s-row cols-3">
                    <div class="vs08s-field">
                        <label>Nom de l'hôtel</label>
                        <input type="text" name="vs08s[hotel_nom]" value="<?php echo esc_attr($hotel_nom); ?>" placeholder="Ex: Radisson Blu Resort">
                    </div>
                    <div class="vs08s-field">
                        <label>Étoiles</label>
                        <select name="vs08s[hotel_etoiles]">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($hotel_etoiles, $i); ?>><?php echo str_repeat('★', $i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="vs08s-field">
                        <label>Pension par défaut</label>
                        <select name="vs08s[pension]">
                            <?php foreach ($pensions_map as $k => $v): ?>
                            <option value="<?php echo esc_attr($k); ?>" <?php selected($pension, $k); ?>><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="vs08s-section">
                <h3>🔗 Codes Bedsonline / Hotelbeds</h3>
                <p class="vs08s-hint" style="margin-bottom:12px">Ajoutez un ou plusieurs codes hôtel Bedsonline. Le système cherchera les tarifs sur tous les codes et prendra le meilleur prix.</p>
                <div class="vs08s-field">
                    <label>Code hôtel principal</label>
                    <input type="text" name="vs08s[hotel_code]" value="<?php echo esc_attr($hotel_code); ?>" placeholder="Ex: 134589">
                    <p class="vs08s-hint">Trouvez le code dans votre back-office Bedsonline (Portfolio > Hotels)</p>
                </div>
                <div id="vs08s-hotel-codes-list">
                    <?php if (!empty($hotel_codes)): foreach ($hotel_codes as $idx => $code): if (empty($code)) continue; ?>
                    <div class="vs08s-hotel-code-item">
                        <input type="text" name="vs08s[hotel_codes][]" value="<?php echo esc_attr($code); ?>" placeholder="Code alternatif">
                        <button type="button" class="vs08s-hotel-code-remove" onclick="this.parentElement.remove()">✕</button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" class="vs08s-btn vs08s-btn-add" onclick="var d=document.createElement('div');d.className='vs08s-hotel-code-item';d.innerHTML='<input type=\'text\' name=\'vs08s[hotel_codes][]\' placeholder=\'Code alternatif\'><button type=\'button\' class=\'vs08s-hotel-code-remove\' onclick=\'this.parentElement.remove()\'>✕</button>';document.getElementById('vs08s-hotel-codes-list').appendChild(d)">+ Ajouter un code hôtel</button>
            </div>

            <div class="vs08s-section">
                <h3>🚌 Transferts</h3>
                <div class="vs08s-row">
                    <div class="vs08s-field">
                        <label>Type de transfert</label>
                        <select name="vs08s[transfert_type]">
                            <option value="groupes" <?php selected($transfert_type, 'groupes'); ?>>🚌 Transferts groupés</option>
                            <option value="prives" <?php selected($transfert_type, 'prives'); ?>>🚐 Transferts privés</option>
                            <option value="inclus" <?php selected($transfert_type, 'inclus'); ?>>✅ Inclus dans l'hôtel</option>
                            <option value="aucun" <?php selected($transfert_type, 'aucun'); ?>>❌ Non inclus</option>
                        </select>
                    </div>
                    <div class="vs08s-field">
                        <label>Prix transfert par personne (€)</label>
                        <input type="number" name="vs08s[transfert_prix]" value="<?php echo esc_attr($transfert_prix); ?>" step="0.01" min="0">
                        <p class="vs08s-hint">Aller-retour. 0 si inclus.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ VOLS & AÉROPORTS ═══ -->
        <div class="vs08s-panel" id="panel-vols">
            <div class="vs08s-field">
                <label>Code IATA de destination</label>
                <input type="text" name="vs08s[iata_dest]" value="<?php echo esc_attr($iata_dest); ?>" placeholder="Ex: DJE, HRG, AGP..." style="width:200px;text-transform:uppercase">
                <p class="vs08s-hint">Code IATA de l'aéroport de destination (ex: DJE pour Djerba, HRG pour Hurghada)</p>
            </div>

            <div class="vs08s-row">
                <div class="vs08s-field">
                    <label>Prix bagage soute /pers (€)</label>
                    <input type="number" name="vs08s[prix_bagage_soute]" value="<?php echo esc_attr($prix_bagage_soute); ?>" step="0.01" min="0">
                </div>
                <div class="vs08s-field">
                    <label>Prix bagage cabine /pers (€)</label>
                    <input type="number" name="vs08s[prix_bagage_cabine]" value="<?php echo esc_attr($prix_bagage_cabine); ?>" step="0.01" min="0">
                </div>
            </div>

            <h3>Aéroports de départ</h3>
            <div id="vs08s-aeroports-list">
            <?php if (!empty($aeroports)): foreach ($aeroports as $ai => $a): ?>
                <div class="vs08s-aeroport-block">
                    <button type="button" class="vs08s-aeroport-remove" onclick="this.parentElement.remove()">✕</button>
                    <div class="vs08s-row cols-3">
                        <div class="vs08s-field">
                            <label>Code IATA</label>
                            <input type="text" name="vs08s[aeroports][<?php echo $ai; ?>][code]" value="<?php echo esc_attr($a['code'] ?? ''); ?>" style="text-transform:uppercase">
                        </div>
                        <div class="vs08s-field">
                            <label>Ville</label>
                            <input type="text" name="vs08s[aeroports][<?php echo $ai; ?>][ville]" value="<?php echo esc_attr($a['ville'] ?? ''); ?>">
                        </div>
                        <div class="vs08s-field">
                            <label>Supplément vol /pers (€)</label>
                            <input type="number" name="vs08s[aeroports][<?php echo $ai; ?>][supplement]" value="<?php echo esc_attr($a['supplement'] ?? 0); ?>" step="0.01">
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
            </div>
            <button type="button" class="vs08s-btn vs08s-btn-add" id="vs08s-add-aeroport">+ Ajouter un aéroport</button>
        </div>

        <!-- ═══ PRIX & MARGE ═══ -->
        <div class="vs08s-panel" id="panel-prix">
            <div class="vs08s-section">
                <h3>💰 Marge agence</h3>
                <div class="vs08s-row cols-3">
                    <div class="vs08s-field">
                        <label>Type de marge</label>
                        <select name="vs08s[marge_type]">
                            <option value="pourcentage" <?php selected($marge_type, 'pourcentage'); ?>>% du prix net hôtel</option>
                            <option value="fixe_personne" <?php selected($marge_type, 'fixe_personne'); ?>>€ fixe par personne</option>
                            <option value="fixe_total" <?php selected($marge_type, 'fixe_total'); ?>>€ fixe sur le dossier</option>
                        </select>
                    </div>
                    <div class="vs08s-field">
                        <label>Valeur de la marge</label>
                        <input type="number" name="vs08s[marge_valeur]" value="<?php echo esc_attr($marge_valeur); ?>" step="0.01" min="0">
                        <p class="vs08s-hint">Ex: 15 pour 15% ou 50 pour 50€/pers</p>
                    </div>
                    <div class="vs08s-field">
                        <label>Acompte (%)</label>
                        <input type="number" name="vs08s[acompte_pct]" value="<?php echo esc_attr($acompte_pct); ?>" min="0" max="100">
                    </div>
                </div>
                <div class="vs08s-field" style="max-width:200px">
                    <label>Délai solde (jours avant départ)</label>
                    <input type="number" name="vs08s[delai_solde]" value="<?php echo esc_attr($delai_solde); ?>" min="1">
                </div>
            </div>
        </div>

        <!-- ═══ CONTENU ═══ -->
        <div class="vs08s-panel" id="panel-contenu">
            <div class="vs08s-row">
                <div class="vs08s-field">
                    <label>✅ Ce qui est inclus (une ligne par item)</label>
                    <textarea name="vs08s[inclus]" rows="8"><?php echo esc_textarea($inclus); ?></textarea>
                </div>
                <div class="vs08s-field">
                    <label>❌ Ce qui n'est pas inclus</label>
                    <textarea name="vs08s[non_inclus]" rows="8"><?php echo esc_textarea($non_inclus); ?></textarea>
                </div>
            </div>
        </div>

        <!-- ═══ CONDITIONS ═══ -->
        <div class="vs08s-panel" id="panel-conditions">
            <div class="vs08s-field">
                <label>Conditions d'annulation (texte libre)</label>
                <textarea name="vs08s[annulation_texte]" rows="6" placeholder="Ex: Annulation gratuite jusqu'à 30 jours avant le départ..."><?php echo esc_textarea($annulation_texte); ?></textarea>
            </div>
            <h3>Paliers d'annulation</h3>
            <div id="vs08s-annulation-list">
            <?php if (!empty($annulation) && is_array($annulation)): foreach ($annulation as $ki => $pal): ?>
                <div class="vs08s-row cols-3" style="align-items:end">
                    <div class="vs08s-field"><label>Jours avant départ</label><input type="number" name="vs08s[annulation][<?php echo $ki; ?>][jours]" value="<?php echo esc_attr($pal['jours'] ?? ''); ?>"></div>
                    <div class="vs08s-field"><label>% retenu</label><input type="number" name="vs08s[annulation][<?php echo $ki; ?>][pct]" value="<?php echo esc_attr($pal['pct'] ?? ''); ?>"></div>
                    <div class="vs08s-field"><button type="button" class="vs08s-btn" style="background:#dc2626;color:#fff" onclick="this.closest('.vs08s-row').remove()">✕ Supprimer</button></div>
                </div>
            <?php endforeach; endif; ?>
            </div>
            <button type="button" class="vs08s-btn vs08s-btn-add" id="vs08s-add-annulation">+ Ajouter un palier</button>
        </div>

        <script>
        (function(){
            // Tabs
            document.querySelectorAll('.vs08s-tab').forEach(function(tab){
                tab.addEventListener('click', function(){
                    document.querySelectorAll('.vs08s-tab').forEach(function(t){t.classList.remove('active')});
                    document.querySelectorAll('.vs08s-panel').forEach(function(p){p.classList.remove('active')});
                    tab.classList.add('active');
                    document.getElementById('panel-'+tab.dataset.panel).classList.add('active');
                });
            });

            // Add aeroport
            var aeroIdx = <?php echo max(count($aeroports), 0); ?>;
            document.getElementById('vs08s-add-aeroport').addEventListener('click', function(){
                var html = '<div class="vs08s-aeroport-block"><button type="button" class="vs08s-aeroport-remove" onclick="this.parentElement.remove()">✕</button>'
                    + '<div class="vs08s-row cols-3">'
                    + '<div class="vs08s-field"><label>Code IATA</label><input type="text" name="vs08s[aeroports]['+aeroIdx+'][code]" style="text-transform:uppercase"></div>'
                    + '<div class="vs08s-field"><label>Ville</label><input type="text" name="vs08s[aeroports]['+aeroIdx+'][ville]"></div>'
                    + '<div class="vs08s-field"><label>Supplément vol /pers (€)</label><input type="number" name="vs08s[aeroports]['+aeroIdx+'][supplement]" value="0" step="0.01"></div>'
                    + '</div></div>';
                document.getElementById('vs08s-aeroports-list').insertAdjacentHTML('beforeend', html);
                aeroIdx++;
            });

            // Add annulation
            var annulIdx = <?php echo max(count($annulation), 0); ?>;
            document.getElementById('vs08s-add-annulation').addEventListener('click', function(){
                var html = '<div class="vs08s-row cols-3" style="align-items:end">'
                    + '<div class="vs08s-field"><label>Jours avant départ</label><input type="number" name="vs08s[annulation]['+annulIdx+'][jours]"></div>'
                    + '<div class="vs08s-field"><label>% retenu</label><input type="number" name="vs08s[annulation]['+annulIdx+'][pct]"></div>'
                    + '<div class="vs08s-field"><button type="button" class="vs08s-btn" style="background:#dc2626;color:#fff" onclick="this.closest(\'.vs08s-row\').remove()">✕ Supprimer</button></div>'
                    + '</div>';
                document.getElementById('vs08s-annulation-list').insertAdjacentHTML('beforeend', html);
                annulIdx++;
            });
        })();
        </script>
        <?php
    }

    public static function save($post_id, $post) {
        if (!isset($_POST['vs08s_nonce']) || !wp_verify_nonce($_POST['vs08s_nonce'], 'vs08s_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== 'vs08_sejour') return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (!isset($_POST['vs08s'])) return;

        $raw = wp_unslash($_POST['vs08s']);
        if (!is_array($raw)) return;

        $m = [];
        $m['destination']       = sanitize_text_field($raw['destination'] ?? '');
        $m['pays']              = sanitize_text_field($raw['pays'] ?? '');
        $m['flag']              = sanitize_text_field($raw['flag'] ?? '');
        $m['duree']             = max(1, intval($raw['duree'] ?? 7));
        $m['duree_jours']       = max(1, intval($raw['duree_jours'] ?? 8));
        $m['hotel_nom']         = sanitize_text_field($raw['hotel_nom'] ?? '');
        $m['hotel_etoiles']     = min(5, max(1, intval($raw['hotel_etoiles'] ?? 5)));
        $m['hotel_code']        = sanitize_text_field($raw['hotel_code'] ?? '');
        $m['pension']           = sanitize_text_field($raw['pension'] ?? 'ai');
        $m['iata_dest']         = strtoupper(sanitize_text_field($raw['iata_dest'] ?? ''));
        $m['transfert_type']    = sanitize_text_field($raw['transfert_type'] ?? 'groupes');
        $m['transfert_prix']    = floatval($raw['transfert_prix'] ?? 0);
        $m['marge_type']        = sanitize_text_field($raw['marge_type'] ?? 'pourcentage');
        $m['marge_valeur']      = floatval($raw['marge_valeur'] ?? 15);
        $m['acompte_pct']       = min(100, max(0, floatval($raw['acompte_pct'] ?? 30)));
        $m['delai_solde']       = max(1, intval($raw['delai_solde'] ?? 30));
        $m['statut']            = sanitize_text_field($raw['statut'] ?? 'actif');
        $m['badge']             = sanitize_text_field($raw['badge'] ?? '');
        $m['description_courte'] = sanitize_textarea_field($raw['description_courte'] ?? '');
        $m['inclus']            = sanitize_textarea_field($raw['inclus'] ?? '');
        $m['non_inclus']        = sanitize_textarea_field($raw['non_inclus'] ?? '');
        $m['annulation_texte']  = sanitize_textarea_field($raw['annulation_texte'] ?? '');
        $m['prix_bagage_soute'] = floatval($raw['prix_bagage_soute'] ?? 0);
        $m['prix_bagage_cabine'] = floatval($raw['prix_bagage_cabine'] ?? 0);

        // Galerie
        $galerie_raw = $raw['galerie_raw'] ?? '';
        $m['galerie'] = array_values(array_filter(array_map('trim', explode("\n", $galerie_raw))));

        // Codes hôtel multiples
        $hotel_codes = $raw['hotel_codes'] ?? [];
        $m['hotel_codes'] = array_values(array_filter(array_map('sanitize_text_field', is_array($hotel_codes) ? $hotel_codes : [])));

        // Aéroports
        $aeroports = $raw['aeroports'] ?? [];
        $m['aeroports'] = [];
        if (is_array($aeroports)) {
            foreach ($aeroports as $a) {
                $code = strtoupper(sanitize_text_field($a['code'] ?? ''));
                if (empty($code)) continue;
                $m['aeroports'][] = [
                    'code'       => $code,
                    'ville'      => sanitize_text_field($a['ville'] ?? ''),
                    'supplement' => floatval($a['supplement'] ?? 0),
                ];
            }
        }

        // Paliers d'annulation
        $annulation = $raw['annulation'] ?? [];
        $m['annulation'] = [];
        if (is_array($annulation)) {
            foreach ($annulation as $pal) {
                $jours = intval($pal['jours'] ?? 0);
                $pct = intval($pal['pct'] ?? 0);
                if ($jours > 0 && $pct > 0) {
                    $m['annulation'][] = ['jours' => $jours, 'pct' => $pct];
                }
            }
            usort($m['annulation'], function($a, $b) { return $b['jours'] - $a['jours']; });
        }

        update_post_meta($post_id, '_vs08s_meta', $m);
    }
}
