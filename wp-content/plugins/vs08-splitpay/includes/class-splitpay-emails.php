<?php
/**
 * VS08 SplitPay — Emails
 *
 * 4 types d'emails envoyés par le système :
 *
 * 1. INVITATION      → Envoyé à chaque participant avec son lien de paiement
 * 2. CONFIRMATION    → Envoyé au participant après son paiement
 * 3. PROGRESSION     → Envoyé au capitaine quand quelqu'un paye
 * 4. GROUPE COMPLET  → Envoyé au capitaine quand 100% est atteint
 *
 * Tous les emails utilisent le design VS08 (Playfair Display, turquoise, navy).
 */
if (!defined('ABSPATH')) exit;

class VS08SP_Emails {

    public static function init() {
        // Rien à initialiser — les emails sont appelés directement par les autres classes
    }

    /* ══════════════════════════════════════════
     *  1. INVITATIONS (envoyées à la création du groupe)
     * ══════════════════════════════════════════ */
    public static function send_invitations(int $group_id) {
        $group = VS08SP_DB::get_group($group_id);
        if (!$group) return;

        $shares = VS08SP_DB::get_shares($group_id);
        if (empty($shares)) return;

        foreach ($shares as $share) {
            $payment_url = VS08SP_DB::get_payment_url($share['token']);
            $amount = number_format(floatval($share['amount']), 2, ',', ' ');
            $total = number_format(floatval($group['total_amount']), 2, ',', ' ');
            $deadline = date('d/m/Y à H\hi', strtotime($group['expires_at']));
            $prenom = explode(' ', $share['name'])[0] ?: 'Bonjour';

            $subject = sprintf(
                '🏌️ %s vous invite à partager un voyage golf — %s',
                $group['captain_name'],
                $group['voyage_titre']
            );

            if ($share['is_captain']) {
                $subject = sprintf(
                    '🏌️ Votre paiement groupé est créé — %s',
                    $group['voyage_titre']
                );
            }

            $body = self::email_wrapper(
                $subject,
                self::invitation_content($share, $group, $payment_url, $amount, $total, $deadline, $prenom)
            );

            self::send($share['email'], $subject, $body);
        }

        error_log(sprintf('[VS08SP] %d invitations envoyées pour le groupe #%d', count($shares), $group_id));
    }

    /**
     * Contenu de l'email d'invitation.
     */
    private static function invitation_content($share, $group, $payment_url, $amount, $total, $deadline, $prenom): string {
        $is_captain = (bool) $share['is_captain'];

        if ($is_captain) {
            $intro = "Votre paiement groupé pour <strong>{$group['voyage_titre']}</strong> a bien été créé. "
                   . "Les liens de paiement ont été envoyés à tous les participants.";
        } else {
            $intro = "<strong>{$group['captain_name']}</strong> vous invite à participer au voyage "
                   . "<strong>{$group['voyage_titre']}</strong>.<br>"
                   . "Cliquez sur le bouton ci-dessous pour régler votre part.";
        }

        $html = <<<HTML
        <p style="font-size:16px;color:#333;">$prenom,</p>
        <p style="font-size:15px;color:#555;line-height:1.6;">$intro</p>

        <div style="background:#f8f5f0;border-radius:12px;padding:24px;margin:24px 0;text-align:center;">
            <div style="font-size:14px;color:#888;margin-bottom:4px;">Votre part à régler</div>
            <div style="font-size:36px;font-weight:700;color:#0b1120;font-family:'Playfair Display',Georgia,serif;">$amount €</div>
            <div style="font-size:13px;color:#888;margin-top:4px;">sur un total de $total € — {$group['nb_participants']} participants</div>
        </div>

        <div style="text-align:center;margin:32px 0;">
            <a href="$payment_url"
               style="display:inline-block;background:linear-gradient(135deg,#59b7b7,#4a9e9e);color:#fff;text-decoration:none;
                      padding:16px 40px;border-radius:8px;font-size:16px;font-weight:600;letter-spacing:0.5px;">
                🔒 Payer ma part
            </a>
        </div>

        <p style="font-size:13px;color:#888;text-align:center;">
            ⏰ Date limite : <strong>$deadline</strong><br>
            Paiement 100% sécurisé par carte bancaire
        </p>
HTML;

        return $html;
    }

