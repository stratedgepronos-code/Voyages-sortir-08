<?php
/**
 * VS08 SplitPay — Intégration espace admin VS08
 * S'intègre dans /espace-admin/ via hooks (pas de page WP admin séparée).
 */
if (!defined('ABSPATH')) exit;

class VS08SP_Admin {

    public static function init() {
        add_action('vs08_espace_admin_nav', [__CLASS__, 'render_nav_item']);
        add_action('vs08_espace_admin_mobile_nav', [__CLASS__, 'render_mobile_nav']);
        add_action('vs08_espace_admin_content', [__CLASS__, 'render_content']);
        add_action('vs08_espace_admin_content', [__CLASS__, 'render_dashboard_widget']);
        add_action('init', [__CLASS__, 'register_rewrite']);
        add_action('template_redirect', [__CLASS__, 'handle_actions']);
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'order_splitpay_badge']);
    }

    public static function register_rewrite() {
        add_rewrite_rule('^espace-admin/splitpay/?$', 'index.php?vs08_admin=splitpay', 'top');
    }

    public static function render_nav_item($admin_view) {
        $pending = VS08SP_DB::count_groups('pending');
        ?><a href="<?php echo home_url('/espace-admin/splitpay/'); ?>" class="ea-nav-item <?php echo $admin_view === 'splitpay' ? 'active' : ''; ?>"><span class="ea-nav-icon">💳</span> Paiements Groupés<?php if ($pending > 0): ?><span class="ea-nav-badge"><?php echo $pending; ?></span><?php endif; ?></a><?php
    }

    public static function render_mobile_nav($admin_view) {
        ?><a href="<?php echo home_url('/espace-admin/splitpay/'); ?>" class="ea-mn-item <?php echo $admin_view === 'splitpay' ? 'active' : ''; ?>"><span>💳</span>Groupés</a><?php
    }

    public static function render_dashboard_widget($admin_view) {
        if ($admin_view !== 'dashboard') return;
        $pending = VS08SP_DB::count_groups('pending');
        $complete = VS08SP_DB::count_groups('complete');
        if ($pending === 0 && $complete === 0) return;
        $pending_groups = VS08SP_DB::list_groups('pending', 100, 0);
        $pending_amount = 0;
        foreach ($pending_groups as $g) {
            $progress = VS08SP_DB::get_payment_progress(intval($g['id']));
            $pending_amount += floatval($g['total_amount']) - $progress['amount_paid'];
        }
        ?>
        <div class="ea-table-card" style="margin-top:24px"><div class="ea-table-header"><span class="ea-table-title">💳 Paiements Groupés</span><a href="<?php echo home_url('/espace-admin/splitpay/'); ?>" style="font-size:13px;color:#59b7b7;text-decoration:none;">Voir tout →</a></div>
        <div style="display:flex;gap:16px;padding:16px;flex-wrap:wrap;">
            <div style="flex:1;min-width:120px;background:#fef3c7;border-radius:10px;padding:14px;text-align:center;border:1px solid #fbbf24"><div style="font-size:11px;color:#92400e;">En attente</div><div style="font-size:24px;font-weight:700;color:#0f2424"><?php echo $pending; ?></div><div style="font-size:11px;color:#92400e"><?php echo number_format($pending_amount, 0, ',', ' '); ?> € restants</div></div>
            <div style="flex:1;min-width:120px;background:#d1fae5;border-radius:10px;padding:14px;text-align:center;border:1px solid #34d399"><div style="font-size:11px;color:#065f46;">Validés</div><div style="font-size:24px;font-weight:700;color:#0f2424"><?php echo $complete; ?></div></div>
        </div></div>
        <?php
    }

    public static function handle_actions() {
        if (get_query_var('vs08_admin') !== 'splitpay') return;
        if (empty($_GET['vs08sp_action']) || empty($_GET['group_id'])) return;
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'vs08sp_admin_action')) return;
        if (!current_user_can('manage_woocommerce')) return;
        $group_id = intval($_GET['group_id']);
        $action = sanitize_text_field($_GET['vs08sp_action']);
        switch ($action) {
            case 'extend':
                global $wpdb;
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}vs08sp_groups SET expires_at = DATE_ADD(expires_at, INTERVAL %d HOUR), status = 'pending' WHERE id = %d", VS08SP_EXPIRY_HOURS, $group_id));
                break;
            case 'cancel':
                VS08SP_DB::update_group_status($group_id, 'cancelled');
                break;
            case 'force_complete':
                VS08SP_DB::update_group_status($group_id, 'complete');
                break;
        }
        wp_redirect(home_url('/espace-admin/splitpay/'));
        exit;
    }

    public static function render_content($admin_view) {
        if ($admin_view !== 'splitpay') return;
        if (!current_user_can('manage_woocommerce')) return;
        $filter = sanitize_text_field($_GET['status'] ?? '');
        $groups = VS08SP_DB::list_groups($filter, 50, 0);
        $counts = ['all'=>VS08SP_DB::count_groups(),'pending'=>VS08SP_DB::count_groups('pending'),'complete'=>VS08SP_DB::count_groups('complete'),'expired'=>VS08SP_DB::count_groups('expired'),'cancelled'=>VS08SP_DB::count_groups('cancelled')];
        ?>
        <h1 class="ea-page-title">💳 Paiements Groupés</h1>
        <p class="ea-page-sub">Suivi des paiements partagés entre participants</p>
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
        <?php foreach (['' => ['Tous',$counts['all'],'#0f2424'],'pending'=>['En attente',$counts['pending'],'#c8a45e'],'complete'=>['Validés',$counts['complete'],'#27ae60'],'expired'=>['Expirés',$counts['expired'],'#e8734a'],'cancelled'=>['Annulés',$counts['cancelled'],'#999']] as $k=>$f):
            $a = ($filter === $k) ? "background:{$f[2]};color:#fff;" : "background:#f0f0f0;color:#555;"; ?>
            <a href="<?php echo home_url('/espace-admin/splitpay/' . ($k ? '?status='.$k : '')); ?>" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;<?php echo $a; ?>"><?php echo $f[0]; ?> <span style="background:rgba(0,0,0,0.1);padding:1px 6px;border-radius:10px;font-size:11px;"><?php echo $f[1]; ?></span></a>
        <?php endforeach; ?>
        </div>
        <?php if (empty($groups)): ?>
            <div class="ea-table-card" style="padding:40px;text-align:center;color:#9ca3af;">Aucun paiement groupé<?php echo $filter ? ' avec ce statut' : ''; ?>.</div>
        <?php else: foreach ($groups as $g):
            $progress = VS08SP_DB::get_payment_progress(intval($g['id']));
            $pct = floatval($g['total_amount']) > 0 ? round(($progress['amount_paid'] / floatval($g['total_amount'])) * 100) : 0;
            $shares = VS08SP_DB::get_shares(intval($g['id']));
            $st_map = ['pending'=>['En attente','#c8a45e','#fef3c7'],'complete'=>['Validé','#065f46','#d1fae5'],'expired'=>['Expiré','#92400e','#fef3c7'],'cancelled'=>['Annulé','#6b7280','#f3f4f6']];
            $st = $st_map[$g['status']] ?? $st_map['pending'];
        ?>
        <div class="ea-table-card" style="margin-bottom:16px;">
            <div style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div><div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;"><strong style="font-size:16px;color:#0f2424;"><?php echo esc_html($g['voyage_titre']); ?></strong><span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;color:<?php echo $st[1]; ?>;background:<?php echo $st[2]; ?>;"><?php echo $st[0]; ?></span></div><div style="font-size:13px;color:#6b7280;">Capitaine : <strong><?php echo esc_html($g['captain_name']); ?></strong> · <?php echo esc_html($g['captain_email']); ?> · Créé le <?php echo date('d/m/Y H:i', strtotime($g['created_at'])); ?></div></div>
                <div style="text-align:right;"><div style="font-size:22px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;"><?php echo number_format(floatval($g['total_amount']), 0, ',', ' '); ?> €</div><div style="font-size:12px;color:#9ca3af;">Expire le <?php echo date('d/m/Y H:i', strtotime($g['expires_at'])); ?></div></div>
            </div>
            <div style="padding:0 20px 12px;"><div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:6px;"><span><?php echo $progress['paid']; ?>/<?php echo $progress['total']; ?> ont payé</span><span style="font-weight:700;color:#0f2424;"><?php echo number_format($progress['amount_paid'], 0, ',', ' '); ?> / <?php echo number_format($progress['amount_total'], 0, ',', ' '); ?> € (<?php echo $pct; ?>%)</span></div><div style="background:#e5e7eb;border-radius:6px;height:8px;overflow:hidden;"><div style="background:<?php echo $g['status']==='complete'?'#27ae60':($g['status']==='expired'?'#e8734a':'linear-gradient(90deg,#59b7b7,#c8a45e)'); ?>;height:100%;width:<?php echo $pct; ?>%;border-radius:6px;"></div></div></div>
            <div style="padding:0 20px 16px;"><table style="width:100%;font-size:13px;border-collapse:collapse;"><thead><tr style="border-bottom:1px solid #e5e7eb;"><th style="text-align:left;padding:8px 4px;color:#6b7280;font-weight:500;">Participant</th><th style="text-align:left;padding:8px 4px;color:#6b7280;font-weight:500;">Email</th><th style="text-align:right;padding:8px 4px;color:#6b7280;font-weight:500;">Montant</th><th style="text-align:center;padding:8px 4px;color:#6b7280;font-weight:500;">Statut</th></tr></thead><tbody>
            <?php foreach ($shares as $s): ?>
                <tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px 4px;"><strong><?php echo esc_html($s['name'] ?: '—'); ?></strong><?php if ($s['is_captain']): ?> <span style="background:#c8a45e;color:#fff;font-size:9px;font-weight:700;padding:1px 6px;border-radius:8px;">CAP</span><?php endif; ?></td><td style="padding:8px 4px;color:#6b7280;"><?php echo esc_html($s['email']); ?></td><td style="padding:8px 4px;text-align:right;font-weight:600;"><?php echo number_format(floatval($s['amount']), 0, ',', ' '); ?> €</td><td style="padding:8px 4px;text-align:center;"><?php if ($s['status'] === 'paid'): ?><span style="color:#27ae60;font-weight:600;">✅ Payé</span><br><small style="color:#9ca3af;"><?php echo date('d/m H:i', strtotime($s['paid_at'])); ?></small><?php else: ?><span style="color:#e8734a;">⏳ En attente</span><?php endif; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php if (in_array($g['status'], ['pending', 'expired'])): ?>
            <div style="padding:12px 20px;background:#f9fafb;border-top:1px solid #e5e7eb;display:flex;gap:8px;flex-wrap:wrap;">
                <a href="<?php echo wp_nonce_url(home_url('/espace-admin/splitpay/?vs08sp_action=extend&group_id=' . $g['id']), 'vs08sp_admin_action'); ?>" class="ea-btn ea-btn-outline" style="padding:6px 14px;font-size:12px;">⏰ Prolonger</a>
                <a href="<?php echo wp_nonce_url(home_url('/espace-admin/splitpay/?vs08sp_action=force_complete&group_id=' . $g['id']), 'vs08sp_admin_action'); ?>" class="ea-btn ea-btn-primary" style="padding:6px 14px;font-size:12px;" onclick="return confirm('Valider ce voyage ?');">✅ Valider</a>
                <a href="<?php echo wp_nonce_url(home_url('/espace-admin/splitpay/?vs08sp_action=cancel&group_id=' . $g['id']), 'vs08sp_admin_action'); ?>" class="ea-btn ea-btn-outline" style="padding:6px 14px;font-size:12px;color:#e8734a;border-color:#e8734a;" onclick="return confirm('Annuler ?');">❌ Annuler</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif;
    }

    public static function order_splitpay_badge($order) {
        $group_id = $order->get_meta('_vs08sp_group_id');
        if (!$group_id) return;
        $is_parent = $order->get_meta('_vs08sp_is_parent');
        echo '<div style="margin-top:12px;padding:10px 14px;background:#edf8f8;border-radius:8px;border-left:4px solid #59b7b7;"><strong>💳 Paiement Groupé</strong> — ' . ($is_parent ? 'Commande parent' : 'Part individuelle') . ' — <a href="' . home_url('/espace-admin/splitpay/') . '">Groupe #' . intval($group_id) . '</a></div>';
    }
}
