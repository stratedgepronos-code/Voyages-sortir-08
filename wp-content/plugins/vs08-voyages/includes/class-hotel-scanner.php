<?php
/**
 * VS08 Voyages — Scanner IA d'hôtel
 * Récupère les infos d'un site hôtelier via Claude API
 */
class VS08V_HotelScanner {

    const API_KEY = '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';
    const MODEL   = 'claude-sonnet-4-20250514';

    public static function register() {
        add_action('wp_ajax_vs08v_scan_hotel',        [__CLASS__, 'ajax_scan']);
        add_action('wp_ajax_vs08v_scan_hotel_pdf',     [__CLASS__, 'ajax_scan_pdf']);
        add_action('wp_ajax_vs08v_scan_hotel_by_name', [__CLASS__, 'ajax_scan_by_name']);
        add_action('wp_ajax_vs08v_scan_golf_by_name',  [__CLASS__, 'ajax_scan_golf_by_name']);
        add_action('wp_ajax_vs08v_geo_hotel',         [__CLASS__, 'ajax_geo_hotel']);
        add_action('wp_ajax_vs08v_search_car_photo',  [__CLASS__, 'ajax_search_car_photo']);
    }

    public static function ajax_scan() {
        check_ajax_referer('vs08v_scan_hotel', 'nonce');

        $url = esc_url_raw($_POST['hotel_url'] ?? '');
        if (!$url) wp_send_json_error('URL manquante');

        // 1. Récupérer le contenu de la page hôtel
        $response = wp_remote_get($url, [
            'timeout'    => 20,
            'user-agent' => 'Mozilla/5.0 (compatible; VS08VoyagesBot/1.0)',
            'sslverify'  => false,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Impossible de charger le site : ' . $response->get_error_message());
        }

        $html    = wp_remote_retrieve_body($response);
        $code    = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            wp_send_json_error("Le site a retourné une erreur $code");
        }

        // Nettoyer le HTML : garder uniquement le texte pertinent
        $text = self::extract_text($html);

        if (strlen($text) < 100) {
            wp_send_json_error('Pas assez de contenu récupéré sur cette page.');
        }

        // Tronquer pour ne pas dépasser les limites de tokens
        $text = mb_substr($text, 0, 8000);

        // 2. Envoyer à Claude pour extraction structurée
        $prompt = <<<PROMPT
Tu es un assistant spécialisé en voyages golf et hôtellerie de luxe.

Voici le contenu textuel d'un site hôtelier :
---
$text
---

Extrait toutes les informations disponibles et réponds UNIQUEMENT avec un JSON valide, sans aucun texte avant ou après, selon cette structure exacte :

{
  "nom": "Nom de l'hôtel",
  "etoiles": 5,
  "label": "resort|golf_resort|spa_resort|luxe|boutique|eco|",
  "desc": "Description commerciale accrocheuse de 2-3 phrases max",
  "pension": "bb|dp|pc|ai",
  "type_etab": "hotel|resort|villa|chalet|riad|chateau",
  "nb_chambres": "nombre ou vide",
  "distance_golf": "ex: 10 min ou Sur place",
  "equipements": ["piscine_ext","piscine_int","piscine_chauffee","spa","hammam","fitness","restaurant","bar","room_service","wifi","clim","terrasse","vue_golf","vue_mer","kids_club","tennis","beach","navette","parking","velo","boutique","seminaire"],
  "chambres": {
    "double":  {"dispo":"1","superficie":"","desc":""},
    "simple":  {"dispo":"0","superficie":"","desc":""},
    "triple":  {"dispo":"0","superficie":"","desc":""}
  },
  "resto_nb": "",
  "resto_cuisine": "",
  "resto_desc": "",
  "spa_superficie": "",
  "spa_marques": "",
  "spa_desc": "",
  "golfs": [
    {
      "nom": "",
      "trous": "",
      "distance": "",
      "sur_place": "oui|non",
      "diff": "tous|debutant|intermediaire|confirme|champion",
      "practice": "oui|non",
      "architecte": "",
      "desc": ""
    }
  ],
  "adresse": "",
  "dist_aero": "",
  "dist_centre": "",
  "loc_desc": ""
}

Règles importantes :
- Pour "equipements" : inclure uniquement les équipements CONFIRMÉS par le texte
- Pour "pension" : bb=petit-déjeuner seulement, dp=demi-pension, pc=pension complète, ai=tout inclus
- Pour "label" : choisir le plus adapté ou laisser vide
- Pour "golfs" : si plusieurs parcours mentionnés, créer plusieurs objets dans le tableau
- Si une info n'est pas disponible, laisser la valeur vide ""
- Ne jamais inventer d'informations
PROMPT;

        $api_response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 60,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => self::API_KEY,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model'      => self::MODEL,
                'max_tokens' => 2000,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]),
        ]);

        if (is_wp_error($api_response)) {
            wp_send_json_error('Erreur API Claude : ' . $api_response->get_error_message());
        }

        $api_body = json_decode(wp_remote_retrieve_body($api_response), true);

        if (empty($api_body['content'][0]['text'])) {
            $err_msg = $api_body['error']['message'] ?? 'Réponse API invalide';
            wp_send_json_error('Erreur Claude : ' . $err_msg);
        }

        $raw_text = $api_body['content'][0]['text'];

        // Extraire le JSON de la réponse
        preg_match('/\{[\s\S]*\}/', $raw_text, $matches);
        if (empty($matches[0])) {
            wp_send_json_error('Claude n\'a pas retourné de JSON valide.');
        }

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('JSON malformé : ' . json_last_error_msg());
        }

        wp_send_json_success($data);
    }

    public static function ajax_scan_pdf() {
        check_ajax_referer('vs08v_scan_hotel', 'nonce');

        $pdf_b64  = $_POST['pdf_b64']  ?? '';
        $pdf_name = $_POST['pdf_name'] ?? 'document.pdf';

        if (!$pdf_b64) wp_send_json_error('Fichier PDF manquant');

        // Envoyer directement le PDF à Claude API (support natif des documents)
        $api_response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 90,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => self::API_KEY,
                'anthropic-version' => '2023-06-01',
                'anthropic-beta'    => 'pdfs-2024-09-25',
            ],
            'body' => json_encode([
                'model'      => self::MODEL,
                'max_tokens' => 2000,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'document',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => 'application/pdf',
                                'data'       => $pdf_b64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => self::get_extraction_prompt(),
                        ],
                    ],
                ]],
            ]),
        ]);

        if (is_wp_error($api_response)) {
            wp_send_json_error('Erreur API Claude : ' . $api_response->get_error_message());
        }

        $api_body = json_decode(wp_remote_retrieve_body($api_response), true);

        if (empty($api_body['content'][0]['text'])) {
            $err_msg = $api_body['error']['message'] ?? 'Réponse API invalide';
            wp_send_json_error('Erreur Claude : ' . $err_msg);
        }

        $raw_text = $api_body['content'][0]['text'];
        preg_match('/\{[\s\S]*\}/', $raw_text, $matches);
        if (empty($matches[0])) wp_send_json_error('JSON introuvable dans la réponse');

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) wp_send_json_error('JSON malformé');

        wp_send_json_success($data);
    }

    /**
     * Recherche par nom d'hôtel : Claude utilise la recherche web pour trouver
     * les infos sur plusieurs sites et retourne le JSON structuré.
     */
    public static function ajax_scan_by_name() {
        check_ajax_referer('vs08v_scan_hotel', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission refusée');
        }

        $hotel_name = sanitize_text_field($_POST['hotel_name'] ?? '');
        $location   = sanitize_text_field($_POST['location'] ?? '');

        if (!$hotel_name) {
            wp_send_json_error('Veuillez entrer le nom de l\'hôtel.');
        }

        $query = $hotel_name;
        if ($location) {
            $query .= ', ' . $location;
        }

        $api_key = defined('VS08V_CLAUDE_KEY') ? VS08V_CLAUDE_KEY : self::API_KEY;

        $schema = self::get_search_extraction_schema();

        $prompt = <<<PROMPT
Tu es un assistant spécialisé en fiches hôtelières pour une agence de voyages golf.

**Mission :** Recherche sur le web des informations complètes sur cet établissement : « {$query} ».

**Ordre de recherche obligatoire :**
1. Vérifie d'abord le site officiel de l'hôtel (page d'accueil, hébergement/chambres, équipements, tarifs) pour récupérer un maximum d'infos (nom exact, étoiles, description, types de chambres, superficies en m², équipements, restauration, spa, golfs, adresse).
2. Si certaines informations manquent (notamment les m² des chambres, la description, les équipements), consulte ensuite les sites qui parlent de l'hôtel : Booking, TripAdvisor, pages dédiées, etc., pour compléter.

**Important — superficies des chambres :** Pour chaque type de chambre (double, simple, triple), tu dois chercher et remplir le champ "superficie" en m² (ex: "28", "35"). C'est une donnée prioritaire : consulte la fiche chambres du site officiel et les comparateurs pour trouver les m².

Extrait toutes les informations disponibles et réponds UNIQUEMENT avec un JSON valide, sans aucun texte avant ou après, selon cette structure exacte :

{$schema}

Règles :
- Pour "equipements" : liste uniquement les équipements CONFIRMÉS par tes recherches (piscine_ext, piscine_int, spa, hammam, fitness, restaurant, bar, wifi, clim, etc.).
- Pour "pension" : bb=petit-déjeuner, dp=demi-pension, pc=pension complète, ai=tout inclus.
- Pour "chambres" : pour chaque type (double, simple, triple), remplis obligatoirement "dispo" ("1" ou "0"), "superficie" en m² (nombre seul, ex: "28") si trouvé, et "desc" (description courte). Ne pas laisser superficie vide si l'info existe sur le site officiel ou les comparateurs.
- Pour "golfs" : si l'hôtel est lié à un ou des parcours de golf, remplis le tableau ; sinon tableau vide [].
- Ne jamais inventer : si une info est introuvable, laisser "" ou valeur par défaut.
- Nom de l'hôtel : utilise le nom officiel tel qu'affiché sur le site de l'hôtel.
- TripAdvisor : recherche la note TripAdvisor de l'hôtel (ex: "4.5") et l'URL de la page TripAdvisor (ex: "https://www.tripadvisor.fr/Hotel_Review-..."). Ne pas arrondir la note, garder la décimale exacte.
PROMPT;

        $body = [
            'model'      => self::MODEL,
            'max_tokens' => 4000,
            'tools'      => [['type' => 'web_search_20250305', 'name' => 'web_search']],
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        $api_response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 90,
            'headers' => [
                'Content-Type'       => 'application/json',
                'x-api-key'          => $api_key,
                'anthropic-version'  => '2023-06-01',
            ],
            'body' => json_encode($body),
        ]);

        if (is_wp_error($api_response)) {
            wp_send_json_error('Erreur API Claude : ' . $api_response->get_error_message());
        }

        $api_body = json_decode(wp_remote_retrieve_body($api_response), true);
        $code     = wp_remote_retrieve_response_code($api_response);

        if ($code !== 200) {
            $err_msg = $api_body['error']['message'] ?? 'Erreur HTTP ' . $code;
            wp_send_json_error('Claude : ' . $err_msg);
        }

        $text = '';
        foreach ($api_body['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        // Si Claude a utilisé l'outil sans renvoyer le JSON dans le même bloc, relancer une fois
        if (empty(trim($text))) {
            $has_tool_use = false;
            foreach ($api_body['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'tool_use') {
                    $has_tool_use = true;
                    break;
                }
            }
            if ($has_tool_use) {
                $messages = [
                    ['role' => 'user', 'content' => $prompt],
                    ['role' => 'assistant', 'content' => $api_body['content']],
                    ['role' => 'user', 'content' => 'Maintenant réponds uniquement avec le JSON demandé, sans aucun commentaire avant ou après.'],
                ];
                $api_response2 = wp_remote_post('https://api.anthropic.com/v1/messages', [
                    'timeout' => 60,
                    'headers' => [
                        'Content-Type'       => 'application/json',
                        'x-api-key'          => $api_key,
                        'anthropic-version' => '2023-06-01',
                    ],
                    'body' => json_encode([
                        'model'    => self::MODEL,
                        'max_tokens' => 4000,
                        'messages' => $messages,
                    ]),
                ]);
                if (!is_wp_error($api_response2)) {
                    $api_body2 = json_decode(wp_remote_retrieve_body($api_response2), true);
                    foreach ($api_body2['content'] ?? [] as $block) {
                        if (($block['type'] ?? '') === 'text') {
                            $text .= $block['text'];
                        }
                    }
                }
            }
        }

        if (empty(trim($text))) {
            wp_send_json_error('L\'IA n\'a pas pu extraire les données. Réessayez avec un nom plus précis ou ajoutez la ville/pays.');
        }

        $text = preg_replace('/```json|```/i', '', $text);
        $text = trim($text);
        preg_match('/\{[\s\S]*\}/', $text, $matches);

        if (empty($matches[0])) {
            wp_send_json_error('Réponse invalide de l\'IA. Vérifiez le nom de l\'hôtel et réessayez.');
        }

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Données mal formées : ' . json_last_error_msg());
        }

        if (!empty($data['golfs']) && is_array($data['golfs'])) {
            $data['golfs'] = self::sanitize_golf_text_fields($data['golfs']);
        }

        wp_send_json_success($data);
    }

    /**
     * Supprime les balises HTML des champs texte des parcours golf (réponses IA).
     */
    private static function sanitize_golf_text_fields($golfs) {
        if (!is_array($golfs)) return $golfs;
        $keys = ['nom', 'desc', 'architecte', 'distance', 'trous'];
        foreach ($golfs as $i => $g) {
            if (!is_array($g)) continue;
            foreach ($keys as $k) {
                if (isset($g[$k]) && is_string($g[$k])) {
                    $golfs[$i][$k] = wp_strip_all_tags($g[$k]);
                }
            }
        }
        return $golfs;
    }

    /**
     * Recherche par nom de golf : Claude utilise la recherche web et retourne
     * les infos d'un parcours (nom, trous, distance, sur_place, diff, practice, architecte, desc).
     */
    public static function ajax_scan_golf_by_name() {
        check_ajax_referer('vs08v_scan_hotel', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission refusée');
        }

        $golf_name = sanitize_text_field($_POST['golf_name'] ?? '');
        $location  = sanitize_text_field($_POST['location'] ?? '');

        if (!$golf_name) {
            wp_send_json_error('Veuillez entrer le nom du parcours.');
        }

        $query = $golf_name;
        if ($location) {
            $query .= ', ' . $location;
        }

        $api_key = defined('VS08V_CLAUDE_KEY') ? VS08V_CLAUDE_KEY : self::API_KEY;

        $schema = '{
  "nom": "Nom officiel du parcours",
  "trous": "18 trous ou 9 trous",
  "distance": "Sur place ou ex: 15 min en voiture",
  "sur_place": "oui ou non",
  "diff": "tous|debutant|intermediaire|confirme|champion",
  "practice": "oui ou non",
  "architecte": "Nom de l\'architecte ou vide",
  "desc": "Description du parcours, 2-4 phrases"
}';

        $prompt = <<<PROMPT
Tu es un assistant spécialisé en parcours de golf pour une agence de voyages golf.

**Mission :** Recherche sur le web les informations complètes sur ce parcours : « {$query} ».

**Ordre de recherche obligatoire :**
1. Vérifie d'abord le site officiel du golf / du parcours (page d'accueil, présentation, caractéristiques, architecte, nombre de trous, practice, niveau) pour récupérer un maximum d'infos.
2. Si certaines informations manquent, consulte ensuite les sites qui parlent de ce parcours (FFGolf, comparateurs, avis, etc.) pour compléter.

