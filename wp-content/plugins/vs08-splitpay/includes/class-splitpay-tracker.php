<?php
/**
 * VS08 SplitPay — Suivi des paiements
 *
 * Ce fichier fait le lien entre WooCommerce (Paybox) et notre système de parts.
 * 
 * Quand un participant paye via Paybox :
 *   1. WooCommerce déclenche le hook `woocommerce_payment_complete`
 *   2. On vérifie si la commande correspond à une part splitpay
 *   3. Si oui, on marque la part comme payée
 *   4. On vérifie si toutes les parts sont payées
 *   5. Si 100% → on déclenche la validation du voyage (emails, contrat, etc.)
 *
 * Ce fichier gère aussi la barre de progression dans l'espace voyageur du capitaine.
 */
if (!defined('ABSPATH')) exit;

class VS08SP_Tracker {

    public static function init() {
        // Écouter les paiements WooCommerce
        add_action('woocommerce_payment_complete', [__CLASS__, 'on_payment_complete'], 20);

        // Écouter aussi les changements de statut (pour le paiement par virement/chèque)
        add_action('woocommerce_order_status_processing', [__CLASS__, 'on_payment_complete'], 20);
        add_action('woocommerce_order_status_completed', [__CLASS__, 'on_payment_complete'], 20);

        // Ajouter la section "Paiement groupé" dans l'espace voyageur du capitaine
        add_action('vs08_espace_voyageur_after_panels', [__CLASS__, 'render_captain_dashboard'], 10, 2);

        // Masquer les produits splitpay du catalogue WooCommerce
        add_filter('woocommerce_product_query_meta_query', [__CLASS__, 'hide_splitpay_products']);
    }

    /* ══════════════════════════════════════════
     *  RÉCEPTION D'UN PAIEMENT
     * ══════════════════════════════════════════ */
    public static function on_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Déjà traité ?
        if ($order->get_meta('_vs08sp_payment_processed')) return;

        // Chercher si cette commande correspond à une part splitpay
        $share_id = 0;
        $group_id = 0;
        $token    = '';

        // Vérifier d'abord dans les meta de la commande
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if (!$product_id) continue;

