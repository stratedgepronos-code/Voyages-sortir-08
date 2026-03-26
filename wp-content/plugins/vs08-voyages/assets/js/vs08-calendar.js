/**
 * VS08 Calendar Component — Premium Travel Design v1.3
 */
(function(){
'use strict';

var MOIS = ['Janvier','F\u00e9vrier','Mars','Avril','Mai','Juin','Juillet','Ao\u00fbt','Septembre','Octobre','Novembre','D\u00e9cembre'];
var MOIS_S = ['Jan','F\u00e9v','Mar','Avr','Mai','Juin','Juil','Ao\u00fbt','Sep','Oct','Nov','D\u00e9c'];
var JOURS = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];

function VS08Calendar(opts) {
    this.opts = Object.assign({
        el: null, mode: 'date', inline: true,
        input: null, inputEnd: null,
        from: 'CDG', fromCity: 'Paris', to: 'RAK', toCity: 'Marrakech',
        title: 'S\u00e9lectionnez une date', subtitle: '',
        minDate: null, maxDate: null,
        yearRange: [1940, new Date().getFullYear()],
        prices: {}, available: null,
        onSelect: null, onConfirm: null
    }, opts);

    this.today = new Date();
    this.viewMonth = this.today.getMonth();
    this.viewYear = this.today.getFullYear();
    this.selected = null;
    this.selectedEnd = null;
    this.selectMode = 'start';
    this._isOpen = false;
    this._skipClose = false;

    this.container = typeof this.opts.el === 'string' ? document.querySelector(this.opts.el) : this.opts.el;
    this.inputEl = typeof this.opts.input === 'string' ? document.querySelector(this.opts.input) : this.opts.input;
    this.inputEndEl = typeof this.opts.inputEnd === 'string' ? document.querySelector(this.opts.inputEnd) : this.opts.inputEnd;

    if (!this.container) return;

    if (this.opts.mode === 'date' && this.opts.yearRange[1] <= this.today.getFullYear()) {
        this.viewYear = Math.max(this.opts.yearRange[0], this.opts.yearRange[1] - 30);
        this.viewMonth = 0;
    }

    this.build();
    this.render();
}

VS08Calendar.prototype.build = function() {
    var cal = this;
    var wrap = document.createElement('div');
    wrap.className = 'vs-cal';

    if (this.opts.mode === 'travel') {
        wrap.innerHTML += this._buildTravelHeader();
    } else {
        wrap.innerHTML += this._buildSimpleHeader();
    }

    wrap.innerHTML += '<div class="vs-cal-perf"><div class="vs-cal-perf-line"></div></div>';

    var navHTML = '<div class="vs-cal-nav">';
    navHTML += '<button type="button" class="vs-cal-nav-btn vs-cal-prev">\u2039</button>';
    navHTML += '<div class="vs-cal-nav-center">';
    if (this.opts.mode === 'date') {
        navHTML += '<select class="vs-cal-nav-select vs-cal-sel-month">';
        for (var m = 0; m < 12; m++) {
            navHTML += '<option value="'+m+'">'+MOIS[m]+'</option>';
        }
        navHTML += '</select>';
        navHTML += '<select class="vs-cal-nav-select vs-cal-sel-year">';
        for (var y = this.opts.yearRange[1]; y >= this.opts.yearRange[0]; y--) {
            navHTML += '<option value="'+y+'">'+y+'</option>';
        }
        navHTML += '</select>';
    } else {
        navHTML += '<div class="vs-cal-nav-title"></div>';
    }
    navHTML += '</div>';
    navHTML += '<button type="button" class="vs-cal-nav-btn vs-cal-next">\u203a</button>';
    navHTML += '</div>';
    wrap.innerHTML += navHTML;

    var daysHTML = '<div class="vs-cal-days">';
    JOURS.forEach(function(j) { daysHTML += '<div class="vs-cal-day-name">'+j+'</div>'; });
    daysHTML += '</div>';
    wrap.innerHTML += daysHTML;

    wrap.innerHTML += '<div class="vs-cal-grid"></div>';

    if (this.opts.mode === 'travel' || this.opts.mode === 'range') {
        wrap.innerHTML += '<div class="vs-cal-footer"><div class="vs-cal-nights"></div><button type="button" class="vs-cal-btn" disabled>Confirmer \u2708</button></div>';
    }
    /* Mode date : pas de bouton Valider, le clic sur une date valide directement */

    // ══════════════════════════════════════════════
    // INLINE ou DROPDOWN
    // ══════════════════════════════════════════════
    if (this.opts.inline) {
        this.container.appendChild(wrap);
    } else {
        this.container.style.position = 'relative';
        var overlay = document.createElement('div');
        overlay.className = 'vs-cal-overlay';
        overlay.appendChild(wrap);
        this.container.appendChild(overlay);
        this.overlay = overlay;

        // Mode BULLE : les enfants reçoivent le clic, puis overlay stoppe la remontée
        overlay.addEventListener('mousedown', function(e) { e.stopPropagation(); });
        overlay.addEventListener('click', function(e) { e.stopPropagation(); });
        overlay.addEventListener('touchstart', function(e) { e.stopPropagation(); });

        // Fermer quand on clique en dehors
        document.addEventListener('mousedown', function(e) {
            if (!cal._isOpen) return;
            if (cal._skipClose) return;
            if (cal.overlay.contains(e.target)) return;
            if (cal.container.contains(e.target)) return;
            cal.close();
        });
    }

    this.wrap = wrap;
    this.grid = wrap.querySelector('.vs-cal-grid');

    // Events
    wrap.querySelector('.vs-cal-prev').addEventListener('click', function(e) {
        e.preventDefault(); cal.nav(-1);
    });
    wrap.querySelector('.vs-cal-next').addEventListener('click', function(e) {
        e.preventDefault(); cal.nav(1);
    });

    var selMonth = wrap.querySelector('.vs-cal-sel-month');
    var selYear = wrap.querySelector('.vs-cal-sel-year');
    if (selMonth) {
        selMonth.addEventListener('change', function() { cal.viewMonth = parseInt(this.value); cal.render(); });
    }
    if (selYear) {
        selYear.addEventListener('change', function() { cal.viewYear = parseInt(this.value); cal.render(); });
    }

    var btn = wrap.querySelector('.vs-cal-btn');
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            cal._applySelectionOnClose();
            if (!cal.opts.inline) cal.close();
        });
    }

    if (this.opts.mode === 'travel') {
        var boxDep = wrap.querySelector('.vs-cal-box-dep');
        var boxRet = wrap.querySelector('.vs-cal-box-ret');
        if (this.opts.autoReturn && this.opts.autoReturn > 0) {
            if (boxRet) boxRet.style.pointerEvents = 'none';
        } else {
            if (boxDep) boxDep.addEventListener('click', function() { cal.selectMode = 'start'; cal.updateHeader(); });
            if (boxRet) boxRet.addEventListener('click', function() { cal.selectMode = 'end'; cal.updateHeader(); });
        }
    }
};

