jQuery(function($) {
    // ============================================================
    // MEDIA UPLOADER — Galerie photos
    // ============================================================
    var frame;
    $('#vs08v-add-photos').on('click', function() {
        if (frame) { frame.open(); return; }
        frame = wp.media({ title: 'Sélectionner des photos', button: { text: 'Ajouter' }, multiple: true });
        frame.on('select', function() {
            frame.state().get('selection').each(function(attachment) {
                var url = attachment.toJSON().url;
                $('#vs08v-galerie').append(
                    '<div class="vs08v-img-row" style="display:flex;align-items:center;gap:8px;margin-bottom:6px">' +
                    '<img src="'+url+'" style="width:60px;height:45px;object-fit:cover;border-radius:4px">' +
                    '<input type="hidden" name="vs08v[galerie][]" value="'+url+'">' +
                    '<button type="button" class="button vs08v-rm" onclick="this.closest(\'.vs08v-img-row\').remove()">✕</button>' +
                    '</div>'
                );
            });
        });
        frame.open();
    });

    // ============================================================
    // SAISONS — Ajouter une période
    // ============================================================
    var saisonIdx = $('#vs08v-saisons .vs08v-dyn-row').length;
    $('.vs08v-add-saison').on('click', function() {
        var i = saisonIdx++;
        $('#vs08v-saisons').append(
            '<div class="vs08v-dyn-row">' +
            '<input type="text" name="vs08v[saisons]['+i+'][label]" placeholder="Haute saison" style="width:130px">' +
            '<input type="date" name="vs08v[saisons]['+i+'][date_debut]">' +
            '<span style="padding:0 6px;color:#999">→</span>' +
            '<input type="date" name="vs08v[saisons]['+i+'][date_fin]">' +
            '<div class="vs08v-pi" style="width:110px"><span>€</span><input type="number" name="vs08v[saisons]['+i+'][supp]" placeholder="€/nuit/pers"></div>' +
            '<button type="button" class="button vs08v-rm" onclick="this.closest(\'.vs08v-dyn-row\').remove()">✕</button>' +
            '</div>'
        );
    });

    // ============================================================
    // LOCATION VOITURE — Ajouter une période tarifaire
    // ============================================================
    $(document).on('click', '.vs08v-add-voiture-periode', function() {
        var container = document.getElementById('vs08v-voiture-periodes');
        if (!container) return;
        var i = container.querySelectorAll('.vs08v-dyn-row').length;
        var row = document.createElement('div');
        row.className = 'vs08v-dyn-row';
        row.innerHTML = '<input type="text" name="vs08v[voiture_periodes]['+i+'][label]" placeholder="Ex: Haute saison" style="flex:0 0 120px">' +
            '<input type="date" name="vs08v[voiture_periodes]['+i+'][date_debut]">' +
            '<span style="color:#999;flex-shrink:0">→</span>' +
            '<input type="date" name="vs08v[voiture_periodes]['+i+'][date_fin]">' +
            '<div class="vs08v-pi" style="flex:0 0 110px"><span>€</span><input type="number" name="vs08v[voiture_periodes]['+i+'][prix]" placeholder="Prix/séjour" step="0.01"></div>' +
            '<button type="button" class="vs08v-rm" onclick="this.closest(\'.vs08v-dyn-row\').remove()">✕ Suppr.</button>';
        container.appendChild(row);
    });

    // ============================================================
    // AÉROPORTS — Ajouter un aéroport (avec périodes + jours directs)
    // ============================================================
    function vs08vAeroBlockHtml(i) {
        var jours = [{n:1,l:'Lun'},{n:2,l:'Mar'},{n:3,l:'Mer'},{n:4,l:'Jeu'},{n:5,l:'Ven'},{n:6,l:'Sam'},{n:7,l:'Dim'}];
        var j = '';
        jours.forEach(function(o){ j += '<label class="vs08v-jour-cb"><input type="checkbox" name="vs08v[aeroports]['+i+'][jours_direct][]" value="'+o.n+'" checked> '+o.l+'</label>'; });
        var cbsPeriode = '';
        jours.forEach(function(o){ cbsPeriode += '<label class="vs08v-jour-cb"><input type="checkbox" name="vs08v[aeroports]['+i+'][periodes_vol][0][jours_direct][]" value="'+o.n+'" checked> '+o.l+'</label>'; });
        var periode0 = '<div class="vs08v-periode-row" style="margin-bottom:12px;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;">' +
            '<div class="vs08v-dyn-row" style="margin-bottom:8px"><span style="font-size:12px;color:#666">Du</span><input type="date" name="vs08v[aeroports]['+i+'][periodes_vol][0][date_debut]"><span style="font-size:12px;color:#666">au</span><input type="date" name="vs08v[aeroports]['+i+'][periodes_vol][0][date_fin]"><button type="button" class="button vs08v-rm vs08v-rm-periode" title="Supprimer cette période">✕</button></div>' +
            '<div style="font-size:11px;color:#6b7280;margin-bottom:4px">Jours avec vol direct pour cette période :</div><div class="vs08v-aero-jours" style="margin:0">'+cbsPeriode+'</div></div>';
        return '<div class="vs08v-aeroport-block" data-aero-idx="'+i+'">' +
            '<div class="vs08v-dyn-row">' +
            '<input type="text" name="vs08v[aeroports]['+i+'][code]" placeholder="CDG" style="flex:0 0 60px;text-transform:uppercase">' +
            '<input type="text" name="vs08v[aeroports]['+i+'][ville]" placeholder="Paris Charles de Gaulle">' +
            '<button type="button" class="button vs08v-rm vs08v-rm-aeroport" title="Supprimer cet aéroport">✕ Suppr.</button>' +
            '</div>' +
            '<div class="vs08v-aero-sub">' +
            '<div class="vs08v-aero-label">📅 Vol ouvert (périodes) — chaque période a ses propres jours</div>' +
            '<div class="vs08v-aero-periodes">' + periode0 + '</div>' +
            '<button type="button" class="button vs08v-add-periode" data-aero-idx="'+i+'" style="margin-bottom:10px;font-size:11px">+ Ajouter une période</button>' +
            '<div class="vs08v-aero-label">📆 Jours par défaut (si une période n\'a aucun jour coché)</div>' +
            '<div class="vs08v-aero-jours">' + j + '</div>' +
            '</div></div>';
    }
    var aeroIdx = $('.vs08v-aeroport-block').length;
    $('.vs08v-add-aeroport').on('click', function() {
        var i = aeroIdx++;
        $('#vs08v-aeroports').append(vs08vAeroBlockHtml(i));
    });
    $(document).on('click', '.vs08v-rm-aeroport', function() { $(this).closest('.vs08v-aeroport-block').remove(); });
    $(document).on('click', '.vs08v-rm-periode', function() { $(this).closest('.vs08v-periode-row').remove(); });
    // L'ajout de période est géré par le script inline dans class-meta-boxes.php (délégation sur #vs08v-aeroports)

    // ============================================================
    // DATES DÉPART — Ajouter une date
    // ============================================================
    var dateIdx = $('.vs08v-date-row').length;
    $('.vs08v-add-date').on('click', function() {
        var i = dateIdx++;
        $('#vs08v-dates-depart').append(
            '<div class="vs08v-date-row vs08v-dyn-row">' +
            '<input type="date" name="vs08v[dates_depart]['+i+'][date]">' +
            '<input type="number" name="vs08v[dates_depart]['+i+'][places]" value="20" placeholder="Places" style="width:70px">' +
            '<select name="vs08v[dates_depart]['+i+'][statut]">' +
            '<option value="dispo">Disponible</option>' +
            '<option value="complet">Complet</option>' +
            '<option value="derniere_place">Dernières places</option>' +
            '</select>' +
            '<button type="button" class="button vs08v-rm" onclick="this.closest(\'.vs08v-dyn-row\').remove()">✕</button>' +
            '</div>'
        );
    });

    // ============================================================
    // OPTIONS — Ajouter une option
    // ============================================================
    var optIdx = $('.vs08v-option-row').length;
    $('.vs08v-add-option').on('click', function() {
        var i = optIdx++;
        $('#vs08v-options-list').append(
            '<div class="vs08v-option-row vs08v-dyn-row" style="background:#f9f9f9;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:8px;display:block">' +
            '<div class="vs08v-field-row">' +
            '<div class="vs08v-field"><label>ID</label><input type="text" name="vs08v[options]['+i+'][id]" placeholder="mon_option"></div>' +
            '<div class="vs08v-field vs08v-field-2"><label>Libellé</label><input type="text" name="vs08v[options]['+i+'][label]" placeholder="Nom de l\'option"></div>' +
            '<div class="vs08v-field"><label>Prix €</label><div class="vs08v-pi"><span>€</span><input type="number" name="vs08v[options]['+i+'][prix]" step="0.01"></div></div>' +
            '</div>' +
            '<div class="vs08v-field-row">' +
            '<div class="vs08v-field vs08v-field-2"><label>Description</label><input type="text" name="vs08v[options]['+i+'][desc]" placeholder="Description courte"></div>' +
            '<div class="vs08v-field"><label>Type calcul</label><select name="vs08v[options]['+i+'][type]"><option value="par_pers">Par personne</option><option value="par_pers_nuit">Par pers./nuit</option><option value="quantite">Par quantité</option><option value="fixe">Prix fixe</option></select></div>' +
            '<div class="vs08v-field"><label>Unité</label><input type="text" name="vs08v[options]['+i+'][unite]" placeholder="/pers."></div>' +
            '<div class="vs08v-field" style="align-self:flex-end"><button type="button" class="button vs08v-rm" onclick="this.closest(\'.vs08v-dyn-row\').remove()">✕ Suppr.</button></div>' +
            '</div>' +
            '</div>'
        );
    });

    // ============================================================
    // PROGRAMME — Ajouter un jour
    // ============================================================
    var jourIdx = $('.vs08v-jour-row').length;
    $('.vs08v-add-jour').on('click', function() {
        var i = jourIdx++;
        $('#vs08v-programme-list').append(
            '<div class="vs08v-jour-row vs08v-dyn-row" style="background:#f9f9f9;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:8px;display:block">' +
            '<div class="vs08v-field-row">' +
            '<div class="vs08v-field" style="flex:0 0 100px"><label>Jour</label><input type="text" name="vs08v[programme]['+i+'][num]" value="Jour '+(i+1)+'" placeholder="Jour '+(i+1)+'"></div>' +
            '<div class="vs08v-field vs08v-field-2"><label>Titre</label><input type="text" name="vs08v[programme]['+i+'][titre]" placeholder="Titre de la journée..."></div>' +
            '<div class="vs08v-field" style="align-self:flex-end"><button type="button" class="button vs08v-rm" onclick="this.closest(\'.vs08v-dyn-row\').remove()">✕</button></div>' +
            '</div>' +
            '<div class="vs08v-field"><label>Description</label><textarea name="vs08v[programme]['+i+'][desc]" rows="2" style="width:100%;border:1.5px solid #dde1e7;border-radius:6px;padding:8px;font-size:13px" placeholder="Description de la journée..."></textarea></div>' +
            '<div class="vs08v-field"><label>Tags (virgules)</label><input type="text" name="vs08v[programme]['+i+'][tags]" placeholder="✈️ Vol, 🏨 Hôtel, ⛳ Golf"></div>' +
            '</div>'
        );
    });

    // ============================================================
    // CONDITIONS ANNULATION — Ajouter un palier
    // ============================================================
    var annulIdx = $('.vs08v-dyn-row','#vs08v-annulation-list').length;
    jQuery(document).on('click','.vs08v-add-annulation', function() {
        var i = annulIdx++;
        $('#vs08v-annulation-list').append(
            '<div class="vs08v-dyn-row">' +
            '<input type="text" name="vs08v[annulation]['+i+'][label]" placeholder="Libellé de la tranche" style="flex:2">' +
            '<span style="flex-shrink:0;color:#888;font-size:12px">Jours avant :</span>' +
            '<input type="number" name="vs08v[annulation]['+i+'][jours_avant]" placeholder="30" style="flex:0 0 60px" min="0">' +
            '<span style="flex-shrink:0;color:#888;font-size:12px">Retenue :</span>' +
            '<div class="vs08v-pi" style="flex:0 0 90px"><span>%</span><input type="number" name="vs08v[annulation]['+i+'][retenue]" placeholder="30" min="0" max="100"></div>' +
            '<button type="button" class="vs08v-rm" onclick="this.closest(\'.vs08v-dyn-row\').remove()">✕ Suppr.</button>' +
            '</div>'
        );
    });

}); // ← FIN de jQuery(function($) { — ligne manquante qui causait le bug !
