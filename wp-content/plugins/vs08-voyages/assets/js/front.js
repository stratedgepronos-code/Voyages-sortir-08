/**
 * VS08 Voyages — front.js (GOLD v2.0.0)
 * Expose l'objet global VS08V (majuscules) utilisé par les templates inline.
 * Gère : recherche de vols, calcul de prix, spinner de chargement.
 */

(function($) {
    'use strict';

    // ============================================================
    // EXPOSITION GLOBALE : VS08V (majuscules)
    // wp_localize_script injecte "vs08v" (minuscules),
    // les templates utilisent "VS08V" (majuscules) → on fait le pont.
    // ============================================================
    if (typeof vs08v !== 'undefined') {
        window.VS08V = {
            ajax_url : vs08v.ajax_url,
            nonce    : vs08v.nonce,
        };
    } else {
        // Fallback de sécurité si le localize ne s'est pas chargé
        window.VS08V = {
            ajax_url : '/wp-admin/admin-ajax.php',
            nonce    : '',
        };
        console.warn('[VS08] vs08v non défini — vérifiez wp_localize_script dans vs08-voyages.php');
    }

    // ============================================================
    // HELPERS GLOBAUX — utilisables depuis les templates inline
    // ============================================================

    /**
     * Affiche / masque le spinner de chargement dans un conteneur
     * @param {string} selector - ex: '#vs08v-vols-result'
     * @param {string} message  - texte affiché sous le spinner
     */
    window.VS08V.showSpinner = function(selector, message) {
        message = message || 'Chargement en cours…';
        $(selector).html(
            '<div class="vs08v-spinner-wrap">' +
            '<div class="vs08v-spinner"></div>' +
            '<p class="vs08v-spinner-msg">' + message + '</p>' +
            '</div>'
        );
    };

    /**
     * Affiche une erreur dans un conteneur
     * @param {string} selector
     * @param {string} msg
     */
    window.VS08V.showError = function(selector, msg) {
        $(selector).html(
            '<div class="vs08v-alert vs08v-alert-error">' +
            '<span class="vs08v-alert-icon">⚠️</span>' +
            '<span>' + msg + '</span>' +
            '</div>'
        );
    };

    /**
     * Affiche un succès dans un conteneur
     * @param {string} selector
     * @param {string} msg
     */
    window.VS08V.showSuccess = function(selector, msg) {
        $(selector).html(
            '<div class="vs08v-alert vs08v-alert-success">' +
            '<span class="vs08v-alert-icon">✅</span>' +
            '<span>' + msg + '</span>' +
            '</div>'
        );
    };

    /**
     * Formate un prix en euros : 1234.5 → "1 234,50 €"
     * @param {number} amount
     * @return {string}
     */
    window.VS08V.formatPrice = function(amount) {
        return parseFloat(amount).toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' €';
    };

    /**
     * Recherche les vols via AJAX Duffel
     * @param {object} params - { voyage_id, date, aeroport, passengers }
     * @param {function} onSuccess - callback(data)
     * @param {function} onError   - callback(msg)
     */
    window.VS08V.fetchVols = function(params, onSuccess, onError) {
        $.ajax({
            url    : VS08V.ajax_url,
            method : 'POST',
            data   : $.extend({ action: 'vs08v_get_flight', nonce: VS08V.nonce }, params),
            success: function(resp) {
                if (resp.success) {
                    onSuccess(resp.data);
                } else {
                    onError(resp.data || 'Aucun vol trouvé pour cette sélection.');
                }
            },
            error: function() {
                onError('Erreur de connexion. Veuillez réessayer.');
            }
        });
    };

    /**
     * Calcule le prix du séjour via AJAX
     * @param {object} params - { voyage_id, nb_golfeurs, nb_nongolfeurs, ... }
     * @param {function} onSuccess - callback(data)
     * @param {function} onError   - callback(msg)
     */
    window.VS08V.calculate = function(params, onSuccess, onError) {
        $.ajax({
            url    : VS08V.ajax_url,
            method : 'POST',
            data   : $.extend({ action: 'vs08v_calculate', nonce: VS08V.nonce }, params),
            success: function(resp) {
                if (resp.success) {
                    onSuccess(resp.data);
                } else {
                    onError(resp.data || 'Erreur lors du calcul du prix.');
                }
            },
            error: function() {
                onError('Erreur de connexion. Veuillez réessayer.');
            }
        });
    };

    // ============================================================
    // INIT — Log de confirmation au chargement
    // ============================================================
    $(function() {
        if (typeof window.VS08V !== 'undefined') {
            console.log('[VS08 Voyages] ✅ front.js chargé — VS08V disponible');
        }
    });

})(jQuery);
