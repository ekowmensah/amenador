(function ($) {
    'use strict';
    $(function () {
        var modal = document.getElementById('display-companion-modal');
        if (!modal) return;
        var register, preferred = false;
        try {
            register = localStorage.getItem('amenador-display-register');
            if (!/^[a-f0-9]{64}$/.test(register || '')) {
                register = Array.from(crypto.getRandomValues(new Uint8Array(32)), function (n) { return n.toString(16).padStart(2, '0'); }).join('');
                localStorage.setItem('amenador-display-register', register);
            }
            preferred = localStorage.getItem('amenador-display-mode') === 'desktop';
        } catch (error) {
            $('#display-companion-status').text('Enable browser storage to pair this register.');
            return;
        }
        var csrf = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val();
        var devices = [], latest = null, busy = false, stopped = false, manual = null;
        var lastSent = '', lastSentAt = 0, changeTimer = null;
        var setting = document.getElementById('customer-display-auto-open');
        var autoOpen = !setting || setting.value === '1';
        var status = $('#display-companion-status');
        var checkbox = $('#display-companion-preferred').prop('checked', preferred);
        function active() { return manual === null ? preferred && autoOpen : manual; }
        function request(kind, data) {
            return $.ajax({url: modal.dataset[kind], method: 'POST', data: JSON.stringify(Object.assign({register: register}, data)),
                contentType: 'application/json', headers: {'X-CSRF-TOKEN': csrf, Accept: 'application/json'}, timeout: 8000});
        }
        function enabled() { document.dispatchEvent(new CustomEvent('pos-companion-enabled', {detail: devices.length > 0})); }
        function error(xhr) {
            status.text(xhr.status === 401 || xhr.status === 419 ? 'Session expired. Reload the POS to reconnect.' :
                xhr.status === 409 ? 'This register is already open in another tab. Close the other POS tab.' : 'Cannot reach the display service. Retrying automatically.');
        }
        function refresh() {
            if (stopped) return;
            return request('status').done(function (data) {
                devices = data.devices; enabled();
                var list = $('#display-companion-devices').empty();
                if (!devices.length) $('<p>').text('No displays paired yet.').appendTo(list);
                devices.forEach(function (device) {
                    var row = $('<p>').css({display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '12px'}).appendTo(list);
                    $('<span>').text(device.name + (device.last_seen_at ? ' · Last connected: ' + device.last_seen_at : ' · Waiting for app')).appendTo(row);
                    $('<button type="button" class="btn btn-default btn-sm">').text('Unpair').appendTo(row).on('click', function () {
                        request('revoke', {device_id: device.id}).done(refresh).fail(error);
                    });
                });
            }).fail(error);
        }
        function publish() {
            if (stopped || busy || !devices.length || !latest) return;
            var signature = JSON.stringify({order: latest, active: active()});
            if (signature === lastSent && Date.now() - lastSentAt < 4000) return;
            busy = true;
            request('publish', {order: latest, active: active(), owner: owner}).done(function () {
                lastSent = signature;
                lastSentAt = Date.now();
                status.text(active() ? 'Sending this register’s order to the desktop app.' : 'Desktop display is hidden.');
            }).fail(error).always(function () {
                busy = false;
                // Send edits made during a request as soon as that request completes.
                if (lastSent === signature && JSON.stringify({order: latest, active: active()}) !== signature) scheduleChange();
            });
        }
        function scheduleChange() {
            if (changeTimer !== null) return;
            changeTimer = setTimeout(function () { changeTimer = null; tick(); }, 80);
        }
        document.addEventListener('pos-customer-snapshot', function (event) {
            var changed = JSON.stringify(latest) !== JSON.stringify(event.detail);
            latest = event.detail;
            if (changed) scheduleChange();
        });
        checkbox.on('change', function () {
            preferred = this.checked; manual = null;
            localStorage.setItem('amenador-display-mode', preferred ? 'desktop' : 'browser');
            document.dispatchEvent(new CustomEvent('pos-companion-mode', {detail: preferred}));
            tick();
        });
        $('#display-companion-code-button').on('click', function () {
            var button = $(this).prop('disabled', true);
            request('code').done(function (data) { $('#display-companion-code').text(data.code + ' · valid for 5 minutes'); })
                .fail(error).always(function () { button.prop('disabled', false); });
        });
        $('#display-companion-show').on('click', function () { manual = true; tick(); });
        $('#display-companion-hide').on('click', function () { manual = false; tick(); });
        $(modal).on('shown.bs.modal', refresh);
        // A local browser lease prevents two tabs from publishing different orders to one register.
        var owner = Array.from(crypto.getRandomValues(new Uint8Array(32)), function (n) { return n.toString(16).padStart(2, '0'); }).join('');
        function tick() {
            if (stopped) return;
            try {
                var lease = JSON.parse(localStorage.getItem('amenador-display-lease') || 'null');
                if (lease && lease.owner !== owner && Date.now() - lease.at < 7000) {
                    status.text('Another POS tab is controlling this register’s desktop display.');
                    return;
                }
                localStorage.setItem('amenador-display-lease', JSON.stringify({owner: owner, at: Date.now()}));
            } catch (e) { return; }
            publish();
        }
        window.addEventListener('pagehide', function () {
            stopped = true;
            clearTimeout(changeTimer); changeTimer = null;
            try {
                var lease = JSON.parse(localStorage.getItem('amenador-display-lease') || 'null');
                if (lease && lease.owner === owner) localStorage.removeItem('amenador-display-lease');
            } catch (e) { /* The server heartbeat also expires automatically. */ }
        });
        window.addEventListener('pageshow', function () { stopped = false; refresh(); });
        refresh();
        setInterval(refresh, 15000);
        setInterval(tick, 2000);
    });
})(jQuery);
