<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin : édition des avis Google 5* affichés en carousel sur la page d'accueil.
 * Option vs08v_google_reviews (tableau d'objets initials, name, trip, text).
 */
class VS08V_Avis_Admin {

    const OPTION_KEY = 'vs08v_google_reviews';

    public static function register() {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'save']);
    }

    public static function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=vs08_voyage',
            'Avis Google',
            'Avis Google',
            'manage_options',
            'vs08v-avis',
            [__CLASS__, 'render_page']
        );
    }

    public static function get_reviews() {
        $v = get_option(self::OPTION_KEY, []);
        return is_array($v) ? $v : [];
    }

    public static function save() {
        if (!isset($_POST['vs08v_avis_nonce']) || !wp_verify_nonce($_POST['vs08v_avis_nonce'], 'vs08v_avis_save')) {
            return;
        }
        if (!current_user_can('manage_options')) return;

        $reviews = [];
        $n = isset($_POST['vs08v_avis_n']) ? intval($_POST['vs08v_avis_n']) : 0;
        for ($i = 0; $i < $n; $i++) {
            $initials = sanitize_text_field(wp_unslash($_POST['avis_initials'][$i] ?? ''));
            $name = sanitize_text_field(wp_unslash($_POST['avis_name'][$i] ?? ''));
            $trip = sanitize_text_field(wp_unslash($_POST['avis_trip'][$i] ?? ''));
            $text = sanitize_textarea_field(wp_unslash($_POST['avis_text'][$i] ?? ''));
            if ($name || $text) {
                $reviews[] = [
                    'initials' => $initials ?: mb_substr($name, 0, 2),
                    'name'     => $name,
                    'trip'     => $trip,
                    'text'     => $text,
                ];
            }
        }
        update_option(self::OPTION_KEY, $reviews, false);
        add_settings_error(
            'vs08v_avis',
            'saved',
            'Avis enregistrés.',
            'success'
        );
    }

    public static function render_page() {
        $reviews = self::get_reviews();
        if (empty($reviews)) {
            $reviews = [
                ['initials' => 'MR', 'name' => 'Michel R.', 'trip' => 'Portugal Algarve — Oct. 2024', 'text' => 'Séjour parfait au Portugal. Parcours magnifiques, hôtel de rêve.'],
            ];
        }
        ?>
        <div class="wrap">
            <h1>Avis clients Google (5★)</h1>
            <p>Ces avis s'affichent en carousel sur la page d'accueil. Uniquement des avis 5 étoiles avec du texte.</p>
            <form method="post" action="">
                <?php wp_nonce_field('vs08v_avis_save', 'vs08v_avis_nonce'); ?>
                <input type="hidden" name="vs08v_avis_n" value="<?php echo count($reviews); ?>">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:80px">Initiales</th>
                            <th style="width:140px">Nom</th>
                            <th style="width:180px">Séjour / date</th>
                            <th>Texte de l'avis</th>
                        </tr>
                    </thead>
                    <tbody id="vs08v-avis-tbody">
                        <?php foreach ($reviews as $i => $r) : ?>
                        <tr>
                            <td><input type="text" name="avis_initials[<?php echo $i; ?>]" value="<?php echo esc_attr($r['initials'] ?? ''); ?>" maxlength="4" style="width:100%"></td>
                            <td><input type="text" name="avis_name[<?php echo $i; ?>]" value="<?php echo esc_attr($r['name'] ?? ''); ?>" style="width:100%"></td>
                            <td><input type="text" name="avis_trip[<?php echo $i; ?>]" value="<?php echo esc_attr($r['trip'] ?? ''); ?>" style="width:100%"></td>
                            <td><textarea name="avis_text[<?php echo $i; ?>]" rows="2" style="width:100%"><?php echo esc_textarea($r['text'] ?? ''); ?></textarea></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <button type="button" class="button" id="vs08v-avis-add">+ Ajouter un avis</button>
                </p>
                <p class="submit">
                    <button type="submit" class="button button-primary">Enregistrer</button>
                </p>
            </form>
        </div>
        <script>
        (function(){
            var tbody = document.getElementById('vs08v-avis-tbody');
            var btn = document.getElementById('vs08v-avis-add');
            if (!tbody || !btn) return;
            var n = tbody.querySelectorAll('tr').length;
            btn.addEventListener('click', function(){
                var tr = document.createElement('tr');
                tr.innerHTML = '<td><input type="text" name="avis_initials['+n+']" value="" maxlength="4" style="width:100%"></td>' +
                    '<td><input type="text" name="avis_name['+n+']" value="" style="width:100%"></td>' +
                    '<td><input type="text" name="avis_trip['+n+']" value="" style="width:100%"></td>' +
                    '<td><textarea name="avis_text['+n+']" rows="2" style="width:100%"></textarea></td>';
                tbody.appendChild(tr);
                var hid = document.querySelector('input[name="vs08v_avis_n"]');
                if (hid) hid.value = n + 1;
                n++;
            });
        })();
        </script>
        <?php
    }
}
