(function(){
'use strict';

var plane = document.querySelector('.takeoff-plane');
var duration = 16000, pauseEnd = 2000, totalCycle = duration + pauseEnd;
var startTime = null;
function easeInQuad(t){return t*t;}
function easeOutCubic(t){return 1-Math.pow(1-t,3);}
function smoothstep(t){return t*t*(3-2*t);}

function animatePlane(ts){
    if(!startTime) startTime=ts;
    var el=(ts-startTime)%totalCycle, t=el/duration;
    var x=0,y=0,rot=0,op=0;
    if(t>1){op=0;}
    else if(t<0.02){op=t/0.02;}
    else if(t<0.45){var p=(t-0.02)/0.43;var a=easeInQuad(p);x=a*1100;op=1;}
    else if(t<0.55){var p=(t-0.45)/0.10;var s=smoothstep(p);x=1100+p*200;y=-s*10;rot=-s*5;op=1;}
    else if(t<0.90){var p=(t-0.55)/0.35;var c=easeOutCubic(p);x=1300+p*600;y=-10-c*400;rot=-5-c*10;op=1-p*p;}
    else{var p=(t-0.90)/0.10;x=1900+p*100;y=-410-p*50;rot=-15;op=Math.max(0,1-p);}
    if(plane) plane.style.transform='translate('+x+'px,'+y+'px) rotate('+rot+'deg)';
    if(plane) plane.style.opacity=op;
    requestAnimationFrame(animatePlane);
}
if(plane) requestAnimationFrame(animatePlane);

var wFR={'Clear':'Dégagé','Sunny':'Ensoleillé','Partly cloudy':'Partiellement nuageux','Partly Cloudy':'Partiellement nuageux','Cloudy':'Nuageux','Overcast':'Couvert','Mist':'Brumeux','Fog':'Brouillard','Patchy rain possible':'Averses possibles','Patchy rain nearby':'Averses éparses','Light drizzle':'Bruine légère','Light rain':'Pluie légère','Moderate rain':'Pluie modérée','Heavy rain':'Forte pluie','Light rain shower':'Averse légère','Moderate or heavy rain shower':'Forte averse','Patchy light rain with thunder':'Orage léger','Moderate or heavy rain with thunder':'Fort orage','Patchy snow possible':'Neige possible','Light snow':'Neige légère','Moderate snow':'Neige modérée','Heavy snow':'Forte neige','Blowing snow':'Poudrerie','Blizzard':'Blizzard','Thundery outbreaks possible':'Risque d\'orage','Light freezing rain':'Pluie verglaçante','Freezing fog':'Brouillard givrant'};

function wType(code){
    code=+code;
    if(code===113) return 'clear';
    if([116,119,122].indexOf(code)>-1) return 'cloudy';
    if([143,248,260].indexOf(code)>-1) return 'fog';
    if([176,263,266,281,284,293,296,299,302,305,308,311,314,353,356,359].indexOf(code)>-1) return 'rain';
    if([179,182,185,227,230,323,326,329,332,335,338,350,362,365,368,371,374,377].indexOf(code)>-1) return 'snow';
    if([200,386,389,392,395].indexOf(code)>-1) return 'storm';
    return 'cloudy';
}
/** Open-Meteo (WMO) — pas d’appel wttr.in (réseau / blocages fréquents). */
function wmoToWeather(code){
    code=+code;
    if(code===0||code===1) return {type:'clear',fr:code===0?'Dégagé':'Principalement dégagé'};
    if(code===2) return {type:'cloudy',fr:'Partiellement nuageux'};
    if(code===3) return {type:'cloudy',fr:'Couvert'};
    if(code>=45&&code<=48) return {type:'fog',fr:'Brouillard'};
    if(code>=51&&code<=57) return {type:'rain',fr:code>=56?'Bruine verglaçante':'Bruine'};
    if(code>=61&&code<=67) return {type:'rain',fr:'Pluie'};
    if(code>=71&&code<=77) return {type:'snow',fr:'Neige'};
    if(code>=80&&code<=82) return {type:'rain',fr:'Averses'};
    if(code>=85&&code<=86) return {type:'snow',fr:'Averses de neige'};
    if(code>=95&&code<=99) return {type:'storm',fr:'Orage'};
    return {type:'cloudy',fr:'Nuageux'};
}
function wEmoji(t){return{clear:'☀️',cloudy:'⛅',fog:'🌫️',rain:'🌧️',snow:'❄️',storm:'⛈️'}[t]||'🌤️';}

var skyProfiles = {
    night:   ['#060810','#0a0e1a','#101828','#182040','#1a2448','#1e2850','#1a2040','#141830'],
    dawn:    ['#0a0e1a','#141e38','#2a3460','#4a4a78','#8a5a68','#c87858','#e8a868','#f0c888'],
    day:     ['#1a3060','#2848a0','#3868c8','#5090e0','#70a8e8','#88c0f0','#a0d0f4','#b8ddf8'],
    sunset:  ['#0c1028','#162040','#2a3c68','#4a5888','#b87050','#d89860','#e8c080','#f0d8a8'],
    evening: ['#080c18','#0e1428','#182040','#283060','#604858','#905848','#b87050','#c88860']
};

function getSkyProfile(hour){
    if(hour>=22||hour<5) return 'night';
    if(hour>=5&&hour<7) return 'dawn';
    if(hour>=7&&hour<17) return 'day';
    if(hour>=17&&hour<20) return 'sunset';
    return 'evening';
}

function applySkyColors(profile, weatherType){
    var colors = skyProfiles[profile];
    if(!colors) return;
    var darken = (weatherType==='rain'||weatherType==='storm'||weatherType==='fog'||weatherType==='snow');
    var svg = document.querySelector('.scene-svg svg');
    if(!svg) return;
    var stops = svg.querySelectorAll('#skyG stop');
    if(stops.length<8) return;
    for(var i=0;i<Math.min(stops.length,colors.length);i++){
        var c = colors[i];
        if(darken){ c = blendDark(c, weatherType==='fog'?0.5:0.3); }
        stops[i].setAttribute('stop-color', c);
    }
}

function blendDark(hex, amount){
    var r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
    r=Math.round(r*(1-amount)); g=Math.round(g*(1-amount)); b=Math.round(b*(1-amount));
    return '#'+((1<<24)+(r<<16)+(g<<8)+b).toString(16).slice(1);
}

function applyVisuals(weatherType, hour){
    var profile = getSkyProfile(hour);
    applySkyColors(profile, weatherType);
    var isNight = (profile==='night'||profile==='evening'||profile==='dawn');
    var starEls = document.querySelectorAll('.scene-svg circle[fill="#fff"]');
    if(starEls.length) starEls.forEach(function(s){s.setAttribute('opacity', isNight?'0.5':'0');});
    var skylineG = document.querySelector('.scene-svg [filter="url(#blurMed)"]');
    if(skylineG){
        var op = 0.25;
        if(weatherType==='fog') op=0.06;
        else if(weatherType==='rain'||weatherType==='storm') op=0.15;
        else if(weatherType==='snow') op=0.12;
        else if(profile==='night') op=0.18;
        else if(profile==='day'&&weatherType==='clear') op=0.35;
        skylineG.setAttribute('opacity', op);
    }
    var cloudG = document.querySelector('.scene-svg g[opacity=".12"]');
    if(cloudG){
        var cop = 0.12;
        if(weatherType==='cloudy') cop=0.25;
        else if(weatherType==='rain'||weatherType==='storm') cop=0.35;
        else if(weatherType==='snow') cop=0.30;
        else if(weatherType==='fog') cop=0.40;
        else if(weatherType==='clear') cop=0.06;
        cloudG.setAttribute('opacity', cop);
    }
    var tarmacRef = document.querySelector('.scene-svg rect[fill="rgba(180,140,100,.03)"]');
    if(tarmacRef){
        if(weatherType==='rain'||weatherType==='storm') tarmacRef.setAttribute('fill','rgba(180,160,120,.08)');
        else if(weatherType==='snow') tarmacRef.setAttribute('fill','rgba(200,200,220,.04)');
        else tarmacRef.setAttribute('fill','rgba(180,140,100,.02)');
    }
}

var wxCvs = document.getElementById('wxCvs');
var wxCtx = wxCvs ? wxCvs.getContext('2d') : null;
var wxDrops = [], wxAnim = null, wxType = '';

function resizeWxCvs(){
    if(!wxCvs) return;
    var r = wxCvs.parentElement.getBoundingClientRect();
    wxCvs.width = r.width; wxCvs.height = r.height;
}
if(wxCvs){ resizeWxCvs(); window.addEventListener('resize', resizeWxCvs); }

function startWxEffect(type){
    if(wxAnim){ cancelAnimationFrame(wxAnim); wxAnim=null; }
    wxDrops=[]; wxType = type;
    if(!wxCtx) return;
    resizeWxCvs();
    var W=wxCvs.width, H=wxCvs.height;
    if(type==='rain'||type==='storm'){
        var n = type==='storm'?180:100;
        for(var i=0;i<n;i++){
            wxDrops.push({x:Math.random()*W*1.2,y:-Math.random()*H,vx:1.5+Math.random()*(type==='storm'?2.5:1),vy:8+Math.random()*(type==='storm'?10:6),len:10+Math.random()*15,a:0.1+Math.random()*(type==='storm'?0.3:0.2)});
        }
    }
    if(type==='snow'){
        for(var i=0;i<80;i++){
            wxDrops.push({x:Math.random()*W,y:-Math.random()*H,vx:-0.3+Math.random()*0.6,vy:0.5+Math.random()*1.5,sz:1.5+Math.random()*3.5,a:0.3+Math.random()*0.5,wA:20+Math.random()*40,wS:0.008+Math.random()*0.015,wO:Math.random()*Math.PI*2});
        }
    }
    var time=0;
    function frame(){
        wxCtx.clearRect(0,0,wxCvs.width,wxCvs.height);
        time++;
        for(var i=0;i<wxDrops.length;i++){
            var d=wxDrops[i];
            if(type==='rain'||type==='storm'){
                d.x+=d.vx; d.y+=d.vy;
                if(d.y>wxCvs.height){d.y=-20;d.x=Math.random()*wxCvs.width*1.2;}
                wxCtx.beginPath();
                wxCtx.strokeStyle='rgba(180,200,230,'+d.a+')';
                wxCtx.lineWidth=1.5;
                wxCtx.moveTo(d.x,d.y);
                wxCtx.lineTo(d.x-d.vx*1.5,d.y-d.len);
                wxCtx.stroke();
            }
            if(type==='snow'){
                d.x+=d.vx+Math.sin(time*d.wS+d.wO)*d.wA*0.01;
                d.y+=d.vy;
                if(d.y>wxCvs.height){d.y=-10;d.x=Math.random()*wxCvs.width;}
                wxCtx.save();
                wxCtx.globalAlpha=d.a;
                wxCtx.shadowColor='rgba(255,255,255,0.3)';
                wxCtx.shadowBlur=4;
                wxCtx.fillStyle='rgba(255,255,255,'+d.a+')';
                wxCtx.beginPath();
                wxCtx.arc(d.x,d.y,d.sz,0,Math.PI*2);
                wxCtx.fill();
                wxCtx.restore();
            }
        }
        if(type==='storm'&&Math.random()<0.004){
            wxCtx.fillStyle='rgba(200,210,255,0.15)';
            wxCtx.fillRect(0,0,wxCvs.width,wxCvs.height);
        }
        wxAnim=requestAnimationFrame(frame);
    }
    if(wxDrops.length>0) frame();
}

function stopWxEffect(){
    if(wxAnim){ cancelAnimationFrame(wxAnim); wxAnim=null; }
    wxDrops=[]; wxType='';
    if(wxCtx&&wxCvs) wxCtx.clearRect(0,0,wxCvs.width,wxCvs.height);
}

function renderWxChalons(type, fr, temp, feels, windKmh){
    var hour = new Date().getHours();
    var wxE = document.getElementById('wxE'), wxT = document.getElementById('wxT'), wxD = document.getElementById('wxD'), wxW = document.getElementById('wxW');
    if(wxE) wxE.textContent = wEmoji(type);
    if(wxT) wxT.textContent = temp+'°C';
    if(wxD) wxD.textContent = fr;
    if(wxW) wxW.title = 'Châlons-en-Champagne (51) · '+fr+' · '+temp+'°C (ressenti '+feels+'°C) · Vent '+windKmh+'km/h';
    applyVisuals(type, hour);
    if(type==='rain'||type==='storm'||type==='snow') startWxEffect(type);
    else stopWxEffect();
}
function fetchWeather(){
    var lat = 48.9564, lon = 4.3673;
    var om = 'https://api.open-meteo.com/v1/forecast?latitude='+lat+'&longitude='+lon+'&current=temperature_2m,apparent_temperature,weather_code,wind_speed_10m&wind_speed_unit=kmh&timezone=Europe%2FParis';
    fetch(om)
    .then(function(r){ if(!r.ok) throw 0; return r.json(); })
    .then(function(data){
        var cur = data.current;
        if(!cur || cur.temperature_2m == null) throw 0;
        var wm = wmoToWeather(cur.weather_code);
        var feels = cur.apparent_temperature != null ? Math.round(cur.apparent_temperature) : Math.round(cur.temperature_2m);
        renderWxChalons(wm.type, wm.fr, Math.round(cur.temperature_2m), feels, Math.round(cur.wind_speed_10m || 0));
    })
    .catch(function(){
        var hour = new Date().getHours();
        applyVisuals('cloudy', hour);
        stopWxEffect();
        var wxT = document.getElementById('wxT'), wxD = document.getElementById('wxD');
        if(wxT) wxT.textContent = '—';
        if(wxD) wxD.textContent = 'Châlons';
    });
}

fetchWeather();
setInterval(fetchWeather, 600000);

})();
