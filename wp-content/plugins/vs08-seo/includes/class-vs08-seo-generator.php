<?php
if (!defined('ABSPATH')) exit;

/**
 * Génération SEO via Claude — orientée référencement golf / circuits (intentions de recherche, SERP, rich results).
 */
class VS08_SEO_Generator {

    const CLAUDE_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    /** Modèle par défaut : qualité rédaction SEO (filtre vs08_seo_claude_model pour surcharger). */
    const MODEL_DEFAULT   = 'claude-sonnet-4-20250514';
    const MAX_TOKENS      = 1800;

    /** Labels « compris » alignés sur VS08V_ComprisBox (pour enrichir le prompt). */
    private static function compris_labels(): array {
        return [
            'vol'               => 'Vol aller-retour',
            'transfert_groupe'  => 'Transferts aéroport / hôtel groupés',
            'transfert_prive'   => 'Transferts privés',
            'location_vehicule' => 'Location de véhicule',
            'hebergement'       => 'Hébergement',
            'petit_dej'         => 'Petits-déjeuners',
            'demi_pension'      => 'Demi-pension',
            'tout_inclus'       => 'Formule tout inclus',
            'greenfees'         => 'Green fees / parcours',
            'buggy'             => 'Buggy / chariot',
            'assurance'         => 'Assurance',
            'encadrement'       => 'Encadrement / guide',
            'taxes'             => 'Taxes incluses',
            'welcome'           => 'Accueil / cocktail',
            'navette_golfs'     => 'Navette hôtel-golfs',
        ];
    }

    private static function type_voyage_label(string $slug): string {
        $map = [
            'sejour_golf' => 'Séjour golfique',
            'sejour'      => 'Séjour',
            'road_trip'   => 'Road trip',
            'circuit'     => 'Circuit',
            'city_trip'   => 'City trip',
        ];
        return $map[$slug] ?? $slug;
    }