VS08Calendar.prototype._buildTravelHeader = function() {
    var autoRet = this.opts.autoReturn && this.opts.autoReturn > 0;
    var html = '<div class="vs-cal-header">'
        + '<div class="vs-cal-route">'
        +   '<div><div class="vs-cal-iata">'+this.opts.from+'</div><div class="vs-cal-route-city">'+this.opts.fromCity+'</div></div>'
        +   '<div class="vs-cal-route-mid">'
        +     '<div class="vs-cal-route-line"></div>'
        +     '<div class="vs-cal-trail"></div>'
        +     '<div class="vs-cal-plane"><svg width="20" height="20" viewBox="0 0 24 24" fill="#59b7b7" style="display:block;transform:scaleX(-1)"><path d="M21 16v-2l-8-5V3.5A1.5 1.5 0 0 0 11.5 2 1.5 1.5 0 0 0 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z" transform="rotate(-90 12 12)"/></svg></div>'
        +   '</div>'
        +   '<div><div class="vs-cal-iata" style="text-align:right">'+this.opts.to+'</div><div class="vs-cal-route-city right">'+this.opts.toCity+'</div></div>'
        + '</div>';
    if (autoRet) {
        html += '<div class="vs-cal-sel-bar">'
            +   '<div class="vs-cal-sel-box active vs-cal-box-dep" style="flex:1;cursor:default"><div class="vs-cal-sel-box-label">S\u00e9lectionnez votre date de d\u00e9part</div><div class="vs-cal-sel-box-val placeholder vs-cal-dep-val">Cliquez sur un jour ci-dessous</div></div>'
            + '</div>'
            + '<div style="text-align:center;padding:6px 0 0;font-size:10px;color:rgba(255,255,255,.4);font-family:Outfit,sans-serif">Retour calcul\u00e9 automatiquement \u00b7 <span class="vs-cal-ret-info" style="color:#59b7b7;font-weight:600">' + this.opts.autoReturn + ' nuits</span></div>';
    } else {
        html += '<div class="vs-cal-sel-bar">'
            +   '<div class="vs-cal-sel-box active vs-cal-box-dep"><div class="vs-cal-sel-box-label">D\u00e9part</div><div class="vs-cal-sel-box-val placeholder vs-cal-dep-val">Choisir</div></div>'
            +   '<div class="vs-cal-sel-arrow">\u2192</div>'
            +   '<div class="vs-cal-sel-box vs-cal-box-ret"><div class="vs-cal-sel-box-label">Retour</div><div class="vs-cal-sel-box-val placeholder vs-cal-ret-val">Choisir</div></div>'
            + '</div>';
    }
    html += '</div>';
    return html;
};

