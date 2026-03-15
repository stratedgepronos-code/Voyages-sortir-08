<?php
/**
 * VS08 Voyages — Meta box Golf & Parcours (dédiée)
 */
class VS08V_GolfBox {

    public static function register() {
        add_meta_box(
            'vs08v_golf',
            '⛳ Golf & Parcours',
            [__CLASS__, 'render'],
            'vs08_voyage',
            'normal',
            'high'
        );
    }

    public static function render($post) {
        $m    = VS08V_MetaBoxes::get($post->ID);
        $h    = $m['hotel'] ?? [];
        $nonce    = wp_create_nonce('vs08v_scan_hotel');
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <style>
        .vs08g-section{margin-bottom:20px}
        .vs08g-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:#59b7b7;border-bottom:2px solid #edf8f8;padding-bottom:6px;margin:0 0 12px}
        .vs08g-golf-block{background:#f4fbfb;border:1.5px solid #b2dfdf;border-radius:10px;padding:16px;margin-bottom:10px}
        .vs08g-golf-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
        .vs08g-golf-num{font-size:13px;font-weight:700;color:#1a3a3a;display:flex;align-items:center;gap:6px}
        .vs08g-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;align-items:flex-start}
        .vs08g-field{display:flex;flex-direction:column;flex:1 1 140px;min-width:0}
        .vs08g-field label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:4px}
        .vs08g-field input,.vs08g-field select,.vs08g-field textarea{width:100%;box-sizing:border-box;border:1.5px solid #dde1e7;border-radius:6px;padding:8px 10px;font-size:13px;font-family:inherit;background:#fafafa;transition:border-color .2s}
        .vs08g-field input:focus,.vs08g-field select:focus,.vs08g-field textarea:focus{border-color:#59b7b7;outline:none;background:#fff}
        .vs08g-field-full{flex:1 1 100%}
        .vs08g-field-2{flex:2 1 240px}
        .vs08g-add-btn{background:#59b7b7;color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;margin-top:4px;transition:background .2s;font-family:inherit}
        .vs08g-add-btn:hover{background:#3d9a9a}
        .vs08v-rm{background:#fff0f0;color:#e53e3e;border:1.5px solid #fca5a5;border-radius:6px;padding:5px 10px;font-size:11px;font-weight:700;cursor:pointer;transition:all .2s}
        .vs08v-rm:hover{background:#e53e3e;color:#fff}
        </style>

        <p style="font-size:13px;color:#6b7280;margin:0 0 16px;line-height:1.6">
            ⛳ Ajoutez tous les parcours accessibles depuis ce séjour — ils s'afficheront chacun sur la fiche client avec leurs caractéristiques détaillées.
        </p>

        <?php $nb_parcours_cfg = intval($m['nb_parcours'] ?? 0); ?>

        <!-- Recherche par nom (prioritaire) -->
        <div class="vs08g-scanner-wrap" style="background:linear-gradient(135deg,#0f2424,#1a4a4a);border-radius:14px;padding:20px;margin-bottom:20px">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="font-size:22px">🤖</span>
                <div>
                    <h3 style="color:#fff;margin:0;font-size:15px;font-weight:700">Recherche par nom</h3>
                    <p style="color:rgba(255,255,255,.6);font-size:12px;margin:0">Ajoutez un ou plusieurs parcours ci-dessous, puis cliquez sur « Rechercher » : l'IA remplit les infos pour tous.</p>
                </div>
            </div>
            <div id="vs08g-search-rows" style="margin-bottom:10px">
                <div class="vs08g-search-row" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:8px">
                    <div style="flex:1;min-width:160px">
                        <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.85);margin-bottom:4px">Nom du parcours *</label>
                        <input type="text" class="vs08g-search-nom" placeholder="Ex: Royal Golf Marrakech" style="width:100%;background:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:8px;padding:10px 14px;font-size:14px;color:#1a3a3a;font-family:inherit;box-sizing:border-box">
                    </div>
                    <div style="flex:1;min-width:120px">
                        <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.85);margin-bottom:4px">Ville / Région (optionnel)</label>
                        <input type="text" class="vs08g-search-loc" placeholder="Ex: Marrakech" style="width:100%;background:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:8px;padding:10px 14px;font-size:14px;color:#1a3a3a;font-family:inherit;box-sizing:border-box">
                    </div>
                    <button type="button" class="vs08g-search-rm vs08v-rm" style="flex-shrink:0;margin-bottom:2px" title="Retirer cette ligne">✕</button>
                </div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
                <button type="button" id="vs08g-add-search-row" style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit">
                    ➕ Ajouter un parcours à rechercher
                </button>
                <button type="button" id="vs08g-by-name-btn" style="background:#59b7b7;color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit;white-space:nowrap">
                    <span id="vs08g-by-name-icon">🔍</span>
                    <span id="vs08g-by-name-txt">Rechercher et remplir avec l'IA</span>
                </button>
            </div>
            <template id="vs08g-search-row-tpl">
                <div class="vs08g-search-row" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:8px">
                    <div style="flex:1;min-width:160px">
                        <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.85);margin-bottom:4px">Nom du parcours *</label>
                        <input type="text" class="vs08g-search-nom" placeholder="Ex: Royal Golf Marrakech" style="width:100%;background:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:8px;padding:10px 14px;font-size:14px;color:#1a3a3a;font-family:inherit;box-sizing:border-box">
                    </div>
                    <div style="flex:1;min-width:120px">
                        <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.85);margin-bottom:4px">Ville / Région (optionnel)</label>
                        <input type="text" class="vs08g-search-loc" placeholder="Ex: Marrakech" style="width:100%;background:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:8px;padding:10px 14px;font-size:14px;color:#1a3a3a;font-family:inherit;box-sizing:border-box">
                    </div>
                    <button type="button" class="vs08g-search-rm vs08v-rm" style="flex-shrink:0;margin-bottom:2px" title="Retirer cette ligne">✕</button>
                </div>
            </template>
            <p style="margin:0 0 10px;font-size:12px;color:rgba(255,255,255,.5)">Ou importez une fiche PDF :</p>
            <label id="vs08g-pdf-label" class="vs08g-pdf-drop" for="vs08g-pdf-input" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:rgba(255,255,255,.07);border:2px dashed rgba(255,255,255,.25);border-radius:12px;padding:20px;cursor:pointer;transition:all .2s;text-align:center;margin-bottom:10px">
                <span style="font-size:26px">📄</span>
                <span id="vs08g-pdf-txt" style="font-size:12px;color:rgba(255,255,255,.7)">Glissez votre PDF ici ou <u>cliquez pour choisir</u></span>
                <input type="file" id="vs08g-pdf-input" accept=".pdf" style="display:none">
            </label>
            <button type="button" id="vs08g-pdf-btn" style="display:none;background:#59b7b7;color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;align-items:center;gap:6px;font-family:inherit;width:100%;justify-content:center">
                <span>🤖</span><span id="vs08g-pdf-btn-txt">Analyser le PDF</span>
            </button>
            <div id="vs08g-status" style="display:none;margin-top:10px;padding:8px 12px;border-radius:8px;font-size:12px"></div>
            <div id="vs08g-progress-wrap" style="display:none;margin-top:10px">
                <div style="background:rgba(255,255,255,.1);border-radius:100px;height:5px"><div id="vs08g-progress-bar" style="height:100%;background:#59b7b7;border-radius:100px;width:0%;transition:width .4s"></div></div>
            </div>
        </div>

        <div id="vs08g-golfs-list">
        <?php
        $golfs = $h['golfs'] ?? [['nom'=>'','trous'=>'','distance'=>'','sur_place'=>'non','diff'=>'tous','practice'=>'oui','architecte'=>'','desc'=>'']];
        foreach($golfs as $gi => $golf): ?>
        <div class="vs08g-golf-block">
            <div class="vs08g-golf-header">
                <span class="vs08g-golf-num">⛳ Parcours <?php echo $gi+1; ?></span>
                <button type="button" class="vs08v-rm" onclick="var b=this.closest('.vs08g-golf-block');b.remove();if(typeof vs08gUpdateAddButton==='function')vs08gUpdateAddButton();">✕ Supprimer</button>
            </div>
            <div class="vs08g-row">
                <div class="vs08g-field vs08g-field-2">
                    <label>Nom du parcours *</label>
                    <input type="text" name="vs08v[hotel][golfs][<?php echo $gi;?>][nom]" value="<?php echo esc_attr($golf['nom']??'');?>" placeholder="Royal Golf Marrakech">
                </div>
                <div class="vs08g-field">
                    <label>Nombre de trous</label>
                    <input type="text" name="vs08v[hotel][golfs][<?php echo $gi;?>][trous]" value="<?php echo esc_attr($golf['trous']??'');?>" placeholder="18 trous">
                </div>
                <div class="vs08g-field">
                    <label>Accès / Distance</label>
                    <input type="text" name="vs08v[hotel][golfs][<?php echo $gi;?>][distance]" value="<?php echo esc_attr($golf['distance']??'');?>" placeholder="15 min en voiture">
                </div>
            </div>
            <div class="vs08g-row">
                <div class="vs08g-field">
                    <label>Emplacement</label>
                    <select name="vs08v[hotel][golfs][<?php echo $gi;?>][sur_place]">
                        <option value="non" <?php selected($golf['sur_place']??'non','non');?>>📍 À proximité</option>
                        <option value="oui" <?php selected($golf['sur_place']??'non','oui');?>>✅ Sur place</option>
                    </select>
                </div>
                <div class="vs08g-field">
                    <label>Niveau requis</label>
                    <select name="vs08v[hotel][golfs][<?php echo $gi;?>][diff]">
                        <?php foreach(['tous'=>'🏌️ Tous niveaux','debutant'=>'🟢 Débutant','intermediaire'=>'🟡 Intermédiaire','confirme'=>'🟠 Confirmé','champion'=>'🔴 Championnat'] as $v=>$l): ?>
                        <option value="<?php echo $v;?>" <?php selected($golf['diff']??'tous',$v);?>><?php echo $l;?></option>
                        <?php endforeach;?>
                    </select>
                </div>
                <div class="vs08g-field">
                    <label>Practice / Académie</label>
                    <select name="vs08v[hotel][golfs][<?php echo $gi;?>][practice]">
                        <option value="oui" <?php selected($golf['practice']??'oui','oui');?>>✅ Practice inclus</option>
                        <option value="non" <?php selected($golf['practice']??'oui','non');?>>❌ Non disponible</option>
                    </select>
                </div>
                <div class="vs08g-field">
                    <label>Architecte / Concepteur</label>
                    <input type="text" name="vs08v[hotel][golfs][<?php echo $gi;?>][architecte]" value="<?php echo esc_attr($golf['architecte']??'');?>" placeholder="Robert Trent Jones Jr.">
                </div>
            </div>
            <div class="vs08g-row">
                <div class="vs08g-field vs08g-field-full">
                    <label>Description du parcours</label>
                    <textarea name="vs08v[hotel][golfs][<?php echo $gi;?>][desc]" rows="3" placeholder="Décrivez le parcours, ses caractéristiques, l'environnement, les défis..."><?php echo esc_textarea($golf['desc']??'');?></textarea>
                </div>
            </div>
            <div class="vs08g-row">
                <div class="vs08g-field">
                    <label>📷 Photo du parcours</label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="hidden" name="vs08v[hotel][golfs][<?php echo $gi;?>][photo]"
                            id="vs08g-golf-photo-<?php echo $gi;?>"
                            value="<?php echo esc_attr($golf['photo']??''); ?>">
                        <button type="button" class="button vs08g-golf-photo-btn"
                            data-target="vs08g-golf-photo-<?php echo $gi;?>"
                            data-preview="vs08g-golf-preview-<?php echo $gi;?>"
                            style="font-size:11px">
                            🖼️ Choisir une photo
                        </button>
                        <?php if(!empty($golf['photo'])):?>
                        <img id="vs08g-golf-preview-<?php echo $gi;?>"
                            src="<?php echo esc_url($golf['photo']); ?>"
                            style="height:36px;border-radius:4px;border:1px solid #dde;object-fit:cover">
                        <?php else:?>
                        <img id="vs08g-golf-preview-<?php echo $gi;?>" src=""
                            style="height:36px;border-radius:4px;border:1px solid #dde;object-fit:cover;display:none">
                        <?php endif;?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>


        <button type="button" class="vs08g-add-btn" id="vs08g-add-golf" title="Maximum 5 parcours">
            ➕ Ajouter un parcours <span id="vs08g-add-label">(5 max)</span>
        </button>



        <script>
        var vs08gNonce = '<?php echo esc_js($nonce); ?>';
        var vs08gAjaxUrl = '<?php echo esc_js($ajax_url); ?>';

        // ── Remplir un bloc parcours avec les données IA ─────────
        function vs08gFillBlock(block, data) {
            if (!block || !data) return;
            var set = function(sel, val) {
                var el = block.querySelector(sel);
                if (el && val !== undefined && val !== null) { el.value = String(val); }
            };
            set('input[name*="[nom]"]', data.nom);
            set('input[name*="[trous]"]', data.trous);
            set('input[name*="[distance]"]', data.distance);
            set('select[name*="[sur_place]"]', data.sur_place || 'non');
            set('select[name*="[diff]"]', data.diff || 'tous');
            set('select[name*="[practice]"]', data.practice || 'oui');
            set('input[name*="[architecte]"]', data.architecte);
            set('textarea[name*="[desc]"]', data.desc);
        }

        // ── Fonction centrale pour créer un bloc parcours ─────────
        function vs08gMakeBlock(i, golf, canDelete) {
            golf = golf || {};
            var n = i + 1;
            var diffVals = ['tous','debutant','intermediaire','confirme','champion'];
            var diffLabels = ['🏌️ Tous niveaux','🟢 Débutant','🟡 Intermédiaire','🟠 Confirmé','🔴 Championnat'];
            var diffOpts = diffVals.map(function(v,k){
                return '<option value="'+v+'"'+(golf.diff===v?' selected':'')+'>'+diffLabels[k]+'</option>';
            }).join('');

            var wrap = document.createElement('div');
            wrap.className = 'vs08g-golf-block';
            wrap.innerHTML =
                '<div class="vs08g-golf-header">'+
                    '<span class="vs08g-golf-num">⛳ Parcours '+n+'</span>'+
                    (canDelete ? '<button type="button" class="vs08v-rm vs08g-rm-btn">✕ Supprimer</button>' : '')+
                '</div>'+
                '<div class="vs08g-row">'+
                    '<div class="vs08g-field vs08g-field-2"><label>Nom du parcours *</label>'+
                        '<input type="text" name="vs08v[hotel][golfs]['+i+'][nom]" value="'+(golf.nom||'')+'" placeholder="Royal Golf Marrakech"></div>'+
                    '<div class="vs08g-field"><label>Nombre de trous</label>'+
                        '<input type="text" name="vs08v[hotel][golfs]['+i+'][trous]" value="'+(golf.trous||'')+'" placeholder="18 trous"></div>'+
                    '<div class="vs08g-field"><label>Accès / Distance</label>'+
                        '<input type="text" name="vs08v[hotel][golfs]['+i+'][distance]" value="'+(golf.distance||'')+'" placeholder="15 min en voiture"></div>'+
                '</div>'+
                '<div class="vs08g-row">'+
                    '<div class="vs08g-field"><label>Emplacement</label>'+
                        '<select name="vs08v[hotel][golfs]['+i+'][sur_place]">'+
                            '<option value="non"'+(golf.sur_place==='oui'?'':' selected')+'>📍 À proximité</option>'+
                            '<option value="oui"'+(golf.sur_place==='oui'?' selected':'')+'>✅ Sur place</option>'+
                        '</select></div>'+
                    '<div class="vs08g-field"><label>Niveau requis</label>'+
                        '<select name="vs08v[hotel][golfs]['+i+'][diff]">'+diffOpts+'</select></div>'+
                    '<div class="vs08g-field"><label>Practice / Académie</label>'+
                        '<select name="vs08v[hotel][golfs]['+i+'][practice]">'+
                            '<option value="oui"'+(golf.practice==='non'?'':' selected')+'>✅ Practice inclus</option>'+
                            '<option value="non"'+(golf.practice==='non'?' selected':'')+'>❌ Non disponible</option>'+
                        '</select></div>'+
                    '<div class="vs08g-field"><label>Architecte / Concepteur</label>'+
                        '<input type="text" name="vs08v[hotel][golfs]['+i+'][architecte]" value="'+(golf.architecte||'')+'" placeholder="Robert Trent Jones Jr."></div>'+
                '</div>'+
                '<div class="vs08g-row"><div class="vs08g-field vs08g-field-full"><label>Description du parcours</label>'+
                    '<textarea name="vs08v[hotel][golfs]['+i+'][desc]" rows="3" placeholder="Décrivez le parcours...">'+(golf.desc||'')+'</textarea></div></div>'+
                '<div class="vs08g-row"><div class="vs08g-field"><label>📷 Photo du parcours</label>'+
                    '<div style="display:flex;align-items:center;gap:8px">'+
                        '<input type="hidden" name="vs08v[hotel][golfs]['+i+'][photo]" id="vs08g-golf-photo-'+i+'" value="'+(golf.photo||'')+'">'+
                        '<button type="button" class="button vs08g-golf-photo-btn" data-target="vs08g-golf-photo-'+i+'" data-preview="vs08g-golf-preview-'+i+'" style="font-size:11px">🖼️ Choisir une photo</button>'+
                        '<img id="vs08g-golf-preview-'+i+'" src="'+(golf.photo||'')+'" style="height:36px;border-radius:4px;border:1px solid #dde;object-fit:cover;'+(golf.photo?'':'display:none')+'" >'+
                    '</div></div></div>';

            // Bouton supprimer
            var rmBtn = wrap.querySelector('.vs08g-rm-btn');
            if(rmBtn) rmBtn.addEventListener('click', function(){ wrap.remove(); vs08gUpdateAddButton(); });

            return wrap;
        }

        var GOLF_MAX = 5;

        function vs08gUpdateAddButton() {
            var list = document.getElementById('vs08g-golfs-list');
            var count = list ? list.querySelectorAll('.vs08g-golf-block').length : 0;
            var btn = document.getElementById('vs08g-add-golf');
            var label = document.getElementById('vs08g-add-label');
            if (!btn) return;
            if (count >= GOLF_MAX) {
                btn.disabled = true;
                btn.title = 'Maximum ' + GOLF_MAX + ' parcours atteint';
                if (label) label.textContent = '(' + count + '/' + GOLF_MAX + ' atteint)';
            } else {
                btn.disabled = false;
                btn.title = 'Ajouter un parcours (max ' + GOLF_MAX + ')';
                if (label) label.textContent = '(' + count + '/' + GOLF_MAX + ' max)';
            }
        }

        // ── Bouton Ajouter (max 5) ────────────────────────────
        document.getElementById('vs08g-add-golf').addEventListener('click', function() {
            var list = document.getElementById('vs08g-golfs-list');
            var count = list.querySelectorAll('.vs08g-golf-block').length;
            if (count >= GOLF_MAX) return;
            var i = count;
            list.appendChild(vs08gMakeBlock(i, {}, true));
            vs08gUpdateAddButton();
        });

        // Initialiser le libellé du bouton Ajouter
        vs08gUpdateAddButton();

        // ── Lignes « parcours à rechercher » : ajout / suppression ──
        (function() {
            var container = document.getElementById('vs08g-search-rows');
            var tpl = document.getElementById('vs08g-search-row-tpl');
            var addBtn = document.getElementById('vs08g-add-search-row');
            if (!container || !tpl || !addBtn) return;

            addBtn.addEventListener('click', function() {
                var rows = container.querySelectorAll('.vs08g-search-row');
                if (rows.length >= GOLF_MAX) return;
                var clone = tpl.content.cloneNode(true);
                container.appendChild(clone);
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('vs08g-search-rm')) {
                    var row = e.target.closest('.vs08g-search-row');
                    var rows = container.querySelectorAll('.vs08g-search-row');
                    if (row && rows.length > 1) row.remove();
                }
            });
        })();

        // ── Recherche par nom (IA) — tous les parcours listés ─────────────────
        (function() {
            var btn = document.getElementById('vs08g-by-name-btn');
            var status = document.getElementById('vs08g-status');
            var progWrap = document.getElementById('vs08g-progress-wrap');
            var progBar = document.getElementById('vs08g-progress-bar');
            var container = document.getElementById('vs08g-search-rows');
            if (!btn || !status || !container) return;

            function collectNames() {
                var rows = container.querySelectorAll('.vs08g-search-row');
                var out = [];
                for (var r = 0; r < rows.length; r++) {
                    var nomEl = rows[r].querySelector('.vs08g-search-nom');
                    var locEl = rows[r].querySelector('.vs08g-search-loc');
                    var nom = (nomEl && nomEl.value) ? nomEl.value.trim() : '';
                    if (!nom) continue;
                    out.push({ nom: nom, location: (locEl && locEl.value) ? locEl.value.trim() : '' });
                }
                return out;
            }

            function doOneSearch(golfName, location) {
                var formData = new FormData();
                formData.append('action', 'vs08v_scan_golf_by_name');
                formData.append('nonce', vs08gNonce);
                formData.append('golf_name', golfName);
                formData.append('location', location);
                return fetch(vs08gAjaxUrl, { method: 'POST', body: formData }).then(function(r) { return r.json(); });
            }

            btn.addEventListener('click', function() {
                var items = collectNames();
                if (items.length === 0) {
                    status.style.display = 'block';
                    status.style.background = 'rgba(220,38,38,.15)';
                    status.style.color = '#fca5a5';
                    status.textContent = '❌ Saisissez au moins un nom de parcours.';
                    return;
                }

                btn.disabled = true;
                progWrap.style.display = 'block';
                status.style.display = 'block';
                status.style.background = 'rgba(255,255,255,.1)';
                status.style.color = 'rgba(255,255,255,.9)';

                var list = document.getElementById('vs08g-golfs-list');
                var total = items.length;
                var done = 0;
                var results = [];

                function runNext(index) {
                    if (index >= total) {
                        btn.disabled = false;
                        btn.querySelector('#vs08g-by-name-txt').textContent = 'Rechercher et remplir avec l\'IA';
                        progWrap.style.display = 'none';
                        status.style.background = 'rgba(89,183,183,.2)';
                        status.style.color = '#9ee8e8';
                        status.textContent = '✅ ' + results.length + ' parcours ajouté(s). Vérifiez et sauvegardez.';
                        return;
                    }
                    var item = items[index];
                    status.textContent = 'Recherche ' + (index + 1) + '/' + total + ' : ' + item.nom + '…';
                    progBar.style.width = Math.round((index / total) * 100) + '%';

                    doOneSearch(item.nom, item.location).then(function(res) {
                        if (res.success && res.data) {
                            results.push(res.data);
                            // Toujours ajouter un nouveau parcours (ne pas écraser les golfs déjà créés)
                            var blocks = list.querySelectorAll('.vs08g-golf-block');
                            var nextIndex = blocks.length;
                            if (nextIndex >= GOLF_MAX) {
                                status.style.background = 'rgba(245,158,11,.2)';
                                status.style.color = '#fcd34d';
                                status.textContent = 'Maximum ' + GOLF_MAX + ' parcours atteint. Les résultats suivants ne sont pas ajoutés.';
                            } else {
                                list.appendChild(vs08gMakeBlock(nextIndex, res.data, true));
                                vs08gUpdateAddButton();
                            }
                        }
                        done++;
                        progBar.style.width = Math.round((done / total) * 100) + '%';
                        runNext(index + 1);
                    }).catch(function(err) {
                        status.style.background = 'rgba(220,38,38,.15)';
                        status.style.color = '#fca5a5';
                        status.textContent = '❌ Erreur (' + (index + 1) + '/' + total + ') : ' + (err.message || 'réseau');
                        btn.disabled = false;
                        btn.querySelector('#vs08g-by-name-txt').textContent = 'Rechercher et remplir avec l\'IA';
                        progWrap.style.display = 'none';
                    });
                }

                runNext(0);
            });
        })();

        // ── Media uploader pour photos de parcours ─────────────────
        jQuery(document).ready(function($){
            $(document).on('click', '.vs08g-golf-photo-btn', function(e){
                e.preventDefault();
                var btn = $(this);
                var targetId  = btn.data('target');
                var previewId = btn.data('preview');
                var frame = wp.media({title:'Choisir une photo de parcours', button:{text:'Utiliser cette photo'}, multiple:false});
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    $('#'+targetId).val(att.url);
                    $('#'+previewId).attr('src', att.url).show();
                });
                frame.open();
            });
        });

