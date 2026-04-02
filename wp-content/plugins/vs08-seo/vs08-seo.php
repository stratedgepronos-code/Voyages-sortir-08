<?php
/**
 * Plugin Name: VS08 SEO — Booster IA
 * Description: Génère automatiquement titres SEO, meta descriptions, balises Open Graph et JSON-LD pour les séjours golf et circuits via Claude IA.
 * Version: 1.2.0
 * Author: Voyages Sortir 08
 */
if (!defined('ABSPATH')) exit;

define('VS08_SEO_PATH', plugin_dir_path(__FILE__));
define('VS08_SEO_URL',  plugin_dir_url(__FILE__));
define('VS08_SEO_VER',  '1.2.0');

// Post types ciblés
define('VS08_SEO_POST_TYPES', ['vs08_voyage', 'vs08_circuit']);

require_once VS08_SEO_PATH . 'includes/class-vs08-seo-generator.php';
require_once VS08_SEO_PATH . 'includes/class-vs08-seo-meta-box.php';
require_once VS08_SEO_PATH . 'includes/class-vs08-seo-head.php';
require_once VS08_SEO_PATH . 'includes/class-vs08-seo-front.php';
require_once VS08_SEO_PATH . 'includes/class-vs08-seo-admin-columns.php';

if (class_exists('WP_CLI')) {
    require_once VS08_SEO_PATH . 'includes/class-vs08-seo-cli.php';
    WP_CLI::add_command('vs08-seo', 'VS08_SEO_CLI_Command');
}

if (!function_exists('vs08_seo_product_is_complete')) {
    /**
     * Indique si la fiche a un SEO exploitable (titre + meta description).
     * Utilisable depuis les templates (ex. espace admin produits).
     */
    function vs08_seo_product_is_complete(int $post_id): bool {
        $seo = get_post_meta($post_id, '_vs08_seo_data', true);
        if (class_exists('VS08_SEO_Admin_Columns')) {
            return VS08_SEO_Admin_Columns::is_seo_complete($seo);
        }
        if (!is_array($seo)) {
            return false;
        }
        $t = trim((string) ($seo['seo_title'] ?? ''));
        $d = trim((string) ($seo['seo_desc'] ?? ''));
        return $t !== '' && $d !== '';
    }
}

// ── Init
add_action('init', function() {
    VS08_SEO_MetaBox::register();
    VS08_SEO_Head::register();
    VS08_SEO_Front::register();
    VS08_SEO_Admin_Columns::register();
});

// ── Génération automatique à la publication / mise à jour d'un produit
add_action('save_post', function($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!in_array($post->post_type, VS08_SEO_POST_TYPES)) return;
    if (!in_array($post->post_status, ['publish', 'draft'])) return;
    // Ne pas regénérer si SEO déjà présent et non forcé
    if (get_post_meta($post_id, '_vs08_seo_generated', true) && !get_post_meta($post_id, '_vs08_seo_force_regen', true)) return;
    // Génération en arrière-plan (évite timeout sur save)
    wp_schedule_single_event(time() + 2, 'vs08_seo_generate_single', [$post_id]);
    delete_post_meta($post_id, '_vs08_seo_force_regen');
}, 10, 3);

add_action('vs08_seo_generate_single', function($post_id) {
    VS08_SEO_Generator::generate_and_save($post_id);
});

// ── AJAX admin : générer un produit manuellement
add_action('wp_ajax_vs08_seo_generate', function() {
    check_ajax_referer('vs08_seo_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Permissions insuffisantes.');
    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id) wp_send_json_error('ID produit manquant.');
    $result = VS08_SEO_Generator::generate_and_save($post_id);
    if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
    wp_send_json_success(get_post_meta($post_id, '_vs08_seo_data', true));
});

// ── AJAX admin : batch (un produit à la fois)
add_action('wp_ajax_vs08_seo_batch_next', function() {
    check_ajax_referer('vs08_seo_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Permissions insuffisantes.');
    $offset = intval($_POST['offset'] ?? 0);

    $posts = get_posts([
        'post_type'      => VS08_SEO_POST_TYPES,
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => 1,
        'offset'         => $offset,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'meta_query'     => [
            ['key' => '_vs08_seo_generated', 'compare' => 'NOT EXISTS'],
        ],
    ]);

    if (empty($posts)) {
        wp_send_json_success(['done' => true, 'message' => 'Tous les produits ont été traités.']);
    }

    $post_id = $posts[0];
    $result  = VS08_SEO_Generator::generate_and_save($post_id);

    if (is_wp_error($result)) {
        wp_send_json_success(['done' => false, 'post_id' => $post_id, 'title' => get_the_title($post_id), 'error' => $result->get_error_message()]);
    }

    wp_send_json_success([
        'done'    => false,
        'post_id' => $post_id,
        'title'   => get_the_title($post_id),
        'seo'     => get_post_meta($post_id, '_vs08_seo_data', true),
    ]);
});

