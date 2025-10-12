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
    <title>Agents - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .agent-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }

        .agent-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
        }

        .agent-item:last-child {
            border-bottom: none;
        }

        .agent-info {
            flex-grow: 1;
            min-width: 0; /* Allow text truncation */
        }

        .agent-name {
            margin: 0;
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .agent-ip {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .agent-ip i {
            opacity: 0.7;
        }

        .agent-actions {
            flex-shrink: 0;
        }

        .agent-actions .btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        .agent-actions .btn i {
            font-size: 1rem;
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            padding: 1rem;
        }

        .loading .loading-spinner {
            display: flex;
        }

        .pair-code-display {
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 0.1rem;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
            margin: 1rem 0;
        }

        .modal-body .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Agents</span>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pairAgentModal">
                <i class="bi bi-plus-lg"></i> Pair
            </button>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm flex-grow-1" id="clientFilter" onchange="loadAgents()">
                <option value="">All Clients</option>
            </select>
            <select class="form-select form-select-sm flex-grow-1" id="deviceFilter">
                <option value="">All Devices</option>
            </select>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="agent-list" id="agentList">
            <!-- Agents will be loaded here -->
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </main>

    <!-- Pair Agent Modal -->
    <div class="modal fade" id="pairAgentModal" tabindex="-1" aria-labelledby="pairAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pairAgentModalLabel">Pair New Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tab navigation for pairing methods -->
                    <ul class="nav nav-tabs mb-3" id="pairTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">
                                Manual Pair
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="auto-tab" data-bs-toggle="tab" data-bs-target="#auto" type="button" role="tab">
                                Auto-Pair
                            </button>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content" id="pairTabContent">
                        <!-- Manual Pair Tab -->
                        <div class="tab-pane fade show active" id="manual" role="tabpanel">
                            <form id="manualPairForm">
                                <div class="mb-3">
                                    <label for="deviceSelect" class="form-label">Select Device</label>
                                    <select class="form-select" id="deviceSelect" required>
                                        <option value="">Choose a device...</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="pairCode" class="form-label">Pair Code</label>
                                    <input type="text" class="form-control" id="pairCode" placeholder="ABC123" maxlength="6" required>
                                    <div class="form-text">Enter the 6-character code shown on the agent</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Pair Agent</button>
                            </form>
                        </div>

                        <!-- Auto-Pair Tab -->
                        <div class="tab-pane fade" id="auto" role="tabpanel">
                            <form id="autoPairForm">
                                <div class="mb-3">
                                    <label for="autoDeviceSelect" class="form-label">Select Device</label>
                                    <select class="form-select" id="autoDeviceSelect" required>
                                        <option value="">Choose a device...</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="displayName" class="form-label">Display Name</label>
                                    <input type="text" class="form-control" id="displayName" placeholder="My Computer" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Generate Pair Code</button>
                            </form>
                            <div id="pairCodeResult" style="display: none;">
                                <hr class="my-3">
                                <p class="text-center mb-2">Use this code when installing the agent:</p>
                                <div class="pair-code-display" id="generatedPairCode"></div>
                                <p class="text-center text-muted small">This code will expire in 5 minutes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script>
        let isLoading = false;
        let refreshInterval;
        let selectedDevice = '';
        let allAgents = [];
        let availableDevices = [];
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

        // Get device_id from URL if present
        const urlParams = new URLSearchParams(window.location.search);
        const filterDeviceId = urlParams.get('device_id');

        // Update the device filter dropdown
        function updateDeviceFilter(agents) {
            const deviceFilter = document.getElementById('deviceFilter');
            const devices = new Map(); // Use Map to store unique device info
            
            agents.forEach(agent => {
                if (agent.device) {
                    const deviceName = agent.device.display_name || agent.device.hostname || 'Unknown Device';
                    devices.set(agent.device.device_id, deviceName);
                }
            });

            // Sort devices alphabetically
            const sortedDevices = Array.from(devices.entries()).sort((a, b) => a[1].localeCompare(b[1]));
            
            // Clear existing options except "All Devices"
            while (deviceFilter.options.length > 1) {
                deviceFilter.remove(1);
            }

            // Add device options
            sortedDevices.forEach(([deviceId, deviceName]) => {
                const option = new Option(deviceName, deviceId);
                deviceFilter.add(option);
            });

            // If we have a filtered device_id, select it
            if (filterDeviceId) {
                deviceFilter.value = filterDeviceId;
                selectedDevice = filterDeviceId;
            }
        }

        // Load available devices for pairing
        async function loadDevicesForPairing() {
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getDevices');
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load devices');
                }

                availableDevices = data.data || [];
                
                // Update device selects in modal
                const deviceSelect = document.getElementById('deviceSelect');
                const autoDeviceSelect = document.getElementById('autoDeviceSelect');
                
                // Clear existing options
                deviceSelect.innerHTML = '<option value="">Choose a device...</option>';
                autoDeviceSelect.innerHTML = '<option value="">Choose a device...</option>';
                
                // Add device options
                availableDevices.forEach(device => {
                    const deviceName = device.display_name || device.hostname || 'Unknown Device';
                    const option1 = new Option(deviceName, device.device_id);
                    const option2 = new Option(deviceName, device.device_id);
                    deviceSelect.add(option1);
                    autoDeviceSelect.add(option2);
                });
            } catch (error) {
                console.error('Error loading devices for pairing:', error);
            }
        }

        // Handle manual pair form submission
        document.getElementById('manualPairForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const deviceId = document.getElementById('deviceSelect').value;
            const pairCode = document.getElementById('pairCode').value.toUpperCase();
            
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=pairAgent', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        device_id: deviceId,
                        pair_code: pairCode
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to pair agent');
                }
                
                // Close modal and refresh agents list
                bootstrap.Modal.getInstance(document.getElementById('pairAgentModal')).hide();
                document.getElementById('manualPairForm').reset();
                loadAgents();
                
                alert('Agent paired successfully!');
            } catch (error) {
                console.error('Error pairing agent:', error);
                alert('Failed to pair agent: ' + error.message);
            }
        });

        // Handle auto-pair form submission
        document.getElementById('autoPairForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const deviceId = document.getElementById('autoDeviceSelect').value;
            const displayName = document.getElementById('displayName').value;
            
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=createAgentForPairing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        device_id: deviceId,
                        display_name: displayName
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to create agent');
                }
                
                // Show the generated pair code
                document.getElementById('generatedPairCode').textContent = data.data.pair_code;
                document.getElementById('pairCodeResult').style.display = 'block';
                
                // Refresh agents list
                loadAgents();
            } catch (error) {
                console.error('Error creating agent for pairing:', error);
                alert('Failed to create agent: ' + error.message);
            }
        });

        // Filter and display agents
        function formatLastBackup(snapshot) {
            if (!snapshot || !snapshot.backup_ended_at) return null;
            
            const backupDate = new Date(snapshot.backup_ended_at);
            const now = new Date();
            const diffMs = now - backupDate;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) {
                return 'Just now';
            }
            if (diffMins < 60) {
                return `${diffMins}m ago`;
            }
            if (diffHours < 24) {
                return `${diffHours}h ago`;
            }
            if (diffDays < 7) {
                return `${diffDays}d ago`;
            }
            
            return backupDate.toLocaleDateString();
        }

        function displayAgents(agents) {
            const container = document.getElementById('agentList');
            
            // Add initial-load class only on first load
            if (isInitialLoad) {
                container.classList.add('initial-load');
                isInitialLoad = false;
                // Remove the class after animations complete
                setTimeout(() => {
                    container.classList.remove('initial-load');
                }, 500);
            }
            
            container.innerHTML = ''; // Clear existing agents

            // Sort agents by display name or hostname
            const sortedAgents = agents.sort((a, b) => {
                const nameA = a.display_name || a.hostname || '';
                const nameB = b.display_name || b.hostname || '';
                return nameA.localeCompare(nameB);
            });

            sortedAgents.forEach(agent => {
                // Skip if a device is selected and doesn't match
                if (selectedDevice && agent.device?.device_id !== selectedDevice) {
                    return;
                }

                const agentName = agent.display_name || agent.hostname || 'Unknown Agent';
                const ipDisplay = (agent.ip_addresses || []).join(', ') || 'IP not available';
                
                // Check if agent is stale (last seen > 30 minutes ago)
                const lastSeen = new Date(agent.last_seen_at);
                const thirtyMinutesAgo = new Date(Date.now() - 30 * 60 * 1000);
                const isStale = lastSeen < thirtyMinutesAgo;
                
                const agentEl = document.createElement('div');
                agentEl.className = 'agent-item d-flex align-items-center px-3';
                agentEl.style.cursor = 'pointer';
                agentEl.onclick = () => showAgentDetails(agent.agent_id);

                // Check if agent has an active backup
                const activeBackup = agent.active_backup;
                
                // Get last snapshot info
                const lastSnapshot = agent.lastSnapshot;
                const lastBackupInfo = lastSnapshot ? formatLastBackup(lastSnapshot) : null;
                const hasScreenshot = lastSnapshot?.verify_boot_screenshot_url;
                const screenshotStatus = lastSnapshot?.verify_boot_status;
                const hasSnapshots = !!lastSnapshot;
                
                // Get client name if available
                const client = clients.find(c => c.client_id === agent.client_id);
                const clientName = client ? client.name : null;

                agentEl.innerHTML = `
                    <div class="agent-info me-3 flex-grow-1">
                        <h6 class="agent-name ${isStale ? 'text-warning' : ''}">
                            ${isStale ? '<i class="bi bi-clock me-1"></i>' : ''}
                            ${agentName}
                            ${clientName ? `<span class="client-badge ms-2" style="font-size: 0.7rem;">${clientName}</span>` : ''}
                        </h6>
                        <div class="agent-ip">${ipDisplay}</div>
                        ${lastBackupInfo ? `
                            <div class="agent-ip mt-1">
                                <i class="bi bi-clock-history"></i> Last backup: ${lastBackupInfo}
                                ${hasScreenshot ? 
                                    `<i class="bi bi-${screenshotStatus === 'success' ? 'check-circle-fill text-success' : screenshotStatus === 'warning' ? 'exclamation-triangle-fill text-warning' : screenshotStatus === 'error' ? 'x-circle-fill text-danger' : 'hourglass text-muted'} ms-1" title="Boot verification: ${screenshotStatus || 'pending'}"></i>` 
                                    : ''}
                            </div>
                        ` : '<div class="agent-ip mt-1 text-muted"><i class="bi bi-exclamation-circle"></i> No backups yet</div>'}
                    </div>
                    ${activeBackup ? 
                        `<button class="btn ${activeBackup.status === 'failed' ? 'btn-warning' : 'btn-success'} btn-sm agent-actions">
                            ${activeBackup.status === 'started' ? 
                                '<i class="bi bi-gear-fill spin"></i>' : 
                                '<i class="bi bi-exclamation-triangle"></i>'
                            } ${activeBackup['backup-status'] || activeBackup.status.charAt(0).toUpperCase() + activeBackup.status.slice(1)}
                        </button>` :
                        `<button class="btn btn-primary btn-sm agent-actions" onclick="event.stopPropagation(); backupNow('${agent.agent_id}')">
                            Backup Now
                        </button>`
                    }
                `;
                container.appendChild(agentEl);
            });

            // Show empty state if no agents
            if (container.children.length === 0) {
                container.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 60vh;">
                        <p class="text-muted mb-4">No agents found</p>
                        <button class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#pairAgentModal">
                            <i class="bi bi-plus-lg me-2"></i>
                            Pair New Agent
                        </button>
                    </div>
                `;
            }
        }

        async function loadAgents() {
            if (isLoading) return;
            isLoading = true;
            
            try {
                const clientFilter = document.getElementById('clientFilter').value;
                let url = '/mobile/mobileSlideApi.php?action=getAgents';
                if (clientFilter) {
                    url += `&client_id=${encodeURIComponent(clientFilter)}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load agents');
                }

                // Store all agents
                allAgents = data.data;
                
                // Fetch last snapshot for each agent
                const snapshotPromises = allAgents.map(agent => 
                    fetch(`/mobile/mobileSlideApi.php?action=getSnapshots&agent_id=${agent.agent_id}&limit=1`)
                        .then(r => r.json())
                        .then(d => ({
                            agent_id: agent.agent_id,
                            snapshot: d.success && d.data && d.data.length > 0 ? d.data[0] : null
                        }))
                        .catch(() => ({ agent_id: agent.agent_id, snapshot: null }))
                );

                const snapshotResults = await Promise.all(snapshotPromises);
                
                // Attach snapshot info to agents
                snapshotResults.forEach(result => {
                    const agent = allAgents.find(a => a.agent_id === result.agent_id);
                    if (agent) {
                        agent.lastSnapshot = result.snapshot;
                    }
                });
                
                // Update device filter with all known devices
                updateDeviceFilter(allAgents);
                
                // Display filtered agents
                displayAgents(allAgents);
            } catch (error) {
                console.error('Error loading agents:', error);
            } finally {
                isLoading = false;
                document.querySelector('.loading-spinner').style.display = 'none';
            }
        }

        async function backupNow(agentId) {
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=startBackup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ agent_id: agentId })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start backup');
                }

                // Update the button immediately to show spinning gear
                const agentEl = document.querySelector(`[onclick="showAgentDetails('${agentId}')"]`);
                if (agentEl) {
                    const button = agentEl.querySelector('.btn');
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

        function showAgentDetails(agentId) {
            // Navigate to agent details page
            window.location.href = `agent-details.php?id=${agentId}`;
        }

        // Start periodic refresh
        function startRefresh() {
            // Clear any existing interval
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            // Set new interval
            refreshInterval = setInterval(loadAgents, 5000);
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

        // Handle device filter changes
        document.getElementById('deviceFilter').addEventListener('change', (e) => {
            selectedDevice = e.target.value;
            displayAgents(allAgents);
        });

        // Load devices when modal is shown
        document.getElementById('pairAgentModal').addEventListener('show.bs.modal', () => {
            loadDevicesForPairing();
            // Reset forms
            document.getElementById('manualPairForm').reset();
            document.getElementById('autoPairForm').reset();
            document.getElementById('pairCodeResult').style.display = 'none';
        });

        // Initial load and start refresh
        async function init() {
            await loadClients();
            loadAgents();
            startRefresh();
        }
        
        init();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 