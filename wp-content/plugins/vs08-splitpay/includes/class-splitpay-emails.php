<?php
/**
 * VS08 SplitPay v2 — Emails
 *
 * Types d'emails :
 *
 * 1. ACCOUNT_CREATED       → Identifiants de connexion (nouveau compte WP)
 * 2. PARTICIPANT_INVITATION → Lien de paiement + identifiants + "Payez au plus vite"
 * 3. CAPTAIN_CONFIGURED    → Récap au capitaine quand le groupe est configuré
 * 4. PAYMENT_CONFIRMATION  → Après le paiement d'un participant
 * 5. CAPTAIN_PROGRESS      → Notification au capitaine quand quelqu'un paye
 * 6. GROUP_COMPLETE         → Voyage confirmé (100% payé)
 *
 * Design VS08 : Playfair Display + Outfit, turquoise, navy, gold.
 */
if (!defined('ABSPATH')) exit;

class VS08SP_Emails {

    public static function init() {
        // Les emails sont appelés directement par les autres classes
    }

    /* ══════════════════════════════════════════
     *  1. COMPTE CRÉÉ (identifiants)
     * ══════════════════════════════════════════ */
    public static function send_account_created(string $email, string $password, string $name) {
        $prenom = explode(' ', $name)[0] ?: 'Bonjour';
        $login_url = home_url('/connexion-vip');

        $subject = '🔑 Votre espace voyageur — Voyages Sortir 08';

        $content = <<<HTML
        <p style="font-size:16px;color:#333;">$prenom,</p>
        <p style="font-size:15px;color:#555;line-height:1.6;">
            Un espace voyageur a été créé pour vous sur <strong>Voyages Sortir 08</strong>.
            Connectez-vous pour suivre votre voyage et gérer votre réservation.
        </p>

        <div style="background:#f8f5f0;border-radius:12px;padding:24px;margin:24px 0;">
            <div style="font-size:14px;color:#888;margin-bottom:4px;">Vos identifiants de connexion</div>
            <table style="width:100%;font-size:15px;color:#333;border-collapse:collapse;">
                <tr><td style="padding:6px 0;color:#888;width:120px;">Email :</td><td style="font-weight:600;">$email</td></tr>
                <tr><td style="padding:6px 0;color:#888;">Mot de passe :</td><td style="font-weight:600;font-family:monospace;background:#fff;padding:4px 8px;border-radius:4px;border:1px solid #eee;">$password</td></tr>
            </table>
        </div>

        <div style="text-align:center;margin:32px 0;">
            <a href="$login_url"
               style="display:inline-block;background:linear-gradient(135deg,#59b7b7,#4a9e9e);color:#fff;text-decoration:none;
                      padding:16px 40px;border-radius:8px;font-size:16px;font-weight:600;">
                Se connecter à mon espace →
            </a>
        </div>

        <p style="font-size:12px;color:#888;">
            Vous pourrez changer votre mot de passe à tout moment depuis votre espace voyageur.
        </p>
HTML;

        $body = self::email_wrapper($subject, $content);
        self::send($email, $subject, $body);
    }

    /* ══════════════════════════════════════════
     *  2. INVITATION PARTICIPANT (avec identifiants + lien paiement)
     * ══════════════════════════════════════════ */
    public static function send_participant_invitation(array $group, array $share_data, string $payment_url, string $password = '') {
        $prenom = explode(' ', $share_data['name'])[0] ?: 'Bonjour';
        $amount = number_format(floatval($share_data['amount']), 0, ',', ' ');
        $total = number_format(floatval($group['total_amount']), 0, ',', ' ');
        $captain = $group['captain_name'] ?: $group['captain_email'];
        $login_url = home_url('/connexion-vip');

        $subject = sprintf(
            '🏌️ %s vous invite à partager un voyage golf — %s',
            $captain,
            $group['voyage_titre']
        );

        $content = <<<HTML
        <p style="font-size:16px;color:#333;">$prenom,</p>
        <p style="font-size:15px;color:#555;line-height:1.6;">
            <strong>$captain</strong> vous invite à participer au voyage
            <strong>{$group['voyage_titre']}</strong>.<br>
            Réglez votre part ci-dessous pour confirmer votre place.
        </p>

        <div style="background:linear-gradient(135deg,#0b1120,#1a2744);border-radius:16px;padding:32px;margin:24px 0;text-align:center;color:#fff;">
            <div style="font-size:14px;color:#c8a45e;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;">Votre part à régler</div>
            <div style="font-size:42px;font-weight:700;font-family:'Playfair Display',Georgia,serif;margin-bottom:8px;">$amount €</div>
            <div style="font-size:13px;color:rgba(255,255,255,0.6);">sur un total de $total €</div>
        </div>

        <div style="text-align:center;margin:32px 0;">
            <a href="$payment_url"
               style="display:inline-block;background:linear-gradient(135deg,#59b7b7,#4a9e9e);color:#fff;text-decoration:none;
                      padding:18px 48px;border-radius:8px;font-size:18px;font-weight:700;letter-spacing:0.5px;">
                🔒 Payer ma part
            </a>
        </div>

        <div style="background:#fff8f0;border:1.5px solid #f0dcc0;border-radius:10px;padding:16px;margin:24px 0;text-align:center;">
            <p style="font-size:14px;color:#92400e;font-weight:700;margin:0 0 4px 0;">
                ⚠️ Payez au plus vite pour conserver le prix de votre voyage.
            </p>
            <p style="font-size:12px;color:#b45309;margin:0;">
                Les tarifs aériens et hôteliers sont soumis à disponibilité.
            </p>
        </div>
HTML;

        // Si un nouveau mot de passe a été généré, ajouter les identifiants
        if ($password) {
            $content .= <<<HTML

        <div style="background:#f8f5f0;border-radius:12px;padding:20px;margin:20px 0;">
            <div style="font-size:14px;color:#888;margin-bottom:8px;">🔑 Vos identifiants de connexion</div>
            <table style="width:100%;font-size:14px;color:#333;border-collapse:collapse;">
                <tr><td style="padding:4px 0;color:#888;width:120px;">Email :</td><td style="font-weight:600;">{$share_data['email']}</td></tr>
                <tr><td style="padding:4px 0;color:#888;">Mot de passe :</td><td style="font-weight:600;font-family:monospace;">$password</td></tr>
            </table>
            <div style="margin-top:8px;">
                <a href="$login_url" style="font-size:13px;color:#59b7b7;">Se connecter à mon espace voyageur →</a>
            </div>
        </div>
HTML;
        }

        $content .= <<<HTML

        <p style="font-size:13px;color:#888;text-align:center;">
            Paiement 100% sécurisé par carte bancaire
        </p>
HTML;

        $body = self::email_wrapper($subject, $content);
        self::send($share_data['email'], $subject, $body);
    }

