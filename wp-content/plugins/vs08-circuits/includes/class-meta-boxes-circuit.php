<?php
if (!defined('ABSPATH')) exit;

class VS08C_MetaBoxes {
    const META_KEY = 'vs08c_data';

    public static function register() {
        add_meta_box('vs08c_general', 'Informations générales', [__CLASS__, 'box_general'], 'vs08_circuit', 'normal', 'high');
        add_meta_box('vs08c_periodes', 'Prix par période', [__CLASS__, 'box_periodes'], 'vs08_circuit', 'normal', 'high');
        add_meta_box('vs08c_itineraire', 'Itinéraire (jour par jour)', [__CLASS__, 'box_itineraire'], 'vs08_circuit', 'normal', 'default');
        add_meta_box('vs08c_inclus', 'Inclus / Non inclus', [__CLASS__, 'box_inclus'], 'vs08_circuit', 'normal', 'default');
        add_meta_box('vs08c_galerie', 'Galerie photos', [__CLASS__, 'box_galerie'], 'vs08_circuit', 'side', 'default');
    }

    public static function get($post_id) {
        return get_post_meta($post_id, self::META_KEY, true) ?: [];
    }

    public static function box_general($post) {
        wp_nonce_field('vs08c_save', 'vs08c_nonce');
        $m = self::get($post->ID);
        $duree_jours = $m['duree_jours'] ?? 8;
        $sous_titre = $m['sous_titre'] ?? '';
        ?>
        <div class="vs08c-field-row">
            <div class="vs08c-field">
                <label>Durée (nombre de jours)</label>
                <input type="number" name="vs08c[duree_jours]" value="<?php echo esc_attr($duree_jours); ?>" min="1" max="99" placeholder="8">
            </div>
            <div class="vs08c-field vs08c-field-wide">
                <label>Sous-titre / accroche (affiché sous le titre)</label>
                <input type="text" name="vs08c[sous_titre]" value="<?php echo esc_attr($sous_titre); ?>" placeholder="Ex: De Lisbonne à Porto, l'essentiel du Portugal">
            </div>
        </div>
        <p class="vs08c-help">Les catégories (Destination, Thème, Durée) se gèrent dans les blocs à droite. Utilisez l’éditeur pour la description complète du circuit.</p>
        <?php
    }

    public static function box_periodes($post) {
        $m = self::get($post->ID);
        $periodes = $m['periodes'] ?? [];
        if (empty($periodes)) $periodes = [['date_debut' => '', 'date_fin' => '', 'prix' => '', 'label' => '']];
        ?>
        <p class="vs08c-desc">Définissez les périodes tarifaires. Le prix s’affiche « à partir de » selon la date choisie. Le client pourra ensuite rechercher un vol.</p>
        <div id="vs08c-periodes">
            <?php foreach ($periodes as $i => $p): ?>
            <div class="vs08c-dyn-row">
                <input type="text" name="vs08c[periodes][<?php echo $i; ?>][label]" value="<?php echo esc_attr($p['label'] ?? ''); ?>" placeholder="Ex: Haute saison" style="width:120px">
                <label class="vs08c-inline">Du</label>
                <input type="date" name="vs08c[periodes][<?php echo $i; ?>][date_debut]" value="<?php echo esc_attr($p['date_debut'] ?? ''); ?>">
                <label class="vs08c-inline">au</label>
                <input type="date" name="vs08c[periodes][<?php echo $i; ?>][date_fin]" value="<?php echo esc_attr($p['date_fin'] ?? ''); ?>">
                <span class="vs08c-euro"><input type="number" name="vs08c[periodes][<?php echo $i; ?>][prix]" value="<?php echo esc_attr($p['prix'] ?? ''); ?>" placeholder="Prix/pers" step="0.01" min="0"> €/pers</span>
                <button type="button" class="button vs08c-rm" onclick="this.closest('.vs08c-dyn-row').remove()">✕</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button vs08c-add-periode">+ Ajouter une période</button>
        <script>
        (function(){
            document.querySelector('.vs08c-add-periode')?.addEventListener('click', function(){
                var c = document.getElementById('vs08c-periodes');
                var i = c.querySelectorAll('.vs08c-dyn-row').length;
                var row = document.createElement('div');
                row.className = 'vs08c-dyn-row';
                row.innerHTML = '<input type="text" name="vs08c[periodes]['+i+'][label]" placeholder="Ex: Haute saison" style="width:120px"><label class="vs08c-inline">Du</label><input type="date" name="vs08c[periodes]['+i+'][date_debut]"><label class="vs08c-inline">au</label><input type="date" name="vs08c[periodes]['+i+'][date_fin]"><span class="vs08c-euro"><input type="number" name="vs08c[periodes]['+i+'][prix]" placeholder="Prix/pers" step="0.01" min="0"> €/pers</span><button type="button" class="button vs08c-rm" onclick="this.closest(\'.vs08c-dyn-row\').remove()">✕</button>';
                c.appendChild(row);
            });
        })();
        </script>
        <?php
    }

