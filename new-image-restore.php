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
    <title>New Image Restore - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        /* Add padding for the top safe area */
        header.navbar {
            padding-top: env(safe-area-inset-top);
            background-color: var(--bs-dark) !important; /* Ensure dark background extends into safe area */
        }

        /* Ensure the body background extends into safe areas */
        body {
            background-color: var(--bs-dark);
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }

        .content-area {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Adjust for safe area */
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
            <span class="navbar-brand mb-0 h1">New Image Export</span>
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

            <!-- Step 3: Configure Export -->
            <div class="step" id="step3">
                <h5 class="mb-4">Configure Export</h5>
                <div class="mb-3">
                    <label for="imageType" class="form-label">Image Format</label>
                    <select class="form-select" id="imageType" required>
                        <option value="vhdx">VHDX (Fixed)</option>
                        <option value="vhdx-dynamic">VHDX (Dynamic)</option>
                        <option value="vhd">VHD</option>
                        <option value="raw">Raw Image</option>
                    </select>
                    <div class="form-text">Select the format for the exported image.</div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between mt-4">
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

        // Handle next button click
        document.getElementById('nextButton').addEventListener('click', async () => {
            if (currentStep === 1) {
                await loadSnapshots(selectedAgent);
                currentStep = 2;
                showStep(2);
            } else if (currentStep === 2) {
                currentStep = 3;
                showStep(3);
            } else if (currentStep === 3) {
                // Create image export
                try {
                    const response = await fetch('/mobile/mobileSlideApi.php?action=createImageExport', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            agent_id: selectedAgent,
                            snapshot_id: selectedSnapshot,
                            image_type: document.getElementById('imageType').value
                        })
                    });

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to create image export');
                    }

                    // Redirect to the restores page
                    window.location.href = 'restores.php';
                } catch (error) {
                    console.error('Error creating image export:', error);
                    alert('Failed to create image export: ' + error.message);
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
                    
                    // Advance to the image type selection step
                    currentStep = 3;
                    showStep(3);
                }
            }
            
            updateProgress();
        }
        
        init();
    </script>
</body>
</html> 