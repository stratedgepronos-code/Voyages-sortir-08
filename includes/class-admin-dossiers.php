<?php
/**
 * VS08 Voyages — Gestion Dossiers Voyages (admin)
 * Module admin pour gérer tous les dossiers voyage sans passer par WooCommerce (sauf paiement).
 */
if (!defined('ABSPATH')) exit;

class VS08V_Admin_Dossiers {

    const MENU_SLUG = 'vs08-dossiers';
    const CAP = 'manage_woocommerce';

    public static function register() {
        add_action('admin_menu', [__CLASS__, 'add_menu'], 56);
        add_action('admin_init', [__CLASS__, 'handle_save_dossier']);
        add_action('wp_ajax_vs08v_admin_send_paybox_mail', [__CLASS__, 'ajax_admin_send_paybox_mail']);
    }

    public static function add_menu() {
        add_menu_page(
            'Gestion Dossiers Voyages',
            'Gestion Dossiers Voyages',
            self::CAP,
            self::MENU_SLUG,
            [__CLASS__, 'page_dashboard'],
            'dashicons-portfolio',
            56
        );
        add_submenu_page(
            self::MENU_SLUG,
            'Tableau de bord',
            'Tableau de bord',
            self::CAP,
            self::MENU_SLUG,
            [__CLASS__, 'page_dashboard']
        );
        add_submenu_page(
            self::MENU_SLUG,
            'Tous les dossiers',
            'Dossiers',
            self::CAP,
            self::MENU_SLUG . '-list',
            [__CLASS__, 'page_list']
        );
        add_submenu_page(
            null,
            'Modifier le dossier',
            'Modifier',
            self::CAP,
            self::MENU_SLUG . '-edit',
            [__CLASS__, 'page_edit']
        );
    }

    /** Retourne les IDs des commandes qui ont un dossier voyage (booking_data sur la commande ou sur un line item). */
    public static function get_dossier_order_ids() {
        if (!function_exists('wc_get_orders')) {
            return [];
        }
        $ids_by_meta = wc_get_orders([
            'limit'    => -1,
            'return'   => 'ids',
            'type'     => 'shop_order',
            'status'   => array_keys(wc_get_order_statuses()),
            'meta_key' => '_vs08v_booking_data',
        ]);
        $ids = is_array($ids_by_meta) ? array_values($ids_by_meta) : [];
        $ids_map = array_flip($ids);
        // Inclure les commandes où booking_data est seulement sur un line item (anciennes commandes)
        $recent = wc_get_orders([
            'limit'   => 500,
            'return'  => 'ids',
            'type'    => 'shop_order',
            'status'  => array_keys(wc_get_order_statuses()),
            'orderby' => 'date',
            'order'   => 'DESC',
        ]);
        foreach ($recent as $order_id) {
            if (isset($ids_map[$order_id])) continue;
            $order = wc_get_order($order_id);
            if (!$order) continue;
            foreach ($order->get_items() as $item) {
                $data = $item->get_meta('_vs08v_booking_data');
                if (!empty($data) && is_array($data)) {
                    $ids[] = $order_id;
                    break;
                }
            }
        }
        return array_values(array_unique($ids));
    }

