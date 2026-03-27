<?php
/**
 * Template Name: Résultats de recherche
 * Slug attendu : resultats-recherche
 */
get_header();

$f_type    = sanitize_text_field($_GET['type']    ?? '');
$f_dest    = sanitize_text_field($_GET['dest']    ?? '');
$f_airport = strtoupper(sanitize_text_field($_GET['airport'] ?? ''));
$f_date_min = sanitize_text_field($_GET['date_min'] ?? '');
$f_date_max = sanitize_text_field($_GET['date_max'] ?? '');
$f_duree   = intval($_GET['duree'] ?? 0);
$f_niveau  = sanitize_key($_GET['niveau'] ?? '');

$opts  = class_exists('VS08V_Search') ? VS08V_Search::get_aggregated_options() : ['types'=>[],'destinations'=>[],'aeroports'=>[],'durees'=>[],'dates'=>[]];
$types_labels = VS08V_Search::TYPE_LABELS;
// Toujours afficher tous les types dans le filtre (même sans produits)
foreach ($types_labels as $tk => $tl) {
    if (!isset($opts['types'][$tk])) {
        $opts['types'][$tk] = $tl;
    }
}
$niveau_labels = ['tous'=>'Tous niveaux','debutant'=>'Débutant','intermediaire'=>'Intermédiaire','confirme'=>'Confirmé','champion'=>'Compétition / championnat'];
$badge_labels  = ['new'=>'Nouveauté','promo'=>'Promo','best'=>'Best-seller','derniere'=>'Dernières places'];

$all_posts = get_posts([
    'post_type'      => 'vs08_voyage',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
]);

$results = [];
foreach ($all_posts as $p) {
    $m = VS08V_MetaBoxes::get($p->ID);
    $statut = $m['statut'] ?? 'actif';
    if ($statut === 'archive') continue;

    if ($f_type) {
        if (class_exists('VS08V_Search')) {
            if (!VS08V_Search::voyage_matches_type_filter($m, $f_type)) {
                continue;
            }
        } elseif (($m['type_voyage'] ?? '') !== $f_type) {
            continue;
        }
    }

    if ($f_dest) {
        if (class_exists('VS08V_Search')) {
            if (!VS08V_Search::voyage_matches_dest($m, $f_dest, (int) $p->ID)) {
                continue;
            }
        } elseif (stripos($m['destination'] ?? '', $f_dest) === false && stripos($m['pays'] ?? '', $f_dest) === false) {
            continue;
        }
    }

    if ($f_airport) {
        $aero_match = false;
        foreach (($m['aeroports'] ?? []) as $a) {
            if (strtoupper(trim($a['code'] ?? '')) === $f_airport) { $aero_match = true; break; }
        }
        if (!$aero_match) continue;
    }

    if ($f_date_min || $f_date_max) {
        $date_match = false;
        foreach (($m['dates_depart'] ?? []) as $dd) {
            $dt = $dd['date'] ?? '';
            $st = $dd['statut'] ?? 'dispo';
            if (!$dt || $st === 'complet') continue;
            if ($f_date_min && $dt < $f_date_min) continue;
            if ($f_date_max && $dt > $f_date_max) continue;
            $date_match = true; break;
        }
        if (!$date_match) continue;
    }

    if ($f_duree && intval($m['duree'] ?? 0) !== $f_duree) continue;

    if ($f_niveau && $f_niveau !== 'tous' && ($m['niveau'] ?? 'tous') !== $f_niveau) continue;

    $thumb = get_the_post_thumbnail_url($p->ID, 'medium');
    if (!$thumb) {
        $galerie = $m['galerie'] ?? [];
        $thumb = !empty($galerie[0]) ? $galerie[0] : '';
    }
    $hotel = $m['hotel'] ?? [];
    $appel = VS08V_Search::compute_prix_appel($m, $p->ID);

    $pays = trim($m['pays'] ?? '');
    $dest = trim($m['destination'] ?? '');
    $flag_meta = trim($m['flag'] ?? '');
    $flag = $flag_meta;
    if ($flag === '' && class_exists('VS08V_MetaBoxes')) {
        $flag = VS08V_MetaBoxes::get_flag_emoji($pays);
        if ($flag === '' && $dest !== '') $flag = VS08V_MetaBoxes::get_flag_emoji($dest);
    }
    $results[] = [
        'id'          => $p->ID,
        'title'       => get_the_title($p->ID),
        'url'         => get_permalink($p->ID),
        'thumb'       => $thumb,
        'destination' => $m['destination'] ?? '',
        'pays'        => $pays,
        'flag'        => $flag,
        'prix'        => $appel['prix'],
        'has_vol'     => $appel['has_vol'],
        'debug'       => $appel['debug'],
        'duree'       => intval($m['duree'] ?? 0),
        'nb_parcours' => intval($m['nb_parcours'] ?? 0),
        'niveau'      => $m['niveau'] ?? 'tous',
        'badge'       => $m['badge'] ?? '',
        'desc'           => $m['desc_courte'] ?? wp_trim_words(get_the_excerpt($p->ID), 25, '…'),
        'hotel_nom'      => $hotel['nom'] ?? ($m['hotel_nom'] ?? ''),
        'type_voyage'    => $m['type_voyage'] ?? '',
        'transport_type'  => $m['transport_type'] ?? 'vol',
        'transfert_type'  => $m['transfert_type'] ?? '',
    ];
}