        // ── SCANNER PDF GOLF ───────────────────────────────────────
        (function() {
            var nonce    = '<?php echo esc_js($nonce); ?>';
            var ajaxUrl  = '<?php echo esc_js($ajax_url); ?>';
            var nbParcours = <?php echo intval($m['nb_parcours'] ?? 0); ?>;
            var pdfInput = document.getElementById('vs08g-pdf-input');
            var pdfLabel = document.getElementById('vs08g-pdf-label');
            var pdfTxt   = document.getElementById('vs08g-pdf-txt');
            var pdfBtn   = document.getElementById('vs08g-pdf-btn');
            var pdfBtnTxt= document.getElementById('vs08g-pdf-btn-txt');
            var status   = document.getElementById('vs08g-status');
            var progBar  = document.getElementById('vs08g-progress-bar');
            var progWrap = document.getElementById('vs08g-progress-wrap');
            var selectedFile = null;

            pdfInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    selectedFile = this.files[0];
                    pdfLabel.style.borderColor = '#59b7b7';
                    pdfTxt.innerHTML = '✅ <strong>' + selectedFile.name + '</strong>';
                    pdfBtn.style.display = 'flex';
                }
            });
            pdfLabel.addEventListener('dragover',  function(e) { e.preventDefault(); this.style.borderColor='#59b7b7'; });
            pdfLabel.addEventListener('dragleave', function()  { this.style.borderColor=''; });
            pdfLabel.addEventListener('drop', function(e) {
                e.preventDefault(); this.style.borderColor='';
                var f = e.dataTransfer.files[0];
                if (f && f.type === 'application/pdf') {
                    selectedFile = f;
                    pdfLabel.style.borderColor = '#59b7b7';
                    pdfTxt.innerHTML = '✅ <strong>' + f.name + '</strong>';
                    pdfBtn.style.display = 'flex';
                }
            });

            pdfBtn.addEventListener('click', function() {
                if (!selectedFile) return;
                pdfBtn.disabled = true;
                pdfBtnTxt.textContent = 'Analyse en cours...';
                progWrap.style.display = 'block';
                status.style.display = 'none';
                progBar.style.width = '30%';

                var reader = new FileReader();
                reader.onload = function(e) {
                    progBar.style.width = '65%';
                    var base64 = e.target.result.split(',')[1];
                    var formData = new FormData();
                    formData.append('action',      'vs08v_scan_golf_pdf');
                    formData.append('nonce',       nonce);
                    formData.append('pdf_b64',     base64);
                    formData.append('pdf_name',    selectedFile.name);
                    formData.append('nb_parcours', nbParcours);

                    fetch(ajaxUrl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        pdfBtn.disabled = false;
                        pdfBtnTxt.textContent = 'Analyser le PDF';
                        progWrap.style.display = 'none';

                        if (!res.success) {
                            status.style.display = 'block';
                            status.style.background = 'rgba(220,38,38,.15)';
                            status.style.color = '#fca5a5';
                            status.textContent = '❌ ' + (res.data || 'Erreur inconnue');
                            return;
                        }

                        progBar.style.width = '100%';
                        var list = document.getElementById('vs08g-golfs-list');
                        list.innerHTML = '';

                        var golfs = res.data;
                        if (!Array.isArray(golfs)) golfs = [golfs];

                        golfs.forEach(function(golf, i) {
                            list.appendChild(vs08gMakeBlock(i, golf, true));
                        });

                        status.style.display = 'block';
                        status.style.background = 'rgba(89,183,183,.2)';
                        status.style.color = '#9ee8e8';
                        status.textContent = '✅ ' + golfs.length + ' parcours importés — vérifiez et sauvegardez.';
                    })
                    .catch(function(err) {
                        pdfBtn.disabled = false;
                        pdfBtnTxt.textContent = 'Analyser le PDF';
                        progWrap.style.display = 'none';
                        status.style.display = 'block';
                        status.style.background = 'rgba(220,38,38,.15)';
                        status.style.color = '#fca5a5';
                        status.textContent = '❌ ' + err.message;
                    });
                };
                reader.readAsDataURL(selectedFile);
            });
        })();
        </script>
        <?php
    }

    /**
     * Rendu frontend de la section golf
     */
    public static function render_frontend($golfs) {
        if (empty($golfs)) return '';
        $golfs = array_filter($golfs, fn($g) => !empty($g['nom']));
        if (empty($golfs)) return '';

        $diffs = ['tous'=>'Tous niveaux','debutant'=>'Débutant','intermediaire'=>'Intermédiaire','confirme'=>'Confirmé','champion'=>'Championnat'];
        ob_start(); ?>
        <div class="svg-wrap">
            <?php foreach($golfs as $idx => $golf): ?>
            <div class="svg-card <?php echo $idx > 0 ? 'svg-card--sep' : ''; ?>"
                <?php if(!empty($golf['photo'])):?>data-golf-photo="<?php echo esc_url($golf['photo']); ?>" onmouseenter="svgPhotoHover(event,this)" onmousemove="svgPhotoMove(event)" onmouseleave="svgPhotoLeave()"<?php endif;?>>
                <div class="svg-header">
                    <strong class="svg-name"><?php echo esc_html($golf['nom']); ?></strong>
                    <?php if(!empty($golf['photo'])):?><span style="font-size:11px;color:#9ca3af;font-family:'Outfit',sans-serif">📷</span><?php endif;?>
                    <div class="svg-chips">
                        <?php if (!empty($golf['trous'])): ?><span class="svg-chip">🏌️ <?php echo esc_html($golf['trous']); ?></span><?php endif; ?>
                        <?php if (!empty($golf['diff'])): ?><span class="svg-chip">🎯 <?php echo esc_html($diffs[$golf['diff']]??''); ?></span><?php endif; ?>
                        <?php if (($golf['sur_place']??'non')==='oui'): ?><span class="svg-chip svg-chip-green">📍 Sur place</span>
                        <?php elseif(!empty($golf['distance'])): ?><span class="svg-chip">🚗 <?php echo esc_html($golf['distance']); ?></span><?php endif; ?>
                        <?php if (($golf['practice']??'oui')==='oui'): ?><span class="svg-chip">🏋️ Practice</span><?php endif; ?>
                        <?php if (!empty($golf['architecte'])): ?><span class="svg-chip svg-chip-neutral">✏️ <?php echo esc_html($golf['architecte']); ?></span><?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($golf['desc'])): ?>
                <p class="svg-desc"><?php echo nl2br(esc_html($golf['desc'])); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Tooltip photo golf -->
        <div id="svg-photo-tip" style="position:fixed;z-index:9999;pointer-events:none;display:none;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.22);border:2px solid #fff;width:240px;height:160px">
            <img id="svg-photo-tip-img" src="" alt="" style="width:100%;height:100%;object-fit:cover">
        </div>
        <script>
        function svgPhotoHover(e,el){
            var tip=document.getElementById('svg-photo-tip');
            var img=document.getElementById('svg-photo-tip-img');
            if(!tip||!img) return;
            img.src=el.dataset.golfPhoto;
            tip.style.display='block';
        }
        function svgPhotoMove(e){
            var tip=document.getElementById('svg-photo-tip');
            if(!tip) return;
            var x=e.clientX+20, y=e.clientY+20;
            if(x+250>window.innerWidth) x=e.clientX-260;
            if(y+170>window.innerHeight) y=e.clientY-175;
            tip.style.left=x+'px'; tip.style.top=y+'px';
        }
        function svgPhotoLeave(){
            var tip=document.getElementById('svg-photo-tip');
            if(tip) tip.style.display='none';
        }
        </script>
        <style>
        .svg-wrap{}
        .svg-card{padding:16px 0}
        .svg-card--sep{border-top:1px solid #d4efef}
        .svg-header{display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px;margin-bottom:10px}
        .svg-name{font-size:16px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif}
        .svg-chips{display:flex;gap:6px;flex-wrap:wrap}
        .svg-chip{background:#fff;border:1px solid #b2dfdf;border-radius:100px;padding:4px 12px;font-size:12px;color:#1a3a3a;font-weight:600}
        .svg-chip-green{background:#e8f8f0;border-color:#6fcf97;color:#1a6640}
        .svg-chip-neutral{background:#f9f6f0;border-color:#e8e4dd;color:#4a5568}
        .svg-desc{font-size:13px;color:#4a5568;line-height:1.7;margin:0}
        </style>
        <?php
        return ob_get_clean();
    }
}