    public static function box_itineraire($post) {
        $m = self::get($post->ID);
        $jours = $m['itineraire'] ?? [];
        if (empty($jours)) $jours = [['titre' => '', 'desc' => '']];
        ?>
        <p class="vs08c-desc">Décrivez chaque jour du circuit. Titre court + description.</p>
        <div id="vs08c-itineraire">
            <?php foreach ($jours as $i => $j): ?>
            <div class="vs08c-jour-block">
                <div class="vs08c-jour-num">Jour <?php echo $i + 1; ?></div>
                <div class="vs08c-jour-fields">
                    <input type="text" name="vs08c[itineraire][<?php echo $i; ?>][titre]" value="<?php echo esc_attr($j['titre'] ?? ''); ?>" placeholder="Titre du jour (ex: Lisbonne – Coimbra)">
                    <textarea name="vs08c[itineraire][<?php echo $i; ?>][desc]" rows="3" placeholder="Description de la journée..."><?php echo esc_textarea($j['desc'] ?? ''); ?></textarea>
                </div>
                <button type="button" class="button vs08c-rm" onclick="this.closest('.vs08c-jour-block').remove()">✕</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button vs08c-add-jour">+ Ajouter un jour</button>
        <script>
        (function(){
            document.querySelector('.vs08c-add-jour')?.addEventListener('click', function(){
                var c = document.getElementById('vs08c-itineraire');
                var i = c.querySelectorAll('.vs08c-jour-block').length;
                var row = document.createElement('div');
                row.className = 'vs08c-jour-block';
                row.innerHTML = '<div class="vs08c-jour-num">Jour '+(i+1)+'</div><div class="vs08c-jour-fields"><input type="text" name="vs08c[itineraire]['+i+'][titre]" placeholder="Titre du jour"><textarea name="vs08c[itineraire]['+i+'][desc]" rows="3" placeholder="Description..."></textarea></div><button type="button" class="button vs08c-rm" onclick="this.closest(\'.vs08c-jour-block\').remove()">✕</button>';
                c.appendChild(row);
            });
        })();
        </script>
        <?php
    }

    public static function box_inclus($post) {
        $m = self::get($post->ID);
        $inclus = $m['inclus'] ?? [];
        $non_inclus = $m['non_inclus'] ?? [];
        $inclus_str = is_array($inclus) ? implode("\n", $inclus) : $inclus;
        $non_inclus_str = is_array($non_inclus) ? implode("\n", $non_inclus) : $non_inclus;
        ?>
        <div class="vs08c-field-row vs08c-two-cols">
            <div class="vs08c-field">
                <label>✅ Inclus (un élément par ligne)</label>
                <textarea name="vs08c[inclus]" rows="8" placeholder="Transport aérien&#10;Hébergement en hôtels 3*&#10;Petit-déjeuners&#10;Guide francophone"><?php echo esc_textarea($inclus_str); ?></textarea>
            </div>
            <div class="vs08c-field">
                <label>❌ Non inclus</label>
                <textarea name="vs08c[non_inclus]" rows="8" placeholder="Assurance voyage&#10;Dîners&#10;Dépenses personnelles"><?php echo esc_textarea($non_inclus_str); ?></textarea>
            </div>
        </div>
        <?php
    }

