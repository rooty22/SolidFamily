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

    function positionInModal(self, modal) {
        var cal = self.calendarContainer;
        var field = (self.altInput || self.input).getBoundingClientRect();
        var box = modal.getBoundingClientRect();
        var h = cal.offsetHeight || 310;
        var w = cal.offsetWidth || 307;
        var top = field.bottom - box.top + modal.scrollTop + 4;
        if (field.bottom + h + 8 > window.innerHeight && field.top - h - 8 > 0) {
            top = field.top - box.top + modal.scrollTop - h - 4;   // not enough room below: open above the field
        }
        var left = field.left - box.left + modal.scrollLeft;
        left = Math.max(8, Math.min(left, modal.clientWidth - w - 8));
        cal.style.top = top + 'px';
        cal.style.left = left + 'px';
        cal.style.right = 'auto';
    }

    function enhance(el) {
        if (el._flatpickr || el.dataset.noPicker !== undefined) return;
        var required = el.required;
        el.required = false;
        // Inside a popup the calendar lives in the popup (so Bootstrap's focus trap lets the month / year controls
        // work) but flatpickr positions it with the PAGE scroll offset, so on a scrolled page it landed far below the
        // popup. positionInModal() places it from the field's own box instead, flipping above when there is no room.
        var modal = el.closest('.modal');
        var picker = window.flatpickr(el, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
            disableMobile: true,
            minDate: el.getAttribute('min') || null,
            maxDate: el.getAttribute('max') || null,
            appendTo: modal || undefined,
            position: modal ? function (self) { positionInModal(self, modal); } : 'auto',
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
