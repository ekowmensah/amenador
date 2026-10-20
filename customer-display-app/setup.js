'use strict';
const byId = id => document.getElementById(id);
let initialized = false;
async function refresh() {
    const data = await window.amenadorSetup.status();
    byId('status').textContent = data.message;
    byId('pair-form').hidden = data.paired;
    byId('paired').hidden = !data.paired;
    byId('paired-server').textContent = data.name + ' · ' + data.server;
    if (!initialized) {
        byId('server').value = data.server;
        byId('name').value = data.name;
        byId('allow-primary').checked = data.allowPrimary;
        byId('startup').checked = data.startup;
    }
    const select = byId('monitor'), selected = initialized ? select.value : data.monitor;
    select.replaceChildren(new Option('Automatically use second monitor', 'auto'));
    data.monitors.forEach(display => select.add(new Option(display.name + ' · ' + display.width + ' × ' + display.height + (display.primary ? ' · Primary' : ''), display.id)));
    select.value = [...select.options].some(option => option.value === selected) ? selected : 'auto';
    initialized = true;
}
async function run(operation) {
    byId('error').textContent = '';
    try {
        const result = await operation();
        if (!result.ok) byId('error').textContent = result.error;
        await refresh();
    } catch (_) { byId('error').textContent = 'Could not complete the action. Please try again.'; }
}
byId('pair-form').addEventListener('submit', async event => {
    event.preventDefault(); byId('pair').disabled = true;
    await run(() => window.amenadorSetup.pair({server: byId('server').value.trim(), name: byId('name').value.trim(), code: byId('code').value.trim()}));
    byId('code').value = ''; byId('pair').disabled = false;
});
byId('save').addEventListener('click', () => run(() => window.amenadorSetup.save({monitor: byId('monitor').value, allowPrimary: byId('allow-primary').checked, startup: byId('startup').checked})));
byId('preview').addEventListener('click', () => run(() => window.amenadorSetup.preview()));
byId('unpair').addEventListener('click', () => run(() => window.amenadorSetup.unpair()));
refresh(); setInterval(refresh, 2000);
