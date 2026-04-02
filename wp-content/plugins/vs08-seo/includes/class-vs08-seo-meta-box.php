<?php
if (!defined('ABSPATH')) exit;

class VS08_SEO_MetaBox {

    public static function register() {
        add_action('add_meta_boxes', [__CLASS__, 'add']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
        add_action('save_post', [__CLASS__, 'save_manual_edits'], 20, 2);
    }

    public static function add() {
        foreach (VS08_SEO_POST_TYPES as $pt) {
            add_meta_box(
                'vs08_seo_box',
                '🔍 SEO Booster IA',
                [__CLASS__, 'render'],
                $pt,
                'normal',
                'high'
            );
        }
    }

    public static function enqueue($hook) {
        global $post;
        if (!$post || !in_array($post->post_type, VS08_SEO_POST_TYPES)) return;
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;
        wp_enqueue_script('vs08-seo-admin', VS08_SEO_URL . 'assets/admin.js', ['jquery'], VS08_SEO_VER, true);
        wp_localize_script('vs08-seo-admin', 'vs08seo', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('vs08_seo_nonce'),
            'post_id'  => $post->ID,
        ]);
    }

    public static function render($post) {
        $seo       = get_post_meta($post->ID, '_vs08_seo_data', true) ?: [];
        $generated = get_post_meta($post->ID, '_vs08_seo_generated', true);
        $api_ok    = defined('VS08V_CLAUDE_KEY') && !empty(VS08V_CLAUDE_KEY);
        $char_seo_title = mb_strlen($seo['seo_title'] ?? '');
        $char_seo_desc  = mb_strlen($seo['seo_desc']  ?? '');
        $faq_n          = isset($seo['faq']) && is_array($seo['faq']) ? count($seo['faq']) : 0;
        ?>
        <style>
        .vs08seo-box{font-family:'Outfit',sans-serif}
        .vs08seo-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px}
        .vs08seo-status{font-size:11px;color:#6b7280}
        .vs08seo-status.ok{color:#16a34a;font-weight:600}
        .vs08seo-btn-gen{background:#59b7b7;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;transition:background .2s}
        .vs08seo-btn-gen:hover{background:#3d9a9a}
        .vs08seo-btn-gen:disabled{background:#9ca3af;cursor:not-allowed}
        .vs08seo-field{margin-bottom:14px}
        .vs08seo-field label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:4px}
        .vs08seo-field input,.vs08seo-field textarea{width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:8px 10px;font-size:13px;font-family:'Outfit',sans-serif;box-sizing:border-box}
        .vs08seo-field input:focus,.vs08seo-field textarea:focus{border-color:#59b7b7;outline:none}
        .vs08seo-chars{font-size:10px;text-align:right;margin-top:2px}
        .vs08seo-chars.ok{color:#16a34a}.vs08seo-chars.warn{color:#d97706}.vs08seo-chars.over{color:#dc2626}
        .vs08seo-preview{background:#f0fafa;border:1px solid #b7dfdf;border-radius:10px;padding:14px;margin-top:16px}
        .vs08seo-preview-title{font-size:18px;color:#1a0dab;font-weight:400;margin:0 0 2px;line-height:1.3}
        .vs08seo-preview-url{font-size:12px;color:#006621;margin:0 0 4px}
        .vs08seo-preview-desc{font-size:13px;color:#545454;margin:0;line-height:1.55}
        .vs08seo-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .vs08seo-keywords{background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;padding:8px;display:flex;flex-wrap:wrap;gap:6px;min-height:36px}
        .vs08seo-keyword{background:#e0f7f7;color:#0f2424;font-size:11px;padding:2px 8px;border-radius:100px;font-family:'Outfit',sans-serif}
        .vs08seo-loading{display:none;align-items:center;gap:8px;color:#59b7b7;font-size:13px;margin-top:8px}
        .vs08seo-spinner{width:16px;height:16px;border:2.5px solid #e5e7eb;border-top-color:#59b7b7;border-radius:50%;animation:vs08seo-spin .6s linear infinite;flex-shrink:0}
        @keyframes vs08seo-spin{to{transform:rotate(360deg)}}
        .vs08seo-error{color:#dc2626;font-size:12px;margin-top:8px;display:none}
        .vs08seo-no-api{background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b;margin-bottom:12px}
        </style>

        <div class="vs08seo-box">
            <?php if (!$api_ok): ?>
            <div class="vs08seo-no-api">⛔ <strong>Clé Claude API manquante.</strong> Ajoutez <code>CLAUDE_API_KEY=...</code> dans <code>config.cfg</code></div>
            <?php endif; ?>

            <div class="vs08seo-header">
                <div class="vs08seo-status <?php echo $generated ? 'ok' : ''; ?>">
                    <?php if ($generated): ?>
                        ✅ SEO généré le <?php echo date('d/m/Y à H:i', $generated); ?>
                    <?php else: ?>
                        ⏳ SEO non encore généré
                    <?php endif; ?>
                </div>
                <button type="button" class="vs08seo-btn-gen" id="vs08seo-gen-btn" <?php echo !$api_ok ? 'disabled' : ''; ?>>
                    ⚡ <?php echo $generated ? 'Régénérer via IA' : 'Générer via IA'; ?>
                </button>
            </div>

            <div class="vs08seo-loading" id="vs08seo-loading">
                <div class="vs08seo-spinner"></div>
                Claude génère le SEO optimal pour ce produit…
            </div>
            <div class="vs08seo-error" id="vs08seo-error"></div>

            <?php if ($faq_n > 0): ?>
            <p class="vs08seo-status ok" style="margin:0 0 12px">📋 <?php echo (int) $faq_n; ?> question(s) FAQ affichées sur la fiche + schema FAQPage (rich results).</p>
            <?php endif; ?>

            <!-- Titre SEO -->
            <div class="vs08seo-field">
                <label>Titre SEO <em style="font-weight:400;text-transform:none">(optimal ≤ 58 car., mobile SERP)</em></label>
                <input type="text" name="vs08_seo[seo_title]" id="vs08seo-title"
                       value="<?php echo esc_attr($seo['seo_title'] ?? ''); ?>"
                       maxlength="64" placeholder="Mot-clé principal en tête (destination + golf)…"
                       oninput="vs08seoCount(this,'vs08seo-chars-title',58)">
                <div class="vs08seo-chars <?php echo $char_seo_title <= 58 ? 'ok' : 'over'; ?>" id="vs08seo-chars-title">
                    <?php echo $char_seo_title; ?>/58 caractères
                </div>
            </div>

            <!-- Meta description -->
            <div class="vs08seo-field">
                <label>Meta description <em style="font-weight:400;text-transform:none">(optimal ≤ 152 car.)</em></label>
                <textarea name="vs08_seo[seo_desc]" id="vs08seo-desc" rows="3"
                          maxlength="160" placeholder="Bénéfice + durée + CTA…"
                          oninput="vs08seoCount(this,'vs08seo-chars-desc',152)"><?php echo esc_textarea($seo['seo_desc'] ?? ''); ?></textarea>
                <div class="vs08seo-chars <?php echo $char_seo_desc <= 152 ? 'ok' : 'over'; ?>" id="vs08seo-chars-desc">
                    <?php echo $char_seo_desc; ?>/152 caractères
                </div>
            </div>

            <!-- Open Graph -->
            <div class="vs08seo-grid">
                <div class="vs08seo-field">
                    <label>OG Title (réseaux sociaux)</label>
                    <input type="text" name="vs08_seo[og_title]" id="vs08seo-og-title"
                           value="<?php echo esc_attr($seo['og_title'] ?? ''); ?>" maxlength="72"
                           placeholder="Titre pour Facebook/Twitter…">
                </div>
                <div class="vs08seo-field">
                    <label>Mots-clés focus</label>
                    <input type="text" name="vs08_seo[keywords]" id="vs08seo-keywords-input"
                           value="<?php echo esc_attr($seo['keywords'] ?? ''); ?>"
                           placeholder="golf, séjour, destination…"
                           oninput="vs08seoUpdateKeywords(this.value)">
                </div>
            </div>

            <div class="vs08seo-field">
                <label>OG Description (réseaux sociaux)</label>
                <textarea name="vs08_seo[og_desc]" id="vs08seo-og-desc" rows="2"
                          maxlength="195"><?php echo esc_textarea($seo['og_desc'] ?? ''); ?></textarea>
            </div>

            <!-- Mots-clés visuels -->
            <div class="vs08seo-keywords" id="vs08seo-keywords-tags">
                <?php foreach (array_filter(array_map('trim', explode(',', $seo['keywords'] ?? ''))) as $kw): ?>
                <span class="vs08seo-keyword"><?php echo esc_html($kw); ?></span>
                <?php endforeach; ?>
            </div>

            <!-- Prévisualisation Google -->
            <?php if (!empty($seo['seo_title'])): ?>
            <div class="vs08seo-preview" id="vs08seo-preview">
                <p class="vs08seo-preview-title" id="vs08seo-prev-title"><?php echo esc_html($seo['seo_title']); ?> | Voyages Sortir 08</p>
                <p class="vs08seo-preview-url"><?php echo esc_html(str_replace(['https://', 'http://'], '', get_permalink($post->ID))); ?></p>
                <p class="vs08seo-preview-desc" id="vs08seo-prev-desc"><?php echo esc_html($seo['seo_desc'] ?? ''); ?></p>
            </div>
            <?php else: ?>
            <div id="vs08seo-preview" style="display:none" class="vs08seo-preview">
                <p class="vs08seo-preview-title" id="vs08seo-prev-title"></p>
                <p class="vs08seo-preview-url"><?php echo esc_html(str_replace(['https://', 'http://'], '', get_permalink($post->ID))); ?></p>
                <p class="vs08seo-preview-desc" id="vs08seo-prev-desc"></p>
            </div>
            <?php endif; ?>
        </div>

        <script>
        function vs08seoCount(el, counterId, max) {
            var len = el.value.length;
            var counter = document.getElementById(counterId);
            if (!counter) return;
            counter.textContent = len + '/' + max + ' caractères';
            counter.className = 'vs08seo-chars ' + (len <= max ? 'ok' : 'over');
            // Mise à jour prévisualisation
            if (el.id === 'vs08seo-title') {
                var prev = document.getElementById('vs08seo-prev-title');
                if (prev) prev.textContent = el.value + ' | Voyages Sortir 08';
            }
            if (el.id === 'vs08seo-desc') {
                var prev = document.getElementById('vs08seo-prev-desc');
                if (prev) prev.textContent = el.value;
            }
        }
        function vs08seoUpdateKeywords(val) {
            var tags = document.getElementById('vs08seo-keywords-tags');
            if (!tags) return;
            tags.innerHTML = '';
            val.split(',').forEach(function(kw) {
                kw = kw.trim();
                if (!kw) return;
                var span = document.createElement('span');
                span.className = 'vs08seo-keyword';
                span.textContent = kw;
                tags.appendChild(span);
            });
        }
        </script>
        <?php
    }

    /**
     * Sauvegarde les modifications manuelles du SEO.
     */
    public static function save_manual_edits(int $post_id, \WP_Post $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!in_array($post->post_type, VS08_SEO_POST_TYPES)) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (!isset($_POST['vs08_seo'])) return;

        $input = $_POST['vs08_seo'];
        // Ne sauvegarder que si au moins le titre est rempli (évite d'écraser avec un formulaire vide)
        if (empty(trim($input['seo_title'] ?? ''))) return;

        $existing = get_post_meta($post_id, '_vs08_seo_data', true) ?: [];

        $updated = array_merge($existing, [
            'seo_title' => mb_substr(sanitize_text_field($input['seo_title'] ?? ''), 0, 58),
            'seo_desc'  => mb_substr(sanitize_text_field($input['seo_desc']  ?? ''), 0, 152),
            'og_title'  => mb_substr(sanitize_text_field($input['og_title']  ?? ''), 0, 68),
            'og_desc'   => mb_substr(sanitize_text_field($input['og_desc']   ?? ''), 0, 190),
            'keywords'  => sanitize_text_field($input['keywords'] ?? ''),
        ]);

        update_post_meta($post_id, '_vs08_seo_data', $updated);
        // Marquer comme généré (édition manuelle = SEO considéré comme présent)
        update_post_meta($post_id, '_vs08_seo_generated', time());
    }
}
