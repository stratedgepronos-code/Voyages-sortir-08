<?php
/**
 * VS08 Séjours — Scanner IA pour hôtels all-inclusive
 * Recherche web via Claude API → remplit automatiquement la fiche hôtel
 * Réutilise la clé API de VS08V_HotelScanner
 */
if (!defined('ABSPATH')) exit;

class VS08S_HotelScanner {

    public static function register() {
        add_action('wp_ajax_vs08s_scan_hotel', [__CLASS__, 'ajax_scan']);
    }

    /**
     * Recherche par nom d'hôtel — Claude fait une recherche web
     * et retourne un JSON ultra-complet adapté séjours all-inclusive.
     */
    public static function ajax_scan() {
        check_ajax_referer('vs08v_scan_hotel', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Permission refusée');

        $hotel_name  = sanitize_text_field($_POST['hotel_name'] ?? '');
        $destination = sanitize_text_field($_POST['destination'] ?? '');
        if (!$hotel_name) wp_send_json_error('Nom de l\'hôtel requis.');

        $query = $hotel_name . ($destination ? ', ' . $destination : '');

        // Cache 48h
        $cache_key = 'vs08s_hotel_scan_' . md5($query);
        $cached = get_transient($cache_key);
        if ($cached !== false && is_array($cached)) {
            wp_send_json_success($cached);
            return;
        }

        // Clé API — réutilise celle du golf
        $api_key = '';
        if (defined('VS08V_CLAUDE_KEY') && !empty(VS08V_CLAUDE_KEY)) {
            $api_key = VS08V_CLAUDE_KEY;
        } elseif (class_exists('VS08V_HotelScanner')) {
            $api_key = VS08V_HotelScanner::API_KEY;
        }
        if (empty($api_key)) {
            wp_send_json_error('Clé API Claude non configurée.');
        }

        $schema = self::get_schema();

        $prompt = <<<PROMPT
Tu es un assistant spécialisé en fiches hôtelières pour une agence de voyages spécialisée en **séjours all-inclusive et forfaits balnéaires** (pas de golf).

**Mission :** Recherche sur le web des informations COMPLÈTES et DÉTAILLÉES sur cet établissement : « {$query} ».

**Ordre de recherche obligatoire :**
1. Site officiel de l'hôtel (accueil, chambres, équipements, restaurants, spa, plage, animations, photos)
2. Booking.com, TripAdvisor, Trivago pour compléter les infos manquantes
3. Pages de tour-opérateurs (TUI, Club Med, Jet2, etc.) pour les formules all-inclusive

**Points PRIORITAIRES à rechercher (séjour all-inclusive) :**
- Formule all-inclusive : que comprend-elle exactement ? (boissons locales, snacks, restaurants à thème, soft drinks 24h...)
- Nombre et types de restaurants (buffet, à la carte, thématique)
- Bars (pool bar, beach bar, lobby bar...)
- Piscines (nombre, extérieure/intérieure, chauffée, enfants, toboggans)
- Plage (privée, publique, distance, transats inclus, parasols)
- Animation / spectacles / soirées
- Club enfants / ado
- Sports et activités (tennis, volley, aquagym, kayak, plongée...)
- Spa / Thalasso / Hammam
- Distance aéroport et centre-ville en km
- Nombre total de chambres
- Types de chambres avec superficie en m² (standard, supérieure, suite, familiale)
- Note TripAdvisor exacte avec URL

**Important :** Écris des descriptions LONGUES et COMMERCIALES (3-5 phrases par section). L'agence utilise ces textes directement sur son site web. Sois vendeur mais honnête.

Réponds UNIQUEMENT avec un JSON valide, sans texte avant ou après :

{$schema}

Règles :
- "desc" : description LONGUE et commerciale de l'hôtel (5-8 phrases), donne envie
- "desc_courte" : accroche en 1-2 phrases pour le bandeau produit
- "equipements" : TOUS les équipements confirmés par tes recherches
- "restaurants" : tableau avec chaque restaurant (nom, type, cuisine, description)
- "bars" : tableau avec chaque bar
- "piscines" : tableau avec chaque piscine
- "plage" : détails de la plage
- "animations" : description des animations jour et soir
- "spa" : détails du spa/thalasso si existant
- "chambres" : chaque type avec dispo, superficie m², description détaillée
- "all_inclusive_details" : ce qui est inclus/exclu dans la formule AI
- "enfants" : club enfants, âges, activités
- "sports" : activités sportives disponibles
- Ne jamais inventer. Si inconnu, laisser ""
- Note TripAdvisor : valeur exacte (ex: "4.5"), ne pas arrondir
PROMPT;

        $body = [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 4000,
            'tools'      => [['type' => 'web_search_20250305', 'name' => 'web_search']],
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        $api_response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 120,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version'  => '2023-06-01',
            ],
            'body' => json_encode($body),
        ]);

        if (is_wp_error($api_response)) {
            wp_send_json_error('Erreur API Claude : ' . $api_response->get_error_message());
        }

        $api_body = json_decode(wp_remote_retrieve_body($api_response), true);
        $code = wp_remote_retrieve_response_code($api_response);

