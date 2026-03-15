<?php
class VS08V_Insurance {

    /**
     * Calcule le prix de l'assurance selon le prix par personne
     * Supporte 2 modes : 'fixe' (montant €) et 'pct' (pourcentage du prix)
     */
    public static function get_price($prix_par_pers) {
        $baremes = self::get_baremes();
        foreach ($baremes as $b) {
            if ($prix_par_pers >= $b['min'] && $prix_par_pers <= $b['max']) {
                $type = $b['type'] ?? 'fixe';
                if ($type === 'pct') {
                    return round($prix_par_pers * floatval($b['prix']) / 100, 2);
                }
                return floatval($b['prix']);
            }
        }
        // Au-delà du dernier barème
        $last = end($baremes);
        if ($last && $prix_par_pers > $last['max']) {
            $type = $last['type'] ?? 'fixe';
            if ($type === 'pct') {
                return round($prix_par_pers * floatval($last['prix']) / 100, 2);
            }
            return floatval($last['prix']);
        }
        return 0;
    }

    public static function get_baremes() {
        $baremes = get_option('vs08v_insurance_baremes', null);
        if ($baremes) return $baremes;
        return self::get_defaults();
    }

    public static function get_defaults() {
        return [
            ['min' => 0,    'max' => 849,   'prix' => 0,    'type' => 'fixe', 'label' => 'Moins de 850€'],
            ['min' => 850,  'max' => 1200,  'prix' => 40,   'type' => 'fixe', 'label' => '850€ – 1 200€'],
            ['min' => 1201, 'max' => 1800,  'prix' => 60,   'type' => 'fixe', 'label' => '1 201€ – 1 800€'],
            ['min' => 1801, 'max' => 2500,  'prix' => 85,   'type' => 'fixe', 'label' => '1 801€ – 2 500€'],
            ['min' => 2501, 'max' => 3500,  'prix' => 120,  'type' => 'fixe', 'label' => '2 501€ – 3 500€'],
            ['min' => 3501, 'max' => 16000, 'prix' => 4.20, 'type' => 'pct',  'label' => '3 501€ – 16 000€'],
        ];
    }

    public static function create_defaults() {
        if (!get_option('vs08v_insurance_baremes')) {
            update_option('vs08v_insurance_baremes', self::get_defaults());
        }
    }

