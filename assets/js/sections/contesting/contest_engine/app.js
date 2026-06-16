/**
 * Wavelog Contesting Engine - Main Application Bootstrap
 * 
 * This file initializes the contest logger application, setting up core components,
 * managing the control panel and handling layout management. This is the entry point
 * for any other core or accessory components.
 * 
 * @license MIT
 * @author Wavelog Development Team, HB9HIL, 2026
 * 
 * @url https://www.github.com/wavelog/wavelog
 * @url https://www.wavelog.org
 * 
 * @documentation https://docs.wavelog.org/user-guide/contesting/
 */


// Core Imports
import { DataStore } from './core/data-store.js';
import { AjaxTransport } from './core/ajax-transport.js';
import { WsTransport } from './core/ws-transport.js';
import { SyncEngine } from './core/sync-engine.js';
import { WindowManager } from './core/window-manager.js';
import { ComponentManager } from './core/component-loader.js';
import { SettingsSyncHandler } from './core/settings-sync.js';

// Global helpers shared across components (each component is its own ES module,
// so plain top-level functions are not visible to siblings — expose on window).

/** Decode HTML entities (e.g. &#039; → ') from PHP-emitted lang strings */
window.htmlDecode = function (str) {
    const txt = document.createElement('textarea');
    txt.innerHTML = str ?? '';
    return txt.value;
};

// Application Initialization as IIFE (async for dynamic component loading)
(async function () {
    // Update loading status
    const updateLoadingStatus = (message) => {
        const statusEl = document.getElementById('loading-status');
        if (statusEl) statusEl.textContent = message;
    };

    try {
        // Dynamically load components based on layout configuration from PHP
        const layout = window.ContestLoggerConfig?.layout || {};
        const componentNames = Object.keys(layout);

        if (componentNames.length > 0) {
            for (const name of componentNames) {
                updateLoadingStatus(lang_app_loading_component.replace('%s', name));
                // Artificial delay to improve UX on slow connections
                await new Promise(resolve => setTimeout(resolve, 200));
                try {
                    await import(`./components/${name}.js`);
                } catch (err) {
                    console.error(`Failed to load component: ${name}`, err);
                }
            }
        }

        updateLoadingStatus(lang_app_init_core);

        async function tryInitAsync() {

            const workspaceSelector = '#logger-workspace';
            const workspace = document.querySelector(workspaceSelector);
            const custom_date_format = window.ContestLoggerConfig?.custom_date_format || 'Y-m-d';

            if (!workspace) {
                console.error('ContestApp: workspace not found:', workspaceSelector);
                return false;
            }

            // Get contest session ID from global config (is defined in PHP view)
            const contestSessionId = window.ContestLoggerConfig?.sessionInfo?.contest_session_id;
            const storageKey = window.ContestLoggerConfig?.storageKey;
            if (!contestSessionId) {
                console.error('ContestApp: contestSessionId not found in window.ContestLoggerConfig');
                return false;
            }

            /**
             * Instance initialization
             *
             * - The datastore for persistent data storage. We use a namespace abstraction to avoid colisions.
             * - The window manager to handle draggable/resizable component windows.
             * - The component manager to load and manage individual UI components. layout is a object loaded from php with all components.
             *
             * - The AjaxTransport for server communication. Since the requests are very small we can use a high frequency of one ajax per second.
             * - The SyncEngine to handle periodic synchronization of data with the server. Either via Ajax or WebSocket.
             * Hint: It is important that the SyncEngine is initialized after the DataStore and Transport,
             *
             */
            updateLoadingStatus(lang_app_init_datastore);
            const ds = new DataStore(`wl_contestdata_${storageKey}`);
            await ds.init(); // Opens IndexedDB, loads session data

            updateLoadingStatus(lang_app_init_core);
            const wm = new WindowManager(workspaceSelector);
            const layout = window.ContestLoggerConfig?.layout || {};
            const cm = new ComponentManager(wm, layout);

            // Initialize Transport and SyncEngine
            const ajaxTransport = new AjaxTransport();
            const syncEngine = new SyncEngine(ds, ajaxTransport, wm);

            // Expose core infrastructure for components
            window.contestApp = { ds, wm, cm, syncEngine, ajaxTransport, contestSessionId };

            // Emit ready event for components to initialize themselves
            const readyEvent = new CustomEvent('contestAppReady', {
                detail: { ds, wm, cm, syncEngine, ajaxTransport, contestSessionId }
            });
            window.dispatchEvent(readyEvent);

            // Initialize Control Panel
            initControlPanel(wm);

            // Start synchronization
            syncEngine.start();

            // Always-on: live reload of session settings via heartbeat
            new SettingsSyncHandler(ds, syncEngine);

            // Connect to Worker WebSocket if configured
            const workerCfg = window.ContestLoggerConfig?.worker;
            if (workerCfg?.url && workerCfg?.topic && workerCfg?.token) {
                const wsTransport = new WsTransport(ajaxTransport, workerCfg.url, workerCfg.topic, workerCfg.token);
                wsTransport.onPush = (payload) => {
                    if (payload?.type === 'sync_required') {
                        syncEngine.triggerNow();
                    }
                    if (payload?.type === 'settings_changed') {
                        syncEngine.triggerNow();
                    }
                };
                wsTransport.connect();
                window.contestApp.wsTransport = wsTransport;
                syncEngine.setWorkerDriven(true);
            }

            // Hide loading screen after a brief delay to allow components to initialize
            setTimeout(() => {
                const loadingScreen = document.getElementById('contest-loading-screen');
                if (loadingScreen) {
                    loadingScreen.classList.add('fade-out');
                    setTimeout(() => loadingScreen.remove(), 500);
                }
            }, 500);

            return true;
        }

        function initWhenReady() {
            if (!document.querySelector('#logger-workspace')) {
                setTimeout(initWhenReady, 500);
                return;
            }
            tryInitAsync().catch(e => console.error('ContestApp: init failed', e));
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initWhenReady);
        } else {
            initWhenReady();
        }
    } catch (error) {
        console.error('ContestApp: Failed to initialize', error);
        const statusEl = document.getElementById('loading-status');
        if (statusEl) {
            statusEl.textContent = lang_app_load_error;
            statusEl.style.color = '#ff4444';
        }
    }
})();