    /**
     * Génère et sauvegarde les données SEO pour un produit.
     * @return true|WP_Error
     */
    public static function generate_and_save(int $post_id) {
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, VS08_SEO_POST_TYPES)) {
            return new WP_Error('invalid_post', 'Post invalide ou type non supporté.');
        }

        $data = self::build_product_data($post);
        $seo  = self::call_claude($post->post_type, $data);

        if (is_wp_error($seo)) return $seo;

        update_post_meta($post_id, '_vs08_seo_data',      $seo);
        update_post_meta($post_id, '_vs08_seo_generated', time());

        return true;
    }

    /**
     * Collecte les données clés du produit selon son type.
     */
    private static function build_product_data(\WP_Post $post): array {
        $type = $post->post_type;
        $body    = trim(wp_strip_all_tags($post->post_content));
        $excerpt = trim(wp_strip_all_tags($post->post_excerpt ?? ''));
        $chunks  = array_filter([$body, ($excerpt !== '' && $excerpt !== $body) ? $excerpt : '']);

        if ($type === 'vs08_circuit' && class_exists('VS08C_Meta')) {
            $mc = VS08C_Meta::get($post->ID);
            $md = trim(wp_strip_all_tags($mc['description'] ?? ''));
            if ($md !== '') {
                $chunks[] = $md;
            }
            $pf = array_filter(array_map('trim', explode("\n", $mc['points_forts'] ?? '')));
            if (!empty($pf)) {
                $chunks[] = 'Points forts : ' . implode(' · ', array_slice($pf, 0, 6));
            }
        }

        $merged_desc = implode("\n\n", array_unique($chunks));
        if ($merged_desc === '') {
            $merged_desc = $post->post_title;
        }

        $site_name = get_bloginfo('name') ?: 'Voyages Sortir 08';
        $data      = [
            'titre'       => $post->post_title,
            'description' => mb_substr($merged_desc, 0, 3500),
            'url'         => get_permalink($post->ID),
            'image'       => get_the_post_thumbnail_url($post->ID, 'large') ?: '',
            'site_name'   => $site_name,
            'site_url'    => home_url('/'),
        ];

        if ($type === 'vs08_voyage' && class_exists('VS08V_MetaBoxes')) {
            $m = VS08V_MetaBoxes::get($post->ID);
            $labels = self::compris_labels();
            $compris_human = [];
            foreach ($m['compris']['oui'] ?? [] as $slug) {
                $slug = is_string($slug) ? $slug : '';
                if ($slug && isset($labels[$slug])) {
                    $compris_human[] = $labels[$slug];
                } elseif ($slug) {
                    $compris_human[] = $slug;
                }
            }
            $aeroports_list = [];
            foreach ($m['aeroports'] ?? [] as $a) {
                $code = strtoupper($a['code'] ?? '');
                if ($code) $aeroports_list[] = $code;
            }
            $tv = $m['type_voyage'] ?? 'sejour_golf';
            $data = array_merge($data, [
                'type'            => self::type_voyage_label($tv),
                'type_slug'       => $tv,
                'destination'     => $m['destination'] ?? '',
                'pays'            => $m['pays'] ?? '',
                'duree_nuits'     => (string) ($m['duree'] ?? '7'),
                'duree'           => ($m['duree'] ?? '7') . ' nuits',
                'prix_appel'      => $m['prix_double'] ?? '',
                'hotel'           => $m['hotel_nom'] ?? '',
                'nb_parcours'     => (string) ($m['nb_parcours'] ?? ''),
                'prix_greenfees'  => (string) ($m['prix_greenfees'] ?? ''),
                'licence_ffgolf'  => $m['licence_ffgolf'] ?? '',
                'aeroports'       => implode(', ', $aeroports_list),
                'compris'         => implode(', ', $compris_human),
            ]);
        } elseif ($type === 'vs08_circuit' && class_exists('VS08C_Meta')) {
            $m = VS08C_Meta::get($post->ID);
            $aeroports_list = [];
            foreach ($m['aeroports'] ?? [] as $a) {
                $code = strtoupper($a['code'] ?? '');
                if ($code) $aeroports_list[] = $code;
            }
            $data = array_merge($data, [
                'type'        => 'Circuit voyage',
                'type_slug'   => 'circuit',
                'destination' => $m['destination'] ?? '',
                'pays'        => $m['pays'] ?? '',
                'duree'       => ($m['duree_jours'] ?? '8') . ' jours / ' . ($m['duree'] ?? '7') . ' nuits',
                'prix_appel'  => $m['prix_double'] ?? '',
                'formule'     => $m['pension'] ?? '',
                'transport'   => $m['transport'] ?? '',
                'iata_dest'   => strtoupper($m['iata_dest'] ?? ''),
                'aeroports'   => implode(', ', $aeroports_list),
            ]);
        }

        return $data;
    }

    /**
     * Appelle Claude pour générer les données SEO.
     * @return array|WP_Error
     */
    private static function call_claude(string $post_type, array $data) {
        $api_key = defined('VS08V_CLAUDE_KEY') ? VS08V_CLAUDE_KEY : '';
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Clé Claude API non configurée (VS08V_CLAUDE_KEY).');
        }

        $prix_txt   = !empty($data['prix_appel']) ? 'À partir de ' . $data['prix_appel'] . ' € par personne (indicatif).' : '';
        $brand      = $data['site_name'] ?? 'Voyages Sortir 08';
        $brand_url  = $data['site_url'] ?? '';

        if ($post_type === 'vs08_voyage') {
            $parcours_txt = !empty($data['nb_parcours']) ? $data['nb_parcours'] . ' parcours' : 'non précisé';
            $gf_txt       = !empty($data['prix_greenfees']) ? 'Forfait green fees indicatif : ' . $data['prix_greenfees'] . ' € / golfeur.' : '';
            $licence_txt  = !empty($data['licence_ffgolf']) ? 'Licence FFGolf : ' . $data['licence_ffgolf'] . '.' : '';

            $prompt = <<<PROMPT
Tu es consultant SEO senior (marché France, Google.fr) spécialisé voyage golf et agences réceptives.

CADRE QUALITÉ (2025–2026) — à respecter strictement
- **Contenu utile** : répondre à l’intention réelle (partir en séjour golf, comprendre le forfait, le niveau requis, ce qui est inclus). Pas de texte générique interchangeable entre deux destinations.
- **E-E-A-T** : ton expert, précis, transparent sur ce qui est factuel (données fournies) vs ce qui est à confirmer. Pas de faux témoignages ni d’avis inventés.
- **Interdit** : « meilleur », « n°1 », « miracle », garanties absolues, prix trompeurs, répétitions mécaniques du même mot-clé, MAJUSCULES agressives, emoji dans seo_title/seo_desc/schema.
- **IA** : le texte doit sembler rédigé par un humain de métier ; varier la syntaxe ; intégrer des entités (nom du pays, de la destination, de l’hôtel si fourni, parcours, vols).

MARQUE (pour contexte — ne pas mettre le nom commercial dans seo_title)
- Agence : {$brand}
- Site : {$brand_url}

OBJECTIF RÉFÉRENCEMENT
- Capter les intentions : « séjour golf [destination/pays] », « voyage golf organisé », « package golf vols + hôtel », « green fees [zone] », golfeurs France partant de [aéroports indiqués].

DONNÉES PRODUIT (séjour)
- Titre catalogue : {$data['titre']}
- Type : {$data['type']}
- Destination / zone : {$data['destination']}
- Pays : {$data['pays']}
- Durée : {$data['duree']}
- Prix : {$prix_txt}
- Hôtel / hébergement : {$data['hotel']}
- Nombre de parcours (indication) : {$parcours_txt}
- {$gf_txt}
- {$licence_txt}
- Aéroports de départ possibles : {$data['aeroports']}
- Prestations « compris » (à exploiter dans les FAQ et la description) : {$data['compris']}
- Texte descriptif (extrait) :
"""{$data['description']}"""

RÈGLES SEO (obligatoires)
1) **seo_title** (≤58 car.) : requête principale en tête — dans les **28 premiers caractères**, inclure **« golf »** ou **« séjour golf »** + **destination ou pays**. Forme naturelle (tiret ou « · » possible). Zéro nom d’agence / marque.
2) **seo_desc** (≤152 car.) : ligne 1 = **valeur concrète** tirée des données (durée, ce qui est compris si pertinent, prix indicatif si fourni). Ligne 2 courte ou fin de phrase = **CTA** (Réserver, Voir les dates, Demander un devis). **1 entité** supplémentaire (ex. parcours, vol, transfert) sans keyword stuffing.
3) **og_title** (≤68 car.) : plus engageant pour le partage social, sans clickbait mensonger.
4) **og_desc** (≤190 car.) : ambiance + réassurance (organisation, étapes clés du séjour golf).
5) **keywords** : 10 à 16 termes FR, séparés par des virgules — mix **tête de requête** + **longue traîne** + **variantes** (séjour golf, voyage golf, forfait, green fee, destination, pays, départ BVA/CDG si aéroports listés). Pas de doublons.
6) **schema_name** : titre produit factuel (peut reprendre le titre catalogue légèrement épuré).
7) **schema_desc** : 2 à 4 phrases, factuelles, utiles au moteur (résumé du produit + public cible golfeurs/accompagnants). Pas de superlatifs vides.
8) **FAQ** : exactement **3** Q/R en français. Questions qu’un golfeur tape vraiment (inclus / non inclus, handicap, matériel, assurance, départs aéroports). Réponses ≤3 phrases ; si donnée absente : orienter vers l’agence sans inventer.