    public static function admin_page() {
        if (isset($_POST['vs08v_save_baremes']) && check_admin_referer('vs08v_insurance')) {
            $baremes = [];
            if (!empty($_POST['baremes'])) {
                foreach ($_POST['baremes'] as $b) {
                    $baremes[] = [
                        'min'   => intval($b['min']),
                        'max'   => intval($b['max']),
                        'prix'  => floatval($b['prix']),
                        'type'  => ($b['type'] ?? 'fixe') === 'pct' ? 'pct' : 'fixe',
                        'label' => sanitize_text_field($b['label']),
                    ];
                }
            }
            update_option('vs08v_insurance_baremes', $baremes);
            echo '<div class="notice notice-success"><p>✅ Barèmes assurance enregistrés.</p></div>';
        }
        $baremes = self::get_baremes();
        ?>
        <style>
        .vs08i-wrap{font-family:'Segoe UI',sans-serif;max-width:920px}
        .vs08i-table{width:100%;border-collapse:collapse;margin-top:16px;background:#fff;border:1px solid #e5e5e5;border-radius:10px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.04)}
        .vs08i-table th{padding:10px 12px;text-align:left;font-size:12px;color:#888;font-weight:600;background:#fafafa;border-bottom:1px solid #f0f0f0}
        .vs08i-table td{padding:8px 12px;border-bottom:1px solid #f5f5f5;vertical-align:middle}
        .vs08i-table tr:last-child td{border-bottom:none}
        .vs08i-table input[type=text],.vs08i-table input[type=number]{padding:7px 10px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px;box-sizing:border-box}
        .vs08i-table select{padding:6px 8px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px;cursor:pointer;background:#fff}
        select.vs08i-type-pct{background:#fef3cd;color:#856404;font-weight:600;border-color:#f0d9a8}
        select.vs08i-type-fixe{background:#d4edda;color:#155724;font-weight:600;border-color:#b7dfb7}
        .vs08i-preview{font-size:12px;color:#666;font-style:italic;padding:2px 0}
        .vs08i-btn-add{background:none;border:1.5px dashed #b7dfdf;color:#3d9a9a;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;margin-top:12px}
        .vs08i-btn-add:hover{background:#edf8f8}
        .vs08i-btn-save{background:#0f2424;color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;margin-left:12px}
        .vs08i-btn-save:hover{background:#3d9a9a}
        .vs08i-btn-del{background:none;border:1px solid #e0e0e0;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:14px;color:#dc3545}
        .vs08i-btn-del:hover{background:#fee;border-color:#dc3545}
        .vs08i-unit{font-size:14px;font-weight:700;color:#555;min-width:16px}
        </style>
        <div class="wrap vs08i-wrap">
            <h1>🛡️ Barèmes Assurance Voyage</h1>
            <p style="color:#666">Le prix de l'assurance est proposé automatiquement selon le prix total du voyage par personne.<br>
            <strong>💰 Fixe</strong> = montant en € par personne · <strong>📊 %</strong> = pourcentage calculé sur le prix/pers.</p>
            <form method="post">
                <?php wp_nonce_field('vs08v_insurance'); ?>
                <table class="vs08i-table">
                    <thead><tr>
                        <th>Libellé</th>
                        <th style="width:90px">Min (€)</th>
                        <th style="width:90px">Max (€)</th>
                        <th style="width:110px">Mode</th>
                        <th style="width:110px">Valeur</th>
                        <th style="width:170px">Aperçu (milieu tranche)</th>
                        <th style="width:40px"></th>
                    </tr></thead>
                    <tbody id="vs08v-baremes">
                    <?php foreach ($baremes as $i => $b):
                        $type = $b['type'] ?? 'fixe';
                        $mid  = round(($b['min'] + $b['max']) / 2);
                        if ($type === 'pct') {
                            $simul   = round($mid * floatval($b['prix']) / 100, 2);
                            $preview = $simul . ' € pour ' . number_format($mid, 0, ',', ' ') . ' €/pers.';
                        } else {
                            $preview = floatval($b['prix']) . ' €/pers.';
                        }
                    ?>
                    <tr>
                        <td><input type="text" name="baremes[<?php echo $i;?>][label]" value="<?php echo esc_attr($b['label']);?>" style="width:100%"></td>
                        <td><input type="number" name="baremes[<?php echo $i;?>][min]" value="<?php echo esc_attr($b['min']);?>" style="width:80px"></td>
                        <td><input type="number" name="baremes[<?php echo $i;?>][max]" value="<?php echo esc_attr($b['max']);?>" style="width:80px"></td>
                        <td>
                            <select name="baremes[<?php echo $i;?>][type]" class="vs08i-type-<?php echo $type;?>" onchange="vs08iUpdateType(this)">
                                <option value="fixe" <?php selected($type, 'fixe');?>>💰 Fixe €</option>
                                <option value="pct" <?php selected($type, 'pct');?>>📊 % du prix</option>
                            </select>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:4px">
                                <input type="number" name="baremes[<?php echo $i;?>][prix]" value="<?php echo esc_attr($b['prix']);?>" step="0.01" style="width:80px">
                                <span class="vs08i-unit"><?php echo $type === 'pct' ? '%' : '€';?></span>
                            </div>
                        </td>
                        <td><div class="vs08i-preview"><?php echo esc_html($preview);?></div></td>
                        <td><button type="button" onclick="this.closest('tr').remove()" class="vs08i-btn-del">✕</button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top:14px">
                    <button type="button" class="vs08i-btn-add" onclick="vs08vAddBareme()">+ Ajouter une tranche</button>
                    <input type="submit" name="vs08v_save_baremes" class="vs08i-btn-save" value="💾 Enregistrer les barèmes">
                </p>
            </form>
        </div>
        <script>
        var vs08vBaremeIdx = <?php echo count($baremes); ?>;

        function vs08iUpdateType(sel) {
            var unit = sel.closest('tr').querySelector('.vs08i-unit');
            if (unit) unit.textContent = sel.value === 'pct' ? '%' : '€';
            sel.className = 'vs08i-type-' + sel.value;
        }

        function vs08vAddBareme() {
            var i = vs08vBaremeIdx++;
            var row = '<tr>'
                + '<td><input type="text" name="baremes['+i+'][label]" style="width:100%" placeholder="Nouvelle tranche"></td>'
                + '<td><input type="number" name="baremes['+i+'][min]" style="width:80px" placeholder="0"></td>'
                + '<td><input type="number" name="baremes['+i+'][max]" style="width:80px" placeholder="9999"></td>'
                + '<td><select name="baremes['+i+'][type]" class="vs08i-type-fixe" onchange="vs08iUpdateType(this)">'
                +   '<option value="fixe">💰 Fixe €</option><option value="pct">📊 % du prix</option>'
                + '</select></td>'
                + '<td><div style="display:flex;align-items:center;gap:4px">'
                +   '<input type="number" name="baremes['+i+'][prix]" step="0.01" style="width:80px" placeholder="0">'
                +   '<span class="vs08i-unit">€</span>'
                + '</div></td>'
                + '<td><div class="vs08i-preview">—</div></td>'
                + '<td><button type="button" onclick="this.closest(\'tr\').remove()" class="vs08i-btn-del">✕</button></td>'
                + '</tr>';
            document.querySelector('#vs08v-baremes').insertAdjacentHTML('beforeend', row);
        }
        </script>
        <?php
    }
}

// Page d'admin assurances
add_action('admin_menu', function() {
    add_submenu_page('edit.php?post_type=vs08_voyage', '🛡️ Assurances', '🛡️ Assurances', 'manage_options', 'vs08v-insurance', ['VS08V_Insurance', 'admin_page']);
});