    /* ══════════════════════════════════════════
     *  2. CONFIRMATION DE PAIEMENT INDIVIDUEL
     * ══════════════════════════════════════════ */
    public static function send_payment_confirmation(array $share, array $group) {
        $amount = number_format(floatval($share['amount']), 2, ',', ' ');
        $prenom = explode(' ', $share['name'])[0] ?: 'Bonjour';
        $progress = VS08SP_DB::get_payment_progress(intval($group['id']));

        $subject = sprintf('✅ Paiement reçu — %s', $group['voyage_titre']);

        $pct = $group['total_amount'] > 0
            ? round(($progress['amount_paid'] / floatval($group['total_amount'])) * 100)
            : 0;

        $content = <<<HTML
        <p style="font-size:16px;color:#333;">$prenom,</p>
        <p style="font-size:15px;color:#555;line-height:1.6;">
            Votre paiement de <strong>$amount €</strong> pour le voyage
            <strong>{$group['voyage_titre']}</strong> a bien été reçu. Merci !
        </p>

        <div style="background:#f8f5f0;border-radius:12px;padding:20px;margin:20px 0;">
            <div style="font-size:14px;color:#555;margin-bottom:8px;">
                Progression du groupe : <strong>{$progress['paid']}/{$progress['total']}</strong> participants ont payé
            </div>
            <div style="background:#e0e0e0;border-radius:8px;height:12px;overflow:hidden;">
                <div style="background:linear-gradient(90deg,#59b7b7,#c8a45e);height:100%;width:{$pct}%;border-radius:8px;"></div>
            </div>
        </div>
HTML;

        if ($progress['paid'] === $progress['total']) {
            $content .= '<p style="font-size:16px;text-align:center;color:#27ae60;font-weight:700;">🎉 Tous les participants ont payé — le voyage est confirmé !</p>';
        } else {
            $remaining = $progress['total'] - $progress['paid'];
            $content .= "<p style=\"font-size:14px;color:#888;\">Encore $remaining paiement(s) en attente. Vous serez notifié quand le voyage sera confirmé.</p>";
        }

        $body = self::email_wrapper($subject, $content);
        self::send($share['email'], $subject, $body);
    }

    /* ══════════════════════════════════════════
     *  3. PROGRESSION (notification au capitaine)
     * ══════════════════════════════════════════ */
    public static function send_captain_progress(array $group, array $progress, array $share_just_paid) {
        $captain_email = $group['captain_email'];
        $payer_name = $share_just_paid['name'] ?: $share_just_paid['email'];
        $amount = number_format(floatval($share_just_paid['amount']), 2, ',', ' ');

        $subject = sprintf('💰 %s a payé sa part — %s', $payer_name, $group['voyage_titre']);

        $pct = $group['total_amount'] > 0
            ? round(($progress['amount_paid'] / floatval($group['total_amount'])) * 100)
            : 0;
        $remaining = $progress['total'] - $progress['paid'];

        $content = <<<HTML
        <p style="font-size:15px;color:#555;line-height:1.6;">
            <strong>$payer_name</strong> vient de régler sa part de <strong>$amount €</strong>
            pour le voyage <strong>{$group['voyage_titre']}</strong>.
        </p>

        <div style="background:#f8f5f0;border-radius:12px;padding:20px;margin:20px 0;">
            <div style="font-size:16px;font-weight:700;color:#0b1120;margin-bottom:8px;">
                {$progress['paid']}/{$progress['total']} participants ont payé ({$pct}%)
            </div>
            <div style="background:#e0e0e0;border-radius:8px;height:12px;overflow:hidden;">
                <div style="background:linear-gradient(90deg,#59b7b7,#c8a45e);height:100%;width:{$pct}%;border-radius:8px;"></div>
            </div>
            <div style="font-size:14px;color:#888;margin-top:8px;">
                Encore $remaining paiement(s) en attente
            </div>
        </div>
HTML;

        $body = self::email_wrapper($subject, $content);
        self::send($captain_email, $subject, $body);
    }

