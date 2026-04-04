<?php
/**
 * Données page d'accueil v3 : voyages par type + circuits.
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param string $type type_voyage (sejour_golf, sejour, circuit, city_trip sur vs08_voyage — circuit souvent en CPT séparé)
 * @param int    $limit
 * @return array<int, array<string, mixed>>
 */
function vs08_home_voyage_cards_by_type($type, $limit = 4) {
    if (!class_exists('VS08V_MetaBoxes')) {
        return [];
    }
    $ids = get_posts([
        'post_type'      => 'vs08_voyage',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
    ]);
    $out = [];
    foreach ($ids as $id) {
        $m = VS08V_MetaBoxes::get($id);
        if (($m['statut'] ?? '') === 'archive') {
            continue;
        }
        $tv = $m['type_voyage'] ?? 'sejour_golf';
        if ($type === 'sejour_golf') {
            if (!in_array($tv, ['sejour_golf', ''], true)) {
                continue;
            }
        } elseif ($type !== '') {
            if ($tv !== $type) {
                continue;
            }
        }
        $out[] = vs08_home_pack_voyage($id, $m);
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/**
 * @return array<string, mixed>
 */
function vs08_home_pack_voyage($id, $m = null) {
    $m    = $m ?: VS08V_MetaBoxes::get($id);
    $prix = 0;
    if (class_exists('VS08V_Search')) {
        $pr   = VS08V_Search::compute_prix_appel($m, $id);
        $prix = (int) round((float) ($pr['prix'] ?? 0));
    }
    $img = get_the_post_thumbnail_url($id, 'large');
    if (!$img && !empty($m['galerie'][0])) {
        $img = is_numeric($m['galerie'][0])
            ? (string) wp_get_attachment_image_url((int) $m['galerie'][0], 'large')
            : (string) $m['galerie'][0];
    }
    $flag = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : '';
    $pays = trim((string) ($m['pays'] ?? ''));
    $dest = trim((string) ($m['destination'] ?? ''));
    $line = trim($flag . ' ' . ($pays && $dest ? $pays . ' — ' . $dest : ($pays ?: $dest)));

    return [
        'id'          => $id,
        'title'       => get_the_title($id),
        'url'         => get_permalink($id),
        'line'        => $line,
        'duree'       => (int) ($m['duree'] ?? 0),
        'nb_parcours' => (int) ($m['nb_parcours'] ?? 0),
        'prix'        => $prix,
        'img'         => $img ?: '',
        'hotel'       => $m['hotel']['nom'] ?? '',
        'excerpt'     => wp_trim_words(
            wp_strip_all_tags((string) (get_post_field('post_excerpt', $id) ?: get_post_field('post_content', $id))),
            20,
            '…'
        ),
    ];
}

/**
 * @param int $limit
 * @return array<int, array<string, mixed>>
 */
function vs08_home_circuit_cards($limit = 4) {
    if (!post_type_exists('vs08_circuit') || !class_exists('VS08C_MetaBoxes')) {
        return [];
    }
    $ids = get_posts([
        'post_type'      => 'vs08_circuit',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
    ]);
    $out = [];
    foreach ($ids as $id) {
        $m    = VS08C_MetaBoxes::get($id);
        $prix = (float) get_post_meta($id, 'vs08c_prix_min', true);
        if ($prix <= 0 && !empty($m['periodes']) && is_array($m['periodes'])) {
            foreach ($m['periodes'] as $p) {
                $px = (float) ($p['prix'] ?? 0);
                if ($px > 0 && ($prix <= 0 || $px < $prix)) {
                    $prix = $px;
                }
            }
        }
        $img = get_the_post_thumbnail_url($id, 'large');
        if (!$img && !empty($m['galerie'][0])) {
            $gid = (int) $m['galerie'][0];
            $img = $gid ? (string) wp_get_attachment_image_url($gid, 'large') : '';
        }
        $dest = '';
        $terms = get_the_terms($id, 'circuit_destination');
        if ($terms && !is_wp_error($terms) && isset($terms[0]->name)) {
            $dest = $terms[0]->name;
        }
        $out[] = [
            'title'    => get_the_title($id),
            'url'      => get_permalink($id),
            'duree'    => (int) ($m['duree_jours'] ?? 0),
            'prix'     => (int) round($prix),
            'img'      => $img ?: '',
            'subtitle' => (string) ($m['sous_titre'] ?? ''),
            'dest'     => $dest,
        ];
    }
    return $out;
}

/**
 * Compte les voyages publiés par type (hors archive).
 */
function vs08_home_count_by_type($type) {
    if (!class_exists('VS08V_MetaBoxes')) {
        return 0;
    }
    $ids = get_posts([
        'post_type'      => 'vs08_voyage',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);
    $n = 0;
    foreach ($ids as $id) {
        $m = VS08V_MetaBoxes::get($id);
        if (($m['statut'] ?? '') === 'archive') {
            continue;
        }
        $tv = $m['type_voyage'] ?? 'sejour_golf';
        if ($type === 'sejour_golf') {
            if (in_array($tv, ['sejour_golf', ''], true)) {
                ++$n;
            }
        } elseif ($tv === $type) {
            ++$n;
        }
    }
    return $n;
}
