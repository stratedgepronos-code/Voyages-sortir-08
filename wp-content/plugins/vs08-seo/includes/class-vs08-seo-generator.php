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
        $data = [
            'titre'       => $post->post_title,
            'description' => mb_substr(wp_strip_all_tags($post->post_content), 0, 3500),
            'url'         => get_permalink($post->ID),
            'image'       => get_the_post_thumbnail_url($post->ID, 'large') ?: '',
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

        $prix_txt = !empty($data['prix_appel']) ? 'À partir de ' . $data['prix_appel'] . ' € par personne (indicatif).' : '';

        if ($post_type === 'vs08_voyage') {
            $parcours_txt = !empty($data['nb_parcours']) ? $data['nb_parcours'] . ' parcours' : 'non précisé';
            $gf_txt       = !empty($data['prix_greenfees']) ? 'Forfait green fees indicatif : ' . $data['prix_greenfees'] . ' € / golfeur.' : '';
            $licence_txt  = !empty($data['licence_ffgolf']) ? 'Licence FFGolf : ' . $data['licence_ffgolf'] . '.' : '';

            $prompt = <<<PROMPT
Tu es le meilleur consultant SEO français spécialisé dans le voyage golf et les séjours sportifs haut de gamme (marché FR, Google.fr).

CONTEXTE BUSINESS
- Agence : Voyages Sortir 08 — spécialiste séjours golf en Europe et au-delà.
- Objectif : devenir LA référence sur les requêtes type « séjour golf [destination] », « voyage golf [pays] », « package golf tout compris », « green fees [région] », « golf vacances [ville] ».
- Le contenu doit inspirer confiance (expertise, clarté), sans promesses mensongères ni superlatifs creux interdits par Google.

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
1) Titre SEO (seo_title) : MAX 58 caractères (pas 60 — marge mobile). Le mot-clé principal (destination OU pays + golf) doit apparaître dans les **28 premiers caractères**. Formulation naturelle, une seule barre verticale ou tiret si utile. Pas le nom de l’agence. Pas de CAPS LOCK.
2) Meta description (seo_desc) : MAX 152 caractères. **Phrase 1** = bénéfice + précision (durée, type de séjour golf). **Fin** = CTA clair (Réservez, Découvrez ce séjour, etc.). Intégrer 1 variation sémantique (ex. green fee, parcours, package) sans bourrage.
3) og_title : MAX 68 car., ton légèrement plus émotionnel / social que seo_title.
4) og_desc : MAX 190 car., mettre en avant l’expérience et la sérénité (vols, transferts, parcours selon données).
5) keywords : 8 à 14 expressions **courtes**, séparées par des virgules, en français : mélange mot-clé tête (séjour golf + pays/destination) + longue traîne (vol inclus, green fees, golf [destination], voyage organisé golf…). Pas de doublons inutiles.
6) schema_name / schema_desc : pour JSON-LD, factuels, sans superlatifs marketing.
7) FAQ : exactement **3** paires question/réponse en français, pertinentes pour un golfeur qui compare les offres (ex. ce qui est inclus, niveau requis, bagages golf, flexibilité des dates si tu peux l’inférer du texte — sinon rester générique et honnête). Réponses **concises** (2–3 phrases max chacune). Si une info manque, répondre de façon prudente (« contactez-nous pour précisions »).

FORMAT DE SORTIE
Réponds UNIQUEMENT par un JSON valide UTF-8, sans markdown, sans commentaires :
{"seo_title":"...","seo_desc":"...","og_title":"...","og_desc":"...","keywords":"...","schema_name":"...","schema_desc":"...","faq":[{"question":"...","answer":"..."},{"question":"...","answer":"..."},{"question":"...","answer":"..."}]}
PROMPT;
        } else {
            $prompt = <<<PROMPT
Tu es le meilleur consultant SEO français spécialisé circuits et voyages organisés (marché FR, Google.fr).

CONTEXTE BUSINESS
- Agence : Voyages Sortir 08.
- Objectif : capter les intentions « circuit [destination] », « voyage organisé [pays] », « séjour découverte tout compris », etc.

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
1) seo_title : MAX 58 car., mot-clé principal (destination ou pays + « circuit » ou « voyage ») dans les **28 premiers caractères**. Pas le nom du site.
2) seo_desc : MAX 152 car., bénéfice + durée + CTA final.
3) og_title : MAX 68 car.
4) og_desc : MAX 190 car.
5) keywords : 8 à 14 termes, français, circuit + destination + voyage organisé + variantes.
6) schema_name / schema_desc : factuels pour données structurées.
7) FAQ : **3** Q/R utiles pour un voyageur hésitant (pension, vols, encadrement, etc.), réponses courtes et honnêtes.

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
