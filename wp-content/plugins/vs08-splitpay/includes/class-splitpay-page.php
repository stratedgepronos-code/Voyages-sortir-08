<?php
/**
 * VS08 SplitPay — Page de paiement publique
 *
 * URL : /paiement-groupe/{token}
 *
 * Quand un participant clique sur son lien, il arrive ici.
 * La page affiche :
 *   1. Le récap complet du voyage (dates, hôtel, vols, etc.)
 *   2. Son montant à payer
 *   3. La barre de progression du groupe
 *   4. Un bouton "Payer ma part" qui crée un produit WooCommerce + redirige vers le checkout
 */
if (!defined('ABSPATH')) exit;

class VS08SP_Page {

    public static function init() {
        // Enregistrer la route personnalisée /paiement-groupe/{token}
        add_action('init', [__CLASS__, 'register_rewrite']);
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        add_action('template_redirect', [__CLASS__, 'handle_page']);

        // Endpoint REST pour déclencher le paiement d'une part
        add_action('rest_api_init', [__CLASS__, 'register_pay_route']);
    }

    /* ── Rewrite rules ────────────────────────── */
    public static function register_rewrite() {
        add_rewrite_rule(
            '^paiement-groupe/([a-f0-9]{32,64})/?$',
            'index.php?vs08sp_token=$matches[1]',
            'top'
        );
    }

    public static function add_query_vars($vars) {
        $vars[] = 'vs08sp_token';
        return $vars;
    }

