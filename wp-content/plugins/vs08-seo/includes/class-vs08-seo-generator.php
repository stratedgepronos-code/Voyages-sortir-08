<?php
if (!defined('ABSPATH')) exit;

class VS08_SEO_Generator {

    const CLAUDE_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    const MODEL           = 'claude-haiku-4-5-20251001'; // rapide + économique pour du SEO
    const MAX_TOKENS      = 600;

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
            'description' => wp_strip_all_tags($post->post_content),
            'url'         => get_permalink($post->ID),
            'image'       => get_the_post_thumbnail_url($post->ID, 'large') ?: '',
        ];

        if ($type === 'vs08_voyage' && class_exists('VS08V_MetaBoxes')) {
            $m = VS08V_MetaBoxes::get($post->ID);
            // Rassembler les compris cochés
            $compris_checked = [];
            foreach ($m['compris'] ?? [] as $key => $val) {
                if ($val) $compris_checked[] = $key;
            }
            // Airports list
            $aeroports_list = [];
            foreach ($m['aeroports'] ?? [] as $a) {
                $code = strtoupper($a['code'] ?? '');
                if ($code) $aeroports_list[] = $code;
            }
            $data = array_merge($data, [
                'type'        => 'Séjour golf',
                'destination' => $m['destination'] ?? '',
                'pays'        => $m['pays']         ?? '',
                'duree'       => ($m['duree'] ?? '7') . ' nuits',
                'prix_appel'  => $m['prix_double']   ?? '',
                'hotel'       => $m['hotel_nom']     ?? '',
                'aeroports'   => implode(', ', $aeroports_list),
                'compris'     => implode(', ', $compris_checked),
            ]);
        } elseif ($type === 'vs08_circuit' && class_exists('VS08C_Meta')) {
            $m = VS08C_Meta::get($post->ID);
            $aeroports_list = [];
            foreach ($m['aeroports'] ?? [] as $a) {
                $code = strtoupper($a['code'] ?? '');
                if ($code) $aeroports_list[] = $code;
            }
            $data = array_merge($data, [
                'type'        => 'Circuit découverte',
                'destination' => $m['destination']  ?? '',
                'pays'        => $m['pays']          ?? '',
                'duree'       => ($m['duree_jours']  ?? '8') . ' jours / ' . ($m['duree'] ?? '7') . ' nuits',
                'prix_appel'  => $m['prix_double']   ?? '',
                'formule'     => $m['pension']        ?? '',
                'transport'   => $m['transport']      ?? '',
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

        $site_name = 'Voyages Sortir 08';
        $prix_txt  = !empty($data['prix_appel']) ? 'À partir de ' . $data['prix_appel'] . ' €.' : '';

        if ($post_type === 'vs08_voyage') {
            $prompt = <<<PROMPT
Tu es expert SEO en tourisme golf. Génère des données SEO optimisées pour ce séjour golf.

Données produit :
- Titre : {$data['titre']}
- Type : {$data['type']}
- Destination : {$data['destination']} ({$data['pays']})
- Durée : {$data['duree']}
- Prix : {$prix_txt}
- Hôtel : {$data['hotel']}
- Green fees : {$data['greenfees']}
- Aéroports départ : {$data['aeroports']}
- Compris : {$data['compris']}
- Description : """ {$data['description']} """

Règles :
- seo_title : max 60 caractères, accrocheur, contient destination + "golf" + un avantage clé. Sans le nom du site.
- seo_desc : max 155 caractères, persuasif, mentionne destination, golf, durée approximative, prix si dispo, appel à l'action.
- og_title : max 70 caractères, légèrement plus commercial que seo_title.
- og_desc : max 200 caractères, plus descriptif pour les réseaux sociaux.
- keywords : 6 à 10 mots-clés séparés par des virgules (golf + destination + voyage...).
- schema_name : nom propre du produit pour JSON-LD (identique au titre ou légèrement reformulé).
- schema_desc : description 1-2 phrases pour JSON-LD, claire et factuelle.

Réponds UNIQUEMENT en JSON valide, sans commentaire, sans markdown :
{"seo_title":"...","seo_desc":"...","og_title":"...","og_desc":"...","keywords":"...","schema_name":"...","schema_desc":"..."}
PROMPT;
        } else {
            $prompt = <<<PROMPT
Tu es expert SEO en tourisme culturel et découverte. Génère des données SEO optimisées pour ce circuit voyage.

Données produit :
- Titre : {$data['titre']}
- Type : {$data['type']}
- Destination : {$data['destination']} ({$data['pays']})
- Durée : {$data['duree']}
- Prix : {$prix_txt}
- Formule : {$data['formule']}
- Transport : {$data['transport']}
- Aéroports départ : {$data['aeroports']}
- Description : """ {$data['description']} """

Règles :
- seo_title : max 60 caractères, accrocheur, contient destination + "circuit" + un avantage clé. Sans le nom du site.
- seo_desc : max 155 caractères, persuasif, mentionne destination, durée, découverte, prix si dispo, appel à l'action.
- og_title : max 70 caractères, légèrement plus commercial que seo_title.
- og_desc : max 200 caractères, plus descriptif pour les réseaux sociaux.
- keywords : 6 à 10 mots-clés séparés par des virgules (circuit + destination + voyage...).
- schema_name : nom propre du produit pour JSON-LD.
- schema_desc : description 1-2 phrases pour JSON-LD, claire et factuelle.

Réponds UNIQUEMENT en JSON valide, sans commentaire, sans markdown :
{"seo_title":"...","seo_desc":"...","og_title":"...","og_desc":"...","keywords":"...","schema_name":"...","schema_desc":"..."}
PROMPT;
        }

        $response = wp_remote_post(self::CLAUDE_ENDPOINT, [
            'timeout' => 45,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model'      => defined('VS08V_CLAUDE_MODEL') ? VS08V_CLAUDE_MODEL : self::MODEL,
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

        // Extraire le texte de la réponse
        $raw_text = '';
        foreach ($body['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $raw_text .= $block['text'];
            }
        }

        // Extraire le JSON (peut être entouré de ```json ... ```)
        if (preg_match('/\{[\s\S]*\}/', $raw_text, $m)) {
            $seo = json_decode($m[0], true);
        } else {
            $seo = json_decode($raw_text, true);
        }

        if (!is_array($seo) || empty($seo['seo_title'])) {
            return new WP_Error('parse_error', 'Réponse Claude invalide : ' . substr($raw_text, 0, 200));
        }

        // Nettoyage et troncature de sécurité
        return [
            'seo_title'   => mb_substr(sanitize_text_field($seo['seo_title']   ?? ''), 0, 60),
            'seo_desc'    => mb_substr(sanitize_text_field($seo['seo_desc']    ?? ''), 0, 155),
            'og_title'    => mb_substr(sanitize_text_field($seo['og_title']    ?? $seo['seo_title']), 0, 70),
            'og_desc'     => mb_substr(sanitize_text_field($seo['og_desc']     ?? $seo['seo_desc']), 0, 200),
            'keywords'    => sanitize_text_field($seo['keywords']   ?? ''),
            'schema_name' => sanitize_text_field($seo['schema_name'] ?? $seo['seo_title']),
            'schema_desc' => sanitize_textarea_field($seo['schema_desc'] ?? $seo['seo_desc']),
        ];
    }
}
