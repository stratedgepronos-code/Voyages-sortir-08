<?php
if (!defined('ABSPATH')) exit;

class VS08C_Booking {

    /**
     * Traite la soumission de réservation circuit.
     * Même flux que VS08V_Booking : calcul → produit WooCommerce → redirection checkout.
     */
    public static function process_submission() {
        if (empty($_POST['circuit_id'])) {
            return ['error' => 'Données manquantes. Rechargez la page et réessayez.'];
        }

        $circuit_id = intval($_POST['circuit_id']);
        if (!$circuit_id || get_post_type($circuit_id) !== 'vs08_circuit') {
            return ['error' => 'Circuit introuvable.'];
        }

        $m = VS08C_Meta::get($circuit_id);

        // Paramètres du devis
        $params = [
            'nb_adultes'   => intval($_POST['nb_adultes'] ?? 2),
            'nb_enfants'   => intval($_POST['nb_enfants'] ?? 0),
            'nb_chambres'  => intval($_POST['nb_chambres'] ?? 1),
            'date_depart'  => sanitize_text_field($_POST['date_depart'] ?? ''),
            'aeroport'     => strtoupper(sanitize_text_field($_POST['aeroport'] ?? '')),
            'prix_vol'     => floatval($_POST['prix_vol'] ?? 0) + floatval($_POST['vol_delta_pax'] ?? 0),
            'rooms'        => $_POST['rooms'] ?? '',
            'vol_delta_pax'     => floatval($_POST['vol_delta_pax'] ?? 0),
            'vol_aller_depart'  => sanitize_text_field($_POST['vol_aller_depart'] ?? ''),
            'vol_aller_arrivee' => sanitize_text_field($_POST['vol_aller_arrivee'] ?? ''),
            'vol_aller_num'     => sanitize_text_field($_POST['vol_aller_num'] ?? ''),
            'vol_aller_cie'     => sanitize_text_field($_POST['vol_aller_cie'] ?? ''),
            'vol_retour_depart' => sanitize_text_field($_POST['vol_retour_depart'] ?? ''),
            'vol_retour_arrivee'=> sanitize_text_field($_POST['vol_retour_arrivee'] ?? ''),
            'vol_retour_num'    => sanitize_text_field($_POST['vol_retour_num'] ?? ''),
        ];

        // Fermeture des ventes J-20 : interdire les départs dans moins de 20 jours
        if (!empty($params['date_depart'])) {
            $jours_avant_depart = (strtotime($params['date_depart']) - time()) / 86400;
            if ($jours_avant_depart < 20) {
                return ['error' => 'Les réservations de circuits sont fermées à moins de 20 jours du départ. Veuillez choisir une date ultérieure.'];
            }
        }

        // Calcul du prix
        $devis = VS08C_Calculator::calculate($circuit_id, $params);
        $nb_total = $devis['nb_total'];

        // Options sélectionnées
        $options_selected = [];
        $options_dispo = $m['options'] ?? [];
        $options_totaux = 0;
        if (!empty($_POST['options']) && is_array($_POST['options'])) {
            foreach ($_POST['options'] as $opt_id => $qty) {
                $qty = intval($qty);
                if ($qty <= 0) continue;
                foreach ($options_dispo as $opt) {
                    if (($opt['id'] ?? '') !== $opt_id) continue;
                    $prix_opt = 0;
                    switch ($opt['type'] ?? 'par_pers') {
                        case 'par_pers': $prix_opt = floatval($opt['prix']) * $nb_total; break;
                        case 'quantite': $prix_opt = floatval($opt['prix']) * $qty; break;
                        case 'fixe':     $prix_opt = floatval($opt['prix']); break;
                    }
                    $options_selected[] = ['id' => $opt_id, 'label' => $opt['label'], 'qty' => $qty, 'prix' => $prix_opt];
                    $options_totaux += $prix_opt;
                }
            }
        }

        // Assurance (via vs08-voyages si disponible)
        $assurance = 0;
        if (!empty($_POST['assurance']) && class_exists('VS08V_Insurance')) {
            $assurance = VS08V_Insurance::get_price($devis['par_pers']) * $nb_total;
        }

        $total_final = (int) ceil($devis['total'] + $options_totaux + $assurance);

        // Acompte — aligné sur VS08C_Calculator : plancher = vols (avec supp. aéroport) + bagages soute
        $acompte_pct = floatval($m['acompte_pct'] ?? 30);
        $acompte = $total_final * $acompte_pct / 100;

        $ae = strtoupper(sanitize_text_field($params['aeroport'] ?? ''));
        $supp_aero = 0;
        if ($ae !== '' && !empty($m['aeroports'])) {
            foreach ($m['aeroports'] as $a) {
                if (strtoupper($a['code'] ?? '') === $ae) {
                    $supp_aero = floatval($a['supp'] ?? 0);
                    break;
                }
            }
        }
        $prix_vol_raw = floatval($params['prix_vol'] ?? 0);
        if ($prix_vol_raw <= 0) {
            $prix_vol_raw = floatval($m['prix_vol_base'] ?? 0);
        }
        $prix_vol_final_pp = $prix_vol_raw + $supp_aero;
        $cout_vol = $prix_vol_final_pp * $nb_total;
        $bagage_plancher = floatval($m['prix_bagage'] ?? 0) * $nb_total;
        $plancher = $cout_vol + $bagage_plancher;

        if ($plancher > 0 && $acompte < $plancher && $total_final > 0) {
            $acompte_pct = (int) ceil(($plancher / $total_final) * 100 / 5) * 5;
            $acompte = $total_final * $acompte_pct / 100;
        }
        $acompte = (int) ceil($acompte);

        // Payer tout si départ < delai_solde jours
        $payer_tout = false;
        $delai = intval($m['delai_solde'] ?? 30);
        if ($params['date_depart']) {
            $jours = (strtotime($params['date_depart']) - time()) / 86400;
            if ($jours <= $delai) $payer_tout = true;
        }

        // Voyageurs
        $voyageurs = [];
        if (!empty($_POST['voyageurs']) && is_array($_POST['voyageurs'])) {
            foreach (array_values($_POST['voyageurs']) as $v) {
                if (!is_array($v)) continue;
                $voyageurs[] = [
                    'prenom'    => sanitize_text_field($v['prenom'] ?? ''),
                    'nom'       => sanitize_text_field($v['nom'] ?? ''),
                    'ddn'       => sanitize_text_field($v['ddn'] ?? ''),
                    'passeport' => sanitize_text_field($v['passeport'] ?? ''),
                    'chambre'   => intval($v['chambre'] ?? 1),
                    'type'      => sanitize_text_field($v['type'] ?? 'adulte'),
                ];
            }
        }

        // Facturation
        $facturation = [
            'prenom'  => sanitize_text_field($_POST['fact_prenom'] ?? ''),
            'nom'     => sanitize_text_field($_POST['fact_nom'] ?? ''),
            'email'   => sanitize_email($_POST['fact_email'] ?? ''),
            'tel'     => sanitize_text_field($_POST['fact_tel'] ?? ''),
            'adresse' => sanitize_textarea_field($_POST['fact_adresse'] ?? ''),
            'cp'      => sanitize_text_field($_POST['fact_cp'] ?? ''),
            'ville'   => sanitize_text_field($_POST['fact_ville'] ?? ''),
        ];

        $payment_mode = sanitize_text_field($_POST['vs08_payment_mode'] ?? 'card');
        if (!in_array($payment_mode, ['card', 'agency'], true)) {
            $payment_mode = 'card';
        }
        if ($payment_mode === 'agency' && empty($_POST['vs08_agence_confirm'])) {
            return ['error' => 'Cochez la case « Paiement en agence » et acceptez que le prix n’est pas définitivement bloqué tant que le règlement n’est pas effectué.'];
        }
        $reglement_agence = ($payment_mode === 'agency');

        // Créer le produit WooCommerce
        $booking_data = [
            'circuit_id'      => $circuit_id,
            'circuit_titre'   => get_the_title($circuit_id),
            'type'            => 'circuit',
            'params'          => $params,
            'devis'           => $devis,
            'options'         => $options_selected,
            'assurance'       => $assurance,
            'total'           => $total_final,
            'acompte'         => $acompte,
            'acompte_pct'     => $acompte_pct,
            'payer_tout'      => $payer_tout,
            'voyageurs'        => $voyageurs,
            'facturation'      => $facturation,
            'reglement_agence' => $reglement_agence,
            'payment_mode'     => $payment_mode,
        ];

        $product_id = VS08C_Woo::create_booking_product($booking_data);
        if (is_wp_error($product_id)) {
            return ['error' => $product_id->get_error_message()];
        }

        // Transient pour reconstruire le panier (même pattern vs08-voyages)
        $cart_token = wp_generate_password(32, false);
        set_transient('vs08c_cart_' . $cart_token, [
            'product_id'   => $product_id,
            'payment_mode' => $payment_mode,
        ], 900);

        // Ajout panier classique (fallback)
        try {
            if (function_exists('WC') && WC()) {
                if (is_null(WC()->session) && method_exists(WC(), 'initialize_session')) WC()->initialize_session();
                if (is_null(WC()->cart)) {
                    if (function_exists('wc_load_cart')) wc_load_cart();
                    elseif (method_exists(WC(), 'initialize_cart')) WC()->initialize_cart();
                }
                if (WC()->cart) {
                    WC()->cart->empty_cart();
                    WC()->cart->add_to_cart($product_id, 1);
                    if (WC()->session) {
                        if (!WC()->session->has_session()) WC()->session->set_customer_session_cookie(true);
                        WC()->cart->calculate_totals();
                        WC()->cart->set_session();
                        WC()->cart->maybe_set_cart_cookies();
                        if (method_exists(WC()->session, 'save_data')) WC()->session->save_data();
                    }
                }
            }
        } catch (\Exception $e) { /* transient = solution principale */ }

        $checkout_url = add_query_arg('vs08c_cart', $cart_token, wc_get_checkout_url());
        return ['redirect' => $checkout_url];
    }
}
