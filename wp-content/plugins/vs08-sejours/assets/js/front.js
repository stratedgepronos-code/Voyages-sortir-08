/**
 * VS08 Séjours — Frontend JS
 * Gère : sélection aéroport → calendrier → recherche vols + hôtel → calcul prix
 */
(function() {
    'use strict';

    if (typeof vs08s === 'undefined') return;

    var state = {
        sejour_id: 0,
        aeroport: '',
        date: '',
        adults: 2,
        rooms: 1,
        vol_price: 0,
        vol_offer_id: '',
        hotel_net: 0,
        hotel_board: 'AI',
        hotel_room_name: '',
    };

    // ── Init ──
    function init() {
        var wrap = document.getElementById('sj-calc-card');
        if (!wrap) return;
        state.sejour_id = parseInt(wrap.dataset.sejourId || '0', 10);

        // Aéroport change → activer le calendrier
        var aeroSelect = document.getElementById('sj-aeroport');
        if (aeroSelect) {
            aeroSelect.addEventListener('change', function() {
                state.aeroport = this.value;
                state.date = '';
                state.vol_price = 0;
                state.hotel_net = 0;
                hideResults();
                if (state.aeroport) {
                    showCalendar();
                }
            });
        }

        // Adultes / chambres
        var adultsInput = document.getElementById('sj-adults');
        var roomsInput = document.getElementById('sj-rooms');
        if (adultsInput) adultsInput.addEventListener('change', function() { state.adults = parseInt(this.value) || 2; });
        if (roomsInput) roomsInput.addEventListener('change', function() { state.rooms = parseInt(this.value) || 1; });

        // Bouton réserver
        var bookBtn = document.getElementById('sj-btn-book');
        if (bookBtn) bookBtn.addEventListener('click', goToBooking);

        console.log('[VS08S] front.js chargé — séjour #' + state.sejour_id);
    }

    // ── Calendrier (réutilise VS08Calendar si dispo) ──
    function showCalendar() {
        // Si VS08Calendar est disponible (vs08-voyages), l'utiliser
        if (typeof VS08Calendar !== 'undefined') {
            var calWrap = document.getElementById('sj-calendar-wrap');
            if (!calWrap) return;
            calWrap.style.display = 'block';

            if (!calWrap._calendar) {
                calWrap._calendar = new VS08Calendar(calWrap, {
                    mode: 'date',
                    minDate: new Date(Date.now() + 7 * 86400000), // +7 jours
                    onSelect: function(date) {
                        var d = date.toISOString().split('T')[0];
                        state.date = d;
                        searchAll();
                    }
                });
            }
        } else {
            // Fallback : input type=date
            var dateInput = document.getElementById('sj-date');
            if (dateInput) {
                dateInput.style.display = 'block';
                dateInput.addEventListener('change', function() {
                    state.date = this.value;
                    if (state.date) searchAll();
                });
            }
        }
    }

    // ── Recherche vols + hôtel en parallèle ──
    function searchAll() {
        if (!state.aeroport || !state.date) return;

        showLoading('Recherche des vols et de l\'hôtel...');
        hideResults();

        // Lancer les deux recherches en parallèle
        Promise.all([
            searchFlights(),
            searchHotel()
        ]).then(function(results) {
            hideLoading();
            var flights = results[0];
            var hotel = results[1];

            if (flights && hotel) {
                calculateTotal();
            }
        }).catch(function(err) {
            hideLoading();
            showError('Erreur de recherche : ' + (err.message || err));
        });
    }

    function parseRestResponse(r) {
        return r.text().then(function(txt) {
            var data = {};
            try { data = txt ? JSON.parse(txt) : {}; } catch (e) {}
            if (!r.ok) {
                throw new Error((data && data.message) ? data.message : ('Erreur HTTP ' + r.status));
            }
            return data;
        });
    }

    // ── Recherche vols via Duffel ──
    function searchFlights() {
        return fetch(vs08s.rest_url + 'flights', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': vs08s.nonce },
            body: JSON.stringify({
                sejour_id: state.sejour_id,
                aeroport: state.aeroport,
                date: state.date,
                adults: state.adults,
                iata_dest: vs08s.iata_dest || '',
                duree: vs08s.duree || 7
            })
        })
        .then(parseRestResponse)
        .then(function(data) {
            if (data.code || data.message) {
                showError('Vols : ' + (data.message || 'Aucun vol trouvé'));
                return null;
            }
            // Prendre le meilleur prix
            var offers = data.combos || data.data || data.flights || data;
            if (Array.isArray(offers) && offers.length > 0) {
                // Trier par prix
                offers.sort(function(a, b) {
                    return (a.price_per_pax || a.total_amount || a.price || 999999) - (b.price_per_pax || b.total_amount || b.price || 999999);
                });
                var best = offers[0];
                state.vol_price = parseFloat(best.price_per_pax || ((best.total_amount || best.price || 0) / state.adults) || 0);
                state.vol_offer_id = best.offer_id || best.id || '';
                return best;
            }
            showError('Aucun vol trouvé pour ces dates.');
            return null;
        });
    }

    // ── Recherche hôtel via Bedsonline ──
    function searchHotel() {
        return fetch(vs08s.rest_url + 'hotel-availability', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': vs08s.nonce },
            body: JSON.stringify({
                sejour_id: state.sejour_id,
                date: state.date,
                adults: state.adults,
                rooms: state.rooms,
                hotel_code: vs08s.hotel_code || '',
                hotel_codes: vs08s.hotel_codes || [],
                duree: vs08s.duree || 7
            })
        })
        .then(parseRestResponse)
        .then(function(data) {
            if (data.code || !data.best) {
                showError('Hôtel : ' + (data.message || 'Aucune disponibilité'));
                return null;
            }
            state.hotel_net = parseFloat(data.best.net_price || 0);
            state.hotel_board = data.best.board_code || 'AI';
            state.hotel_room_name = data.best.room_name || '';

            // Afficher le résultat hôtel
            var hotelResult = document.getElementById('sj-hotel-result');
            if (hotelResult) {
                hotelResult.classList.add('active');
                var nameEl = hotelResult.querySelector('.sj-hotel-result-name');
                var boardEl = hotelResult.querySelector('.sj-hotel-result-board');
                var priceEl = hotelResult.querySelector('.sj-hotel-result-price');
                if (nameEl) nameEl.textContent = data.hotel_name + ' — ' + (data.best.room_name || '');
                if (boardEl) boardEl.textContent = data.best.board_name || state.hotel_board;
                if (priceEl) priceEl.textContent = fmt(state.hotel_net) + ' € net';
            }

            return data;
        });
    }

    // ── Calcul prix total ──
    function calculateTotal() {
        var priceResult = document.getElementById('sj-price-result');
        if (!priceResult) return;

        fetch(vs08s.rest_url + 'calculate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': vs08s.nonce },
            body: JSON.stringify({
                sejour_id: state.sejour_id,
                date_depart: state.date,
                aeroport: state.aeroport,
                nb_adultes: state.adults,
                nb_chambres: state.rooms,
                vol_price: state.vol_price,
                hotel_net: state.hotel_net,
                hotel_board: state.hotel_board,
                hotel_room_name: state.hotel_room_name,
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(devis) {
            priceResult.style.display = 'block';
            var linesEl = priceResult.querySelector('.sj-price-lines');
            var totalEl = document.getElementById('sj-total-val');
            var perEl = document.getElementById('sj-per-person');
            var bookBtn = document.getElementById('sj-btn-book');

            if (linesEl && devis.lines) {
                linesEl.innerHTML = devis.lines.map(function(l) {
                    return '<div class="sj-price-line"><span>' + esc(l.label) + '</span><span style="font-weight:600">' + fmt(l.montant) + ' €</span></div>';
                }).join('');
            }
            if (totalEl) totalEl.textContent = fmt(devis.total) + ' €';
            if (perEl) perEl.textContent = fmt(devis.prix_par_personne) + ' €/pers.';
            if (bookBtn) bookBtn.disabled = false;
        });
    }

    // ── Navigation vers la page booking ──
    function goToBooking() {
        var params = new URLSearchParams({
            sejour_id: state.sejour_id,
            aeroport: state.aeroport,
            date_depart: state.date,
            nb_adultes: state.adults,
            nb_chambres: state.rooms,
            vol_price: state.vol_price,
            vol_offer_id: state.vol_offer_id,
            hotel_net: state.hotel_net,
            hotel_board: state.hotel_board,
        });
        // Rediriger vers la page de réservation
        window.location.href = window.location.pathname.replace(/\/$/, '') + '/reserver/?' + params.toString();
    }

    // ── Helpers ──
    function fmt(n) { return Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function showLoading(msg) {
        var el = document.getElementById('sj-loading');
        if (el) { el.classList.add('active'); el.querySelector('span').textContent = msg || 'Recherche en cours...'; }
    }
    function hideLoading() {
        var el = document.getElementById('sj-loading');
        if (el) el.classList.remove('active');
    }
    function showError(msg) {
        var el = document.getElementById('sj-error');
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }
    function hideResults() {
        var el = document.getElementById('sj-price-result');
        if (el) el.style.display = 'none';
        var hr = document.getElementById('sj-hotel-result');
        if (hr) hr.classList.remove('active');
        var err = document.getElementById('sj-error');
        if (err) err.style.display = 'none';
        var bookBtn = document.getElementById('sj-btn-book');
        if (bookBtn) bookBtn.disabled = true;
    }

    // ── Go ──
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