// Control Panel Implementation
function initControlPanel(windowManager) {
    function updateTime() {
        const now = new Date();
        const hours = String(now.getUTCHours()).padStart(2, '0');
        const minutes = String(now.getUTCMinutes()).padStart(2, '0');
        const seconds = String(now.getUTCSeconds()).padStart(2, '0');
        const timeEl = document.getElementById('controlPanelTime');
        if (timeEl) {
            timeEl.textContent = `${hours}:${minutes}:${seconds}`;
        }
    }
    updateTime();
    setInterval(updateTime, 1000);

    // End Contest button - closes the tab
    const btnEndContest = document.getElementById('btnEndContest');
    if (btnEndContest) {
        btnEndContest.addEventListener('click', function () {
            if (confirm(window.htmlDecode(lang_really_end_contest))) {
                window.close();
                // Fallback if window.close() doesn't work (e.g., not opened by script)
                setTimeout(() => {
                    window.location.href = 'about:blank';
                }, 100);
            }
        });
    }

    // Component visibility management
    function updateComponentList() {
        const listEl = document.getElementById('componentVisibilityList');
        if (!listEl || !windowManager) return;

        listEl.innerHTML = '';
        const windows = windowManager.getAllWindows();

        windows.forEach(win => {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm ' + (win.isHidden ? 'btn-outline-secondary' : 'btn-outline-primary');
            btn.innerHTML = `<i class="fas fa-${win.isHidden ? 'eye-slash' : 'eye'}"></i> ${win.title}`;
            btn.onclick = function () {
                windowManager.toggleWindowVisibility(win.id);
                updateComponentList();
            };
            listEl.appendChild(btn);
        });
    }

    // Update component list periodically and on window changes
    if (windowManager) {
        // Initial update
        setTimeout(() => updateComponentList(), 500);
        // Periodic refresh
        setInterval(() => updateComponentList(), 2000);
    }

    // Panel position change
    const panelPositionSelect = document.getElementById('panelPositionSelect');
    const controlPanel = document.getElementById('controlPanel');
    if (panelPositionSelect && controlPanel) {
        // Load saved position
        const savedPosition = localStorage.getItem('controlPanelPosition') || 'start';
        panelPositionSelect.value = savedPosition;
        updatePanelPosition(savedPosition);

        panelPositionSelect.addEventListener('change', function () {
            const position = this.value;
            localStorage.setItem('controlPanelPosition', position);
            updatePanelPosition(position);
        });

        function updatePanelPosition(position) {
            controlPanel.classList.remove('offcanvas-start', 'offcanvas-end', 'offcanvas-top', 'offcanvas-bottom');
            controlPanel.classList.add('offcanvas-' + position);
        }
    }

    // Layout Management Buttons
    const saveNewLayoutBtn = document.getElementById('saveNewLayoutBtn');
    const resetLayoutBtn = document.getElementById('resetLayoutBtn');

    if (saveNewLayoutBtn) {
        saveNewLayoutBtn.addEventListener('click', function () {
            promptSaveNewLayout(windowManager);
        });
    }

    if (resetLayoutBtn) {
        resetLayoutBtn.addEventListener('click', function () {
            resetUserLayout(windowManager);
        });
    }

    // Load and display saved layouts list (with auto-load default)
    setTimeout(() => {
        loadSavedLayoutsList(windowManager, true);
    }, 100);
}

