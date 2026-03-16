(function($){
'use strict';

if (typeof vs08c === 'undefined') return;

/* ══════════════════════════════════════
   ITINERARY — Accordion Toggle
   ══════════════════════════════════════ */
$(document).on('click', '.vc-day-header', function(){
    $(this).closest('.vc-day').toggleClass('open');
});
$('.vc-day').first().addClass('open');

/* ══════════════════════════════════════
   GALLERY LIGHTBOX
   ══════════════════════════════════════ */
var lbImages = [], lbIndex = 0;

$(document).on('click', '.vc-gal-item', function(e){
    e.preventDefault();
    lbImages = [];
    $('.vc-gal-item img').each(function(){ lbImages.push($(this).attr('src')); });
    lbIndex = $(this).index();
    showLightbox();
});

function showLightbox(){
    if (!lbImages.length) return;
    var $lb = $('.vc-lightbox');
    if (!$lb.length){
        $('body').append(
            '<div class="vc-lightbox">'
            + '<button class="vc-lightbox-close">&times;</button>'
            + '<button class="vc-lightbox-nav vc-lightbox-prev">&lsaquo;</button>'
            + '<img src="" alt="">'
            + '<button class="vc-lightbox-nav vc-lightbox-next">&rsaquo;</button>'
            + '</div>'
        );
        $lb = $('.vc-lightbox');
    }
    $lb.find('img').attr('src', lbImages[lbIndex]);
    $lb.addClass('active');
    $('body').css('overflow','hidden');
}
$(document).on('click', '.vc-lightbox-close', function(){ closeLightbox(); });
$(document).on('click', '.vc-lightbox', function(e){ if ($(e.target).hasClass('vc-lightbox')) closeLightbox(); });
$(document).on('click', '.vc-lightbox-prev', function(e){ e.stopPropagation(); lbIndex = (lbIndex - 1 + lbImages.length) % lbImages.length; $('.vc-lightbox img').attr('src', lbImages[lbIndex]); });
$(document).on('click', '.vc-lightbox-next', function(e){ e.stopPropagation(); lbIndex = (lbIndex + 1) % lbImages.length; $('.vc-lightbox img').attr('src', lbImages[lbIndex]); });
$(document).on('keydown', function(e){
    if (!$('.vc-lightbox.active').length) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') $('.vc-lightbox-prev').click();
    if (e.key === 'ArrowRight') $('.vc-lightbox-next').click();
});
function closeLightbox(){ $('.vc-lightbox').removeClass('active'); $('body').css('overflow',''); }

/* ══════════════════════════════════════
   ROOMS — Auto-generation
   Double = max 2, Individuelle = max 1
   ══════════════════════════════════════ */
var hasTriple = (window.VS08C_CIRCUIT && parseFloat(window.VS08C_CIRCUIT.prix_triple || 0) > 0);

function buildRooms(){
    var nbChambres = parseInt($('#vc-nb-chambres').val()) || 1;
    var nbAdultes  = parseInt($('#vc-nb-adultes').val()) || 2;
    var $section   = $('#vc-rooms-section');
    $section.empty();

    for (var i = 0; i < nbChambres; i++) {
        var occupDefault = Math.min(2, Math.ceil(nbAdultes / nbChambres));
        var html = '<div class="vc-room-card" data-room="' + i + '">'
            + '<div class="vc-room-header"><span class="vc-room-title">🛏️ Chambre ' + (i+1) + '</span></div>'
            + '<div class="vc-field-row">'
            + '<div class="vc-field"><label>Type</label>'
            + '<select class="vc-room-type" data-room="' + i + '">'
            + '<option value="double">Double (max 2)</option>'
            + '<option value="simple">Individuelle (1)</option>';
        if (hasTriple) html += '<option value="triple">Triple (max 3)</option>';
        html += '</select></div>'
            + '<div class="vc-field"><label>Occupants</label>'
            + '<select class="vc-room-occupants" data-room="' + i + '">'
            + '<option value="1">1</option>'
            + '<option value="2"' + (occupDefault >= 2 ? ' selected' : '') + '>2</option>'
            + '</select></div>'
            + '</div></div>';
        $section.append(html);
    }
    // Set correct occupants based on room type defaults
    updateAllRoomOccupants();
    triggerCalc();
}

function updateAllRoomOccupants(){
    $('.vc-room-card').each(function(){
        var type = $(this).find('.vc-room-type').val();
        var $occ = $(this).find('.vc-room-occupants');
        var maxOcc = getMaxOccupants(type);
        var current = parseInt($occ.val()) || 2;

        // Rebuild options
        $occ.empty();
        for (var o = 1; o <= maxOcc; o++) {
            $occ.append('<option value="' + o + '"' + (o === Math.min(current, maxOcc) ? ' selected' : '') + '>' + o + '</option>');
        }
        // If individuelle, force 1
        if (type === 'simple') $occ.val(1);
    });
}

function getMaxOccupants(type){
    if (type === 'simple') return 1;
    if (type === 'triple') return 3;
    return 2; // double
}

// When room type changes → update occupants max
$(document).on('change', '.vc-room-type', function(){
    var $card = $(this).closest('.vc-room-card');
    var type = $(this).val();
    var $occ = $card.find('.vc-room-occupants');
    var maxOcc = getMaxOccupants(type);
    var current = parseInt($occ.val()) || 2;

    $occ.empty();
    for (var o = 1; o <= maxOcc; o++) {
        $occ.append('<option value="' + o + '"' + (o === Math.min(current, maxOcc) ? ' selected' : '') + '>' + o + '</option>');
    }
    if (type === 'simple') $occ.val(1);
    triggerCalc();
});

// When nb_chambres or nb_adultes changes → rebuild rooms
$(document).on('change', '#vc-nb-chambres, #vc-nb-adultes', function(){
    buildRooms();
});

// When room occupants changes → trigger calc
$(document).on('change', '.vc-room-occupants', triggerCalc);

/* ══════════════════════════════════════
   CALCULATOR — Real-time price + vol Duffel + options
   ══════════════════════════════════════ */
var CIRCUIT = window.VS08C_CIRCUIT || {};
var calcTimer = null;
var vc_prix_vol = 0;

function triggerCalc(){
    clearTimeout(calcTimer);
    calcTimer = setTimeout(doCalc, 300);
}

function fetchFlightPrice(){
    var date = $('#vc-date-depart').val();
    var aero = $('#vc-aeroport').val();
    var passengers = parseInt($('#vc-nb-adultes').val()) || 2;
    var $st = $('#vc-vol-status');
    if (!date || !aero || !CIRCUIT.id) {
        $st.hide().removeClass('loading loaded error');
        vc_prix_vol = CIRCUIT.prix_vol_base || 0;
        return;
    }
    $st.show().removeClass('loaded error').addClass('loading').text('Recherche du prix vol en cours…');
    $.post(vs08c.ajax_url, {
        action: 'vs08c_get_flight',
        nonce: vs08c.nonce,
        circuit_id: CIRCUIT.id,
        date: date,
        aeroport: aero,
        passengers: passengers
    }, function(resp){
        if (resp.success && resp.data && typeof resp.data.prix !== 'undefined') {
            vc_prix_vol = parseFloat(resp.data.prix) || 0;
            $st.removeClass('loading error').addClass('loaded')
                .text(resp.data.note === 'realtime' ? '✅ Vol ' + Math.round(vc_prix_vol) + ' €/pers. (temps réel)' : '~' + Math.round(vc_prix_vol) + ' €/pers. (estimé)');
        } else {
            vc_prix_vol = CIRCUIT.prix_vol_base || 0;
            $st.removeClass('loading loaded').addClass('error').text(resp.data || 'Prix vol non dispo. — Estimation utilisée.');
        }
        triggerCalc();
    }).fail(function(){
        vc_prix_vol = CIRCUIT.prix_vol_base || 0;
        $st.removeClass('loading loaded').addClass('error').text('Recherche vol indisponible. Estimation utilisée.').show();
        triggerCalc();
    });
}

function collectOptions(){
    var o = {};
    $('.vc-option-cb:checked').each(function(){
        var id = $(this).data('id');
        if (id) o[id] = 1;
    });
    $('.vc-option-qty').each(function(){
        var id = $(this).data('id');
        var qty = parseInt($(this).val(), 10) || 0;
        if (id && qty > 0) o[id] = qty;
    });
    return o;
}

function doCalc(){
    var data = {
        action: 'vs08c_calculate',
        nonce: vs08c.nonce,
        circuit_id: CIRCUIT.id,
        nb_adultes: parseInt($('#vc-nb-adultes').val()) || 2,
        nb_enfants: 0,
        nb_chambres: parseInt($('#vc-nb-chambres').val()) || 1,
        date_depart: $('#vc-date-depart').val() || '',
        aeroport: $('#vc-aeroport').val() || '',
        prix_vol: vc_prix_vol,
        rooms: collectRooms(),
        options: JSON.stringify(collectOptions())
    };

    $.post(vs08c.ajax_url, data, function(resp){
        if (!resp.success) return;
        renderPrice(resp.data);
    });
}

function collectRooms(){
    var rooms = [];
    $('.vc-room-card').each(function(){
        rooms.push({
            type: $(this).find('.vc-room-type').val() || 'double',
            occupants: parseInt($(this).find('.vc-room-occupants').val()) || 2
        });
    });
    return JSON.stringify(rooms);
}

function renderPrice(devis){
    var $result = $('.vc-price-result');
    if (!$result.length) return;

    var html = '<div class="vc-price-lines">';
    if (devis.lines && devis.lines.length) {
        devis.lines.forEach(function(l){
            html += '<div class="vc-price-line"><span>' + l.label + '</span><span>' + formatPrice(l.montant) + ' €</span></div>';
        });
    }
    html += '</div>';
    html += '<div class="vc-price-total"><span class="vc-price-total-label">Total</span><span class="vc-price-total-val">' + formatPrice(devis.total) + ' €</span></div>';
    if (devis.nb_total > 0) {
        html += '<div class="vc-price-pp">soit ' + formatPrice(devis.par_pers) + ' €/pers.</div>';
    }
    if (devis.acompte && devis.acompte < devis.total) {
        html += '<div class="vc-acompte-line"><span>Acompte (' + Math.round(devis.acompte_pct) + '%)</span><span>' + formatPrice(devis.acompte) + ' €</span></div>';
    }
    $result.html(html);

    var dateOk = $('#vc-date-depart').val();
    var aeroOk = $('#vc-aeroport').val();
    $('.vc-cta-btn').prop('disabled', !(dateOk && aeroOk));
}

function formatPrice(v){
    return parseFloat(v).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

// Bind: date ou aéroport → recherche vol puis recalc
$(document).on('change', '#vc-date-depart, #vc-aeroport', function(){
    fetchFlightPrice();
});
$(document).on('change', '.vc-option-cb, .vc-option-qty', triggerCalc);

/* ══════════════════════════════════════
   BOOKING REDIRECT
   ══════════════════════════════════════ */
$(document).on('click', '.vc-cta-btn', function(e){
    e.preventDefault();
    if ($(this).prop('disabled')) return;

    var params = {
        date: $('#vc-date-depart').val(),
        aeroport: $('#vc-aeroport').val(),
        nadultes: $('#vc-nb-adultes').val(),
        nenfants: 0,
        nchamb: $('#vc-nb-chambres').val(),
        rooms: collectRooms(),
        vol: vc_prix_vol,
        options: encodeURIComponent(JSON.stringify(collectOptions()))
    };

    var qs = Object.keys(params).map(function(k){ return k + '=' + encodeURIComponent(params[k]); }).join('&');
    window.location.href = CIRCUIT.booking_url + '?' + qs;
});

/* ══════════════════════════════════════
   INIT
   ══════════════════════════════════════ */
$(function(){
    if (CIRCUIT.id) {
        buildRooms();
        // Si date + aéroport déjà sélectionnés (ex. retour), lancer la recherche vol
        if ($('#vc-date-depart').val() && $('#vc-aeroport').val()) {
            fetchFlightPrice();
        } else {
            setTimeout(triggerCalc, 500);
        }
    }
});

})(jQuery);