VS08Calendar.prototype._buildSimpleHeader = function() {
    return '<div class="vs-cal-header-simple">'
        + '<div class="vs-cal-header-title">'+this.opts.title+'</div>'
        + (this.opts.subtitle ? '<div class="vs-cal-header-sub">'+this.opts.subtitle+'</div>' : '')
        + '<div class="vs-cal-header-val empty vs-cal-simple-val">Choisir une date</div>'
        + '</div>';
};

VS08Calendar.prototype.nav = function(dir) {
    this.viewMonth += dir;
    if (this.viewMonth > 11) { this.viewMonth = 0; this.viewYear++; }
    if (this.viewMonth < 0)  { this.viewMonth = 11; this.viewYear--; }
    this.render();
};

VS08Calendar.prototype.render = function() {
    var cal = this;
    var grid = this.grid;
    grid.innerHTML = '';

    var titleEl = this.wrap.querySelector('.vs-cal-nav-title');
    if (titleEl) titleEl.textContent = MOIS[this.viewMonth] + ' ' + this.viewYear;

    var selMonth = this.wrap.querySelector('.vs-cal-sel-month');
    var selYear = this.wrap.querySelector('.vs-cal-sel-year');
    if (selMonth) selMonth.value = this.viewMonth;
    if (selYear) selYear.value = this.viewYear;

    var first = new Date(this.viewYear, this.viewMonth, 1);
    var startDay = (first.getDay() + 6) % 7;
    var daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
    var todayStr = this._dateStr(this.today);

    for (var i = 0; i < startDay; i++) {
        var e = document.createElement('div');
        e.className = 'vs-cal-cell empty';
        grid.appendChild(e);
    }

    for (var d = 1; d <= daysInMonth; d++) {
        var dt = new Date(this.viewYear, this.viewMonth, d);
        var dtStr = this._dateStr(dt);
        var cell = document.createElement('div');
        cell.className = 'vs-cal-cell';

        var dayEl = document.createElement('div');
        dayEl.className = 'vs-cal-cell-day';
        dayEl.textContent = d;
        cell.appendChild(dayEl);

        var isDisabled = false;
        if (this.opts.minDate && dt < this._clearTime(this.opts.minDate)) isDisabled = true;
        if (this.opts.maxDate && dt > this._clearTime(this.opts.maxDate)) isDisabled = true;
        if (this.opts.available && this.opts.available.indexOf(dtStr) === -1) isDisabled = true;

        if (isDisabled) {
            cell.classList.add('disabled');
        } else {
            if (this.opts.mode === 'travel' && this.opts.prices[dtStr]) {
                var sub = document.createElement('div');
                sub.className = 'vs-cal-cell-sub';
                sub.textContent = this.opts.prices[dtStr];
                cell.appendChild(sub);
            }

            if (this.opts.available) cell.classList.add('available');
            if (dtStr === todayStr) cell.classList.add('today');
            this._applySelection(cell, dt);

            (function(date) {
                cell.addEventListener('click', function() { cal.selectDate(date); });
            })(dt);
        }

        grid.appendChild(cell);
    }

    this.updateHeader();
    this.updateFooter();
};

