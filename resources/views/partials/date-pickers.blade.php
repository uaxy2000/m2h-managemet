<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script>
// Native date pickers follow the browser's locale for the first weekday, so every
// date/datetime-local input is upgraded to flatpickr with Monday as the first day.
(function () {
    flatpickr.l10ns.default.firstDayOfWeek = 1;

    var valueProp = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

    function enhance(el) {
        if (el._flatpickr || el.dataset.nativePicker !== undefined) return;
        var withTime = el.type === 'datetime-local';

        var fp = flatpickr(el, {
            enableTime: withTime,
            time_24hr: true,
            minuteIncrement: 5,
            dateFormat: withTime ? 'Y-m-d\\TH:i' : 'Y-m-d',
            altInput: true,
            altFormat: withTime ? 'd M Y, H:i' : 'd M Y',
            minDate: el.getAttribute('min') || null,
            maxDate: el.getAttribute('max') || null,
            disableMobile: true,
        });

        // Keep the visible picker in sync when Alpine or other scripts set the value directly.
        Object.defineProperty(el, 'value', {
            configurable: true,
            get: function () { return valueProp.get.call(this); },
            set: function (v) {
                var old = valueProp.get.call(this);
                valueProp.set.call(this, v);
                if (v !== old && fp.input === this) fp.setDate(v || null, false);
            },
        });
    }

    function scan(root) {
        if (root.nodeType !== 1) return;
        if (root.matches('input[type="date"], input[type="datetime-local"]')) enhance(root);
        root.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(enhance);
    }

    function cleanup(root) {
        if (root.nodeType !== 1) return;
        [root].concat(Array.from(root.querySelectorAll('input'))).forEach(function (el) {
            if (!el._flatpickr) return;
            el._flatpickr.destroy();
            delete el.value;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        scan(document.body);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(scan);
                m.removedNodes.forEach(cleanup);
            });
        }).observe(document.body, { childList: true, subtree: true });
    });
})();
</script>
