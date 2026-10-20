'use strict';
const {app, BrowserWindow, ipcMain, Menu, Tray, nativeImage, screen, safeStorage, net, session} = require('electron');
const fs = require('node:fs');
const path = require('node:path');
const {pathToFileURL} = require('node:url');
const {serverUrl, endpoint, monitor, validState} = require('./lib.cjs');

let config = {}, token = '', settingsWindow, displayWindow, tray, timer, busy = false, quitting = false;
let loaded = false, lastState = {active: false, connected: false, order: null}, lastSuccess = 0, nextLoad = 0;
let message = 'Pair the app with your POS register.', previewUntil = 0;
const setupUrl = pathToFileURL(path.join(__dirname, 'setup.html')).href;
const configFile = () => path.join(app.getPath('userData'), 'settings.json');
function saveConfig() {
    if (token && !safeStorage.isEncryptionAvailable()) throw new Error('Windows credential encryption is unavailable.');
    const data = {...config, credential: token ? safeStorage.encryptString(token).toString('base64') : ''};
    fs.mkdirSync(app.getPath('userData'), {recursive: true});
    const temporary = configFile() + '.tmp';
    fs.writeFileSync(temporary, JSON.stringify(data), {mode: 0o600});
    fs.renameSync(temporary, configFile());
}
function readConfig() {
    try {
        const data = JSON.parse(fs.readFileSync(configFile(), 'utf8'));
        config = {server: data.server ? serverUrl(data.server) : '', name: data.name || 'Customer screen',
            monitor: String(data.monitor || 'auto'), allowPrimary: data.allowPrimary === true, startup: data.startup === true};
        if (data.credential && safeStorage.isEncryptionAvailable()) token = safeStorage.decryptString(Buffer.from(data.credential, 'base64'));
    } catch (_) { config = {}; token = ''; }
}
function harden(window, allowedUrl) {
    window.webContents.setWindowOpenHandler(() => ({action: 'deny'}));
    window.webContents.on('will-navigate', (event, url) => { if (url !== allowedUrl()) event.preventDefault(); });
    window.webContents.on('will-redirect', event => event.preventDefault());
    window.webContents.session.setPermissionRequestHandler((_contents, _permission, callback) => callback(false));
    window.webContents.session.setPermissionCheckHandler(() => false);
    window.webContents.on('will-attach-webview', event => event.preventDefault());
}
function openSettings() {
    if (settingsWindow && !settingsWindow.isDestroyed()) { settingsWindow.show(); settingsWindow.focus(); return; }
    settingsWindow = new BrowserWindow({width: 580, height: 780, minWidth: 480, minHeight: 600, title: 'AdomGrace Legacy POS Customer Display',
        autoHideMenuBar: true, backgroundColor: '#f5f3ee', webPreferences: {
            preload: path.join(__dirname, 'preload.cjs'), nodeIntegration: false, contextIsolation: true, sandbox: true
        }});
    harden(settingsWindow, () => setupUrl);
    settingsWindow.on('close', event => { if (!quitting && token) { event.preventDefault(); settingsWindow.hide(); } });
    settingsWindow.loadURL(setupUrl);
}
function chosenMonitor() {
    return monitor(screen.getAllDisplays(), config.monitor, screen.getPrimaryDisplay(), config.allowPrimary);
}
function placeDisplay() {
    if (!displayWindow || displayWindow.isDestroyed()) return;
    const target = chosenMonitor();
    if (!target) { displayWindow.hide(); return; }
    displayWindow.setFullScreen(false);
    displayWindow.setBounds(target.bounds);
    displayWindow.setFullScreen(true);
}
function destroyDisplay() {
    loaded = false;
    if (displayWindow && !displayWindow.isDestroyed()) displayWindow.destroy();
    displayWindow = null;
}
async function loadDisplay() {
    if (!token || !config.server || Date.now() < nextLoad) return;
    nextLoad = Date.now() + 10000;
    if (!displayWindow || displayWindow.isDestroyed()) {
        displayWindow = new BrowserWindow({show: false, frame: false, fullscreen: false, skipTaskbar: true,
            backgroundColor: '#141210', title: 'AdomGrace Legacy POS customer screen', webPreferences: {
                preload: path.join(__dirname, 'display-preload.cjs'), nodeIntegration: false,
                contextIsolation: true, sandbox: true, partition: 'amenador-display'
            }});
        harden(displayWindow, () => config.server ? endpoint(config.server, 'screen') : '');
        displayWindow.on('close', event => { if (!quitting) { event.preventDefault(); displayWindow.hide(); previewUntil = 0; } });
        displayWindow.webContents.on('render-process-gone', () => { destroyDisplay(); });
        displayWindow.webContents.on('did-fail-load', () => { loaded = false; });
        // Credentials never enter the page, its URL, localStorage, or the renderer bridge.
        displayWindow.webContents.session.webRequest.onBeforeSendHeaders((details, callback) => {
            const headers = {...details.requestHeaders};
            delete headers.Authorization;
            if (token && config.server && details.url === endpoint(config.server, 'screen')) headers.Authorization = 'Bearer ' + token;
            callback({requestHeaders: headers});
        });
    }
    try {
        await displayWindow.loadURL(endpoint(config.server, 'screen'));
        loaded = true; placeDisplay(); applyState();
    } catch (_) { loaded = false; }
}
function applyState() {
    if (!displayWindow || displayWindow.isDestroyed() || !loaded) return;
    displayWindow.webContents.send('display:state', lastState);
    const target = chosenMonitor();
    if (target && (lastState.active && lastState.connected || Date.now() < previewUntil)) {
        displayWindow.showInactive();
    } else displayWindow.hide();
}
async function api(action, body, credential = token, base = config.server) {
    const response = await net.fetch(endpoint(base, action), {
        method: body ? 'POST' : 'GET', redirect: 'error', credentials: 'omit', signal: AbortSignal.timeout(8000),
        headers: {Accept: 'application/json', ...(body ? {'Content-Type': 'application/json'} : {}), ...(credential ? {Authorization: 'Bearer ' + credential} : {})},
        ...(body ? {body: JSON.stringify(body)} : {})
    });
    if (!response.ok) {
        const error = new Error(response.status === 422 ? 'Pairing code expired or already used. Generate a fresh code in the POS.' :
            response.status === 401 ? 'Display unpaired. Generate a new code in the POS.' : 'Server returned ' + response.status + '. Check the address and connection.');
        error.status = response.status; throw error;
    }
    return response.json();
}
async function poll() {
    if (busy || !token || !config.server) return;
    busy = true;
    try {
        const state = await api('state');
        if (!validState(state)) throw new Error('The server returned an invalid display response.');
        lastState = state; lastSuccess = Date.now();
        message = !chosenMonitor() ? 'Connect a second monitor, or allow the primary screen below.' :
            state.connected ? (state.active ? 'Connected · displaying the current order.' : 'Connected · display hidden by the POS.') : 'Waiting for this register to open.';
        if (!loaded) await loadDisplay();
        applyState();
    } catch (error) {
        lastState = {active: false, connected: false, order: null};
        message = error.message;
        if (displayWindow && !displayWindow.isDestroyed() && loaded) displayWindow.webContents.send('display:state', lastState);
        if (error.status === 401) {
            token = ''; destroyDisplay(); saveConfig(); openSettings();
        } else if (Date.now() - lastSuccess > 15000 && displayWindow && !displayWindow.isDestroyed()) displayWindow.hide();
    } finally { busy = false; }
}
function trustedSetup(event) {
    if (!settingsWindow || event.sender !== settingsWindow.webContents || event.senderFrame !== settingsWindow.webContents.mainFrame || event.senderFrame.url !== setupUrl) throw new Error('Unauthorized app request.');
}
function publicStatus() {
    return {paired: !!token, server: config.server || '', name: config.name || 'Customer screen', monitor: config.monitor || 'auto',
        allowPrimary: !!config.allowPrimary, startup: !!config.startup, message,
        monitors: screen.getAllDisplays().map(display => ({id: String(display.id), name: display.label || 'Monitor ' + display.id,
            width: display.size.width, height: display.size.height, primary: display.id === screen.getPrimaryDisplay().id}))};
}
if (!app.requestSingleInstanceLock()) app.quit();
else {
    app.on('second-instance', openSettings);
    app.on('window-all-closed', () => {});
    app.on('before-quit', () => { quitting = true; clearInterval(timer); });
    app.whenReady().then(async () => {
        Menu.setApplicationMenu(null); readConfig();
        const icon = nativeImage.createFromPath(path.join(__dirname, 'icon.png')).resize({width: 16, height: 16});
        tray = new Tray(icon); tray.setToolTip('AdomGrace Legacy POS Customer Display');
        tray.setContextMenu(Menu.buildFromTemplate([
            {label: 'Display settings', click: openSettings},
            {label: 'Preview customer screen', click: () => { previewUntil = Date.now() + 20000; applyState(); }},
            {type: 'separator'}, {label: 'Quit', click: () => app.quit()}
        ]));
        tray.on('double-click', openSettings);
        screen.on('display-added', () => { placeDisplay(); applyState(); });
        screen.on('display-removed', () => { placeDisplay(); applyState(); });
        screen.on('display-metrics-changed', () => { placeDisplay(); applyState(); });
        ipcMain.handle('setup:status', event => { trustedSetup(event); return publicStatus(); });
        ipcMain.handle('setup:pair', async (event, data) => {
            trustedSetup(event);
            try {
                if (!data || !/^\d{8}$/.test(data.code || '')) throw new Error('Enter the eight-digit pairing code.');
                if (token) throw new Error('Unpair the current register before pairing another.');
                if (!safeStorage.isEncryptionAvailable()) throw new Error('Windows credential encryption is unavailable.');
                const base = serverUrl(data.server);
                const name = String(data.name || 'Customer screen').trim().slice(0, 80) || 'Customer screen';
                const result = await api('pair', {code: data.code, name}, '', base);
                if (!/^[a-zA-Z0-9]{64}$/.test(result.token || '')) throw new Error('The server did not return a valid device credential.');
                config = {...config, server: base, name}; token = result.token; nextLoad = 0;
                saveConfig(); await poll(); return {ok: true};
            } catch (error) { return {ok: false, error: error.message}; }
        });
        ipcMain.handle('setup:save', (event, data) => {
            trustedSetup(event);
            config = {...config, monitor: String(data.monitor || 'auto'), allowPrimary: data.allowPrimary === true, startup: data.startup === true};
            saveConfig();
            app.setLoginItemSettings({openAtLogin: config.startup, args: ['--background']});
            placeDisplay(); applyState(); return {ok: true};
        });
        ipcMain.handle('setup:unpair', event => {
            trustedSetup(event); token = ''; destroyDisplay(); saveConfig(); message = 'Forgot this register. Unpair the device in the POS to revoke its credential.';
            return {ok: true};
        });
        ipcMain.handle('setup:preview', async event => {
            trustedSetup(event);
            if (!token) return {ok: false, error: 'Pair the app first.'};
            if (!chosenMonitor()) return {ok: false, error: 'Connect a second monitor or allow the primary screen.'};
            previewUntil = Date.now() + 20000;
            if (!loaded) { nextLoad = 0; await loadDisplay(); }
            applyState(); return {ok: true};
        });
        ipcMain.on('display:ready', event => {
            if (displayWindow && event.sender === displayWindow.webContents && event.senderFrame === displayWindow.webContents.mainFrame) {
                event.sender.send('display:state', lastState);
            }
        });
        if (!token || !process.argv.includes('--background')) openSettings();
        await poll(); timer = setInterval(poll, 2000);
    }).catch(error => { console.error(error.message); app.quit(); });
}
