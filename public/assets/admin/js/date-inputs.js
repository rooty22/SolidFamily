/**
 * Replaces native <input type="date"> (whose display order follows the browser locale, e.g. mm/dd/yyyy)
 * with a picker that always shows day/month/year while still submitting Y-m-d.
 */
(function () {
    if (!window.flatpickr) return;

    var isRtl = document.documentElement.dir === 'rtl';
    if (isRtl && window.flatpickr.l10ns && window.flatpickr.l10ns.ar) {
        window.flatpickr.localize(window.flatpickr.l10ns.ar);
    }

    function enhance(el) {
        if (el._flatpickr || el.dataset.noPicker !== undefined) return;
        var required = el.required;
        el.required = false;
        var picker = window.flatpickr(el, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
            disableMobile: true,
            minDate: el.getAttribute('min') || null,
            maxDate: el.getAttribute('max') || null,
            appendTo: el.closest('.modal') || undefined,
            locale: { firstDayOfWeek: 6 }
        });
        if (picker.altInput) {
            picker.altInput.required = required;
            picker.altInput.setAttribute('dir', 'ltr');
            picker.altInput.setAttribute('placeholder', 'dd/mm/yyyy');
        }
    }

    function scan(root) {
        (root.matches && root.matches('input[type="date"]') ? [root] : []).concat(
            Array.prototype.slice.call(root.querySelectorAll ? root.querySelectorAll('input[type="date"]') : [])
        ).forEach(enhance);
    }

    document.addEventListener('DOMContentLoaded', function () {
        scan(document);
        new MutationObserver(function (muts) {
            muts.forEach(function (m) {
                m.addedNodes.forEach(function (n) { if (n.nodeType === 1) scan(n); });
            });
        }).observe(document.body, { childList: true, subtree: true });
    });
})();
