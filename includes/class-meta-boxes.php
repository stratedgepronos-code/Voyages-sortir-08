<?php
class VS08V_MetaBoxes {

    public static function register() {
        add_meta_box('vs08v_general',   '🌍 Informations générales', [__CLASS__,'box_general'],   'vs08_voyage','normal','high');
        add_meta_box('vs08v_tarifs',    '💰 Tarifs & Chambres',      [__CLASS__,'box_tarifs'],    'vs08_voyage','normal','high');
        add_meta_box('vs08v_vols',      '✈️ Vols & Aéroports',       [__CLASS__,'box_vols'],      'vs08_voyage','normal','high');
        add_meta_box('vs08v_programme', '🗓️ Programme',               [__CLASS__,'box_programme'], 'vs08_voyage','normal','default');
        add_meta_box('vs08v_annulation','⚠️ Conditions d\'annulation', [__CLASS__,'box_annulation'],'vs08_voyage','normal','default');
        add_meta_box('vs08v_galerie',   '📷 Galerie photos',          [__CLASS__,'box_galerie'],   'vs08_voyage','side','default');
        add_meta_box('vs08v_regles',    '⚙️ Règles de réservation',   [__CLASS__,'box_regles'],    'vs08_voyage','side','default');
    }

    /* ============================================================ */
    public static function box_general($post) {
        wp_nonce_field('vs08v_save','vs08v_nonce');
        $m = self::get($post->ID); ?>
        <?php
        $vs08v_pays_flags = [
            'Portugal'=>'PT','Espagne'=>'ES','Maroc'=>'MA','Turquie'=>'TR','Irlande'=>'IE',
            'Thaïlande'=>'TH','France'=>'FR','Italie'=>'IT','Grèce'=>'GR','Tunisie'=>'TN',
            'Écosse'=>'GB-SCT','Angleterre'=>'GB-ENG','Pays de Galles'=>'GB-WLS','Royaume-Uni'=>'GB',
            'Allemagne'=>'DE','Autriche'=>'AT','Suisse'=>'CH','Belgique'=>'BE','Pays-Bas'=>'NL',
            'Croatie'=>'HR','Monténégro'=>'ME','Bulgarie'=>'BG','Roumanie'=>'RO','Pologne'=>'PL',
            'République Tchèque'=>'CZ','Slovaquie'=>'SK','Hongrie'=>'HU','Slovénie'=>'SI',
            'Chypre'=>'CY','Malte'=>'MT','Islande'=>'IS','Norvège'=>'NO','Suède'=>'SE',
            'Finlande'=>'FI','Danemark'=>'DK','Estonie'=>'EE','Lettonie'=>'LV','Lituanie'=>'LT',
            'Égypte'=>'EG','Afrique du Sud'=>'ZA','Maurice'=>'MU','Sénégal'=>'SN','Cap-Vert'=>'CV',
            'Mexique'=>'MX','République Dominicaine'=>'DO','Cuba'=>'CU','Costa Rica'=>'CR',
            'États-Unis'=>'US','Canada'=>'CA','Brésil'=>'BR','Argentine'=>'AR','Colombie'=>'CO',
            'Vietnam'=>'VN','Indonésie'=>'ID','Japon'=>'JP','Corée du Sud'=>'KR','Chine'=>'CN',
            'Inde'=>'IN','Sri Lanka'=>'LK','Maldives'=>'MV','Émirats arabes unis'=>'AE',
            'Oman'=>'OM','Jordanie'=>'JO','Australie'=>'AU','Nouvelle-Zélande'=>'NZ',
            'Cambodge'=>'KH','Philippines'=>'PH','Malaisie'=>'MY','Singapour'=>'SG',
        ];
        $current_pays = $m['pays'] ?? '';
        $current_flag = $m['flag'] ?? '';
        ?>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Type de voyage</label>
                <select name="vs08v[type_voyage]">
                    <option value="">— Choisir —</option>
                    <?php foreach(['sejour_golf'=>'Séjours Golf','sejour'=>'Séjours Vacances','all_inclusive'=>'All Inclusive','road_trip'=>'Road Trip','circuit'=>'Circuits','city_trip'=>'City Trip','parc'=>'Billets Parcs'] as $tv=>$tl): ?>
                    <option value="<?php echo $tv;?>" <?php selected($m['type_voyage']??'',$tv);?>><?php echo $tl;?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="vs08v-field vs08v-field-2"><label>Destination</label><input type="text" name="vs08v[destination]" value="<?php echo esc_attr($m['destination']??''); ?>" placeholder="Algarve, Portugal"></div>
            <div class="vs08v-field"><label>Pays</label>
                <select name="vs08v[pays]" id="vs08v-pays-select">
                    <option value="">— Choisir —</option>
                    <?php foreach($vs08v_pays_flags as $pays_nom => $pays_code): ?>
                    <option value="<?php echo esc_attr($pays_nom);?>" data-code="<?php echo esc_attr($pays_code);?>" <?php selected($current_pays, $pays_nom);?>><?php echo esc_html($pays_nom);?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="vs08v-field"><label>Drapeau</label><input type="text" name="vs08v[flag]" id="vs08v-flag-input" value="<?php echo esc_attr($current_flag); ?>" placeholder="🇵🇹" style="width:70px">
                <p class="vs08v-help">Auto depuis le pays (modifiable)</p>
            </div>
        </div>
        <script>
        (function(){
            var sel = document.getElementById('vs08v-pays-select');
            var flag = document.getElementById('vs08v-flag-input');
            if (!sel || !flag) return;
            function codeToEmoji(code) {
                if (!code || code.length < 2) return '';
                code = code.substring(0,2).toUpperCase();
                return String.fromCodePoint(0x1F1E6 + code.charCodeAt(0) - 65, 0x1F1E6 + code.charCodeAt(1) - 65);
            }
            sel.addEventListener('change', function(){
                var opt = sel.options[sel.selectedIndex];
                var code = opt ? opt.getAttribute('data-code') : '';
                flag.value = code ? codeToEmoji(code) : '';
            });
        })();
        </script>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Durée — Jours</label><input type="number" name="vs08v[duree_jours]" value="<?php echo esc_attr($m['duree_jours'] ?? (isset($m['duree']) ? (int) $m['duree'] + 1 : 8)); ?>" min="1" max="31" placeholder="8"></div>
            <div class="vs08v-field"><label>Durée — Nuits</label><input type="number" name="vs08v[duree]" value="<?php echo esc_attr($m['duree']??7); ?>" min="1" max="30" placeholder="7"></div>
            <div class="vs08v-field"><label>Nb parcours golf</label><input type="number" name="vs08v[nb_parcours]" value="<?php echo esc_attr($m['nb_parcours']??''); ?>"></div>
            <div class="vs08v-field"><label>Index min. requis</label><input type="number" name="vs08v[index_min]" value="<?php echo esc_attr($m['index_min']??''); ?>" step="0.1" placeholder="Ex: 36 (facultatif)"></div>
            <div class="vs08v-field"><label>Niveau</label>
                <select name="vs08v[niveau]">
                    <?php foreach(['tous'=>'Tous niveaux','debutant'=>'Débutant','intermediaire'=>'Intermédiaire','confirme'=>'Confirmé'] as $v=>$l): ?>
                    <option value="<?php echo $v;?>" <?php selected($m['niveau']??'tous',$v);?>><?php echo $l;?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="vs08v-field"><label>Badge</label>
                <select name="vs08v[badge]">
                    <option value="">Aucun</option>
                    <?php foreach(['new'=>'Nouveauté','promo'=>'Promo','best'=>'Best-seller','derniere'=>'Dernières places'] as $v=>$l): ?>
                    <option value="<?php echo $v;?>" <?php selected($m['badge']??'',$v);?>><?php echo $l;?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        <div class="vs08v-field-row" style="margin-top:6px">
            <div class="vs08v-field vs08v-field-2"><label>🚌 Type de transfert</label>
                <select name="vs08v[transfert_type]">
                    <option value="">— Non précisé —</option>
                    <option value="groupes" <?php selected($m['transfert_type']??'','groupes');?>>🚌 Transferts groupés</option>
                    <option value="prives"  <?php selected($m['transfert_type']??'','prives');?>>🚐 Transferts privés</option>
                    <option value="voiture" <?php selected($m['transfert_type']??'','voiture');?>>🚗 Location de voiture</option>
                </select>
            </div>
        </div>
        <hr class="vs08v-sep">
        <div class="vs08v-field-row">
            <div class="vs08v-field vs08v-field-2"><label>Nom de l'hôtel</label><input type="text" name="vs08v[hotel_nom]" value="<?php echo esc_attr($m['hotel_nom']??''); ?>"></div>
            <div class="vs08v-field"><label>Étoiles</label>
                <select name="vs08v[hotel_etoiles]"><?php for($i=3;$i<=5;$i++): ?><option value="<?php echo $i;?>" <?php selected($m['hotel_etoiles']??5,$i);?>><?php echo $i;?> ★</option><?php endfor;?></select>
            </div>
            <div class="vs08v-field"><label>Type de pension</label>
                <select name="vs08v[pension]">
                    <?php foreach(['bb'=>'Petit-déjeuner (BB)','dp'=>'Demi-pension (DP)','pc'=>'Pension complète (PC)','ai'=>'Tout inclus (AI)'] as $v=>$l): ?>
                    <option value="<?php echo $v;?>" <?php selected($m['pension']??'bb',$v);?>><?php echo $l;?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        <div class="vs08v-field"><label>Description de l'hôtel</label><textarea name="vs08v[hotel_desc]" rows="3" placeholder="Décrivez l'hôtel, ses équipements, son emplacement..."><?php echo esc_textarea($m['hotel_desc']??''); ?></textarea></div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Buggy</label>
                <select name="vs08v[buggy]">
                    <option value="inclus" <?php selected($m['buggy']??'inclus','inclus');?>>✅ Inclus</option>
                    <option value="option" <?php selected($m['buggy']??'inclus','option');?>>🔧 En option</option>
                    <option value="non" <?php selected($m['buggy']??'inclus','non');?>>❌ Non disponible</option>
                </select>
            </div>
            <div class="vs08v-field"><label>Caddie</label>
                <select name="vs08v[caddie]">
                    <option value="non" <?php selected($m['caddie']??'non','non');?>>❌ Non inclus</option>
                    <option value="inclus" <?php selected($m['caddie']??'non','inclus');?>>✅ Inclus</option>
                    <option value="option" <?php selected($m['caddie']??'non','option');?>>🔧 En option</option>
                </select>
            </div>
            <div class="vs08v-field"><label>Carnet de voyage</label>
                <select name="vs08v[carnet]">
                    <option value="oui" <?php selected($m['carnet']??'oui','oui');?>>✅ Inclus (PDF)</option>
                    <option value="non" <?php selected($m['carnet']??'oui','non');?>>❌ Non</option>
                </select>
            </div>
            <div class="vs08v-field"><label>Garant financier</label>
                <select name="vs08v[garant]">
                    <option value="apst" <?php selected($m['garant']??'apst','apst');?>>APST</option>
                    <option value="atout_france" <?php selected($m['garant']??'apst','atout_france');?>>Atout France</option>
                    <option value="autre" <?php selected($m['garant']??'apst','autre');?>>Autre</option>
                </select>
            </div>
        </div>
        <div class="vs08v-field"><label>Description courte (carte séjour)</label><textarea name="vs08v[desc_courte]" rows="2" placeholder="Texte court affiché sur la carte produit..."><?php echo esc_textarea($m['desc_courte']??''); ?></textarea></div>
    <?php }

