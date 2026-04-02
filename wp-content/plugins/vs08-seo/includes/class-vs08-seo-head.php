<?php
if (!defined('ABSPATH')) exit;

class VS08_SEO_Head {

    public static function register() {
        add_filter('pre_get_document_title', [__CLASS__, 'filter_title'], 20);
        add_action('wp_head', [__CLASS__, 'output_meta'], 1);
        add_filter('wpseo_title', [__CLASS__, 'maybe_override_yoast_title']);
        add_filter('wpseo_metadesc', [__CLASS__, 'maybe_override_yoast_desc']);
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

    /** URL page résultats + filtre type (cohérent avec le thème). */
    private static function category_url_and_name(\WP_Post $post): array {
        $base = home_url('/resultats-recherche');
        if ($post->post_type === 'vs08_voyage') {
            $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($post->ID) : [];
            $type = $m['type_voyage'] ?? 'sejour_golf';
            $url  = add_query_arg(['type' => sanitize_key($type)], $base);
            if ($type === 'sejour_golf') {
                $name = 'Séjours golf';
            } else {
                $name = 'Nos séjours';
            }
            return [$url, $name];
        }
        $url = add_query_arg(['type' => 'circuit'], $base);
        return [$url, 'Circuits voyage'];
    }

    public static function filter_title($title) {
        $seo = self::get_seo();
        if (!empty($seo['seo_title'])) {
            return $seo['seo_title'] . ' | Voyages Sortir 08';
        }
        return $title;
    }

    public static function output_meta() {
        if (!self::is_our_single()) return;
        $post = get_post();
        $seo  = self::get_seo();
        if (empty($seo)) return;

        $title    = esc_attr($seo['seo_title'] ?? get_the_title());
        $desc     = esc_attr($seo['seo_desc'] ?? '');
        $og_title = esc_attr($seo['og_title'] ?? $title);
        $og_desc  = esc_attr($seo['og_desc'] ?? $desc);
        $keywords = esc_attr($seo['keywords'] ?? '');
        $url      = esc_url(get_permalink());
        $image    = esc_url(get_the_post_thumbnail_url(null, 'large') ?: '');
        $site     = 'Voyages Sortir 08';

        $published = get_post_time('c', true, $post);
        $modified  = get_post_modified_time('c', true, $post);

        if ($desc): ?>
<meta name="description" content="<?php echo $desc; ?>">
<?php endif;
        if ($keywords): ?>
<meta name="keywords" content="<?php echo $keywords; ?>">
<?php endif; ?>
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<link rel="canonical" href="<?php echo $url; ?>">

<!-- Open Graph -->
<meta property="og:type" content="product">
<meta property="og:title" content="<?php echo $og_title; ?>">
<meta property="og:description" content="<?php echo $og_desc; ?>">
<meta property="og:url" content="<?php echo $url; ?>">
<meta property="og:site_name" content="<?php echo esc_attr($site); ?>">
<meta property="og:locale" content="fr_FR">
<meta property="article:published_time" content="<?php echo esc_attr($published); ?>">
<meta property="article:modified_time" content="<?php echo esc_attr($modified); ?>">
<?php if ($image): ?>
<meta property="og:image" content="<?php echo $image; ?>">
<meta property="og:image:secure_url" content="<?php echo $image; ?>">
<meta property="og:image:alt" content="<?php echo esc_attr($seo['schema_name'] ?? get_the_title()); ?>">
<?php endif; ?>

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $og_title; ?>">
<meta name="twitter:description" content="<?php echo $og_desc; ?>">
<?php if ($image): ?>
<meta name="twitter:image" content="<?php echo $image; ?>">
<?php endif; ?>

<?php
        $post_type = $post->post_type;
        $prix      = 0.0;
        $pays      = '';
        $image_url = get_the_post_thumbnail_url(null, 'large') ?: '';

        if ($post_type === 'vs08_voyage' && class_exists('VS08V_MetaBoxes')) {
            $m    = VS08V_MetaBoxes::get($post->ID);
            $prix = floatval($m['prix_double'] ?? 0);
            $pays = trim((string) ($m['pays'] ?? ''));
        } elseif ($post_type === 'vs08_circuit' && class_exists('VS08C_Meta')) {
            $m    = VS08C_Meta::get($post->ID);
            $prix = floatval($m['prix_double'] ?? 0);
            $pays = trim((string) ($m['pays'] ?? ''));
        }

        if ($prix > 0) {
            echo '<meta property="product:price:amount" content="' . esc_attr((string) $prix) . '">' . "\n";
            echo '<meta property="product:price:currency" content="EUR">' . "\n";
        }

        [$bc_url, $bc_name] = self::category_url_and_name($post);

        $trip_id = $url . '#touristtrip';
        $faq_id  = $url . '#faq';
        $bc_id   = $url . '#breadcrumb';

        $tourist_trip = [
            '@type'        => 'TouristTrip',
            '@id'          => $trip_id,
            'name'         => $seo['schema_name'] ?? get_the_title(),
            'description'  => $seo['schema_desc'] ?? ($seo['seo_desc'] ?? get_the_title()),
            'url'          => get_permalink(),
            'inLanguage'   => 'fr-FR',
            'provider'     => [
                '@type' => 'TravelAgency',
                'name'  => $site,
                'url'   => home_url('/'),
            ],
        ];

        if ($post_type === 'vs08_voyage') {
            $tourist_trip['touristType'] = 'Golfeurs et accompagnants';
        }

        if ($pays !== '') {
            $tourist_trip['touristDestination'] = [
                '@type' => 'Place',
                'name'  => $pays,
            ];
        }

        if ($image_url) {
            $tourist_trip['image'] = [$image_url];
        }

        if ($prix > 0) {
            $tourist_trip['offers'] = [
                '@type'             => 'Offer',
                'name'              => $seo['schema_name'] ?? get_the_title(),
                'description'       => mb_substr($seo['schema_desc'] ?? ($seo['seo_desc'] ?? ''), 0, 320),
                'priceCurrency'     => 'EUR',
                'price'             => $prix,
                'availability'      => 'https://schema.org/InStock',
                'url'               => get_permalink(),
                'priceValidUntil'   => gmdate('Y-m-d', strtotime('+1 year')),
                'itemCondition'     => 'https://schema.org/NewCondition',
                'seller'            => [
                    '@type' => 'TravelAgency',
                    'name'  => $site,
                    'url'   => home_url('/'),
                ],
            ];
        }

        if (!empty($seo['keywords'])) {
            $tourist_trip['keywords'] = $seo['keywords'];
        }

        $graph = [$tourist_trip];

        $breadcrumb = [
            '@type'           => 'BreadcrumbList',
            '@id'             => $bc_id,
            'itemListElement' => [
                [
                    '@type'    => 'ListItem',
                    'position' => 1,
                    'name'     => 'Accueil',
                    'item'     => home_url('/'),
                ],
                [
                    '@type'    => 'ListItem',
                    'position' => 2,
                    'name'     => $bc_name,
                    'item'     => $bc_url,
                ],
                [
                    '@type'    => 'ListItem',
                    'position' => 3,
                    'name'     => $seo['schema_name'] ?? get_the_title(),
                    'item'     => get_permalink(),
                ],
            ],
        ];
        $graph[] = $breadcrumb;

        $faq_list = isset($seo['faq']) && is_array($seo['faq']) ? $seo['faq'] : [];
        if (!empty($faq_list)) {
            $main_entity = [];
            foreach ($faq_list as $pair) {
                if (empty($pair['question']) || empty($pair['answer'])) {
                    continue;
                }
                $main_entity[] = [
                    '@type'          => 'Question',
                    'name'           => $pair['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $pair['answer'],
                    ],
                ];
            }
            if (!empty($main_entity)) {
                $graph[] = [
                    '@type'       => 'FAQPage',
                    '@id'         => $faq_id,
                    'mainEntity'  => $main_entity,
                ];
            }
        }

        $graph = apply_filters('vs08_seo_jsonld_graph', $graph, $post, $seo);

        $payload = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }

    public static function maybe_override_yoast_title($title) {
        $seo = self::get_seo();
        return !empty($seo['seo_title']) ? $seo['seo_title'] . ' | Voyages Sortir 08' : $title;
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
