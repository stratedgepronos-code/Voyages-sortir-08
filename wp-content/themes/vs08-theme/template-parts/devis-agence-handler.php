<?php
if (!defined('ABSPATH')) {
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($vs08_devis_cfg) || !is_array($vs08_devis_cfg)) {
    return;
}
$nonce_name = $vs08_devis_cfg['nonce_name'] ?? 'vs08_devis_nonce';
$nonce_action = $vs08_devis_cfg['nonce_action'] ?? '';
if ($nonce_action === '' || empty($_POST[$nonce_name]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_name])), $nonce_action)) {
    return;
}
if (empty($_POST['vs08_consent_rgpd'])) {
    $devis_error = 'Veuillez accepter le traitement de vos donn' . "\xc3\xa9" . 'es pour l' . "\xe2\x80\x99" . "\xc3\xa9" . 'tude de votre demande.';
    return;
}

$nom            = sanitize_text_field(wp_unslash($_POST['nom'] ?? ''));
$prenom         = sanitize_text_field(wp_unslash($_POST['prenom'] ?? ''));
$email          = sanitize_email(wp_unslash($_POST['email'] ?? ''));
$tel            = sanitize_text_field(wp_unslash($_POST['tel'] ?? ''));
$destination    = sanitize_text_field(wp_unslash($_POST['destination'] ?? ''));
$date_debut     = sanitize_text_field(wp_unslash($_POST['date_debut'] ?? ''));
$date_fin       = sanitize_text_field(wp_unslash($_POST['date_fin'] ?? ''));
$dates_flex     = sanitize_text_field(wp_unslash($_POST['dates_flex'] ?? ''));
$nb_nuits       = sanitize_text_field(wp_unslash($_POST['nb_nuits'] ?? ''));
$nb_adultes     = sanitize_text_field(wp_unslash($_POST['nb_adultes'] ?? ''));
$nb_enfants     = intval(wp_unslash($_POST['nb_enfants'] ?? '0'));
$hebergement    = sanitize_text_field(wp_unslash($_POST['hebergement'] ?? ''));
$budget         = sanitize_text_field(wp_unslash($_POST['budget'] ?? ''));
$message        = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

// Collecter les âges des enfants
$ages_enfants = [];
for ($i = 1; $i <= $nb_enfants; $i++) {
    $age = sanitize_text_field(wp_unslash($_POST['age_enfant_' . $i] ?? ''));
    if ($age !== '') $ages_enfants[] = $age . ' an' . ($age > 1 ? 's' : '');
}
$ages_enfants_str = !empty($ages_enfants) ? implode(', ', $ages_enfants) : '';

if (!is_email($email) || $nom === '' || $prenom === '') {
    $devis_error = 'Veuillez remplir au moins le nom, le pr' . "\xc3\xa9" . 'nom et une adresse email valide.';
    return;
}

$type_label = $vs08_devis_cfg['type_label'] ?? 'Voyage';
$type_emoji = $vs08_devis_cfg['hero_emoji'] ?? '✈️';
$subject = ($vs08_devis_cfg['subject_prefix'] ?? '[Devis]') . ' ' . $prenom . ' ' . strtoupper($nom) . ' — ' . ($destination ?: $type_label);

$d1_fmt = $date_debut ? date('d/m/Y', strtotime($date_debut)) : '';
$d2_fmt = $date_fin ? date('d/m/Y', strtotime($date_fin)) : '';
$periode_fmt = '';
if ($d1_fmt && $d2_fmt) { $periode_fmt = $d1_fmt . ' → ' . $d2_fmt; }
elseif ($d1_fmt) { $periode_fmt = 'À partir du ' . $d1_fmt; }

$tr = function($icon, $label, $value) {
    if (empty($value) || trim($value) === '' || $value === '—') return '';
    return '<tr><td style="padding:10px 14px;font-size:13px;color:#6b7280;font-weight:600;width:40%;border-bottom:1px solid #f0ece4;font-family:Outfit,Arial,sans-serif">' . $icon . ' ' . $label . '</td><td style="padding:10px 14px;font-size:14px;color:#0f2424;font-weight:500;border-bottom:1px solid #f0ece4;font-family:Outfit,Arial,sans-serif">' . esc_html($value) . '</td></tr>';
};

