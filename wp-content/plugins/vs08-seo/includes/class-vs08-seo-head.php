<?php
if (!defined('ABSPATH')) exit;

class VS08_SEO_Head {

    public static function register() {
        // Titre de la page
        add_filter('pre_get_document_title', [__CLASS__, 'filter_title'], 20);
        // Balises <head>
        add_action('wp_head', [__CLASS__, 'output_meta'], 1);
        // Désactiver les doublons Yoast sur nos post types
        add_filter('wpseo_title',           [__CLASS__, 'maybe_override_yoast_title']);
        add_filter('wpseo_metadesc',        [__CLASS__, 'maybe_override_yoast_desc']);
        add_filter('rank_math/frontend/title', [__CLASS__, 'maybe_override_rank_title']);
        add_filter('rank_math/frontend/description', [__CLASS__, 'maybe_override_rank_desc']);
    }

    private static function is_our_single(): bool {
        return is_singular(VS08_SEO_POST_TYPES);
    }

    private static function get_seo(): array {
        if (!self::is_our_single()) return [];
        $seo = get_post_meta(get_the_ID(), '_vs08_seo_data', true);
        return is_array($seo) ? $seo : [];
    }

    public static function filter_title($title) {
        $seo = self::get_seo();
        if (!empty($seo['seo_title'])) {
            return $seo['seo_title'] . ' — Voyages Sortir 08';
        }
        return $title;
    }

    public static function output_meta() {
        if (!self::is_our_single()) return;
        $post = get_post();
        $seo  = self::get_seo();
        if (empty($seo)) return;

        $title       = esc_attr($seo['seo_title']   ?? get_the_title());
        $desc        = esc_attr($seo['seo_desc']     ?? '');
        $og_title    = esc_attr($seo['og_title']     ?? $title);
        $og_desc     = esc_attr($seo['og_desc']      ?? $desc);
        $keywords    = esc_attr($seo['keywords']     ?? '');
        $url         = esc_url(get_permalink());
        $image       = esc_url(get_the_post_thumbnail_url(null, 'large') ?: '');
        $site_name   = 'Voyages Sortir 08';

        // Meta description
        if ($desc): ?>
<meta name="description" content="<?php echo $desc; ?>">
<?php endif;
        // Keywords
        if ($keywords): ?>
<meta name="keywords" content="<?php echo $keywords; ?>">
<?php endif; ?>
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
<link rel="canonical" href="<?php echo $url; ?>">

<!-- Open Graph -->
<meta property="og:type" content="product">
<meta property="og:title" content="<?php echo $og_title; ?>">
<meta property="og:description" content="<?php echo $og_desc; ?>">
<meta property="og:url" content="<?php echo $url; ?>">
<meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">
<meta property="og:locale" content="fr_FR">
<?php if ($image): ?>
<meta property="og:image" content="<?php echo $image; ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $og_title; ?>">
<meta name="twitter:description" content="<?php echo $og_desc; ?>">
<?php if ($image): ?>
<meta name="twitter:image" content="<?php echo $image; ?>">
<?php endif; ?>

<!-- JSON-LD Structured Data -->
<?php
        $post_type = $post->post_type;
        $prix = '';
        $image_url = get_the_post_thumbnail_url(null, 'large') ?: '';

        if ($post_type === 'vs08_voyage' && class_exists('VS08V_MetaBoxes')) {
            $m    = VS08V_MetaBoxes::get($post->ID);
            $prix = floatval($m['prix_double'] ?? 0);
        } elseif ($post_type === 'vs08_circuit' && class_exists('VS08C_Meta')) {
            $m    = VS08C_Meta::get($post->ID);
            $prix = floatval($m['prix_double'] ?? 0);
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'TouristTrip',
            'name'        => $seo['schema_name'] ?? get_the_title(),
            'description' => $seo['schema_desc'] ?? ($seo['seo_desc'] ?? get_the_title()),
            'url'         => get_permalink(),
            'provider'    => [
                '@type' => 'TravelAgency',
                'name'  => $site_name,
                'url'   => home_url(),
            ],
        ];

        if ($image_url) {
            $schema['image'] = $image_url;
        }

        if ($prix > 0) {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'price'         => $prix,
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
                'url'           => get_permalink(),
            ];
        }

        if (!empty($seo['keywords'])) {
            $schema['keywords'] = $seo['keywords'];
        }

        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }

    // Compatibilité Yoast — laisser notre titre/desc passer
    public static function maybe_override_yoast_title($title) {
        $seo = self::get_seo();
        return !empty($seo['seo_title']) ? $seo['seo_title'] . ' — Voyages Sortir 08' : $title;
    }
    public static function maybe_override_yoast_desc($desc) {
        $seo = self::get_seo();
        return !empty($seo['seo_desc']) ? $seo['seo_desc'] : $desc;
    }
    public static function maybe_override_rank_title($title) {
        return self::maybe_override_yoast_title($title);
    }
    public static function maybe_override_rank_desc($desc) {
        return self::maybe_override_yoast_desc($desc);
    }
}
