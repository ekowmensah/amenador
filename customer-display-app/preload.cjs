'use strict';
const {contextBridge, ipcRenderer} = require('electron');
contextBridge.exposeInMainWorld('amenadorSetup', {
    status: () => ipcRenderer.invoke('setup:status'),
    pair: data => ipcRenderer.invoke('setup:pair', data),
    save: data => ipcRenderer.invoke('setup:save', data),
    unpair: () => ipcRenderer.invoke('setup:unpair'),
    preview: () => ipcRenderer.invoke('setup:preview')
});