    /* ══════════════════════════════════════════
     *  3. CAPITAINE : GROUPE CONFIGURÉ
     * ══════════════════════════════════════════ */
    public static function send_captain_group_configured(array $group, array $captain_share, array $all_shares) {
        $prenom = explode(' ', $captain_share['name'])[0] ?: 'Bonjour';
        $total = number_format(floatval($group['total_amount']), 0, ',', ' ');
        $nb = count($all_shares);

        $subject = sprintf('✅ Paiement groupé configuré — %s', $group['voyage_titre']);

        $shares_html = '';
        foreach ($all_shares as $s) {
            $badge = $s['is_captain'] ? ' <span style="background:#c8a45e;color:#fff;padding:1px 6px;border-radius:6px;font-size:10px;font-weight:700;">VOUS</span>' : '';
            $shares_html .= sprintf(
                '<tr><td style="padding:8px;border-bottom:1px solid #eee;">%s%s<br><span style="color:#888;font-size:12px;">%s</span></td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right;font-weight:700;">%s €</td></tr>',
                esc_html($s['name']),
                $badge,
                esc_html($s['email']),
                number_format(floatval($s['amount']), 0, ',', ' ')
            );
        }

        // Lien de paiement du capitaine
        $captain_payment_url = VS08SP_DB::get_payment_url($captain_share['token']);
        $captain_amount = number_format(floatval($captain_share['amount']), 0, ',', ' ');

        $content = <<<HTML
        <p style="font-size:16px;color:#333;">$prenom,</p>
        <p style="font-size:15px;color:#555;line-height:1.6;">
            Votre paiement groupé pour <strong>{$group['voyage_titre']}</strong> est configuré.
            Les liens de paiement ont été envoyés aux <strong>$nb participants</strong>.
        </p>

        <div style="background:#f8f5f0;border-radius:12px;padding:20px;margin:24px 0;">
            <div style="font-size:14px;font-weight:700;color:#0b1120;margin-bottom:12px;">📋 Répartition ($total € au total)</div>
            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                $shares_html
            </table>
        </div>

        <div style="text-align:center;margin:32px 0;">
            <a href="$captain_payment_url"
               style="display:inline-block;background:linear-gradient(135deg,#59b7b7,#4a9e9e);color:#fff;text-decoration:none;
                      padding:16px 40px;border-radius:8px;font-size:16px;font-weight:600;">
                🔒 Payer ma part — $captain_amount €
            </a>
        </div>

        <div style="background:#fff8f0;border:1.5px solid #f0dcc0;border-radius:10px;padding:14px;text-align:center;">
            <p style="font-size:14px;color:#92400e;font-weight:700;margin:0;">
                ⚠️ Payez au plus vite pour conserver le prix de votre voyage.
            </p>
        </div>

        <p style="font-size:13px;color:#888;margin-top:16px;">
            Vous recevrez une notification à chaque paiement d'un participant.
            Suivez la progression dans votre <a href="{espace_url}" style="color:#59b7b7;">espace voyageur</a>.
        </p>
HTML;

        $espace_url = home_url('/espace-voyageur');
        $content = str_replace('{espace_url}', $espace_url, $content);

        $body = self::email_wrapper($subject, $content);
        self::send($captain_share['email'], $subject, $body);
    }