    /** Agrège les infos pour le dashboard. */
    public static function get_dashboard_data() {
        $ids = self::get_dossier_order_ids();
        $by_destination = [];
        $by_month = [];
        $by_year = [];
        $revenue_total = 0;
        $count = 0;

        foreach ($ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;
            $data = $order->get_meta('_vs08v_booking_data');
            if (empty($data) || !is_array($data)) {
                foreach ($order->get_items() as $item) {
                    $data = $item->get_meta('_vs08v_booking_data');
                    if (!empty($data) && is_array($data)) break;
                }
            }
            if (empty($data) || !is_array($data)) continue;

            $count++;
            $total = (float) ($data['total'] ?? 0);
            $params = $data['params'] ?? [];
            $date_depart = $params['date_depart'] ?? '';
            $voyage_id = (int) ($data['voyage_id'] ?? 0);
            $destination = '';
            if ($voyage_id && class_exists('VS08V_MetaBoxes')) {
                $m = VS08V_MetaBoxes::get($voyage_id);
                $destination = $m['destination'] ?? ($voyage_id ? get_the_title($voyage_id) : '') ?: 'Sans destination';
            }
            if (empty($destination)) {
                $destination = $data['voyage_titre'] ?? 'Sans destination';
            }

            $revenue_total += (float) $order->get_total();
            $solde_ids = $order->get_meta('_vs08v_solde_order_ids');
            if (is_array($solde_ids)) {
                foreach ($solde_ids as $sid) {
                    $so = wc_get_order($sid);
                    if ($so && $so->is_paid()) {
                        $revenue_total += (float) $so->get_total();
                    }
                }
            }

            $by_destination[$destination] = ($by_destination[$destination] ?? 0) + 1;
            if ($date_depart) {
                $ts = strtotime($date_depart);
                $ym = date('Y-m', $ts);
                $y = date('Y', $ts);
                $by_month[$ym] = ($by_month[$ym] ?? 0) + 1;
                $by_year[$y] = ($by_year[$y] ?? 0) + 1;
            }
        }

        $revenue_by_month = [];
        $revenue_by_year = [];
        $revenue_by_dest = [];
        foreach ($ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;
            $data = $order->get_meta('_vs08v_booking_data');
            if (empty($data)) {
                foreach ($order->get_items() as $item) {
                    $data = $item->get_meta('_vs08v_booking_data');
                    if (!empty($data)) break;
                }
            }
            if (empty($data)) continue;

            $params = $data['params'] ?? [];
            $date_depart = $params['date_depart'] ?? '';
            $voyage_id = (int) ($data['voyage_id'] ?? 0);
            $destination = 'Sans destination';
            if ($voyage_id && class_exists('VS08V_MetaBoxes')) {
                $m = VS08V_MetaBoxes::get($voyage_id);
                $destination = $m['destination'] ?? ($voyage_id ? get_the_title($voyage_id) : '') ?: 'Sans destination';
            }
            if (!empty($data['voyage_titre']) && $destination === 'Sans destination') {
                $destination = $data['voyage_titre'];
            }

            $amount = (float) $order->get_total();
            $solde_ids = $order->get_meta('_vs08v_solde_order_ids');
            if (is_array($solde_ids)) {
                foreach ($solde_ids as $sid) {
                    $so = wc_get_order($sid);
                    if ($so && $so->is_paid()) $amount += (float) $so->get_total();
                }
            }

            $revenue_by_dest[$destination] = ($revenue_by_dest[$destination] ?? 0) + $amount;
            if ($date_depart) {
                $ts = strtotime($date_depart);
                $ym = date('Y-m', $ts);
                $y = date('Y', $ts);
                $revenue_by_month[$ym] = ($revenue_by_month[$ym] ?? 0) + $amount;
                $revenue_by_year[$y] = ($revenue_by_year[$y] ?? 0) + $amount;
            }
        }

        krsort($by_month);
        krsort($by_year);
        arsort($by_destination);
        krsort($revenue_by_month);
        krsort($revenue_by_year);
        arsort($revenue_by_dest);

        return [
            'total_dossiers' => $count,
            'revenue_total'  => $revenue_total,
            'by_destination' => $by_destination,
            'by_month'       => array_slice($by_month, 0, 12),
            'by_year'        => $by_year,
            'revenue_by_month' => array_slice($revenue_by_month, 0, 12, true),
            'revenue_by_year'  => $revenue_by_year,
            'revenue_by_dest'  => array_slice($revenue_by_dest, 0, 15, true),
        ];
    }

