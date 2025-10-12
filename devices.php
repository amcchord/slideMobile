<?php
require_once 'include/getApiKey.php';
if (!hasApiKey()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Devices - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .device-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }

        .device-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
        }

        .device-item:last-child {
            border-bottom: none;
        }

        .device-info {
            flex-grow: 1;
            min-width: 0; /* Allow text truncation */
        }

        .device-name {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .device-agent {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            padding: 1rem;
        }

        .loading .loading-spinner {
            display: flex;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Devices</span>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <select class="form-select form-select-sm" id="clientFilter" onchange="loadDevices()">
            <option value="">All Clients</option>
        </select>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="device-list" id="deviceList">
            <!-- Devices will be loaded here -->
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script>
        let isLoading = false;
        let refreshInterval;
        let isInitialLoad = true;
        let clients = [];

        async function loadClients() {
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getClients');
                const data = await response.json();

                if (data.success && data.data) {
                    clients = data.data;
                    const clientFilter = document.getElementById('clientFilter');
                    
                    data.data.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.client_id;
                        option.textContent = client.name;
                        clientFilter.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading clients:', error);
            }
        }

        async function loadDevices() {
            if (isLoading) return;
            isLoading = true;
            
            try {
                const clientFilter = document.getElementById('clientFilter').value;
                let url = '/mobile/mobileSlideApi.php?action=getDevices';
                if (clientFilter) {
                    url += `&client_id=${encodeURIComponent(clientFilter)}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load devices');
                }

                // Sort devices by display name or hostname
                const devices = data.data.sort((a, b) => {
                    const nameA = a.display_name || a.hostname || '';
                    const nameB = b.display_name || b.hostname || '';
                    return nameA.localeCompare(nameB);
                });

                const container = document.getElementById('deviceList');
                
                // Add initial-load class only on first load
                if (isInitialLoad) {
                    container.classList.add('initial-load');
                    isInitialLoad = false;
                    // Remove the class after animations complete
                    setTimeout(() => {
                        container.classList.remove('initial-load');
                    }, 500);
                }
                
                container.innerHTML = ''; // Clear existing devices

                devices.forEach(device => {
                    const deviceName = device.display_name || device.hostname || 'Unknown Device';
                    const agentCount = device.agents?.length || 0;
                    
                    // Check if device is stale (last seen > 30 minutes ago)
                    const lastSeen = new Date(device.last_seen_at);
                    const thirtyMinutesAgo = new Date(Date.now() - 30 * 60 * 1000);
                    const isStale = lastSeen < thirtyMinutesAgo;
                    
                    // Get client name if available
                    const client = clients.find(c => c.client_id === device.client_id);
                    const clientName = client ? client.name : null;
                    
                    // Format storage info
                    const storageUsedGB = (device.storage_used_bytes / (1024 * 1024 * 1024)).toFixed(1);
                    const storageTotalGB = (device.storage_total_bytes / (1024 * 1024 * 1024)).toFixed(1);
                    const storagePercent = ((device.storage_used_bytes / device.storage_total_bytes) * 100).toFixed(0);
                    
                    const deviceEl = document.createElement('div');
                    deviceEl.className = 'device-item d-flex align-items-center px-3';
                    deviceEl.style.cursor = 'pointer';
                    deviceEl.onclick = () => showDeviceDetails(device.device_id);

                    deviceEl.innerHTML = `
                        <div class="device-info me-3 flex-grow-1">
                            <h6 class="device-name ${isStale ? 'text-warning' : ''}">
                                ${isStale ? '<i class="bi bi-clock me-1"></i>' : ''}
                                ${deviceName}
                            </h6>
                            <div class="device-agent">
                                ${agentCount} agent${agentCount !== 1 ? 's' : ''}
                                ${clientName ? `<span class="client-badge ms-2">${clientName}</span>` : ''}
                            </div>
                            <div class="device-agent mt-1">
                                <i class="bi bi-hdd"></i> ${storageUsedGB} / ${storageTotalGB} GB (${storagePercent}%)
                            </div>
                        </div>
                    `;
                    container.appendChild(deviceEl);
                });
            } catch (error) {
                console.error('Error loading devices:', error);
            } finally {
                isLoading = false;
                document.querySelector('.loading-spinner').style.display = 'none';
            }
        }

        async function backupNow(deviceId) {
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=startBackup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ device_id: deviceId })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start backup');
                }

                // Update the button immediately to show spinning gear
                const deviceEl = document.querySelector(`[onclick="showDeviceDetails('${deviceId}')"]`);
                if (deviceEl) {
                    const button = deviceEl.querySelector('.btn');
                    if (button) {
                        button.className = 'btn btn-success';
                        button.innerHTML = '<i class="bi bi-gear-fill spin"></i> Started';
                    }
                }
            } catch (error) {
                console.error('Error starting backup:', error);
                alert('Failed to start backup: ' + error.message);
            }
        }

        function showDeviceDetails(deviceId) {
            // Navigate to device details page
            window.location.href = `device-details.php?id=${deviceId}`;
        }

        // Start periodic refresh
        function startRefresh() {
            // Clear any existing interval
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            // Set new interval
            refreshInterval = setInterval(loadDevices, 5000);
        }

        // Stop refresh when page is hidden
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }
            } else {
                startRefresh();
            }
        });

        // Initial load and start refresh
        async function init() {
            await loadClients();
            loadDevices();
            startRefresh();
        }
        
        init();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 