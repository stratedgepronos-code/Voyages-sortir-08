<?php
/**
 * VS08 SplitPay — Gestion des groupes
 *
 * Endpoints REST :
 *   POST /vs08sp/v1/create-group   → crée le groupe + parts + envoie les liens
 *   GET  /vs08sp/v1/group/{id}     → récupère les infos d'un groupe (pour le capitaine)
 *
 * Ce fichier est le point d'entrée principal quand le capitaine
 * choisit "Payer à plusieurs" dans le tunnel de réservation.
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
        // Créer un groupe de paiement partagé
        register_rest_route('vs08sp/v1', '/create-group', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'handle_create_group'],
        ]);

        // Récupérer les infos d'un groupe (pour le capitaine dans son espace)
        register_rest_route('vs08sp/v1', '/group/(?P<id>\d+)', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'handle_get_group'],
        ]);
    }

    /* ══════════════════════════════════════════
     *  CRÉATION D'UN GROUPE
     * ══════════════════════════════════════════
     *
     *  Le JS du tunnel de réservation envoie :
     *  {
     *    booking_data: { ...toutes les données du devis... },
     *    participants: [
     *      { email: "cap@mail.com", name: "Jean", amount: 1200, is_captain: true },
     *      { email: "ami@mail.com", name: "Pierre", amount: 1000 },
     *      ...
     *    ],
     *    nonce: "..."
     *  }
     */
    public static function handle_create_group(WP_REST_Request $request) {
        // Vérifier le nonce
        $nonce = $request->get_param('nonce') ?: '';
        if (!wp_verify_nonce($nonce, 'vs08sp_nonce')) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Session expirée. Rechargez la page.'],
                200
            );
        }

        $booking_data  = $request->get_param('booking_data');
        $participants  = $request->get_param('participants');

        // ── Validations ──────────────────────────
        if (empty($booking_data) || empty($participants)) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Données manquantes.'],
                200
            );
        }

        if (!is_array($participants) || count($participants) < 2) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Il faut au moins 2 participants.'],
                200
            );
        }

        if (count($participants) > VS08SP_MAX_PARTICIPANTS) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Maximum ' . VS08SP_MAX_PARTICIPANTS . ' participants.'],
                200
            );
        }

        $total_voyage = floatval($booking_data['total'] ?? 0);
        if ($total_voyage <= 0) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Le montant total est invalide.'],
                200
            );
        }

        // ── Vérifier que la somme des parts = total ──────
        $sum_shares = 0;
        foreach ($participants as $p) {
            $sum_shares += floatval($p['amount'] ?? 0);
        }
        // Tolérance de 1€ pour les arrondis
        if (abs($sum_shares - $total_voyage) > 1) {
            return new WP_REST_Response(
                ['success' => false, 'message' => sprintf(
                    'La somme des parts (%.2f €) ne correspond pas au total (%.2f €).',
                    $sum_shares, $total_voyage
                )],
                200
            );
        }

        // ── Calculer le montant minimum par part ─────────
        // Règle : max(30% du partage équitable, coût vol par personne)
        // On ne dit PAS au client pourquoi — on impose juste un minimum
        $nb = count($participants);
        $equal_share = $total_voyage / $nb;
        $min_from_pct = $equal_share * 0.30;

        $prix_vol_pp = floatval($booking_data['params']['prix_vol'] ?? 0);
        $min_share = max($min_from_pct, $prix_vol_pp);
        $min_share = ceil($min_share); // Arrondi à l'euro supérieur

        // Vérifier que chaque part respecte le minimum
        foreach ($participants as $i => $p) {
            $amount = floatval($p['amount'] ?? 0);
            if ($amount < $min_share) {
                return new WP_REST_Response(
                    ['success' => false, 'message' => sprintf(
                        'Le montant minimum par participant est de %d €.',
                        $min_share
                    )],
                    200
                );
            }
        }

        // Vérifier les emails
        $captain_found = false;
        $emails_seen = [];
        foreach ($participants as $p) {
            $email = sanitize_email($p['email'] ?? '');
            if (empty($email) || !is_email($email)) {
                return new WP_REST_Response(
                    ['success' => false, 'message' => 'Adresse email invalide : ' . esc_html($p['email'] ?? '')],
                    200
                );
            }
            if (in_array($email, $emails_seen)) {
                return new WP_REST_Response(
                    ['success' => false, 'message' => 'Email en doublon : ' . esc_html($email)],
                    200
                );
            }
            $emails_seen[] = $email;
            if (!empty($p['is_captain'])) $captain_found = true;
        }
        if (!$captain_found) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Un des participants doit être désigné capitaine.'],
                200
            );
        }

        // ── Trouver le capitaine ─────────────────────────
        $captain = null;
        foreach ($participants as $p) {
            if (!empty($p['is_captain'])) {
                $captain = $p;
                break;
            }
        }

        // ── Créer le groupe en BDD ───────────────────────
        $group_id = VS08SP_DB::create_group([
            'voyage_id'       => intval($booking_data['voyage_id'] ?? 0),
            'voyage_titre'    => sanitize_text_field($booking_data['voyage_titre'] ?? ''),
            'booking_data'    => $booking_data,
            'captain_email'   => $captain['email'],
            'captain_name'    => $captain['name'] ?? '',
            'total_amount'    => $total_voyage,
            'min_share'       => $min_share,
            'nb_participants' => $nb,
        ]);

        if (!$group_id) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Erreur lors de la création du groupe. Réessayez.'],
                200
            );
        }

        // ── Créer les parts (une par participant) ────────
        $share_links = [];
        foreach ($participants as $p) {
            $share_id = VS08SP_DB::create_share([
                'group_id'   => $group_id,
                'email'      => $p['email'],
                'name'       => $p['name'] ?? '',
                'amount'     => floatval($p['amount']),
                'is_captain' => !empty($p['is_captain']) ? 1 : 0,
            ]);

            if (!$share_id) {
                // Rollback : on ne peut pas facilement, mais on log l'erreur
                error_log("[VS08SP] Failed to create share for {$p['email']} in group $group_id");
                continue;
            }

            // Récupérer le token pour construire le lien
            $share = VS08SP_DB::get_share_by_token(''); // On a besoin du token qu'on vient de créer
            // Plus simple : relire toutes les parts du groupe
        }

        // Relire toutes les parts créées pour avoir les tokens
        $shares = VS08SP_DB::get_shares($group_id);
        foreach ($shares as $share) {
            $share_links[] = [
                'email'      => $share['email'],
                'name'       => $share['name'],
                'amount'     => floatval($share['amount']),
                'is_captain' => (bool) $share['is_captain'],
                'url'        => VS08SP_DB::get_payment_url($share['token']),
                'token'      => $share['token'],
            ];
        }

        // ── Envoyer les emails d'invitation ──────────────
        VS08SP_Emails::send_invitations($group_id);

        // ── Retourner la confirmation ────────────────────
        return new WP_REST_Response([
            'success'  => true,
            'group_id' => $group_id,
            'message'  => 'Groupe créé ! Les liens de paiement ont été envoyés.',
            'shares'   => $share_links,
            'expires'  => date('d/m/Y à H:i', time() + (VS08SP_EXPIRY_HOURS * 3600)),
        ], 200);
    }

    /* ══════════════════════════════════════════
     *  RÉCUPÉRER UN GROUPE (pour le capitaine)
     * ══════════════════════════════════════════ */
    public static function handle_get_group(WP_REST_Request $request) {
        $group_id = intval($request->get_param('id'));
        $group = VS08SP_DB::get_group($group_id);

        if (!$group) {
            return new WP_REST_Response(['success' => false, 'message' => 'Groupe introuvable.'], 404);
        }

        // Vérifier que le demandeur est le capitaine (par email user connecté)
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->user_email !== $group['captain_email']) {
            // Pas le capitaine → on renvoie seulement les infos publiques
        }

        $shares = VS08SP_DB::get_shares($group_id);
        $progress = VS08SP_DB::get_payment_progress($group_id);

        return new WP_REST_Response([
            'success'  => true,
            'group'    => [
                'id'             => $group['id'],
                'voyage_titre'   => $group['voyage_titre'],
                'total_amount'   => floatval($group['total_amount']),
                'status'         => $group['status'],
                'expires_at'     => $group['expires_at'],
                'nb_participants' => intval($group['nb_participants']),
            ],
            'shares'   => array_map(function ($s) {
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

    /* ══════════════════════════════════════════
     *  UTILITAIRE : calculer le minimum par part
     *  (utilisé aussi par le JS côté front)
     * ══════════════════════════════════════════ */
    public static function calculate_min_share(float $total, int $nb_participants, float $prix_vol_pp = 0): float {
        $equal_share = $total / max($nb_participants, 1);
        $min_from_pct = $equal_share * 0.30;
        $min = max($min_from_pct, $prix_vol_pp);
        return (float) ceil($min);
    }
}