    /* ============================================================ */
    public static function box_tarifs($post) {
        $m = self::get($post->ID); ?>
        <p class="vs08v-notice">💡 Tarifs <strong>par personne / nuit</strong> pour l'hébergement. Le prix total est calculé automatiquement selon la sélection du client.</p>

        <div class="vs08v-stitle">🏨 Hébergement</div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Chambre Double /pers./nuit</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_double]" value="<?php echo esc_attr($m['prix_double']??''); ?>" step="0.01" placeholder="85"></div></div>
            <div class="vs08v-field"><label>Suppl. Chambre Simple</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_simple_supp]" value="<?php echo esc_attr($m['prix_simple_supp']??''); ?>" step="0.01" placeholder="40"></div>
                <select name="vs08v[simple_supp_type]" style="margin-left:6px;max-width:120px">
                    <option value="nuit" <?php selected($m['simple_supp_type']??'nuit','nuit');?>>Par nuit</option>
                    <option value="sejour" <?php selected($m['simple_supp_type']??'nuit','sejour');?>>Par séjour</option>
                </select></div>
            <div class="vs08v-field"><label>Chambre Triple /pers./nuit</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_triple]" value="<?php echo esc_attr($m['prix_triple']??''); ?>" step="0.01" placeholder="75"></div></div>
            <div class="vs08v-field"><label>Suppl. Vue mer/golf</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_vue_supp]" value="<?php echo esc_attr($m['prix_vue_supp']??''); ?>" step="0.01" placeholder="20"></div></div>
        </div>

        <div class="vs08v-stitle">⛳ Green fees & Golf</div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Forfait green fees /golfeur</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_greenfees]" value="<?php echo esc_attr($m['prix_greenfees']??''); ?>" step="0.01"></div><p class="vs08v-help">Prix total du forfait green fees pour 1 golfeur (tout le séjour). Sera × nb de golfeurs.</p></div>
            <div class="vs08v-field"><label>Réduction non-golfeur /pers.</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[reduction_nongolfeur]" value="<?php echo esc_attr($m['reduction_nongolfeur']??'0'); ?>" min="0" step="0.01" placeholder="0"></div><p class="vs08v-help">Montant en € déduit du forfait green fees par accompagnant non-golfeur (ex. 105 → ils paient green_fees − 105€)</p></div>
            <div class="vs08v-field"><label>Prix buggy /buggy (si option)</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_buggy]" value="<?php echo esc_attr($m['prix_buggy']??''); ?>" step="0.01" placeholder="0"></div></div>
        </div>

        <div class="vs08v-stitle">🧳 Options bagages</div>
        <p class="vs08v-notice">💡 1 bagage soute inclus par voyageur (golfeur + accompagnant). 1 bagage golf inclus par golfeur. Le client peut modifier la quantité sur la page de réservation.</p>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Prix bagage soute /unité</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_bagage_soute]" value="<?php echo esc_attr($m['prix_bagage_soute']??'120'); ?>" step="0.01" placeholder="120"></div><p class="vs08v-help">Inclus 1 par voyageur (aller-retour)</p></div>
            <div class="vs08v-field"><label>Prix bagage golf /unité</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_bagage_golf]" value="<?php echo esc_attr($m['prix_bagage_golf']??'120'); ?>" step="0.01" placeholder="120"></div><p class="vs08v-help">Inclus 1 par golfeur (aller-retour)</p></div>
        </div>

        <?php
        $transfert_type = $m['transfert_type'] ?? '';
        $show_transfert_prix = in_array($transfert_type, ['groupes', 'prives'], true);
        $show_voiture_tarifs = ($transfert_type === 'voiture');
        ?>
        <div id="vs08v-block-transfert" style="display:<?php echo $show_transfert_prix ? 'block' : 'none'; ?>">
        <div class="vs08v-stitle">🚐 Prix transferts (par personne)</div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Prix transfert /personne</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[prix_transfert]" value="<?php echo esc_attr($m['prix_transfert']??''); ?>" step="0.01" placeholder="Ex: 50"></div><p class="vs08v-help">Multiplié par le nombre total de voyageurs (golfeurs + accompagnants)</p></div>
        </div>
        </div>

        <div id="vs08v-block-voiture" style="display:<?php echo $show_voiture_tarifs ? 'block' : 'none'; ?>">
        <div class="vs08v-stitle">🚗 Location de voiture — Détails véhicule</div>
        <p class="vs08v-notice" style="margin-bottom:10px">Renseignez les caractéristiques du véhicule de location inclus dans le séjour.</p>
        <?php
        $vd = $m['voiture_details'] ?? [];
        $vs08v_car_cats = [
            'mini'=>'Mini / Citadine','economique'=>'Économique','compacte'=>'Compacte',
            'berline'=>'Berline','suv'=>'SUV / Crossover','monospace'=>'Monospace',
            'premium'=>'Premium / Luxe','cabriolet'=>'Cabriolet',
        ];
        $plugin_cars_url = plugins_url('assets/img/cars/', dirname(__FILE__));
        ?>
        <div class="vs08v-field-row">
            <div class="vs08v-field vs08v-field-2"><label>Modèle</label><input type="text" name="vs08v[voiture_details][modele]" value="<?php echo esc_attr($vd['modele']??'');?>" placeholder="Ex: FIAT 500 ou similaire"></div>
            <div class="vs08v-field"><label>Catégorie</label>
                <select name="vs08v[voiture_details][categorie]" id="vs08v-voiture-cat">
                    <option value="">— Choisir —</option>
                    <?php foreach($vs08v_car_cats as $ck => $cl): ?>
                    <option value="<?php echo esc_attr($ck);?>" <?php selected($vd['categorie']??'',$ck);?>><?php echo esc_html($cl);?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Âge minimum</label><input type="text" name="vs08v[voiture_details][age_min]" value="<?php echo esc_attr($vd['age_min']??'21');?>" placeholder="21 ans"></div>
            <div class="vs08v-field"><label>Ancienneté permis</label><input type="text" name="vs08v[voiture_details][anciennete_permis]" value="<?php echo esc_attr($vd['anciennete_permis']??'1 an');?>" placeholder="1 an"></div>
            <div class="vs08v-field"><label>Boîte de vitesses</label>
                <select name="vs08v[voiture_details][boite]">
                    <option value="manuelle" <?php selected($vd['boite']??'manuelle','manuelle');?>>⚙️ Manuelle</option>
                    <option value="automatique" <?php selected($vd['boite']??'manuelle','automatique');?>>🅰️ Automatique</option>
                </select>
            </div>
            <div class="vs08v-field"><label>Climatisation</label>
                <select name="vs08v[voiture_details][clim]">
                    <option value="oui" <?php selected($vd['clim']??'oui','oui');?>>❄️ Oui</option>
                    <option value="non" <?php selected($vd['clim']??'oui','non');?>>❌ Non</option>
                </select>
            </div>
        </div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Nombre de portes</label><input type="number" name="vs08v[voiture_details][portes]" value="<?php echo esc_attr($vd['portes']??'3');?>" min="2" max="5"></div>
            <div class="vs08v-field"><label>Nombre de places</label><input type="number" name="vs08v[voiture_details][places]" value="<?php echo esc_attr($vd['places']??'4');?>" min="2" max="9"></div>
            <div class="vs08v-field"><label>Politique carburant</label>
                <select name="vs08v[voiture_details][carburant]">
                    <option value="plein_plein" <?php selected($vd['carburant']??'plein_plein','plein_plein');?>>Plein à rendre plein</option>
                    <option value="plein_vide" <?php selected($vd['carburant']??'plein_plein','plein_vide');?>>Plein/Vide</option>
                    <option value="identique" <?php selected($vd['carburant']??'plein_plein','identique');?>>Même niveau</option>
                </select>
            </div>
            <div class="vs08v-field"><label>Emplacement retrait</label>
                <select name="vs08v[voiture_details][emplacement]">
                    <option value="aeroport" <?php selected($vd['emplacement']??'aeroport','aeroport');?>>✈️ Dans l'aéroport</option>
                    <option value="navette" <?php selected($vd['emplacement']??'aeroport','navette');?>>🚌 Navette aéroport</option>
                    <option value="hotel" <?php selected($vd['emplacement']??'aeroport','hotel');?>>🏨 À l'hôtel</option>
                    <option value="ville" <?php selected($vd['emplacement']??'aeroport','ville');?>>🏙️ En ville</option>
                </select>
            </div>
        </div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Kilométrage</label>
                <select name="vs08v[voiture_details][kilometrage]">
                    <option value="illimite" <?php selected($vd['kilometrage']??'illimite','illimite');?>>✅ Illimité</option>
                    <option value="limite" <?php selected($vd['kilometrage']??'illimite','limite');?>>⚠️ Limité</option>
                </select>
            </div>
            <div class="vs08v-field"><label>Responsabilité civile</label>
                <select name="vs08v[voiture_details][rc]">
                    <option value="incluse" <?php selected($vd['rc']??'incluse','incluse');?>>✅ Incluse</option>
                    <option value="non_incluse" <?php selected($vd['rc']??'incluse','non_incluse');?>>❌ Non incluse</option>
                </select>
            </div>
            <div class="vs08v-field"><label>Assurance tous risques</label>
                <select name="vs08v[voiture_details][assurance]">
                    <option value="incluse" <?php selected($vd['assurance']??'incluse','incluse');?>>✅ Incluse</option>
                    <option value="non_incluse" <?php selected($vd['assurance']??'incluse','non_incluse');?>>❌ Non incluse</option>
                </select>
            </div>
        </div>
        <div class="vs08v-field-row" style="margin-top:12px">
            <div class="vs08v-field vs08v-field-2"><label>📷 Photo du véhicule</label>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <input type="text" name="vs08v[voiture_details][image]" id="vs08v-voiture-img-url" value="<?php echo esc_attr($vd['image']??'');?>" placeholder="URL image — ou utiliser les boutons →" style="flex:1;min-width:200px">
                    <button type="button" class="button button-primary" id="vs08v-voiture-ia-btn" style="background:#59b7b7;border-color:#4a9e9e">🤖 Rechercher par IA</button>
                    <button type="button" class="button" id="vs08v-voiture-img-btn">📁 Bibliothèque</button>
                </div>
                <div id="vs08v-voiture-ia-status" style="display:none;margin-top:6px;padding:8px 12px;border-radius:8px;font-size:12px;font-family:'Outfit',sans-serif"></div>
                <?php $img_src = $vd['image'] ?? ''; ?>
                <img src="<?php echo esc_url($img_src);?>" id="vs08v-voiture-img-preview" style="max-width:280px;max-height:160px;margin-top:10px;border-radius:12px;border:1px solid #e5e7eb;object-fit:contain;background:#f0f4f8;display:<?php echo !empty($img_src)?'block':'none';?>">
            </div>
        </div>
        <script>
        (function(){
            var imgUrl      = document.getElementById('vs08v-voiture-img-url');
            var imgPreview  = document.getElementById('vs08v-voiture-img-preview');
            var btnIA       = document.getElementById('vs08v-voiture-ia-btn');
            var btnCustom   = document.getElementById('vs08v-voiture-img-btn');
            var statusEl    = document.getElementById('vs08v-voiture-ia-status');
            var carNonce    = <?php echo json_encode(wp_create_nonce('vs08v_scan_hotel')); ?>;

            function setPreview(url) {
                if (url) { imgPreview.src = url; imgPreview.style.display = 'block'; }
                else { imgPreview.style.display = 'none'; }
            }

            function setStatus(msg, type) {
                statusEl.style.display = 'block';
                statusEl.textContent = msg;
                statusEl.style.background = type === 'loading' ? '#edf8f8' : type === 'error' ? '#fef2f2' : '#e8f8f0';
                statusEl.style.color = type === 'loading' ? '#1a3a3a' : type === 'error' ? '#991b1b' : '#166534';
            }

            if (btnIA) {
                btnIA.addEventListener('click', function(e){
                    e.preventDefault();
                    var modele = document.querySelector('input[name="vs08v[voiture_details][modele]"]');
                    var model = modele ? modele.value.trim() : '';
                    if (!model) {
                        setStatus('⚠️ Renseignez d\'abord le modèle du véhicule ci-dessus.', 'error');
                        return;
                    }
                    btnIA.disabled = true;
                    btnIA.textContent = '🔄 Recherche en cours...';
                    setStatus('🔍 L\'IA recherche une photo officielle de « ' + model + ' » sur les sites constructeurs...', 'loading');

                    var fd = new FormData();
                    fd.append('action', 'vs08v_search_car_photo');
                    fd.append('nonce', carNonce);
                    fd.append('model', model);

                    fetch(ajaxurl, {method:'POST', body:fd})
                    .then(function(r){return r.json();})
                    .then(function(data){
                        btnIA.disabled = false;
                        btnIA.textContent = '🤖 Rechercher par IA';
                        if (data.success && data.data && data.data.image_url) {
                            imgUrl.value = data.data.image_url;
                            setPreview(data.data.image_url);
                            var src = data.data.source ? ' (source : ' + data.data.source + ')' : '';
                            setStatus('✅ Photo trouvée' + src + ' — vérifiez le résultat ci-dessous.', 'success');
                        } else {
                            setStatus('❌ ' + (data.data || 'Aucune photo trouvée. Essayez avec un nom plus précis.'), 'error');
                        }
                    })
                    .catch(function(err){
                        btnIA.disabled = false;
                        btnIA.textContent = '🤖 Rechercher par IA';
                        setStatus('❌ Erreur réseau : ' + err.message, 'error');
                    });
                });
            }

            if (btnCustom) {
                btnCustom.addEventListener('click', function(e){
                    e.preventDefault();
                    var frame = wp.media({title:'Photo du véhicule',multiple:false,library:{type:'image'}});
                    frame.on('select', function(){
                        var url = frame.state().get('selection').first().toJSON().url;
                        imgUrl.value = url;
                        setPreview(url);
                        statusEl.style.display = 'none';
                    });
                    frame.open();
                });
            }
        })();
        </script>

        <div class="vs08v-stitle" style="margin-top:20px">💰 Tarifs location de voiture par période</div>
        <p class="vs08v-notice" style="margin-bottom:10px">Définissez les tarifs de location de voiture par période (du… au…). Le client verra le tarif selon sa date de séjour.</p>
        <div id="vs08v-voiture-periodes">
            <?php $voiture_periodes = $m['voiture_periodes'] ?? []; foreach ($voiture_periodes as $i => $vp): ?>
            <div class="vs08v-dyn-row">
                <input type="text" name="vs08v[voiture_periodes][<?php echo $i;?>][label]" value="<?php echo esc_attr($vp['label']??'');?>" placeholder="Ex: Haute saison" style="flex:0 0 120px">
                <input type="date" name="vs08v[voiture_periodes][<?php echo $i;?>][date_debut]" value="<?php echo esc_attr($vp['date_debut']??'');?>">
                <span style="color:#999;flex-shrink:0">→</span>
                <input type="date" name="vs08v[voiture_periodes][<?php echo $i;?>][date_fin]" value="<?php echo esc_attr($vp['date_fin']??'');?>">
                <div class="vs08v-pi" style="flex:0 0 110px"><span>€</span><input type="number" name="vs08v[voiture_periodes][<?php echo $i;?>][prix]" value="<?php echo esc_attr($vp['prix']??'');?>" placeholder="Prix/séjour" step="0.01"></div>
                <button type="button" class="vs08v-rm" onclick="this.closest('.vs08v-dyn-row').remove()">✕ Suppr.</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button vs08v-add-voiture-periode" style="margin-top:6px">+ Période location voiture</button>
        </div>

        <script>
        (function(){
            var sel = document.querySelector('select[name="vs08v[transfert_type]"]');
            var blockTransfert = document.getElementById('vs08v-block-transfert');
            var blockVoiture = document.getElementById('vs08v-block-voiture');
            function updateBlocks(){
                var v = sel ? sel.value : '';
                var isTransfert = (v === 'groupes' || v === 'prives');
                var isVoiture = (v === 'voiture');
                if (blockTransfert) blockTransfert.style.display = isTransfert ? 'block' : 'none';
                if (blockVoiture) blockVoiture.style.display = isVoiture ? 'block' : 'none';
            }
            if (sel) sel.addEventListener('change', updateBlocks);

            document.addEventListener('click', function(e){
                if (!e.target || !e.target.classList.contains('vs08v-add-voiture-periode')) return;
                e.preventDefault();
                var container = document.getElementById('vs08v-voiture-periodes');
                if (!container) return;
                var i = container.querySelectorAll('.vs08v-dyn-row').length;
                var row = document.createElement('div');
                row.className = 'vs08v-dyn-row';
                row.innerHTML = '<input type="text" name="vs08v[voiture_periodes]['+i+'][label]" placeholder="Ex: Haute saison" style="flex:0 0 120px">' +
                    '<input type="date" name="vs08v[voiture_periodes]['+i+'][date_debut]">' +
                    '<span style="color:#999;flex-shrink:0">→</span>' +
                    '<input type="date" name="vs08v[voiture_periodes]['+i+'][date_fin]">' +
                    '<div class="vs08v-pi" style="flex:0 0 110px"><span>€</span><input type="number" name="vs08v[voiture_periodes]['+i+'][prix]" placeholder="Prix/séjour" step="0.01"></div>' +
                    '<button type="button" class="vs08v-rm" onclick="this.closest(\'.vs08v-dyn-row\').remove()">✕ Suppr.</button>';
                container.appendChild(row);
            });
        })();
        </script>

        <div class="vs08v-stitle">📋 Divers</div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>Frais de dossier /dossier</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[frais_dossier]" value="<?php echo esc_attr($m['frais_dossier']??0); ?>" step="0.01"></div><p class="vs08v-help">Facturé 1 fois par réservation</p></div>
        </div>

        <div class="vs08v-stitle">🏨 Hébergement hôtel (tarifs par période)</div>
        <div id="vs08v-saisons">
            <?php $saisons = $m['saisons'] ?? []; foreach($saisons as $i=>$s): ?>
            <div class="vs08v-dyn-row">
                <input type="text" name="vs08v[saisons][<?php echo $i;?>][label]" value="<?php echo esc_attr($s['label']??'');?>" placeholder="Haute saison" style="flex:0 0 130px">
                <input type="date" name="vs08v[saisons][<?php echo $i;?>][date_debut]" value="<?php echo esc_attr($s['date_debut']??'');?>">
                <span style="color:#999;flex-shrink:0">→</span>
                <input type="date" name="vs08v[saisons][<?php echo $i;?>][date_fin]" value="<?php echo esc_attr($s['date_fin']??'');?>">
                <div class="vs08v-pi" style="flex:0 0 110px"><span>€</span><input type="number" name="vs08v[saisons][<?php echo $i;?>][supp]" value="<?php echo esc_attr($s['supp']??'');?>" placeholder="€/nuit/pers"></div>
                <button type="button" class="vs08v-rm" onclick="this.closest('.vs08v-dyn-row').remove()">✕ Suppr.</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button vs08v-add-saison" style="margin-top:6px">+ Période hébergement</button>
    <?php }

    /* ============================================================ */
    public static function box_vols($post) {
        $m = self::get($post->ID);
        $aeroports = $m['aeroports'] ?? [['code'=>'CDG','ville'=>'Paris Charles de Gaulle'],['code'=>'LYS','ville'=>'Lyon Saint-Exupéry'],['code'=>'LIL','ville'=>'Lille Lesquin']];
        $dates = $m['dates_depart'] ?? [];
        ?>
        <style>
        .vs08v-aeroport-block{margin-bottom:20px;padding:12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;}
        .vs08v-aero-sub{margin-top:12px;padding-left:8px;border-left:3px solid #59b7b7;}
        .vs08v-aero-label{font-size:11px;font-weight:700;color:#374151;margin:10px 0 6px;}
        .vs08v-aero-periodes .vs08v-dyn-row{margin-bottom:6px;}
        .vs08v-aero-jours{display:flex;flex-wrap:wrap;gap:8px 14px;margin-top:6px;}
        .vs08v-jour-cb{font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer;}
        </style>

        <div class="vs08v-stitle" id="vs08v-dates-non-dispo" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:14px;">🚫 Dates de départ non disponibles</div>
        <p style="font-size:12px;color:#666;margin-bottom:10px">Les dates comprises dans ces plages ne seront <strong>pas sélectionnables</strong> sur le calendrier (tous les aéroports). Indiquez par ex. une période de fermeture ou de maintenance.</p>
        <div id="vs08v-periodes-fermees-vente">
            <?php
            $pfv = $m['periodes_fermees_vente'] ?? [];
            foreach ($pfv as $i => $p):
                $deb = $p['date_debut'] ?? '';
                $fin = $p['date_fin'] ?? '';
            ?>
            <div class="vs08v-dyn-row vs08v-pfv-row">
                <span style="font-size:12px;color:#5c2e2e;font-weight:600;">Du</span>
                <input type="date" name="vs08v[periodes_fermees_vente][<?php echo $i;?>][date_debut]" value="<?php echo esc_attr($deb);?>">
                <span style="font-size:12px;color:#5c2e2e;font-weight:600;">au</span>
                <input type="date" name="vs08v[periodes_fermees_vente][<?php echo $i;?>][date_fin]" value="<?php echo esc_attr($fin);?>">
                <button type="button" class="vs08v-rm" onclick="this.closest('.vs08v-pfv-row').remove()">✕ Suppr.</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button vs08v-add-pfv" style="margin-top:6px;margin-bottom:18px;">+ Ajouter une période non disponible</button>
        <script>
        (function(){
            var btn = document.querySelector('.vs08v-add-pfv');
            var container = document.getElementById('vs08v-periodes-fermees-vente');
            if (!btn || !container) return;
            btn.addEventListener('click', function(){
                var n = container.querySelectorAll('.vs08v-pfv-row').length;
                var row = document.createElement('div');
                row.className = 'vs08v-dyn-row vs08v-pfv-row';
                row.innerHTML = '<span style="font-size:12px;color:#5c2e2e;font-weight:600;">Du</span><input type="date" name="vs08v[periodes_fermees_vente]['+n+'][date_debut]"><span style="font-size:12px;color:#5c2e2e;font-weight:600;">au</span><input type="date" name="vs08v[periodes_fermees_vente]['+n+'][date_fin]"><button type="button" class="vs08v-rm" onclick="this.closest(\'.vs08v-pfv-row\').remove()">✕ Suppr.</button>';
                container.appendChild(row);
            });
        })();
        </script>

        <div class="vs08v-stitle">Aéroports de départ disponibles</div>
        <p style="font-size:12px;color:#666;margin-bottom:10px">Chaque aéroport peut avoir plusieurs périodes. <strong>Chaque période</strong> a ses propres dates (du… au…) et ses propres jours d'ouverture (cocher les jours avec vol direct pour cette période). Si vous ne cochez rien pour une période, les jours par défaut de l'aéroport s'appliquent.</p>
        <div id="vs08v-aeroports">
            <?php
            $jours_semaine = [1=>'Lun',2=>'Mar',3=>'Mer',4=>'Jeu',5=>'Ven',6=>'Sam',7=>'Dim'];
            foreach($aeroports as $i=>$a):
                $periodes = $a['periodes_vol'] ?? [];
                if (empty($periodes)) $periodes = [['date_debut'=>'','date_fin'=>'']];
                $jours_direct = $a['jours_direct'] ?? [1,2,3,4,5,6,7];
            ?>
            <div class="vs08v-aeroport-block" data-aero-idx="<?php echo $i;?>">
                <div class="vs08v-dyn-row">
                    <input type="text" name="vs08v[aeroports][<?php echo $i;?>][code]" value="<?php echo esc_attr(strtoupper((string)($a['code']??'')));?>" placeholder="CDG" style="flex:0 0 60px;text-transform:uppercase">
                    <input type="text" name="vs08v[aeroports][<?php echo $i;?>][ville]" value="<?php echo esc_attr($a['ville']??'');?>" placeholder="Paris Charles de Gaulle">
                    <button type="button" class="vs08v-rm vs08v-rm-aeroport" title="Supprimer cet aéroport">✕ Suppr.</button>
                </div>
                <div class="vs08v-aero-sub">
                    <div class="vs08v-aero-label">📅 Vol ouvert (périodes) — avant/après = fermé</div>
                    <div class="vs08v-aero-periodes">
                        <?php foreach($periodes as $p => $per):
                            $per_jours = $per['jours_direct'] ?? [];
                            if (empty($per_jours)) $per_jours = [1,2,3,4,5,6,7];
                        ?>
                        <div class="vs08v-periode-row" style="margin-bottom:12px;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;">
                            <div class="vs08v-dyn-row" style="margin-bottom:8px">
                                <span style="font-size:12px;color:#666">Du</span>
                                <input type="date" name="vs08v[aeroports][<?php echo $i;?>][periodes_vol][<?php echo $p;?>][date_debut]" value="<?php echo esc_attr($per['date_debut']??'');?>">
                                <span style="font-size:12px;color:#666">au</span>
                                <input type="date" name="vs08v[aeroports][<?php echo $i;?>][periodes_vol][<?php echo $p;?>][date_fin]" value="<?php echo esc_attr($per['date_fin']??'');?>">
                                <button type="button" class="button vs08v-rm vs08v-rm-periode" title="Supprimer cette période">✕</button>
                            </div>
                            <div style="font-size:11px;color:#6b7280;margin-bottom:4px">Jours avec vol direct pour cette période :</div>
                            <div class="vs08v-aero-jours" style="margin:0">
                                <?php foreach($jours_semaine as $num=>$lib): ?>
                                <label class="vs08v-jour-cb"><input type="checkbox" name="vs08v[aeroports][<?php echo $i;?>][periodes_vol][<?php echo $p;?>][jours_direct][]" value="<?php echo $num;?>" <?php checked(in_array($num, $per_jours));?>> <?php echo $lib; ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button vs08v-add-periode" data-aero-idx="<?php echo $i;?>" style="margin-bottom:10px;font-size:11px">+ Ajouter une période</button>
                    <div class="vs08v-aero-label">📆 Jours avec vol direct (cocher les jours où il y a un vol direct)</div>
                    <div class="vs08v-aero-jours">
                        <?php foreach($jours_semaine as $num=>$lib): ?>
                        <label class="vs08v-jour-cb"><input type="checkbox" name="vs08v[aeroports][<?php echo $i;?>][jours_direct][]" value="<?php echo $num;?>" <?php checked(in_array($num, $jours_direct));?>> <?php echo $lib; ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button vs08v-add-aeroport" style="margin-top:6px">+ Aéroport</button>
        <script>
        (function(){
            var root = document.getElementById('vs08v-aeroports');
            if (!root) return;
            root.addEventListener('click', function(ev) {
                var btn = ev.target && ev.target.closest && ev.target.closest('.vs08v-add-periode');
                if (!btn) return;
                ev.preventDefault(); ev.stopPropagation();
                var block = btn.closest('.vs08v-aeroport-block');
                if (!block) return;
                var idx = block.getAttribute('data-aero-idx');
                var container = block.querySelector('.vs08v-aero-periodes');
                if (!container || idx === null) return;
                var p = container.querySelectorAll('.vs08v-periode-row').length;
                var jours = [1,2,3,4,5,6,7];
                var libs = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
                var cbs = '';
                for (var j = 0; j < 7; j++) cbs += '<label class="vs08v-jour-cb"><input type="checkbox" name="vs08v[aeroports]['+idx+'][periodes_vol]['+p+'][jours_direct][]" value="'+jours[j]+'" checked> '+libs[j]+'</label>';
                var row = document.createElement('div');
                row.className = 'vs08v-periode-row';
                row.style.cssText = 'margin-bottom:12px;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;';
                row.innerHTML = '<div class="vs08v-dyn-row" style="margin-bottom:8px"><span style="font-size:12px;color:#666">Du</span><input type="date" name="vs08v[aeroports]['+idx+'][periodes_vol]['+p+'][date_debut]"><span style="font-size:12px;color:#666">au</span><input type="date" name="vs08v[aeroports]['+idx+'][periodes_vol]['+p+'][date_fin]"><button type="button" class="button vs08v-rm vs08v-rm-periode" title="Supprimer cette période">✕</button></div><div style="font-size:11px;color:#6b7280;margin-bottom:4px">Jours avec vol direct pour cette période :</div><div class="vs08v-aero-jours" style="margin:0">'+cbs+'</div>';
                container.appendChild(row);
            });
        })();
        </script>

        <div class="vs08v-stitle" style="margin-top:18px">Code IATA destination</div>
        <div class="vs08v-field-row">
            <div class="vs08v-field"><label>IATA arrivée</label><input type="text" name="vs08v[iata_dest]" value="<?php echo esc_attr(strtoupper((string)($m['iata_dest']??''))); ?>" placeholder="FAO" style="text-transform:uppercase"></div>
            <div class="vs08v-field"><label>Ville arrivée</label><input type="text" name="vs08v[ville_arrivee]" value="<?php echo esc_attr($m['ville_arrivee']??''); ?>" placeholder="Faro"></div>
            <div class="vs08v-field"><label>Type transport</label>
                <select name="vs08v[transport_type]">
                    <option value="vol" <?php selected($m['transport_type']??'vol','vol');?>>✈️ Vol inclus</option>
                    <option value="vol_option" <?php selected($m['transport_type']??'vol','vol_option');?>>✈️ Vol en option</option>
                    <option value="sans_vol" <?php selected($m['transport_type']??'vol','sans_vol');?>>🏨 Séjour seul (sans vol)</option>
                    <option value="voiture" <?php selected($m['transport_type']??'vol','voiture');?>>🚗 Location voiture</option>
                </select>
            </div>
        </div>

        <div class="vs08v-stitle">Dates de départ disponibles</div>
        <p style="font-size:12px;color:#888;margin-bottom:8px">Les clients ne pourront choisir que parmi ces dates. Indiquez le nb de places restantes.</p>
        <div id="vs08v-dates-depart">
            <?php foreach($dates as $i=>$d): ?>
            <div class="vs08v-dyn-row">
                <input type="date" name="vs08v[dates_depart][<?php echo $i;?>][date]" value="<?php echo esc_attr($d['date']??'');?>">
                <input type="number" name="vs08v[dates_depart][<?php echo $i;?>][places]" value="<?php echo esc_attr($d['places']??20);?>" placeholder="Places" style="flex:0 0 70px" title="Nb places restantes">
                <select name="vs08v[dates_depart][<?php echo $i;?>][statut]" style="flex:0 0 160px">
                    <option value="dispo" <?php selected($d['statut']??'dispo','dispo');?>>✅ Disponible</option>
                    <option value="complet" <?php selected($d['statut']??'dispo','complet');?>>🔴 Complet</option>
                    <option value="derniere_place" <?php selected($d['statut']??'dispo','derniere_place');?>>⚠️ Dernières places</option>
                    <option value="garantie" <?php selected($d['statut']??'dispo','garantie');?>>🟢 Départ garanti</option>
                </select>
                <button type="button" class="vs08v-rm" onclick="this.closest('.vs08v-dyn-row').remove()">✕ Suppr.</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button vs08v-add-date" style="margin-top:6px">+ Date de départ</button>
    <?php }

    /* ============================================================ */
    public static function box_programme($post) {
        $m = self::get($post->ID);
        $jours = $m['programme'] ?? []; ?>
        <div id="vs08v-programme-list">
        <?php foreach($jours as $i=>$j): ?>
        <div class="vs08v-option-block">
            <div class="vs08v-field-row">
                <div class="vs08v-field" style="flex:0 0 110px"><label>Référence jour</label><input type="text" name="vs08v[programme][<?php echo $i;?>][num]" value="<?php echo esc_attr($j['num']??'Jour '.($i+1));?>"></div>
                <div class="vs08v-field vs08v-field-3"><label>Titre de la journée</label><input type="text" name="vs08v[programme][<?php echo $i;?>][titre]" value="<?php echo esc_attr($j['titre']??'');?>" placeholder="Arrivée & installation..."></div>
                <div class="vs08v-field" style="justify-content:flex-end;padding-top:20px"><button type="button" class="vs08v-rm" onclick="this.closest('.vs08v-option-block').remove()">✕</button></div>
            </div>
            <div class="vs08v-field"><label>Description détaillée</label><textarea name="vs08v[programme][<?php echo $i;?>][desc]" rows="2" placeholder="Décrivez les activités de la journée..."><?php echo esc_textarea($j['desc']??'');?></textarea></div>
            <div class="vs08v-field"><label>Tags (séparés par des virgules)</label><input type="text" name="vs08v[programme][<?php echo $i;?>][tags]" value="<?php echo esc_attr($j['tags']??'');?>" placeholder="✈️ Vol, 🏨 Hôtel, ⛳ Golf, 🍽️ Dîner inclus"></div>
        </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="button button-primary vs08v-add-jour">+ Ajouter un jour</button>
    <?php }

    /* ============================================================ */
    public static function box_annulation($post) {
        $m = self::get($post->ID);
        $paliers = $m['annulation'] ?? [
            ['jours_avant'=>'90','retenue'=>'0','label'=>'Plus de 90j avant départ'],
            ['jours_avant'=>'60','retenue'=>'30','label'=>'Entre 60 et 90j avant départ'],
            ['jours_avant'=>'30','retenue'=>'50','label'=>'Entre 30 et 60j avant départ'],
            ['jours_avant'=>'0','retenue'=>'100','label'=>'Moins de 30j avant départ'],
        ];
        ?>
        <p class="vs08v-notice">⚠️ Ces conditions sont affichées au client lors du tunnel de réservation et figurent sur la confirmation de commande WooCommerce.</p>
        <div class="vs08v-stitle">Paliers d'annulation</div>
        <div id="vs08v-annulation-list">
        <?php foreach($paliers as $i=>$p): ?>
        <div class="vs08v-dyn-row">
            <input type="text" name="vs08v[annulation][<?php echo $i;?>][label]" value="<?php echo esc_attr($p['label']??'');?>" placeholder="Libellé de la tranche" style="flex:2">
            <span style="flex-shrink:0;color:#888;font-size:12px">Jours avant :</span>
            <input type="number" name="vs08v[annulation][<?php echo $i;?>][jours_avant]" value="<?php echo esc_attr($p['jours_avant']??'');?>" placeholder="30" style="flex:0 0 60px" min="0">
            <span style="flex-shrink:0;color:#888;font-size:12px">Retenue :</span>
            <div class="vs08v-pi" style="flex:0 0 90px"><span>%</span><input type="number" name="vs08v[annulation][<?php echo $i;?>][retenue]" value="<?php echo esc_attr($p['retenue']??'');?>" placeholder="30" min="0" max="100"></div>
            <button type="button" class="vs08v-rm" onclick="this.closest('.vs08v-dyn-row').remove()">✕ Suppr.</button>
        </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="button vs08v-add-annulation" style="margin-top:6px">+ Ajouter un palier</button>

        <div class="vs08v-stitle" style="margin-top:20px">Texte de conditions personnalisé</div>
        <div class="vs08v-field"><label>Texte libre affiché dans le bon de commande (optionnel)</label><textarea name="vs08v[annulation_texte]" rows="3" placeholder="Toute annulation doit être notifiée par écrit. Les remboursements interviennent sous 30 jours..."><?php echo esc_textarea($m['annulation_texte']??''); ?></textarea></div>
    <?php }

    /* ============================================================ */
    public static function box_galerie($post) {
        $m = self::get($post->ID);
        $imgs = $m['galerie'] ?? []; ?>
        <div id="vs08v-galerie" style="margin-bottom:10px">
        <?php foreach($imgs as $img): ?>
        <div class="vs08v-img-row" style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <img src="<?php echo esc_url($img);?>" style="width:60px;height:45px;object-fit:cover;border-radius:4px;border:1px solid #dde1e7">
            <input type="hidden" name="vs08v[galerie][]" value="<?php echo esc_url($img);?>">
            <button type="button" class="vs08v-rm" onclick="this.closest('.vs08v-img-row').remove()">✕</button>
        </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="button button-primary" id="vs08v-add-photos" style="width:100%">📷 Ajouter photos</button>
        <p class="vs08v-help" style="margin-top:4px">1ère photo = image principale. Glissez pour réordonner.</p>
    <?php }

    /* ============================================================ */
    public static function box_regles($post) {
        $m = self::get($post->ID); ?>
        <?php
        $acompte_mode = $m['acompte_mode'] ?? 'pct';
        $acompte_pct  = $m['acompte_pct']  ?? 30;
        $acompte_eur  = $m['acompte_eur']  ?? '';
        ?>
        <div class="vs08v-field">
            <label>Acompte requis à la réservation</label>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <label style="display:flex;align-items:center;gap:5px;font-weight:600;cursor:pointer">
                    <input type="radio" name="vs08v[acompte_mode]" value="pct" <?php checked($acompte_mode,'pct');?> onchange="vs08AcompteToggle()"> En pourcentage (%)
                </label>
                <label style="display:flex;align-items:center;gap:5px;font-weight:600;cursor:pointer">
                    <input type="radio" name="vs08v[acompte_mode]" value="eur" <?php checked($acompte_mode,'eur');?> onchange="vs08AcompteToggle()"> Montant fixe (€)
                </label>
            </div>
            <div id="vs08v-acompte-pct" style="<?php echo $acompte_mode==='eur'?'display:none':'';?>">
                <div class="vs08v-pi"><span>%</span><input type="number" name="vs08v[acompte_pct]" value="<?php echo esc_attr($acompte_pct);?>" min="0" max="100" id="vs08v-acompte-pct-val"></div>
                <p class="vs08v-help">Ex: 30 → client paie 30% à la résa, solde au J-<?php echo esc_html($m['delai_solde']??30); ?></p>
            </div>
            <div id="vs08v-acompte-eur" style="<?php echo $acompte_mode!=='eur'?'display:none':'';?>">
                <div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[acompte_eur]" value="<?php echo esc_attr($acompte_eur);?>" min="0" step="0.01" placeholder="Ex: 500" id="vs08v-acompte-eur-val"></div>
                <p class="vs08v-help">Montant fixe en euros quelle que soit la configuration</p>
            </div>
        </div>
        <script>
        function vs08AcompteToggle(){
            var mode = document.querySelector('input[name="vs08v[acompte_mode]"]:checked')?.value || 'pct';
            document.getElementById('vs08v-acompte-pct').style.display = mode==='pct' ? '' : 'none';
            document.getElementById('vs08v-acompte-eur').style.display = mode==='eur' ? '' : 'none';
        }
        </script>
        <div class="vs08v-field" style="margin-top:10px"><label>Solde dû J- (jours avant départ)</label><input type="number" name="vs08v[delai_solde]" value="<?php echo esc_attr($m['delai_solde']??30);?>" min="0" max="90"></div>
        <hr class="vs08v-sep">
        <div class="vs08v-field"><label>Voyageurs min.</label><input type="number" name="vs08v[nb_min]" value="<?php echo esc_attr($m['nb_min']??2);?>" min="1"></div>
        <div class="vs08v-field" style="margin-top:10px"><label>Voyageurs max.</label><input type="number" name="vs08v[nb_max]" value="<?php echo esc_attr($m['nb_max']??20);?>" min="1"></div>
        <hr class="vs08v-sep">
        <div class="vs08v-field"><label>Licence FFGolf requise</label>
            <select name="vs08v[licence_ffgolf]">
                <option value="non" <?php selected($m['licence_ffgolf']??'non','non');?>>Non requis</option>
                <option value="recommandee" <?php selected($m['licence_ffgolf']??'non','recommandee');?>>Recommandée</option>
                <option value="obligatoire" <?php selected($m['licence_ffgolf']??'non','obligatoire');?>>Obligatoire</option>
            </select>
        </div>
        <div class="vs08v-field" style="margin-top:10px"><label>Statut réservation</label>
            <select name="vs08v[statut]">
                <option value="actif" <?php selected($m['statut']??'actif','actif');?>>✅ Réservation ouverte</option>
                <option value="devis" <?php selected($m['statut']??'actif','devis');?>>📝 Devis seulement</option>
                <option value="complet" <?php selected($m['statut']??'actif','complet');?>>🔴 Complet</option>
                <option value="archive" <?php selected($m['statut']??'actif','archive');?>>📦 Archivé</option>
            </select>
        </div>
        <hr class="vs08v-sep">
        <div class="vs08v-field"><label>📈 Marge (appliquée à tous les tarifs + vols en front)</label>
            <label style="display:flex;align-items:center;gap:6px;margin-bottom:8px;cursor:pointer">
                <input type="checkbox" name="vs08v[marge_activate]" value="1" <?php checked($m['marge_activate'] ?? '1');?> onchange="vs08MargeToggle()"> Activer la marge
            </label>
            <div id="vs08v-marge-fields" style="<?php echo empty($m['marge_activate'] ?? '1') ? 'display:none' : '';?>">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                    <label style="display:flex;align-items:center;gap:4px;cursor:pointer"><input type="radio" name="vs08v[marge_type]" value="pct" <?php checked($m['marge_type']??'pct','pct');?>> %</label>
                    <label style="display:flex;align-items:center;gap:4px;cursor:pointer"><input type="radio" name="vs08v[marge_type]" value="eur" <?php checked($m['marge_type']??'pct','eur');?>> €</label>
                </div>
                <div class="vs08v-pi"><span id="vs08v-marge-symbol">%</span><input type="number" name="vs08v[marge_valeur]" value="<?php echo esc_attr($m['marge_valeur']??'15'); ?>" step="0.01" min="0" placeholder="<?php echo (($m['marge_type']??'pct')==='eur' ? '50' : '15');?>" id="vs08v-marge-valeur"></div>
                <p class="vs08v-help">S'applique au total (hébergement, vol, options). Inclut le vol trouvé en temps réel sur le tunnel de réservation.</p>
            </div>
        </div>
        <script>
        function vs08MargeToggle(){
            var on = document.querySelector('input[name="vs08v[marge_activate]"]')?.checked;
            document.getElementById('vs08v-marge-fields').style.display = on ? '' : 'none';
        }
        document.querySelectorAll('input[name="vs08v[marge_type]"]').forEach(function(r){
            r.addEventListener('change',function(){
                document.getElementById('vs08v-marge-symbol').textContent = this.value === 'eur' ? '€' : '%';
                document.getElementById('vs08v-marge-valeur').placeholder = this.value === 'eur' ? '50' : '10';
            });
        });
        </script>
    <?php }

    /* ============================================================ */
    public static function save($post_id, $post) {
        if (!isset($_POST['vs08v_nonce']) || !wp_verify_nonce($_POST['vs08v_nonce'],'vs08v_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== 'vs08_voyage') return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (!isset($_POST['vs08v'])) return;
        $data = wp_unslash($_POST['vs08v']);
        if (!is_array($data)) return;
        // Codes IATA toujours en majuscules
        if (isset($data['iata_dest']) && is_string($data['iata_dest'])) {
            $data['iata_dest'] = strtoupper(trim($data['iata_dest']));
        }
        if (!empty($data['aeroports']) && is_array($data['aeroports'])) {
            foreach ($data['aeroports'] as $k => $a) {
                if (isset($a['code']) && is_string($a['code'])) {
                    $data['aeroports'][$k]['code'] = strtoupper(trim($a['code']));
                }
                if (isset($a['periodes_vol']) && is_array($a['periodes_vol'])) {
                    $data['aeroports'][$k]['periodes_vol'] = array_values(array_map(function ($p) {
                        $out = ['date_debut' => isset($p['date_debut']) ? sanitize_text_field($p['date_debut']) : '', 'date_fin' => isset($p['date_fin']) ? sanitize_text_field($p['date_fin']) : ''];
                        if (isset($p['jours_direct']) && is_array($p['jours_direct'])) {
                            $out['jours_direct'] = array_values(array_map('intval', array_filter($p['jours_direct'])));
                        }
                        return $out;
                    }, array_filter($a['periodes_vol'], function ($p) {
                        return !empty($p['date_debut']) || !empty($p['date_fin']);
                    })));
                }
                if (isset($a['jours_direct']) && is_array($a['jours_direct'])) {
                    $data['aeroports'][$k]['jours_direct'] = array_map('intval', array_values(array_filter($a['jours_direct'])));
                }
            }
        }
        // Périodes fermées à la vente (s'appliquent à tous les aéroports)
        if (isset($data['periodes_fermees_vente']) && is_array($data['periodes_fermees_vente'])) {
            $data['periodes_fermees_vente'] = array_values(array_filter(array_map(function ($p) {
                $date_debut = isset($p['date_debut']) ? sanitize_text_field($p['date_debut']) : '';
                $date_fin   = isset($p['date_fin']) ? sanitize_text_field($p['date_fin']) : '';
                if ($date_debut === '' && $date_fin === '') return null;
                return ['date_debut' => $date_debut, 'date_fin' => $date_fin];
            }, $data['periodes_fermees_vente'])));
        }
        // Périodes location voiture : garder uniquement les lignes avec dates, sanitize
        if (!empty($data['voiture_periodes']) && is_array($data['voiture_periodes'])) {
            $data['voiture_periodes'] = array_values(array_filter(array_map(function ($p) {
                $date_debut = isset($p['date_debut']) ? sanitize_text_field($p['date_debut']) : '';
                $date_fin   = isset($p['date_fin']) ? sanitize_text_field($p['date_fin']) : '';
                if ($date_debut === '' && $date_fin === '') return null;
                return [
                    'label'      => isset($p['label']) ? sanitize_text_field($p['label']) : '',
                    'date_debut' => $date_debut,
                    'date_fin'   => $date_fin,
                    'prix'       => isset($p['prix']) ? floatval($p['prix']) : 0,
                ];
            }, $data['voiture_periodes'])));
        }
        // Prix bagages
        if (isset($data['prix_bagage_soute'])) {
            $data['prix_bagage_soute'] = floatval($data['prix_bagage_soute']);
        }
        if (isset($data['prix_bagage_golf'])) {
            $data['prix_bagage_golf'] = floatval($data['prix_bagage_golf']);
        }
        // Prix transfert (par personne)
        if (isset($data['prix_transfert'])) {
            $data['prix_transfert'] = floatval($data['prix_transfert']);
        }
        // Détails véhicule location
        if (!empty($data['voiture_details']) && is_array($data['voiture_details'])) {
            $vd_keys = ['modele','categorie','age_min','anciennete_permis','boite','clim','portes','places','carburant','emplacement','kilometrage','rc','assurance','image'];
            $clean = [];
            foreach ($vd_keys as $k) {
                $clean[$k] = isset($data['voiture_details'][$k]) ? sanitize_text_field($data['voiture_details'][$k]) : '';
            }
            $data['voiture_details'] = $clean;
        }
        update_post_meta($post_id, 'vs08v_data', $data);
    }

    public static function get($post_id) {
        return get_post_meta($post_id, 'vs08v_data', true) ?: [];
    }

    /** Drapeau effectif: meta flag prioritaire, sinon fallback pays/destination */
    public static function resolve_flag($m) {
        if (!is_array($m)) return '';
        $flag = trim((string) ($m['flag'] ?? ''));
        if ($flag !== '') return $flag;

        $pays = trim((string) ($m['pays'] ?? ''));
        if ($pays !== '') {
            $f = self::get_flag_emoji($pays);
            if ($f !== '') return $f;
        }

        $dest = trim((string) ($m['destination'] ?? ''));
        if ($dest !== '') {
            $f = self::get_flag_emoji($dest);
            if ($f !== '') return $f;
        }

        return '';
    }

    /** Drapeau emoji à partir du nom du pays (fallback si meta flag vide) */
    public static function get_flag_emoji($pays) {
        if (empty($pays) || !is_string($pays)) return '';
        $pays = trim($pays);
        $map = [
            'Portugal'=>'PT','Espagne'=>'ES','Maroc'=>'MA','Turquie'=>'TR','Irlande'=>'IE',
            'Thaïlande'=>'TH','France'=>'FR','Italie'=>'IT','Grèce'=>'GR','Tunisie'=>'TN',
            'Écosse'=>'GB-SCT','Angleterre'=>'GB-ENG','Pays de Galles'=>'GB-WLS','Royaume-Uni'=>'GB',
            'Allemagne'=>'DE','Autriche'=>'AT','Suisse'=>'CH','Belgique'=>'BE','Pays-Bas'=>'NL',
            'Croatie'=>'HR','Monténégro'=>'ME','Bulgarie'=>'BG','Roumanie'=>'RO','Pologne'=>'PL',
            'République Tchèque'=>'CZ','Slovaquie'=>'SK','Hongrie'=>'HU','Slovénie'=>'SI',
            'Chypre'=>'CY','Malte'=>'MT','Islande'=>'IS','Norvège'=>'NO','Suède'=>'SE',
            'Finlande'=>'FI','Danemark'=>'DK','Estonie'=>'EE','Lettonie'=>'LV','Lituanie'=>'LT',
            'Égypte'=>'EG','Afrique du Sud'=>'ZA','Maurice'=>'MU','Sénégal'=>'SN','Cap-Vert'=>'CV',
            'Mexique'=>'MX','République Dominicaine'=>'DO','Cuba'=>'CU','Costa Rica'=>'CR',
            'États-Unis'=>'US','Canada'=>'CA','Brésil'=>'BR','Argentine'=>'AR','Colombie'=>'CO',
            'Vietnam'=>'VN','Indonésie'=>'ID','Japon'=>'JP','Corée du Sud'=>'KR','Chine'=>'CN',
            'Inde'=>'IN','Sri Lanka'=>'LK','Maldives'=>'MV','Émirats arabes unis'=>'AE',
            'Oman'=>'OM','Jordanie'=>'JO','Australie'=>'AU','Nouvelle-Zélande'=>'NZ',
            'Cambodge'=>'KH','Philippines'=>'PH','Malaisie'=>'MY','Singapour'=>'SG',
            'Îles Canaries'=>'ES','Canaries'=>'ES','Marrakech'=>'MA','Agadir'=>'MA','Saidia'=>'MA','Tanger'=>'MA',
            'Majorque'=>'ES','Minorque'=>'ES','Ibiza'=>'ES','Mallorca'=>'ES','Tenerife'=>'ES','Lanzarote'=>'ES','Fuerteventura'=>'ES','Gran Canaria'=>'ES',
            'Algarve'=>'PT','Madère'=>'PT','Madeira'=>'PT','Lisbonne'=>'PT','Djerba'=>'TN','Tunisie'=>'TN',
        ];
        $code = $map[$pays] ?? '';
        if (empty($code)) return '';
        $code = strtoupper(substr($code, 0, 2));
        if (strlen($code) < 2) return '';
        return mb_chr(0x1F1E6 + ord($code[0]) - 65, 'UTF-8') . mb_chr(0x1F1E6 + ord($code[1]) - 65, 'UTF-8');
    }
}
