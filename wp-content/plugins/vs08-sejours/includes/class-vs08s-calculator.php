<?php
if (!defined('ABSPATH')) exit;

class VS08S_Calculator {

    /**
     * Calcule le prix total d'un séjour.
     *
     * @param int    $sejour_id    ID du produit séjour
     * @param array  $params       Paramètres sélectionnés par le client :
     *   - date_depart (string YYYY-MM-DD)
     *   - aeroport (string IATA code)
     *   - nb_adultes (int)
     *   - nb_chambres (int)
     *   - vol_price (float) — prix vol aller-retour par personne (de Duffel)
     *   - hotel_net (float) — prix net hôtel total (de Bedsonline)
     *   - hotel_rate_key (string) — clé tarif Bedsonline
     *   - hotel_board (string) — code board (AI, HB, etc.)
     *   - hotel_room_name (string)
     *   - bagage_soute (int) — nombre de bagages soute
     *   - bagage_cabine (int) — nombre de bagages cabine
     *   - assurance (bool)
     * @return array  Devis détaillé
     */
    public static function compute($sejour_id, $params) {
        $m = VS08S_Meta::get($sejour_id);
        $nb = max(1, intval($params['nb_adultes'] ?? 2));
        $nb_chambres = max(1, intval($params['nb_chambres'] ?? 1));

        // ── Prix hôtel net (depuis Bedsonline) ──
        $hotel_net = floatval($params['hotel_net'] ?? 0);

        // ── Marge agence ──
        $marge = self::compute_marge($m, $hotel_net, $nb);

        // ── Vol ──
        $vol_pax = floatval($params['vol_price'] ?? 0);
        $vol_total = $vol_pax * $nb;

        // ── Supplément aéroport ──
        $supp_aero = 0;
        $aeroport = strtoupper($params['aeroport'] ?? '');
        foreach ($m['aeroports'] ?? [] as $a) {
            if (strtoupper($a['code'] ?? '') === $aeroport) {
                $supp_aero = floatval($a['supplement'] ?? 0);
                break;
            }
        }
        $supp_aero_total = $supp_aero * $nb;

        // ── Transferts ──
        $transfert_pax = floatval($m['transfert_prix'] ?? 0);
        $transfert_total = $transfert_pax * $nb;

        // ── Bagages ──
        $bag_soute_qty = intval($params['bagage_soute'] ?? 0);
        $bag_soute_prix = floatval($m['prix_bagage_soute'] ?? 0);
        $bag_soute_total = $bag_soute_qty * $bag_soute_prix;

        $bag_cabine_qty = intval($params['bagage_cabine'] ?? 0);
        $bag_cabine_prix = floatval($m['prix_bagage_cabine'] ?? 0);
        $bag_cabine_total = $bag_cabine_qty * $bag_cabine_prix;

        // ── Assurance ──
        $assurance_prix = 0;
        if (!empty($params['assurance'])) {
            // Prix assurance : environ 4% du total hors assurance
            $base_assurance = $hotel_net + $vol_total + $transfert_total + $marge;
            $assurance_prix = round($base_assurance * 0.04, 2);
        }

        // ── TOTAL ──
        $total = $hotel_net + $vol_total + $supp_aero_total + $transfert_total + $marge + $bag_soute_total + $bag_cabine_total + $assurance_prix;

        // ── Acompte ──
        $acompte_pct = floatval($m['acompte_pct'] ?? 30);
        $delai_solde = intval($m['delai_solde'] ?? 30);
        $date_depart = $params['date_depart'] ?? '';
        $jours_avant = $date_depart ? max(0, (strtotime($date_depart) - time()) / 86400) : 999;
        $payer_tout = $jours_avant <= $delai_solde;
        $acompte = $payer_tout ? $total : round($total * $acompte_pct / 100, 2);

        // ── Lignes de devis ──
        $lines = [];
        $lines[] = ['label' => '🏨 Hébergement ' . VS08S_Bedsonline::board_label($params['hotel_board'] ?? 'AI'), 'montant' => $hotel_net, 'detail' => $nb_chambres . ' ch. × ' . intval($m['duree'] ?? 7) . ' nuits'];
        if ($vol_total > 0) $lines[] = ['label' => '✈️ Vols aller-retour', 'montant' => $vol_total, 'detail' => $nb . ' × ' . number_format($vol_pax, 0, ',', ' ') . '€'];
        if ($supp_aero_total > 0) $lines[] = ['label' => '✈️ Supplément aéroport ' . $aeroport, 'montant' => $supp_aero_total, 'detail' => $nb . ' × ' . number_format($supp_aero, 0, ',', ' ') . '€'];
        if ($transfert_total > 0) $lines[] = ['label' => '🚌 Transferts', 'montant' => $transfert_total, 'detail' => $nb . ' × ' . number_format($transfert_pax, 0, ',', ' ') . '€'];
        if ($bag_soute_total > 0) $lines[] = ['label' => '🧳 Bagage soute', 'montant' => $bag_soute_total, 'detail' => $bag_soute_qty . ' × ' . number_format($bag_soute_prix, 0, ',', ' ') . '€'];
        if ($bag_cabine_total > 0) $lines[] = ['label' => '🧳 Bagage cabine', 'montant' => $bag_cabine_total, 'detail' => $bag_cabine_qty . ' × ' . number_format($bag_cabine_prix, 0, ',', ' ') . '€'];
        $lines[] = ['label' => 'Marge agence', 'montant' => $marge, 'detail' => ''];
        if ($assurance_prix > 0) $lines[] = ['label' => '🛡️ Assurance multirisques', 'montant' => $assurance_prix, 'detail' => ''];

        return [
            'lines'       => $lines,
            'total'       => round($total, 2),
            'acompte'     => round($acompte, 2),
            'acompte_pct' => $acompte_pct,
            'payer_tout'  => $payer_tout,
            'nb_total'    => $nb,
            'nb_chambres' => $nb_chambres,
            'hotel_net'   => $hotel_net,
            'vol_pax'     => $vol_pax,
            'marge'       => $marge,
            'transfert'   => $transfert_total,
            'assurance'   => $assurance_prix,
            'prix_par_personne' => $nb > 0 ? round($total / $nb, 2) : $total,
        ];
    }

    /**
     * Calcule la marge agence selon le type configuré.
     */
    private static function compute_marge($m, $hotel_net, $nb_pax) {
        $type = $m['marge_type'] ?? 'pourcentage';
        $val  = floatval($m['marge_valeur'] ?? 15);

        switch ($type) {
            case 'pourcentage':
                return round($hotel_net * $val / 100, 2);
            case 'fixe_personne':
                return round($val * $nb_pax, 2);
            case 'fixe_total':
                return round($val, 2);
            default:
                return round($hotel_net * 0.15, 2);
        }
    }

    /**
     * Prix d'appel estimé pour l'affichage sur les cards de la page d'accueil.
     * Basé sur le dernier tarif connu ou un prix fixe de référence.
     */
    public static function prix_appel($sejour_id) {
        $m = VS08S_Meta::get($sejour_id);
        $prix_ref = get_post_meta($sejour_id, '_vs08s_prix_min', true);
        return floatval($prix_ref ?: 0);
    }
}
