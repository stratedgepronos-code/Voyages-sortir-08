(function($){
'use strict';
if (typeof vs08c === 'undefined') return;

var CIRCUIT = window.VS08C_CIRCUIT || {};
var vc_prix_vol = 0;
var calcTimer = null;
var hasTriple = CIRCUIT.prix_triple && parseFloat(CIRCUIT.prix_triple) > 0;

/* ══ ITINERARY ACCORDION ══ */
$(document).on('click', '.vc-day-header', function(){ $(this).closest('.vc-day').toggleClass('open'); });
$('.vc-day').first().addClass('open');

/* ══ PRACTICAL CARDS — repliables, clic pour déplier ══ */
$(document).on('click', '.vc-practical-card-header', function(){
    var $card = $(this).closest('.vc-practical-card');
    var $body = $card.find('.vc-practical-card-body');
    var isOpen = $card.hasClass('open');
    $card.toggleClass('open');
    $body.attr('hidden', isOpen ? true : null);
    $(this).attr('aria-expanded', !isOpen);
});

/* ══ LIGHTBOX ══ */
var lbImages = [], lbIndex = 0;
$(document).on('click', '.vc-gal-item', function(e){
    e.preventDefault(); lbImages = [];
    $('.vc-gal-item img').each(function(){ lbImages.push($(this).attr('src')); });
    lbIndex = $(this).index(); showLB();
});
function showLB(){
    if (!lbImages.length) return;
    var $lb = $('.vc-lightbox');
    if (!$lb.length){ $('body').append('<div class="vc-lightbox"><button class="vc-lightbox-close">&times;</button><button class="vc-lightbox-nav vc-lightbox-prev">&lsaquo;</button><img src="" alt=""><button class="vc-lightbox-nav vc-lightbox-next">&rsaquo;</button></div>'); $lb = $('.vc-lightbox'); }
    $lb.find('img').attr('src', lbImages[lbIndex]); $lb.addClass('active'); $('body').css('overflow','hidden');
}
$(document).on('click', '.vc-lightbox-close', closeLB);
$(document).on('click', '.vc-lightbox', function(e){ if ($(e.target).hasClass('vc-lightbox')) closeLB(); });
$(document).on('click', '.vc-lightbox-prev', function(e){ e.stopPropagation(); lbIndex = (lbIndex-1+lbImages.length)%lbImages.length; $('.vc-lightbox img').attr('src', lbImages[lbIndex]); });
$(document).on('click', '.vc-lightbox-next', function(e){ e.stopPropagation(); lbIndex = (lbIndex+1)%lbImages.length; $('.vc-lightbox img').attr('src', lbImages[lbIndex]); });
$(document).on('keydown', function(e){ if(!$('.vc-lightbox.active').length)return; if(e.key==='Escape')closeLB(); if(e.key==='ArrowLeft')$('.vc-lightbox-prev').click(); if(e.key==='ArrowRight')$('.vc-lightbox-next').click(); });
function closeLB(){ $('.vc-lightbox').removeClass('active'); $('body').css('overflow',''); }

/* ══════════════════════════════════════
   1. AÉROPORT → Active la date
   ══════════════════════════════════════ */
$(document).on('change', '#vc-aeroport', function(){
    var code = $(this).val();
    var $dateEl = $('#vc-date-depart');
    var $hint = $('#vc-date-hint');
    var $wrap = $('#vc-field-date-wrap');

    if (code) {
        $dateEl.prop('disabled', false).show();
        $hint.hide();
        $wrap.addClass('vc-date-active');
        if ($dateEl.val()) vcFetchVol();
    } else {
        $dateEl.prop('disabled', true).hide().val('');
        $hint.show();
        $wrap.removeClass('vc-date-active');
        $('#vc-vol-status').hide();
        vc_prix_vol = 0;
    }
    triggerCalc();
});

/* ══════════════════════════════════════
   2. DATE → Recherche de vols Duffel/SerpAPI
   ══════════════════════════════════════ */
$(document).on('change', '#vc-date-depart', function(){
    if ($(this).val() && $('#vc-aeroport').val()) vcFetchVol();
    triggerCalc();
});

function vcFetchVol() {
    var aeroport = $('#vc-aeroport').val();
    var date     = $('#vc-date-depart').val();
    var $status  = $('#vc-vol-status');
    if (!aeroport || !date) return;

    $status.attr('class', 'vc-vol-status loading').text('⏳ Recherche du meilleur vol...').show();

    $.post(vs08c.ajax_url, {
        action: 'vs08c_get_flight',
        nonce: vs08c.nonce,
        circuit_id: CIRCUIT.id,
        date: date,
        aeroport: aeroport,
        passengers: parseInt($('#vc-nb-adultes').val()) || 2
    }, function(res) {
        if (res.success && res.data) {
            vc_prix_vol = parseFloat(res.data.prix) || 0;
            var flights = res.data.flights || [];
            var count = Array.isArray(flights) ? flights.length : 0;
            var statusText = res.data.note === 'estimate'
                ? (count > 0 ? count + ' vol(s) trouvé(s) (estimé)' : 'Tarif estimé')
                : (count > 0 ? '✅ ' + count + ' vol(s) trouvé(s)' : 'Vols trouvés');
            $status.attr('class', 'vc-vol-status loaded').text(statusText);
        } else {
            vc_prix_vol = parseFloat(CIRCUIT.prix_vol_base) || 0;
            $status.attr('class', 'vc-vol-status ' + (vc_prix_vol > 0 ? 'loaded' : 'error'))
                   .text(vc_prix_vol > 0 ? 'Tarif de base' : 'Tarif vol indisponible');
        }
        triggerCalc();
    }).fail(function(){
        vc_prix_vol = parseFloat(CIRCUIT.prix_vol_base) || 0;
        $status.attr('class', 'vc-vol-status error').text('Erreur réseau — tarif de base');
        triggerCalc();
    });
}

/* ══════════════════════════════════════
   ROOMS — Auto-generation
   Double=max 2, Individuelle=max 1, Triple=max 3
   ══════════════════════════════════════ */
function buildRooms(){
    var nbChambres = parseInt($('#vc-nb-chambres').val()) || 1;
    var $section = $('#vc-rooms-section'); $section.empty();
    for (var i = 0; i < nbChambres; i++) {
        $section.append(
            '<div class="vc-room-card" data-room="'+i+'">'
            + '<div class="vc-room-header"><span class="vc-room-title">🛏️ Chambre '+(i+1)+'</span></div>'
            + '<div class="vc-field-row">'
            + '<div class="vc-field"><label>Type</label><select class="vc-room-type"><option value="double">Double (max 2)</option><option value="simple">Individuelle (1)</option>'+(hasTriple?'<option value="triple">Triple (max 3)</option>':'')+'</select></div>'
            + '<div class="vc-field"><label>Occupants</label><select class="vc-room-occupants"><option value="1">1</option><option value="2" selected>2</option></select></div>'
            + '</div></div>'
        );
    }
    updateRoomOcc(); triggerCalc();
}
function updateRoomOcc(){
    $('.vc-room-card').each(function(){
        var t = $(this).find('.vc-room-type').val();
        var $o = $(this).find('.vc-room-occupants');
        var max = t==='simple'?1:(t==='triple'?3:2);
        var cur = parseInt($o.val())||2;
        $o.empty();
        for(var n=1;n<=max;n++) $o.append('<option value="'+n+'"'+(n===Math.min(cur,max)?' selected':'')+'>'+n+'</option>');
        if(t==='simple') $o.val(1);
    });
}
$(document).on('change', '.vc-room-type', function(){ updateRoomOcc(); triggerCalc(); });
$(document).on('change', '#vc-nb-chambres, #vc-nb-adultes', buildRooms);
$(document).on('change', '.vc-room-occupants', triggerCalc);

/* ══════════════════════════════════════
   OPTIONS — collecte pour le calcul et la résa
   ══════════════════════════════════════ */
function collectOptions(){
    var opts = {};
    $('.vc-option-cb').each(function(){
        var id = $(this).data('id');
        if (id) opts[id] = this.checked ? 1 : 0;
    });
    $('.vc-option-qty').each(function(){
        var id = $(this).data('id');
        if (id) opts[id] = parseInt($(this).val(), 10) || 0;
    });
    return opts;
}

/* ══════════════════════════════════════
   CALCULATOR — Real-time price
   ══════════════════════════════════════ */
function triggerCalc(){ clearTimeout(calcTimer); calcTimer = setTimeout(doCalc, 400); }

function doCalc(){
    var date = $('#vc-date-depart').val();
    var aero = $('#vc-aeroport').val();
    if (!date || !aero) return;

    $('#vc-price-loading').show();

    var rooms = [];
    $('.vc-room-card').each(function(){ rooms.push({type:$(this).find('.vc-room-type').val()||'double', occupants:parseInt($(this).find('.vc-room-occupants').val())||2}); });
    var options = collectOptions();

    $.post(vs08c.ajax_url, {
        action:'vs08c_calculate', nonce:vs08c.nonce, circuit_id:CIRCUIT.id,
        nb_adultes: parseInt($('#vc-nb-adultes').val())||2, nb_enfants:0,
        nb_chambres: parseInt($('#vc-nb-chambres').val())||1,
        date_depart:date, aeroport:aero, prix_vol:vc_prix_vol,
        rooms: JSON.stringify(rooms),
        options: JSON.stringify(options)
    }, function(r){
        $('#vc-price-loading').hide();
        if(!r.success) return;
        renderPrice(r.data);
    });
}

function renderPrice(d){
    var $r = $('#vc-price-result');
    var h = '<div class="vc-price-lines">';
    (d.lines||[]).forEach(function(l){ h += '<div class="vc-price-line"><span>'+l.label+'</span><span>'+fmt(l.montant)+' €</span></div>'; });
    h += '</div>';
    h += '<div class="vc-price-total"><span class="vc-price-total-label">Total estimé</span><span class="vc-price-total-val">'+fmt(d.total)+' €</span></div>';
    if(d.nb_total>0) h += '<div class="vc-price-pp">tout compris · '+fmt(d.par_pers)+' €/pers.</div>';
    if(d.acompte && d.acompte<d.total) h += '<div class="vc-acompte-line"><span>🔒 Acompte '+Math.round(d.acompte_pct)+'%</span><span>'+fmt(d.acompte)+' €</span></div>';
    $r.html(h);
    $('.vc-cta-btn').prop('disabled', false);

    // Store params for booking redirect (options inclus pour page résa)
    window.vc_params = {
        date_depart:$('#vc-date-depart').val(), aeroport:$('#vc-aeroport').val(),
        nb_adultes:$('#vc-nb-adultes').val(), nb_chambres:$('#vc-nb-chambres').val(),
        prix_vol:vc_prix_vol,
        rooms:JSON.stringify($('.vc-room-card').map(function(){return{type:$(this).find('.vc-room-type').val(),occupants:parseInt($(this).find('.vc-room-occupants').val())}}).get()),
        options:JSON.stringify(collectOptions())
    };
}

function fmt(v){ return parseFloat(v).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }

/* ══ BOOKING REDIRECT ══ */
$(document).on('click', '.vc-cta-btn', function(e){
    e.preventDefault();
    if ($(this).prop('disabled') || !window.vc_params) return;
    var p = window.vc_params;
    var url = CIRCUIT.booking_url
        + '?date='+encodeURIComponent(p.date_depart)
        + '&aeroport='+encodeURIComponent(p.aeroport)
        + '&nadultes='+p.nb_adultes
        + '&nenfants=0'
        + '&nchamb='+p.nb_chambres
        + '&vol='+p.prix_vol
        + '&rooms='+encodeURIComponent(p.rooms);
    if (p.options && p.options !== '{}') url += '&options='+encodeURIComponent(p.options);
    window.location.href = url;
});

/* ══ OPTIONS — recalc au changement (checkbox + select) ══ */
$(document).on('change', '.vc-option-cb, .vc-option-qty', function(){
    $(this).closest('.vc-option-card').toggleClass('selected', $(this).is('.vc-option-cb') ? this.checked : (parseInt($(this).val(),10) > 0));
    triggerCalc();
});

/* ══ INIT ══ */
$(function(){ if(CIRCUIT.id) buildRooms(); });

})(jQuery);
