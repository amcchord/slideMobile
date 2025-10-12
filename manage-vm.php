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
    <title>Manage VM - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .content-area {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }
        .vm-details {
            display: none;
        }
        .vm-details.loaded {
            display: block;
        }
        .state-badge {
            text-transform: capitalize;
        }
        .state-badge.running {
            background-color: var(--bs-success);
        }
        .state-badge.stopped {
            background-color: var(--bs-danger);
        }
        .state-badge.paused {
            background-color: var(--bs-warning);
        }
        .action-buttons {
            gap: 0.5rem;
        }
        .action-buttons .btn {
            min-width: 100px;
        }
        .vnc-info {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        .vnc-info pre {
            margin-bottom: 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <a href="restores.php" class="btn btn-link text-light">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="navbar-brand mb-0 h1">Manage Virtual Machine</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container py-4">
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="vm-details">
                <!-- VM Info -->
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 agent-name"></h5>
                        <span class="badge state-badge"></span>
                    </div>
                    <div class="action-buttons d-flex justify-content-between">
                        <button class="btn btn-success" id="startButton" style="display: none;">
                            <i class="bi bi-play-fill"></i>
                            Start
                        </button>
                        <button class="btn btn-warning" id="pauseButton" style="display: none;">
                            <i class="bi bi-pause-fill"></i>
                            Pause
                        </button>
                        <button class="btn btn-info" id="resumeButton" style="display: none;">
                            <i class="bi bi-play-fill"></i>
                            Resume
                        </button>
                        <button class="btn btn-danger" id="stopButton" style="display: none;">
                            <i class="bi bi-stop-fill"></i>
                            Stop
                        </button>
                        <button class="btn btn-outline-danger" id="deleteButton">
                            <i class="bi bi-trash"></i>
                            Delete
                        </button>
                    </div>
                </div>

                <!-- VM Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-3 text-muted">Configuration</h6>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="fw-bold mb-1">CPU Cores</div>
                                <div class="cpu-count"></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="fw-bold mb-1">Memory</div>
                                <div class="memory"></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="fw-bold mb-1">Disk Bus</div>
                                <div class="disk-bus"></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="fw-bold mb-1">Network</div>
                                <div class="network-info"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VNC Access -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-3 text-muted">VNC Access</h6>
                        <div class="vnc-info">
                            <div class="vnc-endpoints mb-3"></div>
                            <div class="mb-2">
                                <strong>Password:</strong>
                                <code class="ms-2 vnc-password"></code>
                            </div>
                            <div>
                                <strong>Local Endpoint:</strong>
                                <div class="local-endpoint mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script>
        // Get VM ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const virtId = urlParams.get('id');
        if (!virtId) {
            window.location.href = 'restores.php';
        }

        let currentState = null;
        let isUpdating = false;

        // Format memory size
        function formatMemory(mb) {
            if (mb < 1024) return `${mb} MB`;
            return `${(mb / 1024).toFixed(1)} GB`;
        }

        // Update VM state UI
        function updateStateUI(state) {
            currentState = state;
            
            // Update state badge
            const badge = document.querySelector('.state-badge');
            badge.textContent = state;
            badge.className = `badge state-badge ${state}`;

            // Show/hide action buttons based on state
            document.getElementById('startButton').style.display = state === 'stopped' ? 'block' : 'none';
            document.getElementById('pauseButton').style.display = state === 'running' ? 'block' : 'none';
            document.getElementById('resumeButton').style.display = state === 'paused' ? 'block' : 'none';
            document.getElementById('stopButton').style.display = ['running', 'paused'].includes(state) ? 'block' : 'none';
        }

        // Load VM details
        async function loadVMDetails() {
            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getVirtualMachines&id=${virtId}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load virtual machine details');
                }

                // Since getVirtualMachines returns an array, we need to find our VM
                const vm = data.data.find(v => v.virt_id === virtId);
                if (!vm) {
                    throw new Error('Virtual machine not found');
                }

                // Update VM info
                document.querySelector('.agent-name').textContent = vm.agent_display_name || vm.agent_hostname || 'Unknown Agent';
                document.querySelector('.cpu-count').textContent = `${vm.cpu_count} Core${vm.cpu_count > 1 ? 's' : ''}`;
                document.querySelector('.memory').textContent = formatMemory(vm.memory_in_mb);
                document.querySelector('.disk-bus').textContent = vm.disk_bus.toUpperCase();
                document.querySelector('.network-info').textContent = `${vm.network_model} (${vm.network_type})`;

                // Update VNC info
                document.querySelector('.vnc-password').textContent = vm.vnc_password;
                let vncConsoleButton = '';
                let localEndpoint = '';
                
                vm.vnc.forEach(vnc => {
                    if (vnc.type === 'local') {
                        localEndpoint = `<pre>${vnc.host}:${vnc.port}</pre>`;
                    } else {
                        const vncUrl = `vnc-viewer.php?id=${virtId}&ws=${encodeURIComponent(vnc.websocket_uri)}&password=${encodeURIComponent(vm.vnc_password)}`;
                        vncConsoleButton = `
                            <div class="d-grid">
                                <a href="${vncUrl}" class="btn btn-primary btn-lg">
                                    <i class="bi bi-display me-2"></i>
                                    Open VNC Console
                                </a>
                            </div>
                        `;
                    }
                });

                document.querySelector('.vnc-endpoints').innerHTML = vncConsoleButton;
                document.querySelector('.local-endpoint').innerHTML = localEndpoint;

                // Update state
                updateStateUI(vm.state);

                // Show VM details
                document.querySelector('.loading-spinner').style.display = 'none';
                document.querySelector('.vm-details').classList.add('loaded');
            } catch (error) {
                console.error('Error loading VM details:', error);
                alert('Failed to load virtual machine details: ' + error.message);
            }
        }

        // Update VM state
        async function updateVMState(newState) {
            if (isUpdating) return;
            isUpdating = true;

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=updateVirt&id=${virtId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        state: newState
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to update virtual machine state');
                }

                // Reload VM details
                await loadVMDetails();
            } catch (error) {
                console.error('Error updating VM state:', error);
                alert('Failed to update virtual machine state: ' + error.message);
            } finally {
                isUpdating = false;
            }
        }

        // Delete VM
        async function deleteVM() {
            if (!confirm('Are you sure you want to delete this virtual machine?')) {
                return;
            }

            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=deleteRestore', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'vm',
                        id: virtId
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to delete virtual machine');
                }

                window.location.href = 'restores.php';
            } catch (error) {
                console.error('Error deleting VM:', error);
                alert('Failed to delete virtual machine: ' + error.message);
            }
        }

        // Add event listeners
        document.getElementById('startButton').addEventListener('click', () => updateVMState('running'));
        document.getElementById('pauseButton').addEventListener('click', () => updateVMState('paused'));
        document.getElementById('resumeButton').addEventListener('click', () => updateVMState('running'));
        document.getElementById('stopButton').addEventListener('click', () => updateVMState('stopped'));
        document.getElementById('deleteButton').addEventListener('click', deleteVM);

        // Initial load
        loadVMDetails();


        // Refresh VM details every 5 seconds
        setInterval(loadVMDetails, 5000);
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 