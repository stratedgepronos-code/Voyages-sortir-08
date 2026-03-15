<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  VS08 — DIAGNOSTIC DUFFEL SERVICES (à supprimer après usage) ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * Accès : https://sortirmonde.fr/wp-content/plugins/vs08-voyages/duffel-debug.php?offer_id=off_XXXXX
 *
 * Remplace off_XXXXX par un offer_id réel récupéré en inspectant la page de réservation :
 *   → F12 > Network > chercher "vs08v_get_flight" > Response > copier un "offer_id"
 *
 * ⚠️ SUPPRIMER CE FICHIER après diagnostic !
 */

// Sécurité basique — IP ou clé secrète
define('DEBUG_SECRET', 'vs08debug2026'); // changer si besoin

$key = $_GET['key'] ?? '';
if ($key !== DEBUG_SECRET) {
    // Autoriser aussi si on passe ?offer_id directement depuis localhost
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'])) {
        http_response_code(403);
        die('Accès refusé. Ajoutez ?key=vs08debug2026&offer_id=off_XXX');
    }
}

$offer_id = trim($_GET['offer_id'] ?? '');

// Charger WordPress pour avoir accès à la config
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
$wp_load = $wp_root . '/wp-load.php';
if (!file_exists($wp_load)) {
    die('Impossible de trouver wp-load.php — chemin : ' . htmlspecialchars($wp_load));
}
require_once $wp_load;

