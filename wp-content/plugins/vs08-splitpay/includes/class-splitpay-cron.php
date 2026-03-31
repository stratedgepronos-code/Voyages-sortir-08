<?php
/**
 * VS08 SplitPay — Cron (vérification d'expiration)
 *
 * Toutes les heures, vérifie si des groupes ont dépassé le délai de 48h.
 * Si un groupe est expiré et que des paiements ont été reçus,
 * l'admin est notifié pour décider : prolonger, rembourser, ou valider quand même.
 *
 * Ce fichier gère aussi l'envoi de rappels aux participants qui n'ont pas encore payé
 * (24h avant l'expiration = rappel envoyé une seule fois).
 */
if (!defined('ABSPATH')) exit;

class VS08SP_Cron {

    public static function init() {
        add_action('vs08sp_check_expired_groups', [__CLASS__, 'check_expired']);
    }

    /**
     * Vérification des groupes expirés.
     * Appelé toutes les heures par WP-Cron.
     */
    public static function check_expired() {
        // 1. Marquer les groupes expirés
        $expired = VS08SP_DB::get_expired_groups();

        foreach ($expired as $group) {
            $group_id = intval($group['id']);
            VS08SP_DB::update_group_status($group_id, 'expired');

            $progress = VS08SP_DB::get_payment_progress($group_id);

            error_log(sprintf(
                '[VS08SP CRON] Groupe #%d expiré — %d/%d payés (%s / %s €)',
                $group_id,
                $progress['paid'], $progress['total'],
                number_format($progress['amount_paid'], 2), number_format($progress['amount_total'], 2)
            ));

            // Notifier l'admin
            self::notify_admin_expired($group, $progress);
        }

        // 2. Envoyer des rappels (24h avant expiration)
        self::send_reminders();
    }

    /**
     * Envoie un rappel aux participants qui n'ont pas encore payé,
     * 24h avant l'expiration du groupe.
     */
    private static function send_reminders() {
        global $wpdb;
        $groups_table = $wpdb->prefix . 'vs08sp_groups';
        $shares_table = $wpdb->prefix . 'vs08sp_shares';

        // Groupes qui expirent dans les prochaines 24h et sont encore "pending"
        $now = current_time('mysql');
        $in_24h = date('Y-m-d H:i:s', strtotime($now) + 86400);

        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $groups_table WHERE status = 'pending' AND expires_at BETWEEN %s AND %s",
            $now, $in_24h
        ), ARRAY_A);

        foreach ($groups as $group) {
            $group_id = intval($group['id']);

            // Récupérer les parts non payées et non encore rappelées
            $unpaid = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $shares_table WHERE group_id = %d AND status = 'pending' AND reminder_sent = 0",
                $group_id
            ), ARRAY_A);

            foreach ($unpaid as $share) {
                self::send_reminder_email($share, $group);
                VS08SP_DB::mark_reminder_sent(intval($share['id']));
            }

            if (!empty($unpaid)) {
                error_log(sprintf('[VS08SP CRON] %d rappels envoyés pour le groupe #%d', count($unpaid), $group_id));
            }
        }
    }

    /**
     * Email de rappel à un participant.
     */
    private static function send_reminder_email(array $share, array $group) {
        $payment_url = VS08SP_DB::get_payment_url($share['token']);
        $amount = number_format(floatval($share['amount']), 2, ',', ' ');
        $deadline = date('d/m/Y à H\hi', strtotime($group['expires_at']));
        $prenom = explode(' ', $share['name'])[0] ?: 'Bonjour';

        $subject = sprintf('⏰ Rappel — Il reste moins de 24h pour payer votre part — %s', $group['voyage_titre']);

        $content = <<<HTML
        <p style="font-size:16px;color:#333;">$prenom,</p>
        <p style="font-size:15px;color:#555;line-height:1.6;">
            C'est un petit rappel : votre part de <strong>$amount €</strong>
            pour le voyage <strong>{$group['voyage_titre']}</strong> n'a pas encore été réglée.
        </p>
        <p style="font-size:15px;color:#e8734a;font-weight:600;">
            ⏰ Date limite : $deadline
        </p>

        <div style="text-align:center;margin:32px 0;">
            <a href="$payment_url"
               style="display:inline-block;background:linear-gradient(135deg,#e8734a,#d35f3a);color:#fff;text-decoration:none;
                      padding:16px 40px;border-radius:8px;font-size:16px;font-weight:600;">
                🔒 Payer ma part maintenant
            </a>
        </div>
HTML;

        // Réutiliser le wrapper email de la classe emails
        $body = self::simple_email_wrapper($subject, $content);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Voyages Sortir 08 <resa@voyagessortir08.com>',
        ];
        wp_mail($share['email'], $subject, $body, $headers);
    }

    /**
     * Notification admin quand un groupe expire.
     */
    private static function notify_admin_expired(array $group, array $progress) {
        $total = number_format(floatval($group['total_amount']), 2, ',', ' ');
        $paid = number_format($progress['amount_paid'], 2, ',', ' ');
        $remaining = $progress['total'] - $progress['paid'];

        $admin_url = admin_url('admin.php?page=vs08-splitpay');

        $subject = sprintf(
            '⚠️ [VS08 SplitPay] Groupe #%d expiré — %s — %d/%d payés',
            $group['id'], $group['voyage_titre'], $progress['paid'], $progress['total']
        );

        $content = <<<HTML
        <p style="font-size:15px;color:#555;line-height:1.6;">
            Le groupe de paiement pour <strong>{$group['voyage_titre']}</strong> a expiré
            après {$group['nb_participants']} participants.
        </p>
        <div style="background:#fff3cd;border-radius:8px;padding:16px;margin:16px 0;border-left:4px solid #e8734a;">
            <strong>État :</strong> {$progress['paid']}/{$progress['total']} participants ont payé ($paid / $total €)<br>
            <strong>Capitaine :</strong> {$group['captain_name']} ({$group['captain_email']})<br>
            <strong>Paiements manquants :</strong> $remaining participant(s)
        </div>
        <p style="font-size:15px;">
            <strong>Que souhaitez-vous faire ?</strong>
        </p>
        <div style="text-align:center;margin:24px 0;">
            <a href="$admin_url" style="display:inline-block;background:#0b1120;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;">
                Gérer dans l'admin
            </a>
        </div>
        <p style="font-size:13px;color:#888;">
            Options disponibles : prolonger le délai, annuler le groupe, ou forcer la validation.
        </p>
HTML;

        $body = self::simple_email_wrapper($subject, $content);
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: VS08 SplitPay <resa@voyagessortir08.com>',
        ];
        wp_mail('sortir08.ag@wanadoo.fr', $subject, $body, $headers);
        wp_mail('sortir08@wanadoo.fr', $subject, $body, $headers);
    }

    /**
     * Wrapper email simplifié (pour les emails envoyés par le cron).
     */
    private static function simple_email_wrapper(string $title, string $content): string {
        $year = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f0f0f0;font-family:'Outfit',Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f0f0f0;">
<tr><td align="center" style="padding:20px;">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#fff;border-radius:12px;overflow:hidden;">
<tr><td style="background:#0b1120;padding:20px 32px;text-align:center;">
    <div style="color:#c8a45e;font-size:14px;font-weight:600;">Voyages Sortir 08 — Paiement Groupé</div>
</td></tr>
<tr><td style="padding:28px 32px;">$content</td></tr>
<tr><td style="background:#f8f5f0;padding:16px 32px;text-align:center;">
    <p style="font-size:11px;color:#888;margin:0;">© $year Voyages Sortir 08</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}
