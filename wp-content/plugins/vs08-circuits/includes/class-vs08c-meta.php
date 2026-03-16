<?php
if (!defined('ABSPATH')) exit;

class VS08C_Meta {

    const META_KEY = '_vs08c_meta';

    /**
     * Récupère toutes les meta d'un circuit.
     */
    public static function get($post_id) {
        $m = get_post_meta($post_id, self::META_KEY, true);
        return is_array($m) ? $m : [];
    }

    /**
     * Résout le drapeau emoji depuis le pays.
     * Gère aussi le cas où $m['flag'] contient un code ISO (ex: "CY") au lieu d'un emoji.
     */
    public static function resolve_flag($m) {
        $flag = trim((string) ($m['flag'] ?? ''));

        // Si le flag est un emoji valide (commence par un caractère multi-byte), le retourner
        if ($flag && mb_strlen($flag) <= 4 && preg_match('/[\x{1F1E6}-\x{1F1FF}]/u', $flag)) {
            return $flag;
        }

        // Si le flag est un code ISO 2 lettres (ex: "CY", "cy"), le convertir en emoji
        if ($flag && preg_match('/^[a-zA-Z]{2}$/', $flag)) {
            return self::code_to_emoji(strtoupper($flag));
        }

        // Sinon, chercher par le nom du pays
        $pays = $m['pays'] ?? '';
        $map = [
            'Portugal'=>'PT','Espagne'=>'ES','Maroc'=>'MA','Turquie'=>'TR','Irlande'=>'IE',
            'Thaïlande'=>'TH','France'=>'FR','Italie'=>'IT','Grèce'=>'GR','Tunisie'=>'TN',
            'Égypte'=>'EG','Jordanie'=>'JO','Inde'=>'IN','Vietnam'=>'VN','Japon'=>'JP',
            'Cambodge'=>'KH','Sri Lanka'=>'LK','Pérou'=>'PE','Colombie'=>'CO','Mexique'=>'MX',
            'Cuba'=>'CU','Costa Rica'=>'CR','Afrique du Sud'=>'ZA','Tanzanie'=>'TZ','Kenya'=>'KE',
            'Madagascar'=>'MG','Islande'=>'IS','Norvège'=>'NO','Écosse'=>'GB','Croatie'=>'HR',
            'Indonésie'=>'ID','Philippines'=>'PH','Malaisie'=>'MY','Myanmar'=>'MM','Laos'=>'LA',
            'Chine'=>'CN','Corée du Sud'=>'KR','Népal'=>'NP','Iran'=>'IR','Oman'=>'OM',
            'Éthiopie'=>'ET','Namibie'=>'NA','Botswana'=>'BW','Ouganda'=>'UG','Rwanda'=>'RW',
            'Sénégal'=>'SN','Argentine'=>'AR','Chili'=>'CL','Bolivie'=>'BO','Équateur'=>'EC',
            'Brésil'=>'BR','Guatemala'=>'GT','Belize'=>'BZ','Panama'=>'PA',
            'République Dominicaine'=>'DO','États-Unis'=>'US','Canada'=>'CA',
            'Australie'=>'AU','Nouvelle-Zélande'=>'NZ','Émirats arabes unis'=>'AE',
            'Chypre'=>'CY','Malte'=>'MT','Monténégro'=>'ME','Bulgarie'=>'BG','Roumanie'=>'RO',
            'Pologne'=>'PL','République Tchèque'=>'CZ','Slovaquie'=>'SK','Hongrie'=>'HU',
            'Slovénie'=>'SI','Albanie'=>'AL','Serbie'=>'RS','Liban'=>'LB','Israël'=>'IL',
            'Maldives'=>'MV','Maurice'=>'MU','Cap-Vert'=>'CV','Singapour'=>'SG',
            'Allemagne'=>'DE','Autriche'=>'AT','Suisse'=>'CH','Belgique'=>'BE','Pays-Bas'=>'NL',
            'Suède'=>'SE','Finlande'=>'FI','Danemark'=>'DK','Estonie'=>'EE','Lettonie'=>'LV','Lituanie'=>'LT',
            'Royaume-Uni'=>'GB','Angleterre'=>'GB',
        ];
        $code = $map[$pays] ?? '';
        if ($code) return self::code_to_emoji($code);

        return $flag; // Retourner ce qu'on a (même vide)
    }

    /**
     * Convertit un code ISO 2 lettres en emoji drapeau.
     * Ex: "FR" → 🇫🇷, "CY" → 🇨🇾
     */
    public static function code_to_emoji($code) {
        if (strlen($code) < 2) return '';
        $code = strtoupper(substr($code, 0, 2));
        $first  = mb_chr(0x1F1E6 + ord($code[0]) - ord('A'));
        $second = mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
        return $first . $second;
    }

    /**
     * Register all meta boxes.
     */
    public static function register() {
        add_meta_box('vs08c_main', '🗺️ Configuration du Circuit', [__CLASS__, 'render_main'], 'vs08_circuit', 'normal', 'high');
    }

