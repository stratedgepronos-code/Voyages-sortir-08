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

            <!-- ── MODULE IA : Recherche par nom ── -->
            <?php $claude_ok = class_exists('VS08V_HotelScanner'); ?>
            <?php if ($claude_ok): ?>
            <div style="background:linear-gradient(135deg,#0f2424,#1a4a4a);border-radius:14px;padding:20px;margin-bottom:20px">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <span style="font-size:22px">🤖</span>
                    <div>
                        <h3 style="color:#fff;margin:0;font-size:15px;font-weight:700">Remplir avec l'IA</h3>
                        <p style="color:rgba(255,255,255,.6);font-size:12px;margin:0">Entrez le nom de l'hôtel et la destination, l'IA remplit automatiquement la description, les équipements et les infos pratiques.</p>
                    </div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px">
                    <input type="text" id="vs08s-ia-hotel-name" placeholder="Nom de l'hôtel (ex: Radisson Blu Djerba)" value="<?php echo esc_attr($hotel_nom); ?>" style="flex:2;min-width:200px;background:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit">
                    <input type="text" id="vs08s-ia-destination" placeholder="Destination (ex: Djerba, Tunisie)" value="<?php echo esc_attr($destination . ($pays ? ', ' . $pays : '')); ?>" style="flex:1;min-width:150px;background:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit">
                    <button type="button" id="vs08s-ia-btn" style="background:#59b7b7;color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap">
                        🔍 <span id="vs08s-ia-btn-txt">Rechercher et remplir</span>
                    </button>
                </div>
                <div id="vs08s-ia-status" style="display:none;margin-top:8px;padding:8px 12px;border-radius:8px;font-size:12px"></div>
            </div>
            <?php endif; ?>

            <div class="vs08s-section">
                <h3>🏨 Hôtel principal</h3>
                <div class="vs08s-row cols-3">
                    <div class="vs08s-field">
                        <label>Nom de l'hôtel</label>
                        <input type="text" name="vs08s[hotel_nom]" id="vs08s-hotel-nom" value="<?php echo esc_attr($hotel_nom); ?>" placeholder="Ex: Radisson Blu Resort">
                    </div>
                    <div class="vs08s-field">
                        <label>Étoiles</label>
                        <select name="vs08s[hotel_etoiles]" id="vs08s-hotel-etoiles">
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
                <div class="vs08s-row">
                    <div class="vs08s-field">
                        <label>Adresse de l'hôtel</label>
                        <input type="text" name="vs08s[hotel_adresse]" id="vs08s-hotel-adresse" value="<?php echo esc_attr($m['hotel_adresse'] ?? ''); ?>" placeholder="Ex: Zone Touristique, Midoun, Djerba">
                    </div>
                    <div class="vs08s-field">
                        <label>URL Google Maps embed</label>
                        <input type="text" name="vs08s[hotel_map_url]" id="vs08s-hotel-map-url" value="<?php echo esc_attr($m['hotel_map_url'] ?? ''); ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                        <p class="vs08s-hint">Google Maps → Partager → Intégrer → copier l'URL src="..."</p>
                    </div>
                </div>
                <div class="vs08s-field">
                    <label>Description de l'hôtel</label>
                    <textarea name="vs08s[hotel_description]" id="vs08s-hotel-desc" rows="5" placeholder="L'IA remplira automatiquement ce champ..."><?php echo esc_textarea($m['hotel_description'] ?? ''); ?></textarea>
                </div>
                <div class="vs08s-field">
                    <label>Équipements & Services (un par ligne)</label>
                    <textarea name="vs08s[hotel_equipements]" id="vs08s-hotel-equip" rows="5" placeholder="Piscine extérieure&#10;Spa & Hammam&#10;Restaurant buffet&#10;Animation en soirée&#10;WiFi gratuit&#10;Salle de sport&#10;Club enfants"><?php echo esc_textarea($m['hotel_equipements'] ?? ''); ?></textarea>
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

            <div class="vs08s-row cols-3">
                <div class="vs08s-field">
                    <label>Code IATA destination</label>
                    <input type="text" name="vs08s[iata_dest]" value="<?php echo esc_attr($iata_dest); ?>" placeholder="DJE, HRG, AGP..." style="text-transform:uppercase">
                </div>
                <div class="vs08s-field">
                    <label>Ville arrivée</label>
                    <input type="text" name="vs08s[ville_arrivee]" value="<?php echo esc_attr($m['ville_arrivee'] ?? ''); ?>" placeholder="Djerba, Hurghada...">
                </div>
                <div class="vs08s-field">
                    <label>Type transport</label>
                    <select name="vs08s[transport_type]">
                        <option value="vol" <?php selected($m['transport_type'] ?? 'vol', 'vol'); ?>>✈️ Vol inclus</option>
                        <option value="vol_option" <?php selected($m['transport_type'] ?? 'vol', 'vol_option'); ?>>✈️ Vol en option</option>
                        <option value="sans_vol" <?php selected($m['transport_type'] ?? 'vol', 'sans_vol'); ?>>🏨 Séjour seul (sans vol)</option>
                    </select>
                </div>
            </div>

            <div class="vs08s-row" style="margin-top:8px;align-items:flex-end">
                <div class="vs08s-field">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
                        <input type="checkbox" name="vs08s[vol_escales_autorisees]" value="1" <?php checked(!empty($m['vol_escales_autorisees'])); ?>>
                        Autoriser escales avec attente limitée
                    </label>
                    <p class="vs08s-hint">Par défaut : vols directs uniquement. Si coché : max 1 escale par tronçon.</p>
                </div>
                <div class="vs08s-field" style="max-width:200px">
                    <label>Attente max (h) entre vols</label>
                    <input type="number" name="vs08s[vol_escale_max_heures]" value="<?php echo esc_attr($m['vol_escale_max_heures'] ?? 5); ?>" min="1" max="24" step="0.5">
                </div>
            </div>

            <div class="vs08s-row" style="margin-top:12px">
                <div class="vs08s-field">
                    <label>Prix bagage soute /pers (€)</label>
                    <input type="number" name="vs08s[prix_bagage_soute]" value="<?php echo esc_attr($prix_bagage_soute); ?>" step="0.01" min="0">
                </div>
                <div class="vs08s-field">
                    <label>Prix bagage cabine /pers (€)</label>
                    <input type="number" name="vs08s[prix_bagage_cabine]" value="<?php echo esc_attr($prix_bagage_cabine); ?>" step="0.01" min="0">
                </div>
            </div>

            <!-- PÉRIODES FERMÉES -->
            <div class="vs08s-section" style="background:#fef2f2;border:1px solid #fecaca;margin-top:20px">
                <h3 style="color:#991b1b">🚫 Dates non disponibles à la vente</h3>
                <p class="vs08s-hint" style="color:#7f1d1d">Les dates comprises dans ces plages ne seront <strong>pas sélectionnables</strong> sur le calendrier (tous les aéroports).</p>
                <div id="vs08s-periodes-fermees">
                    <?php
                    $pfv = $m['periodes_fermees_vente'] ?? [];
                    if (is_array($pfv)):
                    foreach ($pfv as $pi => $pf):
                    ?>
                    <div class="vs08s-pfv-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                        <span style="font-size:12px;color:#991b1b;font-weight:600">Du</span>
                        <input type="date" name="vs08s[periodes_fermees_vente][<?php echo $pi; ?>][date_debut]" value="<?php echo esc_attr($pf['date_debut'] ?? ''); ?>" style="flex:1">
                        <span style="font-size:12px;color:#991b1b;font-weight:600">au</span>
                        <input type="date" name="vs08s[periodes_fermees_vente][<?php echo $pi; ?>][date_fin]" value="<?php echo esc_attr($pf['date_fin'] ?? ''); ?>" style="flex:1">
                        <button type="button" style="background:#dc2626;color:#fff;border:none;border-radius:6px;padding:4px 10px;cursor:pointer" onclick="this.closest('.vs08s-pfv-row').remove()">✕</button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" class="vs08s-btn" style="background:#dc2626;color:#fff;margin-top:8px" id="vs08s-add-pfv">+ Période non disponible</button>
            </div>

            <!-- AÉROPORTS DE DÉPART -->
            <div class="vs08s-section" style="margin-top:20px">
                <h3>✈️ Aéroports de départ</h3>
                <p class="vs08s-hint" style="margin-bottom:12px">Chaque aéroport peut avoir plusieurs <strong>périodes de vol</strong> (dates d'ouverture). Chaque période a ses propres <strong>jours de départ</strong> (ex: lundi et jeudi). En dehors de ces périodes, le calendrier est fermé pour cet aéroport.</p>

                <div id="vs08s-aeroports-list">
                <?php
                $jours_semaine = [1=>'Lun',2=>'Mar',3=>'Mer',4=>'Jeu',5=>'Ven',6=>'Sam',7=>'Dim'];
                if (!empty($aeroports)):
                foreach ($aeroports as $ai => $a):
                    $periodes_vol = $a['periodes_vol'] ?? [];
                    if (empty($periodes_vol)) $periodes_vol = [['date_debut'=>'','date_fin'=>'']];
                    $jours_direct = $a['jours_direct'] ?? [1,2,3,4,5,6,7];
                ?>
                <div class="vs08s-aeroport-block" data-aero-idx="<?php echo $ai; ?>">
                    <button type="button" class="vs08s-aeroport-remove" onclick="this.parentElement.remove()">✕</button>
                    <div class="vs08s-row cols-3">
                        <div class="vs08s-field">
                            <label>Code IATA</label>
                            <input type="text" name="vs08s[aeroports][<?php echo $ai; ?>][code]" value="<?php echo esc_attr(strtoupper($a['code'] ?? '')); ?>" style="text-transform:uppercase" placeholder="CDG">
                        </div>
                        <div class="vs08s-field">
                            <label>Ville</label>
                            <input type="text" name="vs08s[aeroports][<?php echo $ai; ?>][ville]" value="<?php echo esc_attr($a['ville'] ?? ''); ?>" placeholder="Paris Charles de Gaulle">
                        </div>
                        <div class="vs08s-field">
                            <label>Supplément vol /pers (€)</label>
                            <input type="number" name="vs08s[aeroports][<?php echo $ai; ?>][supplement]" value="<?php echo esc_attr($a['supplement'] ?? 0); ?>" step="0.01">
                        </div>
                    </div>

                    <!-- Périodes de vol -->
                    <div style="margin-top:12px;padding-left:10px;border-left:3px solid #59b7b7">
                        <div style="font-size:11px;font-weight:700;color:#374151;margin-bottom:8px">📅 Périodes de vol ouvertes — en dehors = fermé</div>
                        <div class="vs08s-periodes-vol-list">
                        <?php foreach ($periodes_vol as $pi => $pv):
                            $pv_jours = $pv['jours_direct'] ?? [];
                            if (empty($pv_jours)) $pv_jours = [1,2,3,4,5,6,7];
                        ?>
                            <div class="vs08s-periode-row" style="margin-bottom:10px;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px">
                                <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                                    <span style="font-size:12px;color:#6b7280">Du</span>
                                    <input type="date" name="vs08s[aeroports][<?php echo $ai; ?>][periodes_vol][<?php echo $pi; ?>][date_debut]" value="<?php echo esc_attr($pv['date_debut'] ?? ''); ?>" style="flex:1">
                                    <span style="font-size:12px;color:#6b7280">au</span>
                                    <input type="date" name="vs08s[aeroports][<?php echo $ai; ?>][periodes_vol][<?php echo $pi; ?>][date_fin]" value="<?php echo esc_attr($pv['date_fin'] ?? ''); ?>" style="flex:1">
                                    <button type="button" style="background:#f3f4f6;border:1px solid #d1d5db;border-radius:6px;padding:4px 10px;cursor:pointer;color:#dc2626" onclick="this.closest('.vs08s-periode-row').remove()">✕</button>
                                </div>
                                <div style="font-size:11px;color:#6b7280;margin-bottom:4px">Jours avec vol direct :</div>
                                <div style="display:flex;flex-wrap:wrap;gap:8px 14px">
                                    <?php foreach ($jours_semaine as $jnum => $jlib): ?>
                                    <label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer">
                                        <input type="checkbox" name="vs08s[aeroports][<?php echo $ai; ?>][periodes_vol][<?php echo $pi; ?>][jours_direct][]" value="<?php echo $jnum; ?>" <?php checked(in_array($jnum, $pv_jours)); ?>>
                                        <?php echo $jlib; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <button type="button" class="vs08s-btn vs08s-btn-add vs08s-add-periode" data-aero-idx="<?php echo $ai; ?>" style="font-size:11px;padding:5px 12px">+ Période de vol</button>

                        <div style="font-size:11px;font-weight:700;color:#374151;margin:12px 0 6px">📆 Jours de départ par défaut (si aucune période spécifique)</div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px 14px">
                            <?php foreach ($jours_semaine as $jnum => $jlib): ?>
                            <label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer">
                                <input type="checkbox" name="vs08s[aeroports][<?php echo $ai; ?>][jours_direct][]" value="<?php echo $jnum; ?>" <?php checked(in_array($jnum, $jours_direct)); ?>>
                                <?php echo $jlib; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
                </div>

                <button type="button" class="vs08s-btn vs08s-btn-add" id="vs08s-add-aeroport" style="margin-top:10px">+ Ajouter un aéroport</button>
            </div>
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

            // Add aeroport (complet avec périodes + jours)
            var aeroIdx = <?php echo max(count($aeroports), 0); ?>;
            var joursLabels = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];

            function makeJoursCheckboxes(prefix, allChecked) {
                var html = '';
                for (var j = 1; j <= 7; j++) {
                    html += '<label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer"><input type="checkbox" name="' + prefix + '[]" value="' + j + '"' + (allChecked ? ' checked' : '') + '> ' + joursLabels[j-1] + '</label>';
                }
                return html;
            }

            function makePeriodeRow(aeroI, periodeI) {
                return '<div class="vs08s-periode-row" style="margin-bottom:10px;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px">'
                    + '<div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">'
                    + '<span style="font-size:12px;color:#6b7280">Du</span>'
                    + '<input type="date" name="vs08s[aeroports]['+aeroI+'][periodes_vol]['+periodeI+'][date_debut]" style="flex:1">'
                    + '<span style="font-size:12px;color:#6b7280">au</span>'
                    + '<input type="date" name="vs08s[aeroports]['+aeroI+'][periodes_vol]['+periodeI+'][date_fin]" style="flex:1">'
                    + '<button type="button" style="background:#f3f4f6;border:1px solid #d1d5db;border-radius:6px;padding:4px 10px;cursor:pointer;color:#dc2626" onclick="this.closest(\'.vs08s-periode-row\').remove()">✕</button>'
                    + '</div>'
                    + '<div style="font-size:11px;color:#6b7280;margin-bottom:4px">Jours avec vol direct :</div>'
                    + '<div style="display:flex;flex-wrap:wrap;gap:8px 14px">' + makeJoursCheckboxes('vs08s[aeroports]['+aeroI+'][periodes_vol]['+periodeI+'][jours_direct]', true) + '</div>'
                    + '</div>';
            }

            document.getElementById('vs08s-add-aeroport').addEventListener('click', function(){
                var html = '<div class="vs08s-aeroport-block" data-aero-idx="'+aeroIdx+'">'
                    + '<button type="button" class="vs08s-aeroport-remove" onclick="this.parentElement.remove()">✕</button>'
                    + '<div class="vs08s-row cols-3">'
                    + '<div class="vs08s-field"><label>Code IATA</label><input type="text" name="vs08s[aeroports]['+aeroIdx+'][code]" style="text-transform:uppercase" placeholder="CDG"></div>'
                    + '<div class="vs08s-field"><label>Ville</label><input type="text" name="vs08s[aeroports]['+aeroIdx+'][ville]" placeholder="Paris CDG"></div>'
                    + '<div class="vs08s-field"><label>Supplément vol /pers (€)</label><input type="number" name="vs08s[aeroports]['+aeroIdx+'][supplement]" value="0" step="0.01"></div>'
                    + '</div>'
                    + '<div style="margin-top:12px;padding-left:10px;border-left:3px solid #59b7b7">'
                    + '<div style="font-size:11px;font-weight:700;color:#374151;margin-bottom:8px">📅 Périodes de vol ouvertes</div>'
                    + '<div class="vs08s-periodes-vol-list">' + makePeriodeRow(aeroIdx, 0) + '</div>'
                    + '<button type="button" class="vs08s-btn vs08s-btn-add vs08s-add-periode" data-aero-idx="'+aeroIdx+'" style="font-size:11px;padding:5px 12px">+ Période de vol</button>'
                    + '<div style="font-size:11px;font-weight:700;color:#374151;margin:12px 0 6px">📆 Jours par défaut</div>'
                    + '<div style="display:flex;flex-wrap:wrap;gap:8px 14px">' + makeJoursCheckboxes('vs08s[aeroports]['+aeroIdx+'][jours_direct]', true) + '</div>'
                    + '</div></div>';
                document.getElementById('vs08s-aeroports-list').insertAdjacentHTML('beforeend', html);
                aeroIdx++;
            });

            // Ajouter période de vol à un aéroport existant
            document.getElementById('vs08s-aeroports-list').addEventListener('click', function(ev) {
                var btn = ev.target && ev.target.closest && ev.target.closest('.vs08s-add-periode');
                if (!btn) return;
                ev.preventDefault();
                var block = btn.closest('.vs08s-aeroport-block');
                if (!block) return;
                var idx = block.getAttribute('data-aero-idx');
                var list = block.querySelector('.vs08s-periodes-vol-list');
                if (!list) return;
                var p = list.querySelectorAll('.vs08s-periode-row').length;
                list.insertAdjacentHTML('beforeend', makePeriodeRow(idx, p));
            });

            // IA Auto-fill hôtel
            var iaBtn = document.getElementById('vs08s-ia-btn');
            if (iaBtn) {
                iaBtn.addEventListener('click', function() {
                    var name = document.getElementById('vs08s-ia-hotel-name').value.trim();
                    var dest = document.getElementById('vs08s-ia-destination').value.trim();
                    var status = document.getElementById('vs08s-ia-status');
                    var btnTxt = document.getElementById('vs08s-ia-btn-txt');
                    if (!name) { alert('Entrez le nom de l\'hôtel.'); return; }

                    iaBtn.disabled = true;
                    btnTxt.textContent = '🔄 Recherche en cours...';
                    status.style.display = 'block';
                    status.style.background = 'rgba(89,183,183,.2)';
                    status.style.color = '#7ecece';
                    status.textContent = '🔍 L\'IA recherche les informations de « ' + name + ' » à « ' + dest + ' »...';

                    var fd = new FormData();
                    fd.append('action', 'vs08v_scan_hotel_by_name');
                    fd.append('nonce', '<?php echo wp_create_nonce("vs08v_scan_hotel"); ?>');
                    fd.append('hotel_name', name);
                    fd.append('destination', dest);

                    fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        iaBtn.disabled = false;
                        btnTxt.textContent = '🔍 Rechercher et remplir';
                        if (res.success && res.data) {
                            var d = res.data;
                            // Remplir les champs
                            if (d.nom) document.querySelector('[name="vs08s[hotel_nom]"]').value = d.nom;
                            if (d.etoiles) document.querySelector('[name="vs08s[hotel_etoiles]"]').value = d.etoiles;
                            if (d.adresse) document.getElementById('vs08s-hotel-adresse').value = d.adresse;
                            if (d.description) document.getElementById('vs08s-hotel-desc').value = d.description;
                            if (d.map_embed_url) document.getElementById('vs08s-hotel-map-url').value = d.map_embed_url;

                            // Équipements
                            if (d.equipements && Array.isArray(d.equipements)) {
                                document.getElementById('vs08s-hotel-equip').value = d.equipements.join('\n');
                            }

                            // Description courte du séjour (si vide)
                            var descCourte = document.querySelector('[name="vs08s[description_courte]"]');
                            if (descCourte && !descCourte.value.trim() && d.accroche) {
                                descCourte.value = d.accroche;
                            }

                            // Inclus (si vide)
                            var inclus = document.querySelector('[name="vs08s[inclus]"]');
                            if (inclus && !inclus.value.trim() && d.inclus) {
                                inclus.value = d.inclus;
                            }

                            status.style.background = 'rgba(34,197,94,.2)';
                            status.style.color = '#22c55e';
                            status.textContent = '✅ Informations remplies avec succès ! Vérifiez et ajustez si besoin.';
                        } else {
                            status.style.background = 'rgba(220,38,38,.2)';
                            status.style.color = '#ef4444';
                            status.textContent = '❌ ' + (res.data || 'Aucune info trouvée. Essayez avec un nom plus précis.');
                        }
                    })
                    .catch(function(err) {
                        iaBtn.disabled = false;
                        btnTxt.textContent = '🔍 Rechercher et remplir';
                        status.style.background = 'rgba(220,38,38,.2)';
                        status.style.color = '#ef4444';
                        status.textContent = '❌ Erreur réseau : ' + err.message;
                    });
                });
            }

            // Périodes fermées
            var pfvIdx = <?php echo max(count($pfv), 0); ?>;
            document.getElementById('vs08s-add-pfv').addEventListener('click', function(){
                var html = '<div class="vs08s-pfv-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px">'
                    + '<span style="font-size:12px;color:#991b1b;font-weight:600">Du</span>'
                    + '<input type="date" name="vs08s[periodes_fermees_vente]['+pfvIdx+'][date_debut]" style="flex:1">'
                    + '<span style="font-size:12px;color:#991b1b;font-weight:600">au</span>'
                    + '<input type="date" name="vs08s[periodes_fermees_vente]['+pfvIdx+'][date_fin]" style="flex:1">'
                    + '<button type="button" style="background:#dc2626;color:#fff;border:none;border-radius:6px;padding:4px 10px;cursor:pointer" onclick="this.closest(\'.vs08s-pfv-row\').remove()">✕</button>'
                    + '</div>';
                document.getElementById('vs08s-periodes-fermees').insertAdjacentHTML('beforeend', html);
                pfvIdx++;
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
        $m['hotel_adresse']     = sanitize_text_field($raw['hotel_adresse'] ?? '');
        $m['hotel_map_url']     = esc_url_raw($raw['hotel_map_url'] ?? '');
        $m['hotel_description'] = sanitize_textarea_field($raw['hotel_description'] ?? '');
        $m['hotel_equipements'] = sanitize_textarea_field($raw['hotel_equipements'] ?? '');
        $m['pension']           = sanitize_text_field($raw['pension'] ?? 'ai');
        $m['iata_dest']         = strtoupper(sanitize_text_field($raw['iata_dest'] ?? ''));
        $m['ville_arrivee']     = sanitize_text_field($raw['ville_arrivee'] ?? '');
        $m['transport_type']    = sanitize_text_field($raw['transport_type'] ?? 'vol');
        $m['vol_escales_autorisees'] = !empty($raw['vol_escales_autorisees']) ? 1 : 0;
        $h_esc = floatval($raw['vol_escale_max_heures'] ?? 5);
        $m['vol_escale_max_heures'] = min(24, max(1, $h_esc ?: 5));
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

        // Périodes fermées à la vente
        $pfv = $raw['periodes_fermees_vente'] ?? [];
        $m['periodes_fermees_vente'] = [];
        if (is_array($pfv)) {
            foreach ($pfv as $p) {
                $deb = sanitize_text_field($p['date_debut'] ?? '');
                $fin = sanitize_text_field($p['date_fin'] ?? '');
                if ($deb || $fin) {
                    $m['periodes_fermees_vente'][] = ['date_debut' => $deb, 'date_fin' => $fin];
                }
            }
        }

        // Aéroports (avec périodes de vol + jours directs)
        $aeroports = $raw['aeroports'] ?? [];
        $m['aeroports'] = [];
        if (is_array($aeroports)) {
            foreach ($aeroports as $a) {
                $code = strtoupper(sanitize_text_field($a['code'] ?? ''));
                if (empty($code)) continue;

                // Périodes de vol par aéroport
                $periodes_vol = [];
                if (isset($a['periodes_vol']) && is_array($a['periodes_vol'])) {
                    foreach ($a['periodes_vol'] as $pv) {
                        $pd = sanitize_text_field($pv['date_debut'] ?? '');
                        $pf = sanitize_text_field($pv['date_fin'] ?? '');
                        if (!$pd && !$pf) continue;
                        $jd = [];
                        if (isset($pv['jours_direct']) && is_array($pv['jours_direct'])) {
                            $jd = array_values(array_map('intval', array_filter($pv['jours_direct'])));
                        }
                        $periodes_vol[] = ['date_debut' => $pd, 'date_fin' => $pf, 'jours_direct' => $jd];
                    }
                }

                // Jours directs par défaut
                $jours_direct = [1,2,3,4,5,6,7];
                if (isset($a['jours_direct']) && is_array($a['jours_direct'])) {
                    $jours_direct = array_map('intval', array_values(array_filter($a['jours_direct'])));
                }

                $m['aeroports'][] = [
                    'code'         => $code,
                    'ville'        => sanitize_text_field($a['ville'] ?? ''),
                    'supplement'   => floatval($a['supplement'] ?? 0),
                    'periodes_vol' => $periodes_vol,
                    'jours_direct' => $jours_direct,
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
