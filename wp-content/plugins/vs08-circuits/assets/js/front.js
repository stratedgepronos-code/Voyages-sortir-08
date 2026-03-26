(function($){
'use strict';
if (typeof vs08c === 'undefined') return;
var CIRCUIT = window.VS08C_CIRCUIT || {};
var vc_prix_vol = 0, calcTimer = null;
var hasTriple = CIRCUIT.prix_triple && parseFloat(CIRCUIT.prix_triple) > 0;

/* ══ ITINERARY ACCORDION ══ */
$(document).on('click', '.vc-day-header', function(){ $(this).closest('.vc-day').toggleClass('open'); });
$('.vc-day').first().addClass('open');

/* ══ PRACTICAL CARDS — repliables, clic pour déplier ══ */
$(document).on('click', '.vc-practical-card-header', function(){
    var $card=$(this).closest('.vc-practical-card'), $body=$card.find('.vc-practical-card-body');
    var isOpen=$card.hasClass('open');
    $card.toggleClass('open');
    $body.attr('hidden', isOpen ? true : null);
    $(this).attr('aria-expanded', !isOpen);
});

/* ══ LIGHTBOX ══ */
var lbImages=[], lbIdx=0;
$(document).on('click', '.vc-gal-item', function(e){ e.preventDefault(); lbImages=[]; $('.vc-gal-item img').each(function(){ lbImages.push($(this).attr('src')); }); lbIdx=$(this).index(); showLB(); });
function showLB(){ if(!lbImages.length)return; var $l=$('.vc-lightbox'); if(!$l.length){$('body').append('<div class="vc-lightbox"><button class="vc-lightbox-close">&times;</button><button class="vc-lightbox-nav vc-lightbox-prev">&lsaquo;</button><img src="" alt=""><button class="vc-lightbox-nav vc-lightbox-next">&rsaquo;</button></div>');$l=$('.vc-lightbox');} $l.find('img').attr('src',lbImages[lbIdx]);$l.addClass('active');$('body').css('overflow','hidden'); }
$(document).on('click','.vc-lightbox-close',closeLB);$(document).on('click','.vc-lightbox',function(e){if($(e.target).hasClass('vc-lightbox'))closeLB();});
$(document).on('click','.vc-lightbox-prev',function(e){e.stopPropagation();lbIdx=(lbIdx-1+lbImages.length)%lbImages.length;$('.vc-lightbox img').attr('src',lbImages[lbIdx]);});
$(document).on('click','.vc-lightbox-next',function(e){e.stopPropagation();lbIdx=(lbIdx+1)%lbImages.length;$('.vc-lightbox img').attr('src',lbImages[lbIdx]);});
$(document).on('keydown',function(e){if(!$('.vc-lightbox.active').length)return;if(e.key==='Escape')closeLB();if(e.key==='ArrowLeft')$('.vc-lightbox-prev').click();if(e.key==='ArrowRight')$('.vc-lightbox-next').click();});
function closeLB(){$('.vc-lightbox').removeClass('active');$('body').css('overflow','');}

/* ══════════════════════════════════════════════════════════════
   AIRPORT → CALENDAR VS08 → FLIGHT → STEP 2
   ══════════════════════════════════════════════════════════════ */
function getAero(code){ if(!CIRCUIT.aeroports||!code)return null; for(var i=0;i<CIRCUIT.aeroports.length;i++){if((CIRCUIT.aeroports[i].code||'').toUpperCase()===code.toUpperCase())return CIRCUIT.aeroports[i];}return null; }

function vcDateAllowed(dateStr,code){
    var a=getAero(code); if(!a)return true;
    var per=a.periodes_vol||[], jd=a.jours_direct||[1,2,3,4,5,6,7];
    var d=new Date(dateStr+'T12:00:00'), jsDay=d.getDay(), phpDay=jsDay===0?7:jsDay;
    if(!per.length) return jd.indexOf(phpDay)!==-1;
    var t=d.getTime();
    for(var i=0;i<per.length;i++){
        var deb=per[i].date_debut,fin=per[i].date_fin; if(!deb&&!fin)continue;
        var tD=deb?new Date(deb+'T00:00:00').getTime():0, tF=fin?new Date(fin+'T23:59:59').getTime():9e12;
        if(t>=tD&&t<=tF){ var jp=(per[i].jours_direct&&per[i].jours_direct.length)?per[i].jours_direct:jd; return jp.indexOf(phpDay)!==-1; }
    }
    return false;
}

function vcGetAllowed(code){
    if(!code)return[];var out=[],today=new Date();today.setHours(0,0,0,0);
    for(var y=0;y<=2;y++)for(var m=0;m<12;m++){var last=new Date(today.getFullYear()+y,m+1,0).getDate();for(var day=1;day<=last;day++){var d=new Date(today.getFullYear()+y,m,day);if(d<today)continue;var s=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');if(vcDateAllowed(s,code))out.push(s);}}
    return out;
}

/* ══ AÉROPORT CHANGE → Init Calendar ══ */
$(document).on('change','#vc-aeroport',function(){
    var code=$(this).val(), db=document.getElementById('vc-field-date-block'), hint=document.getElementById('vc-date-hint'), s2=document.getElementById('vc-step2');
    $('#vc-date-depart').val('');$('#vc-vol-status').hide();vc_prix_vol=0;
    if(s2)s2.style.display='none';
    if(!code){if(db)db.style.display='none';if(hint)hint.style.display='block';return;}
    if(db)db.style.display='block';if(hint)hint.style.display='none';

    var allowed=vcGetAllowed(code), iataDest=(CIRCUIT.iata_dest||'').trim(), dur=CIRCUIT.duree||7;
    var fM=null,fY=null;
    if(allowed.length){allowed.sort();var p=allowed[0].split('-');fY=parseInt(p[0]);fM=parseInt(p[1])-1;}

    var wrap=document.getElementById('vc-date-wrap');if(!wrap)return;
    wrap.innerHTML='';window.vcCalDate=null;
    if(typeof VS08Calendar==='undefined')return;

    window.vcCalDate=new VS08Calendar({
        el:'#vc-date-wrap',mode:'date',inline:true,input:'#vc-date-depart',
        title:'📅 Date d\'aller · '+code+' → '+iataDest,
        subtitle:'Retour auto après '+dur+' nuits. Cliquez sur un jour avec vol direct.',
        yearRange:[new Date().getFullYear(),new Date().getFullYear()+2],minDate:new Date(),
        available:allowed,
        onSelect:function(dt){
            if(dt){var ds=dt.getFullYear()+'-'+String(dt.getMonth()+1).padStart(2,'0')+'-'+String(dt.getDate()).padStart(2,'0');document.getElementById('vc-date-depart').value=ds;vcOnDateSelected();}
        }
    });
    if(fM!==null&&window.vcCalDate){window.vcCalDate.viewMonth=fM;window.vcCalDate.viewYear=fY;window.vcCalDate.render();}
});

/* ══ DATE SELECTED → Fetch vol → Show step 2 ══ */
function vcOnDateSelected(){
    var aero=$('#vc-aeroport').val(), date=$('#vc-date-depart').val();
    if(!aero||!date)return;
    var $s=$('#vc-vol-status');
    $s.attr('class','vc-vol-status loading').text('⏳ Recherche du meilleur vol...').show();
    if(window.vcCalDate&&window.vcCalDate.showFlightLoading) window.vcCalDate.showFlightLoading();

    $.post(vs08c.ajax_url,{action:'vs08c_get_flight',nonce:vs08c.nonce,circuit_id:CIRCUIT.id,date:date,aeroport:aero,passengers:parseInt($('#vc-nb-adultes').val())||2},function(res){
        if(res.success&&res.data){
            vc_prix_vol=parseFloat(res.data.prix)||0;
            window.vc_flights_data=res.data.flights||[];
            var count=window.vc_flights_data.length;
            var txt=res.data.note==='estimate'
                ?(count>0?'✈️ '+count+' vol(s) A/R trouvé(s) (estimé)':'Tarif estimé')
                :(count>0?'✅ '+count+' vol(s) A/R en temps réel':'Vol(s) trouvé(s)');
            $s.attr('class','vc-vol-status loaded').text(txt);
            if(window.vcCalDate&&window.vcCalDate.hideFlightLoading) window.vcCalDate.hideFlightLoading(count>0?count+' vol(s) trouvé(s) ✓':'Tarif estimé ✓');
        }else{
            vc_prix_vol=parseFloat(CIRCUIT.prix_vol_base)||0;
            $s.attr('class','vc-vol-status '+(vc_prix_vol>0?'loaded':'error')).text(vc_prix_vol>0?'Tarif de base':'Tarif vol indisponible');
            if(window.vcCalDate&&window.vcCalDate.hideFlightLoading) window.vcCalDate.hideFlightLoading(vc_prix_vol>0?'Tarif de base ✓':'Aucun vol trouvé',3000);
        }
        $('#vc-step2').slideDown(300);buildRooms();triggerCalc();
    }).fail(function(){
        vc_prix_vol=parseFloat(CIRCUIT.prix_vol_base)||0;
        $s.attr('class','vc-vol-status error').text('Erreur réseau — tarif de base');
        if(window.vcCalDate&&window.vcCalDate.hideFlightLoading) window.vcCalDate.hideFlightLoading('Erreur réseau',3000);
        $('#vc-step2').slideDown(300);buildRooms();triggerCalc();
    });
}

/* ══ ROOMS ══ */
function applyRoomDefaults(){
    var pax=parseInt($('#vc-nb-adultes').val())||2;
    if(pax===1){
        $('#vc-nb-chambres').val(1);
    }else if(pax===2){
        $('#vc-nb-chambres').val(1);
    }else{
        $('#vc-nb-chambres').val(Math.ceil(pax/2));
    }
    buildRooms();
}
function buildRooms(){
    var pax=parseInt($('#vc-nb-adultes').val())||2;
    var n=parseInt($('#vc-nb-chambres').val())||1,$s=$('#vc-rooms-section');$s.empty();
    var defaultType=(pax===1)?'simple':'double';
    var defaultOcc=(pax===1)?1:Math.min(2,pax);
    for(var i=0;i<n;i++){
        var remaining=pax;$('.vc-room-card').each(function(){remaining-=parseInt($(this).find('.vc-room-occupants').val())||0;});
        var selSimple=(defaultType==='simple')?' selected':'';
        var selDouble=(defaultType==='double')?' selected':'';
        $s.append('<div class="vc-room-card" data-room="'+i+'"><div class="vc-room-header"><span class="vc-room-title">🛏️ Chambre '+(i+1)+'</span></div><div class="vc-field-row"><div class="vc-field"><label>Type</label><select class="vc-room-type"><option value="double"'+selDouble+'>Double (max 2)</option><option value="simple"'+selSimple+'>Individuelle (1)</option>'+(hasTriple?'<option value="triple">Triple (max 3)</option>':'')+'</select></div><div class="vc-field"><label>Occupants</label><select class="vc-room-occupants"><option value="1"'+(defaultOcc===1?' selected':'')+'>1</option><option value="2"'+(defaultOcc===2?' selected':'')+'>2</option></select></div></div></div>');
    }
    updOcc();triggerCalc();
}
function updOcc(){$('.vc-room-card').each(function(){var t=$(this).find('.vc-room-type').val(),$o=$(this).find('.vc-room-occupants'),mx=t==='simple'?1:(t==='triple'?3:2),c=parseInt($o.val())||2;$o.empty();for(var n=1;n<=mx;n++)$o.append('<option value="'+n+'"'+(n===Math.min(c,mx)?' selected':'')+'>'+n+'</option>');if(t==='simple')$o.val(1);});}
$(document).on('change','.vc-room-type',function(){updOcc();triggerCalc();});
$(document).on('change','#vc-nb-adultes',applyRoomDefaults);
$(document).on('change','#vc-nb-chambres',buildRooms);
$(document).on('change','.vc-room-occupants',triggerCalc);

/* ══ OPTIONS — collecte pour la calc card ══ */
function collectOptions(){
    var opts={};
    $('.vc-option-check').each(function(){var id=$(this).attr('data-opt-id');if(id)opts[id]=this.checked?1:0;});
    return opts;
}

/* ══ CALCULATOR ══ */
function triggerCalc(){clearTimeout(calcTimer);calcTimer=setTimeout(doCalc,400);}
function doCalc(){
    var date=$('#vc-date-depart').val(),aero=$('#vc-aeroport').val();if(!date||!aero)return;
    $('#vc-price-loading').show();
    var rooms=[];$('.vc-room-card').each(function(){rooms.push({type:$(this).find('.vc-room-type').val()||'double',occupants:parseInt($(this).find('.vc-room-occupants').val())||2});});
    var options=collectOptions();
    $.post(vs08c.ajax_url,{action:'vs08c_calculate',nonce:vs08c.nonce,circuit_id:CIRCUIT.id,nb_adultes:parseInt($('#vc-nb-adultes').val())||2,nb_enfants:0,nb_chambres:parseInt($('#vc-nb-chambres').val())||1,date_depart:date,aeroport:aero,prix_vol:vc_prix_vol,rooms:JSON.stringify(rooms),options:JSON.stringify(options)},function(r){$('#vc-price-loading').hide();if(!r.success)return;renderPrice(r.data);});
}
function renderPrice(d){
    var $r=$('#vc-price-result'),h='<div class="vc-price-lines">';
    (d.lines||[]).forEach(function(l){h+='<div class="vc-price-line"><span>'+l.label+'</span><span>'+fmt(l.montant)+' €</span></div>';});
    h+='</div><div class="vc-price-total"><span class="vc-price-total-label">Total estimé tout compris</span><span class="vc-price-total-val">'+fmt(d.total)+' €</span></div>';
    if(d.nb_total>0) h+='<div class="vc-price-pp">'+fmt(d.par_pers)+' € / pers.</div>';
    if(d.acompte&&d.acompte<d.total) h+='<div class="vc-acompte-line"><span>Acompte '+Math.round(d.acompte_pct)+'%</span><span>='+fmt(d.acompte)+' €</span></div>';
    $r.html(h);$('.vc-cta-btn').prop('disabled',false);
    window.vc_params={date_depart:$('#vc-date-depart').val(),aeroport:$('#vc-aeroport').val(),nb_adultes:$('#vc-nb-adultes').val(),nb_chambres:$('#vc-nb-chambres').val(),prix_vol:vc_prix_vol,rooms:JSON.stringify($('.vc-room-card').map(function(){return{type:$(this).find('.vc-room-type').val(),occupants:parseInt($(this).find('.vc-room-occupants').val())}}).get()),options:JSON.stringify(collectOptions())};
}
function fmt(v){return parseFloat(v).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,' ');}

/* ══ BOOKING REDIRECT ══ */
$(document).on('click','.vc-cta-btn',function(e){e.preventDefault();if($(this).prop('disabled')||!window.vc_params)return;var p=window.vc_params;var url=CIRCUIT.booking_url+'?date='+encodeURIComponent(p.date_depart)+'&aeroport='+encodeURIComponent(p.aeroport)+'&nadultes='+p.nb_adultes+'&nenfants=0&nchamb='+p.nb_chambres+'&vol='+p.prix_vol+'&rooms='+encodeURIComponent(p.rooms);if(p.options&&p.options!=='{}')url+='&options='+encodeURIComponent(p.options);window.location.href=url;});

/* ══ OPTIONS TOGGLE — recalc à chaque changement ══ */
$(document).on('change','.vc-option-check',function(){$(this).closest('.vc-option-card').toggleClass('selected',this.checked);triggerCalc();});

})(jQuery);