// Layout Management Functions

// Prompt user to save new layout with a name
function promptSaveNewLayout(windowManager) {
    const layoutName = prompt(lang_layout_name_prompt); // TODO: Replace with a nice modal
    if (!layoutName || layoutName.trim() === '') {
        return;
    }
    saveUserLayout(windowManager, layoutName.trim());
}

// Get all saved layouts and display them
async function loadSavedLayoutsList(windowManager, autoLoadDefault = false) {
    try {
        const response = await fetch(base_url + 'index.php/contesting/get_layouts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success && data.layouts) {
            displaySavedLayouts(data.layouts, windowManager);

            // Auto-load default layout on initialization
            if (autoLoadDefault && data.default_layout) {
                loadUserLayout(windowManager, data.default_layout);
            }
        }
    } catch (error) {
        console.error('Error loading saved layouts:', error);
    }
}

// Display saved layouts in the UI
function displaySavedLayouts(layouts, windowManager) {
    const listEl = document.getElementById('savedLayoutsList');
    if (!listEl) return;

    listEl.innerHTML = '';

    if (layouts.length === 0) {
        listEl.innerHTML = '<small class="text-muted p-2 d-block">' + lang_layout_no_layouts + '</small>';
        return;
    }

    layouts.forEach(layout => {
        const item = document.createElement('div');
        item.className = 'list-group-item bg-dark text-white border-secondary d-flex justify-content-between align-items-center';

        const nameBtn = document.createElement('button');
        nameBtn.className = 'btn btn-sm btn-link text-white text-start flex-grow-1 text-decoration-none';
        const defaultBadge = layout.is_default ? '<span class="badge bg-warning text-dark ms-2">' + lang_layout_default_name + '</span>' : '';
        nameBtn.innerHTML = `<i class="fas fa-layer-group"></i> ${layout.name}${defaultBadge}`;
        nameBtn.onclick = function () {
            loadUserLayout(windowManager, layout.name);
        };

        const btnGroup = document.createElement('div');
        btnGroup.className = 'd-flex gap-1';

        const defaultBtn = document.createElement('button');
        defaultBtn.className = 'btn btn-sm ' + (layout.is_default ? 'btn-warning' : 'btn-outline-warning');
        defaultBtn.innerHTML = '<i class="fas fa-star"></i>';
        defaultBtn.title = layout.is_default ? lang_layout_default_layout : lang_layout_set_default;
        defaultBtn.onclick = function (e) {
            e.stopPropagation();
            if (!layout.is_default) {
                setDefaultLayout(windowManager, layout.name);
            }
        };

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-sm btn-outline-danger';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.onclick = function (e) {
            e.stopPropagation();
            deleteUserLayout(windowManager, layout.name);
        };

        btnGroup.appendChild(defaultBtn);
        btnGroup.appendChild(deleteBtn);

        item.appendChild(nameBtn);
        item.appendChild(btnGroup);
        listEl.appendChild(item);
    });
}