    /* ══════════════════════════════════════════
     *  4. CONFIRMATION PAIEMENT INDIVIDUEL
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
            $content .= "<p style=\"font-size:14px;color:#888;\">Encore $remaining paiement(s) en attente.</p>";
        }

        $body = self::email_wrapper($subject, $content);
        self::send($share['email'], $subject, $body);
    }

    /* ══════════════════════════════════════════
     *  5. PROGRESSION (au capitaine)
     * ══════════════════════════════════════════ */
    public static function send_captain_progress(array $group, array $progress, array $share_just_paid) {
        $payer_name = $share_just_paid['name'] ?: $share_just_paid['email'];
        $amount = number_format(floatval($share_just_paid['amount']), 2, ',', ' ');
        $subject = sprintf('💰 %s a payé sa part — %s', $payer_name, $group['voyage_titre']);

        $pct = $group['total_amount'] > 0
            ? round(($progress['amount_paid'] / floatval($group['total_amount'])) * 100) : 0;
        $remaining = $progress['total'] - $progress['paid'];

        $content = <<<HTML
        <p style="font-size:15px;color:#555;line-height:1.6;">
            <strong>$payer_name</strong> vient de régler <strong>$amount €</strong>
            pour <strong>{$group['voyage_titre']}</strong>.
        </p>
        <div style="background:#f8f5f0;border-radius:12px;padding:20px;margin:20px 0;">
            <div style="font-size:16px;font-weight:700;color:#0b1120;margin-bottom:8px;">
                {$progress['paid']}/{$progress['total']} ont payé ({$pct}%)
            </div>
            <div style="background:#e0e0e0;border-radius:8px;height:12px;overflow:hidden;">
                <div style="background:linear-gradient(90deg,#59b7b7,#c8a45e);height:100%;width:{$pct}%;border-radius:8px;"></div>
            </div>
            <div style="font-size:14px;color:#888;margin-top:8px;">Encore $remaining paiement(s) en attente</div>
        </div>
HTML;

        $body = self::email_wrapper($subject, $content);
        self::send($group['captain_email'], $subject, $body);
    }

    /* ══════════════════════════════════════════
     *  6. GROUPE COMPLET (100%)
     * ══════════════════════════════════════════ */
    public static function send_group_complete(array $group) {
        $total = number_format(floatval($group['total_amount']), 2, ',', ' ');
        $subject = sprintf('🎉 Voyage confirmé ! %s', $group['voyage_titre']);
        $espace_url = home_url('/espace-voyageur');

        $content = <<<HTML
        <p style="font-size:16px;color:#333;">Félicitations !</p>
        <p style="font-size:15px;color:#555;line-height:1.6;">
            Tous les participants ont réglé leur part pour
            <strong>{$group['voyage_titre']}</strong>.
            <strong>$total €</strong> collectés.
        </p>
        <div style="background:#edf8f8;border-radius:12px;padding:24px;margin:24px 0;text-align:center;border:2px solid #59b7b7;">
            <div style="font-size:48px;margin-bottom:8px;">🏌️✈️</div>
            <div style="font-size:20px;font-weight:700;color:#0b1120;font-family:'Playfair Display',Georgia,serif;">Voyage confirmé !</div>
            <p style="font-size:14px;color:#555;margin-top:8px;">
                Votre contrat et les informations pratiques vous seront envoyés sous peu.
            </p>
        </div>
        <p style="font-size:14px;color:#888;">
            Suivez votre voyage dans votre <a href="$espace_url" style="color:#59b7b7;">espace voyageur</a>.
        </p>
HTML;

        $body = self::email_wrapper($subject, $content);
        self::send($group['captain_email'], $subject, $body);

        // Aussi aux admins
        $admin_subject = sprintf('[VS08 SplitPay] Groupe complet — %s — %s €', $group['voyage_titre'], $total);
        self::send('sortir08.ag@wanadoo.fr', $admin_subject, $body);
        self::send('sortir08@wanadoo.fr', $admin_subject, $body);
    }

    /* ══════════════════════════════════════════
     *  ANCIENS EMAILS (compatibilité)
     * ══════════════════════════════════════════ */
    public static function send_invitations(int $group_id) {
        $group = VS08SP_DB::get_group($group_id);
        if (!$group) return;
        $shares = VS08SP_DB::get_shares($group_id);
        if (empty($shares)) return;

        foreach ($shares as $share) {
            $payment_url = VS08SP_DB::get_payment_url($share['token']);
            self::send_participant_invitation($group, [
                'name'   => $share['name'],
                'email'  => $share['email'],
                'amount' => $share['amount'],
                'token'  => $share['token'],
            ], $payment_url, '');
        }
    }

    /* ══════════════════════════════════════════
     *  WRAPPER EMAIL (template VS08)
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
<tr><td style="background:linear-gradient(135deg,#0b1120,#1a2744);padding:32px 40px;text-align:center;">
    <img src="$logo_url" alt="Voyages Sortir 08" style="max-height:50px;margin-bottom:8px;" />
    <div style="color:#c8a45e;font-size:12px;letter-spacing:2px;text-transform:uppercase;margin-top:4px;">Paiement Groupé</div>
</td></tr>
<tr><td style="padding:32px 40px;">
    $content
</td></tr>
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
