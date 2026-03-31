<?php
/**
 * VS08 SplitPay v2 — Gestion des groupes
 *
 * Approche "2 temps" — WooCommerce MINIMAL :
 *   WC n'est utilisé QUE pour encaisser via Paybox (dans class-splitpay-page.php).
 *   Ici, on ne touche PAS à WooCommerce. Tout est dans nos tables custom.
 *
 *   TEMPS 1 : POST /vs08sp/v1/create-booking
 *     → Crée un groupe splitpay (table custom) + compte WP capitaine
 *     → Redirige vers l'espace voyageur
 *     → AUCUN produit WC, AUCUNE commande WC
 *
 *   TEMPS 2 : POST /vs08sp/v1/configure-group
 *     → Le capitaine configure les participants
 *     → Crée les comptes WP + shares + envoie les liens
 *     → AUCUN produit WC
 *
 *   GET /vs08sp/v1/group/{id}
 *     → Récupère les infos d'un groupe
 */
if (!defined('ABSPATH')) exit;

class VS08SP_Group {

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    /* ══════════════════════════════════════════
     *  ROUTES REST API
     * ══════════════════════════════════════════ */
    public static function register_routes() {
        register_rest_route('vs08sp/v1', '/create-booking', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'handle_create_booking'],
        ]);

        register_rest_route('vs08sp/v1', '/configure-group', [
            'methods'             => 'POST',
            'permission_callback' => function() { return is_user_logged_in(); },
            'callback'            => [__CLASS__, 'handle_configure_group'],
        ]);

        register_rest_route('vs08sp/v1', '/group/(?P<id>\d+)', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'handle_get_group'],
        ]);
    }

    /* ══════════════════════════════════════════
     *  TEMPS 1 : CRÉER LE DOSSIER GROUPE
     * ══════════════════════════════════════════
     *
     *  PAS de WooCommerce ici.
     *  On stocke tout dans vs08sp_groups (booking_data en JSON).
     *  WC interviendra SEULEMENT quand un participant clique
     *  "Payer ma part" (class-splitpay-page.php).
     */
    public static function handle_create_booking(WP_REST_Request $request) {
        $body = $request->get_json_params();

        $voyage_id    = intval($body['voyage_id'] ?? 0);
        $voyage_titre = sanitize_text_field($body['voyage_titre'] ?? '');
        $total        = floatval($body['total'] ?? 0);
        $acompte_pct  = floatval($body['acompte_pct'] ?? 30);
        $payer_tout   = !empty($body['payer_tout']);
        $params       = $body['params'] ?? [];
        $devis        = $body['devis'] ?? [];
        $facturation  = $body['facturation'] ?? [];
        $voyageurs    = $body['voyageurs'] ?? [];

        if (!$voyage_id || $total <= 0) {
            return new WP_REST_Response(['success' => false, 'message' => 'Données de réservation invalides.'], 200);
        }

        $captain_email = sanitize_email($facturation['email'] ?? '');
        if (empty($captain_email) || !is_email($captain_email)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Email de facturation requis pour le paiement groupé.'], 200);
        }

        $captain_name = trim(sanitize_text_field($facturation['prenom'] ?? '') . ' ' . sanitize_text_field($facturation['nom'] ?? ''));

        // ── Calculer l'acompte (même logique que class-booking.php) ──
        $acompte = $total * $acompte_pct / 100;
        $prix_vol_pp = floatval($params['prix_vol'] ?? 0);
        $nb_total = intval($params['nb_golfeurs'] ?? 0) + intval($params['nb_nongolfeurs'] ?? 0);
        $cout_vol_total = $prix_vol_pp * $nb_total;

        if ($cout_vol_total > 0 && $acompte < $cout_vol_total && $total > 0) {
            $pct_reel = ($cout_vol_total / $total) * 100;
            $acompte_pct = ceil($pct_reel / 5) * 5;
            $acompte = $total * $acompte_pct / 100;
        }
        $acompte = (int) ceil($acompte);
        if ($payer_tout) $acompte = $total;

        // ── Créer/trouver le compte WP du capitaine ──
        $user_id = email_exists($captain_email);
        if (!$user_id) {
            $password = wp_generate_password(12, true, false);
            $user_id = wp_create_user($captain_email, $password, $captain_email);
            if (is_wp_error($user_id)) {
                return new WP_REST_Response(['success' => false, 'message' => 'Erreur création compte : ' . $user_id->get_error_message()], 200);
            }
            wp_update_user([
                'ID'           => $user_id,
                'first_name'   => sanitize_text_field($facturation['prenom'] ?? ''),
                'last_name'    => sanitize_text_field($facturation['nom'] ?? ''),
                'display_name' => $captain_name,
                'role'         => 'customer',
            ]);
            VS08SP_Emails::send_account_created($captain_email, $password, $captain_name);
            error_log(sprintf('[VS08SP] Compte WP créé pour capitaine %s (user #%d)', $captain_email, $user_id));
        }

        // ── Stocker TOUTES les données dans notre table custom ──
        $booking_data = [
            'voyage_id'    => $voyage_id,
            'voyage_titre' => $voyage_titre ?: get_the_title($voyage_id),
            'params'       => $params,
            'devis'        => $devis,
            'total'        => $total,
            'acompte'      => $acompte,
            'acompte_pct'  => $acompte_pct,
            'payer_tout'   => $payer_tout,
            'facturation'  => $facturation,
            'voyageurs'    => $voyageurs,
            'splitpay'     => true,
        ];

        $group_id = VS08SP_DB::create_group([
            'voyage_id'       => $voyage_id,
            'voyage_titre'    => $booking_data['voyage_titre'],
            'booking_data'    => $booking_data,
            'captain_email'   => $captain_email,
            'captain_name'    => $captain_name,
            'total_amount'    => $payer_tout ? $total : $acompte,
            'min_share'       => 0,
            'nb_participants' => 2,
        ]);

        if (!$group_id) {
            return new WP_REST_Response(['success' => false, 'message' => 'Erreur lors de la création du dossier.'], 200);
        }

        // Passer en "draft" (en attente de configuration des participants)
        VS08SP_DB::update_group_status($group_id, 'draft');

        error_log(sprintf('[VS08SP] Groupe #%d (draft) créé — capitaine %s — %s €',
            $group_id, $captain_email, number_format($payer_tout ? $total : $acompte, 0, ',', ' ')
        ));

        // ── Connecter automatiquement le capitaine ──
        if (!is_user_logged_in()) {
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);
        }

        // ── Redirection vers l'espace voyageur ──
        $redirect_url = home_url('/espace-voyageur/');
        $redirect_url = add_query_arg('splitpay_group', $group_id, $redirect_url);

        return new WP_REST_Response([
            'success'  => true,
            'group_id' => $group_id,
            'redirect' => $redirect_url,
            'message'  => 'Dossier groupe créé !',
        ], 200);
    }

    /* ══════════════════════════════════════════
     *  TEMPS 2 : CONFIGURER LES PARTICIPANTS
     * ══════════════════════════════════════════
     *
     *  Toujours PAS de WooCommerce.
     *  On crée les comptes WP + les shares dans notre table.
     *  Les produits WC seront créés à la volée quand
     *  chaque participant clique "Payer" (class-splitpay-page.php).
     */
    public static function handle_configure_group(WP_REST_Request $request) {
        $body = $request->get_json_params();
        $group_id     = intval($body['group_id'] ?? 0);
        $participants = $body['participants'] ?? [];

        if (!$group_id || empty($participants)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Données manquantes.'], 200);
        }

        $group = VS08SP_DB::get_group($group_id);
        if (!$group) {
            return new WP_REST_Response(['success' => false, 'message' => 'Groupe introuvable.'], 200);
        }

        $current_user = wp_get_current_user();
        if ($current_user->user_email !== $group['captain_email']) {
            return new WP_REST_Response(['success' => false, 'message' => 'Seul le capitaine peut configurer le groupe.'], 200);
        }

        if ($group['status'] !== 'draft') {
            return new WP_REST_Response(['success' => false, 'message' => 'Ce groupe a déjà été configuré.'], 200);
        }

        if (count($participants) < 2) {
            return new WP_REST_Response(['success' => false, 'message' => 'Il faut au moins 2 participants.'], 200);
        }
        if (count($participants) > VS08SP_MAX_PARTICIPANTS) {
            return new WP_REST_Response(['success' => false, 'message' => 'Maximum ' . VS08SP_MAX_PARTICIPANTS . ' participants.'], 200);
        }

        $amount_to_split = floatval($group['total_amount']);

        // ── Validation ──
        $sum = 0;
        $emails_seen = [];
        foreach ($participants as $i => $p) {
            $email = sanitize_email($p['email'] ?? '');
            if (empty($email) || !is_email($email)) {
                return new WP_REST_Response(['success' => false, 'message' => 'Email invalide pour le participant ' . ($i + 1) . '.'], 200);
            }
            if (in_array(strtolower($email), $emails_seen)) {
                return new WP_REST_Response(['success' => false, 'message' => 'Email en doublon : ' . $email], 200);
            }
            $emails_seen[] = strtolower($email);
            $amount = floatval($p['amount'] ?? 0);
            if ($amount <= 0) {
                return new WP_REST_Response(['success' => false, 'message' => 'Le montant doit être > 0 pour chaque participant.'], 200);
            }
            $sum += $amount;
        }

        if (abs($sum - $amount_to_split) > 1) {
            return new WP_REST_Response(['success' => false, 'message' => sprintf(
                'La somme (%s €) ne correspond pas au montant (%s €).',
                number_format($sum, 0, ',', ' '), number_format($amount_to_split, 0, ',', ' ')
            )], 200);
        }

        // ── Minimum par part ──
        $nb = count($participants);
        $booking_data = $group['booking_data'];
        $prix_vol_pp = floatval($booking_data['params']['prix_vol'] ?? 0);
        $min_share = ceil(max(($amount_to_split / $nb) * 0.30, $prix_vol_pp));

        foreach ($participants as $i => $p) {
            if (floatval($p['amount']) < $min_share) {
                return new WP_REST_Response(['success' => false, 'message' => sprintf('Montant minimum par participant : %d €.', $min_share)], 200);
            }
        }

        // ── Créer les comptes WP + shares ──
        $created_shares = [];
        foreach ($participants as $p) {
            $email  = sanitize_email($p['email']);
            $prenom = sanitize_text_field($p['prenom'] ?? '');
            $nom    = sanitize_text_field($p['nom'] ?? '');
            $name   = trim($prenom . ' ' . $nom);
            $amount = floatval($p['amount']);
            $is_captain = (strtolower($email) === strtolower($group['captain_email'])) ? 1 : 0;

            // Compte WP
            $user_id = email_exists($email);
            $password_generated = '';
            if (!$user_id) {
                $password_generated = wp_generate_password(12, true, false);
                $user_id = wp_create_user($email, $password_generated, $email);
                if (is_wp_error($user_id)) {
                    error_log(sprintf('[VS08SP] Erreur création compte %s : %s', $email, $user_id->get_error_message()));
                    continue;
                }
                wp_update_user([
                    'ID'           => $user_id,
                    'first_name'   => $prenom,
                    'last_name'    => $nom,
                    'display_name' => $name ?: $email,
                    'role'         => 'customer',
                ]);
            }

            // Share dans notre table custom (PAS de produit WC ici)
            $share_id = VS08SP_DB::create_share([
                'group_id'   => $group_id,
                'email'      => $email,
                'name'       => $name,
                'amount'     => $amount,
                'is_captain' => $is_captain,
            ]);

            if ($share_id) {
                // Relire pour avoir le token auto-généré
                $all_shares = VS08SP_DB::get_shares($group_id);
                $token = '';
                foreach ($all_shares as $s) {
                    if (intval($s['id']) === $share_id) { $token = $s['token']; break; }
                }

                $created_shares[] = [
                    'share_id'   => $share_id,
                    'email'      => $email,
                    'name'       => $name,
                    'amount'     => $amount,
                    'is_captain' => $is_captain,
                    'user_id'    => $user_id,
                    'password'   => $password_generated,
                    'token'      => $token,
                ];
            }
        }

        // ── Mettre à jour le groupe → "pending" ──
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vs08sp_groups',
            ['status' => 'pending', 'nb_participants' => count($created_shares), 'min_share' => $min_share],
            ['id' => $group_id],
            ['%s', '%d', '%f'],
            ['%d']
        );

        // ── Envoyer les emails ──
        foreach ($created_shares as $cs) {
            $payment_url = VS08SP_DB::get_payment_url($cs['token']);
            if ($cs['is_captain']) {
                VS08SP_Emails::send_captain_group_configured($group, $cs, $created_shares);
            } else {
                VS08SP_Emails::send_participant_invitation($group, $cs, $payment_url, $cs['password']);
            }
        }

        error_log(sprintf('[VS08SP] Groupe #%d configuré — %d participants', $group_id, count($created_shares)));

        return new WP_REST_Response([
            'success'  => true,
            'group_id' => $group_id,
            'shares'   => array_map(function($cs) {
                return [
                    'email'      => $cs['email'],
                    'name'       => $cs['name'],
                    'amount'     => $cs['amount'],
                    'is_captain' => (bool) $cs['is_captain'],
                    'url'        => VS08SP_DB::get_payment_url($cs['token']),
                ];
            }, $created_shares),
            'message'  => 'Groupe configuré ! Les liens de paiement ont été envoyés.',
        ], 200);
    }

    /* ══════════════════════════════════════════
     *  RÉCUPÉRER UN GROUPE
     * ══════════════════════════════════════════ */
    public static function handle_get_group(WP_REST_Request $request) {
        $group_id = intval($request->get_param('id'));
        $group = VS08SP_DB::get_group($group_id);

        if (!$group) {
            return new WP_REST_Response(['success' => false, 'message' => 'Groupe introuvable.'], 404);
        }

        $shares = VS08SP_DB::get_shares($group_id);
        $progress = VS08SP_DB::get_payment_progress($group_id);

        return new WP_REST_Response([
            'success' => true,
            'group'   => [
                'id'              => $group['id'],
                'voyage_titre'    => $group['voyage_titre'],
                'total_amount'    => floatval($group['total_amount']),
                'status'          => $group['status'],
                'expires_at'      => $group['expires_at'],
                'nb_participants' => intval($group['nb_participants']),
            ],
            'shares'   => array_map(function($s) {
                return [
                    'email'      => $s['email'],
                    'name'       => $s['name'],
                    'amount'     => floatval($s['amount']),
                    'is_captain' => (bool) $s['is_captain'],
                    'status'     => $s['status'],
                    'paid_at'    => $s['paid_at'],
                    'url'        => VS08SP_DB::get_payment_url($s['token']),
                ];
            }, $shares),
            'progress' => $progress,
        ], 200);
    }

    public static function calculate_min_share(float $total, int $nb_participants, float $prix_vol_pp = 0): float {
        $equal_share = $total / max($nb_participants, 1);
        return (float) ceil(max($equal_share * 0.30, $prix_vol_pp));
    }
}
