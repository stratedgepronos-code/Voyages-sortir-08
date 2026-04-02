<?php
if (!defined('ABSPATH')) exit;

/**
 * Affiche les FAQ SEO en contenu visible (aligné avec le JSON-LD FAQPage).
 * Séjours : ajout après the_content. Circuits : hook vs08_seo_faq dans le template single.
 */
class VS08_SEO_Front {

    public static function register() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_filter('the_content', [__CLASS__, 'append_faq_voyage'], 18);
        add_action('vs08_seo_faq', [__CLASS__, 'action_render_faq']);
    }

    public static function enqueue_assets() {
        if (!is_singular(VS08_SEO_POST_TYPES)) return;
        $pid = get_queried_object_id();
        if (!$pid) return;
        $seo = get_post_meta($pid, '_vs08_seo_data', true);
        $faq = (is_array($seo) && !empty($seo['faq']) && is_array($seo['faq'])) ? $seo['faq'] : [];
        if (empty($faq)) return;
        wp_enqueue_style('vs08-seo-faq', VS08_SEO_URL . 'assets/faq-visibility.css', [], VS08_SEO_VER);
    }

    public static function action_render_faq(): void {
        $pid = get_the_ID();
        if (!$pid) return;
        echo self::render_faq_html($pid);
    }

    /**
     * @return string HTML échappé
     */
    public static function render_faq_html(int $post_id): string {
        if (!in_array(get_post_type($post_id), VS08_SEO_POST_TYPES, true)) {
            return '';
        }
        $seo = get_post_meta($post_id, '_vs08_seo_data', true);
        if (!is_array($seo) || empty($seo['faq']) || !is_array($seo['faq'])) {
            return '';
        }
        $faq = $seo['faq'];
        ob_start();
        ?>
        <section class="vs08-seo-faq" aria-labelledby="vs08-seo-faq-heading">
            <h2 id="vs08-seo-faq-heading" class="vs08-seo-faq__title">Questions fréquentes</h2>
            <div class="vs08-seo-faq__list">
                <?php foreach ($faq as $pair):
                    $q = isset($pair['question']) ? sanitize_text_field($pair['question']) : '';
                    $a_raw = isset($pair['answer']) ? sanitize_textarea_field($pair['answer']) : '';
                    if ($q === '' || $a_raw === '') {
                        continue;
                    }
                    $a = wp_kses_post(wpautop(esc_html($a_raw)));
                    ?>
                <details class="vs08-seo-faq__item">
                    <summary class="vs08-seo-faq__q"><?php echo esc_html($q); ?></summary>
                    <div class="vs08-seo-faq__a"><?php echo $a; ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function append_faq_voyage(string $content): string {
        if (!is_singular('vs08_voyage') || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        return $content . self::render_faq_html(get_the_ID());
    }
}