Extrait toutes les informations disponibles et réponds UNIQUEMENT avec un objet JSON valide (un seul parcours), sans aucun texte avant ou après, selon cette structure exacte :

{$schema}

Règles :
- "nom" : nom officiel du parcours tel qu'affiché sur le site.
- "trous" : ex. "18 trous", "9 trous", "27 trous".
- "distance" : "Sur place" si le parcours est sur le domaine, sinon ex. "10 min en voiture", "5 km".
- "sur_place" : "oui" si le parcours est sur le domaine de l'établissement, "non" sinon.
- "diff" : une seule valeur parmi tous, debutant, intermediaire, confirme, champion.
- "practice" : "oui" ou "non" selon si un practice / académie est mentionné.
- "architecte" : nom du concepteur si trouvé, sinon "".
- "desc" : description commerciale du parcours, 2-4 phrases. Ne jamais inventer ; si une info est introuvable, laisser "".
PROMPT;

        $body = [
            'model'     => self::MODEL,
            'max_tokens' => 2000,
            'tools'     => [['type' => 'web_search_20250305', 'name' => 'web_search']],
            'messages'  => [['role' => 'user', 'content' => $prompt]],
        ];

        $api_response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 60,
            'headers' => [
                'Content-Type'       => 'application/json',
                'x-api-key'          => $api_key,
                'anthropic-version'  => '2023-06-01',
            ],
            'body' => json_encode($body),
        ]);

        if (is_wp_error($api_response)) {
            wp_send_json_error('Erreur API Claude : ' . $api_response->get_error_message());
        }

        $api_body = json_decode(wp_remote_retrieve_body($api_response), true);
        $code     = wp_remote_retrieve_response_code($api_response);

        if ($code !== 200) {
            $err_msg = $api_body['error']['message'] ?? 'Erreur HTTP ' . $code;
            wp_send_json_error('Claude : ' . $err_msg);
        }

        $text = '';
        foreach ($api_body['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        if (empty(trim($text))) {
            $has_tool_use = false;
            foreach ($api_body['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'tool_use') {
                    $has_tool_use = true;
                    break;
                }
            }
            if ($has_tool_use) {
                $messages = [
                    ['role' => 'user', 'content' => $prompt],
                    ['role' => 'assistant', 'content' => $api_body['content']],
                    ['role' => 'user', 'content' => 'Maintenant réponds uniquement avec le JSON demandé (un seul objet parcours), sans aucun commentaire.'],
                ];
                $api_response2 = wp_remote_post('https://api.anthropic.com/v1/messages', [
                    'timeout' => 45,
                    'headers' => [
                        'Content-Type'       => 'application/json',
                        'x-api-key'          => $api_key,
                        'anthropic-version' => '2023-06-01',
                    ],
                    'body' => json_encode([
                        'model'      => self::MODEL,
                        'max_tokens' => 2000,
                        'messages'   => $messages,
                    ]),
                ]);
                if (!is_wp_error($api_response2)) {
                    $api_body2 = json_decode(wp_remote_retrieve_body($api_response2), true);
                    foreach ($api_body2['content'] ?? [] as $block) {
                        if (($block['type'] ?? '') === 'text') {
                            $text .= $block['text'];
                        }
                    }
                }
            }
        }

        if (empty(trim($text))) {
            wp_send_json_error('L\'IA n\'a pas pu extraire les données. Réessayez avec un nom plus précis ou ajoutez la ville/région.');
        }

        $text = preg_replace('/```json|```/i', '', $text);
        $text = trim($text);
        preg_match('/\{[\s\S]*\}/', $text, $matches);

        if (empty($matches[0])) {
            wp_send_json_error('Réponse invalide de l\'IA. Vérifiez le nom du parcours et réessayez.');
        }

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Données mal formées : ' . json_last_error_msg());
        }

        $data = self::sanitize_golf_text_fields([ $data ])[0];
        wp_send_json_success($data);
    }

    private static function get_search_extraction_schema() {
        return '{
  "nom": "Nom officiel de l\'hôtel",
  "etoiles": 5,
  "label": "resort|golf_resort|spa_resort|luxe|boutique|eco|",
  "desc": "Description commerciale 2-3 phrases",
  "pension": "bb|dp|pc|ai",
  "type_etab": "hotel|resort|villa|chalet|riad|chateau",
  "nb_chambres": "nombre ou vide",
  "distance_golf": "ex: 10 min ou Sur place",
  "equipements": ["piscine_ext","piscine_int","spa","hammam","fitness","restaurant","bar","wifi","clim", etc.],
  "chambres": {
    "double":  {"dispo":"1","superficie":"","desc":""},
    "simple":  {"dispo":"0","superficie":"","desc":""},
    "triple":  {"dispo":"0","superficie":"","desc":""}
  },
  "resto_nb": "",
  "resto_cuisine": "",
  "resto_desc": "",
  "spa_superficie": "",
  "spa_marques": "",
  "spa_desc": "",
  "golfs": [{"nom":"","trous":"","distance":"","sur_place":"non","diff":"tous","practice":"oui","architecte":"","desc":""}],
  "adresse": "",
  "dist_aero": "",
  "dist_centre": "",
  "loc_desc": "",
  "tripadvisor_note": "",
  "tripadvisor_url": ""
}';
    }

    public static function ajax_scan_golf_pdf() {
        check_ajax_referer('vs08v_scan_hotel', 'nonce');
        $pdf_b64     = $_POST['pdf_b64']     ?? '';
        $nb_parcours = intval($_POST['nb_parcours'] ?? 0);
        if (!$pdf_b64) wp_send_json_error('Fichier PDF manquant');

        // Instructions adaptées selon le nb de parcours configuré
        $nb_hint = '';
        if ($nb_parcours > 0) {
            $nb_hint = "IMPORTANT : ce séjour est configuré pour {$nb_parcours} parcours de golf. "
                . "Le PDF peut contenir des fiches pour plusieurs parcours distincts. "
                . "Tu dois extraire exactement {$nb_parcours} parcours en lisant le document section par section. "
                . "Si le PDF présente plusieurs parcours (ex: 3 fiches différentes), tu les traites l'un après l'autre "
                . "et tu retournes un tableau avec {$nb_parcours} éléments. "
                . "Si le PDF n'en décrit qu'un seul, retourne un tableau à 1 élément.\n\n";
        }

        $golf_prompt = $nb_hint .
'Extrait tous les parcours de golf présents dans ce document.
Réponds UNIQUEMENT avec un tableau JSON valide, sans texte avant ni après, sans balises markdown :

[
  {
    "nom": "Nom exact du parcours",
    "trous": "18 trous",
    "distance": "Sur place ou X min en voiture",
    "sur_place": "oui ou non",
    "diff": "tous | debutant | intermediaire | confirme | champion",
    "practice": "oui ou non",
    "architecte": "Nom ou vide",
    "desc": "Description complète du parcours, 2-4 phrases"
  }
]

Règles strictes :
- 1 objet par parcours distinct trouvé dans le document
- Ne jamais inventer une information absente du PDF
- Si un champ est inconnu, laisser la valeur vide ""
- Le champ "diff" doit être une des 5 valeurs exactes ci-dessus
- Le champ "sur_place" doit être "oui" ou "non"';

        $api_response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 90,
            'headers' => [
                'Content-Type'    => 'application/json',
                'x-api-key'       => self::API_KEY,
                'anthropic-version' => '2023-06-01',
                'anthropic-beta'  => 'pdfs-2024-09-25',
            ],
            'body' => json_encode([
                'model'      => self::MODEL,
                'max_tokens' => 4000,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'document',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => 'application/pdf',
                                'data'       => $pdf_b64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $golf_prompt,
                        ],
                    ],
                ]],
            ]),
        ]);

        if (is_wp_error($api_response)) {
            wp_send_json_error('Erreur API : ' . $api_response->get_error_message());
        }

        $http_code = wp_remote_retrieve_response_code($api_response);
        $api_body  = json_decode(wp_remote_retrieve_body($api_response), true);

        if ($http_code !== 200) {
            $err_msg = $api_body['error']['message'] ?? 'Erreur HTTP ' . $http_code;
            wp_send_json_error('Claude API : ' . $err_msg);
        }

        if (empty($api_body['content'][0]['text'])) {
            wp_send_json_error('Réponse vide de Claude. Détail : ' . wp_remote_retrieve_body($api_response));
        }

        $raw = $api_body['content'][0]['text'];

        // Nettoyer les backticks markdown éventuels
        $raw = preg_replace('/```json|```/i', '', $raw);
        $raw = trim($raw);

        // Extraire le tableau JSON
        preg_match('/\[[\s\S]*\]/u', $raw, $matches);
        if (empty($matches[0])) {
            wp_send_json_error('JSON introuvable dans la réponse. Réponse brute : ' . substr($raw, 0, 300));
        }

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('JSON malformé : ' . json_last_error_msg() . ' — ' . substr($matches[0], 0, 200));
        }

        if (empty($data)) {
            wp_send_json_error('Aucun parcours extrait du PDF.');
        }

        $data = self::sanitize_golf_text_fields($data);
        wp_send_json_success($data);
    }

    private static function get_extraction_prompt() {
        return 'Extrait toutes les informations de cet hôtel/séjour et réponds UNIQUEMENT avec un JSON valide, sans aucun texte avant ou après :

{
  "nom": "",
  "etoiles": 5,
  "label": "resort|golf_resort|spa_resort|luxe|boutique|eco|",
  "desc": "Description accrocheuse 2-3 phrases",
  "pension": "bb|dp|pc|ai",
  "type_etab": "hotel|resort|villa|chalet|riad|chateau",
  "nb_chambres": "",
  "distance_golf": "",
  "equipements": [],
  "chambres": {
    "double":  {"dispo":"1","superficie":"","desc":""},
    "simple":  {"dispo":"0","superficie":"","desc":""},
    "triple":  {"dispo":"0","superficie":"","desc":""}
  },
  "resto_nb": "",
  "resto_cuisine": "",
  "resto_desc": "",
  "spa_superficie": "",
  "spa_marques": "",
  "spa_desc": "",
  "golfs": [{"nom":"","trous":"","distance":"","sur_place":"non","diff":"tous","practice":"oui","architecte":"","desc":""}],
  "adresse": "",
  "dist_aero": "",
  "dist_centre": "",
  "loc_desc": ""
}

Equipements possibles : piscine_ext, piscine_int, piscine_chauffee, spa, hammam, fitness, restaurant, bar, room_service, wifi, clim, terrasse, vue_golf, vue_mer, kids_club, tennis, beach, navette, parking, velo, boutique, seminaire.
Ne jamais inventer — si info absente, laisser vide.';
    }

    private static function extract_text($html) {
        // Supprimer scripts, styles, nav, footer
        $html = preg_replace('/<script[^>]*>[\s\S]*?<\/script>/i', '', $html);
        $html = preg_replace('/<style[^>]*>[\s\S]*?<\/style>/i', '', $html);
        $html = preg_replace('/<nav[^>]*>[\s\S]*?<\/nav>/i', '', $html);
        $html = preg_replace('/<footer[^>]*>[\s\S]*?<\/footer>/i', '', $html);
        $html = preg_replace('/<header[^>]*>[\s\S]*?<\/header>/i', '', $html);
        // Convertir les balises de blocs en espaces
        $html = preg_replace('/<br\s*\/?>/', "\n", $html);
        $html = preg_replace('/<\/?(p|div|h[1-6]|li|tr|td)[^>]*>/', "\n", $html);
        // Supprimer toutes les autres balises
        $text = strip_tags($html);
        // Nettoyer les espaces
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    /* ───────────────────────────────────────────────────────────
       Géolocalisation : Nominatim (OSM) en priorité, Claude IA
       en fallback, embed Google Maps par nom (recherche native).
    ─────────────────────────────────────────────────────────── */
    public static function ajax_geo_hotel() {
        check_ajax_referer('vs08v_scan_hotel', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Permission refusée');

        $query = sanitize_text_field($_POST['query'] ?? '');
        if (!$query) wp_send_json_error(['message' => 'Requête vide']);

        $api_key = defined('VS08V_CLAUDE_KEY') ? VS08V_CLAUDE_KEY : self::API_KEY;

        $lat    = null;
        $lon    = null;
        $label  = $query;

        // ── Étape 1 : Nominatim (OpenStreetMap) — POI précis ──
        $nom = self::nominatim_geocode($query);
        if ($nom) {
            $lat   = $nom['lat'];
            $lon   = $nom['lon'];
            $label = $nom['display_name'];
        }

        // ── Étape 2 : Claude IA + web_search si Nominatim n'a rien trouvé ──
        if ($lat === null) {
            $claude = self::claude_geocode($query, $api_key);
            if ($claude) {
                $lat   = $claude['lat'];
                $lon   = $claude['lon'];
                $label = $claude['adresse_complete'] ?: $query;

                // Tenter de raffiner via Nominatim avec l'adresse exacte trouvée par Claude
                if (!empty($claude['adresse_complete'])) {
                    $refined = self::nominatim_geocode($claude['adresse_complete']);
                    if ($refined) {
                        $lat = $refined['lat'];
                        $lon = $refined['lon'];
                    }
                }
            }
        }

        if ($lat === null || $lon === null) {
            wp_send_json_error(['message' => "L'IA n'a pas pu localiser cet hôtel. Vérifiez le nom et la destination."]);
        }

        $lat = round(floatval($lat), 6);
        $lon = round(floatval($lon), 6);

        // Embed URL : le NOM comme requête Google Maps (sa recherche native est plus précise que des coordonnées brutes)
        $embed_url = sprintf(
            'https://maps.google.com/maps?q=%s&z=17&t=m&hl=fr',
            rawurlencode($query)
        );

        wp_send_json_success([
            'lat'       => $lat,
            'lon'       => $lon,
            'label'     => $label,
            'embed_url' => $embed_url,
        ]);
    }

    /* ── Nominatim (OpenStreetMap) — géocodage gratuit, précis pour les POI ── */
    private static function nominatim_geocode($query) {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q'               => $query,
            'format'          => 'json',
            'addressdetails'  => 1,
            'limit'           => 5,
            'accept-language' => 'fr',
        ]);

        $resp = wp_remote_get($url, [
            'timeout'    => 10,
            'user-agent' => 'VS08Voyages/1.0 (WordPress Golf Travel Plugin)',
            'headers'    => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) {
            return null;
        }

        $results = json_decode(wp_remote_retrieve_body($resp), true);
        if (empty($results) || !is_array($results)) {
            return null;
        }

        $poi_classes = ['tourism', 'amenity', 'building', 'leisure', 'shop'];

        foreach ($results as $r) {
            if (in_array($r['class'] ?? '', $poi_classes)) {
                return [
                    'lat'          => floatval($r['lat']),
                    'lon'          => floatval($r['lon']),
                    'display_name' => $r['display_name'] ?? '',
                ];
            }
        }

        // Utiliser le 1er résultat s'il n'est pas un lieu trop générique (ville, pays…)
        $generic_classes = ['place', 'boundary', 'landuse', 'natural'];
        $first = $results[0];
        if (!in_array($first['class'] ?? '', $generic_classes)) {
            return [
                'lat'          => floatval($first['lat']),
                'lon'          => floatval($first['lon']),
                'display_name' => $first['display_name'] ?? '',
            ];
        }

        return null;
    }

    /* ── Claude IA + web_search — fallback géocodage ── */
    private static function claude_geocode($query, $api_key) {
        $prompt = <<<PROMPT
Tu es un expert en géolocalisation précise d'établissements hôteliers.

**Requête :** "{$query}"

**Mission :** Trouver les coordonnées GPS EXACTES du BÂTIMENT de cet hôtel (pas le centre-ville, pas une zone approximative).

**Méthode obligatoire :**
1. Recherche cet hôtel sur Google Maps. Si tu trouves une URL Google Maps contenant des coordonnées (ex: google.com/maps/place/…/@31.6064,-8.0157,17z/), EXTRAIS les coordonnées de l'URL.
2. Sinon, cherche la fiche de l'hôtel sur Booking.com, TripAdvisor ou son site officiel pour trouver son adresse postale exacte.
3. Les coordonnées doivent correspondre à l'ENTRÉE ou au BÂTIMENT PRINCIPAL de l'hôtel, PAS au centre-ville ni à une zone approximative.
4. Si tu trouves plusieurs établissements homonymes, choisis celui qui correspond au nom et localisation fournis.
5. Précision minimale : 4 décimales (ex: 31.6064, -8.0157).

Réponds UNIQUEMENT avec un JSON valide, sans texte ni balises markdown :
{"lat":XX.XXXXXX,"lon":XX.XXXXXX,"adresse_complete":"adresse postale exacte trouvée"}
PROMPT;

        $cl_resp = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'        => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'     => 'application/json',
            ],
            'timeout' => 45,
            'body'    => json_encode([
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 400,
                'tools'      => [['type' => 'web_search_20250305', 'name' => 'web_search']],
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]),
        ]);

        if (is_wp_error($cl_resp)) return null;

        $cl_body = json_decode(wp_remote_retrieve_body($cl_resp), true);

        $text = '';
        foreach ($cl_body['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        // Si Claude s'est arrêté sur tool_use sans JSON final, relancer
        if (empty(trim($text))) {
            $has_tool = false;
            foreach ($cl_body['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'tool_use') { $has_tool = true; break; }
            }
            if ($has_tool) {
                $cl_resp2 = wp_remote_post('https://api.anthropic.com/v1/messages', [
                    'headers' => [
                        'x-api-key'        => $api_key,
                        'anthropic-version' => '2023-06-01',
                        'content-type'     => 'application/json',
                    ],
                    'timeout' => 30,
                    'body'    => json_encode([
                        'model'      => 'claude-sonnet-4-20250514',
                        'max_tokens' => 400,
                        'messages'   => [
                            ['role' => 'user',      'content' => $prompt],
                            ['role' => 'assistant',  'content' => $cl_body['content']],
                            ['role' => 'user',       'content' => 'Réponds maintenant uniquement avec le JSON demandé. Aucun autre texte.'],
                        ],
                    ]),
                ]);
                if (!is_wp_error($cl_resp2)) {
                    $body2 = json_decode(wp_remote_retrieve_body($cl_resp2), true);
                    foreach ($body2['content'] ?? [] as $block) {
                        if (($block['type'] ?? '') === 'text') $text .= $block['text'];
                    }
                }
            }
        }

        $text = preg_replace('/```json|```/i', '', $text);
        $text = trim($text);

        $coords = null;
        if (preg_match('/\{"lat"\s*:\s*([0-9.-]+)\s*,\s*"lon"\s*:\s*([0-9.-]+)\s*,\s*"adresse_complete"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"\s*\}/s', $text, $m)) {
            $coords = [
                'lat'              => (float) $m[1],
                'lon'              => (float) $m[2],
                'adresse_complete' => stripslashes($m[3]),
            ];
        }
        if (!$coords && preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $coords = json_decode($matches[0], true);
            if (is_array($coords)) {
                if (empty($coords['lat']) && isset($coords['latitude']))  $coords['lat'] = $coords['latitude'];
                if (empty($coords['lon']) && isset($coords['longitude'])) $coords['lon'] = $coords['longitude'];
            }
        }

        if (empty($coords) || !isset($coords['lat']) || !isset($coords['lon'])) {
            return null;
        }

        return [
            'lat'              => floatval($coords['lat']),
            'lon'              => floatval($coords['lon']),
            'adresse_complete' => $coords['adresse_complete'] ?? '',
        ];
    }

    public static function ajax_search_car_photo() {
        check_ajax_referer('vs08v_scan_hotel', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission refusée');
        }

        $model = sanitize_text_field($_POST['model'] ?? '');
        if (!$model) {
            wp_send_json_error('Veuillez entrer un modèle de véhicule.');
        }

        $api_key = defined('VS08V_CLAUDE_KEY') ? VS08V_CLAUDE_KEY : self::API_KEY;

        $prompt = <<<PROMPT
Tu es un assistant spécialisé en recherche d'images de véhicules de location.

**Mission :** Trouve une URL d'image officielle (photo haute qualité, fond blanc ou neutre, vue 3/4 avant) pour ce véhicule : « {$model} ».

**Stratégie de recherche (dans cet ordre) :**
1. Cherche sur le site officiel du constructeur (ex: fiat.fr, renault.fr, peugeot.fr, volkswagen.fr, toyota.fr, etc.) la page du modèle et trouve l'URL de l'image principale du véhicule.
2. Si pas trouvé, cherche sur les sites de location de voitures (recordgo.com, europcar.com, hertz.com, avis.com, sixt.com) l'image du modèle.
3. Si pas trouvé, cherche "{$model} car png transparent" ou "{$model} press photo" sur le web.

**IMPORTANT :**
- L'URL doit pointer directement vers un fichier image (.jpg, .jpeg, .png, .webp)
- L'image doit montrer le véhicule en entier (pas un détail)
- Privilégie les images sur fond blanc/transparent (photos presse constructeur)
- L'URL doit être accessible publiquement (pas derrière un login)

Réponds UNIQUEMENT avec un JSON valide, sans texte avant ou après :
{"image_url": "https://...", "source": "nom du site source"}

Si aucune image trouvée, réponds : {"image_url": "", "source": ""}
PROMPT;

        $body = [
            'model'      => self::MODEL,
            'max_tokens' => 1000,
            'tools'      => [['type' => 'web_search_20250305', 'name' => 'web_search']],
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        $api_response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 60,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode($body),
        ]);

        if (is_wp_error($api_response)) {
            wp_send_json_error('Erreur API : ' . $api_response->get_error_message());
        }

        $api_body = json_decode(wp_remote_retrieve_body($api_response), true);
        $code     = wp_remote_retrieve_response_code($api_response);

        if ($code !== 200) {
            $err_msg = $api_body['error']['message'] ?? 'Erreur HTTP ' . $code;
            wp_send_json_error('Claude : ' . $err_msg);
        }

        $text = '';
        foreach ($api_body['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        if (empty($text) && ($api_body['stop_reason'] ?? '') === 'tool_use') {
            $messages = [
                ['role' => 'user', 'content' => $prompt],
                ['role' => 'assistant', 'content' => $api_body['content']],
                ['role' => 'user', 'content' => 'Continue et donne-moi le JSON avec l\'URL de l\'image.'],
            ];
            $body['messages'] = $messages;
            $api_response2 = wp_remote_post('https://api.anthropic.com/v1/messages', [
                'timeout' => 60,
                'headers' => [
                    'Content-Type'      => 'application/json',
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                ],
                'body' => json_encode($body),
            ]);
            if (!is_wp_error($api_response2)) {
                $api_body2 = json_decode(wp_remote_retrieve_body($api_response2), true);
                foreach ($api_body2['content'] ?? [] as $block) {
                    if (($block['type'] ?? '') === 'text') {
                        $text .= $block['text'];
                    }
                }
            }
        }

        if (!preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            wp_send_json_error('Aucune image trouvée pour ce modèle.');
        }

        $result = json_decode($matches[0], true);
        if (!is_array($result) || empty($result['image_url'])) {
            wp_send_json_error('Aucune image trouvée pour « ' . $model . ' ».');
        }

        wp_send_json_success([
            'image_url' => esc_url_raw($result['image_url']),
            'source'    => sanitize_text_field($result['source'] ?? ''),
        ]);
    }
}
