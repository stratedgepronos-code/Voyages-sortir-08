<?php
if (!defined('ABSPATH')) exit;

class VS08C_Calculator {

    /**
     * Calcule le prix total d'un circuit selon la sélection.
     */
    public static function calculate($circuit_id, $params) {
        $m     = VS08C_Meta::get($circuit_id);
        $duree = intval($m['duree'] ?? 7);
        $result = ['lines' => [], 'total' => 0, 'par_pers' => 0, 'nb_total' => 0];

        $nb_adultes = intval($params['nb_adultes'] ?? 2);
        $nb_enfants = intval($params['nb_enfants'] ?? 0);
        $nb_total   = $nb_adultes + $nb_enfants;
        $nb_chambres = intval($params['nb_chambres'] ?? 1);
        $date_depart = sanitize_text_field($params['date_depart'] ?? '');
        $aeroport    = strtoupper(sanitize_text_field($params['aeroport'] ?? ''));
        $prix_vol_api = floatval($params['prix_vol'] ?? 0);

        $result['nb_total']   = $nb_total;
        $result['nb_adultes'] = $nb_adultes;
        $result['nb_enfants'] = $nb_enfants;

        // Parse rooms si disponible
        $rooms = [];
        if (!empty($params['rooms'])) {
            $rooms_raw = is_string($params['rooms']) ? json_decode(stripslashes($params['rooms']), true) : $params['rooms'];
            if (is_array($rooms_raw)) $rooms = $rooms_raw;
        }

        $prix_base    = floatval($m['prix_double'] ?? 0);
        $prix_simple  = floatval($m['prix_simple_supp'] ?? 0);
        $simple_type  = $m['simple_supp_type'] ?? 'total';
        $prix_triple  = floatval($m['prix_triple'] ?? 0);

        // ── 1. CIRCUIT BASE (par personne, en double) ──
        $montant_base_adultes = $prix_base * $nb_adultes;
        $result['lines'][] = [
            'label'   => '🗺️ Circuit ' . $duree . ' nuits',
            'montant' => $montant_base_adultes,
            'detail'  => number_format($prix_base, 0) . '€/pers × ' . $nb_adultes . ' adulte(s)',
        ];

        // Enfants
        if ($nb_enfants > 0) {
            $reduc = floatval($m['reduc_enfant'] ?? 0);
            $prix_enfant = $prix_base * (1 - $reduc / 100);
            $montant_enfants = $prix_enfant * $nb_enfants;
            $result['lines'][] = [
                'label'   => '👶 Enfant(s) (-' . intval($reduc) . '%)',
                'montant' => $montant_enfants,
                'detail'  => number_format($prix_enfant, 0) . '€/pers × ' . $nb_enfants . ' enfant(s)',
            ];
        }

        // ── 2. SUPPLÉMENT CHAMBRE INDIVIDUELLE ──
        if (!empty($rooms)) {
            $supp_total = 0;
            foreach ($rooms as $room) {
                $type = $room['type'] ?? 'double';
                if ($type === 'simple' || $type === 'individuelle') {
                    $supp = ($simple_type === 'nuit') ? $prix_simple * $duree : $prix_simple;
                    $supp_total += $supp;
                }
            }
            if ($supp_total > 0) {
                $result['lines'][] = [
                    'label'   => '🛏️ Supp. chambre individuelle',
                    'montant' => $supp_total,
                    'detail'  => ($simple_type === 'nuit') ? $prix_simple . '€/nuit × ' . $duree . ' nuits' : number_format($supp_total, 0) . '€ forfait',
                ];
            }
        }

        // ── 3. SUPPLÉMENT DATE (depuis périodes) ──
        if ($date_depart && !empty($m['dates_depart'])) {
            $ts_depart = strtotime($date_depart);
            foreach ($m['dates_depart'] as $per) {
                $ts_debut = strtotime($per['date_debut'] ?? '');
                $ts_fin   = strtotime($per['date_fin'] ?? '');
                if (!$ts_debut || !$ts_fin || !$ts_depart) continue;
                if ($ts_depart >= $ts_debut && $ts_depart <= $ts_fin) {
                    $supp_date = floatval($per['supp'] ?? 0);
                    if ($supp_date > 0) {
                        $result['lines'][] = [
                            'label'   => '📅 Supplément période',
                            'montant' => $supp_date * $nb_total,
                            'detail'  => $supp_date . '€/pers × ' . $nb_total . ' pers.',
                        ];
                    }
                    break;
                }
            }
        }

        // ── 4. VOLS ──
        $prix_vol = $prix_vol_api > 0 ? $prix_vol_api : floatval($m['prix_vol_base'] ?? 0);
        $supp_aero = 0;
        if ($aeroport && !empty($m['aeroports'])) {
            foreach ($m['aeroports'] as $a) {
                if (strtoupper($a['code'] ?? '') === $aeroport) {
                    $supp_aero = floatval($a['supp'] ?? 0);
                    break;
                }
            }
        }
        $prix_vol_final = $prix_vol + $supp_aero;
        if ($prix_vol_final > 0) {
            $result['lines'][] = [
                'label'   => '✈️ Vols' . ($supp_aero > 0 ? ' (supp. aéroport +' . intval($supp_aero) . '€)' : ''),
                'montant' => $prix_vol_final * $nb_total,
                'detail'  => number_format($prix_vol_final, 0) . '€/pers × ' . $nb_total . ' pers.',
            ];
        }

        // ── 5. OPTIONS / SUPPLÉMENTS ──
        $options_raw = $params['options'] ?? '';
        $options_circuit = $m['options'] ?? [];
        if (!empty($options_raw) && !empty($options_circuit)) {
            $options_decoded = is_string($options_raw) ? json_decode(stripslashes($options_raw), true) : $options_raw;
            if (is_array($options_decoded)) {
                foreach ($options_circuit as $opt) {
                    $id = $opt['id'] ?? '';
                    $qty = isset($options_decoded[$id]) ? max(0, intval($options_decoded[$id])) : 0;
                    if ($qty <= 0) continue;
                    $prix_opt = floatval($opt['prix'] ?? 0);
                    $type = $opt['type'] ?? 'par_pers';
                    if ($type === 'par_pers') {
                        $montant = $prix_opt * $qty * $nb_total;
                        $result['lines'][] = ['label' => '🎁 ' . ($opt['label'] ?? 'Option'), 'montant' => $montant, 'detail' => $prix_opt . '€/pers × ' . $nb_total . ' pers.'];
                    } elseif ($type === 'fixe') {
                        $montant = $prix_opt * $qty;
                        $result['lines'][] = ['label' => '🎁 ' . ($opt['label'] ?? 'Option'), 'montant' => $montant, 'detail' => $prix_opt . '€ × ' . $qty];
                    } else {
                        $montant = $prix_opt * $qty;
                        $result['lines'][] = ['label' => '🎁 ' . ($opt['label'] ?? 'Option'), 'montant' => $montant, 'detail' => $prix_opt . '€ × ' . $qty];
                    }
                }
            }
        }

        // ── 6. TAXES ──
        $taxes = floatval($m['prix_taxe'] ?? 0);
        if ($taxes > 0) {
            $result['lines'][] = [
                'label'   => '📋 Taxes & frais de dossier',
                'montant' => $taxes * $nb_total,
                'detail'  => $taxes . '€/pers × ' . $nb_total . ' pers.',
            ];
        }

        // ── 7. TRANSFERTS ──
        $transfert = floatval($m['prix_transfert'] ?? 0);
        if ($transfert > 0) {
            $result['lines'][] = [
                'label'   => '🚐 Transferts',
                'montant' => $transfert * $nb_total,
                'detail'  => $transfert . '€/pers × ' . $nb_total . ' pers.',
            ];
        }

        // ── 8. BAGAGE ──
        $bagage = floatval($m['prix_bagage'] ?? 0);
        if ($bagage > 0) {
            $result['lines'][] = [
                'label'   => '🧳 Bagage (soute)',
                'montant' => $bagage * $nb_total,
                'detail'  => $bagage . '€/pers × ' . $nb_total . ' pers.',
            ];
        }

        // ── SOUS-TOTAL (avant marge) ──
        $total = 0;
        foreach ($result['lines'] as $line) {
            $total += $line['montant'];
        }

        // ── 9. MARGE (globale ou onglet circuit selon réglages) ──
        $mg            = VS08C_Marge::get_effective_for_circuit((int) $circuit_id);
        $marge_type    = $mg['marge_type'];
        $marge_pct     = $mg['marge_pct'];
        $marge_montant = $mg['marge_montant'];
        $marge_value   = 0;
        if ($marge_type === 'montant' && $marge_montant > 0) {
            $marge_value = $marge_montant;
            $result['lines'][] = [
                'label'   => '📊 Marge',
                'montant' => $marge_value,
                'detail'  => 'Forfait',
            ];
        } elseif (($marge_type === 'pct' || $marge_type === '') && $marge_pct > 0) {
            $marge_value = round($total * $marge_pct / 100, 2);
            $result['lines'][] = [
                'label'   => '📊 Marge (' . $marge_pct . ' %)',
                'montant' => $marge_value,
                'detail'  => $marge_pct . '% du sous-total',
            ];
        }
        $total += $marge_value;

        $result['total']    = round($total, 2);
        $result['par_pers'] = $nb_total > 0 ? round($total / $nb_total, 2) : 0;

        // Acompte — ne peut JAMAIS être inférieur au prix des vols + bagages
        $acompte_pct = floatval($m['acompte_pct'] ?? 30);
        $acompte     = ceil($total * $acompte_pct / 100);

        // Plancher : vols + bagages
        $cout_vol_total = $prix_vol_final * $nb_total;
        $bagage_total   = floatval($m['prix_bagage'] ?? 0) * $nb_total;
        $plancher_vol   = $cout_vol_total + $bagage_total;
        if ($plancher_vol > 0 && $acompte < $plancher_vol && $total > 0) {
            $pct_reel    = ($plancher_vol / $total) * 100;
            $acompte_pct = ceil($pct_reel / 5) * 5;
            $acompte     = ceil($total * $acompte_pct / 100);
        }

        $result['acompte']     = $acompte;
        $result['acompte_pct'] = $acompte_pct;
        $result['solde']       = $total - $acompte;

        return $result;
    }
}