VS08Calendar.prototype._applySelection = function(cell, dt) {
    var ts = dt.getTime();
    var hasStart = this.selected;
    var hasEnd = this.selectedEnd;
    var autoRet = this.opts.autoReturn && this.opts.autoReturn > 0;

    // Mode "date aller uniquement" : on n'affiche qu'une seule date sélectionnée (pas de plage)
    if (autoRet && hasStart && ts === this.selected.getTime()) {
        cell.classList.add('selected');
        return;
    }
    if (hasStart && hasEnd && !autoRet) {
        var startTs = this.selected.getTime();
        var endTs = this.selectedEnd.getTime();
        if (ts === startTs && ts === endTs) {
            cell.classList.add('selected');
        } else if (ts === startTs) {
            cell.classList.add('range-start');
        } else if (ts === endTs) {
            cell.classList.add('range-end');
        } else if (ts > startTs && ts < endTs) {
            cell.classList.add('in-range');
        }
    } else if (hasStart && ts === this.selected.getTime()) {
        cell.classList.add('selected');
    }
};

VS08Calendar.prototype.selectDate = function(dt) {
    var mode = this.opts.mode;

    if (mode === 'date') {
        this.selected = dt;
        this._writeInput(dt);
        if (this.opts.onSelect) this.opts.onSelect(dt);
        if (this.opts.onConfirm) this.opts.onConfirm(dt);
        if (this.overlay) this.close();
    } else if (this.opts.autoReturn && this.opts.autoReturn > 0) {
        this.selected = dt;
        var ret = new Date(dt);
        ret.setDate(ret.getDate() + this.opts.autoReturn);
        this.selectedEnd = ret;
        this._writeInput(this.selected);
        this._writeInputEnd(this.selectedEnd);
        if (this.opts.onSelect) this.opts.onSelect(this.selected, this.selectedEnd);
    } else {
        if (this.selected && this.selectedEnd) {
            this.selected = dt;
            this.selectedEnd = null;
            this.selectMode = 'end';
        } else if (this.selectMode === 'start') {
            this.selected = dt;
            if (this.selectedEnd && this.selectedEnd <= dt) this.selectedEnd = null;
            this.selectMode = 'end';
        } else {
            if (this.selected && dt <= this.selected) {
                this.selected = dt;
                this.selectedEnd = null;
            } else {
                this.selectedEnd = dt;
            }
        }
        this._writeInput(this.selected);
        this._writeInputEnd(this.selectedEnd);
        if (this.opts.onSelect) this.opts.onSelect(this.selected, this.selectedEnd);
    }

    this.render();
};

VS08Calendar.prototype.updateHeader = function() {
    if (this.opts.mode === 'travel') {
        var autoRet = this.opts.autoReturn && this.opts.autoReturn > 0;
        var depVal = this.wrap.querySelector('.vs-cal-dep-val');
        var retVal = this.wrap.querySelector('.vs-cal-ret-val');
        var boxDep = this.wrap.querySelector('.vs-cal-box-dep');
        var boxRet = this.wrap.querySelector('.vs-cal-box-ret');

        if (autoRet) {
            if (this.selected) {
                var depTxt = this.selected.getDate() + ' ' + MOIS_S[this.selected.getMonth()];
                var retTxt = this.selectedEnd ? (this.selectedEnd.getDate() + ' ' + MOIS_S[this.selectedEnd.getMonth()]) : '';
                depVal.innerHTML = '<span style="font-weight:700;font-size:15px">' + depTxt + '</span>' + (retTxt ? ' <span style="opacity:.5;font-size:11px">\u2192 retour ' + retTxt + '</span>' : '');
                depVal.classList.remove('placeholder');
            } else {
                depVal.textContent = 'Cliquez sur un jour ci-dessous';
                depVal.classList.add('placeholder');
            }
        } else {
            if (this.selected) {
                depVal.textContent = this.selected.getDate() + ' ' + MOIS_S[this.selected.getMonth()];
                depVal.classList.remove('placeholder');
            } else { depVal.textContent = 'Choisir'; depVal.classList.add('placeholder'); }
            if (this.selectedEnd) {
                retVal.textContent = this.selectedEnd.getDate() + ' ' + MOIS_S[this.selectedEnd.getMonth()];
                retVal.classList.remove('placeholder');
            } else { retVal.textContent = 'Choisir'; retVal.classList.add('placeholder'); }

            boxDep.classList.toggle('active', this.selectMode === 'start');
            if (boxRet) boxRet.classList.toggle('active', this.selectMode === 'end');
        }
    } else {
        var simpleVal = this.wrap.querySelector('.vs-cal-simple-val');
        if (simpleVal) {
            if (this.selected) {
                var txt = this.selected.getDate() + ' ' + MOIS[this.selected.getMonth()] + ' ' + this.selected.getFullYear();
                if (this.selectedEnd) {
                    txt += '  \u2192  ' + this.selectedEnd.getDate() + ' ' + MOIS[this.selectedEnd.getMonth()] + ' ' + this.selectedEnd.getFullYear();
                }
                simpleVal.textContent = txt;
                simpleVal.classList.remove('empty');
            } else { simpleVal.textContent = 'Choisir une date'; simpleVal.classList.add('empty'); }
        }
    }
};