FORMAT DE SORTIE
Réponds UNIQUEMENT par un JSON valide UTF-8, sans markdown, sans commentaires :
{"seo_title":"...","seo_desc":"...","og_title":"...","og_desc":"...","keywords":"...","schema_name":"...","schema_desc":"...","faq":[{"question":"...","answer":"..."},{"question":"...","answer":"..."},{"question":"...","answer":"..."}]}
PROMPT;
        } else {
            $prompt = <<<PROMPT
Tu es consultant SEO senior (France, Google.fr) spécialisé circuits et voyages organisés.

CADRE QUALITÉ (2025–2026)
- Contenu **utile et spécifique** à l’itinéraire / pays (pas un texte générique « beau voyage »).
- **E-E-A-T** : précis, honnête, pas de promesses impossibles ; pas d’avis ou notes inventés.
- **Interdit** : meilleur, n°1, miracle, prix trompeurs, bourrage de mots-clés, CAPS abusives, emoji dans seo_title/seo_desc/schema.

MARQUE (contexte — pas dans seo_title)
- {$brand} — {$brand_url}

OBJECTIF
- Intentions : « circuit [destination/pays] », « voyage organisé », « séjour avec guide », « tout compris » (seulement si cohérent avec les données).

DONNÉES PRODUIT (circuit)
- Titre : {$data['titre']}
- Destination / itinéraire : {$data['destination']}
- Pays : {$data['pays']}
- Durée : {$data['duree']}
- Prix : {$prix_txt}
- Pension / formule : {$data['formule']}
- Transport : {$data['transport']}
- Aéroports départ : {$data['aeroports']}
- Texte descriptif (extrait) :
"""{$data['description']}"""

RÈGLES SEO
1) **seo_title** (≤58 car.) : dans les **28 premiers caractères**, « circuit » ou « voyage » + **destination ou pays**. Pas de nom d’agence.
2) **seo_desc** (≤152 car.) : durée + expérience concrète (transport, pension si connue) + CTA.
3) **og_title** / **og_desc** : même esprit, ton partage social.
4) **keywords** : 10 à 16 termes FR (circuit, voyage organisé, destination, pays, guide, vol, pension…).
5) **schema_name** / **schema_desc** : factuels, 2–4 phrases pour schema_desc.
6) **FAQ** : **3** Q/R (niveau physique, bagages, visa/formalités si pays exotique, repas, taille du groupe…) — réponses courtes ; pas d’invention.

