<?php
if (!defined('ABSPATH')) exit;

class VS08V_Traveler_Space {

    public static function get_voyage_orders($customer_id = 0) {
        if (!$customer_id) {
            $customer_id = get_current_user_id();
        }
        if (!$customer_id) {
            return [];
        }

        $orders = wc_get_orders([
            'customer' => $customer_id,
            'limit'    => -1,
            'status'   => array_keys(wc_get_order_statuses()),
            'orderby'  => 'date',
            'order'    => 'DESC',
        ]);

        $result = [];
        $today  = date('Y-m-d');

        foreach ($orders as $order) {
            $data = self::get_booking_data_from_order($order);
            if (!$data) {
                continue;
            }
            $params   = $data['params'] ?? [];
            $depart   = $params['date_depart'] ?? '';
            $result[] = [
                'order'        => $order,
                'booking_data' => $data,
                'date_depart'  => $depart,
                'is_upcoming'  => $depart && $depart >= $today,
            ];
        }

        return $result;
    }

    public static function get_booking_data_from_order($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }
        if (!$order) {
            return null;
        }
        $data = $order->get_meta('_vs08v_booking_data');
        if (!empty($data) && is_array($data)) {
            return $data;
        }
        foreach ($order->get_items() as $item) {
            $data = $item->get_meta('_vs08v_booking_data');
            if (!empty($data) && is_array($data)) {
                return $data;
            }
        }
        return null;
    }

    public static function get_solde_info($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return null;
        }
        $data = self::get_booking_data_from_order($order);
        if (!$data) {
            return null;
        }

        $total_voyage = floatval($data['total'] ?? 0);
        $payer_tout   = !empty($data['payer_tout']);
        $voyage_id    = (int) ($data['voyage_id'] ?? 0);
        $m            = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
        $delai_solde  = intval($m['delai_solde'] ?? 30);
        $params       = $data['params'] ?? [];
        $date_depart  = $params['date_depart'] ?? '';

        $paid = (float) $order->get_total();
        $solde_ids = $order->get_meta('_vs08v_solde_order_ids');
        if (!is_array($solde_ids)) {
            $legacy = $order->get_meta('_vs08v_solde_order_id');
            $solde_ids = $legacy ? [(int) $legacy] : [];
        }
        foreach ($solde_ids as $sid) {
            $so = wc_get_order($sid);
            if ($so && $so->is_paid()) {
                $paid += (float) $so->get_total();
            }
        }
        // Paiements via Paybox Mail (hors WooCommerce)
        $pbm_payments = $order->get_meta('_vs08v_paybox_mail_payments');
        if (is_array($pbm_payments)) {
            foreach ($pbm_payments as $p) {
                $paid += (float) ($p['amount'] ?? 0);
            }
        }
        // Paiements en attente de validation (virement, cheque) : commandes on-hold
        $pending_payments = [];
        foreach ($solde_ids as $sid) {
            $so = wc_get_order($sid);
            if ($so && $so->has_status('on-hold')) {
                $method = $so->get_payment_method_title();
                if (!$method) $method = $so->get_payment_method();
                $pending_payments[] = [
                    'order_id' => $sid,
                    'amount'   => (float) $so->get_total(),
                    'method'   => $method ?: 'Virement / Cheque',
                    'date'     => $so->get_date_created() ? $so->get_date_created()->date('d/m/Y') : '',
                ];
            }
        }
        $pending_total = array_sum(array_column($pending_payments, 'amount'));

        $solde = max(0, $total_voyage - $paid);
        $solde_marque_paye = $order->get_meta('_vs08v_solde_marque_paye');
        if ($solde_marque_paye) {
            $solde = 0;
        }
        $solde_date = '';
        if (!$payer_tout && $date_depart && $solde > 0) {
            $solde_ts = strtotime($date_depart) - ($delai_solde * 86400);
            $solde_date = date('d/m/Y', $solde_ts);
        }
        $solde_paye = !$payer_tout && $solde <= 0;

        return [
            'total_voyage'      => $total_voyage,
            'paid'              => $paid,
            'solde'             => $solde,
            'solde_due'         => $solde > 0,
            'solde_date'        => $solde_date,
            'payer_tout'        => $payer_tout,
            'soldé_paye'        => $solde_paye,
            'pending_payments'  => $pending_payments,
            'pending_total'     => $pending_total,
        ];
    }

    public static function current_user_can_view_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        if ((int) $order->get_customer_id() !== (int) get_current_user_id()) {
            return false;
        }
        return self::get_booking_data_from_order($order) !== null;
    }

    /**
     * Crée une commande WooCommerce pour régler le solde (entier ou partie).
     * Redirection vers la page checkout Paybox. Plusieurs paiements partiels possibles.
     *
     * @param int      $parent_order_id Commande voyage (acompte).
     * @param float|null $amount         Montant à payer (null = solde entier).
     */
    private static function solde_log($step) {
        $f = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/vs08-solde-debug.log' : (defined('VS08V_PATH') ? VS08V_PATH . 'vs08-solde-debug.log' : '');
        if ($f) {
            @file_put_contents($f, date('Y-m-d H:i:s') . ' ' . $step . "\n", FILE_APPEND | LOCK_EX);
        }
        error_log('VS08 solde: ' . $step);
    }

    public static function create_solde_order($parent_order_id, $amount = null) {
        self::solde_log('step 0 start');
        if (!self::current_user_can_view_order($parent_order_id)) {
            return ['success' => false, 'payment_url' => false, 'error' => 'Accès refusé.'];
        }
        self::solde_log('step 1 get order');
        $parent = wc_get_order($parent_order_id);
        $solde_info = self::get_solde_info($parent_order_id);
        if (!$solde_info || !$solde_info['solde_due'] || $solde_info['solde'] <= 0) {
            return ['success' => false, 'payment_url' => false, 'error' => 'Aucun solde à régler.'];
        }

        $solde_remaining = (float) $solde_info['solde'];
        if ($amount !== null && $amount !== '') {
            $amount = (float) $amount;
            if ($amount <= 0 || $amount > $solde_remaining) {
                return ['success' => false, 'payment_url' => false, 'error' => 'Montant invalide. Saisissez un montant entre 0,01 € et ' . number_format($solde_remaining, 2, ',', ' ') . ' €.'];
            }
            $pay_amount = $amount;
        } else {
            $pay_amount = $solde_remaining;
        }

        $customer_id = $parent->get_customer_id();
        $data = self::get_booking_data_from_order($parent);
        $titre = $data['voyage_titre'] ?? 'Séjour golf';

        self::solde_log('step 2 create product');
        $product = new WC_Product_Simple();
        $product->set_name(($pay_amount >= $solde_remaining ? 'Solde' : 'Acompte solde') . ' — ' . $titre . ' (VS08-' . $parent_order_id . ')');
        $product->set_price($pay_amount);
        $product->set_regular_price($pay_amount);
        $product->set_status('private');
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');
        $product_id = $product->save();
        update_post_meta($product_id, '_vs08v_solde_parent_order_id', $parent_order_id);

        self::solde_log('step 3 create order');
        $order = wc_create_order();
        $order->add_product($product, 1);
        $order->set_customer_id($customer_id);
        $order->set_address($parent->get_address('billing'), 'billing');
        self::solde_log('step 4 calculate_totals');
        $order->calculate_totals();
        self::solde_log('step 5 order save');
        $order->save();
        $order->update_meta_data('_vs08v_order_solde_parent', $parent_order_id);
        $order->save_meta_data();

        self::solde_log('step 5b parent meta');
        $solde_ids = $parent->get_meta('_vs08v_solde_order_ids');
        if (!is_array($solde_ids)) {
            $legacy = $parent->get_meta('_vs08v_solde_order_id');
            $solde_ids = $legacy ? [(int) $legacy] : [];
        }
        $solde_ids[] = $order->get_id();
        // Utiliser update_post_meta pour éviter les hooks WooCommerce/Paybox qui bloquent sur save_meta_data()
        $parent_id = $parent->get_id();
        update_post_meta($parent_id, '_vs08v_solde_order_ids', $solde_ids);
        delete_post_meta($parent_id, '_vs08v_solde_order_id');

        self::solde_log('step 5c set pending');
        $order->set_status('pending');
        $order->save();

        self::solde_log('step 6 get_checkout_payment_url');
        // Construire l'URL manuellement pour éviter le blocage du gateway Paybox dans le filtre WooCommerce
        $payment_url = wc_get_endpoint_url('order-pay', $order->get_id(), wc_get_checkout_url()) . '?pay_for_order=true&key=' . $order->get_order_key();
        self::solde_log('step 7 done');
        return ['success' => true, 'payment_url' => $payment_url, 'order_id' => $order->get_id()];
    }

    public static function send_question_email($order_id, $sujet, $message) {
        if (!self::current_user_can_view_order($order_id)) {
            return false;
        }
        $order = wc_get_order($order_id);
        $data = self::get_booking_data_from_order($order);
        if (!$data) {
            return false;
        }
        $fact = $data['facturation'] ?? [];
        $client = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
        $recap = self::format_booking_recap_for_email($data, $order_id);
        $body = '<div style="padding:24px;font-family:Georgia,serif;font-size:14px;color:#333;">'
            . '<h2 style="color:#1a3a3a;">Question client — Voyage VS08-' . $order_id . '</h2>'
            . '<p><strong>Sujet :</strong> ' . esc_html($sujet) . '</p>'
            . '<p><strong>Message :</strong></p><div style="background:#f5f5f5;padding:16px;border-radius:8px;margin:12px 0;">' . nl2br(esc_html($message)) . '</div>'
            . '<p><strong>Client :</strong> ' . esc_html($client) . ' — ' . esc_html($fact['email'] ?? '') . '</p>'
            . '<hr style="margin:20px 0;border:0;border-top:1px solid #ddd;">'
            . '<h3 style="color:#2a7f7f;">Récapitulatif du voyage</h3>'
            . $recap
            . '</div>';

        $subject = 'Question voyage VS08-' . $order_id . ' — ' . wp_specialchars_decode($sujet, ENT_QUOTES);
        return wp_mail(VS08V_Emails::ADMIN_RECIPIENTS, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }

    public static function format_booking_recap_for_email($data, $order_id) {
        $params = $data['params'] ?? [];
        $devis = $data['devis'] ?? [];
        $m = class_exists('VS08V_MetaBoxes') && !empty($data['voyage_id']) ? VS08V_MetaBoxes::get($data['voyage_id']) : [];
        $hotel_nom = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
        $destination = $m['destination'] ?? '';

        $html = '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:13px;">'
            . '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;width:180px;">N° contrat</td><td style="border:1px solid #e0e0e0;">VS08-' . $order_id . '</td></tr>'
            . '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Voyage</td><td style="border:1px solid #e0e0e0;">' . esc_html($data['voyage_titre'] ?? '') . '</td></tr>'
            . '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Destination</td><td style="border:1px solid #e0e0e0;">' . esc_html($destination) . '</td></tr>'
            . '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Date départ</td><td style="border:1px solid #e0e0e0;">' . esc_html($params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'])) : '') . '</td></tr>'
            . '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Hébergement</td><td style="border:1px solid #e0e0e0;">' . esc_html($hotel_nom) . '</td></tr>'
            . '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Voyageurs</td><td style="border:1px solid #e0e0e0;">' . (int) ($devis['nb_total'] ?? 0) . ' personne(s)</td></tr>';
        if (!empty($params['vol_aller_num'])) {
            $html .= '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Vol aller</td><td style="border:1px solid #e0e0e0;">' . esc_html($params['vol_aller_num'] . ' — ' . ($params['vol_aller_depart'] ?? '') . ' → ' . ($params['vol_aller_arrivee'] ?? '')) . '</td></tr>';
        }
        if (!empty($params['vol_retour_num'])) {
            $html .= '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Vol retour</td><td style="border:1px solid #e0e0e0;">' . esc_html($params['vol_retour_num'] . ' — ' . ($params['vol_retour_depart'] ?? '') . ' → ' . ($params['vol_retour_arrivee'] ?? '')) . '</td></tr>';
        }
        $html .= '<tr><td style="border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Total</td><td style="border:1px solid #e0e0e0;">' . number_format((float) ($data['total'] ?? 0), 2, ',', ' ') . ' €</td></tr>'
            . '</table>';
        return $html;
    }

    public static function get_contract_url($order_id) {
        return add_query_arg([
            'vs08_contract' => 1,
            'order_id'      => (int) $order_id,
            'key'           => wp_create_nonce('vs08_contract_' . $order_id),
        ], home_url('/espace-voyageur/'));
    }

    public static function maybe_output_contract() {
        if (empty($_GET['vs08_contract']) || empty($_GET['order_id']) || empty($_GET['key'])) {
            return;
        }
        $order_id = (int) $_GET['order_id'];
        $key = sanitize_text_field(wp_unslash($_GET['key']));
        if (!wp_verify_nonce($key, 'vs08_contract_' . $order_id)) {
            wp_die('Lien invalide ou expiré.');
        }
        if (!self::current_user_can_view_order($order_id)) {
            wp_die('Accès refusé.');
        }
        $html = VS08V_Contract::generate($order_id);
        if (empty($html)) {
            wp_die('Contrat non disponible.');
        }
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /* ================================================================
     * URLs
     * ============================================================= */

    public static function base_url() {
        return home_url('/espace-voyageur/');
    }

    public static function voyage_url($order_id) {
        return home_url('/espace-voyageur/voyage/' . (int) $order_id . '/');
    }

    /* ================================================================
     * Enregistrement : routes, template_redirect, AJAX
     * ============================================================= */

    public static function register() {
        add_action('init', [__CLASS__, 'register_routes'], 11);
        add_action('template_redirect', [__CLASS__, 'render_page']);
        add_action('template_redirect', [__CLASS__, 'render_auth_page']);
        add_action('template_redirect', [__CLASS__, 'maybe_output_contract']);
        add_action('wp_ajax_vs08v_traveler_question', [__CLASS__, 'ajax_question']);
        add_action('wp_ajax_vs08v_traveler_solde', [__CLASS__, 'ajax_solde']);
        add_action('wp_ajax_vs08v_traveler_profile_save', [__CLASS__, 'ajax_profile_save']);
        add_action('wp_ajax_vs08v_traveler_profile_photo', [__CLASS__, 'ajax_profile_photo']);
        add_action('wp_ajax_vs08v_traveler_checklist', [__CLASS__, 'ajax_checklist']);
        add_action('wp_ajax_vs08v_traveler_wishlist', [__CLASS__, 'ajax_wishlist']);
        add_action('wp_ajax_vs08v_traveler_review', [__CLASS__, 'ajax_review']);
        add_action('wp_ajax_vs08v_traveler_send_documents', [__CLASS__, 'ajax_send_documents']);
        add_action('wp_ajax_vs08v_traveler_voyageur_save', [__CLASS__, 'ajax_voyageur_save']);
        add_action('wp_ajax_vs08v_traveler_voyageur_delete', [__CLASS__, 'ajax_voyageur_delete']);
        add_action('wp_ajax_nopriv_vs08v_traveler_wishlist', [__CLASS__, 'ajax_wishlist']);
        add_action('wp_ajax_nopriv_vs08v_auth_login', [__CLASS__, 'ajax_auth_login']);
        add_action('wp_ajax_nopriv_vs08v_auth_register', [__CLASS__, 'ajax_auth_register']);
        add_action('wp_ajax_vs08v_auth_login', [__CLASS__, 'ajax_auth_login']);
        add_action('wp_ajax_vs08v_auth_register', [__CLASS__, 'ajax_auth_register']);
        add_filter('login_url', [__CLASS__, 'filter_login_url'], 20, 3);
        add_filter('lostpassword_url', [__CLASS__, 'filter_lostpassword_url'], 20, 2);
    }

    public static function auth_url() {
        return home_url('/connexion/');
    }

    /** Redirige les appels a wp_login_url() vers /connexion/. */
    public static function filter_login_url($login_url, $redirect, $force_reauth) {
        $url = self::auth_url();
        if ($redirect) {
            $url = add_query_arg('redirect_to', urlencode($redirect), $url);
        }
        return $url;
    }

    /** Redirige les appels a wp_lostpassword_url(). */
    public static function filter_lostpassword_url($url, $redirect) {
        return home_url('/connexion/?tab=forgot');
    }

    /** Retourne l'URL de la page profil. */
    public static function profile_url() {
        return home_url('/espace-voyageur/profil/');
    }

    /** Infos facturation sauvegardées (user meta). */
    public static function get_saved_facturation($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $saved = get_user_meta($user_id, 'vs08_facturation', true);
        return is_array($saved) ? $saved : [];
    }

    /** Photo de profil (attachment ID ou 0). */
    public static function get_profile_photo_id($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        return (int) get_user_meta($user_id, 'vs08_profile_photo_id', true);
    }

    /** Voyageurs réguliers (pour pré-remplissage au step voyageurs). */
    public static function get_saved_voyageurs($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $saved = get_user_meta($user_id, 'vs08_voyageurs_reguliers', true);
        return is_array($saved) ? $saved : [];
    }

    public static function ajax_voyageur_save() {
        check_ajax_referer('vs08v_traveler', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'Non connecté.']);
        }
        $prenom   = sanitize_text_field($_POST['prenom'] ?? '');
        $nom      = sanitize_text_field($_POST['nom'] ?? '');
        $ddn      = sanitize_text_field($_POST['ddn'] ?? '');
        $passeport= sanitize_text_field($_POST['passeport'] ?? '');
        $type     = (isset($_POST['type']) && $_POST['type'] === 'non-golfeur') ? 'non-golfeur' : 'golfeur';
        if ($prenom === '' || $nom === '') {
            wp_send_json_error(['message' => 'Prénom et nom obligatoires.']);
        }
        $voyageurs = self::get_saved_voyageurs($user_id);
        $index = isset($_POST['index']) ? (int) $_POST['index'] : -1;
        $entry = ['prenom' => $prenom, 'nom' => $nom, 'ddn' => $ddn, 'passeport' => $passeport, 'type' => $type];
        if ($index >= 0 && isset($voyageurs[$index])) {
            $voyageurs[$index] = $entry;
        } else {
            $voyageurs[] = $entry;
        }
        update_user_meta($user_id, 'vs08_voyageurs_reguliers', $voyageurs);
        wp_send_json_success(['message' => 'Voyageur enregistré.', 'voyageurs' => $voyageurs]);
    }

    public static function ajax_voyageur_delete() {
        check_ajax_referer('vs08v_traveler', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'Non connecté.']);
        }
        $index = isset($_POST['index']) ? (int) $_POST['index'] : -1;
        $voyageurs = self::get_saved_voyageurs($user_id);
        if ($index >= 0 && isset($voyageurs[$index])) {
            array_splice($voyageurs, $index, 1);
            update_user_meta($user_id, 'vs08_voyageurs_reguliers', $voyageurs);
        }
        wp_send_json_success(['message' => 'Voyageur retiré.', 'voyageurs' => $voyageurs]);
    }

    public static function ajax_profile_save() {
        check_ajax_referer('vs08v_traveler', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'Non connecté.']);
        }
        $facturation = [
            'prenom'  => sanitize_text_field($_POST['prenom'] ?? ''),
            'nom'     => sanitize_text_field($_POST['nom'] ?? ''),
            'email'   => sanitize_email($_POST['email'] ?? ''),
            'tel'     => sanitize_text_field($_POST['tel'] ?? ''),
            'adresse' => sanitize_textarea_field($_POST['adresse'] ?? ''),
            'cp'      => sanitize_text_field($_POST['cp'] ?? ''),
            'ville'   => sanitize_text_field($_POST['ville'] ?? ''),
        ];
        update_user_meta($user_id, 'vs08_facturation', $facturation);
        if (!empty($_POST['profile_photo_id'])) {
            $att_id = (int) $_POST['profile_photo_id'];
            if ($att_id && get_post_type($att_id) === 'attachment') {
                update_user_meta($user_id, 'vs08_profile_photo_id', $att_id);
            }
        }
        wp_send_json_success(['message' => 'Profil enregistré.']);
    }

    public static function ajax_profile_photo() {
        check_ajax_referer('vs08v_traveler', 'nonce');
        if (!get_current_user_id()) {
            wp_send_json_error(['message' => 'Non connecté.']);
        }
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'Aucun fichier ou erreur d\'upload.']);
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $att_id = media_handle_upload('photo', 0);
        if (is_wp_error($att_id)) {
            wp_send_json_error(['message' => $att_id->get_error_message()]);
        }
        update_user_meta(get_current_user_id(), 'vs08_profile_photo_id', $att_id);
        wp_send_json_success(['attachment_id' => $att_id, 'url' => wp_get_attachment_image_url($att_id, 'thumbnail')]);
    }

    public static function register_routes() {
        add_rewrite_rule('^espace-voyageur/?$', 'index.php?vs08_espace=list', 'top');
        add_rewrite_rule('^espace-voyageur/profil/?$', 'index.php?vs08_espace=profil', 'top');
        add_rewrite_rule('^espace-voyageur/favoris/?$', 'index.php?vs08_espace=favoris', 'top');
        add_rewrite_rule('^espace-voyageur/voyage/([0-9]+)/?$', 'index.php?vs08_espace=detail&vs08_voyage_order=$matches[1]', 'top');
        add_rewrite_rule('^connexion/?$', 'index.php?vs08_auth=1', 'top');
        add_rewrite_tag('%vs08_espace%', '([a-z]+)');
        add_rewrite_tag('%vs08_voyage_order%', '([0-9]+)');
        add_rewrite_tag('%vs08_auth%', '([0-9]+)');

        if (get_option('vs08v_espace_rewrite_v', '') !== '2.5') {
            flush_rewrite_rules(false);
            update_option('vs08v_espace_rewrite_v', '2.5');
        }
    }

    public static function render_page() {
        $espace = get_query_var('vs08_espace');
        if (!$espace) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_redirect(self::auth_url() . '?redirect_to=' . urlencode(self::base_url()));
            exit;
        }

        // Contrat : si demandé en GET, afficher le contrat et quitter (évite d'afficher la liste)
        if (!empty($_GET['vs08_contract']) && !empty($_GET['order_id']) && !empty($_GET['key'])) {
            self::maybe_output_contract();
            return;
        }

        wp_enqueue_style(
            'vs08v-espace-voyageur',
            VS08V_URL . 'assets/css/espace-voyageur.css',
            [],
            filemtime(VS08V_PATH . 'assets/css/espace-voyageur.css')
        );
        wp_enqueue_style('vs08-calendar', VS08V_URL . 'assets/css/vs08-calendar.css', [], '1.3.0');
        wp_enqueue_script('vs08-calendar', VS08V_URL . 'assets/js/vs08-calendar.js', [], '1.3.0', false);

        include VS08V_PATH . 'templates/espace-voyageur.php';
        exit;
    }

    /** Page /connexion/ : page standalone login/register. */
    public static function render_auth_page() {
        $is_connexion_url = get_query_var('vs08_auth')
            || (isset($_SERVER['REQUEST_URI']) && preg_match('#^/connexion/?(\?|$)#', wp_unslash($_SERVER['REQUEST_URI'])));
        if (!$is_connexion_url) {
            return;
        }
        if (is_user_logged_in()) {
            $redir = !empty($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : self::base_url();
            wp_safe_redirect($redir);
            exit;
        }
        $css_file = VS08V_PATH . 'assets/css/auth.css';
        wp_enqueue_style(
            'vs08v-auth',
            VS08V_URL . 'assets/css/auth.css',
            [],
            file_exists($css_file) ? (string) filemtime($css_file) : VS08V_VER
        );
        include VS08V_PATH . 'templates/page-auth.php';
        exit;
    }

    /** AJAX login (nopriv). */
    public static function ajax_auth_login() {
        check_ajax_referer('vs08v_auth', 'nonce');

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $transient_key = 'vs08_login_attempts_' . md5($ip);
        $attempts = (int) get_transient($transient_key);
        if ($attempts >= 5) {
            wp_send_json_error(['message' => 'Trop de tentatives. Veuillez patienter quelques minutes.']);
        }

        $login = sanitize_text_field($_POST['login'] ?? '');
        $pass = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (!$login || !$pass) {
            wp_send_json_error(['message' => 'Veuillez remplir tous les champs.']);
        }

        $user = wp_signon([
            'user_login'    => $login,
            'user_password' => $pass,
            'remember'      => $remember,
        ], is_ssl());

        if (is_wp_error($user)) {
            set_transient($transient_key, $attempts + 1, 300);
            wp_send_json_error(['message' => 'Identifiants incorrects. Veuillez réessayer.']);
        }

        delete_transient($transient_key);
        $redirect = !empty($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : self::base_url();
        wp_send_json_success(['redirect' => $redirect]);
    }

    /** AJAX register (nopriv). */
    public static function ajax_auth_register() {
        check_ajax_referer('vs08v_auth', 'nonce');

        if (!get_option('users_can_register')) {
            update_option('users_can_register', 1);
        }

        $prenom = sanitize_text_field($_POST['prenom'] ?? '');
        $nom    = sanitize_text_field($_POST['nom'] ?? '');
        $email  = sanitize_email($_POST['email'] ?? '');
        $pass   = $_POST['password'] ?? '';

        if (!$prenom || !$nom) {
            wp_send_json_error(['message' => 'Prénom et nom sont obligatoires.']);
        }
        if (!$email || !is_email($email)) {
            wp_send_json_error(['message' => 'Adresse e-mail invalide.']);
        }
        if (strlen($pass) < 8) {
            wp_send_json_error(['message' => 'Le mot de passe doit contenir au moins 8 caractères.']);
        }
        if (email_exists($email)) {
            wp_send_json_error(['message' => 'Cette adresse e-mail est déjà utilisée. Connectez-vous.']);
        }

        $username = sanitize_user(strtolower($prenom . '.' . $nom));
        $username = str_replace(' ', '.', $username);
        $base = $username;
        $i = 1;
        while (username_exists($username)) {
            $username = $base . $i;
            $i++;
        }

        $user_id = wp_create_user($username, $pass, $email);
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        wp_update_user([
            'ID'         => $user_id,
            'first_name' => $prenom,
            'last_name'  => $nom,
            'role'       => 'customer',
        ]);

        update_user_meta($user_id, 'vs08_facturation', [
            'prenom' => $prenom,
            'nom'    => $nom,
            'email'  => $email,
        ]);

        $user = wp_signon([
            'user_login'    => $username,
            'user_password' => $pass,
            'remember'      => true,
        ], is_ssl());

        $redirect = !empty($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : self::base_url();
        wp_send_json_success(['redirect' => $redirect]);
    }

    /* ================================================================
     * AJAX
     * ============================================================= */

    public static function ajax_question() {
        check_ajax_referer('vs08v_traveler', 'nonce');
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $sujet    = isset($_POST['sujet']) ? sanitize_text_field($_POST['sujet']) : '';
        $message  = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        if (!$order_id || !$sujet || !$message) {
            wp_send_json_error(['message' => 'Veuillez remplir tous les champs.']);
        }
        if (!self::current_user_can_view_order($order_id)) {
            wp_send_json_error(['message' => 'Accès refusé.']);
        }
        $sent = self::send_question_email($order_id, $sujet, $message);
        if ($sent) {
            wp_send_json_success(['message' => 'Votre message a bien été envoyé. Nous vous répondrons rapidement.']);
        }
        wp_send_json_error(['message' => 'Erreur lors de l\'envoi. Veuillez réessayer.']);
    }

    public static function ajax_solde() {
        $log_file = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/vs08-solde-debug.log' : '';
        if ($log_file) {
            @file_put_contents($log_file, date('Y-m-d H:i:s') . ' ajax_solde CALLED' . "\n", FILE_APPEND | LOCK_EX);
        }
        @set_time_limit(25);
        check_ajax_referer('vs08v_traveler', 'nonce');
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        if (!$order_id) {
            wp_send_json_error(['message' => 'Commande invalide.']);
        }
        $amount = isset($_POST['amount']) && $_POST['amount'] !== '' ? (float) $_POST['amount'] : null;

        // Paybox Mail désactivé temporairement — debug en cours.
        // Quand l'API sera validée, décommenter le bloc ci-dessous.
        /*
        if (class_exists('VS08V_Paybox_Mail') && VS08V_Paybox_Mail::is_configured()) {
            try {
                $result = VS08V_Paybox_Mail::create_solde_payment($order_id, $amount);
                if ($result['success']) {
                    wp_send_json_success([
                        'mode'        => 'paybox_mail',
                        'message'     => $result['message'],
                        'payment_url' => $result['payment_url'] ?? '',
                        'amount'      => $result['amount'] ?? 0,
                    ]);
                }
                error_log('VS08 Paybox Mail failed, fallback WooCommerce: ' . ($result['error'] ?? 'unknown'));
            } catch (\Throwable $e) {
                error_log('VS08 Paybox Mail crash, fallback WooCommerce: ' . $e->getMessage());
            }
        }
        */

        // Paiement via WooCommerce checkout (Paybox)
        try {
            $result = self::create_solde_order($order_id, $amount);
            if ($result['success'] && !empty($result['payment_url'])) {
                wp_send_json_success(['redirect' => $result['payment_url']]);
            }
            wp_send_json_error(['message' => $result['error'] ?? 'Impossible de créer le paiement du solde.']);
        } catch (\Throwable $e) {
            error_log('VS08 ajax_solde exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            wp_send_json_error(['message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
    }

    /* ================================================================
     * CHECKLIST PRÉ-DÉPART
     * ============================================================= */

    public static function get_checklist_items() {
        return [
            'passeport'     => 'Passeport (ou CNI) valide au moins 6 mois après le retour',
            'assurance'     => 'Assurance voyage / rapatriement',
            'checkin'       => 'Check-in en ligne effectué (si proposé par la compagnie)',
            'contrat'       => 'Contrat de vente imprimé ou sauvegardé',
            'documents_vol' => 'Confirmation vol et horaires à jour',
        ];
    }

    public static function get_saved_checklist($order_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return [];
        }
        $key   = 'vs08_checklist_' . (int) $order_id;
        $saved = get_user_meta($user_id, $key, true);
        return is_array($saved) ? $saved : [];
    }

    public static function ajax_checklist() {
        check_ajax_referer('vs08v_traveler', 'nonce');
        $user_id  = get_current_user_id();
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $checked  = isset($_POST['checked']) && is_array($_POST['checked']) ? array_map('sanitize_text_field', $_POST['checked']) : [];
        if (!$user_id || !$order_id || !self::current_user_can_view_order($order_id)) {
            wp_send_json_error(['message' => 'Accès refusé.']);
        }
        $allowed = array_keys(self::get_checklist_items());
        $checked = array_intersect($checked, $allowed);
        update_user_meta($user_id, 'vs08_checklist_' . $order_id, $checked);
        wp_send_json_success(['checked' => array_values($checked)]);
    }

    /* ================================================================
     * WISHLIST (liste d'envies)
     * ============================================================= */

    public static function get_wishlist($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $ids = get_user_meta($user_id, 'vs08_wishlist', true);
        if (!is_array($ids)) {
            return [];
        }
        return array_filter(array_map('intval', $ids));
    }

    public static function is_in_wishlist($voyage_id, $user_id = 0) {
        return in_array((int) $voyage_id, self::get_wishlist($user_id), true);
    }

    public static function add_to_wishlist($user_id, $voyage_id) {
        $voyage_id = (int) $voyage_id;
        if (get_post_type($voyage_id) !== 'vs08_voyage') {
            return false;
        }
        $ids = self::get_wishlist($user_id);
        if (in_array($voyage_id, $ids, true)) {
            return true;
        }
        $ids[] = $voyage_id;
        update_user_meta($user_id, 'vs08_wishlist', $ids);
        return true;
    }

    public static function remove_from_wishlist($user_id, $voyage_id) {
        $ids = self::get_wishlist($user_id);
        $ids = array_values(array_filter($ids, function ($id) use ($voyage_id) {
            return (int) $id !== (int) $voyage_id;
        }));
        update_user_meta($user_id, 'vs08_wishlist', $ids);
        return true;
    }

    public static function ajax_wishlist() {
        $voyage_id = isset($_POST['voyage_id']) ? (int) $_POST['voyage_id'] : 0;
        $action_wl = isset($_POST['wishlist_action']) ? sanitize_text_field($_POST['wishlist_action']) : 'toggle';
        if (!$voyage_id || get_post_type($voyage_id) !== 'vs08_voyage') {
            wp_send_json_error(['message' => 'Voyage invalide.']);
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'Connectez-vous pour ajouter des séjours à votre liste.', 'login_required' => true]);
        }
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vs08v_traveler')) {
            wp_send_json_error(['message' => 'Sécurité invalide.']);
        }
        if ($action_wl === 'remove') {
            self::remove_from_wishlist($user_id, $voyage_id);
            wp_send_json_success(['in_wishlist' => false]);
        }
        if (self::is_in_wishlist($voyage_id, $user_id)) {
            self::remove_from_wishlist($user_id, $voyage_id);
            wp_send_json_success(['in_wishlist' => false]);
        } else {
            self::add_to_wishlist($user_id, $voyage_id);
            wp_send_json_success(['in_wishlist' => true]);
        }
    }

    public static function favoris_url() {
        return home_url('/espace-voyageur/favoris/');
    }

    /* ================================================================
     * AVIS CLIENTS (reviews)
     * ============================================================= */

    const REVIEW_TYPE = 'vs08_review';
    const REVIEW_META_RATING = 'vs08_rating';
    const REVIEW_META_ORDER_ID = 'vs08_order_id';

    public static function get_reviews($voyage_id, $approved_only = true) {
        $args = [
            'post_id' => (int) $voyage_id,
            'type'    => self::REVIEW_TYPE,
            'status'  => $approved_only ? 'approve' : 'all',
            'orderby' => 'comment_date',
            'order'   => 'DESC',
        ];
        return get_comments($args);
    }

    public static function get_average_rating($voyage_id) {
        $reviews = self::get_reviews($voyage_id);
        if (empty($reviews)) {
            return 0;
        }
        $sum = 0;
        $n   = 0;
        foreach ($reviews as $c) {
            $r = (int) get_comment_meta($c->comment_ID, self::REVIEW_META_RATING, true);
            if ($r >= 1 && $r <= 5) {
                $sum += $r;
                $n++;
            }
        }
        return $n ? round($sum / $n, 1) : 0;
    }

    /** Le client a-t-il déjà donné son avis pour ce voyage (après un séjour) ? */
    public static function has_user_reviewed_voyage($voyage_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $reviews = get_comments([
            'post_id' => (int) $voyage_id,
            'user_id' => $user_id,
            'type'    => self::REVIEW_TYPE,
        ]);
        return !empty($reviews);
    }

    /** Peut-il laisser un avis ? (voyage passé + a réservé + pas encore avis) */
    public static function can_review($order_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id || !self::current_user_can_view_order($order_id)) {
            return false;
        }
        $order = wc_get_order($order_id);
        $data  = self::get_booking_data_from_order($order);
        if (!$data) {
            return false;
        }
        $voyage_id = (int) ($data['voyage_id'] ?? 0);
        $params    = $data['params'] ?? [];
        $depart    = $params['date_depart'] ?? '';
        if ($depart && $depart >= date('Y-m-d')) {
            return false; // pas encore parti
        }
        return !self::has_user_reviewed_voyage($voyage_id, $user_id);
    }

    public static function ajax_review() {
        check_ajax_referer('vs08v_traveler', 'nonce');
        $user_id   = get_current_user_id();
        $order_id  = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $rating    = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
        $comment   = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
        if (!$user_id || !$order_id || !self::can_review($order_id, $user_id)) {
            wp_send_json_error(['message' => 'Vous ne pouvez pas déposer d\'avis pour ce voyage.']);
        }
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => 'Veuillez choisir une note entre 1 et 5.']);
        }
        $order = wc_get_order($order_id);
        $data  = self::get_booking_data_from_order($order);
        $voyage_id = (int) ($data['voyage_id'] ?? 0);
        $user = get_userdata($user_id);
        $author = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        if (empty($author)) {
            $author = $user->display_name ?? 'Voyageur';
        }
        $comment_id = wp_insert_comment([
            'comment_post_ID'  => $voyage_id,
            'comment_author'   => $author,
            'comment_author_email' => $user->user_email,
            'comment_content'  => $comment,
            'comment_type'     => self::REVIEW_TYPE,
            'comment_approved' => 1,
            'user_id'          => $user_id,
        ]);
        if (!$comment_id) {
            wp_send_json_error(['message' => 'Erreur lors de l\'enregistrement.']);
        }
        add_comment_meta($comment_id, self::REVIEW_META_RATING, $rating);
        add_comment_meta($comment_id, self::REVIEW_META_ORDER_ID, $order_id);
        wp_send_json_success(['message' => 'Merci pour votre avis !']);
    }

    /**
     * Envoi de documents par le client vers les mails admin (év-card-docs).
     */
    public static function ajax_send_documents() {
        if (!isset($_POST['vs08v_docs_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['vs08v_docs_nonce']), 'vs08v_send_documents')) {
            wp_send_json_error(['message' => 'Sécurité invalide.']);
        }
        $user_id  = get_current_user_id();
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        if (!$user_id || !$order_id || !self::current_user_can_view_order($order_id)) {
            wp_send_json_error(['message' => 'Accès refusé.']);
        }
        if (empty($_FILES['vs08v_docs']) || empty($_FILES['vs08v_docs']['name'])) {
            wp_send_json_error(['message' => 'Veuillez sélectionner au moins un fichier.']);
        }
        $files = $_FILES['vs08v_docs'];
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmps  = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];
        $max_size = 10 * 1024 * 1024; // 10 Mo par fichier
        $attachments = [];
        foreach ($names as $i => $name) {
            if (empty($name) || ($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($tmps[$i]) || !is_uploaded_file($tmps[$i])) {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                wp_send_json_error(['message' => 'Type de fichier non autorisé : ' . esc_html($name) . '. Autorisés : PDF, images, Word.']);
            }
            if (isset($files['size']) && (is_array($files['size']) ? $files['size'][$i] : $files['size']) > $max_size) {
                wp_send_json_error(['message' => 'Fichier trop volumineux : ' . esc_html($name) . '. Max 10 Mo.']);
            }
            $attachments[] = $tmps[$i];
        }
        if (empty($attachments)) {
            wp_send_json_error(['message' => 'Aucun fichier valide à envoyer.']);
        }
        $order   = wc_get_order($order_id);
        $data    = self::get_booking_data_from_order($order);
        $fact    = $data['facturation'] ?? [];
        $client  = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
        $subject = 'Documents client — Voyage VS08-' . $order_id;
        $body    = '<p>Un client a envoyé des documents pour le voyage <strong>VS08-' . $order_id . '</strong>.</p>'
            . '<p><strong>Client :</strong> ' . esc_html($client) . ' — ' . esc_html($fact['email'] ?? '') . '</p>'
            . '<p>Pièces jointes : ' . count($attachments) . ' fichier(s).</p>';
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $recipients = class_exists('VS08V_Emails') ? VS08V_Emails::ADMIN_RECIPIENTS : [];
        if (empty($recipients)) {
            wp_send_json_error(['message' => 'Configuration email manquante.']);
        }
        $sent = wp_mail($recipients, $subject, $body, $headers, $attachments);
        if (!$sent) {
            wp_send_json_error(['message' => 'L\'envoi a échoué. Réessayez ou contactez-nous.']);
        }
        wp_send_json_success(['message' => 'Vos documents ont bien été envoyés à l\'agence.']);
    }
}
