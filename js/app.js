// Offline detection and indicator
class OfflineManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.banner = null;
        this.init();
    }

    init() {
        this.createBanner();
        this.attachEventListeners();
        this.updateStatus();
    }

    createBanner() {
        this.banner = document.createElement('div');
        this.banner.className = 'offline-banner';
        this.banner.innerHTML = `
            <i class="bi bi-wifi-off"></i>
            <span class="ms-2">You are offline. Showing cached data.</span>
        `;
        document.body.appendChild(this.banner);
    }

    attachEventListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateStatus();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateStatus();
        });
    }

    updateStatus() {
        if (this.isOnline) {
            this.hideBanner();
        } else {
            this.showBanner();
        }
    }

    showBanner() {
        if (this.banner) {
            this.banner.classList.add('show');
            // Add offline class to main content if it exists
            const main = document.querySelector('main');
            if (main) {
                main.classList.add('offline-content');
            }
        }
    }

    hideBanner() {
        if (this.banner) {
            this.banner.classList.remove('show');
            // Remove offline class from main content
            const main = document.querySelector('main');
            if (main) {
                main.classList.remove('offline-content');
            }
        }
    }

    getStatus() {
        return this.isOnline;
    }
}

// Service Worker update handler
class ServiceWorkerManager {
    constructor() {
        this.registration = null;
        this.init();
    }

    async init() {
        if ('serviceWorker' in navigator) {
            try {
                this.registration = await navigator.serviceWorker.ready;
                this.checkForUpdates();
                
                // Check for updates every 60 minutes
                setInterval(() => this.checkForUpdates(), 60 * 60 * 1000);
            } catch (error) {
                console.error('Service Worker initialization failed:', error);
            }
        }
    }

    async checkForUpdates() {
        if (this.registration) {
            try {
                await this.registration.update();
            } catch (error) {
                console.error('Service Worker update check failed:', error);
            }
        }
    }
}

// Global instances
let offlineManager;
let serviceWorkerManager;

// Initialize the application when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize offline detection
    offlineManager = new OfflineManager();
    
    // Initialize service worker manager
    serviceWorkerManager = new ServiceWorkerManager();
    
    // Make offline manager globally accessible
    window.isOnline = () => offlineManager.getStatus();
});

// Helper function to check if action should be disabled when offline
window.requiresOnline = function(callback) {
    return function(...args) {
        if (!offlineManager || !offlineManager.getStatus()) {
            alert('This action requires an internet connection. Please connect and try again.');
            return;
        }
        return callback.apply(this, args);
    };
}; 