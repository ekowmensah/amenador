'use strict';
const {contextBridge, ipcRenderer} = require('electron');
contextBridge.exposeInMainWorld('amenadorDisplay', {
    onState: callback => {
        if (typeof callback === 'function') ipcRenderer.on('display:state', (_event, state) => callback(state));
    },
    ready: () => ipcRenderer.send('display:ready')
});