// ── AJAX admin : total produits sans SEO
add_action('wp_ajax_vs08_seo_count_pending', function() {
    check_ajax_referer('vs08_seo_nonce', 'nonce');
    $count = count(get_posts([
        'post_type'      => VS08_SEO_POST_TYPES,
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [['key' => '_vs08_seo_generated', 'compare' => 'NOT EXISTS']],
    ]));
    wp_send_json_success(['count' => $count]);
});

// ── Page admin batch
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=vs08_voyage',
        '🚀 SEO Booster IA',
        '🚀 SEO Booster IA',
        'edit_posts',
        'vs08-seo-batch',
        'vs08_seo_batch_page'
    );
});

function vs08_seo_batch_page() {
    $total_all = count(get_posts([
        'post_type'      => VS08_SEO_POST_TYPES,
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]));
    $total_done = count(get_posts([
        'post_type'      => VS08_SEO_POST_TYPES,
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [['key' => '_vs08_seo_generated', 'compare' => 'EXISTS']],
    ]));
    $total_pending = $total_all - $total_done;
    $nonce = wp_create_nonce('vs08_seo_nonce');
    ?>
    <div class="wrap vs08seo-wrap">
        <h1>🚀 VS08 SEO Booster IA</h1>
        <p style="color:#6b7280;font-size:14px">Génération automatique des titres SEO, meta descriptions et données structurées pour tous vos produits, via Claude IA.</p>
        <p style="color:#9ca3af;font-size:12px;margin-top:-6px">Serveur avec WP-CLI : <code>wp vs08-seo generate-all</code> (produits sans SEO) ou <code>wp vs08-seo generate-all --force</code> (tout régénérer). Pause API : <code>--sleep=2</code>.</p>

        <div class="vs08seo-stats">
            <div class="vs08seo-stat vs08seo-stat-total"><span class="vs08seo-stat-n"><?php echo $total_all; ?></span><span class="vs08seo-stat-lbl">Produits total</span></div>
            <div class="vs08seo-stat vs08seo-stat-done"><span class="vs08seo-stat-n"><?php echo $total_done; ?></span><span class="vs08seo-stat-lbl">SEO généré ✅</span></div>
            <div class="vs08seo-stat vs08seo-stat-pending"><span class="vs08seo-stat-n" id="vs08seo-pending-n"><?php echo $total_pending; ?></span><span class="vs08seo-stat-lbl">En attente ⏳</span></div>
        </div>

        <?php if (!defined('VS08V_CLAUDE_KEY') || empty(VS08V_CLAUDE_KEY)): ?>
        <div class="vs08seo-alert vs08seo-alert-error">
            ⛔ <strong>Clé Claude API non configurée.</strong> Ajoutez <code>CLAUDE_API_KEY=votre_clé</code> dans <code>wp-content/plugins/vs08-voyages/config.cfg</code>.
        </div>
        <?php else: ?>
        <div class="vs08seo-alert vs08seo-alert-ok">
            ✅ Clé Claude API détectée. Prêt à générer.
        </div>
        <?php endif; ?>

        <div class="vs08seo-actions">
            <button id="vs08seo-batch-btn" class="button button-primary button-hero" <?php echo (!defined('VS08V_CLAUDE_KEY') || empty(VS08V_CLAUDE_KEY)) ? 'disabled' : ''; ?>>
                ⚡ Générer le SEO pour tous les produits en attente (<?php echo $total_pending; ?>)
            </button>
            <button id="vs08seo-regen-btn" class="button button-secondary" style="margin-left:10px" <?php echo (!defined('VS08V_CLAUDE_KEY') || empty(VS08V_CLAUDE_KEY)) ? 'disabled' : ''; ?>>
                🔄 Tout régénérer (<?php echo $total_all; ?> produits)
            </button>
        </div>

        <div id="vs08seo-progress-wrap" style="display:none;margin-top:24px">
            <div class="vs08seo-progress-bar-bg">
                <div class="vs08seo-progress-bar" id="vs08seo-progress-bar" style="width:0%"></div>
            </div>
            <div id="vs08seo-progress-label" style="font-size:13px;color:#374151;margin-top:6px;font-family:'Outfit',sans-serif">Initialisation…</div>
        </div>

        <div id="vs08seo-log" style="margin-top:20px;max-height:400px;overflow-y:auto;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:12px;font-family:monospace;font-size:12px;display:none"></div>
    </div>

    <style>
    .vs08seo-wrap{max-width:860px}
    .vs08seo-stats{display:flex;gap:16px;margin:20px 0}
    .vs08seo-stat{background:#fff;border:1.5px solid #e5e7eb;border-radius:14px;padding:18px 24px;text-align:center;flex:1;box-shadow:0 2px 8px rgba(0,0,0,.05)}
    .vs08seo-stat-n{display:block;font-size:36px;font-weight:700;color:#0f2424}
    .vs08seo-stat-lbl{display:block;font-size:12px;color:#6b7280;margin-top:4px}
    .vs08seo-stat-done .vs08seo-stat-n{color:#16a34a}
    .vs08seo-stat-pending .vs08seo-stat-n{color:#d97706}
    .vs08seo-alert{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px}
    .vs08seo-alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
    .vs08seo-alert-ok{background:#dcfce7;color:#166534;border:1px solid #86efac}
    .vs08seo-actions{margin:16px 0}
    .vs08seo-progress-bar-bg{background:#e5e7eb;border-radius:100px;height:14px;overflow:hidden}
    .vs08seo-progress-bar{background:linear-gradient(90deg,#59b7b7,#3d9a9a);height:100%;border-radius:100px;transition:width .4s}
    .vs08seo-log-entry{padding:4px 0;border-bottom:1px solid #f3f4f6;line-height:1.5}
    .vs08seo-log-ok{color:#16a34a}.vs08seo-log-err{color:#dc2626}.vs08seo-log-info{color:#6b7280}
    </style>

    <script>
    (function($){
        var nonce = '<?php echo $nonce; ?>';
        var processing = false;
        var totalPending = <?php echo $total_pending; ?>;
        var totalAll = <?php echo $total_all; ?>;
        var processed = 0;
        var mode = 'pending'; // 'pending' ou 'regen'

        function log(msg, cls) {
            var $log = $('#vs08seo-log');
            $log.show().append('<div class="vs08seo-log-entry vs08seo-log-' + (cls||'info') + '">' + msg + '</div>');
            $log.scrollTop($log[0].scrollHeight);
        }

        function setProgress(pct, label) {
            $('#vs08seo-progress-bar').css('width', pct + '%');
            $('#vs08seo-progress-label').text(label);
        }

        function processNext(offset) {
            if (!processing) return;
            $.post(ajaxurl, {
                action: 'vs08_seo_batch_next',
                nonce: nonce,
                offset: offset
            }, function(res) {
                if (!res.success) { log('❌ Erreur serveur : ' + (res.data || ''), 'err'); processing = false; return; }
                var d = res.data;
                if (d.done) {
                    setProgress(100, '✅ Terminé ! ' + processed + ' produit(s) traité(s).');
                    log('✅ Génération SEO terminée pour ' + processed + ' produit(s).', 'ok');
                    processing = false;
                    $('#vs08seo-batch-btn,#vs08seo-regen-btn').prop('disabled', false);
                    $('#vs08seo-pending-n').text(0);
                    return;
                }
                processed++;
                var pct = totalPending > 0 ? Math.round(processed / totalPending * 100) : 100;
                setProgress(pct, 'Traitement ' + processed + '/' + totalPending + ' — ' + d.title);
                if (d.error) {
                    log('⚠️ [#' + d.post_id + '] ' + d.title + ' — ' + d.error, 'err');
                } else {
                    var seo = d.seo || {};
                    log('✅ [#' + d.post_id + '] ' + d.title + '<br><small style="color:#6b7280">' + (seo.seo_title || '') + ' | ' + (seo.seo_desc || '').substring(0,80) + '…</small>', 'ok');
                }
                setTimeout(function() { processNext(0); }, 300);
            }).fail(function() {
                log('❌ Erreur réseau. Nouvelle tentative dans 3s…', 'err');
                setTimeout(function() { processNext(offset); }, 3000);
            });
        }

        function startBatch(regenAll) {
            if (processing) return;
            processing = true;
            processed = 0;
            mode = regenAll ? 'regen' : 'pending';
            totalPending = regenAll ? totalAll : totalPending;
            $('#vs08seo-batch-btn,#vs08seo-regen-btn').prop('disabled', true);
            $('#vs08seo-progress-wrap').show();
            $('#vs08seo-log').show().empty();
            setProgress(0, 'Démarrage…');

            if (regenAll) {
                // Supprimer tous les _vs08_seo_generated via AJAX puis relancer
                $.post(ajaxurl, {action:'vs08_seo_reset_all', nonce:nonce}, function() {
                    processNext(0);
                });
            } else {
                processNext(0);
            }
        }

        $('#vs08seo-batch-btn').on('click', function() { startBatch(false); });
        $('#vs08seo-regen-btn').on('click', function() {
            if (!confirm('Régénérer le SEO de TOUS les produits ? Cela écrasera les SEO existants.')) return;
            startBatch(true);
        });
    })(jQuery);
    </script>
    <?php
}

// ── AJAX admin : reset tous les _vs08_seo_generated (pour force-regen)
add_action('wp_ajax_vs08_seo_reset_all', function() {
    check_ajax_referer('vs08_seo_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error();
    global $wpdb;
    $wpdb->delete($wpdb->postmeta, ['meta_key' => '_vs08_seo_generated']);
    wp_send_json_success();
});
