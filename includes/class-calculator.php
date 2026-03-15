<?php
class VS08V_Calculator {

    /**
     * Calcule le prix total d'un séjour selon la sélection
     */
    public static function calculate($voyage_id, $params) {
        $m      = VS08V_MetaBoxes::get($voyage_id);
        $duree  = intval($m['duree'] ?? 7);
        $result = ['lines' => [], 'total' => 0, 'acompte' => 0, 'solde' => 0];

        $nb_golfeurs    = intval($params['nb_golfeurs'] ?? 1);
        $nb_nongolfeurs = intval($params['nb_nongolfeurs'] ?? 0);
        $nb_total       = $nb_golfeurs + $nb_nongolfeurs;
        $type_chambre   = $params['type_chambre'] ?? 'double';
        $nb_chambres    = intval($params['nb_chambres'] ?? 1);
        $date_depart    = sanitize_text_field($params['date_depart'] ?? '');
        $prix_vol_api   = floatval($params['prix_vol'] ?? 0);

        // Parser le JSON rooms si disponible (chambres multiples de types différents)
        $rooms = [];
        if (!empty($params['rooms'])) {
            $rooms_raw = is_string($params['rooms']) ? json_decode(stripslashes($params['rooms']), true) : $params['rooms'];
            if (is_array($rooms_raw)) $rooms = $rooms_raw;
        }

        // Prix de base par type de chambre
        $prix_double      = floatval($m['prix_double'] ?? 0);
        $prix_triple      = floatval($m['prix_triple'] ?? 0);
        $prix_simple_supp = floatval($m['prix_simple_supp'] ?? 0);
        $simple_supp_type = isset($m['simple_supp_type']) ? $m['simple_supp_type'] : 'nuit';

        // 1. HÉBERGEMENT — calcul par chambre si rooms disponible, sinon fallback

        // Hébergement hôtel : calcul nuit par nuit pour gérer les séjours qui chevauchent plusieurs périodes
        if (!empty($m['saisons']) && $date_depart) {
            $ts_depart = strtotime(trim($date_depart));
            if ($ts_depart) {
                $saison_nuits = [];
                for ($n = 0; $n < $duree; $n++) {
                    $nuit_ts = strtotime(date('Y-m-d', $ts_depart) . ' +' . $n . ' days');
                    $matched = false;
                    foreach ($m['saisons'] as $si => $s) {
                        if (empty($s['date_debut']) || empty($s['date_fin'])) continue;
                        $supp_val = floatval($s['supp'] ?? 0);
                        if ($supp_val <= 0) continue;
                        $sd = strtotime($s['date_debut']);
                        $sf = strtotime($s['date_fin']);
                        if (!$sd || !$sf) continue;
                        if ($nuit_ts >= $sd && $nuit_ts <= $sf) {
                            $key = 'p' . $si;
                            if (!isset($saison_nuits[$key])) {
                                $saison_nuits[$key] = [
                                    'label' => $s['label'] ?? 'Période',
                                    'prix'  => $supp_val,
                                    'nuits' => 0,
                                    'du'    => date('d/m', $nuit_ts),
                                    'au'    => date('d/m', $nuit_ts),
                                ];
                            }
                            $saison_nuits[$key]['nuits']++;
                            $saison_nuits[$key]['au'] = date('d/m', $nuit_ts);
                            $matched = true;
                            break;
                        }
                    }
                }
                foreach ($saison_nuits as $sn) {
                    $montant_saison = $sn['prix'] * $sn['nuits'] * $nb_total;
                    $date_range = $sn['du'] . ' → ' . $sn['au'];
                    $result['lines'][] = [
                        'label'   => '🏨 Hébergement hôtel (' . $sn['label'] . ')',
                        'montant' => $montant_saison,
                        'detail'  => $sn['prix'].'€/nuit × '.$sn['nuits'].' nuit'.($sn['nuits']>1?'s':'').' × '.$nb_total.' pers. ('.$date_range.')',
                    ];
                }
            }
        }

        if (!empty($rooms)) {
            // Calcul chambre par chambre
            $total_hebergement = 0;
            $nb_simples = 0;
            foreach ($rooms as $ri => $room) {
                $tc = isset($room['tc']) ? $room['tc'] : 'double';
                $ng = intval($room['ng'] ?? 0);
                $nn = intval($room['nn'] ?? 0);
                $nb_in_room = $ng + $nn;
                if ($nb_in_room === 0) continue;

                switch ($tc) {
                    case 'simple':
                        $pn = $prix_double;
                        if ($simple_supp_type === 'nuit') $pn += $prix_simple_supp;
                        $nb_simples += $nb_in_room;
                        break;
                    case 'triple':
                        $pn = $prix_triple > 0 ? $prix_triple : $prix_double;
                        break;
                    default:
                        $pn = $prix_double;
                        break;
                }
                $montant_room = $pn * $duree * $nb_in_room;
                $total_hebergement += $montant_room;
                $label_types = ['double'=>'Double','simple'=>'Individuelle','triple'=>'Triple'];
                $result['lines'][] = ['label' => '🛏️ Chambre '.($ri+1).' ('.($label_types[$tc]??$tc).')', 'montant' => $montant_room, 'detail' => $pn.'€/nuit × '.$duree.' nuits × '.$nb_in_room.' pers.'];
            }
            if ($nb_simples > 0 && $simple_supp_type === 'sejour' && $prix_simple_supp > 0) {
                $result['lines'][] = ['label' => 'Suppl. chambre simple (par séjour)', 'montant' => $prix_simple_supp * $nb_simples, 'detail' => $prix_simple_supp.'€ × '.$nb_simples.' pers.'];
            }
        } else {
            // Fallback : un seul type de chambre (ancien fonctionnement)
            $prix_nuit = $prix_double;
            if ($type_chambre === 'simple') {
                if ($simple_supp_type !== 'sejour') $prix_nuit += $prix_simple_supp;
            } elseif ($type_chambre === 'triple' && $prix_triple > 0) {
                $prix_nuit = $prix_triple;
            }

            $total_hebergement_golfeurs    = $prix_nuit * $duree * $nb_golfeurs;
            $total_hebergement_nongolfeurs = $prix_nuit * $duree * $nb_nongolfeurs;
            if ($type_chambre === 'simple' && $simple_supp_type === 'sejour' && $prix_simple_supp > 0) {
                $result['lines'][] = ['label' => 'Suppl. chambre simple (par séjour)', 'montant' => $prix_simple_supp * $nb_total, 'detail' => $prix_simple_supp.'€ × '.$nb_total.' pers.'];
            }
            if ($nb_golfeurs > 0) {
                $result['lines'][] = ['label' => 'Hébergement golfeurs ('.$prix_nuit.'€/nuit)', 'montant' => $total_hebergement_golfeurs, 'detail' => $prix_nuit.'€ × '.$duree.' nuits × '.$nb_golfeurs.' pers.'];
            }
            if ($nb_nongolfeurs > 0) {
                $result['lines'][] = ['label' => 'Hébergement non-golfeurs', 'montant' => $total_hebergement_nongolfeurs, 'detail' => $prix_nuit.'€ × '.$duree.' nuits × '.$nb_nongolfeurs.' pers.'];
            }
        }

        // 2. GREEN FEES — prix_greenfees = forfait total séjour par golfeur (déjà calculé)
        $prix_gf_total = floatval($m['prix_greenfees'] ?? 0);
        if ($prix_gf_total > 0 && $nb_golfeurs > 0) {
            $result['lines'][] = ['label' => '⛳ Forfait green fees', 'montant' => $prix_gf_total * $nb_golfeurs, 'detail' => $prix_gf_total.'€/golfeur × '.$nb_golfeurs.' golfeur'.($nb_golfeurs>1?'s':'')];
        }
        if ($prix_gf_total > 0 && $nb_nongolfeurs > 0) {
            $reduction_eur = floatval($m['reduction_nongolfeur'] ?? 0);
            $prix_nongolf_unit = max(0, $prix_gf_total - $reduction_eur);
            $prix_nongolf_total = $prix_nongolf_unit * $nb_nongolfeurs;
            if ($prix_nongolf_total > 0) {
                $reduction_lbl = $reduction_eur > 0 ? ' -'.$reduction_eur.'€/pers.' : '';
                $result['lines'][] = ['label' => '👤 Accompagnants non-golfeurs'.$reduction_lbl, 'montant' => $prix_nongolf_total, 'detail' => round($prix_nongolf_unit, 2).'€ × '.$nb_nongolfeurs.' pers.'];
            }
        }

        // 3. VOLS
        $prix_vol_par_pers = $prix_vol_api > 0 ? $prix_vol_api : floatval($m['prix_vol_base'] ?? 0);
        $taxe              = floatval($m['prix_taxe'] ?? 0);
        $transfert         = floatval($m['prix_transfert'] ?? 0);
        if ($prix_vol_par_pers > 0) {
            $result['lines'][] = ['label' => 'Vols aller-retour '.($prix_vol_api > 0 ? '(tarif en temps réel)' : '(estimé)'), 'montant' => ($prix_vol_par_pers) * $nb_total, 'detail' => $prix_vol_par_pers.'€ × '.$nb_total.' pers.'];
        }

        // 3b. BAGAGES — depuis les champs Tarifs & Chambres (défaut 120€ si non renseigné)
        $prix_bagage_soute = floatval($m['prix_bagage_soute'] ?? 120);
        $prix_bagage_golf  = floatval($m['prix_bagage_golf'] ?? 120);
        if (empty($m['prix_bagage_soute']) && !isset($m['prix_bagage_soute'])) $prix_bagage_soute = 120;
        if (empty($m['prix_bagage_golf'])  && !isset($m['prix_bagage_golf']))  $prix_bagage_golf  = 120;

        // Règle compagnie : Tunisair (TU) et Royal Air Maroc (AT) offrent le bagage golf
        $airline_iata = strtoupper(sanitize_text_field($params['airline_iata'] ?? ''));
        $golf_bag_free_airlines = ['TU', 'AT'];
        $golf_bag_free = in_array($airline_iata, $golf_bag_free_airlines);

        $nb_bagage_soute = intval($params['nb_bagage_soute'] ?? $nb_total);
        $nb_bagage_golf  = intval($params['nb_bagage_golf'] ?? $nb_golfeurs);

        if ($prix_bagage_soute > 0 && $nb_bagage_soute > 0) {
            $result['lines'][] = ['label' => '🧳 Bagage soute', 'montant' => $prix_bagage_soute * $nb_bagage_soute, 'detail' => $prix_bagage_soute.'€ × '.$nb_bagage_soute.' bagage'.($nb_bagage_soute > 1 ? 's' : '')];
        }
        if ($nb_bagage_golf > 0 && $nb_golfeurs > 0) {
            if ($golf_bag_free) {
                $result['lines'][] = ['label' => '🏌️ Bagage golf', 'montant' => 0, 'detail' => 'Offert par '.($airline_iata === 'TU' ? 'Tunisair' : 'Royal Air Maroc')];
            } elseif ($prix_bagage_golf > 0) {
                $result['lines'][] = ['label' => '🏌️ Bagage golf', 'montant' => $prix_bagage_golf * $nb_bagage_golf, 'detail' => $prix_bagage_golf.'€ × '.$nb_bagage_golf.' bagage'.($nb_bagage_golf > 1 ? 's' : '')];
            }
        }

        if ($taxe > 0) {
            $result['lines'][] = ['label' => 'Taxes aéroport', 'montant' => $taxe * $nb_total, 'detail' => $taxe.'€ × '.$nb_total.' pers.'];
        }

        // Transferts : prix par personne
        $transfert_type = $m['transfert_type'] ?? '';
        if ($transfert_type === 'voiture' && !empty($m['voiture_periodes']) && $date_depart) {
            $ts = strtotime($date_depart);
            foreach ($m['voiture_periodes'] as $vp) {
                if (empty($vp['date_debut']) || empty($vp['date_fin'])) continue;
                if ($ts >= strtotime($vp['date_debut']) && $ts <= strtotime($vp['date_fin'])) {
                    $prix_loc = floatval($vp['prix'] ?? 0);
                    if ($prix_loc > 0) {
                        $result['lines'][] = ['label' => '🚗 Location de voiture', 'montant' => $prix_loc, 'detail' => ($vp['label'] ?? 'Période') . ' · forfait'];
                    }
                    break;
                }
            }
        } elseif ($transfert > 0) {
            $montant_transfert = $transfert * $nb_total;
            $result['lines'][] = ['label' => '🚐 Transferts', 'montant' => $montant_transfert, 'detail' => $transfert.'€ × '.$nb_total.' pers.'];
        }

        // 4. TOTAL — arrondi à l'euro supérieur
        $total = 0;
        foreach ($result['lines'] as $l) $total += $l['montant'];
        $total = (int) ceil($total);

        // 4b. MARGE (€ ou %) — s'applique à tous les tarifs, y compris vol trouvé en front
        if (!empty($m['marge_activate']) && isset($m['marge_valeur']) && (floatval($m['marge_valeur']) > 0)) {
            $marge_type   = isset($m['marge_type']) ? $m['marge_type'] : 'pct';
            $marge_valeur = floatval($m['marge_valeur']);
            if ($marge_type === 'pct') {
                $marge_eur = $total * ($marge_valeur / 100);
            } else {
                $marge_eur = $marge_valeur;
            }
            $total += (int) ceil($marge_eur);
            $result['lines'][] = ['label' => 'Marge agence', 'montant' => (int) ceil($marge_eur), 'detail' => $marge_type === 'pct' ? $marge_valeur.'%' : $marge_valeur.'€'];
        }

        $result['total']   = $total;
        $result['nb_total'] = $nb_total;
        $result['par_pers'] = $nb_total > 0 ? (int) ceil($total / $nb_total) : 0;

        // 5. ACOMPTE — ne peut JAMAIS être inférieur au prix total des vols
        $acompte_pct       = floatval($m['acompte_pct'] ?? 30);
        $acompte_base      = $total * $acompte_pct / 100;
        $cout_vol_total    = $prix_vol_par_pers * $nb_total;

        // Si l'acompte de base ne couvre pas les vols → augmenter le %
        $acompte_pct_final = $acompte_pct;
        if ($cout_vol_total > 0 && $acompte_base < $cout_vol_total && $total > 0) {
            // Vrai % du vol dans le total
            $pct_reel = ($cout_vol_total / $total) * 100;
            // Arrondir au palier de 5% supérieur (43% → 45%, 51% → 55%)
            $acompte_pct_final = ceil($pct_reel / 5) * 5;
            $acompte_base = $total * $acompte_pct_final / 100;
        }

        // Arrondi à l'euro supérieur — montant envoyé au serveur de paiement
        $result['acompte']          = (int) ceil($acompte_base);
        $result['solde']            = $total - $result['acompte'];
        $result['acompte_pct']      = $acompte_pct;        // % configuré dans le back-office
        $result['acompte_pct_final']= $acompte_pct_final;  // % réellement appliqué (peut être plus élevé)

        // Délai solde
        $result['delai_solde'] = intval($m['delai_solde'] ?? 30);

        return $result;
    }

    /**
     * Prix assurace selon barème
     */
    public static function get_insurance_price($prix_par_pers) {
        return VS08V_Insurance::get_price($prix_par_pers);
    }
}