    public static function page_dashboard() {
        if (!current_user_can(self::CAP)) return;
        $d = self::get_dashboard_data();
        $list_url = admin_url('admin.php?page=' . self::MENU_SLUG . '-list');
        ?>
        <div class="wrap vs08-dossiers-dash">
            <h1>Tableau de bord — Gestion Dossiers Voyages</h1>
            <p class="vs08-dash-desc">Vue d’ensemble des réservations et du chiffre d’affaires.</p>

            <div class="vs08-dash-cards">
                <div class="vs08-dash-card">
                    <span class="vs08-dash-card-n"><?php echo (int) $d['total_dossiers']; ?></span>
                    <span class="vs08-dash-card-l">Dossiers voyage</span>
                </div>
                <div class="vs08-dash-card vs08-dash-card-revenue">
                    <span class="vs08-dash-card-n"><?php echo number_format($d['revenue_total'], 0, ',', ' '); ?> €</span>
                    <span class="vs08-dash-card-l">Chiffre d’affaires total</span>
                </div>
            </div>

            <div class="vs08-dash-grid">
                <div class="vs08-dash-box">
                    <h2>CA par destination</h2>
                    <table class="vs08-dash-table">
                        <thead><tr><th>Destination</th><th>Montant</th></tr></thead>
                        <tbody>
                        <?php foreach ($d['revenue_by_dest'] as $dest => $amt): ?>
                            <tr><td><?php echo esc_html($dest); ?></td><td><?php echo number_format($amt, 0, ',', ' '); ?> €</td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($d['revenue_by_dest'])): ?>
                            <tr><td colspan="2">Aucune donnée</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="vs08-dash-box">
                    <h2>CA par mois</h2>
                    <table class="vs08-dash-table">
                        <thead><tr><th>Mois</th><th>Montant</th></tr></thead>
                        <tbody>
                        <?php foreach ($d['revenue_by_month'] as $ym => $amt): ?>
                            <tr><td><?php echo esc_html(date('m/Y', strtotime($ym . '-01'))); ?></td><td><?php echo number_format($amt, 0, ',', ' '); ?> €</td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($d['revenue_by_month'])): ?>
                            <tr><td colspan="2">Aucune donnée</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="vs08-dash-box">
                    <h2>CA par année</h2>
                    <table class="vs08-dash-table">
                        <thead><tr><th>Année</th><th>Montant</th></tr></thead>
                        <tbody>
                        <?php foreach ($d['revenue_by_year'] as $y => $amt): ?>
                            <tr><td><?php echo esc_html($y); ?></td><td><?php echo number_format($amt, 0, ',', ' '); ?> €</td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($d['revenue_by_year'])): ?>
                            <tr><td colspan="2">Aucune donnée</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="vs08-dash-box">
                    <h2>Dossiers par destination</h2>
                    <table class="vs08-dash-table">
                        <thead><tr><th>Destination</th><th>Nombre</th></tr></thead>
                        <tbody>
                        <?php foreach ($d['by_destination'] as $dest => $n): ?>
                            <tr><td><?php echo esc_html($dest); ?></td><td><?php echo (int) $n; ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($d['by_destination'])): ?>
                            <tr><td colspan="2">Aucune donnée</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p style="margin-top:24px"><a href="<?php echo esc_url($list_url); ?>" class="button button-primary">Voir tous les dossiers</a></p>
        </div>
        <style>
            .vs08-dossiers-dash{max-width:1200px}
            .vs08-dash-desc{color:#666;margin-bottom:20px}
            .vs08-dash-cards{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:28px}
            .vs08-dash-card{background:#fff;border:1px solid #c3c4c7;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:20px 28px;border-radius:4px;min-width:200px}
            .vs08-dash-card-n{display:block;font-size:28px;font-weight:700;color:#1d2327}
            .vs08-dash-card-l{font-size:13px;color:#646970}
            .vs08-dash-card-revenue .vs08-dash-card-n{color:#00a32a}
            .vs08-dash-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
            .vs08-dash-box{background:#fff;border:1px solid #c3c4c7;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:16px;border-radius:4px}
            .vs08-dash-box h2{margin:0 0 12px;font-size:14px;padding-bottom:8px;border-bottom:1px solid #eee}
            .vs08-dash-table{width:100%;border-collapse:collapse;font-size:13px}
            .vs08-dash-table th,.vs08-dash-table td{padding:6px 8px;text-align:left;border-bottom:1px solid #f0f0f1}
            .vs08-dash-table th{font-weight:600;color:#1d2327}
        </style>
        <?php
    }

    public static function page_list() {
        if (!current_user_can(self::CAP)) return;
        $ids = self::get_dossier_order_ids();
        $edit_base = admin_url('admin.php?page=' . self::MENU_SLUG . '-edit&id=');
        ?>
        <div class="wrap">
            <h1>Dossiers voyage</h1>
            <p>Tous les dossiers (commandes voyage). Cliquez sur un dossier pour le modifier.</p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Client</th>
                        <th>Voyage</th>
                        <th>Départ</th>
                        <th>Total</th>
                        <th>Solde</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ids as $order_id):
                    $order = wc_get_order($order_id);
                    if (!$order) continue;
                    $data = $order->get_meta('_vs08v_booking_data');
                    if (empty($data)) {
                        foreach ($order->get_items() as $item) {
                            $data = $item->get_meta('_vs08v_booking_data');
                            if (!empty($data)) break;
                        }
                    }
                    if (empty($data)) continue;
                    $fact = $data['facturation'] ?? [];
                    $client = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
                    $titre = $data['voyage_titre'] ?? 'Séjour';
                    $params = $data['params'] ?? [];
                    $date_depart = !empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'])) : '—';
                    $total = (float) ($data['total'] ?? 0);
                    $solde_info = class_exists('VS08V_Traveler_Space') ? VS08V_Traveler_Space::get_solde_info($order_id) : null;
                    $solde_label = $solde_info && $solde_info['solde_due'] ? number_format($solde_info['solde'], 0, ',', ' ') . ' €' : 'Soldé';
                    ?>
                    <tr>
                        <td><strong>VS08-<?php echo (int) $order_id; ?></strong></td>
                        <td><?php echo esc_html($client ?: '—'); ?></td>
                        <td><?php echo esc_html($titre); ?></td>
                        <td><?php echo esc_html($date_depart); ?></td>
                        <td><?php echo number_format($total, 0, ',', ' '); ?> €</td>
                        <td><?php echo esc_html($solde_label); ?></td>
                        <td>
                            <a href="<?php echo esc_url($edit_base . $order_id); ?>" class="button button-small">Modifier</a>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" class="button button-small" target="_blank">Commande WC</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($ids)): ?>
                    <tr><td colspan="7">Aucun dossier voyage.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function page_edit() {
        if (!current_user_can(self::CAP)) return;
        $order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$order_id) {
            wp_die('Dossier introuvable.');
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_die('Commande introuvable.');
        }
        $data = $order->get_meta('_vs08v_booking_data');
        if (empty($data)) {
            foreach ($order->get_items() as $item) {
                $data = $item->get_meta('_vs08v_booking_data');
                if (!empty($data) && is_array($data)) break;
            }
        }
        if (empty($data) || !is_array($data)) {
            wp_die('Ce dossier n’a pas de données réservation.');
        }

        $list_url = admin_url('admin.php?page=' . self::MENU_SLUG . '-list');
        $params = $data['params'] ?? [];
        $fact = $data['facturation'] ?? [];
        $voyageurs = $data['voyageurs'] ?? [];
        $devis = $data['devis'] ?? [];
        $saved = isset($_GET['saved']) ? true : false;
        ?>
        <div class="wrap vs08-dossier-edit">
            <h1>Modifier le dossier VS08-<?php echo (int) $order_id; ?></h1>
            <p><a href="<?php echo esc_url($list_url); ?>">← Retour à la liste</a></p>
            <?php if ($saved): ?>
                <div class="notice notice-success is-dismissible"><p>Dossier enregistré.</p></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('vs08_save_dossier', 'vs08_dossier_nonce'); ?>
                <input type="hidden" name="vs08_dossier_order_id" value="<?php echo (int) $order_id; ?>">

                <div class="vs08-edit-sections">
                    <div class="vs08-edit-section">
                        <h2>Voyage</h2>
                        <p><label>Intitulé voyage</label><input type="text" name="vs08_d[voyage_titre]" value="<?php echo esc_attr($data['voyage_titre'] ?? ''); ?>" class="large-text"></p>
                        <p><label>Voyage ID (post)</label><input type="number" name="vs08_d[voyage_id]" value="<?php echo (int) ($data['voyage_id'] ?? 0); ?>"></p>
                    </div>

                    <div class="vs08-edit-section">
                        <h2>Dates & paramètres</h2>
                        <p><label>Date départ</label><input type="date" name="vs08_d[params][date_depart]" value="<?php echo esc_attr($params['date_depart'] ?? ''); ?>"></p>
                        <p><label>Aéroport</label><input type="text" name="vs08_d[params][aeroport]" value="<?php echo esc_attr($params['aeroport'] ?? ''); ?>"></p>
                        <p><label>Nb golfeurs</label><input type="number" name="vs08_d[params][nb_golfeurs]" value="<?php echo (int) ($params['nb_golfeurs'] ?? 1); ?>" min="0"></p>
                        <p><label>Nb non-golfeurs</label><input type="number" name="vs08_d[params][nb_nongolfeurs]" value="<?php echo (int) ($params['nb_nongolfeurs'] ?? 0); ?>" min="0"></p>
                        <p><label>Type chambre</label><input type="text" name="vs08_d[params][type_chambre]" value="<?php echo esc_attr($params['type_chambre'] ?? 'double'); ?>"></p>
                        <p><label>Nb chambres</label><input type="number" name="vs08_d[params][nb_chambres]" value="<?php echo (int) ($params['nb_chambres'] ?? 1); ?>" min="1"></p>
                        <p><label>Prix vol (par pers)</label><input type="number" name="vs08_d[params][prix_vol]" value="<?php echo esc_attr($params['prix_vol'] ?? 0); ?>" step="0.01"></p>
                        <?php if (!empty($params['vol_aller_num'])): ?>
                        <p><label>Vol aller</label> <?php echo esc_html($params['vol_aller_num'] ?? ''); ?> — <?php echo esc_html($params['vol_aller_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_aller_arrivee'] ?? ''); ?></p>
                        <input type="hidden" name="vs08_d[params][vol_aller_num]" value="<?php echo esc_attr($params['vol_aller_num'] ?? ''); ?>">
                        <input type="hidden" name="vs08_d[params][vol_aller_depart]" value="<?php echo esc_attr($params['vol_aller_depart'] ?? ''); ?>">
                        <input type="hidden" name="vs08_d[params][vol_aller_arrivee]" value="<?php echo esc_attr($params['vol_aller_arrivee'] ?? ''); ?>">
                        <input type="hidden" name="vs08_d[params][vol_aller_cie]" value="<?php echo esc_attr($params['vol_aller_cie'] ?? ''); ?>">
                        <?php endif; ?>
                        <?php if (!empty($params['vol_retour_num'])): ?>
                        <p><label>Vol retour</label> <?php echo esc_html($params['vol_retour_num'] ?? ''); ?></p>
                        <input type="hidden" name="vs08_d[params][vol_retour_num]" value="<?php echo esc_attr($params['vol_retour_num'] ?? ''); ?>">
                        <input type="hidden" name="vs08_d[params][vol_retour_depart]" value="<?php echo esc_attr($params['vol_retour_depart'] ?? ''); ?>">
                        <input type="hidden" name="vs08_d[params][vol_retour_arrivee]" value="<?php echo esc_attr($params['vol_retour_arrivee'] ?? ''); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="vs08-edit-section">
                        <h2>Facturation (client)</h2>
                        <p><label>Prénom</label><input type="text" name="vs08_d[facturation][prenom]" value="<?php echo esc_attr($fact['prenom'] ?? ''); ?>"></p>
                        <p><label>Nom</label><input type="text" name="vs08_d[facturation][nom]" value="<?php echo esc_attr($fact['nom'] ?? ''); ?>"></p>
                        <p><label>Email</label><input type="email" name="vs08_d[facturation][email]" value="<?php echo esc_attr($fact['email'] ?? ''); ?>" class="large-text"></p>
                        <p><label>Téléphone</label><input type="text" name="vs08_d[facturation][tel]" value="<?php echo esc_attr($fact['tel'] ?? ''); ?>"></p>
                        <p><label>Adresse</label><textarea name="vs08_d[facturation][adresse]" rows="2" class="large-text"><?php echo esc_textarea($fact['adresse'] ?? ''); ?></textarea></p>
                        <p><label>Code postal</label><input type="text" name="vs08_d[facturation][cp]" value="<?php echo esc_attr($fact['cp'] ?? ''); ?>"></p>
                        <p><label>Ville</label><input type="text" name="vs08_d[facturation][ville]" value="<?php echo esc_attr($fact['ville'] ?? ''); ?>"></p>
                    </div>

                    <div class="vs08-edit-section">
                        <h2>Montants</h2>
                        <p><label>Total voyage (€)</label><input type="number" name="vs08_d[total]" value="<?php echo esc_attr($data['total'] ?? 0); ?>" step="0.01"></p>
                        <p><label>Acompte (€)</label><input type="number" name="vs08_d[acompte]" value="<?php echo esc_attr($data['acompte'] ?? 0); ?>" step="0.01"></p>
                        <p><label><input type="checkbox" name="vs08_d[payer_tout]" value="1" <?php checked(!empty($data['payer_tout'])); ?>> Paiement intégral</label></p>
                    </div>

                    <div class="vs08-edit-section">
                        <h2>Voyageurs</h2>
                        <?php foreach ($voyageurs as $i => $v): ?>
                        <div class="vs08-voyageur-block">
                            <strong>Voyageur <?php echo (int) $i + 1; ?></strong>
                            <p><label>Prénom</label><input type="text" name="vs08_d[voyageurs][<?php echo (int) $i; ?>][prenom]" value="<?php echo esc_attr($v['prenom'] ?? ''); ?>"></p>
                            <p><label>Nom</label><input type="text" name="vs08_d[voyageurs][<?php echo (int) $i; ?>][nom]" value="<?php echo esc_attr($v['nom'] ?? ''); ?>"></p>
                            <p><label>Date naissance</label><input type="text" name="vs08_d[voyageurs][<?php echo (int) $i; ?>][ddn]" value="<?php echo esc_attr($v['ddn'] ?? $v['date_naissance'] ?? ''); ?>" placeholder="YYYY-MM-DD"></p>
                            <p><label>Passeport</label><input type="text" name="vs08_d[voyageurs][<?php echo (int) $i; ?>][passeport]" value="<?php echo esc_attr($v['passeport'] ?? ''); ?>"></p>
                            <p><label>Type</label><input type="text" name="vs08_d[voyageurs][<?php echo (int) $i; ?>][type]" value="<?php echo esc_attr($v['type'] ?? 'golfeur'); ?>"></p>
                            <input type="hidden" name="vs08_d[voyageurs][<?php echo (int) $i; ?>][chambre]" value="<?php echo esc_attr($v['chambre'] ?? 1); ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="vs08-edit-section">
                        <h2>Options dossier (admin)</h2>
                        <p><label><input type="checkbox" name="vs08_solde_marque_paye" value="1" <?php checked((bool) $order->get_meta('_vs08v_solde_marque_paye')); ?>> Solde marqué comme réglé</label></p>
                    </div>

                    <?php
                    $solde_info = VS08V_Traveler_Space::get_solde_info($order_id);
                    $pbm_payments = $order->get_meta('_vs08v_paybox_mail_payments');
                    $pbm_pending = $order->get_meta('_vs08v_paybox_mail_pending');
                    ?>
                    <div class="vs08-edit-section">
                        <h2>💳 Paiement du solde (Paybox Mail)</h2>
                        <?php if ($solde_info): ?>
                        <table class="widefat" style="margin-bottom:12px">
                            <tr><th>Total voyage</th><td><?php echo number_format($solde_info['total_voyage'], 2, ',', ' '); ?> €</td></tr>
                            <tr><th>Déjà payé</th><td><?php echo number_format($solde_info['paid'], 2, ',', ' '); ?> €</td></tr>
                            <tr><th>Solde restant</th><td style="font-weight:700;color:<?php echo $solde_info['solde'] > 0 ? '#dc2626' : '#22c55e'; ?>"><?php echo number_format($solde_info['solde'], 2, ',', ' '); ?> €</td></tr>
                            <?php if ($solde_info['solde_date']): ?>
                            <tr><th>Échéance solde</th><td><?php echo esc_html($solde_info['solde_date']); ?></td></tr>
                            <?php endif; ?>
                        </table>
                        <?php endif; ?>

                        <?php if (is_array($pbm_payments) && $pbm_payments): ?>
                        <h3 style="font-size:13px;margin:14px 0 6px">Paiements reçus via Paybox Mail</h3>
                        <table class="widefat striped" style="font-size:12px">
                            <thead><tr><th>Date</th><th>Montant</th><th>Référence</th></tr></thead>
                            <tbody>
                            <?php foreach ($pbm_payments as $p): ?>
                            <tr>
                                <td><?php echo esc_html($p['date'] ?? '—'); ?></td>
                                <td><?php echo number_format((float)($p['amount'] ?? 0), 2, ',', ' '); ?> €</td>
                                <td><code><?php echo esc_html($p['reference'] ?? ''); ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>

                        <?php if (is_array($pbm_pending) && $pbm_pending): ?>
                        <h3 style="font-size:13px;margin:14px 0 6px">Liens de paiement envoyés</h3>
                        <table class="widefat striped" style="font-size:12px">
                            <thead><tr><th>Date</th><th>Montant</th><th>Email</th><th>Lien</th></tr></thead>
                            <tbody>
                            <?php foreach ($pbm_pending as $p): ?>
                            <tr>
                                <td><?php echo esc_html($p['date'] ?? '—'); ?></td>
                                <td><?php echo number_format((float)($p['amount'] ?? 0), 2, ',', ' '); ?> €</td>
                                <td><?php echo esc_html($p['email'] ?? ''); ?></td>
                                <td><?php if (!empty($p['payment_url'])): ?><a href="<?php echo esc_url($p['payment_url']); ?>" target="_blank">Ouvrir</a><?php else: ?>—<?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>

                        <?php if ($solde_info && $solde_info['solde'] > 0 && VS08V_Paybox_Mail::is_configured()): ?>
                        <div style="margin-top:14px;padding:12px;background:#f0fdf4;border:1px solid #86efac;border-radius:4px">
                            <p style="margin:0 0 8px;font-weight:600;font-size:13px">Envoyer un lien de paiement au client</p>
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                                <input type="number" id="vs08-pbm-amount" step="0.01" min="0.01" max="<?php echo esc_attr($solde_info['solde']); ?>" value="<?php echo esc_attr($solde_info['solde']); ?>" style="width:120px;padding:4px 8px">
                                <span>€</span>
                                <button type="button" id="vs08-pbm-send" class="button button-primary">Envoyer le lien Paybox Mail</button>
                                <span id="vs08-pbm-status" style="font-size:12px"></span>
                            </div>
                        </div>
                        <script>
                        document.getElementById('vs08-pbm-send').addEventListener('click',function(){
                            var btn = this;
                            var amount = document.getElementById('vs08-pbm-amount').value;
                            var status = document.getElementById('vs08-pbm-status');
                            if(!amount||parseFloat(amount)<=0){status.textContent='Montant invalide.';return;}
                            btn.disabled=true;
                            status.textContent='Envoi en cours…';
                            var fd=new FormData();
                            fd.append('action','vs08v_admin_send_paybox_mail');
                            fd.append('nonce','<?php echo wp_create_nonce('vs08v_admin_pbm'); ?>');
                            fd.append('order_id','<?php echo (int)$order_id; ?>');
                            fd.append('amount',amount);
                            fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'})
                            .then(function(r){return r.json();})
                            .then(function(res){
                                btn.disabled=false;
                                if(res.success){
                                    status.innerHTML='<span style="color:#22c55e">✅ '+res.data.message+'</span>';
                                }else{
                                    status.innerHTML='<span style="color:#dc2626">❌ '+(res.data&&res.data.message||'Erreur')+'</span>';
                                }
                            })
                            .catch(function(){btn.disabled=false;status.textContent='Erreur réseau.';});
                        });
                        </script>
                        <?php elseif ($solde_info && $solde_info['solde'] > 0 && !VS08V_Paybox_Mail::is_configured()): ?>
                        <p style="color:#b45309;font-size:12px;margin-top:10px">⚠️ Paybox Mail non configuré. Ajoutez <code>PAYBOX_MAIL_APP_KEY</code> et <code>PAYBOX_MAIL_SECRET_KEY</code> dans <code>config.cfg</code>.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="submit"><button type="submit" name="vs08_save_dossier" class="button button-primary">Enregistrer le dossier</button></p>
            </form>
        </div>
        <style>
            .vs08-dossier-edit .vs08-edit-sections{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;margin-top:20px}
            .vs08-edit-section{background:#fff;border:1px solid #c3c4c7;padding:16px;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04)}
            .vs08-edit-section h2{margin:0 0 12px;font-size:14px;padding-bottom:8px;border-bottom:1px solid #eee}
            .vs08-edit-section p{margin:0 0 12px}
            .vs08-edit-section p:last-child{margin-bottom:0}
            .vs08-edit-section label{display:block;font-weight:600;margin-bottom:4px;font-size:12px}
            .vs08-voyageur-block{border:1px solid #eee;padding:12px;margin-bottom:12px;border-radius:4px}
        </style>
        <?php
    }

    public static function handle_save_dossier() {
        if (empty($_POST['vs08_save_dossier']) || empty($_POST['vs08_dossier_nonce']) || !wp_verify_nonce($_POST['vs08_dossier_nonce'], 'vs08_save_dossier')) {
            return;
        }
        if (!current_user_can(self::CAP)) return;
        $order_id = isset($_POST['vs08_dossier_order_id']) ? (int) $_POST['vs08_dossier_order_id'] : 0;
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        if (!$order) return;

        $raw = isset($_POST['vs08_d']) && is_array($_POST['vs08_d']) ? $_POST['vs08_d'] : [];
        $data = $order->get_meta('_vs08v_booking_data');
        if (empty($data)) {
            foreach ($order->get_items() as $item) {
                $data = $item->get_meta('_vs08v_booking_data');
                if (!empty($data) && is_array($data)) break;
            }
        }
        if (empty($data) || !is_array($data)) return;

        $data['voyage_titre'] = sanitize_text_field($raw['voyage_titre'] ?? $data['voyage_titre']);
        $data['voyage_id'] = (int) ($raw['voyage_id'] ?? $data['voyage_id']);
        $data['total'] = isset($raw['total']) ? (float) $raw['total'] : $data['total'];
        $data['acompte'] = isset($raw['acompte']) ? (float) $raw['acompte'] : ($data['acompte'] ?? 0);
        $data['payer_tout'] = !empty($raw['payer_tout']);

        if (!empty($raw['params']) && is_array($raw['params'])) {
            $data['params'] = array_merge($data['params'] ?? [], array_map('sanitize_text_field', $raw['params']));
            if (isset($raw['params']['nb_golfeurs'])) $data['params']['nb_golfeurs'] = (int) $raw['params']['nb_golfeurs'];
            if (isset($raw['params']['nb_nongolfeurs'])) $data['params']['nb_nongolfeurs'] = (int) $raw['params']['nb_nongolfeurs'];
            if (isset($raw['params']['nb_chambres'])) $data['params']['nb_chambres'] = (int) $raw['params']['nb_chambres'];
            if (isset($raw['params']['prix_vol'])) $data['params']['prix_vol'] = (float) $raw['params']['prix_vol'];
        }
        if (!empty($raw['facturation']) && is_array($raw['facturation'])) {
            $data['facturation'] = array_merge($data['facturation'] ?? [], array_map('sanitize_text_field', $raw['facturation']));
            if (isset($raw['facturation']['adresse'])) $data['facturation']['adresse'] = sanitize_textarea_field($raw['facturation']['adresse']);
        }
        if (!empty($raw['voyageurs']) && is_array($raw['voyageurs'])) {
            $data['voyageurs'] = [];
            foreach ($raw['voyageurs'] as $i => $v) {
                if (!is_array($v)) continue;
                $data['voyageurs'][(int) $i] = [
                    'prenom' => sanitize_text_field($v['prenom'] ?? ''),
                    'nom' => sanitize_text_field($v['nom'] ?? ''),
                    'ddn' => sanitize_text_field($v['ddn'] ?? ''),
                    'passeport' => sanitize_text_field($v['passeport'] ?? ''),
                    'type' => sanitize_text_field($v['type'] ?? 'golfeur'),
                    'chambre' => (int) ($v['chambre'] ?? 1),
                ];
            }
            ksort($data['voyageurs']);
            $data['voyageurs'] = array_values($data['voyageurs']);
        }

        $order->update_meta_data('_vs08v_booking_data', $data);
        $order->save();

        foreach ($order->get_items() as $item) {
            if ($item->get_meta('_vs08v_booking_data')) {
                $item->update_meta_data('_vs08v_booking_data', $data);
                $item->save();
            }
        }

        if (!empty($_POST['vs08_solde_marque_paye'])) {
            $order->update_meta_data('_vs08v_solde_marque_paye', 1);
        } else {
            $order->delete_meta_data('_vs08v_solde_marque_paye');
        }
        $order->save();

        wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '-edit&id=' . $order_id . '&saved=1'));
        exit;
    }

    /**
     * AJAX : Admin envoie un lien de paiement Paybox Mail au client.
     */
    public static function ajax_admin_send_paybox_mail() {
        check_ajax_referer('vs08v_admin_pbm', 'nonce');
        if (!current_user_can(self::CAP)) {
            wp_send_json_error(['message' => 'Accès refusé.']);
        }

        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $amount   = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;

        if (!$order_id || $amount <= 0) {
            wp_send_json_error(['message' => 'Paramètres invalides.']);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Commande introuvable.']);
        }

        $data = $order->get_meta('_vs08v_booking_data');
        if (empty($data)) {
            foreach ($order->get_items() as $item) {
                $data = $item->get_meta('_vs08v_booking_data');
                if (!empty($data) && is_array($data)) break;
            }
        }
        if (empty($data) || !is_array($data)) {
            wp_send_json_error(['message' => 'Données de réservation introuvables.']);
        }

        $fact  = $data['facturation'] ?? [];
        $email = $fact['email'] ?? $order->get_billing_email();
        $titre = $data['voyage_titre'] ?? 'Séjour golf';

        if (!$email) {
            wp_send_json_error(['message' => 'Aucun email client trouvé.']);
        }

        $solde_info = VS08V_Traveler_Space::get_solde_info($order_id);
        if (!$solde_info || $solde_info['solde'] <= 0) {
            wp_send_json_error(['message' => 'Aucun solde à régler pour ce dossier.']);
        }
        if ($amount > $solde_info['solde']) {
            wp_send_json_error(['message' => 'Le montant dépasse le solde restant (' . number_format($solde_info['solde'], 2, ',', ' ') . ' €).']);
        }

        $is_partial = $amount < $solde_info['solde'];
        $reference = 'VS08-' . $order_id . '-SOLDE' . ($is_partial ? '-PARTIEL' : '') . '-' . time();

        $result = VS08V_Paybox_Mail::create_payment_request($reference, $amount, $email, true);

        if (!$result['success']) {
            wp_send_json_error(['message' => $result['error'] ?? 'Erreur Paybox Mail.']);
        }

        $pbm_data = $result['data'];
        $payment_url = $pbm_data['payment_url'] ?? ($pbm_data['url'] ?? '');

        $pending_requests = $order->get_meta('_vs08v_paybox_mail_pending');
        if (!is_array($pending_requests)) $pending_requests = [];
        $pending_requests[] = [
            'request_id'  => $pbm_data['id'] ?? '',
            'reference'   => $reference,
            'amount'      => $amount,
            'email'       => $email,
            'date'        => current_time('Y-m-d H:i:s'),
            'payment_url' => $payment_url,
            'sent_by'     => 'admin',
        ];
        $order->update_meta_data('_vs08v_paybox_mail_pending', $pending_requests);
        $order->add_order_note(sprintf(
            'Lien de paiement Paybox Mail envoyé par l\'admin — %.2f € — Réf: %s — Envoyé à %s',
            $amount,
            $reference,
            $email
        ));
        $order->save();

        wp_send_json_success([
            'message' => 'Lien de paiement de ' . number_format($amount, 2, ',', ' ') . ' € envoyé à ' . $email . '.',
        ]);
    }
}