$body = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head>'
    . '<body style="margin:0;padding:0;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif">'
    . '<div style="max-width:640px;margin:20px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">'
    . '<div style="background:linear-gradient(135deg,#0f2424,#1a4a4a);padding:28px 32px;text-align:center">'
    . '<div style="font-size:20px;font-weight:700;color:#fff;font-family:Georgia,serif;letter-spacing:1px">Voyages Sortir 08</div>'
    . '<div style="font-size:11px;color:rgba(255,255,255,.5);margin-top:4px">SPÉCIALISTE GOLF & VOYAGES</div></div>'
    . '<div style="text-align:center;padding:20px 32px 0"><span style="display:inline-block;background:linear-gradient(135deg,#e8724a,#d4603c);color:#fff;padding:6px 20px;border-radius:100px;font-size:12px;font-weight:700;letter-spacing:1px;font-family:Outfit,Arial,sans-serif">' . $type_emoji . ' DEVIS ' . strtoupper($type_label) . '</span></div>'
    . '<div style="padding:20px 32px 0"><div style="font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;font-family:Outfit,Arial,sans-serif">👤 Client</div>'
    . '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse">'
    . $tr('', 'Nom', strtoupper($nom)) . $tr('', 'Prénom', $prenom)
    . $tr('📧', 'Email', $email) . $tr('📞', 'Téléphone', $tel)
    . '</table></div>'
    . '<div style="padding:20px 32px 0"><div style="font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;font-family:Outfit,Arial,sans-serif">🗓️ Projet de voyage</div>'
    . '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse">'
    . $tr('🌍', 'Destination', $destination) . $tr('📅', 'Période départ', $periode_fmt)
    . $tr('🌙', 'Durée', $nb_nuits ? $nb_nuits . ' nuits' : '') . $tr('🔄', 'Flexibilité', $dates_flex) . $tr('👥', 'Adultes', $nb_adultes)
    . $tr('👶', 'Enfants', $nb_enfants > 0 ? $nb_enfants : '')
    . $tr('🎂', 'Âges enfants', $ages_enfants_str)
    . $tr('🏨', 'Hébergement', $hebergement) . $tr('💰', 'Budget', $budget)
    . '</table></div>'
    . (trim($message) ? '<div style="padding:20px 32px 0"><div style="font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;font-family:Outfit,Arial,sans-serif">💬 Message</div>'
        . '<div style="background:#f9f6f0;border-radius:12px;padding:16px;font-size:14px;color:#374151;line-height:1.6;font-family:Outfit,Arial,sans-serif;white-space:pre-wrap">' . esc_html($message) . '</div></div>' : '')
    . '<div style="padding:24px 32px;text-align:center"><a href="mailto:' . esc_attr($email) . '?subject=' . rawurlencode('Votre devis ' . $type_label . ' — Voyages Sortir 08') . '" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#0f2424,#1a3a3a);color:#fff;text-decoration:none;border-radius:12px;font-weight:700;font-size:14px;font-family:Outfit,Arial,sans-serif">✉️ Répondre au client</a></div>'
    . '<div style="background:#f9f6f0;padding:16px 32px;text-align:center;font-size:11px;color:#9ca3af;font-family:Outfit,Arial,sans-serif">Reçu le ' . date('d/m/Y à H:i') . ' · Formulaire devis ' . esc_html($type_label) . '</div>'
    . '</div></body></html>';

if (function_exists('vs08_mail_devis_agence') && vs08_mail_devis_agence($subject, $body, $email)) {
    $devis_sent = true;
} else {
    $devis_error = "L'envoi a échoué. Merci de nous joindre par téléphone ou à resa@voyagessortir08.com.";
}
