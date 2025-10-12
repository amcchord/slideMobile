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
    <title>New VM Restore - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .content-area {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .form-text {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
        .form-select:disabled {
            background-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <a href="new-restore.php" class="btn btn-link text-light">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="navbar-brand mb-0 h1">New Virtual Machine</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container py-4">
            <!-- Progress -->
            <div class="progress mb-4" style="height: 4px;">
                <div class="progress-bar" role="progressbar" id="progressBar"></div>
            </div>

            <!-- Step 1: Select Agent -->
            <div class="step active" id="step1">
                <h5 class="mb-4">Select Agent</h5>
                <div class="mb-3">
                    <label for="agentSelect" class="form-label">
                        Agent
                        <div class="spinner-border spinner-border-sm loading-spinner" id="agentSpinner" style="display: none;"></div>
                    </label>
                    <select class="form-select" id="agentSelect" required>
                        <option value="" selected disabled>Loading agents...</option>
                    </select>
                    <div class="form-text mt-2" id="agentInfo"></div>
                </div>
            </div>

            <!-- Step 2: Select Recovery Point -->
            <div class="step" id="step2">
                <h5 class="mb-4">Select Recovery Point</h5>
                <div class="mb-3">
                    <label for="snapshotSelect" class="form-label">
                        Recovery Point
                        <div class="spinner-border spinner-border-sm loading-spinner" id="snapshotSpinner" style="display: none;"></div>
                    </label>
                    <select class="form-select" id="snapshotSelect" required>
                        <option value="" selected disabled>Select a recovery point...</option>
                    </select>
                    <div class="form-text mt-2" id="snapshotInfo"></div>
                </div>
            </div>

            <!-- Step 3: Configure VM -->
            <div class="step" id="step3">
                <h5 class="mb-4">Configure Virtual Machine</h5>
                <div id="creatingVmOverlay" style="display: none;" class="text-center mb-4">
                    <div class="spinner-border text-primary mb-3"></div>
                    <p>Creating virtual machine...</p>
                </div>
                <div id="vmForm">
                    <div class="mb-3">
                        <label for="cpuCount" class="form-label">CPU Cores</label>
                        <select class="form-select" id="cpuCount" required>
                            <option value="1">1 Core</option>
                            <option value="2">2 Cores</option>
                            <option value="4" selected>4 Cores</option>
                            <option value="8">8 Cores</option>
                            <option value="16">16 Cores</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="memoryInMB" class="form-label">Memory</label>
                        <select class="form-select" id="memoryInMB" required>
                            <option value="1024">1 GB</option>
                            <option value="2048">2 GB</option>
                            <option value="4096">4 GB</option>
                            <option value="8192" selected>8 GB</option>
                            <option value="12288">12 GB</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="diskBus" class="form-label">Disk Bus</label>
                        <select class="form-select" id="diskBus" required>
                            <option value="sata" selected>SATA</option>
                            <option value="virtio">VirtIO</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="networkModel" class="form-label">Network Model</label>
                        <select class="form-select" id="networkModel" required>
                            <option value="e1000" selected>Intel E1000</option>
                            <option value="rtl8139">Realtek RTL8139</option>
                            <option value="hypervisor_default">Hypervisor Default</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="networkType" class="form-label">Network Type</label>
                        <select class="form-select" id="networkType" required>
                            <option value="network-nat-shared">NAT Shared</option>
                            <option value="network-nat-isolated">NAT Isolated</option>
                            <option value="bridge" selected>Bridge</option>
                            <option value="network-id">Specific Network</option>
                        </select>
                        <div class="form-text">Choose how the VM connects to the network</div>
                    </div>
                    <div class="mb-3" id="networkSelectGroup" style="display: none;">
                        <label for="networkSelect" class="form-label">
                            Select Network
                            <div class="spinner-border spinner-border-sm loading-spinner" id="networkSpinner" style="display: none;"></div>
                        </label>
                        <select class="form-select" id="networkSelect">
                            <option value="" selected disabled>Select a network...</option>
                        </select>
                        <div class="form-text">Choose which network to connect to</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between mt-4 mb-5">
                <button class="btn btn-secondary" id="prevButton" style="display: none;">
                    <i class="bi bi-arrow-left me-2"></i>Previous
                </button>
                <button class="btn btn-primary" id="nextButton" disabled>
                    Next<i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script>
        let currentStep = 1;
        let selectedAgent = null;
        let selectedSnapshot = null;
        const totalSteps = 3;

        // Update progress bar
        function updateProgress() {
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progressBar').style.width = `${progress}%`;
        }

        // Show/hide steps
        function showStep(step) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById(`step${step}`).classList.add('active');
            
            // Update buttons
            document.getElementById('prevButton').style.display = step > 1 ? 'block' : 'none';
            const nextButton = document.getElementById('nextButton');
            nextButton.disabled = (step === 1 && !selectedAgent) || 
                                (step === 2 && !selectedSnapshot);
            
            updateProgress();
        }

        // Show/hide loading spinner
        function showSpinner(elementId, show) {
            const spinner = document.getElementById(elementId);
            const select = elementId === 'agentSpinner' ? document.getElementById('agentSelect') : document.getElementById('snapshotSelect');
            spinner.style.display = show ? 'inline-block' : 'none';
            select.disabled = show;
        }

        // Format date for display
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return 'Unknown Date';
            
            const now = new Date();
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            
            // If it's today
            if (date.toDateString() === now.toDateString()) {
                return `Today at ${date.toLocaleTimeString()}`;
            }
            // If it's yesterday
            else if (date.toDateString() === yesterday.toDateString()) {
                return `Yesterday at ${date.toLocaleTimeString()}`;
            }
            // If it's within the last 7 days
            else if ((now - date) < 7 * 24 * 60 * 60 * 1000) {
                return `${date.toLocaleDateString(undefined, { weekday: 'long' })} at ${date.toLocaleTimeString()}`;
            }
            // Otherwise show full date
            else {
                return date.toLocaleString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }
        }

        // Format bytes to human readable size
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Load agents
        async function loadAgents() {
            showSpinner('agentSpinner', true);
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getAgents');
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load agents');
                }

                const agentSelect = document.getElementById('agentSelect');
                if (data.data.length === 0) {
                    agentSelect.innerHTML = '<option value="" disabled>No agents found</option>';
                    return;
                }

                // Sort agents by display name or hostname
                const sortedAgents = data.data.sort((a, b) => {
                    const nameA = a.display_name || a.hostname;
                    const nameB = b.display_name || b.hostname;
                    return nameA.localeCompare(nameB);
                });

                agentSelect.innerHTML = `
                    <option value="" disabled selected>Select an agent...</option>
                    ${sortedAgents.map(agent => `
                        <option value="${agent.agent_id}" 
                                data-hostname="${agent.hostname}"
                                data-device="${agent.device?.display_name || 'Unknown Device'}">
                            ${agent.display_name || agent.hostname}
                        </option>
                    `).join('')}
                `;

                // Add change event listener
                agentSelect.addEventListener('change', function() {
                    selectedAgent = this.value;
                    const selectedOption = this.options[this.selectedIndex];
                    document.getElementById('agentInfo').innerHTML = `
                        <div class="mt-2">
                            <div>Hostname: ${selectedOption.dataset.hostname}</div>
                            <div>Device: ${selectedOption.dataset.device}</div>
                        </div>
                    `;
                    document.getElementById('nextButton').disabled = !selectedAgent;
                });

            } catch (error) {
                console.error('Error loading agents:', error);
                document.getElementById('agentSelect').innerHTML = `
                    <option value="" disabled>Failed to load agents</option>
                `;
            } finally {
                showSpinner('agentSpinner', false);
            }
        }

        // Load snapshots for selected agent
        async function loadSnapshots(agentId) {
            showSpinner('snapshotSpinner', true);
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getSnapshots&agent_id=' + encodeURIComponent(agentId));
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load snapshots');
                }

                // Filter and sort snapshots for selected agent
                const agentSnapshots = data.data
                    .filter(snapshot => snapshot.agent_id === agentId)
                    .sort((a, b) => new Date(b.backup_ended_at) - new Date(a.backup_ended_at));
                
                const snapshotSelect = document.getElementById('snapshotSelect');
                if (agentSnapshots.length === 0) {
                    snapshotSelect.innerHTML = '<option value="" disabled>No recovery points found</option>';
                    return;
                }

                snapshotSelect.innerHTML = `
                    <option value="" disabled selected>Select a recovery point...</option>
                    ${agentSnapshots.map(snapshot => `
                        <option value="${snapshot.snapshot_id}"
                                data-time="${snapshot.backup_ended_at}">
                            ${formatDate(snapshot.backup_ended_at)}
                        </option>
                    `).join('')}
                `;

                // Add change event listener
                snapshotSelect.addEventListener('change', function() {
                    selectedSnapshot = this.value;
                    const selectedOption = this.options[this.selectedIndex];
                    document.getElementById('snapshotInfo').innerHTML = `
                        <div class="mt-2">
                            <div>Time: ${formatDate(selectedOption.dataset.time)}</div>
                        </div>
                    `;
                    document.getElementById('nextButton').disabled = !selectedSnapshot;
                });

            } catch (error) {
                console.error('Error loading snapshots:', error);
                document.getElementById('snapshotSelect').innerHTML = `
                    <option value="" disabled>Failed to load recovery points</option>
                `;
            } finally {
                showSpinner('snapshotSpinner', false);
            }
        }

        // Load networks
        async function loadNetworks() {
            const spinner = document.getElementById('networkSpinner');
            const select = document.getElementById('networkSelect');
            
            spinner.style.display = 'inline-block';
            select.disabled = true;
            
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getNetworks');
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load networks');
                }

                if (data.data.length === 0) {
                    select.innerHTML = '<option value="" disabled>No networks found</option>';
                    return;
                }

                select.innerHTML = `
                    <option value="" disabled selected>Select a network...</option>
                    ${data.data.map(network => `
                        <option value="${network.network_id}">
                            ${network.name} (${network.type === 'bridge-lan' ? 'Bridge to LAN' : 'Standard'})
                        </option>
                    `).join('')}
                `;

            } catch (error) {
                console.error('Error loading networks:', error);
                select.innerHTML = '<option value="" disabled>Failed to load networks</option>';
            } finally {
                spinner.style.display = 'none';
                select.disabled = false;
            }
        }

        // Handle network type change
        document.getElementById('networkType').addEventListener('change', function() {
            const networkSelectGroup = document.getElementById('networkSelectGroup');
            if (this.value === 'network-id') {
                networkSelectGroup.style.display = 'block';
                loadNetworks();
            } else {
                networkSelectGroup.style.display = 'none';
                document.getElementById('networkSelect').value = '';
            }
        });

        // Handle next button click
        document.getElementById('nextButton').addEventListener('click', async () => {
            if (currentStep === 1) {
                await loadSnapshots(selectedAgent);
                currentStep = 2;
                showStep(2);
            } else if (currentStep === 2) {
                currentStep = 3;
                showStep(3);
                document.getElementById('nextButton').disabled = false;
            } else if (currentStep === 3) {
                // Create virtual machine
                try {
                    document.getElementById('creatingVmOverlay').style.display = 'block';
                    document.getElementById('vmForm').style.opacity = '0.5';
                    document.getElementById('nextButton').disabled = true;
                    document.getElementById('prevButton').disabled = true;

                    const networkType = document.getElementById('networkType').value;
                    const payload = {
                        agent_id: selectedAgent,
                        snapshot_id: selectedSnapshot,
                        cpu_count: parseInt(document.getElementById('cpuCount').value),
                        memory_in_mb: parseInt(document.getElementById('memoryInMB').value),
                        disk_bus: document.getElementById('diskBus').value,
                        network_model: document.getElementById('networkModel').value,
                        network_type: networkType
                    };

                    // Add network_source if specific network is selected
                    if (networkType === 'network-id') {
                        const networkId = document.getElementById('networkSelect').value;
                        if (!networkId) {
                            throw new Error('Please select a network');
                        }
                        payload.network_source = networkId;
                    }

                    const response = await fetch('/mobile/mobileSlideApi.php?action=createVirtualMachine', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to create virtual machine');
                    }

                    if (data.data?.virt_id) {
                        // Redirect to manage VM page on success
                        window.location.href = `manage-vm.php?id=${data.data.virt_id}`;
                    } else {
                        throw new Error('No virtual machine ID returned from server');
                    }
                } catch (error) {
                    console.error('Error creating virtual machine:', error);
                    // Hide loading state on error
                    document.getElementById('creatingVmOverlay').style.display = 'none';
                    document.getElementById('vmForm').style.opacity = '1';
                    document.getElementById('nextButton').disabled = false;
                    document.getElementById('prevButton').disabled = false;
                    
                    // Show more detailed error message
                    let errorMessage = error.message;
                    if (error.response) {
                        try {
                            const errorData = await error.response.json();
                            errorMessage = errorData.message || errorData.error || error.message;
                        } catch (e) {
                            // If we can't parse the error response, use the original error message
                        }
                    }
                    alert('Failed to create virtual machine: ' + errorMessage);
                }
            }
        });

        // Handle previous button click
        document.getElementById('prevButton').addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });

        // Check for URL parameters to pre-fill
        const urlParams = new URLSearchParams(window.location.search);
        const prefilledAgentId = urlParams.get('agent_id');
        const prefilledSnapshotId = urlParams.get('snapshot_id');
        const prefilledDeviceId = urlParams.get('device_id');

        // Initial load
        async function init() {
            await loadAgents();
            
            // Auto-select agent if provided
            if (prefilledAgentId) {
                const agentSelect = document.getElementById('agentSelect');
                agentSelect.value = prefilledAgentId;
                selectedAgent = prefilledAgentId;
                agentSelect.dispatchEvent(new Event('change'));
                
                // Load snapshots for this agent
                await loadSnapshots(prefilledAgentId);
                
                // Auto-select snapshot if provided
                if (prefilledSnapshotId) {
                    const snapshotSelect = document.getElementById('snapshotSelect');
                    snapshotSelect.value = prefilledSnapshotId;
                    selectedSnapshot = prefilledSnapshotId;
                    snapshotSelect.dispatchEvent(new Event('change'));
                    
                    // Load devices
                    await loadDevices();
                    
                    // Auto-select device if provided
                    if (prefilledDeviceId) {
                        const deviceSelect = document.getElementById('deviceSelect');
                        deviceSelect.value = prefilledDeviceId;
                        selectedDevice = prefilledDeviceId;
                        deviceSelect.dispatchEvent(new Event('change'));
                    }
                    
                    // Advance to the configuration step
                    currentStep = 3;
                    showStep(3);
                }
            }
            
            updateProgress();
        }
        
        init();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 