    /* ══════════════════════════════════════════
     *  4. GROUPE COMPLET (100%)
     * ══════════════════════════════════════════ */
    public static function send_group_complete(array $group) {
        $total = number_format(floatval($group['total_amount']), 2, ',', ' ');

        $subject = sprintf('🎉 Voyage confirmé ! %s — Tous les paiements reçus', $group['voyage_titre']);

        $content = <<<HTML
        <p style="font-size:16px;color:#333;">Félicitations !</p>
        <p style="font-size:15px;color:#555;line-height:1.6;">
            Tous les participants ont réglé leur part pour le voyage
            <strong>{$group['voyage_titre']}</strong>.
            Le montant total de <strong>$total €</strong> a été collecté.
        </p>

        <div style="background:#edf8f8;border-radius:12px;padding:24px;margin:24px 0;text-align:center;border:2px solid #59b7b7;">
            <div style="font-size:48px;margin-bottom:8px;">🏌️✈️</div>
            <div style="font-size:20px;font-weight:700;color:#0b1120;font-family:'Playfair Display',Georgia,serif;">
                Voyage confirmé !
            </div>
            <p style="font-size:14px;color:#555;margin-top:8px;">
                Votre contrat de voyage et toutes les informations pratiques
                vous seront envoyés sous peu.
            </p>
        </div>

        <p style="font-size:14px;color:#888;">
            Vous pouvez suivre votre voyage dans votre
            <a href="{$this->get_espace_url()}" style="color:#59b7b7;">espace voyageur</a>.
        </p>
HTML;

        // Corriger le $this dans un contexte static
        $espace_url = home_url('/espace-voyageur');
        $content = str_replace('{$this->get_espace_url()}', $espace_url, $content);

        $body = self::email_wrapper($subject, $content);
        self::send($group['captain_email'], $subject, $body);

        // Envoyer aussi aux admins
        $admin_subject = sprintf('[VS08 SplitPay] Groupe complet — %s — %s €', $group['voyage_titre'], $total);
        self::send('sortir08.ag@wanadoo.fr', $admin_subject, $body);
        self::send('sortir08@wanadoo.fr', $admin_subject, $body);
    }

    /* ══════════════════════════════════════════
     *  WRAPPER EMAIL (template HTML VS08)
     * ══════════════════════════════════════════ */
    private static function email_wrapper(string $title, string $content): string {
        $logo_url = home_url('/wp-content/themes/vs08-theme/assets/img/logo.png');
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f0f0f0;font-family:'Outfit',Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f0f0f0;">
<tr><td align="center" style="padding:20px;">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<!-- Header -->
<tr><td style="background:linear-gradient(135deg,#0b1120,#1a2744);padding:32px 40px;text-align:center;">
    <img src="$logo_url" alt="Voyages Sortir 08" style="max-height:50px;margin-bottom:8px;" />
    <div style="color:#c8a45e;font-size:12px;letter-spacing:2px;text-transform:uppercase;margin-top:4px;">Paiement Groupé</div>
</td></tr>

<!-- Contenu -->
<tr><td style="padding:32px 40px;">
    $content
</td></tr>

<!-- Footer -->
<tr><td style="background:#f8f5f0;padding:20px 40px;text-align:center;border-top:1px solid #eee;">
    <p style="font-size:12px;color:#888;margin:0;">
        Voyages Sortir 08 — Châlons-en-Champagne<br>
        03 26 65 28 63 — resa@voyagessortir08.com<br>
        Immatriculation IM051160003 — © $year
    </p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    /* ── Envoi d'email via wp_mail ──────────── */
    private static function send(string $to, string $subject, string $body): bool {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Voyages Sortir 08 <resa@voyagessortir08.com>',
        ];
        $result = wp_mail($to, $subject, $body, $headers);
        if (!$result) {
            error_log("[VS08SP] Échec envoi email à $to — sujet : $subject");
        }
        return $result;
    }
}
