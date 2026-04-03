(function ($) {
    'use strict';

    // ── Bouton "Générer via IA" dans la meta box
    $(document).on('click', '#vs08seo-gen-btn', function () {
        var $btn = $(this);
        var $loading = $('#vs08seo-loading');
        var $error = $('#vs08seo-error');

        if (!vs08seo.post_id) return;

        $btn.prop('disabled', true).text('⏳ Génération en cours…');
        $loading.css('display', 'flex');
        $error.hide().text('');

        $.post(vs08seo.ajax_url, {
            action:  'vs08_seo_generate',
            nonce:   vs08seo.nonce,
            post_id: vs08seo.post_id
        }, function (res) {
            $btn.prop('disabled', false).text('⚡ Régénérer via IA');
            $loading.hide();

            if (!res.success) {
                $error.show().text('❌ ' + (res.data || 'Erreur inconnue.'));
                return;
            }

            var d = res.data;

            // Remplir les champs
            $('#vs08seo-title').val(d.seo_title || '').trigger('input');
            $('#vs08seo-desc').val(d.seo_desc || '').trigger('input');
            $('#vs08seo-og-title').val(d.og_title || '');
            $('#vs08seo-og-desc').val(d.og_desc || '');
            $('#vs08seo-keywords-input').val(d.keywords || '');
            vs08seoUpdateKeywords(d.keywords || '');

            if (d.faq && Array.isArray(d.faq)) {
                for (var i = 0; i < 3; i++) {
                    var it = d.faq[i];
                    $('#vs08seo-faq-q-' + i).val(it && it.question ? it.question : '');
                    $('#vs08seo-faq-a-' + i).val(it && it.answer ? it.answer : '');
                }
            }

            // Mise à jour prévisualisation
            $('#vs08seo-prev-title').text((d.seo_title || '') + ' | Voyages Sortir 08');
            $('#vs08seo-prev-desc').text(d.seo_desc || '');
            $('#vs08seo-preview').show();

            // Compteurs
            vs08seoCount(document.getElementById('vs08seo-title'), 'vs08seo-chars-title', 58);
            vs08seoCount(document.getElementById('vs08seo-desc'), 'vs08seo-chars-desc', 152);

            // Flash succès
            var $box = $('.vs08seo-box');
            $box.css('transition', 'background .4s').css('background', '#f0fdf4');
            setTimeout(function () { $box.css('background', ''); }, 1200);
        }).fail(function () {
            $btn.prop('disabled', false).text('⚡ Régénérer via IA');
            $loading.hide();
            $error.show().text('❌ Erreur réseau. Vérifiez votre connexion et réessayez.');
        });
    });

})(jQuery);
