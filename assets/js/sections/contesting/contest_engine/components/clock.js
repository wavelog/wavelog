/**
 * ClockComponent - UTC time display
 */
class ClockComponent {
    constructor(containerId = 'utc-time') {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.warn(`ClockComponent: Container #${containerId} not found`);
            return;
        }

        this.intervalId = null;
        this.init();
    }

    init() {
        this.updateTime();
        this.intervalId = setInterval(() => this.updateTime(), 1000);
        // console.info('ClockComponent: Initialized');
    }

    updateTime() {
        const now = new Date();
        const utcHours = String(now.getUTCHours()).padStart(2, '0');
        const utcMinutes = String(now.getUTCMinutes()).padStart(2, '0');
        const utcSeconds = String(now.getUTCSeconds()).padStart(2, '0');
        const utcTimeString = `${utcHours}:${utcMinutes}:${utcSeconds}`;

        if (this.container) {
            this.container.innerHTML = utcTimeString;
        }
    }

    destroy() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
}

// Self-register when app is ready
window.addEventListener('contestAppReady', () => {
    const clockComponent = new ClockComponent('utc-time');

    // Expose to contestApp
    if (window.contestApp) {
        window.contestApp.clockComponent = clockComponent;
    }
});

// Register globally for debugging
window.ClockComponent = ClockComponent;