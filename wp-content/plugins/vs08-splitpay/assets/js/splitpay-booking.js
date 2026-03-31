/**
 * VS08 SplitPay — Booking Form JS
 *
 * Ce script s'insère dans le tunnel de réservation (booking-steps.php).
 * Il ajoute une section "Payer à plusieurs" après le récap du devis.
 *
 * Fonctionnement :
 *  1. Le capitaine coche "Payer à plusieurs"
 *  2. Un formulaire apparaît : nombre de participants + emails + montants
 *  3. Le montant est réparti équitablement par défaut, modifiable librement
 *  4. Un minimum est imposé (30% du partage équitable ou prix vol/pers)
 *  5. Le bouton "Envoyer les liens" appelle l'API REST et crée le groupe
 */
(function($) {
    'use strict';

    // Attendre que le DOM soit prêt + que les données du devis soient disponibles
    $(document).ready(function() {
        // On vérifie que le tunnel de réservation est présent
        if (!document.querySelector('.vs08v-step, .vs08v-booking-steps, .vs08s-booking')) return;

        // On observe les changements dans le DOM pour injecter le formulaire
        // au bon moment (après le calcul du devis)
        var observer = new MutationObserver(function(mutations) {
            var recapSection = document.querySelector('.vs08v-recap-final, .vs08v-step-recap, .vs08s-step-recap, [data-step="recap"]');
            if (recapSection && !document.querySelector('.vs08sp-split-section')) {
                injectSplitForm(recapSection);
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });

        // Vérifier aussi immédiatement
        setTimeout(function() {
            var recapSection = document.querySelector('.vs08v-recap-final, .vs08v-step-recap, .vs08s-step-recap, [data-step="recap"]');
            if (recapSection && !document.querySelector('.vs08sp-split-section')) {
                injectSplitForm(recapSection);
            }
        }, 2000);
    });

    // ── State ──
    var state = {
        enabled: false,
        participants: [],
        total: 0,
        minShare: 0,
        prixVolPP: 0,
        maxParticipants: parseInt(vs08sp.max_participants) || 10,
    };

    /**
     * Injecte le formulaire de paiement partagé après la section récap.
     */
    function injectSplitForm(recapSection) {
        // Essayer de récupérer le total du devis depuis le DOM
        var totalEl = recapSection.querySelector('.vs08v-total-amount, .vs08v-total-prix, [data-total]');
        if (!totalEl) {
            // Chercher plus largement
            totalEl = document.querySelector('[data-vs08-total], .vs08v-recap-total .amount, .vs08v-total');
        }

        // Fallback : chercher dans les données JS globales
        if (window.vs08v_booking_data && window.vs08v_booking_data.total) {
            state.total = parseFloat(window.vs08v_booking_data.total);
        } else if (totalEl) {
            var text = totalEl.textContent.replace(/[^\d,\.]/g, '').replace(',', '.');
            state.total = parseFloat(text) || 0;
        }

        if (window.vs08v_booking_data && window.vs08v_booking_data.prix_vol) {
            state.prixVolPP = parseFloat(window.vs08v_booking_data.prix_vol);
        }

        // Créer le HTML du formulaire
        var html = '<div class="vs08sp-split-section" style="display:none;" id="vs08sp-form">';
        html += '<div class="vs08sp-split-title">👥 Répartir le paiement</div>';
        html += '<div id="vs08sp-participants"></div>';
        html += '<div class="vs08sp-total-bar" id="vs08sp-total-bar">';
        html += '<span>Total réparti : <strong id="vs08sp-sum">0</strong> / <strong id="vs08sp-target">' + formatMoney(state.total) + '</strong> €</span>';
        html += '<span class="vs08sp-total-remaining" id="vs08sp-remaining"></span>';
        html += '</div>';
        html += '<div class="vs08sp-actions">';
        html += '<button type="button" class="vs08sp-add-btn" id="vs08sp-add">+ Ajouter un participant</button>';
        html += '<button type="button" class="vs08sp-submit-btn" id="vs08sp-submit" disabled>📤 Envoyer les liens de paiement</button>';
        html += '</div>';
        html += '<div id="vs08sp-message" style="margin-top:12px;text-align:center;font-size:14px;"></div>';
        html += '</div>';

        // Ajouter la checkbox avant le bouton de paiement principal
        var payBtn = document.querySelector('.vs08v-btn-payer, .vs08v-btn-checkout, .vs08s-btn-payer, [data-action="checkout"]');
        var insertTarget = payBtn ? payBtn.parentNode : recapSection;

        // Checkbox toggle
        var checkboxHtml = '<div class="vs08sp-toggle" style="margin:20px 0;padding:16px;background:#f8f5f0;border-radius:8px;border:1px solid #ddd;">';
        checkboxHtml += '<label style="display:flex;align-items:center;gap:12px;cursor:pointer;font-size:15px;color:#0b1120;">';
        checkboxHtml += '<input type="checkbox" id="vs08sp-enable" style="width:20px;height:20px;accent-color:#59b7b7;">';
        checkboxHtml += '<span><strong>👥 Payer à plusieurs</strong> — Chaque participant reçoit son propre lien de paiement</span>';
        checkboxHtml += '</label>';
        checkboxHtml += '</div>';

        insertTarget.insertAdjacentHTML('beforebegin', checkboxHtml + html);

        // Events
        $('#vs08sp-enable').on('change', function() {
            state.enabled = this.checked;
            if (state.enabled) {
                // Lire le total à nouveau (il a pu changer)
                refreshTotal();
                if (state.participants.length === 0) {
                    // Ajouter le capitaine + 1 participant par défaut
                    addParticipant(true);  // capitaine
                    addParticipant(false); // participant 1
                }
                $('#vs08sp-form').slideDown(300);
                // Cacher le bouton de paiement normal
                if (payBtn) $(payBtn).hide();
            } else {
                $('#vs08sp-form').slideUp(300);
                if (payBtn) $(payBtn).show();
            }
        });

        $('#vs08sp-add').on('click', function() {
            if (state.participants.length >= state.maxParticipants) {
                showMessage('Maximum ' + state.maxParticipants + ' participants.', 'error');
                return;
            }
            addParticipant(false);
            redistributeEqually();
        });

        $('#vs08sp-submit').on('click', submitGroup);
    }

    /**
     * Ajoute une ligne participant dans le formulaire.
     */
    function addParticipant(isCaptain) {
        var idx = state.participants.length;
        var equalShare = state.total / (idx + 1);

        state.participants.push({
            email: '',
            name: '',
            amount: equalShare,
            is_captain: isCaptain
        });

        var label = isCaptain ? 'Vous (capitaine)' : 'Participant ' + idx;
        var html = '<div class="vs08sp-participant-row' + (isCaptain ? ' is-captain' : '') + '" data-idx="' + idx + '">';
        if (isCaptain) {
            html += '<span class="vs08sp-captain-badge">Capitaine</span>';
        }
        html += '<input type="text" placeholder="Prénom / Nom" class="vs08sp-name" data-idx="' + idx + '">';
        html += '<input type="email" placeholder="Email" class="vs08sp-email" data-idx="' + idx + '">';
        html += '<input type="number" step="1" min="0" class="vs08sp-amount" data-idx="' + idx + '" value="' + Math.round(equalShare) + '"> €';
        if (!isCaptain && idx > 1) {
            html += '<button type="button" class="vs08sp-remove-btn" data-idx="' + idx + '">✕</button>';
        }
        html += '</div>';

        $('#vs08sp-participants').append(html);

        // Events sur les inputs
        var $row = $('[data-idx="' + idx + '"].vs08sp-participant-row');
        $row.find('.vs08sp-name').on('input', function() {
            state.participants[idx].name = this.value;
        });
        $row.find('.vs08sp-email').on('input', function() {
            state.participants[idx].email = this.value;
        });
        $row.find('.vs08sp-amount').on('input', function() {
            var val = parseFloat(this.value) || 0;
            var min = calculateMinShare();
            if (val < min) {
                this.value = min;
                val = min;
                showMessage(vs08sp.i18n.min_amount.replace('%s', min), 'warn');
            }
            state.participants[idx].amount = val;
            updateTotalBar();
        });
        $row.find('.vs08sp-remove-btn').on('click', function() {
            removeParticipant(idx);
        });

        redistributeEqually();
    }

    /**
     * Supprime un participant.
     */
    function removeParticipant(idx) {
        state.participants.splice(idx, 1);
        renderParticipants();
        redistributeEqually();
    }

    /**
     * Re-render toutes les lignes (après suppression).
     */
    function renderParticipants() {
        $('#vs08sp-participants').empty();
        var copy = state.participants.slice();
        state.participants = [];
        copy.forEach(function(p, i) {
            addParticipant(p.is_captain);
            state.participants[i].email = p.email;
            state.participants[i].name = p.name;
            $('[data-idx="' + i + '"].vs08sp-participant-row .vs08sp-email').val(p.email);
            $('[data-idx="' + i + '"].vs08sp-participant-row .vs08sp-name').val(p.name);
        });
    }

    /**
     * Répartit équitablement entre tous les participants.
     */
    function redistributeEqually() {
        if (state.participants.length === 0) return;
        var share = Math.round(state.total / state.participants.length);
        var remainder = state.total - (share * state.participants.length);

        state.participants.forEach(function(p, i) {
            p.amount = share + (i === 0 ? remainder : 0); // Le capitaine prend les centimes restants
            $('[data-idx="' + i + '"] .vs08sp-amount').val(p.amount);
        });

        updateTotalBar();
    }

    /**
     * Met à jour la barre de total.
     */
    function updateTotalBar() {
        var sum = 0;
        state.participants.forEach(function(p) { sum += p.amount; });

        $('#vs08sp-sum').text(formatMoney(sum));
        $('#vs08sp-target').text(formatMoney(state.total));

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

    /**
     * Calcule le montant minimum par participant.
     */
    function calculateMinShare() {
        var nb = state.participants.length || 1;
        var equalShare = state.total / nb;
        var minFromPct = equalShare * 0.30;
        var min = Math.max(minFromPct, state.prixVolPP);
        return Math.ceil(min);
    }

    /**
     * Relire le total depuis le DOM (au cas où il aurait changé).
     */
    function refreshTotal() {
        if (window.vs08v_booking_data && window.vs08v_booking_data.total) {
            state.total = parseFloat(window.vs08v_booking_data.total);
        }
        state.minShare = calculateMinShare();
        $('#vs08sp-target').text(formatMoney(state.total));
    }

    /**
     * Soumission du groupe.
     */
    function submitGroup() {
        var $btn = $('#vs08sp-submit');
        $btn.prop('disabled', true).text(vs08sp.i18n.sending);

        // Validation des emails
        for (var i = 0; i < state.participants.length; i++) {
            var p = state.participants[i];
            // Lire depuis le DOM (plus fiable)
            p.email = $('[data-idx="' + i + '"] .vs08sp-email').val() || '';
            p.name = $('[data-idx="' + i + '"] .vs08sp-name').val() || '';
            p.amount = parseFloat($('[data-idx="' + i + '"] .vs08sp-amount').val()) || 0;

            if (!p.email || !p.email.includes('@')) {
                showMessage(vs08sp.i18n.email_invalid.replace('%s', p.email), 'error');
                $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
                return;
            }
        }

        // Construire les booking_data depuis les données du formulaire de réservation
        var bookingData = window.vs08v_booking_data || {};

        // Si pas de données globales, essayer de lire les champs du formulaire
        if (!bookingData.voyage_id) {
            var voyageInput = document.querySelector('input[name="voyage_id"]');
            if (voyageInput) bookingData.voyage_id = voyageInput.value;
        }

        var payload = {
            booking_data: bookingData,
            participants: state.participants.map(function(p) {
                return {
                    email: p.email,
                    name: p.name,
                    amount: p.amount,
                    is_captain: p.is_captain
                };
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
                    // Afficher les liens générés
                    showLinks(response.shares, response.expires);
                } else {
                    showMessage('❌ ' + response.message, 'error');
                    $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
                }
            },
            error: function() {
                showMessage(vs08sp.i18n.error, 'error');
                $btn.prop('disabled', false).text('📤 Envoyer les liens de paiement');
            }
        });
    }

    /**
     * Affiche les liens de paiement générés (confirmation).
     */
    function showLinks(shares, expires) {
        var html = '<div style="margin-top:20px;padding:20px;background:#edf8f8;border-radius:12px;">';
        html += '<h3 style="margin:0 0 12px;font-family:Playfair Display,serif;font-size:18px;">📤 Liens de paiement envoyés</h3>';
        html += '<p style="font-size:13px;color:#888;">Expire le ' + expires + '</p>';

        shares.forEach(function(s) {
            var badge = s.is_captain ? ' <span style="background:#c8a45e;color:#fff;padding:2px 6px;border-radius:8px;font-size:10px;">CAPITAINE</span>' : '';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;margin:8px 0;background:#fff;border-radius:6px;border:1px solid #eee;">';
            html += '<div><strong>' + (s.name || s.email) + '</strong>' + badge + '<br><small style="color:#888;">' + s.email + '</small></div>';
            html += '<div style="text-align:right;"><strong style="color:#0b1120;">' + formatMoney(s.amount) + ' €</strong><br><small style="color:#59b7b7;">📧 Email envoyé</small></div>';
            html += '</div>';
        });

        html += '<p style="font-size:13px;color:#555;margin-top:12px;">Chaque participant a reçu un email avec son lien de paiement personnel. Vous pouvez aussi copier les liens ci-dessus pour les partager par WhatsApp.</p>';
        html += '</div>';

        $('#vs08sp-form .vs08sp-actions').hide();
        $('#vs08sp-form').append(html);
    }

    /**
     * Affiche un message dans le formulaire.
     */
    function showMessage(text, type) {
        var colors = { error: '#e8734a', success: '#27ae60', warn: '#c8a45e' };
        var color = colors[type] || '#555';
        $('#vs08sp-message').html('<span style="color:' + color + ';">' + text + '</span>');
        if (type !== 'error') {
            setTimeout(function() { $('#vs08sp-message').html(''); }, 5000);
        }
    }

    /**
     * Formate un nombre en monétaire.
     */
    function formatMoney(amount) {
        return Math.round(amount).toLocaleString('fr-FR');
    }

})(jQuery);