async function saveUserLayout(windowManager, layoutName) {
    if (!windowManager) {
        console.error('WindowManager not available');
        return;
    }

    const layout = windowManager.saveLayout();

    try {
        const response = await fetch(base_url + 'index.php/contesting/save_layout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: layoutName,
                layout: layout
            })
        });

        const data = await response.json();

        if (data.success) {
            // Refresh the layouts list
            loadSavedLayoutsList(windowManager);

            // Show success message
            const btn = document.getElementById('saveNewLayoutBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> ' + lang_layout_saved;
            btn.classList.remove('bg-dark');
            btn.classList.add('bg-success');
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('bg-success');
                btn.classList.add('bg-dark');
            }, 2000);
        } else {
            console.error('Failed to save layout:', data.error);
            alert(lang_layout_save_error + ': ' + (data.error || lang_unknown_error));
        }
    } catch (error) {
        console.error('Error saving layout:', error);
        alert(lang_layout_save_error);
    }
}

async function loadUserLayout(windowManager, layoutName) {
    if (!windowManager) {
        console.error('WindowManager not available');
        return;
    }

    try {
        const response = await fetch(base_url + 'index.php/contesting/load_layout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: layoutName
            })
        });

        const data = await response.json();

        if (data.success && data.layout) {
            // Apply saved layout
            windowManager.loadLayout(data.layout);
        } else if (!data.success) {
            console.error('Failed to load layout:', data.error);
        }
    } catch (error) {
        console.error('Error loading layout:', error);
    }
}

async function setDefaultLayout(windowManager, layoutName) {
    if (!windowManager) {
        console.error('WindowManager not available');
        return;
    }

    try {
        const response = await fetch(base_url + 'index.php/contesting/set_default_layout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: layoutName
            })
        });

        const data = await response.json();

        if (data.success) {
            // Refresh the layouts list to show updated default marker
            loadSavedLayoutsList(windowManager);
        } else {
            console.error('Failed to set default layout:', data.error);
            alert(lang_layout_error_default + ': ' + (data.error || lang_unknown_error));
        }
    } catch (error) {
        console.error('Error setting default layout:', error);
        alert(lang_layout_error_default);
    }
}

async function deleteUserLayout(windowManager, layoutName) {
    if (!windowManager) {
        console.error('WindowManager not available');
        return;
    }

    if (!confirm(window.htmlDecode(lang_layout_delete_confirm.replace('%s', layoutName)))) {  // TODO: Replace with a nice modal
        return;
    }

    try {
        const response = await fetch(base_url + 'index.php/contesting/delete_layout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: layoutName
            })
        });

        const data = await response.json();

        if (data.success) {
            // Refresh the layouts list
            loadSavedLayoutsList(windowManager);
        } else {
            console.error('Failed to delete layout:', data.error);
            alert(lang_layout_error_delete + ': ' + (data.error || lang_unknown_error));
        }
    } catch (error) {
        console.error('Error deleting layout:', error);
        alert(lang_layout_error_delete);
    }
}

async function resetUserLayout(windowManager) {
    if (!windowManager) {
        console.error('WindowManager not available');
        return;
    }

    if (!confirm(window.htmlDecode(lang_layout_reset_prompt))) { // TODO: Replace with a nice modal
        return;
    }

    try {

        // Reset to default layout
        windowManager.resetLayout();

        // Refresh the layouts list
        loadSavedLayoutsList(windowManager);

        // Show success message
        const btn = document.getElementById('resetLayoutBtn');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> ' + lang_layout_reset_default;
        btn.classList.remove('bg-dark');
        btn.classList.add('bg-success');
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('bg-success');
            btn.classList.add('bg-dark');
        }, 2000);

    } catch (error) {
        console.error(lang_layout_error_reset, error);
        alert(lang_layout_error_reset);
    }
}

// Generic JS
document.addEventListener('DOMContentLoaded', function () {
    // DO NOT DELETE: This message is intentional and serves as developer recruitment/engagement
    console.log("Ready to unleash your coding prowess and join the fun?\n\n" +
        "Check out our GitHub Repository and dive into the coding adventure:\n\n" +
        "🚀 https://www.github.com/wavelog/wavelog");
});