    /**
     * Render the main tabbed metabox.
     */
    public static function render_main($post) {
        wp_nonce_field('vs08c_save', 'vs08c_nonce');
        $m = self::get($post->ID);

        $pays_flags = [
            'Portugal'=>'PT','Espagne'=>'ES','Maroc'=>'MA','Turquie'=>'TR','Irlande'=>'IE',
            'Thaïlande'=>'TH','France'=>'FR','Italie'=>'IT','Grèce'=>'GR','Tunisie'=>'TN',
            'Égypte'=>'EG','Jordanie'=>'JO','Inde'=>'IN','Vietnam'=>'VN','Japon'=>'JP',
            'Cambodge'=>'KH','Sri Lanka'=>'LK','Pérou'=>'PE','Colombie'=>'CO','Mexique'=>'MX',
            'Cuba'=>'CU','Costa Rica'=>'CR','Afrique du Sud'=>'ZA','Tanzanie'=>'TZ','Kenya'=>'KE',
            'Madagascar'=>'MG','Islande'=>'IS','Norvège'=>'NO','Écosse'=>'GB','Croatie'=>'HR',
            'Indonésie'=>'ID','Philippines'=>'PH','Malaisie'=>'MY','Myanmar'=>'MM','Laos'=>'LA',
            'Chine'=>'CN','Corée du Sud'=>'KR','Népal'=>'NP','Iran'=>'IR','Oman'=>'OM',
            'Éthiopie'=>'ET','Namibie'=>'NA','Botswana'=>'BW','Ouganda'=>'UG','Rwanda'=>'RW',
            'Sénégal'=>'SN','Argentine'=>'AR','Chili'=>'CL','Bolivie'=>'BO','Équateur'=>'EC',
            'Brésil'=>'BR','Guatemala'=>'GT','Belize'=>'BZ','Panama'=>'PA',
            'République Dominicaine'=>'DO','Islande'=>'IS','États-Unis'=>'US','Canada'=>'CA',
            'Australie'=>'AU','Nouvelle-Zélande'=>'NZ','Émirats arabes unis'=>'AE',
            'Chypre'=>'CY','Malte'=>'MT','Monténégro'=>'ME','Bulgarie'=>'BG','Roumanie'=>'RO',
            'Pologne'=>'PL','République Tchèque'=>'CZ','Slovaquie'=>'SK','Hongrie'=>'HU',
            'Slovénie'=>'SI','Albanie'=>'AL','Serbie'=>'RS','Liban'=>'LB','Israël'=>'IL',
            'Maldives'=>'MV','Maurice'=>'MU','Cap-Vert'=>'CV','Singapour'=>'SG',
            'Allemagne'=>'DE','Autriche'=>'AT','Suisse'=>'CH','Belgique'=>'BE','Pays-Bas'=>'NL',
            'Suède'=>'SE','Finlande'=>'FI','Danemark'=>'DK','Angleterre'=>'GB','Écosse'=>'GB','Royaume-Uni'=>'GB',
        ];
        ?>
        <div class="vs08c-admin-wrap">
            <!-- Tabs -->
            <nav class="vs08c-tabs">
                <button type="button" class="vs08c-tab active" data-tab="general"><span class="dashicons dashicons-admin-generic"></span> Général</button>
                <button type="button" class="vs08c-tab" data-tab="itinerary"><span class="dashicons dashicons-calendar-alt"></span> Itinéraire</button>
                <button type="button" class="vs08c-tab" data-tab="gallery"><span class="dashicons dashicons-format-gallery"></span> Galerie</button>
                <button type="button" class="vs08c-tab" data-tab="inclus"><span class="dashicons dashicons-yes-alt"></span> Inclus / Non inclus</button>
                <button type="button" class="vs08c-tab" data-tab="pricing"><span class="dashicons dashicons-money-alt"></span> Tarifs & Aéroports</button>
                <button type="button" class="vs08c-tab" data-tab="hotels"><span class="dashicons dashicons-building"></span> Hébergements</button>
                <button type="button" class="vs08c-tab" data-tab="practical"><span class="dashicons dashicons-info"></span> Infos pratiques</button>
            </nav>

            <!-- ═══════ TAB: GÉNÉRAL ═══════ -->
            <div class="vs08c-panel active" data-panel="general">
                <h3 class="vs08c-panel-title">Informations générales du circuit</h3>
                <div class="vs08c-field-row">
                    <div class="vs08c-field vs08c-w2">
                        <label>📝 Description du circuit</label>
                        <?php wp_editor($m['description'] ?? '', 'vs08c_description', [
                            'textarea_name' => 'vs08c[description]',
                            'textarea_rows' => 8,
                            'media_buttons' => true,
                            'teeny'         => false,
                        ]); ?>
                    </div>
                </div>
                <div class="vs08c-field-row">
                    <div class="vs08c-field"><label>🌍 Pays</label>
                        <select name="vs08c[pays]" id="vs08c-pays-select">
                            <option value="">— Choisir —</option>
                            <?php foreach ($pays_flags as $nom => $code): ?>
                            <option value="<?php echo esc_attr($nom); ?>" data-code="<?php echo esc_attr($code); ?>" <?php selected($m['pays'] ?? '', $nom); ?>><?php echo esc_html($nom); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="vs08c-field"><label>🏳️ Drapeau</label>
                        <input type="text" name="vs08c[flag]" id="vs08c-flag-input" value="<?php echo esc_attr($m['flag'] ?? ''); ?>" placeholder="🇲🇦" style="width:80px">
                    </div>
                    <div class="vs08c-field"><label>📍 Destination / Villes</label>
                        <input type="text" name="vs08c[destination]" value="<?php echo esc_attr($m['destination'] ?? ''); ?>" placeholder="Ex: Marrakech, Ouarzazate, Merzouga">
                    </div>
                </div>
                <div class="vs08c-field-row">
                    <div class="vs08c-field"><label>📅 Durée — Jours</label>
                        <input type="number" name="vs08c[duree_jours]" value="<?php echo esc_attr($m['duree_jours'] ?? '8'); ?>" min="1" max="60">
                    </div>
                    <div class="vs08c-field"><label>🌙 Durée — Nuits</label>
                        <input type="number" name="vs08c[duree]" value="<?php echo esc_attr($m['duree'] ?? '7'); ?>" min="0" max="59">
                    </div>
                    <div class="vs08c-field"><label>🗣️ Langue du guide</label>
                        <input type="text" name="vs08c[guide_lang]" value="<?php echo esc_attr($m['guide_lang'] ?? 'Français'); ?>">
                    </div>
                    <div class="vs08c-field"><label>👥 Groupe min</label>
                        <input type="number" name="vs08c[group_min]" value="<?php echo esc_attr($m['group_min'] ?? '2'); ?>" min="1">
                    </div>
                    <div class="vs08c-field"><label>👥 Groupe max</label>
                        <input type="number" name="vs08c[group_max]" value="<?php echo esc_attr($m['group_max'] ?? '20'); ?>" min="1">
                    </div>
                </div>
                <div class="vs08c-field-row">
                    <div class="vs08c-field"><label>🍽️ Pension</label>
                        <select name="vs08c[pension]">
                            <option value="bb" <?php selected($m['pension'] ?? '', 'bb'); ?>>Petit-déjeuner</option>
                            <option value="dp" <?php selected($m['pension'] ?? '', 'dp'); ?>>Demi-pension</option>
                            <option value="pc" <?php selected($m['pension'] ?? '', 'pc'); ?>>Pension complète (hors boissons)</option>
                            <option value="ai" <?php selected($m['pension'] ?? '', 'ai'); ?>>Tout inclus</option>
                            <option value="mixed" <?php selected($m['pension'] ?? '', 'mixed'); ?>>Selon le programme</option>
                        </select>
                    </div>
                    <div class="vs08c-field"><label>🚌 Transport sur place</label>
                        <select name="vs08c[transport]">
                            <option value="bus" <?php selected($m['transport'] ?? '', 'bus'); ?>>Minibus / Bus climatisé</option>
                            <option value="4x4" <?php selected($m['transport'] ?? '', '4x4'); ?>>4x4</option>
                            <option value="voiture" <?php selected($m['transport'] ?? '', 'voiture'); ?>>Voiture de location</option>
                            <option value="train" <?php selected($m['transport'] ?? '', 'train'); ?>>Train</option>
                            <option value="mixed" <?php selected($m['transport'] ?? '', 'mixed'); ?>>Mixte</option>
                        </select>
                    </div>
                    <div class="vs08c-field"><label>🏷️ Badge</label>
                        <select name="vs08c[badge]">
                            <option value="">Aucun</option>
                            <option value="new" <?php selected($m['badge'] ?? '', 'new'); ?>>🆕 Nouveauté</option>
                            <option value="best" <?php selected($m['badge'] ?? '', 'best'); ?>>❤️ Coup de cœur</option>
                            <option value="promo" <?php selected($m['badge'] ?? '', 'promo'); ?>>🔥 Promo</option>
                            <option value="derniere" <?php selected($m['badge'] ?? '', 'derniere'); ?>>⏳ Dernières places</option>
                        </select>
                    </div>
                    <div class="vs08c-field"><label>📊 Statut</label>
                        <select name="vs08c[statut]">
                            <option value="actif" <?php selected($m['statut'] ?? 'actif', 'actif'); ?>>✅ Actif</option>
                            <option value="complet" <?php selected($m['statut'] ?? '', 'complet'); ?>>🔴 Complet</option>
                            <option value="bientot" <?php selected($m['statut'] ?? '', 'bientot'); ?>>🔜 Bientôt disponible</option>
                            <option value="archive" <?php selected($m['statut'] ?? '', 'archive'); ?>>📦 Archivé</option>
                        </select>
                    </div>
                </div>
                <div class="vs08c-field-row">
                    <div class="vs08c-field"><label>✈️ Code IATA destination</label>
                        <input type="text" name="vs08c[iata_dest]" value="<?php echo esc_attr($m['iata_dest'] ?? ''); ?>" placeholder="Ex: RAK" style="text-transform:uppercase">
                        <p class="vs08c-help">Code de l'aéroport d'arrivée pour la recherche de vols</p>
                    </div>
                    <div class="vs08c-field"><label>🔗 Thématiques</label>
                        <input type="text" name="vs08c[themes]" value="<?php echo esc_attr($m['themes'] ?? ''); ?>" placeholder="Culture, Nature, Aventure, Désert...">
                        <p class="vs08c-help">Séparés par des virgules</p>
                    </div>
                </div>
            </div>

            <!-- ═══════ TAB: ITINÉRAIRE ═══════ -->
            <div class="vs08c-panel" data-panel="itinerary">
                <h3 class="vs08c-panel-title">Programme jour par jour</h3>
                <p class="vs08c-help-block">Décrivez chaque jour du circuit. Vous pouvez réorganiser les jours par glisser-déposer.</p>
                <div id="vs08c-itinerary-list" class="vs08c-repeater">
                    <?php
                    $jours = $m['jours'] ?? [];
                    if (empty($jours)) $jours = [['titre' => '', 'description' => '', 'repas' => '', 'nuit' => '', 'transport' => '', 'image' => '']];
                    foreach ($jours as $i => $jour):
                    ?>
                    <div class="vs08c-repeater-item vs08c-jour" data-index="<?php echo $i; ?>">
                        <div class="vs08c-jour-header">
                            <span class="vs08c-jour-handle dashicons dashicons-move"></span>
                            <span class="vs08c-jour-num">Jour <?php echo $i + 1; ?></span>
                            <button type="button" class="vs08c-jour-toggle dashicons dashicons-arrow-down-alt2"></button>
                            <button type="button" class="vs08c-remove-jour" title="Supprimer ce jour">✕</button>
                        </div>
                        <div class="vs08c-jour-body">
                            <div class="vs08c-field-row">
                                <div class="vs08c-field vs08c-w2"><label>📍 Titre du jour</label>
                                    <input type="text" name="vs08c[jours][<?php echo $i; ?>][titre]" value="<?php echo esc_attr($jour['titre'] ?? ''); ?>" placeholder="Ex: Marrakech → Ouarzazate via le Col du Tichka">
                                </div>
                            </div>
                            <div class="vs08c-field-row">
                                <div class="vs08c-field vs08c-w2"><label>📝 Description</label>
                                    <textarea name="vs08c[jours][<?php echo $i; ?>][description]" rows="4" placeholder="Décrivez les activités, visites et moments forts de cette journée..."><?php echo esc_textarea($jour['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="vs08c-field-row">
                                <div class="vs08c-field"><label>🍽️ Repas inclus</label>
                                    <input type="text" name="vs08c[jours][<?php echo $i; ?>][repas]" value="<?php echo esc_attr($jour['repas'] ?? ''); ?>" placeholder="Ex: Petit-déjeuner, Déjeuner, Dîner">
                                </div>
                                <div class="vs08c-field"><label>🏨 Nuit à</label>
                                    <input type="text" name="vs08c[jours][<?php echo $i; ?>][nuit]" value="<?php echo esc_attr($jour['nuit'] ?? ''); ?>" placeholder="Ex: Riad à Ouarzazate">
                                </div>
                                <div class="vs08c-field"><label>🚌 Transport</label>
                                    <input type="text" name="vs08c[jours][<?php echo $i; ?>][transport]" value="<?php echo esc_attr($jour['transport'] ?? ''); ?>" placeholder="Ex: 4h de route en minibus">
                                </div>
                            </div>
                            <div class="vs08c-field-row">
                                <div class="vs08c-field"><label>🏷️ Tags (optionnel)</label>
                                    <input type="text" name="vs08c[jours][<?php echo $i; ?>][tags]" value="<?php echo esc_attr($jour['tags'] ?? ''); ?>" placeholder="Culture, UNESCO, Randonnée...">
                                </div>
                                <div class="vs08c-field"><label>🖼️ Image du jour</label>
                                    <div class="vs08c-image-field">
                                        <input type="hidden" name="vs08c[jours][<?php echo $i; ?>][image]" value="<?php echo esc_attr($jour['image'] ?? ''); ?>" class="vs08c-img-input">
                                        <div class="vs08c-img-preview"><?php if (!empty($jour['image'])): ?><img src="<?php echo esc_url($jour['image']); ?>"><?php endif; ?></div>
                                        <button type="button" class="button vs08c-upload-img">📷 Choisir</button>
                                        <button type="button" class="button vs08c-remove-img" <?php if (empty($jour['image'])) echo 'style="display:none"'; ?>>✕</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="vs08c-add-jour" class="button button-primary" style="margin-top:12px">
                    ＋ Ajouter un jour
                </button>
            </div>

            <!-- ═══════ TAB: GALERIE ═══════ -->
            <div class="vs08c-panel" data-panel="gallery">
                <h3 class="vs08c-panel-title">Galerie photos du circuit</h3>
                <p class="vs08c-help-block">La première image sera l'image principale (hero). Glissez-déposez pour réorganiser.</p>
                <div id="vs08c-gallery" class="vs08c-gallery-grid">
                    <?php $galerie = $m['galerie'] ?? [];
                    foreach ($galerie as $gi => $img_url): if (empty($img_url)) continue; ?>
                    <div class="vs08c-gallery-item" data-url="<?php echo esc_attr($img_url); ?>">
                        <img src="<?php echo esc_url($img_url); ?>">
                        <button type="button" class="vs08c-gallery-remove">✕</button>
                        <input type="hidden" name="vs08c[galerie][]" value="<?php echo esc_attr($img_url); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="vs08c-add-gallery" class="button button-primary" style="margin-top:12px">
                    📷 Ajouter des photos
                </button>
            </div>

            <!-- ═══════ TAB: INCLUS / NON INCLUS ═══════ -->
            <div class="vs08c-panel" data-panel="inclus">
                <h3 class="vs08c-panel-title">Ce qui est inclus & non inclus</h3>
                <div class="vs08c-field-row">
                    <div class="vs08c-field">
                        <label>✅ Inclus dans le prix</label>
                        <p class="vs08c-help">Un élément par ligne</p>
                        <textarea name="vs08c[inclus]" rows="10" placeholder="Vol aller-retour&#10;Hébergement en hôtel 4★&#10;Pension complète (hors boissons)&#10;Transferts sur place en bus climatisé&#10;Guide francophone&#10;Visites et excursions mentionnées au programme"><?php echo esc_textarea($m['inclus'] ?? ''); ?></textarea>
                    </div>
                    <div class="vs08c-field">
                        <label>❌ Non inclus</label>
                        <p class="vs08c-help">Un élément par ligne</p>
                        <textarea name="vs08c[non_inclus]" rows="10" placeholder="Boissons&#10;Pourboires&#10;Dépenses personnelles&#10;Assurance voyage (proposée en option)&#10;Repas non mentionnés au programme"><?php echo esc_textarea($m['non_inclus'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="vs08c-field-row" style="margin-top:16px">
                    <div class="vs08c-field vs08c-w2">
                        <label>✨ Points forts du circuit (3-4 max)</label>
                        <textarea name="vs08c[points_forts]" rows="4" placeholder="Nuit dans le désert sous les étoiles&#10;Visite de la Médina de Fès, classée UNESCO&#10;Traversée des gorges du Todra&#10;Balade en dromadaire dans l'Erg Chebbi"><?php echo esc_textarea($m['points_forts'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ═══════ TAB: TARIFS & AÉROPORTS ═══════ -->
            <div class="vs08c-panel" data-panel="pricing">
                <h3 class="vs08c-panel-title">Tarification du circuit</h3>

                <div class="vs08c-pricing-section">
                    <h4>💰 Prix de base (par personne)</h4>
                    <div class="vs08c-field-row">
                        <div class="vs08c-field"><label>Prix en chambre double</label>
                            <input type="number" name="vs08c[prix_double]" value="<?php echo esc_attr($m['prix_double'] ?? ''); ?>" step="0.01" placeholder="890">
                            <p class="vs08c-help">Prix de référence affiché</p>
                        </div>
                        <div class="vs08c-field"><label>Supplément chambre individuelle</label>
                            <input type="number" name="vs08c[prix_simple_supp]" value="<?php echo esc_attr($m['prix_simple_supp'] ?? ''); ?>" step="0.01" placeholder="250">
                        </div>
                        <div class="vs08c-field"><label>Supp. individuel par</label>
                            <select name="vs08c[simple_supp_type]">
                                <option value="total" <?php selected($m['simple_supp_type'] ?? 'total', 'total'); ?>>Forfait total</option>
                                <option value="nuit" <?php selected($m['simple_supp_type'] ?? '', 'nuit'); ?>>Par nuit</option>
                            </select>
                        </div>
                        <div class="vs08c-field"><label>Prix chambre triple</label>
                            <input type="number" name="vs08c[prix_triple]" value="<?php echo esc_attr($m['prix_triple'] ?? ''); ?>" step="0.01" placeholder="0">
                            <p class="vs08c-help">Laisser vide ou 0 si pas de triple</p>
                        </div>
                    </div>
                    <div class="vs08c-field-row">
                        <div class="vs08c-field"><label>Prix vol de base (par pers.)</label>
                            <input type="number" name="vs08c[prix_vol_base]" value="<?php echo esc_attr($m['prix_vol_base'] ?? ''); ?>" step="0.01" placeholder="0">
                            <p class="vs08c-help">Si 0, le prix vol sera cherché via l'API</p>
                        </div>
                        <div class="vs08c-field"><label>Taxes & Frais de dossier</label>
                            <input type="number" name="vs08c[prix_taxe]" value="<?php echo esc_attr($m['prix_taxe'] ?? ''); ?>" step="0.01" placeholder="0">
                        </div>
                        <div class="vs08c-field"><label>Transferts (par pers.)</label>
                            <input type="number" name="vs08c[prix_transfert]" value="<?php echo esc_attr($m['prix_transfert'] ?? ''); ?>" step="0.01" placeholder="0">
                        </div>
                    </div>
                    <div class="vs08c-field-row">
                        <div class="vs08c-field"><label>Réduction enfant -12 ans (%)</label>
                            <input type="number" name="vs08c[reduc_enfant]" value="<?php echo esc_attr($m['reduc_enfant'] ?? ''); ?>" min="0" max="100" placeholder="30">
                        </div>
                        <div class="vs08c-field"><label>Acompte (%)</label>
                            <input type="number" name="vs08c[acompte_pct]" value="<?php echo esc_attr($m['acompte_pct'] ?? '30'); ?>" min="10" max="100">
                        </div>
                        <div class="vs08c-field"><label>Délai solde (jours avant départ)</label>
                            <input type="number" name="vs08c[delai_solde]" value="<?php echo esc_attr($m['delai_solde'] ?? '30'); ?>" min="1">
                        </div>
                    </div>
                </div>

                <div class="vs08c-pricing-section" style="margin-top:24px">
                    <h4>✈️ Aéroports de départ</h4>
                    <p class="vs08c-help-block">Ajoutez les aéroports disponibles. Le supplément vol est par rapport au prix vol de base ci-dessus.</p>
                    <div id="vs08c-airports-list" class="vs08c-repeater">
                        <?php $aeroports = $m['aeroports'] ?? [];
                        if (empty($aeroports)) $aeroports = [['code' => 'ORY', 'label' => 'Paris Orly', 'supp' => '0']];
                        foreach ($aeroports as $ai => $aero): ?>
                        <div class="vs08c-repeater-item vs08c-airport-item">
                            <div class="vs08c-field-row vs08c-compact">
                                <div class="vs08c-field"><label>Code IATA</label><input type="text" name="vs08c[aeroports][<?php echo $ai; ?>][code]" value="<?php echo esc_attr($aero['code'] ?? ''); ?>" placeholder="ORY" style="text-transform:uppercase"></div>
                                <div class="vs08c-field"><label>Nom aéroport</label><input type="text" name="vs08c[aeroports][<?php echo $ai; ?>][label]" value="<?php echo esc_attr($aero['label'] ?? ''); ?>" placeholder="Paris Orly"></div>
                                <div class="vs08c-field"><label>Supp. vol (€/pers)</label><input type="number" name="vs08c[aeroports][<?php echo $ai; ?>][supp]" value="<?php echo esc_attr($aero['supp'] ?? '0'); ?>" step="0.01"></div>
                                <button type="button" class="vs08c-remove-repeater" title="Supprimer">✕</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="vs08c-add-airport" class="button">＋ Ajouter un aéroport</button>
                </div>

                <div class="vs08c-pricing-section" style="margin-top:24px">
                    <h4>📅 Périodes de départ</h4>
                    <p class="vs08c-help-block">Définissez les périodes pendant lesquelles le circuit est disponible, et cochez les jours de départ possibles.</p>
                    <?php
                    $jours_semaine = [1=>'Lun',2=>'Mar',3=>'Mer',4=>'Jeu',5=>'Ven',6=>'Sam',7=>'Dim'];
                    $dates_periodes = $m['dates_depart'] ?? [];
                    if (empty($dates_periodes)) $dates_periodes = [['date_debut' => '', 'date_fin' => '', 'jours_depart' => [6], 'supp' => '0', 'statut' => 'ouvert']];
                    ?>
                    <div id="vs08c-dates-list" class="vs08c-repeater">
                        <?php foreach ($dates_periodes as $di => $per):
                            $per_jours = $per['jours_depart'] ?? [6];
                            if (empty($per_jours) || !is_array($per_jours)) $per_jours = [1,2,3,4,5,6,7];
                            $date_debut_fmt = !empty($per['date_debut']) ? date('d/m/Y', strtotime($per['date_debut'])) : '';
                            $date_fin_fmt   = !empty($per['date_fin']) ? date('d/m/Y', strtotime($per['date_fin'])) : '';
                        ?>
                        <div class="vs08c-repeater-item vs08c-date-item" style="padding:16px" data-period-idx="<?php echo $di; ?>">
                            <div class="vs08c-field-row vs08c-compact" style="margin-bottom:10px">
                                <div class="vs08c-field">
                                    <label>Du</label>
                                    <div class="vs08c-cal-wrap" id="vs08c-cal-wrap-debut-<?php echo $di; ?>">
                                        <div class="vs08c-cal-trigger" id="vs08c-cal-trigger-debut-<?php echo $di; ?>" onclick="vs08cToggleCal('debut',<?php echo $di; ?>)">
                                            <?php echo $date_debut_fmt ? '📅 ' . esc_html($date_debut_fmt) : '📅 Choisir date début'; ?>
                                        </div>
                                    </div>
                                    <input type="hidden" name="vs08c[dates_depart][<?php echo $di; ?>][date_debut]" id="vs08c-input-debut-<?php echo $di; ?>" value="<?php echo esc_attr($per['date_debut'] ?? ''); ?>">
                                </div>
                                <div class="vs08c-field">
                                    <label>Au</label>
                                    <div class="vs08c-cal-wrap" id="vs08c-cal-wrap-fin-<?php echo $di; ?>">
                                        <div class="vs08c-cal-trigger" id="vs08c-cal-trigger-fin-<?php echo $di; ?>" onclick="vs08cToggleCal('fin',<?php echo $di; ?>)">
                                            <?php echo $date_fin_fmt ? '📅 ' . esc_html($date_fin_fmt) : '📅 Choisir date fin'; ?>
                                        </div>
                                    </div>
                                    <input type="hidden" name="vs08c[dates_depart][<?php echo $di; ?>][date_fin]" id="vs08c-input-fin-<?php echo $di; ?>" value="<?php echo esc_attr($per['date_fin'] ?? ''); ?>">
                                </div>
                                <div class="vs08c-field"><label>Supp. (€/pers)</label><input type="number" name="vs08c[dates_depart][<?php echo $di; ?>][supp]" value="<?php echo esc_attr($per['supp'] ?? '0'); ?>" step="0.01"></div>
                                <div class="vs08c-field"><label>Statut</label>
                                    <select name="vs08c[dates_depart][<?php echo $di; ?>][statut]">
                                        <option value="ouvert" <?php selected($per['statut'] ?? 'ouvert', 'ouvert'); ?>>Ouvert</option>
                                        <option value="garanti" <?php selected($per['statut'] ?? '', 'garanti'); ?>>Garanti</option>
                                        <option value="complet" <?php selected($per['statut'] ?? '', 'complet'); ?>>Complet</option>
                                    </select>
                                </div>
                                <button type="button" class="vs08c-remove-repeater" title="Supprimer" style="align-self:end;margin-bottom:6px">✕</button>
                            </div>
                            <div style="font-size:11px;font-weight:700;color:#1a3a3a;margin-bottom:6px;font-family:'Outfit',sans-serif">📆 Jours de départ possibles :</div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px 14px">
                                <?php foreach ($jours_semaine as $num => $lib): ?>
                                <label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer;font-family:'Outfit',sans-serif">
                                    <input type="checkbox" name="vs08c[dates_depart][<?php echo $di; ?>][jours_depart][]" value="<?php echo $num; ?>" <?php checked(in_array($num, $per_jours)); ?>>
                                    <?php echo $lib; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="vs08c-add-date" class="button">＋ Ajouter une période</button>
                </div>

                <div class="vs08c-pricing-section" style="margin-top:24px">
                    <h4>🎁 Suppléments / Options</h4>
                    <div id="vs08c-options-list" class="vs08c-repeater">
                        <?php $options = $m['options'] ?? [];
                        foreach ($options as $oi => $opt): ?>
                        <div class="vs08c-repeater-item">
                            <div class="vs08c-field-row vs08c-compact">
                                <div class="vs08c-field"><label>Libellé</label><input type="text" name="vs08c[options][<?php echo $oi; ?>][label]" value="<?php echo esc_attr($opt['label'] ?? ''); ?>" placeholder="Ex: Excursion en 4x4"></div>
                                <div class="vs08c-field"><label>Prix (€)</label><input type="number" name="vs08c[options][<?php echo $oi; ?>][prix]" value="<?php echo esc_attr($opt['prix'] ?? ''); ?>" step="0.01"></div>
                                <div class="vs08c-field"><label>Type</label>
                                    <select name="vs08c[options][<?php echo $oi; ?>][type]">
                                        <option value="par_pers" <?php selected($opt['type'] ?? '', 'par_pers'); ?>>Par personne</option>
                                        <option value="fixe" <?php selected($opt['type'] ?? '', 'fixe'); ?>>Prix fixe</option>
                                        <option value="quantite" <?php selected($opt['type'] ?? '', 'quantite'); ?>>Par quantité</option>
                                    </select>
                                </div>
                                <button type="button" class="vs08c-remove-repeater" title="Supprimer">✕</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="vs08c-add-option" class="button">＋ Ajouter une option</button>
                </div>
            </div>

            <!-- ═══════ TAB: HÉBERGEMENTS ═══════ -->
            <div class="vs08c-panel" data-panel="hotels">
                <h3 class="vs08c-panel-title">Hébergements du circuit</h3>
                <p class="vs08c-help-block">Listez les hôtels / riads / hébergements utilisés pendant le circuit.</p>
                <div id="vs08c-hotels-list" class="vs08c-repeater">
                    <?php $hotels = $m['hotels'] ?? [];
                    if (empty($hotels)) $hotels = [['nom' => '', 'etoiles' => '4', 'ville' => '', 'nuits' => '1', 'description' => '', 'image' => '']];
                    foreach ($hotels as $hi => $hotel): ?>
                    <div class="vs08c-repeater-item">
                        <div class="vs08c-field-row vs08c-compact">
                            <div class="vs08c-field"><label>🏨 Nom</label><input type="text" name="vs08c[hotels][<?php echo $hi; ?>][nom]" value="<?php echo esc_attr($hotel['nom'] ?? ''); ?>" placeholder="Riad Salam"></div>
                            <div class="vs08c-field"><label>⭐ Étoiles</label>
                                <select name="vs08c[hotels][<?php echo $hi; ?>][etoiles]">
                                    <option value="2" <?php selected($hotel['etoiles'] ?? '4', '2'); ?>>★★</option>
                                    <option value="3" <?php selected($hotel['etoiles'] ?? '4', '3'); ?>>★★★</option>
                                    <option value="4" <?php selected($hotel['etoiles'] ?? '4', '4'); ?>>★★★★</option>
                                    <option value="5" <?php selected($hotel['etoiles'] ?? '4', '5'); ?>>★★★★★</option>
                                    <option value="riad" <?php selected($hotel['etoiles'] ?? '', 'riad'); ?>>Riad</option>
                                    <option value="camp" <?php selected($hotel['etoiles'] ?? '', 'camp'); ?>>Camp / Bivouac</option>
                                </select>
                            </div>
                            <div class="vs08c-field"><label>📍 Ville</label><input type="text" name="vs08c[hotels][<?php echo $hi; ?>][ville]" value="<?php echo esc_attr($hotel['ville'] ?? ''); ?>" placeholder="Marrakech"></div>
                            <div class="vs08c-field"><label>🌙 Nuits</label><input type="number" name="vs08c[hotels][<?php echo $hi; ?>][nuits]" value="<?php echo esc_attr($hotel['nuits'] ?? '1'); ?>" min="1"></div>
                            <button type="button" class="vs08c-remove-repeater" title="Supprimer">✕</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="vs08c-add-hotel" class="button">＋ Ajouter un hébergement</button>
            </div>

            <!-- ═══════ TAB: INFOS PRATIQUES ═══════ -->
            <div class="vs08c-panel" data-panel="practical">
                <h3 class="vs08c-panel-title">Informations pratiques</h3>
                <div class="vs08c-field-row">
                    <div class="vs08c-field"><label>🛂 Formalités (visa, passeport)</label>
                        <textarea name="vs08c[formalites]" rows="4" placeholder="Passeport valide 6 mois après la date de retour..."><?php echo esc_textarea($m['formalites'] ?? ''); ?></textarea>
                    </div>
                    <div class="vs08c-field"><label>💉 Santé / Vaccins</label>
                        <textarea name="vs08c[sante]" rows="4" placeholder="Aucun vaccin obligatoire, mais..."><?php echo esc_textarea($m['sante'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="vs08c-field-row">
                    <div class="vs08c-field"><label>🌤️ Climat / Meilleure période</label>
                        <textarea name="vs08c[climat]" rows="4" placeholder="Climat agréable d'octobre à avril..."><?php echo esc_textarea($m['climat'] ?? ''); ?></textarea>
                    </div>
                    <div class="vs08c-field"><label>💱 Monnaie & Pourboires</label>
                        <textarea name="vs08c[monnaie]" rows="4" placeholder="Le dirham marocain (MAD). Prévoir..."><?php echo esc_textarea($m['monnaie'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="vs08c-field-row">
                    <div class="vs08c-field vs08c-w2"><label>⚠️ Conditions d'annulation</label>
                        <textarea name="vs08c[annulation]" rows="6" placeholder="Plus de 30 jours avant le départ : acompte non remboursable&#10;Entre 30 et 14 jours : 50% du total&#10;Moins de 14 jours : 100% du total"><?php echo esc_textarea($m['annulation'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="vs08c-field-row">
                    <div class="vs08c-field vs08c-w2"><label>📝 Notes internes (non visibles par le client)</label>
                        <textarea name="vs08c[notes_internes]" rows="4" placeholder="Notes de l'équipe..."><?php echo esc_textarea($m['notes_internes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function(){
            var sel = document.getElementById('vs08c-pays-select');
            var flag = document.getElementById('vs08c-flag-input');
            if(!sel||!flag)return;
            function codeToEmoji(c){if(!c||c.length<2)return '';c=c.substring(0,2).toUpperCase();return String.fromCodePoint(0x1F1E6+c.charCodeAt(0)-65,0x1F1E6+c.charCodeAt(1)-65);}
            sel.addEventListener('change',function(){var o=sel.options[sel.selectedIndex];flag.value=o?codeToEmoji(o.getAttribute('data-code')||''):'';});
        })();
        </script>
        <?php
    }

    /**
     * Save all meta fields.
     */
    public static function save($post_id, $post) {
        if (!isset($_POST['vs08c_nonce']) || !wp_verify_nonce($_POST['vs08c_nonce'], 'vs08c_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== 'vs08_circuit') return;
        if (!current_user_can('edit_post', $post_id)) return;

        $raw = $_POST['vs08c'] ?? [];
        if (!is_array($raw)) return;

        // Sanitize
        $m = [];
        $text_fields = ['pays','flag','destination','guide_lang','pension','transport','badge','statut','iata_dest','themes','simple_supp_type'];
        foreach ($text_fields as $f) {
            $m[$f] = sanitize_text_field($raw[$f] ?? '');
        }
        $num_fields = ['duree_jours','duree','group_min','group_max','prix_double','prix_simple_supp','prix_triple','prix_vol_base','prix_taxe','prix_transfert','reduc_enfant','acompte_pct','delai_solde'];
        foreach ($num_fields as $f) {
            $m[$f] = floatval($raw[$f] ?? 0);
        }
        $textarea_fields = ['description','inclus','non_inclus','points_forts','formalites','sante','climat','monnaie','annulation','notes_internes'];
        foreach ($textarea_fields as $f) {
            $m[$f] = wp_kses_post($raw[$f] ?? '');
        }

        // Jours (itinéraire)
        $m['jours'] = [];
        if (!empty($raw['jours']) && is_array($raw['jours'])) {
            foreach ($raw['jours'] as $jour) {
                if (!is_array($jour)) continue;
                $m['jours'][] = [
                    'titre'       => sanitize_text_field($jour['titre'] ?? ''),
                    'description' => wp_kses_post($jour['description'] ?? ''),
                    'repas'       => sanitize_text_field($jour['repas'] ?? ''),
                    'nuit'        => sanitize_text_field($jour['nuit'] ?? ''),
                    'transport'   => sanitize_text_field($jour['transport'] ?? ''),
                    'tags'        => sanitize_text_field($jour['tags'] ?? ''),
                    'image'       => esc_url_raw($jour['image'] ?? ''),
                ];
            }
        }

        // Galerie
        $m['galerie'] = [];
        if (!empty($raw['galerie']) && is_array($raw['galerie'])) {
            foreach ($raw['galerie'] as $url) {
                $u = esc_url_raw($url);
                if ($u) $m['galerie'][] = $u;
            }
        }

        // Aéroports
        $m['aeroports'] = [];
        if (!empty($raw['aeroports']) && is_array($raw['aeroports'])) {
            foreach ($raw['aeroports'] as $aero) {
                if (!is_array($aero) || empty($aero['code'])) continue;
                $m['aeroports'][] = [
                    'code'  => strtoupper(sanitize_text_field($aero['code'] ?? '')),
                    'label' => sanitize_text_field($aero['label'] ?? ''),
                    'supp'  => floatval($aero['supp'] ?? 0),
                ];
            }
        }

        // Dates de départ (périodes)
        $m['dates_depart'] = [];
        if (!empty($raw['dates_depart']) && is_array($raw['dates_depart'])) {
            foreach ($raw['dates_depart'] as $date) {
                if (!is_array($date)) continue;
                if (empty($date['date_debut']) && empty($date['date_fin'])) continue;
                $jours = [];
                if (!empty($date['jours_depart']) && is_array($date['jours_depart'])) {
                    $jours = array_map('intval', $date['jours_depart']);
                }
                $m['dates_depart'][] = [
                    'date_debut'    => sanitize_text_field($date['date_debut'] ?? ''),
                    'date_fin'      => sanitize_text_field($date['date_fin'] ?? ''),
                    'jours_depart'  => $jours,
                    'supp'          => floatval($date['supp'] ?? 0),
                    'statut'        => sanitize_text_field($date['statut'] ?? 'ouvert'),
                ];
            }
        }

        // Options
        $m['options'] = [];
        if (!empty($raw['options']) && is_array($raw['options'])) {
            foreach ($raw['options'] as $i => $opt) {
                if (!is_array($opt) || empty($opt['label'])) continue;
                $m['options'][] = [
                    'id'    => 'opt_' . $i,
                    'label' => sanitize_text_field($opt['label'] ?? ''),
                    'prix'  => floatval($opt['prix'] ?? 0),
                    'type'  => sanitize_text_field($opt['type'] ?? 'par_pers'),
                ];
            }
        }

        // Hôtels
        $m['hotels'] = [];
        if (!empty($raw['hotels']) && is_array($raw['hotels'])) {
            foreach ($raw['hotels'] as $hotel) {
                if (!is_array($hotel) || empty($hotel['nom'])) continue;
                $m['hotels'][] = [
                    'nom'     => sanitize_text_field($hotel['nom'] ?? ''),
                    'etoiles' => sanitize_text_field($hotel['etoiles'] ?? '4'),
                    'ville'   => sanitize_text_field($hotel['ville'] ?? ''),
                    'nuits'   => intval($hotel['nuits'] ?? 1),
                ];
            }
        }

        update_post_meta($post_id, self::META_KEY, $m);
    }
}
