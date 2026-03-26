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
    $devis_error = 'Veuillez accepter le traitement de vos données pour l’étude de votre demande.';
    return;
}

$civilite       = sanitize_text_field(wp_unslash($_POST['civilite'] ?? ''));
$nom            = sanitize_text_field(wp_unslash($_POST['nom'] ?? ''));
$prenom         = sanitize_text_field(wp_unslash($_POST['prenom'] ?? ''));
$email          = sanitize_email(wp_unslash($_POST['email'] ?? ''));
$tel            = sanitize_text_field(wp_unslash($_POST['tel'] ?? ''));
$ville          = sanitize_text_field(wp_unslash($_POST['ville'] ?? ''));
$cp             = sanitize_text_field(wp_unslash($_POST['cp'] ?? ''));
$pays_res       = sanitize_text_field(wp_unslash($_POST['pays_res'] ?? ''));
$destination    = sanitize_text_field(wp_unslash($_POST['destination'] ?? ''));
$date_debut     = sanitize_text_field(wp_unslash($_POST['date_debut'] ?? ''));
$date_fin       = sanitize_text_field(wp_unslash($_POST['date_fin'] ?? ''));
$dates_flex     = sanitize_text_field(wp_unslash($_POST['dates_flex'] ?? ''));
$nb_adultes     = sanitize_text_field(wp_unslash($_POST['nb_adultes'] ?? ''));
$nb_enfants     = sanitize_text_field(wp_unslash($_POST['nb_enfants'] ?? ''));
$hebergement    = sanitize_text_field(wp_unslash($_POST['hebergement'] ?? ''));
$budget         = sanitize_text_field(wp_unslash($_POST['budget'] ?? ''));
$comment_connu  = sanitize_text_field(wp_unslash($_POST['comment_connu'] ?? ''));
$message        = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

if (!is_email($email) || $nom === '' || $prenom === '') {
    $devis_error = 'Veuillez remplir au moins le nom, le prénom et une adresse email valide.';
    return;
}

$subject = ($vs08_devis_cfg['subject_prefix'] ?? '[Devis]') . ' ' . $prenom . ' ' . $nom;
$body = "Nouvelle demande de devis depuis le site Voyages Sortir 08.\n\n";
$body .= "— Type : " . ($vs08_devis_cfg['type_label'] ?? '') . "\n";
$body .= "— Civilité : " . $civilite . "\n";
$body .= "— Nom : " . $nom . "\n";
$body .= "— Prénom : " . $prenom . "\n";
$body .= "— Email : " . $email . "\n";
$body .= "— Téléphone : " . $tel . "\n\n";
$body .= "— Ville : " . $ville . "\n";
$body .= "— Code postal : " . $cp . "\n";
$body .= "— Pays de résidence : " . $pays_res . "\n\n";
$body .= "— Destination / thème souhaité : " . $destination . "\n";
$body .= "— Date début souhaitée : " . $date_debut . "\n";
$body .= "— Date fin souhaitée : " . $date_fin . "\n";
$body .= "— Dates flexibles : " . $dates_flex . "\n\n";
$body .= "— Nombre d’adultes : " . $nb_adultes . "\n";
$body .= "— Nombre d’enfants : " . $nb_enfants . "\n";
$body .= "— Hébergement souhaité : " . $hebergement . "\n";
$body .= "— Budget approximatif (par pers. ou total) : " . $budget . "\n";
$body .= "— Comment nous avez-vous connus ? " . $comment_connu . "\n\n";
$body .= "— Précisions / message :\n" . $message . "\n";

if (function_exists('vs08_mail_devis_agence') && vs08_mail_devis_agence($subject, $body, $email)) {
    $devis_sent = true;
} else {
    $devis_error = 'L’envoi a échoué. Merci de nous joindre par téléphone ou à resa@voyagessortir08.com.';
}