        if ($code !== 200) {
            $err = $api_body['error']['message'] ?? 'Erreur HTTP ' . $code;
            wp_send_json_error('Claude : ' . $err);
        }

        // Extraire le texte de la réponse
        $text = '';
        foreach ($api_body['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') $text .= $block['text'];
        }

        // Si Claude a utilisé la recherche web mais pas encore retourné le JSON
        if (empty(trim($text))) {
            $has_tool = false;
            foreach ($api_body['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'tool_use') { $has_tool = true; break; }
            }
            if ($has_tool) {
                // Relancer avec l'historique pour obtenir le JSON
                $api2 = wp_remote_post('https://api.anthropic.com/v1/messages', [
                    'timeout' => 90,
                    'headers' => [
                        'Content-Type'      => 'application/json',
                        'x-api-key'         => $api_key,
                        'anthropic-version'  => '2023-06-01',
                    ],
                    'body' => json_encode([
                        'model'      => 'claude-haiku-4-5-20251001',
                        'max_tokens' => 4000,
                        'messages'   => [
                            ['role' => 'user', 'content' => $prompt],
                            ['role' => 'assistant', 'content' => $api_body['content']],
                            ['role' => 'user', 'content' => 'Maintenant réponds UNIQUEMENT avec le JSON complet demandé. Pas de commentaire, pas de markdown.'],
                        ],
                    ]),
                ]);
                if (!is_wp_error($api2)) {
                    $body2 = json_decode(wp_remote_retrieve_body($api2), true);
                    foreach ($body2['content'] ?? [] as $block) {
                        if (($block['type'] ?? '') === 'text') $text .= $block['text'];
                    }
                }
            }
        }

        if (empty(trim($text))) {
            wp_send_json_error('Aucune réponse de Claude.');
        }

        // Extraire le JSON
        preg_match('/\{[\s\S]*\}/', $text, $matches);
        if (empty($matches[0])) wp_send_json_error('JSON introuvable.');

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) wp_send_json_error('JSON malformé : ' . json_last_error_msg());

        // Cacher 48h
        set_transient($cache_key, $data, 48 * 3600);

        wp_send_json_success($data);
    }

    private static function get_schema() {
        return '{
  "nom": "Nom officiel de l\'hotel",
  "etoiles": 5,
  "desc": "Description commerciale LONGUE de l\'hotel (5-8 phrases). Donne envie, decris l\'ambiance, le style, les points forts.",
  "desc_courte": "Accroche 1-2 phrases pour le bandeau produit",
  "adresse": "Adresse complete",
  "pays": "Pays",
  "pension": "ai",
  "type_etab": "hotel|resort|club|riad",
  "nb_chambres_total": "",
  "tripadvisor_note": "4.5",
  "tripadvisor_url": "https://...",
  "dist_aero": "25",
  "dist_centre": "10",
  "dist_plage": "0",
  "loc_desc": "Description de la localisation (2-3 phrases)",
  "all_inclusive_details": "Description detaillee de ce qui est inclus dans la formule all inclusive (3-5 phrases)",
  "equipements": ["piscine_ext","piscine_int","spa","hammam","fitness","restaurant","bar","wifi","clim","plage_privee","animation","kids_club","tennis","aquagym","plongee","kayak","volley","disco","boutique","parking","navette"],
  "restaurants": [
    {"nom":"","type":"buffet|carte|thematique","cuisine":"","desc":"Description 2-3 phrases"}
  ],
  "bars": [
    {"nom":"","type":"pool_bar|beach_bar|lobby_bar|snack","desc":""}
  ],
  "piscines": [
    {"type":"exterieure|interieure|enfants|toboggan","desc":"","chauffee":"oui|non"}
  ],
  "plage": {
    "type":"privee|publique|mixte",
    "distance":"Sur place|50m|...",
    "transats":"inclus|payant",
    "desc":"Description 2-3 phrases"
  },
  "animations": {
    "jour":"Description des activites de jour",
    "soir":"Description des spectacles/soirees",
    "desc":"Description generale 2-3 phrases"
  },
  "spa": {
    "nom":"",
    "superficie":"",
    "desc":"Description 2-3 phrases",
    "soins":"Types de soins proposes"
  },
  "enfants": {
    "club":"oui|non",
    "ages":"4-12 ans",
    "desc":"Description du club enfants"
  },
  "sports": ["tennis","volley","aquagym","kayak","plongee","football","basketball","ping-pong","petanque","tir_arc","mini_golf"],
  "chambres": {
    "standard": {"dispo":"1","superficie":"28","desc":"Description 2-3 phrases"},
    "superieure": {"dispo":"0","superficie":"","desc":""},
    "suite": {"dispo":"0","superficie":"","desc":""},
    "familiale": {"dispo":"0","superficie":"","desc":""}
  },
  "inclus_list": "Vol aller-retour\\nHebergement en chambre standard\\nFormule All Inclusive\\nTransferts aeroport-hotel\\nAnimation jour et soir",
  "non_inclus_list": "Excursions optionnelles\\nSoins spa\\nBoissons premium\\nAssurance voyage"
}';
    }
}
