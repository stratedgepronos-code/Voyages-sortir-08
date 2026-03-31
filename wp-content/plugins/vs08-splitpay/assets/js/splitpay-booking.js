/**
 * VS08 SplitPay — Intégration tunnel de réservation (booking-steps.php)
 *
 * S'insère dans le step 4 (Confirmation) du tunnel VS08.
 * Utilise BK_DATA (variable globale exposée par booking-steps.php).
 *
 * Quand le capitaine coche "Payer à plusieurs" :
 *  1. Le bouton "Procéder au paiement" est remplacé par le formulaire de répartition
 *  2. Il entre les emails + noms + montants de chaque participant
 *  3. Il clique "Envoyer les liens" → appel REST API → groupe créé → emails envoyés
 */
(function($) {
    'use strict';

    var state = {
        enabled: false,
        participants: [],
        total: 0,
        minShare: 0,
        prixVolPP: 0,
        maxParticipants: parseInt(vs08sp.max_participants) || 10,
        injected: false
    };

    $(document).ready(function() {
        // Vérifier qu'on est dans le tunnel de réservation
        if (typeof BK_DATA === 'undefined') return;

        // Observer le step 4 (Confirmation) pour injecter le formulaire
        // Le step 4 se révèle quand l'utilisateur clique "Continuer" au step 3
        var observer = new MutationObserver(function() {
            var step4 = document.getElementById('bk-step-4');
            if (step4 && step4.style.display !== 'none' && !state.injected) {
                injectSplitForm();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style'] });

        // Aussi écouter la fonction bkGo pour détecter le passage au step 4
        if (typeof window.bkGo === 'function') {
            var originalBkGo = window.bkGo;
            window.bkGo = function(step) {
                originalBkGo(step);
                if (step === 4 && !state.injected) {
                    setTimeout(injectSplitForm, 200);
                }
            };
        }
    });

    /**
     * Calcule le total actuel du voyage (base + vols + options + assurance).
     */
    function computeTotal() {
        var total = (parseFloat(BK_DATA.devis.total) || 0)
                  + (window.bk_vol_delta_total || 0)
                  + (window.bk_options_total || 0);
        if (window.bk_insurance_check) {
            total += parseFloat(BK_DATA.insurance_total) || 0;
        }
        return Math.ceil(total);
    }

    /**
     * Injecte le formulaire de répartition dans le step 4.
     */
    function injectSplitForm() {
        var step4 = document.getElementById('bk-step-4');
        if (!step4 || state.injected) return;
        state.injected = true;

        // Calculer le total et le prix vol
        state.total = computeTotal();
        state.prixVolPP = parseFloat(BK_DATA.params.prix_vol) || 0;

        // Trouver l'emplacement : avant les checkboxes CGU
        var cguLabel = step4.querySelector('.bk-cgu') || step4.querySelector('[id="bk-cgu"]');
        var insertBefore = cguLabel ? cguLabel.parentElement || cguLabel : null;

        // Fallback : insérer avant la nav (boutons)
        if (!insertBefore) {
            insertBefore = step4.querySelector('.bk-nav');
        }
        if (!insertBefore) return;

        // ── Checkbox toggle ──
        var toggle = document.createElement('div');
        toggle.className = 'vs08sp-toggle';
        toggle.style.cssText = 'margin:20px 0;padding:18px;background:linear-gradient(135deg,#f8f5f0,#fff);border-radius:12px;border:2px solid #59b7b7;';
        toggle.innerHTML = '<label style="display:flex;align-items:center;gap:14px;cursor:pointer;font-size:15px;color:#0b1120;">'
            + '<input type="checkbox" id="vs08sp-enable" style="width:22px;height:22px;accent-color:#59b7b7;flex-shrink:0;">'
            + '<span><strong>👥 Payer à plusieurs</strong><br><span style="font-size:13px;color:#6b7280;">Chaque participant reçoit son propre lien de paiement sécurisé</span></span>'
            + '</label>';
        insertBefore.parentNode.insertBefore(toggle, insertBefore);

        // ── Formulaire de répartition (caché par défaut) ──
        var form = document.createElement('div');
        form.id = 'vs08sp-form';
        form.className = 'vs08sp-split-section';
        form.style.display = 'none';
        form.innerHTML = '<div class="vs08sp-split-title">👥 Répartir le paiement</div>'
            + '<div id="vs08sp-participants"></div>'
            + '<div class="vs08sp-total-bar" id="vs08sp-total-bar">'
            + '<span>Total réparti : <strong id="vs08sp-sum">0</strong> / <strong id="vs08sp-target">' + formatMoney(state.total) + '</strong> €</span>'
            + '<span class="vs08sp-total-remaining" id="vs08sp-remaining"></span>'
            + '</div>'
            + '<div class="vs08sp-actions">'
            + '<button type="button" class="vs08sp-add-btn" id="vs08sp-add">+ Ajouter un participant</button>'
            + '<button type="button" class="vs08sp-submit-btn" id="vs08sp-submit" disabled>📤 Envoyer les liens de paiement</button>'
            + '</div>'
            + '<div id="vs08sp-message" style="margin-top:12px;text-align:center;font-size:14px;"></div>';
        insertBefore.parentNode.insertBefore(form, insertBefore);

        // ── Events ──
        var submitBtn = step4.querySelector('.bk-btn-submit');

        $('#vs08sp-enable').on('change', function() {
            state.enabled = this.checked;
            if (state.enabled) {
                state.total = computeTotal();
                $('#vs08sp-target').text(formatMoney(state.total));
                if (state.participants.length === 0) {
                    // Auto-remplir le capitaine avec les données de facturation
                    var captEmail = $('#fact-email').val() || '';
                    var captName = ($('#fact-prenom').val() || '') + ' ' + ($('#fact-nom').val() || '');
                    addParticipant(true, captEmail.trim(), captName.trim());
                    addParticipant(false, '', '');
                }
                $('#vs08sp-form').slideDown(300);
                if (submitBtn) $(submitBtn).hide();
            } else {
                $('#vs08sp-form').slideUp(300);
                if (submitBtn) $(submitBtn).show();
            }
        });

        $('#vs08sp-add').on('click', function() {
            if (state.participants.length >= state.maxParticipants) {
                showMessage('Maximum ' + state.maxParticipants + ' participants.', 'error');
                return;
            }
            addParticipant(false, '', '');
            redistributeEqually();
        });

        $('#vs08sp-submit').on('click', submitGroup);
    }

    /**
     * Ajoute une ligne participant.
     */
    function addParticipant(isCaptain, prefillEmail, prefillName) {
        var idx = state.participants.length;
        var equalShare = Math.round(state.total / (idx + 1));

        state.participants.push({
            email: prefillEmail || '',
            name: prefillName || '',
            amount: equalShare,
            is_captain: isCaptain
        });

        var html = '<div class="vs08sp-participant-row' + (isCaptain ? ' is-captain' : '') + '" data-idx="' + idx + '">';
        if (isCaptain) html += '<span class="vs08sp-captain-badge">Capitaine</span>';
        html += '<input type="text" placeholder="Prénom Nom" class="vs08sp-name" data-idx="' + idx + '" value="' + escAttr(prefillName) + '">';
        html += '<input type="email" placeholder="Email" class="vs08sp-email" data-idx="' + idx + '" value="' + escAttr(prefillEmail) + '">';
        html += '<input type="number" step="1" min="0" class="vs08sp-amount" data-idx="' + idx + '" value="' + equalShare + '"> <span style="font-weight:600;">€</span>';
        if (!isCaptain && idx > 1) {
            html += '<button type="button" class="vs08sp-remove-btn" data-idx="' + idx + '">✕</button>';
        }
        html += '</div>';

        $('#vs08sp-participants').append(html);

        // Events sur les inputs
        var $row = $('[data-idx="' + idx + '"].vs08sp-participant-row');
        $row.find('.vs08sp-name').on('input', function() { state.participants[idx].name = this.value; });
        $row.find('.vs08sp-email').on('input', function() { state.participants[idx].email = this.value; });
        $row.find('.vs08sp-amount').on('input', function() {
            var val = parseFloat(this.value) || 0;
            var min = calculateMinShare();
            if (val < min && val > 0) {
                this.value = min;
                val = min;
                showMessage('Montant minimum par participant : ' + min + ' €', 'warn');
            }
            state.participants[idx].amount = val;
            updateTotalBar();
        });
        $row.find('.vs08sp-remove-btn').on('click', function() { removeParticipant(idx); });

        redistributeEqually();
    }

    function removeParticipant(idx) {
        state.participants.splice(idx, 1);
        renderParticipants();
        redistributeEqually();
    }

    function renderParticipants() {
        var copy = state.participants.slice();
        $('#vs08sp-participants').empty();
        state.participants = [];
        copy.forEach(function(p) {
            addParticipant(p.is_captain, p.email, p.name);
        });
    }

    function redistributeEqually() {
        if (state.participants.length === 0) return;
        var share = Math.round(state.total / state.participants.length);
        var remainder = state.total - (share * state.participants.length);
        state.participants.forEach(function(p, i) {
            p.amount = share + (i === 0 ? remainder : 0);
            $('[data-idx="' + i + '"] .vs08sp-amount').val(p.amount);
        });
        updateTotalBar();
    }

    function updateTotalBar() {
        var sum = 0;
        state.participants.forEach(function(p) { sum += p.amount; });
        $('#vs08sp-sum').text(formatMoney(sum));
        var diff = state.total - sum;
        var $bar = $('#vs08sp-total-bar');
        var $remaining = $('#vs08sp-remaining');
        $bar.removeClass('is-valid is-error');
        if (Math.abs(diff) <= 1) {
            $bar.addClass('is-valid');
            $remaining.text('✅ Répartition valide');
            $('#vs08sp-submit').prop('disabled', false);
        } else if (diff > 0) {
            $bar.addClass('is-error');
            $remaining.text('Reste ' + formatMoney(diff) + ' € à répartir');
            $('#vs08sp-submit').prop('disabled', true);
        } else {
            $bar.addClass('is-error');
            $remaining.text('Excédent de ' + formatMoney(Math.abs(diff)) + ' €');
            $('#vs08sp-submit').prop('disabled', true);
        }
    }

    function calculateMinShare() {
        var nb = state.participants.length || 1;
        var equalShare = state.total / nb;
        var minFromPct = equalShare * 0.30;
        var min = Math.max(minFromPct, state.prixVolPP);
        return Math.ceil(min);
    }

    /**
     * Construit les booking_data depuis BK_DATA et les champs du formulaire.
     */
    function buildBookingData() {
        var total = computeTotal();
        var acomptePct = parseFloat(BK_DATA.acompte_pct) || 30;
        var acompte = Math.ceil(total * acomptePct / 100);

        // Récupérer les voyageurs saisis
        var voyageurs = [];
        for (var i = 0; i < BK_DATA.nb_total; i++) {
            voyageurs.push({
                prenom: $('#v-prenom-' + i).val() || '',
                nom: $('#v-nom-' + i).val() || '',
                ddn: $('#v-ddn-' + i).val() || '',
                passeport: $('#v-passeport-' + i).val() || '',
            });
        }

        return {
            voyage_id: BK_DATA.voyage_id,
            voyage_titre: BK_DATA.titre,
            total: total,
            acompte: acompte,
            payer_tout: BK_DATA.payer_tout,
            params: BK_DATA.params,
            devis: BK_DATA.devis,
            voyageurs: voyageurs,
            facturation: {
                prenom: $('#fact-prenom').val() || '',
                nom: $('#fact-nom').val() || '',
                email: $('#fact-email').val() || '',
                tel: $('#fact-tel').val() || '',
                adresse: $('#fact-adresse').val() || '',
                cp: $('#fact-cp').val() || '',
                ville: $('#fact-ville').val() || '',
            }
        };
    }

    /**
     * Soumission : crée le groupe via l'API REST.
     */
    function submitGroup() {
        var $btn = $('#vs08sp-submit');
        $btn.prop('disabled', true).text('Envoi en cours...');

        // Relire les valeurs depuis le DOM
        state.participants.forEach(function(p, i) {
            p.email = $('[data-idx="' + i + '"] .vs08sp-email').val() || '';
            p.name = $('[data-idx="' + i + '"] .vs08sp-name').val() || '';
            p.amount = parseFloat($('[data-idx="' + i + '"] .vs08sp-amount').val()) || 0;
        });

        // Valider les emails
        for (var i = 0; i < state.participants.length; i++) {
            if (!state.participants[i].email || state.participants[i].email.indexOf('@') === -1) {
                showMessage('Email invalide : ' + (state.participants[i].email || '(vide)'), 'error');
                $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
                return;
            }
        }

        // Vérifier doublons email
        var emails = state.participants.map(function(p) { return p.email.toLowerCase(); });
        var unique = emails.filter(function(e, i) { return emails.indexOf(e) === i; });
        if (unique.length !== emails.length) {
            showMessage('Deux participants ne peuvent pas avoir le même email.', 'error');
            $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
            return;
        }

        var payload = {
            booking_data: buildBookingData(),
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
            success: function(response) {
                if (response.success) {
                    showMessage('✅ ' + response.message, 'success');
                    $btn.text('✅ Liens envoyés !');
                    showLinks(response.shares, response.expires);
                } else {
                    showMessage('❌ ' + response.message, 'error');
                    $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
                }
            },
            error: function() {
                showMessage('Erreur de connexion. Réessayez.', 'error');
                $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
            }
        });
    }

    function showLinks(shares, expires) {
        var html = '<div style="margin-top:20px;padding:20px;background:#edf8f8;border-radius:12px;">';
        html += '<h3 style="margin:0 0 12px;font-family:Playfair Display,serif;font-size:18px;">📤 Liens de paiement envoyés !</h3>';
        html += '<p style="font-size:13px;color:#888;">Expire le ' + expires + '</p>';
        shares.forEach(function(s) {
            var badge = s.is_captain ? ' <span style="background:#c8a45e;color:#fff;padding:2px 6px;border-radius:8px;font-size:10px;">CAPITAINE</span>' : '';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;margin:8px 0;background:#fff;border-radius:6px;border:1px solid #eee;">';
            html += '<div><strong>' + escHtml(s.name || s.email) + '</strong>' + badge + '<br><small style="color:#888;">' + escHtml(s.email) + '</small></div>';
            html += '<div style="text-align:right;"><strong style="color:#0b1120;">' + formatMoney(s.amount) + ' €</strong><br><small style="color:#59b7b7;">📧 Email envoyé</small></div>';
            html += '</div>';
        });
        html += '<p style="font-size:13px;color:#555;margin-top:12px;">Chaque participant a reçu un email avec son lien de paiement. Vous pouvez aussi copier les liens pour les partager par WhatsApp.</p>';
        html += '</div>';
        $('#vs08sp-form .vs08sp-actions').hide();
        $('#vs08sp-form').append(html);
    }

    function showMessage(text, type) {
        var colors = { error: '#e8734a', success: '#27ae60', warn: '#c8a45e' };
        $('#vs08sp-message').html('<span style="color:' + (colors[type] || '#555') + ';">' + text + '</span>');
        if (type !== 'error') setTimeout(function() { $('#vs08sp-message').html(''); }, 5000);
    }

    function formatMoney(amount) { return Math.round(amount).toLocaleString('fr-FR'); }
    function escHtml(s) { return $('<span>').text(s || '').html(); }
    function escAttr(s) { return (s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

})(jQuery);
