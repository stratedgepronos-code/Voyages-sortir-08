/**
 * VS08 SplitPay v2 — Booking integration
 *
 * Ce fichier est volontairement minimal.
 * La logique du tunnel est inline dans booking-steps.php (radio + bkSubmit).
 * La logique de configuration est inline dans class-splitpay-tracker.php (dashboard JS).
 *
 * Ce fichier ne fait plus que :
 *  - Vérifier que vs08sp est chargé (localized data)
 *  - Exposer vs08sp globalement pour les autres scripts
 */
(function() {
    'use strict';

    // Rien à faire si vs08sp n'est pas défini (page sans WooCommerce)
    if (typeof vs08sp === 'undefined') return;

    // Log pour debug
    if (window.console && window.console.log) {
        console.log('[VS08SP] SplitPay v2 chargé — rest_url:', vs08sp.rest_url);
    }

})();
