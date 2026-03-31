<?php
/**
 * Plugin Name: VS08 SplitPay — Paiement Partagé
 * Description: Permet aux groupes de golfeurs de partager le paiement d'un voyage.
 * Version: 1.0.0
 * Author: Voyages Sortir 08
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Text Domain: vs08-splitpay
 *
 * Le capitaine crée la réservation, répartit le montant entre les participants,
 * chaque participant reçoit un lien de paiement unique.
 * Le voyage est validé quand 100% des parts sont payées.
 */
if (!defined('ABSPATH')) exit;

/* ── Constantes ────────────────────────────────────────── */
define('VS08SP_VER',  '1.0.0');
define('VS08SP_PATH', plugin_dir_path(__FILE__));
define('VS08SP_URL',  plugin_dir_url(__FILE__));
define('VS08SP_DB_VERSION', '1.0.0');

/* ── Nombre max de participants ────────────────────────── */
define('VS08SP_MAX_PARTICIPANTS', 10);

/* ── Délai d'expiration (en heures) ────────────────────── */
define('VS08SP_EXPIRY_HOURS', 48);

/* ── Activation : crée les tables SQL ──────────────────── */
register_activation_hook(__FILE__, 'vs08sp_activate');
function vs08sp_activate() {
    require_once VS08SP_PATH . 'includes/class-splitpay-db.php';
    VS08SP_DB::create_tables();
    // Planifier le cron de vérification d'expiration (toutes les heures)
    if (!wp_next_scheduled('vs08sp_check_expired_groups')) {
        wp_schedule_event(time(), 'hourly', 'vs08sp_check_expired_groups');
    }
    flush_rewrite_rules();
}

/* ── Désactivation : retire le cron ────────────────────── */
register_deactivation_hook(__FILE__, 'vs08sp_deactivate');
function vs08sp_deactivate() {
    wp_clear_scheduled_hook('vs08sp_check_expired_groups');
    flush_rewrite_rules();
}

/* ── Chargement des classes ────────────────────────────── */
add_action('plugins_loaded', function () {
    // Ne rien charger si WooCommerce est absent
    if (!class_exists('WooCommerce')) return;

    require_once VS08SP_PATH . 'includes/class-splitpay-db.php';
    require_once VS08SP_PATH . 'includes/class-splitpay-group.php';
    require_once VS08SP_PATH . 'includes/class-splitpay-page.php';
    require_once VS08SP_PATH . 'includes/class-splitpay-tracker.php';
    require_once VS08SP_PATH . 'includes/class-splitpay-emails.php';
    require_once VS08SP_PATH . 'includes/class-splitpay-admin.php';
    require_once VS08SP_PATH . 'includes/class-splitpay-cron.php';

    // Initialiser chaque module
    VS08SP_Group::init();
    VS08SP_Page::init();
    VS08SP_Tracker::init();
    VS08SP_Emails::init();
    VS08SP_Admin::init();
    VS08SP_Cron::init();
}, 20); // Priorité 20 = après WooCommerce et vs08-voyages

/* ── Assets frontend ───────────────────────────────────── */
add_action('wp_enqueue_scripts', function () {
    // JS pour le formulaire de répartition dans booking-steps
    if (is_singular('vs08_voyage') || is_page()) {
        wp_enqueue_script(
            'vs08-splitpay-booking',
            VS08SP_URL . 'assets/js/splitpay-booking.js',
            ['jquery'],
            VS08SP_VER,
            true
        );
        wp_localize_script('vs08-splitpay-booking', 'vs08sp', [
            'ajax_url'         => admin_url('admin-ajax.php'),
            'rest_url'         => rest_url('vs08sp/v1/'),
            'nonce'            => wp_create_nonce('vs08sp_nonce'),
            'max_participants' => VS08SP_MAX_PARTICIPANTS,
            'i18n'             => [
                'min_amount'      => 'Montant minimum : %s €',
                'total_mismatch'  => 'La somme des parts doit être égale au total du voyage.',
                'email_required'  => 'Veuillez renseigner l\'email de chaque participant.',
                'email_invalid'   => 'L\'adresse email "%s" n\'est pas valide.',
                'sending'         => 'Envoi en cours...',
                'success'         => 'Liens de paiement envoyés !',
                'error'           => 'Une erreur est survenue. Réessayez.',
            ],
        ]);
    }

    // CSS global (page participant + barre progression)
    wp_enqueue_style(
        'vs08-splitpay',
        VS08SP_URL . 'assets/css/splitpay.css',
        [],
        VS08SP_VER
    );
}, 25);
