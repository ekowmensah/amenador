(() => {
    'use strict';
    document.querySelectorAll('[data-copy-tracking]').forEach(button => {
        button.addEventListener('click', async () => {
            const code = button.dataset.copyTracking;
            const note = document.querySelector('[data-copy-status]');
            try {
                if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(code);
                else {
                    const input = document.createElement('textarea');
                    input.value = code; input.style.position = 'fixed'; input.style.opacity = '0';
                    document.body.appendChild(input); input.select();
                    const copied = document.execCommand('copy'); input.remove();
                    if (!copied) throw new Error('Copy unavailable');
                }
                if (note) note.textContent = 'Order code copied.';
            } catch (_) { if (note) note.textContent = 'Select the order code above to copy it.'; }
        });
    });
    const result = document.getElementById('sf-tracking-result');
    if (!result) return;
    const note = result.querySelector('[data-tracking-refresh]');
    const button = result.querySelector('[data-tracking-refresh-button]');
    let busy = false;
    let expired = false;
    let lastHeading = result.querySelector('[data-tracking-heading]').textContent;
    async function refresh() {
        if (busy || expired || document.visibilityState !== 'visible') return;
        busy = true; button.disabled = true;
        try {
            const response = await fetch(result.dataset.statusUrl, {credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/json'}, signal: AbortSignal.timeout(15000)});
            if (response.status === 401) {
                expired = true;
                throw new Error('Enter your code and phone number again to continue tracking.');
            }
            if (!response.ok) throw new Error('Could not refresh right now. Please try again.');
            const state = await response.json();
            result.querySelector('[data-tracking-heading]').textContent = state.heading;
            result.querySelector('[data-tracking-description]').textContent = state.description;
            result.querySelector('[data-tracking-payment]').textContent = state.payment;
            const list = result.querySelector('[data-tracking-steps]');
            list.replaceChildren(...state.steps.map((step, index) => {
                const row = document.createElement('li');
                row.className = `sf-step-${step.state}`;
                if (step.state === 'current') row.setAttribute('aria-current', 'step');
                const marker = document.createElement('span'); marker.className = 'sf-step-marker'; marker.setAttribute('aria-hidden', 'true'); marker.textContent = step.state === 'done' ? '✓' : index + 1;
                const title = document.createElement('span'); title.textContent = step.label;
                const status = document.createElement('small'); status.textContent = step.state === 'done' ? 'Complete' : step.state === 'current' ? 'In progress' : 'Next';
                row.append(marker, title, status); return row;
            }));
            note.textContent = (lastHeading !== state.heading ? `Order updated: ${state.heading}. ` : '') + 'Checked just now. Updates automatically every 30 seconds.';
            lastHeading = state.heading;
        } catch (error) {
            note.textContent = error.name === 'TimeoutError' ? 'The refresh timed out. Please try again.' : error.message;
        } finally { busy = false; button.disabled = expired; }
    }
    button.addEventListener('click', refresh);
    setInterval(refresh, 30000);
    document.addEventListener('visibilitychange', refresh);
})();