VS08Calendar.prototype.updateFooter = function() {
    if (!this.wrap) return;
    var btn = this.wrap.querySelector('.vs-cal-btn');
    var nightsEl = this.wrap.querySelector('.vs-cal-nights');
    var hintEl = this.wrap.querySelector('.vs-cal-footer-hint');

    if (!btn && !nightsEl && !hintEl) return;

    var setDisabled = function(el, val) { if (el && typeof el.disabled !== 'undefined') el.disabled = val; };

    var autoRet = this.opts.autoReturn && this.opts.autoReturn > 0;
    if (this.opts.mode === 'travel' || this.opts.mode === 'range') {
        if (autoRet && this.selected && this.selectedEnd) {
            var n2 = Math.round((this.selectedEnd - this.selected) / 86400000);
            if (nightsEl) nightsEl.innerHTML = '\u2708\uFE0F <span class="vs-cal-nights-badge">' + n2 + ' nuit' + (n2 > 1 ? 's' : '') + '</span> \u00b7 retour le ' + this.selectedEnd.getDate() + ' ' + MOIS_S[this.selectedEnd.getMonth()];
            setDisabled(btn, false);
        } else if (this.selected && this.selectedEnd) {
            var n = Math.round((this.selectedEnd - this.selected) / 86400000);
            var label = this.opts.mode === 'travel' ? ' nuit' : ' jour';
            if (nightsEl) nightsEl.innerHTML = '\uD83C\uDF19 <span class="vs-cal-nights-badge">' + n + label + (n > 1 ? 's' : '') + '</span>';
            setDisabled(btn, false);
        } else if (this.selected) {
            var hint = this.opts.mode === 'travel'
                ? 'S\u00e9lectionnez le retour'
                : 'S\u00e9lectionnez la date au plus tard';
            if (nightsEl) nightsEl.innerHTML = '<span class="vs-cal-footer-hint">' + hint + '</span>';
            setDisabled(btn, true);
        } else { if (nightsEl) nightsEl.innerHTML = ''; setDisabled(btn, true); }
    } else {
        if (this.selected) {
            if (hintEl) hintEl.textContent = this._dateStr(this.selected);
            setDisabled(btn, false);
        } else {
            if (hintEl) hintEl.textContent = 'Cliquez sur une date';
            setDisabled(btn, true);
        }
    }
};

// ══════════════════════════════════════════════
// DROPDOWN : OPEN / CLOSE / TOGGLE
// ══════════════════════════════════════════════
VS08Calendar.prototype.open = function() {
    if (!this.overlay) return;
    this.overlay.classList.add('open');
    this._isOpen = true;
    this._skipClose = true;
    var cal = this;
    setTimeout(function() { cal._skipClose = false; }, 350);
};

VS08Calendar.prototype.close = function() {
    if (!this.overlay) return;
    // Appliquer la sélection même si le client n'a pas cliqué sur Confirmer/Valider
    this._applySelectionOnClose();
    this.overlay.classList.remove('open');
    this._isOpen = false;
};

/** Applique la sélection en cours (inputs + trigger) à la fermeture ou sans clic sur le bouton */
VS08Calendar.prototype._applySelectionOnClose = function() {
    if (this.opts.mode === 'date') {
        if (this.selected) {
            this._writeInput(this.selected);
            if (this.opts.onConfirm) this.opts.onConfirm(this.selected);
        }
    } else {
        if (this.selected) {
            this._writeInput(this.selected);
            if (this.selectedEnd) this._writeInputEnd(this.selectedEnd);
            if (this.opts.onConfirm) this.opts.onConfirm(this.selected, this.selectedEnd);
        }
    }
};

