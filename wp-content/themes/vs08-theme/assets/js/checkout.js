(function(){
    function addPaymentTitle() {
        var ul = document.querySelector('.woocommerce-checkout-payment .wc_payment_methods');
        if (ul && !document.querySelector('.vs08-payment-title')) {
            var title = document.createElement('div');
            title.className = 'vs08-payment-title';
            title.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Moyen de paiement';
            ul.parentNode.insertBefore(title, ul);
        }
    }
    function renamePayboxLabels() {
        var labels = document.querySelectorAll('.woocommerce-checkout-payment .payment_methods li.wc_payment_method > label');
        labels.forEach(function(label) {
            var nodes = label.childNodes;
            for (var i = 0; i < nodes.length; i++) {
                if (nodes[i].nodeType === 3) {
                    var txt = nodes[i].textContent.trim().toLowerCase();
                    if (txt.indexOf('paybox payment') !== -1) {
                        nodes[i].textContent = ' Carte bancaire ';
                    }
                    if (txt.indexOf('3 times') !== -1) {
                        nodes[i].textContent = ' Payer en 3x sans frais ';
                    }
                }
            }
        });
    }
    function hidePaybox3x() {
        var items = document.querySelectorAll('li.payment_method_paybox_3x, li.wc_payment_method.payment_method_paybox_3x');
        for (var i = 0; i < items.length; i++) {
            items[i].style.cssText = 'display:none!important;visibility:hidden!important;height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;';
            items[i].setAttribute('hidden', '');
        }
        var liStd = document.querySelector('li.payment_method_paybox_std');
        if (liStd) {
            liStd.style.flex = '1 1 100%';
            liStd.style.maxWidth = '100%';
            liStd.style.width = '100%';
        }
    }
    function hideRecapPriceDetail() {
        var recap = document.querySelector('.vs08v-checkout-recap-card');
        if (!recap) return;
        var allElements = recap.querySelectorAll('*');
        var found = false;
        for (var i = 0; i < allElements.length; i++) {
            var el = allElements[i];
            if (!found) {
                var txt = el.textContent || '';
                if ((el.tagName === 'H4' || el.tagName === 'H3' || el.tagName === 'STRONG' || el.tagName === 'B' || el.tagName === 'P') && txt.indexOf('tail du prix') !== -1) {
                    found = true;
                    el.style.display = 'none';
                }
            } else {
                if (el.tagName === 'TABLE' || el.tagName === 'H4' || el.tagName === 'H3' || el.tagName === 'HR' || el.tagName === 'STRONG') {
                    el.style.display = 'none';
                }
            }
        }
    }
    function fixRecapDetails() {
        var recap = document.querySelector('.vs08v-checkout-recap-card');
        if (!recap) return;
        var tds = recap.querySelectorAll('td');
        for (var i = 0; i < tds.length; i++) {
            var txt = tds[i].textContent;
            var match = txt.match(/(\d+)\s*nuits?\s*\/\s*(\d+)\s*jours?/i);
            if (match) {
                tds[i].textContent = match[2] + ' jours / ' + match[1] + ' nuits';
            }
        }
    }
    function fixProductName() {
        var names = document.querySelectorAll('.shop_table .cart_item .product-name, .woocommerce-checkout-review-order-table .cart_item .product-name');
        for (var i = 0; i < names.length; i++) {
            var el = names[i];
            if (el.getAttribute('data-vs08-fixed')) continue;
            var html = el.innerHTML;
            html = html.replace(/\s*[^\w\s]\s*\d{1,2}\/\d{1,2}\/\d{2,4}/g, '');
            el.innerHTML = html;
            el.setAttribute('data-vs08-fixed', '1');
        }
    }
    function runAll() {
        addPaymentTitle();
        renamePayboxLabels();
        hidePaybox3x();
        hideRecapPriceDetail();
        fixRecapDetails();
        fixProductName();
    }
    runAll();
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('updated_checkout', function() {
            setTimeout(runAll, 200);
        });
    }
})();
