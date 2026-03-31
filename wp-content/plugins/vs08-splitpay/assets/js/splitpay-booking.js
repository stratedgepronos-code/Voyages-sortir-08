/**
 * VS08 SplitPay — v3 — Intégration tunnel booking-steps.php
 *
 * CORRECTIONS v3 :
 *  1. Position : 3ème option radio dans "Mode de règlement" (pas en haut)
 *  2. Montant : partage l'ACOMPTE (pas le total)
 *  3. Nonce : refresh via BK_DATA.rest_nonce avant soumission
 *  4. Minimum : message rouge si montant trop bas, saisie libre au clavier
 */
(function($) {
    'use strict';

    var state = {
        enabled: false,
        participants: [],
        acompte: 0,
        total: 0,
        minShare: 0,
        prixVolPP: 0,
        maxParticipants: parseInt(vs08sp.max_participants) || 10,
        injected: false
    };

    $(document).ready(function() {
        if (typeof BK_DATA === 'undefined') return;
        // Hook bkGo pour détecter le passage au step 4
        if (typeof window.bkGo === 'function') {
            var orig = window.bkGo;
            window.bkGo = function(step) {
                orig(step);
                if (step === 4) setTimeout(inject, 300);
            };
        }
        // Observer en backup
        var obs = new MutationObserver(function() {
            var s4 = document.getElementById('bk-step-4');
            if (s4 && s4.style.display !== 'none' && !state.injected) setTimeout(inject, 200);
        });
        obs.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style'] });
    });

    /** Calcule le total et l'acompte (même logique que bkBuildRecap). */
    function computeAmounts() {
        var total = (parseFloat(BK_DATA.devis.total) || 0)
                  + (window.bk_vol_delta_total || 0)
                  + (window.bk_options_total || 0);
        if (window.bk_insurance_check) total += parseFloat(BK_DATA.insurance_total) || 0;
        total = Math.ceil(total);

        var acompte = total;
        if (!BK_DATA.payer_tout) {
            var pct = parseFloat(BK_DATA.acompte_pct) || 30;
            acompte = total * pct / 100;
            var prixVolPax = parseFloat(BK_DATA.params.prix_vol) || 0;
            var nbPax = (parseInt(BK_DATA.params.nb_golfeurs) || 0) + (parseInt(BK_DATA.params.nb_nongolfeurs) || 0);
            var coutVol = prixVolPax * nbPax + (window.bk_vol_delta_total || 0);
            if (coutVol > 0 && acompte < coutVol && total > 0) {
                pct = Math.ceil((coutVol / total) * 100 / 5) * 5;
                acompte = total * pct / 100;
            }
            acompte = Math.ceil(acompte);
        }
        return { total: total, acompte: acompte };
    }

    /** Injecte l'option "Payer à plusieurs" comme 3ème radio dans Mode de règlement. */
    function inject() {
        if (state.injected) return;
        // Trouver la section Mode de règlement
        var modeSection = null;
        var radios = document.querySelectorAll('input[name="bk-payment-mode"]');
        if (radios.length > 0) {
            modeSection = radios[0].closest('div[style]');
            // Remonter au conteneur avec le border
            while (modeSection && !modeSection.style.borderRadius) {
                modeSection = modeSection.parentElement;
            }
        }
        if (!modeSection) return;
        state.injected = true;

        var amounts = computeAmounts();
        state.total = amounts.total;
        state.acompte = amounts.acompte;
        state.prixVolPP = parseFloat(BK_DATA.params.prix_vol) || 0;

        // ── 3ème radio : Payer à plusieurs ──
        var radioDiv = document.createElement('div');
        radioDiv.id = 'vs08sp-radio-wrap';
        radioDiv.style.cssText = 'margin-top:12px;padding-top:12px;border-top:1px dashed #e5e7eb;';
        radioDiv.innerHTML = '<label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">'
            + '<input type="radio" name="bk-payment-mode" value="splitpay" id="bk-payment-splitpay" style="margin-top:4px;flex-shrink:0">'
            + '<span style="font-size:13px;color:#374151;line-height:1.5"><strong>👥 Payer à plusieurs</strong> — chaque participant reçoit son lien de paiement sécurisé.'
            + '<br><span style="font-size:11px;color:#59b7b7;">Idéal pour les groupes de golfeurs</span></span>'
            + '</label>';

        // Insérer après le dernier radio existant (paiement agence + son confirm wrap)
        var agenceWrap = document.getElementById('bk-agence-confirm-wrap');
        if (agenceWrap) {
            agenceWrap.parentNode.insertBefore(radioDiv, agenceWrap.nextSibling);
        } else {
            modeSection.appendChild(radioDiv);
        }

        // ── Formulaire de répartition (caché) ──
        var form = document.createElement('div');
        form.id = 'vs08sp-form';
        form.style.cssText = 'display:none;margin-top:14px;padding:16px;background:#f8f5f0;border-radius:10px;border:1.5px solid #59b7b7;';
        var amountLabel = BK_DATA.payer_tout ? 'Total à répartir' : 'Acompte à répartir';
        var amountValue = BK_DATA.payer_tout ? state.total : state.acompte;
        form.innerHTML = '<div style="font-size:14px;font-weight:700;color:#0b1120;margin-bottom:4px;">👥 Répartir le paiement</div>'
            + '<div style="font-size:12px;color:#6b7280;margin-bottom:14px;">' + amountLabel + ' : <strong style="color:#0b1120;">' + fmt(amountValue) + ' €</strong>'
            + (BK_DATA.payer_tout ? '' : ' (sur un total de ' + fmt(state.total) + ' €)') + '</div>'
            + '<div id="vs08sp-participants"></div>'
            + '<div id="vs08sp-total-bar" style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;margin-top:12px;border-radius:8px;font-size:13px;font-weight:600;background:#0b1120;color:#fff;">'
            + '<span>Total réparti : <strong id="vs08sp-sum">0</strong> / <strong id="vs08sp-target">' + fmt(amountValue) + '</strong> €</span>'
            + '<span id="vs08sp-remaining" style="color:#c8a45e;"></span>'
            + '</div>'
            + '<div id="vs08sp-error-msg" style="display:none;margin-top:8px;padding:8px 12px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;color:#dc2626;font-size:12px;font-weight:600;text-align:center;"></div>'
            + '<div style="display:flex;gap:10px;margin-top:14px;">'
            + '<button type="button" id="vs08sp-add" style="padding:8px 16px;border:2px dashed #ccc;background:none;border-radius:8px;cursor:pointer;font-size:13px;color:#888;">+ Participant</button>'
            + '<button type="button" id="vs08sp-submit" disabled style="flex:1;padding:12px;background:linear-gradient(135deg,#59b7b7,#4a9e9e);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;font-family:Outfit,sans-serif;">📤 Envoyer les liens de paiement</button>'
            + '</div>';
        radioDiv.appendChild(form);

        // ── Events radio ──
        $(document).on('change', 'input[name="bk-payment-mode"]', function() {
            var val = this.value;
            var submitBtn = document.querySelector('.bk-btn-submit');
            if (val === 'splitpay') {
                state.enabled = true;
                state.acompte = computeAmounts().acompte;
                state.total = computeAmounts().total;
                var av = BK_DATA.payer_tout ? state.total : state.acompte;
                $('#vs08sp-target').text(fmt(av));
                if (state.participants.length === 0) {
                    addParticipant(true, $('#fact-email').val() || '', (($('#fact-prenom').val() || '') + ' ' + ($('#fact-nom').val() || '')).trim());
                    addParticipant(false, '', '');
                }
                $('#vs08sp-form').slideDown(200);
                if (submitBtn) $(submitBtn).hide();
            } else {
                state.enabled = false;
                $('#vs08sp-form').slideUp(200);
                if (submitBtn) $(submitBtn).show();
            }
        });

        $('#vs08sp-form').on('click', '#vs08sp-add', function() {
            if (state.participants.length >= state.maxParticipants) {
                showError('Maximum ' + state.maxParticipants + ' participants.');
                return;
            }
            addParticipant(false, '', '');
            redistribute();
        });

        $('#vs08sp-form').on('click', '#vs08sp-submit', submitGroup);
    }

    function addParticipant(isCaptain, email, name) {
        var idx = state.participants.length;
        var amountToSplit = BK_DATA.payer_tout ? state.total : state.acompte;
        var share = Math.round(amountToSplit / (idx + 1));

        state.participants.push({ email: email, name: name, amount: share, is_captain: isCaptain });

        var html = '<div class="vs08sp-row" data-idx="' + idx + '" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;padding:10px;background:#fff;border-radius:8px;border:1px solid ' + (isCaptain ? '#c8a45e' : '#eee') + ';flex-wrap:wrap;">';
        if (isCaptain) html += '<span style="background:#c8a45e;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;letter-spacing:.5px;">CAPITAINE</span>';
        html += '<input type="text" placeholder="Prénom Nom" class="sp-name" value="' + esc(name) + '" style="flex:1;min-width:120px;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;font-family:Outfit,sans-serif;">';
        html += '<input type="email" placeholder="Email" class="sp-email" value="' + esc(email) + '" style="flex:1;min-width:150px;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;font-family:Outfit,sans-serif;">';
        html += '<input type="number" step="1" min="0" class="sp-amount" value="' + share + '" style="width:90px;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:700;text-align:right;font-family:Outfit,sans-serif;"> <span style="font-weight:600;font-size:13px;">€</span>';
        if (!isCaptain && idx > 1) html += '<button type="button" class="sp-remove" style="background:none;border:none;color:#e8734a;cursor:pointer;font-size:16px;padding:4px;">✕</button>';
        html += '</div>';

        $('#vs08sp-participants').append(html);

        // Events
        var $r = $('#vs08sp-participants .vs08sp-row').last();
        $r.find('.sp-name').on('input', function() { state.participants[idx].name = this.value; });
        $r.find('.sp-email').on('input', function() { state.participants[idx].email = this.value; });
        $r.find('.sp-amount').on('input', function() {
            state.participants[idx].amount = parseFloat(this.value) || 0;
            updateBar();
            // Check minimum
            var min = calcMin();
            if (state.participants[idx].amount > 0 && state.participants[idx].amount < min) {
                showError('⚠️ Attention : le montant minimum par participant est de ' + min + ' €. Veuillez saisir un montant suffisant.');
            } else {
                hideError();
            }
        });
        $r.find('.sp-remove').on('click', function() {
            state.participants.splice(idx, 1);
            renderAll();
            redistribute();
        });

        redistribute();
    }

    function renderAll() {
        var copy = state.participants.slice();
        $('#vs08sp-participants').empty();
        state.participants = [];
        copy.forEach(function(p) { addParticipant(p.is_captain, p.email, p.name); });
    }

    function redistribute() {
        if (!state.participants.length) return;
        var av = BK_DATA.payer_tout ? state.total : state.acompte;
        var share = Math.round(av / state.participants.length);
        var remainder = av - (share * state.participants.length);
        state.participants.forEach(function(p, i) {
            p.amount = share + (i === 0 ? remainder : 0);
            $('#vs08sp-participants .vs08sp-row').eq(i).find('.sp-amount').val(p.amount);
        });
        updateBar();
    }

    function updateBar() {
        var sum = 0;
        state.participants.forEach(function(p) { sum += p.amount; });
        var av = BK_DATA.payer_tout ? state.total : state.acompte;
        $('#vs08sp-sum').text(fmt(sum));
        var diff = av - sum;
        var $bar = $('#vs08sp-total-bar');
        var $rem = $('#vs08sp-remaining');
        if (Math.abs(diff) <= 1) {
            $bar.css('background', '#27ae60');
            $rem.text('✅ Répartition valide');
            // Check all minimums
            var min = calcMin();
            var allOk = true;
            state.participants.forEach(function(p) { if (p.amount > 0 && p.amount < min) allOk = false; });
            $('#vs08sp-submit').prop('disabled', !allOk);
            if (!allOk) showError('⚠️ Un ou plusieurs montants sont en dessous du minimum de ' + min + ' €.');
            else hideError();
        } else if (diff > 0) {
            $bar.css('background', '#e8734a');
            $rem.text('Reste ' + fmt(diff) + ' € à répartir');
            $('#vs08sp-submit').prop('disabled', true);
        } else {
            $bar.css('background', '#e8734a');
            $rem.text('Excédent de ' + fmt(Math.abs(diff)) + ' €');
            $('#vs08sp-submit').prop('disabled', true);
        }
    }

    function calcMin() {
        var nb = state.participants.length || 1;
        var av = BK_DATA.payer_tout ? state.total : state.acompte;
        var eq = av / nb;
        return Math.ceil(Math.max(eq * 0.30, state.prixVolPP));
    }

    function showError(msg) { $('#vs08sp-error-msg').text(msg).show(); }
    function hideError() { $('#vs08sp-error-msg').hide(); }

    /** Rafraîchit le nonce avant soumission (même mécanisme que bkSubmit). */
    function refreshNonce(callback) {
        if (BK_DATA.rest_nonce) {
            $.get(BK_DATA.rest_nonce).done(function(res) {
                if (res && res.nonce) vs08sp.nonce = res.nonce;
                callback();
            }).fail(function() { callback(); });
        } else {
            callback();
        }
    }

    function submitGroup() {
        var $btn = $('#vs08sp-submit');
        $btn.prop('disabled', true).text('Envoi en cours...');

        // Relire les valeurs depuis le DOM
        state.participants.forEach(function(p, i) {
            var $r = $('#vs08sp-participants .vs08sp-row').eq(i);
            p.email = $r.find('.sp-email').val() || '';
            p.name = $r.find('.sp-name').val() || '';
            p.amount = parseFloat($r.find('.sp-amount').val()) || 0;
        });

        // Valider emails
        for (var i = 0; i < state.participants.length; i++) {
            if (!state.participants[i].email || state.participants[i].email.indexOf('@') === -1) {
                showError('Email invalide : ' + (state.participants[i].email || '(vide)'));
                $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
                return;
            }
        }

        // Doublons
        var emails = state.participants.map(function(p) { return p.email.toLowerCase(); });
        if (new Set(emails).size !== emails.length) {
            showError('Deux participants ne peuvent pas avoir le même email.');
            $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
            return;
        }

        // Rafraîchir le nonce puis envoyer
        refreshNonce(function() {
            var amounts = computeAmounts();
            var payload = {
                booking_data: {
                    voyage_id: BK_DATA.voyage_id,
                    voyage_titre: BK_DATA.titre,
                    total: amounts.total,
                    acompte: amounts.acompte,
                    payer_tout: BK_DATA.payer_tout,
                    params: BK_DATA.params,
                    devis: BK_DATA.devis,
                    facturation: {
                        prenom: $('#fact-prenom').val() || '',
                        nom: $('#fact-nom').val() || '',
                        email: $('#fact-email').val() || '',
                        tel: $('#fact-tel').val() || '',
                        adresse: $('#fact-adresse').val() || '',
                        cp: $('#fact-cp').val() || '',
                        ville: $('#fact-ville').val() || '',
                    }
                },
                participants: state.participants.map(function(p) {
                    return { email: p.email, name: p.name, amount: p.amount, is_captain: p.is_captain };
                }),
                nonce: vs08sp.nonce
            };

            $.ajax({
                url: vs08sp.rest_url + 'create-group',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(r) {
                    if (r.success) {
                        hideError();
                        $btn.text('✅ Liens envoyés !').css('background', '#27ae60');
                        showLinks(r.shares, r.expires);
                    } else {
                        showError(r.message || 'Erreur.');
                        $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
                    }
                },
                error: function() {
                    showError('Erreur de connexion. Réessayez.');
                    $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
                }
            });
        });
    }

    function showLinks(shares, expires) {
        var html = '<div style="margin-top:14px;padding:16px;background:#edf8f8;border-radius:10px;">';
        html += '<div style="font-weight:700;font-size:15px;color:#0b1120;margin-bottom:4px;">📤 Liens envoyés !</div>';
        html += '<div style="font-size:11px;color:#888;margin-bottom:10px;">Expire le ' + expires + '</div>';
        shares.forEach(function(s) {
            var badge = s.is_captain ? ' <span style="background:#c8a45e;color:#fff;padding:1px 5px;border-radius:6px;font-size:9px;font-weight:700;">CAP</span>' : '';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;margin:4px 0;background:#fff;border-radius:6px;border:1px solid #eee;font-size:13px;">';
            html += '<div><strong>' + esc(s.name || s.email) + '</strong>' + badge + '<br><span style="color:#888;font-size:11px;">' + esc(s.email) + '</span></div>';
            html += '<div style="text-align:right;"><strong>' + fmt(s.amount) + ' €</strong><br><span style="color:#59b7b7;font-size:11px;">📧 Envoyé</span></div>';
            html += '</div>';
        });
        html += '</div>';
        $('#vs08sp-form').find('div:last').hide(); // cacher les boutons
        $('#vs08sp-form').append(html);
    }

    function fmt(n) { return Math.round(n).toLocaleString('fr-FR'); }
    function esc(s) { return $('<span>').text(s || '').html(); }

})(jQuery);