VS08Calendar.prototype.toggle = function() {
    if (this._isOpen) { this.close(); } else { this.open(); }
};

// Input writing
VS08Calendar.prototype._writeInput = function(dt) {
    if (this.inputEl && dt) {
        this.inputEl.value = this._dateStr(dt);
        var ev = new Event('change', { bubbles: true });
        this.inputEl.dispatchEvent(ev);
    }
};
VS08Calendar.prototype._writeInputEnd = function(dt) {
    if (this.inputEndEl && dt) {
        this.inputEndEl.value = this._dateStr(dt);
        var ev = new Event('change', { bubbles: true });
        this.inputEndEl.dispatchEvent(ev);
    }
};

// Helpers
VS08Calendar.prototype._dateStr = function(d) {
    if (!d) return '';
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
};
VS08Calendar.prototype._clearTime = function(d) {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
};

VS08Calendar.prototype.setDate = function(dt) {
    this.selected = dt;
    if (dt) { this.viewMonth = dt.getMonth(); this.viewYear = dt.getFullYear(); }
    this.render();
};
VS08Calendar.prototype.setRange = function(start, end) {
    this.selected = start;
    this.selectedEnd = end;
    if (start) { this.viewMonth = start.getMonth(); this.viewYear = start.getFullYear(); }
    this.render();
};

/** Met à jour la liste des dates sélectionnables (YYYY-MM-DD). null = toutes autorisées dans min/max. */
VS08Calendar.prototype.setAvailable = function(dateStrings) {
    this.opts.available = Array.isArray(dateStrings) ? dateStrings : null;
    this.render();
};

/**
 * Affiche l'animation "Recherche de vols en cours" sous le header du calendrier.
 * @param {string} [msg] - texte personnalisé (défaut: "Recherche de vols en cours…")
 */
VS08Calendar.prototype.showFlightLoading = function(msg) {
    msg = msg || 'Recherche de vols en cours\u2026';
    var existing = this.wrap.querySelector('.vs-cal-flight-loading');
    if (!existing) {
        var el = document.createElement('div');
        el.className = 'vs-cal-flight-loading';
        el.innerHTML = '<div class="vs-cal-flight-loading-inner">'
            + '<div class="vs-cal-flight-loading-icon"><svg viewBox="0 0 24 24" fill="#59b7b7"><path d="M21 16v-2l-8-5V3.5A1.5 1.5 0 0 0 11.5 2 1.5 1.5 0 0 0 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z" transform="rotate(-45 12 12)"/></svg></div>'
            + '<div class="vs-cal-flight-loading-text">'
            + '<p class="vs-cal-flight-loading-label">' + msg + '</p>'
            + '<div class="vs-cal-flight-loading-bar"></div>'
            + '</div></div>';
        // Insérer après le perf (ligne de perforation)
        var perf = this.wrap.querySelector('.vs-cal-perf');
        if (perf && perf.nextSibling) {
            this.wrap.insertBefore(el, perf.nextSibling);
        } else {
            this.wrap.appendChild(el);
        }
        // Force reflow pour que la transition s'active
        el.offsetHeight;
        el.classList.add('active');
    } else {
        var label = existing.querySelector('.vs-cal-flight-loading-label');
        if (label) label.textContent = msg;
        existing.classList.remove('done');
        existing.classList.add('active');
    }
};

/**
 * Masque l'animation de recherche de vols avec un état "trouvé" temporaire.
 * @param {string} [msg] - texte de succès (défaut: "Vols trouvés ✓")
 * @param {number} [delay] - ms avant disparition (défaut: 2000)
 */
VS08Calendar.prototype.hideFlightLoading = function(msg, delay) {
    msg = msg || 'Vols trouv\u00e9s \u2713';
    delay = delay || 2000;
    var existing = this.wrap.querySelector('.vs-cal-flight-loading');
    if (!existing) return;
    var label = existing.querySelector('.vs-cal-flight-loading-label');
    if (label) label.textContent = msg;
    existing.classList.add('done');
    setTimeout(function() {
        existing.classList.remove('active', 'done');
    }, delay);
};

window.VS08Calendar = VS08Calendar;

})();