    public static function box_galerie($post) {
        $m = self::get($post->ID);
        $galerie = $m['galerie'] ?? [];
        $ids = is_array($galerie) ? implode(',', $galerie) : $galerie;
        ?>
        <p class="vs08c-desc">Images du circuit (la première sert d’image principale si pas d’image à la une).</p>
        <input type="hidden" id="vs08c_galerie_ids" name="vs08c[galerie_ids]" value="<?php echo esc_attr($ids); ?>">
        <button type="button" class="button" id="vs08c-upload-gal">Choisir des images</button>
        <div id="vs08c-gal-preview" class="vs08c-gal-preview"></div>
        <?php
    }

    public static function save($post_id, $post) {
        if (!isset($_POST['vs08c_nonce']) || !wp_verify_nonce($_POST['vs08c_nonce'], 'vs08c_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== 'vs08_circuit') return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (!isset($_POST['vs08c'])) return;

        $data = wp_unslash($_POST['vs08c']);
        if (!is_array($data)) return;

        $out = [];
        $out['duree_jours'] = isset($data['duree_jours']) ? max(1, (int) $data['duree_jours']) : 8;
        $out['sous_titre'] = isset($data['sous_titre']) ? sanitize_text_field($data['sous_titre']) : '';

        if (!empty($data['periodes']) && is_array($data['periodes'])) {
            $out['periodes'] = array_values(array_filter(array_map(function ($p) {
                $deb = isset($p['date_debut']) ? sanitize_text_field($p['date_debut']) : '';
                $fin = isset($p['date_fin']) ? sanitize_text_field($p['date_fin']) : '';
                if ($deb === '' && $fin === '') return null;
                return [
                    'label'       => isset($p['label']) ? sanitize_text_field($p['label']) : '',
                    'date_debut'  => $deb,
                    'date_fin'    => $fin,
                    'prix'        => isset($p['prix']) ? floatval($p['prix']) : 0,
                ];
            }, $data['periodes'])));
        }

        if (!empty($data['itineraire']) && is_array($data['itineraire'])) {
            $out['itineraire'] = array_values(array_filter(array_map(function ($j) {
                $titre = isset($j['titre']) ? sanitize_text_field($j['titre']) : '';
                $desc = isset($j['desc']) ? sanitize_textarea_field($j['desc']) : '';
                if ($titre === '' && $desc === '') return null;
                return ['titre' => $titre, 'desc' => $desc];
            }, $data['itineraire'])));
        }

        if (isset($data['inclus'])) {
            $lines = array_filter(array_map('trim', explode("\n", $data['inclus'])));
            $out['inclus'] = array_values(array_map('sanitize_text_field', $lines));
        }
        if (isset($data['non_inclus'])) {
            $lines = array_filter(array_map('trim', explode("\n", $data['non_inclus'])));
            $out['non_inclus'] = array_values(array_map('sanitize_text_field', $lines));
        }

        if (!empty($data['galerie_ids'])) {
            $ids = array_map('intval', array_filter(explode(',', $data['galerie_ids'])));
            $out['galerie'] = $ids;
        }

        update_post_meta($post_id, self::META_KEY, $out);

        // Prix min pour le tri dans la recherche
        $prix_min = VS08C_Search::get_prix_min_for_circuit($out);
        update_post_meta($post_id, 'vs08c_prix_min', $prix_min);
    }
}