    /* ── Route REST pour "Payer ma part" ──────── */
    public static function register_pay_route() {
        register_rest_route('vs08sp/v1', '/pay', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'handle_pay'],
        ]);
    }

    /* ══════════════════════════════════════════
     *  AFFICHAGE DE LA PAGE PARTICIPANT
     * ══════════════════════════════════════════ */
    public static function handle_page() {
        $token = get_query_var('vs08sp_token');
        if (empty($token)) return;

        // Récupérer la part
        $share = VS08SP_DB::get_share_by_token($token);
        if (!$share) {
            self::render_error('Lien de paiement introuvable', 'Ce lien n\'existe pas ou a expiré.');
            exit;
        }

        // Récupérer le groupe
        $group = VS08SP_DB::get_group(intval($share['group_id']));
        if (!$group) {
            self::render_error('Groupe introuvable', 'Une erreur est survenue.');
            exit;
        }

        // Vérifier l'expiration
        if ($group['status'] === 'expired') {
            self::render_error('Délai dépassé', 'Le délai de paiement de ' . VS08SP_EXPIRY_HOURS . 'h est écoulé. Contactez l\'organisateur du voyage.');
            exit;
        }

        if ($group['status'] === 'cancelled') {
            self::render_error('Groupe annulé', 'Ce paiement groupé a été annulé.');
            exit;
        }

        // Déjà payé ?
        if ($share['status'] === 'paid') {
            self::render_already_paid($share, $group);
            exit;
        }

        // Tout est bon : afficher la page de paiement
        $progress = VS08SP_DB::get_payment_progress(intval($group['id']));
        $booking = $group['booking_data'];

        self::render_payment_page($share, $group, $progress, $booking);
        exit;
    }

    /* ══════════════════════════════════════════
     *  PAIEMENT D'UNE PART
     * ══════════════════════════════════════════
     *
     *  Le participant clique "Payer ma part".
     *  On crée un produit WooCommerce temporaire avec son montant,
     *  on l'ajoute au panier, et on redirige vers le checkout.
     *  Paybox prend le relais normalement.
     */
    public static function handle_pay(WP_REST_Request $request) {
        $token = sanitize_text_field($request->get_param('token') ?? '');
        $nonce = $request->get_param('nonce') ?? '';

        if (!wp_verify_nonce($nonce, 'vs08sp_pay_' . $token)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Session expirée.'], 200);
        }

        $share = VS08SP_DB::get_share_by_token($token);
        if (!$share) {
            return new WP_REST_Response(['success' => false, 'message' => 'Lien invalide.'], 200);
        }

        if ($share['status'] === 'paid') {
            return new WP_REST_Response(['success' => false, 'message' => 'Cette part est déjà payée.'], 200);
        }

        $group = VS08SP_DB::get_group(intval($share['group_id']));
        if (!$group || !in_array($group['status'], ['pending'])) {
            return new WP_REST_Response(['success' => false, 'message' => 'Ce groupe n\'est plus actif.'], 200);
        }

        // Vérifier expiration
        if (strtotime($group['expires_at']) < time()) {
            VS08SP_DB::update_group_status(intval($group['id']), 'expired');
            return new WP_REST_Response(['success' => false, 'message' => 'Le délai de 48h est dépassé.'], 200);
        }

        // ── Créer un produit WooCommerce temporaire ──
        $amount = floatval($share['amount']);
        $product_name = sprintf(
            'Paiement groupé — %s — Part de %s',
            $group['voyage_titre'],
            $share['name'] ?: $share['email']
        );

        $product = new WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_price($amount);
        $product->set_regular_price($amount);
        $product->set_status('private');
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');
        $product->set_short_description($product_name);

        // Ajouter le récap comme description
        $desc = self::build_share_description($share, $group);
        $product->set_description($desc);

        $product_id = $product->save();

        if (!$product_id) {
            return new WP_REST_Response(['success' => false, 'message' => 'Erreur de création du produit.'], 200);
        }

        // Stocker les références splitpay sur le produit
        update_post_meta($product_id, '_vs08sp_share_id', intval($share['id']));
        update_post_meta($product_id, '_vs08sp_group_id', intval($share['group_id']));
        update_post_meta($product_id, '_vs08sp_token', $token);
        // Stocker aussi les booking_data pour le recap au checkout
        update_post_meta($product_id, '_vs08v_booking_data', $group['booking_data']);

        // ── Ajouter au panier + rediriger vers le checkout ──
        $cart_token = wp_generate_password(32, false);
        set_transient('vs08_cart_' . $cart_token, $product_id, 900);

        // Tenter l'ajout au panier classique
        try {
            if (function_exists('WC') && WC()) {
                if (is_null(WC()->session)) WC()->initialize_session();
                if (is_null(WC()->cart)) {
                    if (function_exists('wc_load_cart')) wc_load_cart();
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
                    }
                }
            }
        } catch (\Throwable $e) {
            // Le transient est le fallback
        }

        $checkout_url = wc_get_checkout_url();
        $checkout_url = add_query_arg('vs08_cart', $cart_token, $checkout_url);

        return new WP_REST_Response([
            'success'  => true,
            'redirect' => $checkout_url,
        ], 200);
    }

    /* ══════════════════════════════════════════
     *  TEMPLATES DE RENDU
     * ══════════════════════════════════════════ */

    /**
     * Page de paiement complète pour le participant.
     */
    private static function render_payment_page(array $share, array $group, array $progress, $booking) {
        $amount   = floatval($share['amount']);
        $total    = floatval($group['total_amount']);
        $pct      = $total > 0 ? round(($progress['amount_paid'] / $total) * 100) : 0;
        $params   = $booking['params'] ?? [];
        $devis    = $booking['devis'] ?? [];
        $voyage_id = intval($group['voyage_id']);

        // Récupérer les meta du voyage pour l'hôtel
        $m = [];
        if ($voyage_id && function_exists('get_post_meta')) {
            if (class_exists('VS08V_MetaBoxes')) {
                $m = VS08V_MetaBoxes::get($voyage_id);
            }
        }

        $pension_labels = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus'];
        $hotel_nom     = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
        $hotel_etoiles = $m['hotel_etoiles'] ?? ($m['hotel']['etoiles'] ?? '');
        $pension_code  = $m['pension'] ?? ($m['hotel']['pension'] ?? '');
        $pension_label = $pension_labels[$pension_code] ?? '';
        $duree         = $m['duree'] ?? '';

        $nonce = wp_create_nonce('vs08sp_pay_' . $share['token']);

        // Charger le header WordPress
        get_header();
        ?>
        <div class="vs08sp-page-wrapper">
            <div class="vs08sp-page-container">

                <!-- ── En-tête ── -->
                <div class="vs08sp-header">
                    <div class="vs08sp-badge">🏌️ Paiement Groupé</div>
                    <h1 class="vs08sp-title"><?php echo esc_html($group['voyage_titre']); ?></h1>
                    <p class="vs08sp-subtitle">
                        Bonjour <strong><?php echo esc_html($share['name'] ?: $share['email']); ?></strong>,
                        voici votre part du voyage organisé par <strong><?php echo esc_html($group['captain_name']); ?></strong>.
                    </p>
                </div>

                <!-- ── Barre de progression ── -->
                <div class="vs08sp-progress-section">
                    <div class="vs08sp-progress-label">
                        <span><?php echo $progress['paid']; ?>/<?php echo $progress['total']; ?> participants ont payé</span>
                        <span class="vs08sp-progress-pct"><?php echo $pct; ?>%</span>
                    </div>
                    <div class="vs08sp-progress-bar">
                        <div class="vs08sp-progress-fill" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                    <p class="vs08sp-deadline">
                        ⏰ Date limite : <strong><?php echo date('d/m/Y à H\hi', strtotime($group['expires_at'])); ?></strong>
                    </p>
                </div>

                <!-- ── Récap voyage ── -->
                <div class="vs08sp-recap-card">
                    <h3>📋 Récapitulatif du voyage</h3>
                    <table class="vs08sp-recap-table">
                        <tr><td>Destination</td><td><?php echo esc_html($group['voyage_titre']); ?></td></tr>
                        <?php if (!empty($params['date_depart'])): ?>
                        <tr><td>Date de départ</td><td><?php echo esc_html(date('d/m/Y', strtotime($params['date_depart']))); ?></td></tr>
                        <?php endif; ?>
                        <?php if ($duree): ?>
                        <tr><td>Durée</td><td><?php echo esc_html($duree); ?> nuits</td></tr>
                        <?php endif; ?>
                        <?php if (!empty($params['aeroport'])): ?>
                        <tr><td>Aéroport</td><td><?php echo esc_html(strtoupper($params['aeroport'])); ?></td></tr>
                        <?php endif; ?>
                        <?php if ($hotel_nom): ?>
                        <tr><td>Hôtel</td><td><?php echo esc_html($hotel_nom); ?> <?php if ($hotel_etoiles) echo str_repeat('★', intval($hotel_etoiles)); ?></td></tr>
                        <?php endif; ?>
                        <?php if ($pension_label): ?>
                        <tr><td>Formule</td><td><?php echo esc_html($pension_label); ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($params['vol_aller_num'])): ?>
                        <tr><td>✈️ Vol aller</td><td><?php echo esc_html($params['vol_aller_num']); ?> — <?php echo esc_html($params['vol_aller_depart']); ?> → <?php echo esc_html($params['vol_aller_arrivee']); ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($params['vol_retour_num'])): ?>
                        <tr><td>✈️ Vol retour</td><td><?php echo esc_html($params['vol_retour_num']); ?> — <?php echo esc_html($params['vol_retour_depart']); ?> → <?php echo esc_html($params['vol_retour_arrivee']); ?></td></tr>
                        <?php endif; ?>
                        <tr><td>Voyageurs</td><td><?php echo esc_html($devis['nb_total'] ?? $group['nb_participants']); ?> personne(s)</td></tr>
                        <tr class="vs08sp-total-row"><td>Total du voyage</td><td><?php echo number_format($total, 2, ',', ' '); ?> €</td></tr>
                    </table>
                </div>

                <!-- ── Montant à payer ── -->
                <div class="vs08sp-amount-card">
                    <div class="vs08sp-amount-label">Votre part à régler</div>
                    <div class="vs08sp-amount-value"><?php echo number_format($amount, 2, ',', ' '); ?> €</div>
                    <div class="vs08sp-amount-detail">
                        sur un total de <?php echo number_format($total, 2, ',', ' '); ?> € réparti entre <?php echo $group['nb_participants']; ?> participants
                    </div>
                </div>

                <!-- ── Bouton payer ── -->
                <div class="vs08sp-pay-section">
                    <button type="button" class="vs08sp-pay-btn" id="vs08sp-pay-btn"
                            data-token="<?php echo esc_attr($share['token']); ?>"
                            data-nonce="<?php echo esc_attr($nonce); ?>"
                            data-rest-url="<?php echo esc_url(rest_url('vs08sp/v1/pay')); ?>">
                        🔒 Payer ma part — <?php echo number_format($amount, 0, ',', ' '); ?> €
                    </button>
                    <p class="vs08sp-secure-note">
                        Paiement 100% sécurisé par carte bancaire
                    </p>
                </div>

            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('vs08sp-pay-btn');
            if (!btn) return;
            btn.addEventListener('click', function() {
                btn.disabled = true;
                btn.textContent = 'Redirection vers le paiement...';
                fetch(btn.dataset.restUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        token: btn.dataset.token,
                        nonce: btn.dataset.nonce
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Erreur lors du paiement.');
                        btn.disabled = false;
                        btn.textContent = '🔒 Payer ma part — <?php echo number_format($amount, 0, ',', ' '); ?> €';
                    }
                })
                .catch(function() {
                    alert('Erreur de connexion. Réessayez.');
                    btn.disabled = false;
                    btn.textContent = '🔒 Payer ma part — <?php echo number_format($amount, 0, ',', ' '); ?> €';
                });
            });
        });
        </script>
        <?php
        get_footer();
    }

    /**
     * Page "Déjà payé".
     */
    private static function render_already_paid(array $share, array $group) {
        get_header();
        ?>
        <div class="vs08sp-page-wrapper">
            <div class="vs08sp-page-container">
                <div class="vs08sp-success-card">
                    <div class="vs08sp-success-icon">✅</div>
                    <h1>Paiement reçu !</h1>
                    <p>Votre part de <strong><?php echo number_format(floatval($share['amount']), 2, ',', ' '); ?> €</strong>
                       pour le voyage <strong><?php echo esc_html($group['voyage_titre']); ?></strong>
                       a bien été réglée le <?php echo date('d/m/Y à H\hi', strtotime($share['paid_at'])); ?>.</p>
                    <?php
                    $progress = VS08SP_DB::get_payment_progress(intval($group['id']));
                    $pct = $group['total_amount'] > 0 ? round(($progress['amount_paid'] / floatval($group['total_amount'])) * 100) : 0;
                    ?>
                    <div class="vs08sp-progress-section">
                        <div class="vs08sp-progress-label">
                            <span><?php echo $progress['paid']; ?>/<?php echo $progress['total']; ?> participants ont payé</span>
                            <span class="vs08sp-progress-pct"><?php echo $pct; ?>%</span>
                        </div>
                        <div class="vs08sp-progress-bar">
                            <div class="vs08sp-progress-fill" style="width: <?php echo $pct; ?>%"></div>
                        </div>
                    </div>
                    <?php if ($progress['paid'] === $progress['total']): ?>
                        <p class="vs08sp-complete-msg">🎉 Tous les participants ont payé — le voyage est confirmé !</p>
                    <?php else: ?>
                        <p>En attente des paiements restants. Le voyage sera confirmé quand 100% sera atteint.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }

    /**
     * Page d'erreur générique.
     */
    private static function render_error(string $title, string $message) {
        get_header();
        ?>
        <div class="vs08sp-page-wrapper">
            <div class="vs08sp-page-container">
                <div class="vs08sp-error-card">
                    <div class="vs08sp-error-icon">⚠️</div>
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><?php echo esc_html($message); ?></p>
                    <a href="<?php echo home_url(); ?>" class="vs08sp-back-btn">Retour à l'accueil</a>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }

    /**
     * Description WooCommerce du produit de paiement partiel.
     */
    private static function build_share_description(array $share, array $group): string {
        $booking = $group['booking_data'] ?? [];
        $params = $booking['params'] ?? [];
        ob_start();
        ?>
        <div class="vs08v-woo-recap">
            <h3>📋 Paiement groupé — Part de <?php echo esc_html($share['name'] ?: $share['email']); ?></h3>
            <table>
                <tr><td><strong>Voyage</strong></td><td><?php echo esc_html($group['voyage_titre']); ?></td></tr>
                <?php if (!empty($params['date_depart'])): ?>
                <tr><td><strong>Date de départ</strong></td><td><?php echo esc_html(date('d/m/Y', strtotime($params['date_depart']))); ?></td></tr>
                <?php endif; ?>
                <tr><td><strong>Groupe</strong></td><td><?php echo intval($group['nb_participants']); ?> participants</td></tr>
                <tr><td><strong>Organisateur</strong></td><td><?php echo esc_html($group['captain_name']); ?></td></tr>
                <tr style="font-weight:bold;border-top:2px solid #333">
                    <td>Votre part</td>
                    <td><?php echo number_format(floatval($share['amount']), 2, ',', ' '); ?> €</td>
                </tr>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
