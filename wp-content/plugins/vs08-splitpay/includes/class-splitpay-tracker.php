<?php
/**
 * VS08 SplitPay v2 — Suivi des paiements + Dashboard capitaine
 *
 * ── Suivi WooCommerce ──
 *   Écoute les paiements (woocommerce_payment_complete) pour marquer
 *   les parts comme payées et déclencher le flux complet quand 100%.
 *
 * ── Dashboard espace voyageur ──
 *   Injecté via do_action('vs08_espace_voyageur_after_panels').
 *   Affiche le formulaire de configuration (draft) ou le suivi (pending/complete).
 */
if (!defined('ABSPATH')) exit;

class VS08SP_Tracker {

    public static function init() {
        // Écouter les paiements WooCommerce
        add_action('woocommerce_payment_complete', [__CLASS__, 'on_payment_complete'], 20);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'on_payment_complete'], 20);
        add_action('woocommerce_order_status_completed', [__CLASS__, 'on_payment_complete'], 20);

        // Dashboard dans l'espace voyageur
        add_action('vs08_espace_voyageur_after_panels', [__CLASS__, 'render_captain_dashboard'], 10, 2);

        // Masquer les produits splitpay du catalogue
        add_filter('woocommerce_product_query_meta_query', [__CLASS__, 'hide_splitpay_products']);
    }

    /* ══════════════════════════════════════════
     *  RÉCEPTION D'UN PAIEMENT
     * ══════════════════════════════════════════ */
    public static function on_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        if ($order->get_meta('_vs08sp_payment_processed')) return;

        $share_id = 0;
        $group_id = 0;
        $token    = '';

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

        if (!$share_id || !$group_id) return;

        VS08SP_DB::mark_share_paid($share_id, $order_id);

        $order->update_meta_data('_vs08sp_share_id', $share_id);
        $order->update_meta_data('_vs08sp_group_id', $group_id);
        $order->update_meta_data('_vs08sp_token', $token);
        $order->update_meta_data('_vs08sp_payment_processed', current_time('mysql'));
        $order->save();

        // ── Supprimer le produit WC temporaire (ménage) ──
        if ($product_id) {
            wp_delete_post($product_id, true); // true = force delete, pas de corbeille
            error_log(sprintf('[VS08SP] Produit WC temporaire #%d supprimé après paiement', $product_id));
        }

        error_log(sprintf('[VS08SP] Part #%d payée (commande #%d, groupe #%d)', $share_id, $order_id, $group_id));

        $share = VS08SP_DB::get_share_by_token($token);
        $group = VS08SP_DB::get_group($group_id);
        if ($share && $group) {
            VS08SP_Emails::send_payment_confirmation($share, $group);
        }

        if (VS08SP_DB::is_group_fully_paid($group_id)) {
            self::complete_group($group_id);
        } else {
            $progress = VS08SP_DB::get_payment_progress($group_id);
            if ($group) {
                VS08SP_Emails::send_captain_progress($group, $progress, $share);
            }
        }
    }

    /* ══════════════════════════════════════════
     *  VALIDATION GROUPE (100% PAYÉ)
     * ══════════════════════════════════════════ */
    private static function complete_group(int $group_id) {
        $group = VS08SP_DB::get_group($group_id);
        if (!$group) return;

        VS08SP_DB::update_group_status($group_id, 'complete');
        error_log(sprintf('[VS08SP] Groupe #%d COMPLET !', $group_id));

        $booking_data = $group['booking_data'];
        if (!is_array($booking_data)) return;

        try {
            $parent_order = wc_create_order(['status' => 'processing']);
            if (is_wp_error($parent_order)) {
                error_log('[VS08SP] Erreur commande parent : ' . $parent_order->get_error_message());
                return;
            }

            $product = new WC_Product_Simple();
            $product->set_name(sprintf('Voyage groupé — %s — %d participants', $group['voyage_titre'], $group['nb_participants']));
            $product->set_price(floatval($group['total_amount']));
            $product->set_regular_price(floatval($group['total_amount']));
            $product->set_status('private');
            $product->set_virtual(true);
            $product_id = $product->save();

            update_post_meta($product_id, '_vs08v_booking_data', $booking_data);
            update_post_meta($product_id, '_vs08v_voyage_id', intval($group['voyage_id']));
            update_post_meta($product_id, '_vs08sp_parent_group', $group_id);

            $parent_order->add_product($product, 1);

            $fact = $booking_data['facturation'] ?? [];
            $parent_order->set_billing_first_name($fact['prenom'] ?? '');
            $parent_order->set_billing_last_name($fact['nom'] ?? '');
            $parent_order->set_billing_email($group['captain_email']);
            $parent_order->set_billing_phone($fact['tel'] ?? '');
            $parent_order->set_billing_address_1($fact['adresse'] ?? '');
            $parent_order->set_billing_postcode($fact['cp'] ?? '');
            $parent_order->set_billing_city($fact['ville'] ?? '');
            $parent_order->set_billing_country('FR');

            $parent_order->update_meta_data('_vs08sp_group_id', $group_id);
            $parent_order->update_meta_data('_vs08sp_is_parent', true);
            $parent_order->update_meta_data('_vs08v_booking_data', $booking_data);
            $parent_order->update_meta_data('_vs08v_voyage_id', intval($group['voyage_id']));
            $parent_order->update_meta_data('_vs08v_total_voyage', floatval($group['total_amount']));
            $parent_order->update_meta_data('_vs08v_payer_tout', true);

            $shares = VS08SP_DB::get_shares($group_id);
            $note = "Paiement groupé — " . count($shares) . " participants :\n";
            foreach ($shares as $s) {
                $note .= sprintf("• %s (%s) — %s € — Cmd #%d\n",
                    $s['name'], $s['email'],
                    number_format(floatval($s['amount']), 2, ',', ' '),
                    intval($s['order_id'])
                );
            }
            $parent_order->add_order_note($note);
            $parent_order->calculate_totals();
            $parent_order->save();

            if (class_exists('VS08V_Emails')) {
                VS08V_Emails::dispatch($parent_order->get_id());
            }

            VS08SP_Emails::send_group_complete($group);

        } catch (\Throwable $e) {
            error_log('[VS08SP] ERREUR complete_group : ' . $e->getMessage());
        }
    }

    /* ══════════════════════════════════════════
     *  DASHBOARD CAPITAINE (espace voyageur)
     * ══════════════════════════════════════════
     *
     *  3 états possibles :
     *  - draft    → Formulaire de configuration des participants
     *  - pending  → Barre de progression + tableau des paiements
     *  - complete → Badge "Voyage confirmé"
     */
    public static function render_captain_dashboard($current_user = null, $voyage_orders = []) {
        if (!$current_user) $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->user_email) return;

        $groups = VS08SP_DB::get_groups_by_captain($current_user->user_email);
        if (empty($groups)) return;

        ?>
        <div class="vs08sp-captain-section" style="margin-top:40px;">
            <h2 style="font-family:'Playfair Display',serif;color:#0b1120;margin-bottom:20px;">
                👥 Mes paiements groupés
            </h2>

            <?php foreach ($groups as $group):
                $status = $group['status'];
                $group_id = intval($group['id']);
            ?>

            <?php if ($status === 'draft'): ?>
                <?php self::render_draft_form($group); ?>
            <?php else: ?>
                <?php self::render_group_status($group); ?>
            <?php endif; ?>

            <?php endforeach; ?>
        </div>

        <?php self::render_config_js(); ?>
        <?php
    }

    /* ── Formulaire de configuration (draft) ── */
    private static function render_draft_form(array $group) {
        $group_id = intval($group['id']);
        $total = floatval($group['total_amount']);
        $booking = $group['booking_data'];
        $nb_voyageurs = intval($booking['devis']['nb_total'] ?? 2);
        $prix_vol_pp = floatval($booking['params']['prix_vol'] ?? 0);
        ?>
        <div class="vs08sp-captain-card" id="vs08sp-config-<?php echo $group_id; ?>"
             style="background:#f8f5f0;border-radius:12px;padding:24px;margin-bottom:20px;border-left:4px solid #c8a45e;">

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <h3 style="margin:0;font-family:'Playfair Display',serif;"><?php echo esc_html($group['voyage_titre']); ?></h3>
                <span style="font-size:14px;font-weight:600;color:#c8a45e;">📝 À configurer</span>
            </div>

            <p style="font-size:14px;color:#6b7280;margin-bottom:20px;font-family:'Outfit',sans-serif;">
                Entrez les informations de chaque participant et répartissez le montant de
                <strong style="color:#0b1120;"><?php echo number_format($total, 0, ',', ' '); ?> €</strong>
                <?php if (!$group['booking_data']['payer_tout']): ?>
                    (acompte <?php echo intval($booking['acompte_pct'] ?? 30); ?>%)
                <?php endif; ?>
            </p>

            <!-- Participants -->
            <div id="vs08sp-participants-<?php echo $group_id; ?>">
                <!-- Le 1er participant = capitaine (pré-rempli) -->
                <div class="vs08sp-prow" data-idx="0" style="background:#fffef5;border:1.5px solid #c8a45e;border-radius:10px;padding:14px;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="background:#c8a45e;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;">CAPITAINE</span>
                        <span style="font-size:13px;color:#888;">Participant 1 (vous)</span>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <input type="text" class="sp-prenom" placeholder="Prénom" value="<?php echo esc_attr($booking['facturation']['prenom'] ?? ''); ?>"
                               style="flex:1;min-width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:'Outfit',sans-serif;">
                        <input type="text" class="sp-nom" placeholder="Nom" value="<?php echo esc_attr($booking['facturation']['nom'] ?? ''); ?>"
                               style="flex:1;min-width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:'Outfit',sans-serif;">
                        <input type="email" class="sp-email" placeholder="Email" value="<?php echo esc_attr($group['captain_email']); ?>" readonly
                               style="flex:2;min-width:180px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:'Outfit',sans-serif;background:#f0f0f0;">
                        <div style="display:flex;align-items:center;gap:4px;">
                            <input type="number" class="sp-amount" placeholder="Montant" value="<?php echo round($total / 2); ?>"
                                   style="width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:700;text-align:right;font-family:'Outfit',sans-serif;">
                            <span style="font-size:14px;color:#888;">€</span>
                        </div>
                    </div>
                </div>

                <!-- 2ème participant (vide) -->
                <div class="vs08sp-prow" data-idx="1" style="background:#fff;border:1.5px solid #eee;border-radius:10px;padding:14px;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:13px;color:#888;">Participant 2</span>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <input type="text" class="sp-prenom" placeholder="Prénom" style="flex:1;min-width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:'Outfit',sans-serif;">
                        <input type="text" class="sp-nom" placeholder="Nom" style="flex:1;min-width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:'Outfit',sans-serif;">
                        <input type="email" class="sp-email" placeholder="Email" style="flex:2;min-width:180px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:'Outfit',sans-serif;">
                        <div style="display:flex;align-items:center;gap:4px;">
                            <input type="number" class="sp-amount" placeholder="Montant" value="<?php echo round($total / 2); ?>"
                                   style="width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:700;text-align:right;font-family:'Outfit',sans-serif;">
                            <span style="font-size:14px;color:#888;">€</span>
                        </div>
                        <button type="button" class="vs08sp-remove-participant" style="background:none;border:none;color:#e8734a;cursor:pointer;font-size:18px;padding:4px 8px;" title="Retirer">✕</button>
                    </div>
                </div>
            </div>

            <!-- Barre totale -->
            <div id="vs08sp-bar-<?php echo $group_id; ?>"
                 style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;background:#0b1120;color:#fff;margin-bottom:12px;">
                <span>Total réparti : <strong class="vs08sp-sum">0</strong> / <strong><?php echo number_format($total, 0, ',', ' '); ?></strong> €</span>
                <span class="vs08sp-remaining" style="color:#c8a45e;"></span>
            </div>

            <div id="vs08sp-error-<?php echo $group_id; ?>"
                 style="display:none;padding:8px 12px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;color:#dc2626;font-size:12px;font-weight:600;text-align:center;margin-bottom:12px;"></div>

            <!-- Boutons -->
            <div style="display:flex;gap:10px;">
                <button type="button" class="vs08sp-add-btn"
                        data-group="<?php echo $group_id; ?>"
                        data-total="<?php echo $total; ?>"
                        data-max="<?php echo VS08SP_MAX_PARTICIPANTS; ?>"
                        style="padding:10px 20px;border:2px dashed #ccc;background:none;border-radius:8px;cursor:pointer;font-size:13px;color:#888;font-family:'Outfit',sans-serif;">
                    + Ajouter un participant
                </button>
                <button type="button" class="vs08sp-config-submit"
                        data-group="<?php echo $group_id; ?>"
                        data-total="<?php echo $total; ?>"
                        data-vol="<?php echo $prix_vol_pp; ?>"
                        disabled
                        style="flex:1;padding:14px;background:linear-gradient(135deg,#59b7b7,#4a9e9e);color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;">
                    📤 Envoyer les liens de paiement
                </button>
            </div>
        </div>
        <?php
    }

    /* ── Groupe en cours ou terminé ── */
    private static function render_group_status(array $group) {
        $progress = VS08SP_DB::get_payment_progress(intval($group['id']));
        $pct = $group['total_amount'] > 0
            ? round(($progress['amount_paid'] / floatval($group['total_amount'])) * 100) : 0;
        $shares = VS08SP_DB::get_shares(intval($group['id']));
        $status_labels = [
            'pending'   => '⏳ En attente',
            'complete'  => '✅ Validé',
            'expired'   => '⌛ Expiré',
            'cancelled' => '❌ Annulé',
        ];
        $status_label = $status_labels[$group['status']] ?? $group['status'];
        $border_color = $group['status'] === 'complete' ? '#59b7b7' : ($group['status'] === 'expired' ? '#e8734a' : '#c8a45e');
        ?>
        <div class="vs08sp-captain-card" style="background:#f8f5f0;border-radius:12px;padding:24px;margin-bottom:20px;border-left:4px solid <?php echo $border_color; ?>;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="margin:0;font-family:'Playfair Display',serif;"><?php echo esc_html($group['voyage_titre']); ?></h3>
                <span style="font-size:14px;font-weight:600;"><?php echo $status_label; ?></span>
            </div>

            <div style="margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:6px;">
                    <span><?php echo $progress['paid']; ?>/<?php echo $progress['total']; ?> ont payé</span>
                    <span style="font-weight:700;"><?php echo number_format($progress['amount_paid'], 0, ',', ' '); ?> / <?php echo number_format($progress['amount_total'], 0, ',', ' '); ?> €</span>
                </div>
                <div style="background:#e0e0e0;border-radius:8px;height:10px;overflow:hidden;">
                    <div style="background:linear-gradient(90deg,#59b7b7,#c8a45e);height:100%;width:<?php echo $pct; ?>%;border-radius:8px;transition:width 0.5s;"></div>
                </div>
            </div>

            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #ddd;">
                        <th style="text-align:left;padding:8px 4px;">Participant</th>
                        <th style="text-align:left;padding:8px 4px;">Montant</th>
                        <th style="text-align:left;padding:8px 4px;">Statut</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($shares as $s): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:8px 4px;">
                            <?php echo esc_html($s['name'] ?: $s['email']); ?>
                            <?php if ($s['is_captain']): ?><small style="color:#c8a45e;">(vous)</small><?php endif; ?>
                        </td>
                        <td style="padding:8px 4px;font-weight:600;"><?php echo number_format(floatval($s['amount']), 0, ',', ' '); ?> €</td>
                        <td style="padding:8px 4px;">
                            <?php if ($s['status'] === 'paid'): ?>
                                <span style="color:#27ae60;">✅ Payé</span>
                                <small style="color:#888;"><?php echo date('d/m H\hi', strtotime($s['paid_at'])); ?></small>
                            <?php else: ?>
                                <span style="color:#e8734a;">⏳ En attente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($group['status'] === 'pending'): ?>
            <p style="margin-top:12px;font-size:13px;color:#888;">
                ⏰ Expire le <?php echo date('d/m/Y à H\hi', strtotime($group['expires_at'])); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /* ── JavaScript de configuration ── */
    private static function render_config_js() {
        ?>
        <script>
        (function() {
            'use strict';

            /* ── Ajouter un participant ── */
            document.querySelectorAll('.vs08sp-add-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var gid = this.dataset.group;
                    var max = parseInt(this.dataset.max) || 10;
                    var container = document.getElementById('vs08sp-participants-' + gid);
                    var rows = container.querySelectorAll('.vs08sp-prow');
                    if (rows.length >= max) { alert('Maximum ' + max + ' participants.'); return; }

                    var idx = rows.length;
                    var total = parseFloat(this.dataset.total) || 0;
                    var share = Math.round(total / (idx + 1));

                    var div = document.createElement('div');
                    div.className = 'vs08sp-prow';
                    div.dataset.idx = idx;
                    div.style.cssText = 'background:#fff;border:1.5px solid #eee;border-radius:10px;padding:14px;margin-bottom:10px;';
                    div.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">'
                        + '<span style="font-size:13px;color:#888;">Participant ' + (idx + 1) + '</span>'
                        + '</div>'
                        + '<div style="display:flex;gap:8px;flex-wrap:wrap;">'
                        + '<input type="text" class="sp-prenom" placeholder="Prénom" style="flex:1;min-width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:\'Outfit\',sans-serif;">'
                        + '<input type="text" class="sp-nom" placeholder="Nom" style="flex:1;min-width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:\'Outfit\',sans-serif;">'
                        + '<input type="email" class="sp-email" placeholder="Email" style="flex:2;min-width:180px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:\'Outfit\',sans-serif;">'
                        + '<div style="display:flex;align-items:center;gap:4px;">'
                        + '<input type="number" class="sp-amount" placeholder="Montant" value="' + share + '" style="width:100px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:700;text-align:right;font-family:\'Outfit\',sans-serif;">'
                        + '<span style="font-size:14px;color:#888;">€</span></div>'
                        + '<button type="button" class="vs08sp-remove-participant" style="background:none;border:none;color:#e8734a;cursor:pointer;font-size:18px;padding:4px 8px;" title="Retirer">✕</button>'
                        + '</div>';
                    container.appendChild(div);
                    redistributeAmounts(gid);
                    updateBar(gid);
                });
            });

            /* ── Supprimer un participant ── */
            document.addEventListener('click', function(e) {
                if (!e.target.classList.contains('vs08sp-remove-participant')) return;
                var row = e.target.closest('.vs08sp-prow');
                if (!row) return;
                var container = row.parentElement;
                var gid = container.id.replace('vs08sp-participants-', '');
                if (container.querySelectorAll('.vs08sp-prow').length <= 2) {
                    alert('Il faut au moins 2 participants.');
                    return;
                }
                row.remove();
                // Renuméroter
                container.querySelectorAll('.vs08sp-prow').forEach(function(r, i) {
                    r.dataset.idx = i;
                    var label = r.querySelector('span');
                    if (i > 0 && label) label.textContent = 'Participant ' + (i + 1);
                });
                updateBar(gid);
            });

            /* ── Mettre à jour la barre quand un montant change ── */
            document.addEventListener('input', function(e) {
                if (!e.target.classList.contains('sp-amount')) return;
                var container = e.target.closest('[id^="vs08sp-participants-"]');
                if (!container) return;
                var gid = container.id.replace('vs08sp-participants-', '');
                updateBar(gid);
            });

            function redistributeAmounts(gid) {
                var btn = document.querySelector('.vs08sp-config-submit[data-group="' + gid + '"]');
                var total = parseFloat(btn.dataset.total) || 0;
                var container = document.getElementById('vs08sp-participants-' + gid);
                var rows = container.querySelectorAll('.vs08sp-prow');
                var share = Math.round(total / rows.length);
                var remainder = total - (share * rows.length);
                rows.forEach(function(r, i) {
                    r.querySelector('.sp-amount').value = share + (i === 0 ? remainder : 0);
                });
            }

            function updateBar(gid) {
                var btn = document.querySelector('.vs08sp-config-submit[data-group="' + gid + '"]');
                var total = parseFloat(btn.dataset.total) || 0;
                var container = document.getElementById('vs08sp-participants-' + gid);
                var bar = document.getElementById('vs08sp-bar-' + gid);
                var sumEl = bar.querySelector('.vs08sp-sum');
                var remEl = bar.querySelector('.vs08sp-remaining');

                var sum = 0;
                container.querySelectorAll('.sp-amount').forEach(function(inp) {
                    sum += parseFloat(inp.value) || 0;
                });

                sumEl.textContent = Math.round(sum).toLocaleString('fr-FR');
                var diff = total - sum;

                if (Math.abs(diff) <= 1) {
                    bar.style.background = '#27ae60';
                    remEl.textContent = '✅ Répartition valide';
                    btn.disabled = false;
                } else if (diff > 0) {
                    bar.style.background = '#e8734a';
                    remEl.textContent = 'Reste ' + Math.round(diff).toLocaleString('fr-FR') + ' €';
                    btn.disabled = true;
                } else {
                    bar.style.background = '#e8734a';
                    remEl.textContent = 'Excédent ' + Math.round(Math.abs(diff)).toLocaleString('fr-FR') + ' €';
                    btn.disabled = true;
                }
            }

            /* ── Soumettre la configuration ── */
            document.querySelectorAll('.vs08sp-config-submit').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var gid = this.dataset.group;
                    var container = document.getElementById('vs08sp-participants-' + gid);
                    var errEl = document.getElementById('vs08sp-error-' + gid);

                    var participants = [];
                    var hasError = false;

                    container.querySelectorAll('.vs08sp-prow').forEach(function(row) {
                        var p = {
                            prenom: (row.querySelector('.sp-prenom') || {}).value || '',
                            nom:    (row.querySelector('.sp-nom') || {}).value || '',
                            email:  (row.querySelector('.sp-email') || {}).value || '',
                            amount: parseFloat((row.querySelector('.sp-amount') || {}).value) || 0
                        };
                        if (!p.email || p.email.indexOf('@') === -1) {
                            hasError = true;
                            errEl.textContent = 'Email invalide : ' + (p.email || '(vide)');
                            errEl.style.display = '';
                        }
                        if (!p.prenom && !p.nom) {
                            hasError = true;
                            errEl.textContent = 'Nom et prénom requis pour chaque participant.';
                            errEl.style.display = '';
                        }
                        participants.push(p);
                    });

                    if (hasError) return;
                    errEl.style.display = 'none';

                    btn.disabled = true;
                    btn.textContent = '⏳ Envoi en cours…';

                    var restUrl = (typeof vs08sp !== 'undefined' && vs08sp.rest_url)
                        ? vs08sp.rest_url + 'configure-group'
                        : '/wp-json/vs08sp/v1/configure-group';

                    fetch(restUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': (typeof vs08sp !== 'undefined') ? vs08sp.nonce : ''
                        },
                        body: JSON.stringify({
                            group_id: parseInt(gid),
                            participants: participants
                        })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            btn.textContent = '✅ Liens envoyés !';
                            btn.style.background = '#27ae60';
                            // Recharger la page pour afficher le suivi
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            errEl.textContent = data.message || 'Erreur.';
                            errEl.style.display = '';
                            btn.disabled = false;
                            btn.textContent = '📤 Envoyer les liens de paiement';
                        }
                    })
                    .catch(function() {
                        errEl.textContent = 'Erreur de connexion. Réessayez.';
                        errEl.style.display = '';
                        btn.disabled = false;
                        btn.textContent = '📤 Envoyer les liens de paiement';
                    });
                });
            });

            /* ── Init : calculer les barres ── */
            document.querySelectorAll('.vs08sp-config-submit').forEach(function(btn) {
                updateBar(btn.dataset.group);
            });

        })();
        </script>
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
