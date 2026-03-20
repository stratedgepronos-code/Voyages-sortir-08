<?php
/**
 * Page admin : marge globale pour tous les circuits ou par circuit (onglet).
 */
if (!defined('ABSPATH')) {
    exit;
}

class VS08C_Marge {

    public static function register_menu(): void {
        add_submenu_page(
            'edit.php?post_type=vs08_circuit',
            'Marge circuits',
            'Marge circuits',
            'edit_posts',
            'vs08c-marge',
            [__CLASS__, 'render_page']
        );
    }

    public static function register_settings(): void {
        add_filter('option_page_capability_vs08c_marge', static function () {
            return 'edit_posts';
        });
        register_setting('vs08c_marge', 'vs08c_marge_scope', [
            'type'              => 'string',
            'sanitize_callback' => [__CLASS__, 'sanitize_scope'],
            'default'           => 'per_circuit',
        ]);
        register_setting('vs08c_marge', 'vs08c_marge_type', [
            'type'              => 'string',
            'sanitize_callback' => [__CLASS__, 'sanitize_type'],
            'default'           => 'pct',
        ]);
        register_setting('vs08c_marge', 'vs08c_marge_pct', [
            'type'              => 'number',
            'sanitize_callback' => [__CLASS__, 'sanitize_pct'],
            'default'           => 15,
        ]);
        register_setting('vs08c_marge', 'vs08c_marge_montant', [
            'type'              => 'number',
            'sanitize_callback' => [__CLASS__, 'sanitize_montant'],
            'default'           => 0,
        ]);
    }

    public static function sanitize_scope($v): string {
        $v = is_string($v) ? $v : '';
        return in_array($v, ['global', 'per_circuit'], true) ? $v : 'per_circuit';
    }

    public static function sanitize_type($v): string {
        $v = is_string($v) ? $v : '';
        return ($v === 'montant') ? 'montant' : 'pct';
    }

    public static function sanitize_pct($v): float {
        $n = is_numeric($v) ? (float) $v : 15;
        return max(0, min(100, round($n, 2)));
    }

    public static function sanitize_montant($v): float {
        $n = is_numeric($v) ? (float) $v : 0;
        return max(0, round($n, 2));
    }

    /**
     * True si la marge est pilotée uniquement par cette page (pas l'onglet circuit).
     */
    public static function is_global_mode(): bool {
        return get_option('vs08c_marge_scope', 'per_circuit') === 'global';
    }

    /**
     * Valeurs de marge effectives pour un circuit (global ou meta).
     *
     * @return array{marge_type:string,marge_pct:float,marge_montant:float}
     */
    public static function get_effective_for_circuit(int $circuit_id): array {
        if (self::is_global_mode()) {
            return [
                'marge_type'    => get_option('vs08c_marge_type', 'pct') ?: 'pct',
                'marge_pct'     => (float) get_option('vs08c_marge_pct', 15),
                'marge_montant' => (float) get_option('vs08c_marge_montant', 0),
            ];
        }
        $m = VS08C_Meta::get($circuit_id);

        return [
            'marge_type'    => isset($m['marge_type']) && $m['marge_type'] !== '' ? (string) $m['marge_type'] : 'pct',
            'marge_pct'     => floatval($m['marge_pct'] ?? 15),
            'marge_montant' => floatval($m['marge_montant'] ?? 0),
        ];
    }

    public static function render_page(): void {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Droits insuffisants.', 'vs08-circuits'));
        }
        if (!empty($_GET['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Réglages enregistrés.', 'vs08-circuits') . '</p></div>';
        }
        $scope    = get_option('vs08c_marge_scope', 'per_circuit');
        $type     = get_option('vs08c_marge_type', 'pct');
        $pct      = get_option('vs08c_marge_pct', 15);
        $montant  = get_option('vs08c_marge_montant', 0);
        ?>
        <div class="wrap vs08c-marge-admin">
            <h1><?php echo esc_html__('Marge circuits', 'vs08-circuits'); ?></h1>
            <p class="description">
                <?php echo esc_html__('Définissez comment la marge est appliquée aux prix affichés aux clients (après vols, hébergement, options, etc.).', 'vs08-circuits'); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields('vs08c_marge'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Mode', 'vs08-circuits'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="vs08c_marge_scope" value="per_circuit" <?php checked($scope, 'per_circuit'); ?> />
                                    <?php echo esc_html__("Par circuit — chaque circuit utilise l'onglet « Marge » de sa fiche.", 'vs08-circuits'); ?>
                                </label><br><br>
                                <label>
                                    <input type="radio" name="vs08c_marge_scope" value="global" <?php checked($scope, 'global'); ?> />
                                    <?php echo esc_html__("Globale — la même marge s'applique à tous les circuits (l'onglet Marge par circuit est ignoré).", 'vs08-circuits'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Type de marge (mode global)', 'vs08-circuits'); ?></th>
                        <td>
                            <select name="vs08c_marge_type" id="vs08c_marge_type">
                                <option value="pct" <?php selected($type, 'pct'); ?>><?php echo esc_html__('Pourcentage du sous-total', 'vs08-circuits'); ?></option>
                                <option value="montant" <?php selected($type, 'montant'); ?>><?php echo esc_html__('Montant forfaitaire (€) sur la réservation', 'vs08-circuits'); ?></option>
                            </select>
                            <p class="description"><?php echo esc_html__('Utilisé uniquement lorsque le mode « Globale » est activé.', 'vs08-circuits'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vs08c_marge_pct"><?php echo esc_html__('Marge (%)', 'vs08-circuits'); ?></label></th>
                        <td>
                            <input name="vs08c_marge_pct" id="vs08c_marge_pct" type="number" step="0.5" min="0" max="100" value="<?php echo esc_attr((string) $pct); ?>" class="small-text" />
                            <p class="description"><?php echo esc_html__('Ex. 15 pour 15 % du total avant marge.', 'vs08-circuits'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vs08c_marge_montant"><?php echo esc_html__('Marge forfaitaire (€)', 'vs08-circuits'); ?></label></th>
                        <td>
                            <input name="vs08c_marge_montant" id="vs08c_marge_montant" type="number" step="0.01" min="0" value="<?php echo esc_attr((string) $montant); ?>" class="small-text" />
                            <p class="description"><?php echo esc_html__('Utilisé si le type = montant forfaitaire.', 'vs08-circuits'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Enregistrer', 'vs08-circuits')); ?>
            </form>
        </div>
        <?php
    }
}
