<!DOCTYPE html>
<?php
require_once '../include/getApiKey.php';
$hasKey = hasApiKey();
?>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        /* PWA Install Banner Styles */
        #pwaInstallBanner {
            position: fixed;
            bottom: calc(70px + env(safe-area-inset-bottom)); /* Position above bottom nav */
            left: 0;
            right: 0;
            background-color: var(--bs-dark);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            z-index: 1000;
            display: none;
            transform: translateY(100%);
            transition: transform 0.3s ease-in-out;
        }

        #pwaInstallBanner.show {
            transform: translateY(0);
            display: block;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Slide Mobile</span>
            <?php if (hasApiKey()): ?>
                <a href="key-management.php" class="btn btn-link text-white">
                    <i class="bi bi-key"></i>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1 d-flex flex-column align-items-center pt-4">
        <h1 class="display-4 mb-5">Welcome</h1>
        
        <?php if (!$hasKey): ?>
        <div class="setup-options text-center">
            <p class="mb-4">Please set up your API key to continue</p>
            <div class="d-grid gap-3">
                <a href="key-management.php" class="btn btn-primary">
                    <i class="bi bi-key-fill me-2"></i>Set Up API Key
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="container px-4">
            <div class="row g-4">
                <!-- Agents Card -->
                <div class="col-6 col-md-3">
                    <a href="/mobile/agents.php" class="text-decoration-none">
                        <div class="card bg-dark h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-pc mb-2" style="font-size: 1.5rem;"></i>
                                <h3 class="card-title h2 mb-0" id="agentCount">-</h3>
                                <div class="text-muted">Agents</div>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Devices Card -->
                <div class="col-6 col-md-3">
                    <a href="/mobile/devices.php" class="text-decoration-none">
                        <div class="card bg-dark h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-hdd mb-2" style="font-size: 1.5rem;"></i>
                                <h3 class="card-title h2 mb-0" id="deviceCount">-</h3>
                                <div class="text-muted">Devices</div>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Restores Card -->
                <div class="col-6 col-md-3">
                    <a href="/mobile/restores.php" class="text-decoration-none">
                        <div class="card bg-dark h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-arrow-counterclockwise mb-2" style="font-size: 1.5rem;"></i>
                                <h3 class="card-title h2 mb-0" id="restoreCount">-</h3>
                                <div class="text-muted">Active Restores</div>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Alerts Card -->
                <div class="col-6 col-md-3">
                    <a href="/mobile/alerts.php" class="text-decoration-none">
                        <div class="card bg-dark h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-exclamation-triangle mb-2" style="font-size: 1.5rem;"></i>
                                <h3 class="card-title h2 mb-0" id="alertCount">-</h3>
                                <div class="text-muted">Unresolved Alerts</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <!-- PWA Install Banner -->
    <div id="pwaInstallBanner" class="container-fluid">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="bi bi-download me-2"></i>
                <span>Install Slide Mobile for a better experience</span>
            </div>
            <div class="d-flex align-items-center">
                <button id="pwaInstallButton" class="btn btn-primary btn-sm me-2">Install</button>
                <button id="pwaCloseButton" class="btn btn-link btn-sm text-white">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        let deferredPrompt;
        const pwaInstallBanner = document.getElementById('pwaInstallBanner');
        const pwaInstallButton = document.getElementById('pwaInstallButton');
        const pwaCloseButton = document.getElementById('pwaCloseButton');

        // Check if user has already dismissed or installed
        const isPWAInstalled = localStorage.getItem('pwaInstalled');
        const isPWADismissed = localStorage.getItem('pwaDismissed');

        // Listen for beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later
            deferredPrompt = e;

            // Show the install banner if not installed or dismissed
            if (!isPWAInstalled && !isPWADismissed) {
                pwaInstallBanner.classList.add('show');
            }
        });

        // Install button click handler
        pwaInstallButton.addEventListener('click', async () => {
            if (!deferredPrompt) return;

            // Show the install prompt
            deferredPrompt.prompt();

            // Wait for the user to respond to the prompt
            const { outcome } = await deferredPrompt.userChoice;
            
            // Clear the deferredPrompt variable
            deferredPrompt = null;

            // Hide the banner
            pwaInstallBanner.classList.remove('show');

            // Mark as installed if accepted
            if (outcome === 'accepted') {
                localStorage.setItem('pwaInstalled', 'true');
            }
        });

        // Close button click handler
        pwaCloseButton.addEventListener('click', () => {
            pwaInstallBanner.classList.remove('show');
            localStorage.setItem('pwaDismissed', 'true');
        });

        // Listen for successful installation
        window.addEventListener('appinstalled', () => {
            localStorage.setItem('pwaInstalled', 'true');
            pwaInstallBanner.classList.remove('show');
        });

        <?php if ($hasKey): ?>
        let isLoading = false;
        let updateInterval;

        async function updateStats(isBackgroundUpdate = false) {
            if (isLoading) return;
            isLoading = true;

            try {
                // Fetch all data in parallel
                const [agentsResponse, devicesResponse, restoresResponse, alertsResponse] = await Promise.all([
                    fetch('/mobile/mobileSlideApi.php?action=getAgents'),
                    fetch('/mobile/mobileSlideApi.php?action=getDevices'),
                    Promise.all([
                        fetch('/mobile/mobileSlideApi.php?action=getFileRestores'),
                        fetch('/mobile/mobileSlideApi.php?action=getImageExports'),
                        fetch('/mobile/mobileSlideApi.php?action=getVirtualMachines')
                    ]),
                    fetch('/mobile/mobileSlideApi.php?action=getAlerts')
                ]);

                // Process responses
                const agentsData = await agentsResponse.json();
                const devicesData = await devicesResponse.json();
                const [fileRestores, imageExports, virtualMachines] = await Promise.all(
                    restoresResponse.map(r => r.json())
                );
                const alertsData = await alertsResponse.json();

                // Update counts
                if (agentsData.success) {
                    document.getElementById('agentCount').textContent = agentsData.data.length;
                }
                if (devicesData.success) {
                    document.getElementById('deviceCount').textContent = devicesData.data.length;
                }
                
                // Calculate total active restores
                const totalRestores = (
                    (fileRestores.success ? fileRestores.data.length : 0) +
                    (imageExports.success ? imageExports.data.length : 0) +
                    (virtualMachines.success ? virtualMachines.data.length : 0)
                );
                document.getElementById('restoreCount').textContent = totalRestores;

                // Count unresolved alerts
                if (alertsData.success) {
                    const unresolvedAlerts = alertsData.data.filter(alert => !alert.resolved).length;
                    document.getElementById('alertCount').textContent = unresolvedAlerts;
                }

            } catch (error) {
                console.error('Error updating stats:', error);
                if (!isBackgroundUpdate) {
                    document.querySelectorAll('#agentCount, #deviceCount, #restoreCount, #alertCount').forEach(el => {
                        el.textContent = '-';
                    });
                }
            } finally {
                isLoading = false;
            }
        }

        // Start periodic updates
        function startUpdates() {
            // Initial update
            updateStats(false);
            
            // Set up background updates
            updateInterval = setInterval(() => {
                updateStats(true);
            }, 5000);
        }

        // Start updates when page loads
        startUpdates();

        // Clean up interval when page is unloaded
        window.addEventListener('unload', () => {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
        <?php endif; ?>
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
