(function($){
'use strict';

/* ══════════════════════════════════════
   TABS
   ══════════════════════════════════════ */
$(document).on('click', '.vs08c-tab', function(e){
    e.preventDefault();
    var tab = $(this).data('tab');
    $('.vs08c-tab').removeClass('active');
    $(this).addClass('active');
    $('.vs08c-panel').removeClass('active');
    $('.vs08c-panel[data-panel="'+tab+'"]').addClass('active');
    // Trigger WP editor resize
    if (window.tinyMCE) {
        try { tinyMCE.triggerSave(); } catch(ex){}
    }
});

/* ══════════════════════════════════════
   ITINERARY — Add / Remove / Toggle / Sortable
   ══════════════════════════════════════ */
function reindexJours(){
    $('#vs08c-itinerary-list .vs08c-jour').each(function(i){
        $(this).attr('data-index', i);
        $(this).find('.vs08c-jour-num').text('Jour ' + (i+1));
        $(this).find('input, textarea, select').each(function(){
            var name = $(this).attr('name');
            if(!name) return;
            $(this).attr('name', name.replace(/vs08c\[jours\]\[\d+\]/, 'vs08c[jours]['+i+']'));
        });
    });
}

// Toggle collapse
$(document).on('click', '.vs08c-jour-toggle, .vs08c-jour-num', function(e){
    e.stopPropagation();
    $(this).closest('.vs08c-jour').toggleClass('collapsed');
});

// Remove day
$(document).on('click', '.vs08c-remove-jour', function(e){
    e.stopPropagation();
    if($('#vs08c-itinerary-list .vs08c-jour').length <= 1){
        alert('Un circuit doit avoir au minimum 1 jour.');
        return;
    }
    if(confirm('Supprimer ce jour ?')){
        $(this).closest('.vs08c-jour').slideUp(300, function(){
            $(this).remove();
            reindexJours();
        });
    }
});

// Add day
$('#vs08c-add-jour').on('click', function(){
    var idx = $('#vs08c-itinerary-list .vs08c-jour').length;
    var html = '<div class="vs08c-repeater-item vs08c-jour" data-index="'+idx+'">'
        + '<div class="vs08c-jour-header">'
        + '<span class="vs08c-jour-handle dashicons dashicons-move"></span>'
        + '<span class="vs08c-jour-num">Jour '+(idx+1)+'</span>'
        + '<button type="button" class="vs08c-jour-toggle dashicons dashicons-arrow-down-alt2"></button>'
        + '<button type="button" class="vs08c-remove-jour" title="Supprimer">✕</button>'
        + '</div>'
        + '<div class="vs08c-jour-body">'
        + '<div class="vs08c-field-row"><div class="vs08c-field vs08c-w2"><label>📍 Titre du jour</label><input type="text" name="vs08c[jours]['+idx+'][titre]" placeholder="Ex: Marrakech → Ouarzazate"></div></div>'
        + '<div class="vs08c-field-row"><div class="vs08c-field vs08c-w2"><label>📝 Description</label><textarea name="vs08c[jours]['+idx+'][description]" rows="4" placeholder="Décrivez les activités..."></textarea></div></div>'
        + '<div class="vs08c-field-row">'
        + '<div class="vs08c-field"><label>🍽️ Repas inclus</label><input type="text" name="vs08c[jours]['+idx+'][repas]" placeholder="Petit-déjeuner, Déjeuner, Dîner"></div>'
        + '<div class="vs08c-field"><label>🏨 Nuit à</label><input type="text" name="vs08c[jours]['+idx+'][nuit]" placeholder="Riad à Ouarzazate"></div>'
        + '<div class="vs08c-field"><label>🚌 Transport</label><input type="text" name="vs08c[jours]['+idx+'][transport]" placeholder="4h de route en minibus"></div>'
        + '</div>'
        + '<div class="vs08c-field-row">'
        + '<div class="vs08c-field"><label>🏷️ Tags</label><input type="text" name="vs08c[jours]['+idx+'][tags]" placeholder="Culture, UNESCO..."></div>'
        + '<div class="vs08c-field"><label>🖼️ Image</label><div class="vs08c-image-field"><input type="hidden" name="vs08c[jours]['+idx+'][image]" value="" class="vs08c-img-input"><div class="vs08c-img-preview"></div><button type="button" class="button vs08c-upload-img">📷 Choisir</button><button type="button" class="button vs08c-remove-img" style="display:none">✕</button></div></div>'
        + '</div></div></div>';
    $('#vs08c-itinerary-list').append(html);
    // Scroll to new item
    var $new = $('#vs08c-itinerary-list .vs08c-jour').last();
    $('html,body').animate({scrollTop: $new.offset().top - 100}, 400);
});

// Sortable days
if($.fn.sortable){
    $('#vs08c-itinerary-list').sortable({
        handle: '.vs08c-jour-handle',
        placeholder: 'vs08c-sort-placeholder',
        tolerance: 'pointer',
        update: function(){ reindexJours(); }
    });
}

/* ══════════════════════════════════════
   GALLERY
   ══════════════════════════════════════ */
$('#vs08c-add-gallery').on('click', function(){
    var frame = wp.media({
        title: 'Sélectionner des photos',
        multiple: true,
        library: {type:'image'},
        button: {text:'Ajouter à la galerie'}
    });
    frame.on('select', function(){
        var selection = frame.state().get('selection');
        selection.each(function(attachment){
            var url = attachment.attributes.url;
            var html = '<div class="vs08c-gallery-item" data-url="'+url+'">'
                + '<img src="'+url+'">'
                + '<button type="button" class="vs08c-gallery-remove">✕</button>'
                + '<input type="hidden" name="vs08c[galerie][]" value="'+url+'">'
                + '</div>';
            $('#vs08c-gallery').append(html);
        });
    });
    frame.open();
});

$(document).on('click', '.vs08c-gallery-remove', function(e){
    e.stopPropagation();
    $(this).closest('.vs08c-gallery-item').fadeOut(200, function(){ $(this).remove(); });
});

// Sortable gallery
if($.fn.sortable){
    $('#vs08c-gallery').sortable({
        tolerance: 'pointer',
        placeholder: 'vs08c-sort-placeholder'
    });
}

/* ══════════════════════════════════════
   IMAGE UPLOAD (per-day images)
   ══════════════════════════════════════ */
$(document).on('click', '.vs08c-upload-img', function(){
    var $field = $(this).closest('.vs08c-image-field');
    var frame = wp.media({
        title: 'Choisir une image',
        multiple: false,
        library: {type:'image'},
        button: {text:'Utiliser cette image'}
    });
    frame.on('select', function(){
        var url = frame.state().get('selection').first().attributes.url;
        $field.find('.vs08c-img-input').val(url);
        $field.find('.vs08c-img-preview').html('<img src="'+url+'">');
        $field.find('.vs08c-remove-img').show();
    });
    frame.open();
});

$(document).on('click', '.vs08c-remove-img', function(){
    var $field = $(this).closest('.vs08c-image-field');
    $field.find('.vs08c-img-input').val('');
    $field.find('.vs08c-img-preview').html('');
    $(this).hide();
});

/* ══════════════════════════════════════
   GENERIC REPEATERS (airports, dates, options, hotels)
   ══════════════════════════════════════ */
function addRepeaterItem(listId, templateFn){
    var $list = $('#'+listId);
    var idx = $list.children('.vs08c-repeater-item').length;
    $list.append(templateFn(idx));
}

$('#vs08c-add-airport').on('click', function(){
    addRepeaterItem('vs08c-airports-list', function(i){
        var period0 = '<div class="vs08c-periode-row" style="margin-bottom:10px;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px">'
            + '<div class="vs08c-field-row vs08c-compact" style="margin-bottom:6px"><span style="font-size:12px;color:#6b7280">Du</span><input type="date" name="vs08c[aeroports]['+i+'][periodes_vol][0][date_debut]"><span style="font-size:12px;color:#6b7280">au</span><input type="date" name="vs08c[aeroports]['+i+'][periodes_vol][0][date_fin]"><button type="button" class="button vs08c-rm-periode" title="Supprimer cette période">✕</button></div>'
            + '<div style="font-size:11px;color:#6b7280;margin-bottom:4px">Jours avec vol direct pour cette période :</div>'
            + '<div style="display:flex;flex-wrap:wrap;gap:6px 12px">'
            + ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'].map(function(lib,j){ return '<label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer"><input type="checkbox" name="vs08c[aeroports]['+i+'][periodes_vol][0][jours_direct][]" value="'+(j+1)+'" checked> '+lib+'</label>'; }).join('')
            + '</div></div>';
        var joursDef = [1,2,3,4,5,6,7].map(function(n){ return '<label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer"><input type="checkbox" name="vs08c[aeroports]['+i+'][jours_direct][]" value="'+n+'" checked> '+['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'][n-1]+'</label>'; }).join('');
        return '<div class="vs08c-repeater-item vs08c-airport-item" data-aero-idx="'+i+'" style="padding:16px;margin-bottom:16px;border:1px solid #e5e7eb;border-radius:12px;background:#fafafa">'
            + '<div class="vs08c-field-row vs08c-compact" style="margin-bottom:12px">'
            + '<div class="vs08c-field"><label>Code IATA</label><input type="text" name="vs08c[aeroports]['+i+'][code]" placeholder="ORY" style="text-transform:uppercase"></div>'
            + '<div class="vs08c-field"><label>Nom aéroport</label><input type="text" name="vs08c[aeroports]['+i+'][label]" placeholder="Paris Orly"></div>'
            + '<div class="vs08c-field"><label>Supp. vol (€/pers)</label><input type="number" name="vs08c[aeroports]['+i+'][supp]" value="0" step="0.01"></div>'
            + '<button type="button" class="button vs08c-duplicate-aero" title="Dupliquer cet aéroport (même périodes)">📋 Dupliquer</button>'
            + '<button type="button" class="vs08c-remove-repeater" title="Supprimer cet aéroport">✕ Suppr.</button></div>'
            + '<div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:8px">📅 Vol ouvert (périodes) — avant/après = fermé</div>'
            + '<div class="vs08c-aero-periodes" style="margin-bottom:12px">'+period0+'</div>'
            + '<button type="button" class="button vs08c-add-periode-aero" data-aero-idx="'+i+'" style="margin-bottom:10px;font-size:11px">+ Ajouter une période</button>'
            + '<div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:6px">📆 Jours avec vol direct (défaut aéroport)</div>'
            + '<div style="display:flex;flex-wrap:wrap;gap:6px 12px">'+joursDef+'</div></div>';
    });
});

// Dupliquer un aéroport (même périodes et jours)
$(document).on('click', '.vs08c-duplicate-aero', function(){
    var $block = $(this).closest('.vs08c-airport-item');
    if (!$block.length) return;
    var $list = $('#vs08c-airports-list');
    var newIdx = $list.children('.vs08c-airport-item').length;
    var oldIdx = $block.attr('data-aero-idx');
    var $clone = $block.clone();
    $clone.attr('data-aero-idx', newIdx);
    $clone.find('.vs08c-add-periode-aero').attr('data-aero-idx', newIdx);
    $clone.find('input, select').each(function(){
        var n = $(this).attr('name');
        if (n) $(this).attr('name', n.replace(new RegExp('vs08c\\[aeroports\\]\\['+oldIdx+'\\]', 'g'), 'vs08c[aeroports]['+newIdx+']'));
    });
    $list.append($clone);
    $('html,body').animate({scrollTop: $clone.offset().top - 100}, 400);
});

/* ══════════════════════════════════════
   VS08 CALENDAR — Admin date pickers
   ══════════════════════════════════════ */
// Global calendar instances registry
window.vs08c_cals = {};

// Toggle a calendar by type (debut/fin) and period index
window.vs08cToggleCal = function(type, idx) {
    var key = type + '_' + idx;
    if (window.vs08c_cals[key]) {
        window.vs08c_cals[key].toggle();
    }
};

// Initialize calendars for a given period index
function vs08cInitCalendar(type, idx) {
    if (typeof VS08Calendar === 'undefined') return;
    var key     = type + '_' + idx;
    var wrapId  = '#vs08c-cal-wrap-' + type + '-' + idx;
    var inputId = '#vs08c-input-' + type + '-' + idx;
    var trigId  = '#vs08c-cal-trigger-' + type + '-' + idx;

    if (!document.querySelector(wrapId)) return;
    if (window.vs08c_cals[key]) return; // Already initialized

    var title = type === 'debut' ? '📅 Date de début' : '📅 Date de fin';

    var cal = new VS08Calendar({
        el:       wrapId,
        mode:     'date',
        inline:   false,
        input:    inputId,
        title:    title,
        subtitle: 'Période ' + (idx + 1),
        yearRange: [new Date().getFullYear(), new Date().getFullYear() + 3],
        minDate:   new Date(),
        onConfirm: function(dt) {
            var trigger = document.querySelector(trigId);
            if (trigger && dt) {
                var d = new Date(dt);
                trigger.textContent = '📅 ' + d.toLocaleDateString('fr-FR', {day:'numeric', month:'long', year:'numeric'});
                trigger.classList.add('has-value');
            }
        }
    });
    window.vs08c_cals[key] = cal;
}

// Init all existing period calendars on page load
function vs08cInitAllCalendars() {
    if (typeof VS08Calendar === 'undefined') return;
    $('#vs08c-dates-list .vs08c-date-item').each(function() {
        var idx = $(this).index();
        vs08cInitCalendar('debut', idx);
        vs08cInitCalendar('fin', idx);
    });
}

// Run on DOM ready
$(function(){ vs08cInitAllCalendars(); });

// Add period
$('#vs08c-add-date').on('click', function(){
    var $list = $('#vs08c-dates-list');
    var idx = $list.children('.vs08c-repeater-item').length;
    var jours = [['1','Lun'],['2','Mar'],['3','Mer'],['4','Jeu'],['5','Ven'],['6','Sam'],['7','Dim']];
    var cbs = '';
    for (var j = 0; j < jours.length; j++) {
        var checked = (jours[j][0] === '6') ? ' checked' : '';
        cbs += '<label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer;font-family:\'Outfit\',sans-serif"><input type="checkbox" name="vs08c[dates_depart]['+idx+'][jours_depart][]" value="'+jours[j][0]+'"'+checked+'> '+jours[j][1]+'</label>';
    }
    var html = '<div class="vs08c-repeater-item vs08c-date-item" style="padding:16px" data-period-idx="'+idx+'">'
        + '<div class="vs08c-field-row vs08c-compact" style="margin-bottom:10px">'
        + '<div class="vs08c-field"><label>Du</label>'
        + '<div class="vs08c-cal-wrap" id="vs08c-cal-wrap-debut-'+idx+'"><div class="vs08c-cal-trigger" id="vs08c-cal-trigger-debut-'+idx+'" onclick="vs08cToggleCal(\'debut\','+idx+')">📅 Choisir date début</div></div>'
        + '<input type="hidden" name="vs08c[dates_depart]['+idx+'][date_debut]" id="vs08c-input-debut-'+idx+'" value=""></div>'
        + '<div class="vs08c-field"><label>Au</label>'
        + '<div class="vs08c-cal-wrap" id="vs08c-cal-wrap-fin-'+idx+'"><div class="vs08c-cal-trigger" id="vs08c-cal-trigger-fin-'+idx+'" onclick="vs08cToggleCal(\'fin\','+idx+')">📅 Choisir date fin</div></div>'
        + '<input type="hidden" name="vs08c[dates_depart]['+idx+'][date_fin]" id="vs08c-input-fin-'+idx+'" value=""></div>'
        + '<div class="vs08c-field"><label>Supp. (€/pers)</label><input type="number" name="vs08c[dates_depart]['+idx+'][supp]" value="0" step="0.01"></div>'
        + '<div class="vs08c-field"><label>Statut</label><select name="vs08c[dates_depart]['+idx+'][statut]"><option value="ouvert">Ouvert</option><option value="garanti">Garanti</option><option value="complet">Complet</option></select></div>'
        + '<button type="button" class="vs08c-remove-repeater" title="Supprimer" style="align-self:end;margin-bottom:6px">✕</button>'
        + '</div>'
        + '<div style="font-size:11px;font-weight:700;color:#1a3a3a;margin-bottom:6px;font-family:\'Outfit\',sans-serif">📆 Jours de départ possibles :</div>'
        + '<div style="display:flex;flex-wrap:wrap;gap:8px 14px">' + cbs + '</div>'
        + '</div>';
    $list.append(html);

    // Init calendars for new period (slight delay for DOM render)
    setTimeout(function(){
        vs08cInitCalendar('debut', idx);
        vs08cInitCalendar('fin', idx);
    }, 100);
});

$('#vs08c-add-option').on('click', function(){
    addRepeaterItem('vs08c-options-list', function(i){
        return '<div class="vs08c-repeater-item"><div class="vs08c-field-row vs08c-compact">'
            + '<div class="vs08c-field"><label>Libellé</label><input type="text" name="vs08c[options]['+i+'][label]" placeholder="Excursion en 4x4"></div>'
            + '<div class="vs08c-field"><label>Prix (€)</label><input type="number" name="vs08c[options]['+i+'][prix]" step="0.01"></div>'
            + '<div class="vs08c-field"><label>Type</label><select name="vs08c[options]['+i+'][type]"><option value="par_pers">Par personne</option><option value="fixe">Prix fixe</option><option value="quantite">Par quantité</option></select></div>'
            + '<button type="button" class="vs08c-remove-repeater" title="Supprimer">✕</button>'
            + '</div></div>';
    });
});

$('#vs08c-add-hotel').on('click', function(){
    addRepeaterItem('vs08c-hotels-list', function(i){
        return '<div class="vs08c-repeater-item"><div class="vs08c-field-row vs08c-compact">'
            + '<div class="vs08c-field"><label>🏨 Nom</label><input type="text" name="vs08c[hotels]['+i+'][nom]" placeholder="Riad Salam"></div>'
            + '<div class="vs08c-field"><label>⭐ Étoiles</label><select name="vs08c[hotels]['+i+'][etoiles]"><option value="3">★★★</option><option value="4" selected>★★★★</option><option value="5">★★★★★</option><option value="riad">Riad</option><option value="camp">Camp / Bivouac</option></select></div>'
            + '<div class="vs08c-field"><label>📍 Ville</label><input type="text" name="vs08c[hotels]['+i+'][ville]"></div>'
            + '<div class="vs08c-field"><label>🌙 Nuits</label><input type="number" name="vs08c[hotels]['+i+'][nuits]" value="1" min="1"></div>'
            + '<button type="button" class="vs08c-remove-repeater" title="Supprimer">✕</button>'
            + '</div></div>';
    });
});

// Remove repeater item
$(document).on('click', '.vs08c-remove-repeater', function(){
    $(this).closest('.vs08c-repeater-item').slideUp(200, function(){ $(this).remove(); });
});

})(jQuery);