FORMAT : JSON seul, sans markdown :
{"seo_title":"...","seo_desc":"...","og_title":"...","og_desc":"...","keywords":"...","schema_name":"...","schema_desc":"...","faq":[{"question":"...","answer":"..."},{"question":"...","answer":"..."},{"question":"...","answer":"..."}]}
PROMPT;
        }

        $model = apply_filters('vs08_seo_claude_model', self::MODEL_DEFAULT);

        $response = wp_remote_post(self::CLAUDE_ENDPOINT, [
            'timeout' => 90,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model'      => $model,
                'max_tokens' => self::MAX_TOKENS,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Erreur réseau Claude : ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            $msg = $body['error']['message'] ?? 'Erreur HTTP ' . $code;
            return new WP_Error('api_error', 'Claude API : ' . $msg);
        }

        $raw_text = '';
        foreach ($body['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $raw_text .= $block['text'];
            }
        }

        if (preg_match('/\{[\s\S]*\}/', $raw_text, $m)) {
            $seo = json_decode($m[0], true);
        } else {
            $seo = json_decode($raw_text, true);
        }

        if (!is_array($seo) || empty($seo['seo_title'])) {
            return new WP_Error('parse_error', 'Réponse Claude invalide : ' . substr($raw_text, 0, 200));
        }

        $faq_clean = self::sanitize_faq($seo['faq'] ?? []);

        return [
            'seo_title'   => mb_substr(sanitize_text_field($seo['seo_title'] ?? ''), 0, 58),
            'seo_desc'    => mb_substr(sanitize_text_field($seo['seo_desc'] ?? ''), 0, 152),
            'og_title'    => mb_substr(sanitize_text_field($seo['og_title'] ?? $seo['seo_title']), 0, 68),
            'og_desc'     => mb_substr(sanitize_text_field($seo['og_desc'] ?? $seo['seo_desc']), 0, 190),
            'keywords'    => sanitize_text_field($seo['keywords'] ?? ''),
            'schema_name' => sanitize_text_field($seo['schema_name'] ?? $seo['seo_title']),
            'schema_desc' => sanitize_textarea_field($seo['schema_desc'] ?? $seo['seo_desc']),
            'faq'         => $faq_clean,
        ];
    }

    /**
     * @param mixed $faq_raw
     * @return array<int, array{question:string, answer:string}>
     */
    private static function sanitize_faq($faq_raw): array {
        if (!is_array($faq_raw)) {
            return [];
        }
        $out = [];
        foreach (array_slice($faq_raw, 0, 5) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $q = sanitize_text_field($item['question'] ?? $item['q'] ?? '');
            $a = sanitize_textarea_field($item['answer'] ?? $item['a'] ?? '');
            $a = mb_substr(preg_replace('/\s+/u', ' ', trim($a)), 0, 500);
            if ($q !== '' && $a !== '') {
                $out[] = ['question' => mb_substr($q, 0, 120), 'answer' => $a];
            }
        }
        return $out;
    }
}
