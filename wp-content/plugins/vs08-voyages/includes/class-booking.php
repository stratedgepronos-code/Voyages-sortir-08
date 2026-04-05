<?php
class VS08V_Booking {

    public static function process_submission() {
        if (empty($_POST['voyage_id'])) {
            return ['error' => 'Données manquantes. Rechargez la page et réessayez.'];
        }
        $voyage_id = intval($_POST['voyage_id']);
        if (!$voyage_id || get_post_type($voyage_id) !== 'vs08_voyage') {
            return ['error' => 'Voyage introuvable.'];
        }

        // Paramètres du devis
        $params = [
            'nb_golfeurs'    => intval($_POST['nb_golfeurs'] ?? 1),
            'nb_nongolfeurs' => intval($_POST['nb_nongolfeurs'] ?? 0),
            'type_chambre'   => sanitize_text_field($_POST['type_chambre'] ?? 'double'),
            'nb_chambres'    => intval($_POST['nb_chambres'] ?? 1),
            'date_depart'    => sanitize_text_field($_POST['date_depart'] ?? ''),
            'aeroport'       => sanitize_text_field($_POST['aeroport'] ?? ''),
            'prix_vol'       => floatval($_POST['prix_vol'] ?? 0) + floatval($_POST['vol_delta_pax'] ?? 0),
            'airline_iata'   => strtoupper(sanitize_text_field($_POST['airline_iata'] ?? '')),
            'vol_delta_pax'     => floatval($_POST['vol_delta_pax'] ?? 0),
            'vol_aller_depart'  => sanitize_text_field($_POST['vol_aller_depart'] ?? ''),
            'vol_aller_arrivee' => sanitize_text_field($_POST['vol_aller_arrivee'] ?? ''),
            'vol_aller_num'     => sanitize_text_field($_POST['vol_aller_num'] ?? ''),
            'vol_aller_cie'     => sanitize_text_field($_POST['vol_aller_cie'] ?? ''),
            'vol_retour_depart' => sanitize_text_field($_POST['vol_retour_depart'] ?? ''),
            'vol_retour_arrivee'=> sanitize_text_field($_POST['vol_retour_arrivee'] ?? ''),
            'vol_retour_num'    => sanitize_text_field($_POST['vol_retour_num'] ?? ''),
        ];

        // Fermeture des ventes J-7 : interdire les départs dans moins de 7 jours
        if (!empty($params['date_depart'])) {
            $jours_avant_depart = (strtotime($params['date_depart']) - time()) / 86400;
            if ($jours_avant_depart < 7) {
                return ['error' => 'Les réservations sont fermées à moins de 7 jours du départ. Veuillez choisir une date ultérieure.'];
            }
        }

        // Calcul du prix
        $devis = VS08V_Calculator::calculate($voyage_id, $params);

        // Options sélectionnées
        $options_selected = [];
        $m = VS08V_MetaBoxes::get($voyage_id);
        $options_disponibles = $m['options'] ?? [];
        $options_totaux = 0;
        if (!empty($_POST['options'])) {
            foreach ($_POST['options'] as $opt_id => $qty) {
                $opt_id = sanitize_text_field($opt_id);
                $qty = intval($qty);
                if ($qty <= 0) continue;
                foreach ($options_disponibles as $opt) {
                    if ($opt['id'] !== $opt_id) continue;
                    $prix_opt = 0;
                    switch ($opt['type']) {
                        case 'par_pers':      $prix_opt = floatval($opt['prix']) * $devis['nb_total']; break;
                        case 'par_pers_nuit': $prix_opt = floatval($opt['prix']) * $devis['nb_total'] * intval($m['duree']??7); break;
                        case 'quantite':      $prix_opt = floatval($opt['prix']) * $qty; break;
                        case 'fixe':          $prix_opt = floatval($opt['prix']); break;
                    }
                    $options_selected[] = ['id' => $opt_id, 'label' => $opt['label'], 'qty' => $qty, 'prix' => $prix_opt];
                    $options_totaux += $prix_opt;
                }
            }
        }

        // Assurance
        $assurance = 0;
        if (!empty($_POST['assurance'])) {
            $assurance = VS08V_Insurance::get_price($devis['par_pers']) * $devis['nb_total'];
        }

        $total_final = $devis['total'] + $options_totaux + $assurance;
        $total_final = (int) ceil($total_final); // Arrondi à l'euro supérieur

        $nb_total = (int) ($devis['nb_total'] ?? ($params['nb_golfeurs'] + $params['nb_nongolfeurs']));
        // Même règle que VS08V_Calculator (plancher vol + bagages) — évite écart tunnel / Paybox
        $params_acompte = $params;
        $params_acompte['nb_bagage_soute'] = isset($_POST['nb_bagage_soute']) ? intval($_POST['nb_bagage_soute']) : $nb_total;
        $params_acompte['nb_bagage_golf']  = isset($_POST['nb_bagage_golf']) ? intval($_POST['nb_bagage_golf']) : intval($params['nb_golfeurs'] ?? 0);
        $ac_brk = VS08V_Calculator::compute_acompte_for_total($m, $params_acompte, $total_final, $nb_total);
        $acompte = $ac_brk['acompte'];
        $acompte_pct = $ac_brk['acompte_pct_final'];

        // Vérifier si solde complet requis (départ dans moins de delai_solde jours)
        $payer_tout = false;
        $delai_solde = intval($m['delai_solde'] ?? 30);
        if ($params['date_depart']) {
            $jours_restants = (strtotime($params['date_depart']) - time()) / 86400;
            if ($jours_restants <= $delai_solde) $payer_tout = true;
        }

        // Voyageurs (PHP peut recevoir indices numériques ou chaînes "0","1")
        $voyageurs = [];
        if (!empty($_POST['voyageurs']) && is_array($_POST['voyageurs'])) {
            $raw = array_values($_POST['voyageurs']);
            foreach ($raw as $v) {
                if (!is_array($v)) continue;
                $voyageurs[] = [
                    'prenom'    => sanitize_text_field($v['prenom'] ?? ''),
                    'nom'       => sanitize_text_field($v['nom'] ?? ''),
                    'ddn'       => sanitize_text_field($v['ddn'] ?? ''),
                    'passeport' => sanitize_text_field($v['passeport'] ?? ''),
                    'chambre'   => intval($v['chambre'] ?? 1),
                    'type'      => (isset($v['type']) && $v['type'] === 'non-golfeur') ? 'accompagnant' : 'golfeur',
                ];
            }
        }

        // Facturation
        $facturation = [
            'prenom' => sanitize_text_field($_POST['fact_prenom'] ?? ''),
            'nom'    => sanitize_text_field($_POST['fact_nom'] ?? ''),
            'email'  => sanitize_email($_POST['fact_email'] ?? ''),
            'tel'    => sanitize_text_field($_POST['fact_tel'] ?? ''),
            'adresse'=> sanitize_textarea_field($_POST['fact_adresse'] ?? ''),
            'cp'     => sanitize_text_field($_POST['fact_cp'] ?? ''),
            'ville'  => sanitize_text_field($_POST['fact_ville'] ?? ''),
        ];

        $payment_mode = sanitize_text_field($_POST['vs08_payment_mode'] ?? 'card');
        if (!in_array($payment_mode, ['card', 'agency'], true)) {
            $payment_mode = 'card';
        }
        if ($payment_mode === 'agency' && empty($_POST['vs08_agence_confirm'])) {
            return ['error' => 'Cochez la case « Paiement en agence » et acceptez que le prix n’est pas définitivement bloqué tant que le règlement n’est pas effectué.'];
        }
        $reglement_agence = ($payment_mode === 'agency');

        // Créer le produit WooCommerce et rediriger
        $woo = new VS08V_Woo();
        $product_id = $woo->create_booking_product([
            'voyage_id'        => $voyage_id,
            'voyage_titre'     => get_the_title($voyage_id),
            'params'           => $params,
            'devis'            => $devis,
            'options'          => $options_selected,
            'assurance'        => $assurance,
            'total'            => $total_final,
            'acompte'          => $acompte,
            'acompte_pct_applied' => $ac_brk['acompte_pct_final'],
            'payer_tout'       => $payer_tout,
            'voyageurs'        => $voyageurs,
            'facturation'      => $facturation,
            'reglement_agence' => $reglement_agence,
            'payment_mode'     => $payment_mode,
        ]);

        if (is_wp_error($product_id)) {
            return ['error' => $product_id->get_error_message()];
        }

        // Sauvegarder l'ID produit dans un transient avec une clé unique.
        // La page checkout reconstituera le panier à partir de cette clé,
        // ce qui élimine le problème de cookies de session non enregistrés
        // entre la requête AJAX/REST et la redirection JavaScript.
        $cart_token = wp_generate_password(32, false);
        set_transient('vs08_cart_' . $cart_token, [
            'product_id'    => $product_id,
            'payment_mode'  => $payment_mode,
        ], 900);

        // Tenter aussi l'ajout au panier classique (fallback si cookies fonctionnent)
        try {
            if (function_exists('WC') && WC()) {
                if (is_null(WC()->session) && method_exists(WC(), 'initialize_session')) {
                    WC()->initialize_session();
                }
                if (is_null(WC()->cart)) {
                    if (function_exists('wc_load_cart')) { wc_load_cart(); }
                    elseif (method_exists(WC(), 'initialize_cart')) { WC()->initialize_cart(); }
                }
                if (WC()->cart) {
                    WC()->cart->empty_cart();
                    WC()->cart->add_to_cart($product_id, 1);
                    if (WC()->session) {
                        if (!WC()->session->has_session()) {
                            WC()->session->set_customer_session_cookie(true);
                        }
                        WC()->cart->calculate_totals();
                        WC()->cart->set_session();
                        WC()->cart->maybe_set_cart_cookies();
                        if (method_exists(WC()->session, 'save_data')) {
                            WC()->session->save_data();
                        }
                        do_action('woocommerce_set_cart_cookies', true);
                    }
                }
            }
        } catch (Exception $e) {
            // Le transient est la solution principale, le panier classique est un bonus
        }

        $checkout_url = wc_get_checkout_url();
        $checkout_url = apply_filters('vs08v_booking_checkout_redirect_url', $checkout_url);
        // Ajouter le token à l'URL pour que la page checkout puisse reconstruire le panier
        $checkout_url = add_query_arg('vs08_cart', $cart_token, $checkout_url);
        return ['redirect' => $checkout_url];
    }
}