// ── Circuits (post_type vs08_circuit) ──
if (class_exists('VS08C_Meta') && (!$f_type || $f_type === 'circuit')) {
    $circuit_posts = get_posts([
        'post_type'      => 'vs08_circuit',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);
    foreach ($circuit_posts as $cp) {
        $cm = VS08C_Meta::get($cp->ID);
        if (($cm['statut'] ?? '') === 'archive') continue;

        if ($f_dest) {
            $cd = trim($cm['destination'] ?? '');
            $cpays = trim($cm['pays'] ?? '');
            if (stripos($cd, $f_dest) === false && stripos($cpays, $f_dest) === false) continue;
        }

        if ($f_airport) {
            $aero_match = false;
            foreach (($cm['aeroports'] ?? []) as $a) {
                if (strtoupper(trim($a['code'] ?? '')) === $f_airport) { $aero_match = true; break; }
            }
            if (!$aero_match) continue;
        }

        $cthumb = get_the_post_thumbnail_url($cp->ID, 'medium');
        if (!$cthumb) {
            $cgal = $cm['galerie'] ?? ($cm['photos'] ?? []);
            $cthumb = !empty($cgal[0]) ? (is_array($cgal[0]) ? ($cgal[0]['url'] ?? '') : $cgal[0]) : '';
        }
        $cpays = trim($cm['pays'] ?? '');
        $cflag = '';
        if (class_exists('VS08C_Meta')) {
            $cflag = VS08C_Meta::resolve_flag($cm);
        }

        // ── Calcul prix d'appel circuit (reproduit le calculateur réel pour 1 personne) ──
        $c_prix_base  = floatval($cm['prix_double'] ?? 0); // base par personne
        $c_prix_vol   = 0;
        $c_has_vol    = false;
        $c_vol_est    = false;

        // 1) Cache vol (recherches visiteurs — le moins cher gagne)
        $c_vol_cache = get_post_meta($cp->ID, '_vs08c_vol_min_cache', true);
        if (!empty($c_vol_cache['prix']) && !empty($c_vol_cache['ts'])) {
            $c_age = time() - intval($c_vol_cache['ts']);
            if ($c_age < 14 * DAY_IN_SECONDS) {
                $c_prix_vol = floatval($c_vol_cache['prix']);
                $c_has_vol  = true;
            }
        }
        // 2) Fallback: estimation admin
        if ($c_prix_vol <= 0) {
            $c_pvb = floatval($cm['prix_vol_base'] ?? 0);
            if ($c_pvb > 0) {
                $c_prix_vol = $c_pvb;
                $c_vol_est  = true;
            }
        }

        // Taxes, transferts, bagage (par personne)
        $c_taxe      = floatval($cm['prix_taxe'] ?? 0);
        $c_transfert = floatval($cm['prix_transfert'] ?? 0);
        $c_bagage    = floatval($cm['prix_bagage'] ?? 0);

        // Total par personne = base + vol + taxe + transfert + bagage
        $c_total = $c_prix_base + $c_prix_vol + $c_taxe + $c_transfert + $c_bagage;

        // Supplément date (saison la moins chère si existante)
        if (!empty($cm['dates_depart']) && is_array($cm['dates_depart'])) {
            $c_supps = [];
            foreach ($cm['dates_depart'] as $dd) {
                $sv = floatval($dd['supp'] ?? 0);
                if ($sv >= 0) $c_supps[] = $sv;
            }
            if (!empty($c_supps)) $c_total += min($c_supps);
        }

        // Marge (utilise VS08C_Marge si disponible, sinon champs meta)
        if (class_exists('VS08C_Marge')) {
            $c_mg = VS08C_Marge::get_effective_for_circuit($cp->ID);
            if (($c_mg['marge_type'] ?? '') === 'montant' && floatval($c_mg['marge_montant'] ?? 0) > 0) {
                // Marge forfaitaire : diviser par 1 personne pour le prix d'appel
                // (approximation — en réalité dépend du nb de voyageurs)
                $c_total += floatval($c_mg['marge_montant']);
            } elseif (floatval($c_mg['marge_pct'] ?? 0) > 0) {
                $c_total *= (1 + floatval($c_mg['marge_pct']) / 100);
            }
        } elseif (!empty($cm['marge_pct']) && floatval($cm['marge_pct']) > 0) {
            $c_total *= (1 + floatval($cm['marge_pct']) / 100);
        } elseif (!empty($cm['marge_montant']) && floatval($cm['marge_montant']) > 0) {
            $c_total += floatval($cm['marge_montant']);
        }

        $c_prix_final = $c_total > 0 ? (int) ceil($c_total) : 0;
        $c_price_hint = '';
        if ($c_has_vol) {
            $c_price_hint = 'Basé sur le meilleur tarif vol récemment trouvé.';
        } elseif ($c_vol_est) {
            $c_price_hint = 'Vol estimé — une recherche sur la fiche actualise le prix.';
        }

        $results[] = [
            'id'          => $cp->ID,
            'title'       => get_the_title($cp->ID),
            'url'         => get_permalink($cp->ID),
            'thumb'       => $cthumb,
            'destination' => $cm['destination'] ?? '',
            'pays'        => $cpays,
            'flag'        => $cflag,
            'prix'        => $c_prix_final,
            'has_vol'     => $c_has_vol || $c_vol_est,
            'debug'       => $c_price_hint,
            'duree'       => intval($cm['duree'] ?? ($cm['nb_jours'] ?? 0)),
            'nb_parcours' => 0,
            'niveau'      => 'tous',
            'badge'       => $cm['badge'] ?? '',
            'desc'           => !empty($cm['desc_courte']) ? $cm['desc_courte'] : (!empty($cm['description']) ? wp_trim_words(wp_strip_all_tags($cm['description']), 25, '…') : wp_trim_words(get_post_field('post_content', $cp->ID), 25, '…')),
            'hotel_nom'      => '',
            'type_voyage'    => 'circuit',
            'transport_type'  => (!empty($cm['aeroports']) || $c_has_vol || $c_vol_est) ? 'vol' : '',
            'transfert_type'  => '',
            // Circuit-specific
            'pension'        => $cm['pension'] ?? '',
            'transport'      => $cm['transport'] ?? '',
            'guide_lang'     => $cm['guide_lang'] ?? '',
            'themes'         => $cm['themes'] ?? '',
            'nb_etapes'      => is_array($cm['jours'] ?? null) ? count($cm['jours']) : 0,
        ];
    }
}

// ── Séjours All Inclusive (post_type vs08_sejour) ──
if (class_exists('VS08S_Meta') && (!$f_type || $f_type === 'sejour')) {
    $sejour_posts = get_posts([
        'post_type'      => 'vs08_sejour',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);
    foreach ($sejour_posts as $sp) {
        $sm = VS08S_Meta::get($sp->ID);
        if (($sm['statut'] ?? '') === 'archive') continue;

        if ($f_dest) {
            $sd = trim($sm['destination'] ?? '');
            $spays = trim($sm['pays'] ?? '');
            if (stripos($sd, $f_dest) === false && stripos($spays, $f_dest) === false) continue;
        }

        if ($f_airport) {
            $aero_match = false;
            foreach (($sm['aeroports'] ?? []) as $a) {
                if (strtoupper(trim($a['code'] ?? '')) === $f_airport) { $aero_match = true; break; }
            }
            if (!$aero_match) continue;
        }

        $sthumb = get_the_post_thumbnail_url($sp->ID, 'medium');
        if (!$sthumb && !empty($sm['galerie'][0])) $sthumb = $sm['galerie'][0];

        $sflag = $sm['flag'] ?? '';
        if (!$sflag && class_exists('VS08V_MetaBoxes')) $sflag = VS08V_MetaBoxes::get_flag_emoji($sm['pays'] ?? $sm['destination'] ?? '');

        $s_prix = VS08S_Calculator::prix_appel($sp->ID);

        $results[] = [
            'id'             => $sp->ID,
            'title'          => get_the_title($sp->ID),
            'url'            => get_permalink($sp->ID),
            'thumb'          => $sthumb,
            'destination'    => trim($sm['destination'] ?? ''),
            'pays'           => trim($sm['pays'] ?? ''),
            'flag'           => $sflag,
            'badge'          => $sm['badge'] ?? '',
            'prix'           => $s_prix,
            'has_vol'        => true,
            'vol_estimate'   => false,
            'duree'          => intval($sm['duree'] ?? 7),
            'etoiles'        => intval($sm['hotel_etoiles'] ?? 5),
            'pension'        => $sm['pension'] ?? 'ai',
            'desc'           => !empty($sm['description_courte']) ? $sm['description_courte'] : wp_trim_words(get_post_field('post_content', $sp->ID), 25, '…'),
            'hotel_nom'      => $sm['hotel_nom'] ?? '',
            'type_voyage'    => 'sejour',
            'transport_type' => 'vol',
            'transfert_type' => $sm['transfert_type'] ?? '',
            'pension_label'  => ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déj.','lo'=>'Logement seul'][$sm['pension'] ?? 'ai'] ?? 'All Inclusive',
        ];
    }
}

$active_filters = [];
if ($f_type && isset($types_labels[$f_type]))   $active_filters[] = ['key'=>'type','label'=>$types_labels[$f_type]];
if ($f_dest)    $active_filters[] = ['key'=>'dest','label'=>$f_dest];
if ($f_airport) $active_filters[] = ['key'=>'airport','label'=>$f_airport];
if ($f_date_min || $f_date_max) {
    $dl = '';
    if ($f_date_min) $dl .= date_i18n('j M Y', strtotime($f_date_min));
    if ($f_date_min && $f_date_max) $dl .= ' → ';
    if ($f_date_max) $dl .= date_i18n('j M Y', strtotime($f_date_max));
    $active_filters[] = ['key'=>'date_min,date_max','label'=>$dl];
}
if ($f_duree) $active_filters[] = ['key'=>'duree','label'=>$f_duree . ' nuits'];
if ($f_niveau && $f_niveau !== 'tous' && isset($niveau_labels[$f_niveau])) {
    $active_filters[] = ['key'=>'niveau','label'=>$niveau_labels[$f_niveau]];
}

$total = count($results);

// Tri par défaut : prix croissant
usort($results, function ($a, $b) {
    $pa = (float) ($a['prix'] ?? 0);
    $pb = (float) ($b['prix'] ?? 0);
    if ($pa <= 0 && $pb <= 0) return 0;
    if ($pa <= 0) return 1;
    if ($pb <= 0) return -1;
    return $pa <=> $pb;
});
?>

<style>
.vs08-sr-hero{position:relative;min-height:36vh;display:flex;align-items:center;background:linear-gradient(135deg,#0f2424 0%,#1a3a3a 60%,rgba(89,183,183,.18) 100%);overflow:hidden}
.vs08-sr-hero-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;pointer-events:none}
.vs08-sr-hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,36,36,.75) 0%,rgba(26,58,58,.6) 60%,rgba(89,183,183,.25) 100%);pointer-events:none}
.vs08-sr-hero-content{position:relative;z-index:2;padding:130px 80px 50px;max-width:900px}
.vs08-sr-hero-content h1{font-size:clamp(30px,4vw,48px);color:#fff;margin-bottom:10px;font-family:'Playfair Display',serif;line-height:1.15}
.vs08-sr-hero-content h1 em{color:#7ecece;font-style:italic}
.vs08-sr-hero-desc{font-size:15px;color:rgba(255,255,255,.55);font-family:'Outfit',sans-serif;font-weight:300}

.vs08-sr-filters-bar{background:#f5f2eb;padding:16px 0 20px;margin-bottom:8px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;border-bottom:1px solid #f0f2f4}
.vs08-sr-filter-tag{display:inline-flex;align-items:center;gap:6px;background:#edf8f8;color:#3d9a9a;font-size:12px;font-weight:600;padding:6px 14px;border-radius:100px;font-family:'Outfit',sans-serif}
.vs08-sr-filter-tag button{background:none;border:none;color:#3d9a9a;cursor:pointer;font-size:14px;line-height:1;padding:0;margin-left:2px}
.vs08-sr-filter-tag button:hover{color:#e8724a}
.vs08-sr-reset{color:#6b7280;font-size:12px;font-family:'Outfit',sans-serif;text-decoration:underline;cursor:pointer;background:none;border:none}
.vs08-sr-reset:hover{color:#e8724a}

.vs08-sr-layout{display:flex;gap:0;min-height:60vh;background:#f5f2eb;position:relative}
.vs08-sr-sidebar{width:300px;flex-shrink:0;background:#fff;position:relative;z-index:1;border-right:1px solid #e5e7eb;padding:28px 24px;position:sticky;top:72px;align-self:flex-start;max-height:calc(100vh - 72px);overflow-y:auto}
.vs08-sr-sidebar-title{font-size:16px;font-weight:700;color:#0f2424;margin-bottom:20px;font-family:'Playfair Display',serif;padding-bottom:12px;border-bottom:2px solid #59b7b7;flex-shrink:0}
.vs08-sr-sidebar .vs08-sr-field{margin-bottom:18px}
.vs08-sr-sidebar .vs08-sr-field label{display:block;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;font-family:'Outfit',sans-serif}
.vs08-sr-sidebar .vs08-sr-field select,.vs08-sr-sidebar .vs08-sr-field input[type=text]{width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fff}
.vs08-sr-sidebar .vs08-sr-field select:focus,.vs08-sr-sidebar .vs08-sr-field input:focus{outline:none;border-color:#59b7b7}
.vs08-sr-sidebar .vs08-sr-date-trigger{width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;font-family:'Outfit',sans-serif;color:#6b7280;background:#fff;cursor:pointer;text-align:left}
.vs08-sr-sidebar .vs08-sr-date-trigger:hover{border-color:#59b7b7}
.vs08-sr-sidebar-btns{display:flex;flex-direction:column;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid #f0f2f4}
.vs08-sr-sidebar .vs08-sr-btn-submit{width:100%;padding:14px;background:#59b7b7;color:#fff;border:none;border-radius:100px;font-size:14px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:background .25s}
.vs08-sr-sidebar .vs08-sr-btn-submit:hover{background:#3d9a9a}
.vs08-sr-sidebar .vs08-sr-btn-clear{width:100%;padding:12px;background:transparent;color:#6b7280;border:1.5px solid #e5e7eb;border-radius:100px;font-size:13px;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .25s}
.vs08-sr-sidebar .vs08-sr-btn-clear:hover{border-color:#59b7b7;color:#0f2424}

.vs08-sr-main{flex:1;min-width:0;padding:32px 40px 60px;display:flex;flex-direction:column;align-items:center;background:transparent;position:relative;z-index:1}
.vs08-sr-page{background:transparent;padding:0}
.vs08-sr-inner{max-width:960px;width:100%;margin:0 auto;padding:0}
.vs08-sr-intro{font-size:14px;color:#6b7280;margin-bottom:24px;font-family:'Outfit',sans-serif;line-height:1.6}
.vs08-sr-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
.vs08-sr-count{font-size:14px;color:#6b7280;font-family:'Outfit',sans-serif}
.vs08-sr-count strong{color:#0f2424;font-size:17px}
.vs08-sr-sort select{border:1.5px solid #f0f2f4;border-radius:10px;padding:8px 14px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fff;outline:none;cursor:pointer}

.vs08-sr-grid{display:flex;flex-direction:column;gap:20px}

.vs08-sr-card{background:#fff;border-radius:20px;overflow:hidden;display:flex;flex-direction:row;transition:transform .35s,box-shadow .35s;box-shadow:0 2px 12px rgba(0,0,0,.06);text-decoration:none;color:inherit}
.vs08-sr-card:hover{transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,.14)}
.vs08-sr-card-img{position:relative;overflow:hidden;width:290px;flex-shrink:0}
.vs08-sr-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s;display:block;min-height:220px}
.vs08-sr-card:hover .vs08-sr-card-img img{transform:scale(1.07)}
.vs08-sr-card-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(15,36,36,.2) 0%,transparent 60%)}
.vs08-sr-badge{position:absolute;top:14px;left:14px;padding:5px 13px;border-radius:100px;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#fff;font-family:'Outfit',sans-serif;z-index:2}
.vs08-sr-badge-new{background:#3d9a9a}
.vs08-sr-badge-promo{background:#e8724a}
.vs08-sr-badge-best{background:#c9a84c}
.vs08-sr-badge-derniere{background:#d44a4a}
.vs08-sr-card-body{padding:26px 28px;flex:1;display:flex;flex-direction:column;justify-content:space-between}
.vs08-sr-country{font-size:11px;color:#59b7b7;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;font-family:'Outfit',sans-serif;margin-bottom:6px}
.vs08-sr-card-body h3{font-size:21px;font-weight:700;color:#0f2424;margin-bottom:10px;line-height:1.25;font-family:'Playfair Display',serif}
.vs08-sr-desc{font-size:13px;color:#6b7280;line-height:1.65;font-family:'Outfit',sans-serif;margin-bottom:14px}
.vs08-sr-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.vs08-sr-tag{background:#edf8f8;color:#3d9a9a;font-size:12px;font-weight:600;padding:5px 11px;border-radius:100px;font-family:'Outfit',sans-serif}
.vs08-sr-inclus{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.vs08-sr-inclus-item{font-size:12px;font-weight:600;color:#374151;background:#f3f4f6;padding:5px 11px;border-radius:8px;font-family:'Outfit',sans-serif;white-space:nowrap}
.vs08-sr-footer{display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1px solid #f0f2f4}
.vs08-sr-price-from{display:block;font-size:10px;color:#6b7280;text-transform:uppercase;font-family:'Outfit',sans-serif}
.vs08-sr-price-amount{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#3d9a9a;line-height:1}
.vs08-sr-price-per{font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif}
.vs08-sr-btn{display:inline-block;background:#59b7b7;color:#fff;padding:12px 24px;border-radius:100px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;transition:all .25s;text-decoration:none;white-space:nowrap}
.vs08-sr-btn:hover{background:#3d9a9a;color:#fff}

.vs08-sr-empty{padding:60px 40px;text-align:center;background:#fff;border-radius:20px}
.vs08-sr-empty span{font-size:44px;display:block;margin-bottom:16px}
.vs08-sr-empty h3{font-size:22px;margin-bottom:10px;font-family:'Playfair Display',serif;color:#0f2424}
.vs08-sr-empty p{color:#6b7280;font-family:'Outfit',sans-serif;margin-bottom:20px}
.vs08-sr-empty a{display:inline-block;background:#e8724a;color:#fff;padding:12px 24px;border-radius:100px;font-size:14px;font-weight:700;font-family:'Outfit',sans-serif;text-decoration:none;transition:all .3s}
.vs08-sr-empty a:hover{background:#d4603c}

@media(max-width:900px){
    .vs08-sr-hero-content{padding:120px 24px 40px}
    .vs08-sr-layout{flex-direction:column}
    .vs08-sr-sidebar{width:100%;position:static;top:auto;max-height:none;border-right:none;border-bottom:1px solid #e5e7eb}
    .vs08-sr-main{padding:24px 20px 40px;align-items:stretch}
    .vs08-sr-filters-bar{padding:16px 20px}
    .vs08-sr-inner{padding:0}
    .vs08-sr-card{flex-direction:column}
    .vs08-sr-card-img{width:100%;height:220px}
}
</style>

<?php
$hero_video_url = get_theme_file_uri('assets/video/hero.mp4');
?>
<!-- HERO -->
<section class="vs08-sr-hero">
    <?php if ($hero_video_url): ?>
    <video class="vs08-sr-hero-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
        <source src="<?php echo esc_url($hero_video_url); ?>" type="video/mp4">
    </video>
    <div class="vs08-sr-hero-overlay" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="vs08-sr-hero-content">
        <h1>Résultats de <em>recherche</em></h1>
        <p class="vs08-sr-hero-desc"><?php echo $total; ?> voyage<?php echo $total > 1 ? 's' : ''; ?> correspond<?php echo $total > 1 ? 'ent' : ''; ?> à votre recherche</p>
    </div>
</section>

<!-- LAYOUT SIDEBAR + RÉSULTATS -->
<div class="vs08-sr-layout">
    <!-- SIDEBAR FILTRES -->
    <aside class="vs08-sr-sidebar">
        <h2 class="vs08-sr-sidebar-title">🔍 Affiner ma recherche</h2>
        <form action="<?php echo esc_url(home_url('/resultats-recherche')); ?>" method="get" id="vs08-sr-sidebar-form">
            <div class="vs08-sr-field">
                <label>Type de voyage</label>
                <select name="type">
                    <option value="">Tous les types</option>
                    <?php foreach ($opts['types'] as $tv => $tl): ?>
                    <option value="<?php echo esc_attr($tv); ?>" <?php selected($f_type, $tv); ?>><?php echo esc_html($tl); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vs08-sr-field">
                <label>Destination</label>
                <select name="dest">
                    <option value="">Toutes les destinations</option>
                    <?php foreach ($opts['destinations'] as $d): ?>
                    <option value="<?php echo esc_attr($d['value']); ?>" <?php selected($f_dest, $d['value']); ?>><?php echo esc_html($d['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vs08-sr-field">
                <label>Aéroport de départ</label>
                <select name="airport">
                    <option value="">Tous les aéroports</option>
                    <?php foreach ($opts['aeroports'] as $a): ?>
                    <option value="<?php echo esc_attr($a['code']); ?>" <?php selected($f_airport, $a['code']); ?>><?php echo esc_html($a['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vs08-sr-field">
                <label>Date de départ</label>
                <div id="sr-date-wrap" style="position:relative">
                    <div id="sr-date-trigger" class="vs08-sr-date-trigger" onclick="window.srCalDate && window.srCalDate.toggle()"><?php echo $f_date_min || $f_date_max ? '📅 ' . ($f_date_min ? date_i18n('d M Y', strtotime($f_date_min)) : '…') . ($f_date_max ? ' → ' . date_i18n('d M Y', strtotime($f_date_max)) : '') : '📅 Départ entre… et…'; ?></div>
                </div>
                <input type="hidden" id="sr-date-start" name="date_min" value="<?php echo esc_attr($f_date_min); ?>">
                <input type="hidden" id="sr-date-end" name="date_max" value="<?php echo esc_attr($f_date_max); ?>">
            </div>
            <div class="vs08-sr-field">
                <label>Durée</label>
                <select name="duree">
                    <option value="">Toutes les durées</option>
                    <?php foreach ($opts['durees'] as $dn): ?>
                    <option value="<?php echo esc_attr($dn); ?>" <?php selected($f_duree, (int)$dn); ?>><?php echo esc_html($dn . ' nuits'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vs08-sr-sidebar-btns">
                <button type="submit" class="vs08-sr-btn-submit">Rechercher</button>
                <button type="button" class="vs08-sr-btn-clear" onclick="window.location.href='<?php echo esc_url(home_url('/resultats-recherche')); ?>'">Tout effacer</button>
            </div>
        </form>
    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="vs08-sr-main">
        <?php if ($active_filters): ?>
        <div class="vs08-sr-filters-bar">
            <?php foreach ($active_filters as $af): ?>
            <span class="vs08-sr-filter-tag">
                <?php echo esc_html($af['label']); ?>
                <button onclick="vs08RemoveFilter('<?php echo esc_js($af['key']); ?>')" aria-label="Retirer">&times;</button>
            </span>
            <?php endforeach; ?>
            <button class="vs08-sr-reset" onclick="window.location.href='<?php echo esc_url(home_url('/resultats-recherche')); ?>'">Tout effacer</button>
        </div>
        <?php endif; ?>

        <p class="vs08-sr-intro"><?php echo $total > 0 ? 'Modifiez les critères dans le menu à gauche pour affiner, ou cliquez sur un voyage pour voir le détail et réserver.' : 'Utilisez le menu à gauche pour lancer une nouvelle recherche ou élargir vos critères.'; ?></p>

        <div class="vs08-sr-inner">
        <div class="vs08-sr-header">
            <p class="vs08-sr-count"><strong><?php echo $total; ?></strong> voyage<?php echo $total > 1 ? 's' : ''; ?> trouvé<?php echo $total > 1 ? 's' : ''; ?></p>
            <?php if ($total > 1): ?>
            <div class="vs08-sr-sort">
                <select onchange="vs08SortResults(this.value)">
                    <option value="prix-asc" selected>Prix croissant</option>
                    <option value="prix-desc">Prix décroissant</option>
                    <option value="default">Recommandés</option>
                    <option value="duree-asc">Durée croissante</option>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($total === 0): ?>
        <div class="vs08-sr-empty">
            <span>🔍</span>
            <h3>Aucun voyage ne correspond</h3>
            <p>Essayez d'élargir vos critères ou demandez un voyage sur mesure.</p>
            <a href="<?php echo esc_url(home_url('/contact')); ?>">Demander un devis sur mesure</a>
        </div>
        <?php else: ?>
        <div class="vs08-sr-grid" id="vs08-sr-grid">
            <?php foreach ($results as $r): ?>
            <a href="<?php echo esc_url($r['url']); ?>" class="vs08-sr-card" data-prix="<?php echo esc_attr($r['prix']); ?>" data-duree="<?php echo esc_attr($r['duree']); ?>" data-debug="<?php echo esc_attr(json_encode($r['debug'])); ?>">
                <div class="vs08-sr-card-img">
                    <?php if ($r['badge'] && isset($badge_labels[$r['badge']])): ?>
                    <span class="vs08-sr-badge vs08-sr-badge-<?php echo esc_attr($r['badge']); ?>"><?php echo esc_html($badge_labels[$r['badge']]); ?></span>
                    <?php endif; ?>
                    <?php if ($r['thumb']): ?>
                    <img src="<?php echo esc_url($r['thumb']); ?>" alt="<?php echo esc_attr($r['title']); ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="vs08-sr-card-overlay"></div>
                </div>
                <div class="vs08-sr-card-body">
                    <div>
                        <p class="vs08-sr-country"><?php echo esc_html(($r['flag'] ? $r['flag'] . ' ' : '') . ($r['destination'] ?: $r['pays'])); ?></p>
                        <h3><?php echo esc_html($r['title']); ?></h3>
                        <?php if ($r['desc']): ?>
                        <p class="vs08-sr-desc"><?php echo esc_html($r['desc']); ?></p>
                        <?php endif; ?>
                        <?php if ($r['type_voyage'] === 'circuit'): ?>
                        <div class="vs08-sr-inclus">
                            <?php if (in_array($r['transport_type'], ['vol', 'vol_option'])): ?>
                            <span class="vs08-sr-inclus-item">✈️ Vol A/R</span>
                            <?php endif; ?>
                            <?php
                            $pension_labels = ['bb'=>'🥐 Petit-déj','dp'=>'🍽️ Demi-pension','pc'=>'🍽️ Pension complète','ai'=>'🍽️ Tout inclus','mixed'=>'🍽️ Selon programme'];
                            $transport_labels = ['bus'=>'🚌 Minibus','4x4'=>'🚙 4×4','voiture'=>'🚗 Voiture','train'=>'🚂 Train','mixed'=>'🚌 Transport mixte'];
                            if (!empty($r['pension']) && isset($pension_labels[$r['pension']])): ?>
                            <span class="vs08-sr-inclus-item"><?php echo $pension_labels[$r['pension']]; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($r['transport']) && isset($transport_labels[$r['transport']])): ?>
                            <span class="vs08-sr-inclus-item"><?php echo $transport_labels[$r['transport']]; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($r['guide_lang'])): ?>
                            <span class="vs08-sr-inclus-item">🗣️ Guide <?php echo esc_html($r['guide_lang']); ?></span>
                            <?php endif; ?>
                            <span class="vs08-sr-inclus-item">🏨 Hébergement</span>
                        </div>
                        <?php elseif (in_array($r['type_voyage'], ['sejour_golf', ''])): ?>
                        <div class="vs08-sr-inclus">
                            <?php if (in_array($r['transport_type'], ['vol', 'vol_option'])): ?>
                            <span class="vs08-sr-inclus-item">✈️ Vol A/R direct</span>
                            <?php endif; ?>
                            <span class="vs08-sr-inclus-item">🧳 Bagage soute</span>
                            <span class="vs08-sr-inclus-item">🏌️ Bagage golf</span>
                            <?php if ($r['transfert_type'] === 'voiture'): ?>
                            <span class="vs08-sr-inclus-item">🚗 Location voiture</span>
                            <?php elseif (in_array($r['transfert_type'], ['groupes', 'prives'])): ?>
                            <span class="vs08-sr-inclus-item">🚐 Transferts</span>
                            <?php endif; ?>
                            <span class="vs08-sr-inclus-item">🏨 Hôtel</span>
                        </div>
                        <?php elseif ($r['type_voyage'] === 'sejour'): ?>
                        <div class="vs08-sr-inclus">
                            <span class="vs08-sr-inclus-item">✈️ Vol A/R</span>
                            <span class="vs08-sr-inclus-item">🏨 <?php echo esc_html($r['hotel_nom'] ?: 'Hôtel'); ?> <?php echo str_repeat('★', intval($r['etoiles'] ?? 0)); ?></span>
                            <span class="vs08-sr-inclus-item">🍽️ <?php echo esc_html($r['pension_label'] ?? 'All Inclusive'); ?></span>
                            <?php if (!empty($r['transfert_type']) && $r['transfert_type'] !== 'aucun'): ?>
                            <span class="vs08-sr-inclus-item">🚐 Transferts inclus</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="vs08-sr-meta">
                            <?php if ($r['duree']): ?><span class="vs08-sr-tag">🌙 <?php echo $r['duree']; ?> <?php echo $r['type_voyage'] === 'circuit' ? 'jours' : 'nuits'; ?></span><?php endif; ?>
                            <?php if ($r['type_voyage'] === 'circuit' && !empty($r['nb_etapes'])): ?><span class="vs08-sr-tag">📍 <?php echo $r['nb_etapes']; ?> étapes</span><?php endif; ?>
                            <?php if ($r['nb_parcours']): ?><span class="vs08-sr-tag">⛳ <?php echo $r['nb_parcours']; ?> parcours</span><?php endif; ?>
                            <?php if (!empty($r['themes'])): ?>
                                <?php foreach (array_slice(array_map('trim', explode(',', $r['themes'])), 0, 3) as $theme): ?>
                                <span class="vs08-sr-tag"><?php echo esc_html($theme); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($r['niveau'] && $r['niveau'] !== 'tous' && isset($niveau_labels[$r['niveau']])): ?><span class="vs08-sr-tag"><?php echo esc_html($niveau_labels[$r['niveau']]); ?></span><?php endif; ?>
                            <?php if ($r['type_voyage'] && $r['type_voyage'] !== 'circuit' && isset($types_labels[$r['type_voyage']])): ?><span class="vs08-sr-tag"><?php echo esc_html($types_labels[$r['type_voyage']]); ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="vs08-sr-footer">
                        <?php if ($r['prix']): ?>
                        <div>
                            <span class="vs08-sr-price-from">Dès</span>
                            <span class="vs08-sr-price-amount"><?php echo number_format($r['prix'], 0, ',', ' '); ?> €</span>
                            <span class="vs08-sr-price-per">/pers. <?php echo $r['has_vol'] ? 'tout compris' : 'hors vols'; ?></span>
                        </div>
                        <?php else: ?>
                        <div><span class="vs08-sr-price-per">Prix sur demande</span></div>
                        <?php endif; ?>
                        <span class="vs08-sr-btn">Voir <?php echo $r['type_voyage'] === 'circuit' ? 'ce circuit' : 'ce séjour'; ?> →</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof VS08Calendar !== 'undefined' && document.getElementById('sr-date-wrap')) {
        var availDates = <?php echo wp_json_encode($opts['dates']); ?>;
        window.srCalDate = new VS08Calendar({
            el:       '#sr-date-wrap',
            mode:     'range',
            inline:   false,
            input:    '#sr-date-start',
            inputEnd: '#sr-date-end',
            title:    '📅 Période de départ',
            subtitle: 'Départ au plus tôt → départ au plus tard',
            minDate:  new Date(),
            yearRange: [new Date().getFullYear(), new Date().getFullYear() + 2],
            highlightDates: availDates,
            onConfirm: function(dep, ret) {
                var opts = { day: 'numeric', month: 'short' };
                var txt = '📅 Entre ' + dep.toLocaleDateString('fr-FR', opts);
                if (ret) txt += ' et ' + ret.toLocaleDateString('fr-FR', opts);
                var trigger = document.getElementById('sr-date-trigger');
                if (trigger) { trigger.textContent = txt; trigger.style.color = '#0f2424'; trigger.style.borderColor = '#59b7b7'; }
            }
        });
    }
});
function vs08RemoveFilter(keys) {
    var url = new URL(window.location.href);
    keys.split(',').forEach(function(k){ url.searchParams.delete(k.trim()); });
    window.location.href = url.toString();
}
function vs08SortResults(val) {
    var grid = document.getElementById('vs08-sr-grid');
    if (!grid) return;
    var cards = Array.from(grid.querySelectorAll('.vs08-sr-card'));
    if (val === 'prix-asc')  cards.sort(function(a,b){ return parseFloat(a.dataset.prix) - parseFloat(b.dataset.prix); });
    if (val === 'prix-desc') cards.sort(function(a,b){ return parseFloat(b.dataset.prix) - parseFloat(a.dataset.prix); });
    if (val === 'duree-asc') cards.sort(function(a,b){ return parseInt(a.dataset.duree) - parseInt(b.dataset.duree); });
    cards.forEach(function(c){ grid.appendChild(c); });
}
</script>

<?php get_footer(); ?>