// Récupérer la clé API
$api_key = defined('VS08_DUFFEL_API_KEY') ? VS08_DUFFEL_API_KEY : '';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>VS08 — Diagnostic Duffel Services</title>
<style>
    body { font-family: monospace; background: #0f2424; color: #e2e8f0; padding: 30px; margin: 0; }
    h1 { color: #59b7b7; font-size: 20px; margin-bottom: 4px; }
    h2 { color: #59b7b7; font-size: 15px; margin: 24px 0 8px; border-bottom: 1px solid #1e3a3a; padding-bottom: 6px; }
    .ok   { color: #4ade80; }
    .warn { color: #fbbf24; }
    .err  { color: #f87171; }
    .box  { background: #1a3333; border: 1px solid #2d5555; border-radius: 8px; padding: 16px; margin: 10px 0; overflow-x: auto; white-space: pre-wrap; word-break: break-all; font-size: 12px; max-height: 500px; overflow-y: auto; }
    .label { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; }
    form { margin: 20px 0; }
    input[type=text] { background: #1a3333; border: 1px solid #59b7b7; color: #fff; padding: 8px 12px; border-radius: 6px; width: 380px; font-family: monospace; font-size: 13px; }
    input[type=submit] { background: #59b7b7; color: #0f2424; border: none; padding: 9px 18px; border-radius: 6px; cursor: pointer; font-weight: 700; margin-left: 8px; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .badge-ok   { background: #052e16; color: #4ade80; border: 1px solid #166534; }
    .badge-warn { background: #451a03; color: #fbbf24; border: 1px solid #92400e; }
    .badge-err  { background: #450a0a; color: #f87171; border: 1px solid #991b1b; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 6px 10px; border: 1px solid #2d5555; font-size: 12px; text-align: left; vertical-align: top; }
    th { background: #1a3333; color: #59b7b7; }
    tr:nth-child(even) { background: #0f2020; }
</style>
</head>
<body>
<h1>🔍 VS08 — Diagnostic Duffel Services</h1>
<p class="label">Analyse de la réponse brute de l'API Duffel pour les services ancillaires</p>

<?php

// ── Infos de base ──────────────────────────────────────────────────────────
echo '<h2>Configuration</h2>';
echo '<table>';
echo '<tr><th>Paramètre</th><th>Valeur</th><th>Statut</th></tr>';

$api_status = !empty($api_key) ? 'badge-ok' : 'badge-err';
$api_label  = !empty($api_key) ? '✅ Définie (' . substr($api_key, 0, 12) . '...)' : '❌ MANQUANTE';
echo '<tr><td>VS08_DUFFEL_API_KEY</td><td>' . $api_label . '</td><td><span class="badge ' . $api_status . '">' . (!empty($api_key) ? 'OK' : 'ERREUR') . '</span></td></tr>';

$serpapi_key = defined('VS08_SERPAPI_API_KEY') ? VS08_SERPAPI_API_KEY : '';
$sp_status = !empty($serpapi_key) ? 'badge-ok' : 'badge-err';
$sp_label  = !empty($serpapi_key) ? '✅ Définie (' . substr($serpapi_key, 0, 12) . '...)' : '❌ MANQUANTE';
echo '<tr><td>VS08_SERPAPI_API_KEY</td><td>' . $sp_label . '</td><td><span class="badge ' . $sp_status . '">' . (!empty($serpapi_key) ? 'OK' : 'ERREUR') . '</span></td></tr>';

$sp_class_status = class_exists('VS08_SerpApi') ? 'badge-ok' : 'badge-err';
echo '<tr><td>Classe VS08_SerpApi</td><td>' . (class_exists('VS08_SerpApi') ? '✅ Chargée' : '❌ Non chargée') . '</td><td><span class="badge ' . $sp_class_status . '">' . (class_exists('VS08_SerpApi') ? 'OK' : 'ERREUR') . '</span></td></tr>';

$offer_status = !empty($offer_id) ? 'badge-ok' : 'badge-warn';
$offer_label  = !empty($offer_id) ? htmlspecialchars($offer_id) : 'Non fourni';
echo '<tr><td>offer_id</td><td>' . $offer_label . '</td><td><span class="badge ' . $offer_status . '">' . (!empty($offer_id) ? 'OK' : 'MANQUANT') . '</span></td></tr>';
echo '</table>';

// ── Formulaire ─────────────────────────────────────────────────────────────
echo '<h2>Tester un offer_id</h2>';
echo '<p style="color:#9ca3af;font-size:12px">Obtenir un offer_id : F12 → Network → chercher "vs08v_get_flight" → Response → copier une valeur "offer_id" (commence par "off_")</p>';
echo '<form method="GET">';
echo '<input type="hidden" name="key" value="' . htmlspecialchars(DEBUG_SECRET) . '">';
echo '<input type="text" name="offer_id" value="' . htmlspecialchars($offer_id) . '" placeholder="off_0000000000000000000000">';
echo '<input type="submit" value="🔍 Analyser">';
echo '</form>';

if (empty($api_key)) {
    echo '<p class="err">❌ Clé API manquante — impossible de continuer.</p>';
} elseif (empty($offer_id)) {
    echo '<p class="warn">⏳ En attente d\'un offer_id…</p>';
} else {

    // ── Appel API Duffel ──────────────────────────────────────────────────
    echo '<h2>Appel API Duffel</h2>';
    $passengers_arr = [['type' => 'adult']];
    $url = 'https://api.duffel.com/air/offers/' . rawurlencode($offer_id) . '/available_services';

    echo '<div class="label">URL : ' . htmlspecialchars($url) . '</div>';
    echo '<div class="label">Payload : {"data":{"passengers":[{"type":"adult"}]}}</div><br>';

    $response = wp_remote_post($url, [
        'timeout' => 30,
        'headers' => [
            'Authorization'  => 'Bearer ' . $api_key,
            'Content-Type'   => 'application/json',
            'Accept'         => 'application/json',
            'Duffel-Version' => 'v2',
        ],
        'body' => wp_json_encode(['data' => ['passengers' => $passengers_arr]]),
    ]);

    if (is_wp_error($response)) {
        echo '<p class="err">❌ WP Error : ' . htmlspecialchars($response->get_error_message()) . '</p>';
    } else {
        $http_code = wp_remote_retrieve_response_code($response);
        $body_raw  = wp_remote_retrieve_body($response);
        $body      = json_decode($body_raw, true);

        $code_class = ($http_code === 200) ? 'ok' : 'err';
        echo '<p>HTTP Status : <strong class="' . $code_class . '">' . $http_code . '</strong></p>';

        if ($http_code !== 200) {
            echo '<h2>❌ Erreur API</h2>';
            echo '<div class="box">' . htmlspecialchars(json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</div>';
        } else {
            $raw_services = $body['data'] ?? [];
            $nb = count($raw_services);

            echo '<h2>Résumé</h2>';
            echo '<table>';
            echo '<tr><th>Indicateur</th><th>Valeur</th></tr>';
            echo '<tr><td>Nombre de services retournés</td><td><strong class="' . ($nb > 0 ? 'ok' : 'warn') . '">' . $nb . '</strong></td></tr>';

            if ($nb > 0) {
                // Compter par type
                $types = [];
                foreach ($raw_services as $s) {
                    $t = $s['type'] ?? 'inconnu';
                    $types[$t] = ($types[$t] ?? 0) + 1;
                }
                foreach ($types as $t => $c) {
                    echo '<tr><td>Type "' . htmlspecialchars($t) . '"</td><td>' . $c . ' service(s)</td></tr>';
                }
            }
            echo '</table>';

            if ($nb > 0) {
                // ── Tableau détaillé des services ──────────────────────────
                echo '<h2>Détail de chaque service</h2>';
                echo '<table>';
                echo '<tr><th>#</th><th>type</th><th>total_amount</th><th>total_currency</th><th>metadata</th><th>id</th></tr>';
                foreach ($raw_services as $i => $svc) {
                    $meta = $svc['metadata'] ?? [];
                    echo '<tr>';
                    echo '<td>' . ($i+1) . '</td>';
                    echo '<td><strong>' . htmlspecialchars($svc['type'] ?? '—') . '</strong></td>';
                    echo '<td>' . htmlspecialchars($svc['total_amount'] ?? $svc['amount'] ?? '—') . '</td>';
                    echo '<td>' . htmlspecialchars($svc['total_currency'] ?? $svc['currency'] ?? '—') . '</td>';
                    echo '<td class="box" style="max-height:80px;font-size:11px">' . htmlspecialchars(json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</td>';
                    echo '<td style="font-size:10px">' . htmlspecialchars(substr($svc['id'] ?? '—', 0, 30)) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                // ── JSON brut complet ───────────────────────────────────────
                echo '<h2>JSON brut complet (premier service)</h2>';
                echo '<div class="box">' . htmlspecialchars(json_encode($raw_services[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</div>';

                echo '<h2>JSON brut complet (tous les services)</h2>';
                echo '<div class="box">' . htmlspecialchars(json_encode($raw_services, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</div>';
            } else {
                echo '<p class="warn">⚠️ Aucun service retourné par Duffel pour cet offer_id.<br>Possibilités : offer_id expiré, compagnie sans services ancillaires, ou clé API test.</p>';
            }
        }
    }
}
?>

<h2 style="color:#f87171;margin-top:40px">⚠️ Important</h2>
<p style="color:#f87171">Supprimer ce fichier après le diagnostic !</p>
<p style="color:#9ca3af;font-size:11px">Fichier : /wp-content/plugins/vs08-voyages/duffel-debug.php</p>

</body>
</html>
