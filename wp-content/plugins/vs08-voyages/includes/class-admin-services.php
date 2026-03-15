<?php
/**
 * VS08 Voyages — Back-office Options de vol par compagnie
 * Chemin : /wp-content/plugins/vs08-voyages/includes/class-admin-services.php
 *
 * Données stockées dans wp_options clé 'vs08_airline_services' :
 * {
 *   "TO": { "label": "Transavia", "options": [
 *       { "icon": "🧳", "label": "Bagage soute 23 kg", "desc": "En soute · 23 kg max", "price": 35 }
 *   ]}
 * }
 */

if (!defined('ABSPATH')) exit;

// ── Menu admin ──────────────────────────────────────────────────────────────
add_action('admin_menu', function() {
    add_menu_page(
        'VS08 — Options de vol',
        '✈️ Options de vol',
        'manage_options',
        'vs08-airline-services',
        'vs08_admin_services_page',
        'dashicons-airplane',
        56
    );
});

// ── Sauvegarde ──────────────────────────────────────────────────────────────
add_action('admin_init', function() {
    if (
        isset($_POST['vs08_services_nonce']) &&
        wp_verify_nonce($_POST['vs08_services_nonce'], 'vs08_save_services') &&
        current_user_can('manage_options')
    ) {
        $raw   = $_POST['vs08_airlines'] ?? [];
        $saved = [];

        foreach ($raw as $iata => $airline) {
            $iata  = strtoupper(sanitize_text_field($iata));
            $label = sanitize_text_field($airline['label'] ?? '');
            if (empty($iata) || empty($label)) continue;

            $options = [];
            foreach ($airline['options'] ?? [] as $opt) {
                $lbl   = sanitize_text_field($opt['label'] ?? '');
                $price = floatval($opt['price'] ?? 0);
                if (empty($lbl) || $price <= 0) continue;
                $options[] = [
                    'icon'    => sanitize_text_field($opt['icon']    ?? '🛄'),
                    'label'   => $lbl,
                    'desc'    => sanitize_text_field($opt['desc']    ?? ''),
                    'price'   => $price,
                    'default' => intval($opt['default'] ?? 0),
                ];
            }

            $saved[$iata] = ['label' => $label, 'options' => $options];
        }

        update_option('vs08_airline_services', $saved);
        wp_redirect(add_query_arg(['page' => 'vs08-airline-services', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }
});

// ── Page HTML ────────────────────────────────────────────────────────────────
function vs08_admin_services_page() {
    $data  = get_option('vs08_airline_services', []);
    $saved = !empty($_GET['saved']);

    $defaults = [
        '3O' => 'Air Arabia Maroc',
        'AF' => 'Air France',
        'AT' => 'Royal Air Maroc',
        'FR' => 'Ryanair',
        'IB' => 'Iberia',
        'TO' => 'Transavia',
        'TU' => 'Tunisair',
        'U2' => 'easyJet',
        'VY' => 'Vueling',
    ];

    $airlines = $data;
    foreach ($defaults as $iata => $name) {
        if (!isset($airlines[$iata])) {
            $airlines[$iata] = ['label' => $name, 'options' => []];
        }
    }
    ksort($airlines);

    $icons = ['🧳', '🎒', '💺', '🛄', '📦'];
    ?>
    <style>
    .vs08-wrap { font-family: 'Segoe UI', sans-serif; max-width: 980px; }
    .vs08-block { background: #fff; border: 1px solid #e5e5e5; border-radius: 10px; padding: 20px; margin-bottom: 18px; box-shadow: 0 2px 6px rgba(0,0,0,.04); }
    .vs08-block-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
    .vs08-block-head img { width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e5e5e5; object-fit: contain; background: #fafafa; }
    .vs08-iata-badge { background: #edf8f8; color: #3d9a9a; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
    .vs08-table { width: 100%; border-collapse: collapse; }
    .vs08-table th { padding: 8px 10px; text-align: left; font-size: 11px; color: #999; font-weight: 600; background: #fafafa; border-bottom: 1px solid #f0f0f0; }
    .vs08-table td { padding: 7px 10px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; }
    .vs08-table input[type=text] { padding: 7px 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; }
    .vs08-table input[type=number] { padding: 7px 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 13px; width: 80px; }
    .vs08-table select { padding: 5px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 18px; cursor: pointer; }
    .vs08-btn-add { background: none; border: 1.5px dashed #b7dfdf; color: #3d9a9a; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; margin-top: 10px; }
    .vs08-btn-add:hover { background: #edf8f8; }
    .vs08-save-bar { position: sticky; bottom: 0; background: rgba(255,255,255,.96); padding: 14px 0; border-top: 1px solid #e0e0e0; backdrop-filter: blur(4px); margin-top: 24px; }
    .vs08-btn-save { background: #0f2424; color: #fff; border: none; padding: 12px 32px; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; }
    .vs08-btn-save:hover { background: #3d9a9a; }
    .vs08-add-airline { background: #fdf6e9; border: 1.5px dashed #e8c97a; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    </style>

    <div class="wrap vs08-wrap">
        <h1>✈️ Options de vol par compagnie</h1>
        <p style="color:#666;margin-bottom:20px">
            Configurez les options proposées à l'étape vol du tunnel de réservation.<br>
            <strong>Les lignes sans libellé ou sans prix sont ignorées.</strong>
        </p>

        <?php if ($saved): ?>
            <div class="notice notice-success is-dismissible"><p>✅ Options enregistrées avec succès !</p></div>
        <?php endif; ?>

        <form method="POST">
            <?php wp_nonce_field('vs08_save_services', 'vs08_services_nonce'); ?>

            <div id="vs08-airlines-container">
            <?php foreach ($airlines as $iata => $airline):
                $opts = $airline['options'] ?? [];
                while (count($opts) < 4) $opts[] = ['icon' => '🧳', 'label' => '', 'desc' => '', 'price' => ''];
            ?>
            <div class="vs08-block">
                <div class="vs08-block-head">
                    <img src="https://pics.avs.io/40/40/<?php echo esc_attr($iata); ?>.png"
                         onerror="this.style.display='none'" alt="<?php echo esc_attr($iata); ?>">
                    <strong style="font-size:15px;color:#0f2424"><?php echo esc_html($airline['label']); ?></strong>
                    <span class="vs08-iata-badge"><?php echo esc_html($iata); ?></span>
                    <input type="hidden" name="vs08_airlines[<?php echo esc_attr($iata); ?>][label]"
                           value="<?php echo esc_attr($airline['label']); ?>">
                </div>

                <table class="vs08-table">
                    <thead>
                        <tr>
                            <th style="width:60px">Icône</th>
                            <th>Libellé de l'option</th>
                            <th>Description (sous-titre)</th>
                            <th style="width:120px">Prix / pers.</th>
                            <th style="width:110px;text-align:center" title="Cocher = option automatiquement ajoutée à la résa (bagages soute = 1/voyageur, sac golf = 1/golfeur)">✅ Inclure auto<div style="font-size:10px;font-weight:400;color:#999">1/pax ou 1/golf</div></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-<?php echo esc_attr($iata); ?>">
                    <?php foreach ($opts as $i => $opt): ?>
                        <tr>
                            <td>
                                <select name="vs08_airlines[<?php echo esc_attr($iata); ?>][options][<?php echo $i; ?>][icon]">
                                    <?php foreach ($icons as $ico): ?>
                                        <option value="<?php echo esc_attr($ico); ?>" <?php selected(($opt['icon'] ?? ''), $ico); ?>><?php echo $ico; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text"
                                    name="vs08_airlines[<?php echo esc_attr($iata); ?>][options][<?php echo $i; ?>][label]"
                                    value="<?php echo esc_attr($opt['label'] ?? ''); ?>"
                                    placeholder="Ex: Bagage soute 23 kg">
                            </td>
                            <td>
                                <input type="text"
                                    name="vs08_airlines[<?php echo esc_attr($iata); ?>][options][<?php echo $i; ?>][desc]"
                                    value="<?php echo esc_attr($opt['desc'] ?? ''); ?>"
                                    placeholder="Ex: En soute · 23 kg max">
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px">
                                    <input type="number"
                                        name="vs08_airlines[<?php echo esc_attr($iata); ?>][options][<?php echo $i; ?>][price]"
                                        value="<?php echo esc_attr($opt['price'] ?? ''); ?>"
                                        placeholder="0" min="0" step="0.50">
                                    <span style="color:#999">€</span>
                                </div>
                            </td>
                            <td style="text-align:center">
                                <input type="hidden" name="vs08_airlines[<?php echo esc_attr($iata); ?>][options][<?php echo $i; ?>][default]" value="0">
                                <input type="checkbox"
                                    name="vs08_airlines[<?php echo esc_attr($iata); ?>][options][<?php echo $i; ?>][default]"
                                    value="1"
                                    <?php checked(($opt['default'] ?? 0), 1); ?>
                                    style="width:18px;height:18px;accent-color:#3d9a9a;cursor:pointer"
                                    title="Coché = option pré-sélectionnée dès l'affichage (bagage soute → 1 par voyageur · sac golf → 1 par golfeur)">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="button" class="vs08-btn-add"
                        onclick="vs08AddRow('<?php echo esc_attr($iata); ?>', <?php echo count($opts); ?>)">
                    + Ajouter une ligne
                </button>
                <p style="font-size:12px;color:#9ca3af;margin:8px 0 0;font-style:italic">
                    💡 <strong>✅ Inclure auto</strong> : l'option sera ajoutée automatiquement à la réservation —
                    bagage soute → 1 par voyageur · sac golf → 1 par golfeur.
                    Le client peut modifier la quantité avec − et +.
                </p>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- Ajouter une nouvelle compagnie -->
            <div class="vs08-add-airline">
                <h3 style="margin:0 0 12px;font-size:14px;color:#0f2424">➕ Ajouter une compagnie</h3>
                <div style="display:flex;gap:10px;align-items:flex-end">
                    <div>
                        <label style="font-size:11px;color:#888;display:block;margin-bottom:4px">Code IATA (2-3 lettres)</label>
                        <input type="text" id="new-iata" placeholder="Ex: W6" maxlength="3"
                               style="width:70px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;font-size:14px;text-transform:uppercase">
                    </div>
                    <div>
                        <label style="font-size:11px;color:#888;display:block;margin-bottom:4px">Nom de la compagnie</label>
                        <input type="text" id="new-name" placeholder="Ex: Wizz Air"
                               style="width:220px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;font-size:14px">
                    </div>
                    <button type="button" onclick="vs08AddAirline()"
                            style="background:#3d9a9a;color:#fff;border:none;padding:9px 18px;border-radius:6px;cursor:pointer;font-weight:700;font-size:13px">
                        Ajouter
                    </button>
                </div>
            </div>

            <div class="vs08-save-bar">
                <button type="submit" class="vs08-btn-save">💾 Enregistrer toutes les options</button>
                <span style="color:#999;font-size:12px;margin-left:14px">Actif immédiatement après enregistrement.</span>
            </div>
        </form>
    </div>

    <script>
    var vs08Counts = {};
    <?php foreach ($airlines as $iata => $airline): ?>
    vs08Counts['<?php echo esc_js($iata); ?>'] = <?php echo max(4, count($airline['options'] ?? [])); ?>;
    <?php endforeach; ?>

    var iconList = <?php echo json_encode($icons); ?>;

    function vs08MakeSelect(name) {
        return '<select name="' + name + '">' +
            iconList.map(function(ico){ return '<option value="'+ico+'">'+ico+'</option>'; }).join('') +
        '</select>';
    }

    function vs08AddRow(iata, baseCount) {
        if (!vs08Counts[iata]) vs08Counts[iata] = baseCount || 4;
        var n = vs08Counts[iata];
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td>' + vs08MakeSelect('vs08_airlines['+iata+'][options]['+n+'][icon]') + '</td>'
          + '<td><input type="text" name="vs08_airlines['+iata+'][options]['+n+'][label]" placeholder="Ex: Bagage soute 26 kg" style="width:100%;padding:7px 10px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px;box-sizing:border-box"></td>'
          + '<td><input type="text" name="vs08_airlines['+iata+'][options]['+n+'][desc]" placeholder="En soute · 26 kg max" style="width:100%;padding:7px 10px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px;box-sizing:border-box"></td>'
          + '<td><div style="display:flex;align-items:center;gap:6px"><input type="number" name="vs08_airlines['+iata+'][options]['+n+'][price]" placeholder="0" min="0" step="0.50" style="width:80px;padding:7px 10px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px"> <span style="color:#999">€</span></div></td>'
          + '<td style="text-align:center"><input type="hidden" name="vs08_airlines['+iata+'][options]['+n+'][default]" value="0"><input type="checkbox" name="vs08_airlines['+iata+'][options]['+n+'][default]" value="1" style="width:18px;height:18px;accent-color:#3d9a9a;cursor:pointer" title="Pré-sélectionner par défaut"></td>';
        document.getElementById('tbody-' + iata).appendChild(tr);
        vs08Counts[iata]++;
    }

    function vs08AddAirline() {
        var iata = document.getElementById('new-iata').value.trim().toUpperCase();
        var name = document.getElementById('new-name').value.trim();
        if (!iata || !name) { alert('Code IATA et nom requis.'); return; }
        if (iata.length < 2) { alert('Le code IATA doit faire 2 ou 3 caractères.'); return; }

        var rows = '';
        for (var i = 0; i < 4; i++) {
            rows += '<tr>'
              + '<td>' + vs08MakeSelect('vs08_airlines['+iata+'][options]['+i+'][icon]') + '</td>'
              + '<td><input type="text" name="vs08_airlines['+iata+'][options]['+i+'][label]" placeholder="Ex: Bagage soute 23 kg" style="width:100%;padding:7px 10px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px;box-sizing:border-box"></td>'
              + '<td><input type="text" name="vs08_airlines['+iata+'][options]['+i+'][desc]" placeholder="En soute · 23 kg max" style="width:100%;padding:7px 10px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px;box-sizing:border-box"></td>'
              + '<td><div style="display:flex;align-items:center;gap:6px"><input type="number" name="vs08_airlines['+iata+'][options]['+i+'][price]" placeholder="0" min="0" step="0.50" style="width:80px;padding:7px 10px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px"> <span style="color:#999">€</span></div></td>'
              + '</tr>';
        }

        var block = document.createElement('div');
        block.className = 'vs08-block';
        block.innerHTML =
            '<div class="vs08-block-head">'
          + '<img src="https://pics.avs.io/40/40/'+iata+'.png" onerror="this.style.display=\'none\'" style="width:36px;height:36px;border-radius:8px;border:1px solid #e5e5e5;object-fit:contain;background:#fafafa">'
          + '<strong style="font-size:15px;color:#0f2424">'+name+'</strong>'
          + '<span class="vs08-iata-badge">'+iata+'</span>'
          + '<input type="hidden" name="vs08_airlines['+iata+'][label]" value="'+name+'">'
          + '</div>'
          + '<table class="vs08-table"><thead><tr>'
          + '<th style="width:60px">Icône</th><th>Libellé</th><th>Description</th><th style="width:120px">Prix / pers.</th>'
          + '</tr></thead><tbody id="tbody-'+iata+'">'+rows+'</tbody></table>'
          + '<button type="button" class="vs08-btn-add" onclick="vs08AddRow(\''+iata+'\', 4)">+ Ajouter une ligne</button>';

        document.getElementById('vs08-airlines-container').appendChild(block);
        vs08Counts[iata] = 4;
        document.getElementById('new-iata').value = '';
        document.getElementById('new-name').value = '';
    }
    </script>
    <?php
}