            $sid = get_post_meta($product_id, '_vs08sp_share_id', true);
            if ($sid) {
                $share_id = intval($sid);
                $group_id = intval(get_post_meta($product_id, '_vs08sp_group_id', true));
                $token    = get_post_meta($product_id, '_vs08sp_token', true);
                break;
            }
        }

        // Pas une commande splitpay → on ne fait rien
        if (!$share_id || !$group_id) return;

        // ── Marquer la part comme payée ──────────────
        VS08SP_DB::mark_share_paid($share_id, $order_id);

        // Sauver les refs sur la commande WooCommerce aussi
        $order->update_meta_data('_vs08sp_share_id', $share_id);
        $order->update_meta_data('_vs08sp_group_id', $group_id);
        $order->update_meta_data('_vs08sp_token', $token);
        $order->update_meta_data('_vs08sp_payment_processed', current_time('mysql'));
        $order->save();

        // Log
        error_log(sprintf(
            '[VS08SP] Part #%d payée (commande #%d, groupe #%d)',
            $share_id, $order_id, $group_id
        ));

        // ── Envoyer la confirmation au participant ───
        $share = VS08SP_DB::get_share_by_token($token);
        $group = VS08SP_DB::get_group($group_id);
        if ($share && $group) {
            VS08SP_Emails::send_payment_confirmation($share, $group);
        }

        // ── Vérifier si le groupe est complet ────────
        if (VS08SP_DB::is_group_fully_paid($group_id)) {
            self::complete_group($group_id);
        } else {
            // Notifier le capitaine de la progression
            $progress = VS08SP_DB::get_payment_progress($group_id);
            if ($group) {
                VS08SP_Emails::send_captain_progress($group, $progress, $share);
            }
        }
    }

    /* ══════════════════════════════════════════
     *  VALIDATION DU GROUPE (100% PAYÉ)
     * ══════════════════════════════════════════
     *
     *  Quand toutes les parts sont payées :
     *  1. Le groupe passe en statut "complete"
     *  2. On crée la commande parent "globale" (pour les dossiers admin)
     *  3. On déclenche les emails de confirmation voyage
     *  4. Le contrat PDF est généré
     *  5. Le voyage apparaît dans l'espace voyageur
     */
    private static function complete_group(int $group_id) {
        $group = VS08SP_DB::get_group($group_id);
        if (!$group) return;

        // Mettre à jour le statut
        VS08SP_DB::update_group_status($group_id, 'complete');

        error_log(sprintf('[VS08SP] Groupe #%d COMPLET — tous les participants ont payé !', $group_id));

        $booking_data = $group['booking_data'];
        if (!is_array($booking_data)) return;

        // ── Créer la commande parent dans WooCommerce ──
        // Cette commande regroupe tout : montant total, infos voyage, voyageurs
        // Elle déclenche le flux normal VS08 (emails, contrat, espace voyageur)
        try {
            $parent_order = wc_create_order([
                'status' => 'processing',
            ]);

            if (is_wp_error($parent_order)) {
                error_log('[VS08SP] Erreur création commande parent : ' . $parent_order->get_error_message());
                return;
            }

            // Créer un produit récapitulatif
            $product = new WC_Product_Simple();
            $product->set_name(sprintf('Voyage groupé — %s — %d participants', $group['voyage_titre'], $group['nb_participants']));
            $product->set_price(floatval($group['total_amount']));
            $product->set_regular_price(floatval($group['total_amount']));
            $product->set_status('private');
            $product->set_virtual(true);
            $product_id = $product->save();

            // Stocker TOUTES les booking_data sur le produit (pour l'espace voyageur)
            update_post_meta($product_id, '_vs08v_booking_data', $booking_data);
            update_post_meta($product_id, '_vs08v_voyage_id', intval($group['voyage_id']));
            update_post_meta($product_id, '_vs08sp_parent_group', $group_id);

            // Ajouter le produit à la commande
            $parent_order->add_product($product, 1);

            // Renseigner la facturation depuis le capitaine
            $fact = $booking_data['facturation'] ?? [];
            $parent_order->set_billing_first_name($fact['prenom'] ?? '');
            $parent_order->set_billing_last_name($fact['nom'] ?? '');
            $parent_order->set_billing_email($group['captain_email']);
            $parent_order->set_billing_phone($fact['tel'] ?? '');
            $parent_order->set_billing_address_1($fact['adresse'] ?? '');
            $parent_order->set_billing_postcode($fact['cp'] ?? '');
            $parent_order->set_billing_city($fact['ville'] ?? '');
            $parent_order->set_billing_country('FR');

            // Stocker les références splitpay
            $parent_order->update_meta_data('_vs08sp_group_id', $group_id);
            $parent_order->update_meta_data('_vs08sp_is_parent', true);
            $parent_order->update_meta_data('_vs08v_booking_data', $booking_data);
            $parent_order->update_meta_data('_vs08v_voyage_id', intval($group['voyage_id']));
            $parent_order->update_meta_data('_vs08v_total_voyage', floatval($group['total_amount']));
            $parent_order->update_meta_data('_vs08v_payer_tout', true);

            // Lister les sous-commandes (parts) comme note
            $shares = VS08SP_DB::get_shares($group_id);
            $shares_note = "Paiement groupé — " . count($shares) . " participants :\n";
            foreach ($shares as $s) {
                $shares_note .= sprintf(
                    "• %s (%s) — %s € — Commande #%d\n",
                    $s['name'], $s['email'],
                    number_format(floatval($s['amount']), 2, ',', ' '),
                    intval($s['order_id'])
                );
            }
            $parent_order->add_order_note($shares_note);

            $parent_order->calculate_totals();
            $parent_order->save();

            $parent_order_id = $parent_order->get_id();

            error_log(sprintf('[VS08SP] Commande parent #%d créée pour le groupe #%d', $parent_order_id, $group_id));

            // ── Déclencher le flux normal VS08 ──────────
            // Les emails + contrat sont gérés par VS08V_Emails::dispatch()
            if (class_exists('VS08V_Emails')) {
                VS08V_Emails::dispatch($parent_order_id);
            }

            // Email spécifique splitpay au capitaine
            VS08SP_Emails::send_group_complete($group);

        } catch (\Throwable $e) {
            error_log('[VS08SP] ERREUR complete_group : ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /* ══════════════════════════════════════════
     *  DASHBOARD CAPITAINE (espace voyageur)
     * ══════════════════════════════════════════ */
    public static function render_captain_dashboard($current_user = null, $voyage_orders = []) {
        if (!$current_user) $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->user_email) return;

        $groups = VS08SP_DB::get_groups_by_captain($current_user->user_email);
        if (empty($groups)) return;

        ?>
        <div class="vs08sp-captain-section" style="margin-top: 40px;">
            <h2 style="font-family: 'Playfair Display', serif; color: #0b1120; margin-bottom: 20px;">
                👥 Mes paiements groupés
            </h2>

            <?php foreach ($groups as $group):
                $progress = VS08SP_DB::get_payment_progress(intval($group['id']));
                $pct = $group['total_amount'] > 0
                    ? round(($progress['amount_paid'] / floatval($group['total_amount'])) * 100)
                    : 0;
                $shares = VS08SP_DB::get_shares(intval($group['id']));
                $status_labels = [
                    'pending'   => '⏳ En attente',
                    'complete'  => '✅ Validé',
                    'expired'   => '⌛ Expiré',
                    'cancelled' => '❌ Annulé',
                ];
                $status_label = $status_labels[$group['status']] ?? $group['status'];
            ?>
            <div class="vs08sp-captain-card" style="background: #f8f5f0; border-radius: 12px; padding: 24px; margin-bottom: 20px; border-left: 4px solid <?php echo $group['status'] === 'complete' ? '#59b7b7' : ($group['status'] === 'expired' ? '#e8734a' : '#c8a45e'); ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; font-family: 'Playfair Display', serif;">
                        <?php echo esc_html($group['voyage_titre']); ?>
                    </h3>
                    <span style="font-size: 14px; font-weight: 600;">
                        <?php echo $status_label; ?>
                    </span>
                </div>

                <!-- Barre de progression -->
                <div class="vs08sp-progress-section" style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 6px;">
                        <span><?php echo $progress['paid']; ?>/<?php echo $progress['total']; ?> ont payé</span>
                        <span style="font-weight: 700;"><?php echo number_format($progress['amount_paid'], 0, ',', ' '); ?> / <?php echo number_format($progress['amount_total'], 0, ',', ' '); ?> €</span>
                    </div>
                    <div style="background: #e0e0e0; border-radius: 8px; height: 10px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #59b7b7, #c8a45e); height: 100%; width: <?php echo $pct; ?>%; border-radius: 8px; transition: width 0.5s;"></div>
                    </div>
                </div>

                <!-- Liste des participants -->
                <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <th style="text-align: left; padding: 8px 4px;">Participant</th>
                            <th style="text-align: left; padding: 8px 4px;">Montant</th>
                            <th style="text-align: left; padding: 8px 4px;">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($shares as $s): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 8px 4px;">
                                <?php echo esc_html($s['name'] ?: $s['email']); ?>
                                <?php if ($s['is_captain']): ?><small style="color: #c8a45e;">(vous)</small><?php endif; ?>
                            </td>
                            <td style="padding: 8px 4px; font-weight: 600;">
                                <?php echo number_format(floatval($s['amount']), 0, ',', ' '); ?> €
                            </td>
                            <td style="padding: 8px 4px;">
                                <?php if ($s['status'] === 'paid'): ?>
                                    <span style="color: #27ae60;">✅ Payé</span>
                                    <small style="color: #888;"><?php echo date('d/m H\hi', strtotime($s['paid_at'])); ?></small>
                                <?php else: ?>
                                    <span style="color: #e8734a;">⏳ En attente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($group['status'] === 'pending'): ?>
                <p style="margin-top: 12px; font-size: 13px; color: #888;">
                    ⏰ Expire le <?php echo date('d/m/Y à H\hi', strtotime($group['expires_at'])); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /* ── Masquer les produits splitpay du catalogue ── */
    public static function hide_splitpay_products($meta_query) {
        $meta_query[] = [
            'key'     => '_vs08sp_share_id',
            'compare' => 'NOT EXISTS',
        ];
        return $meta_query;
    }
}
