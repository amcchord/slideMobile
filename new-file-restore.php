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
    <title>New File Restore - Slide Mobile</title>
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
            <span class="navbar-brand mb-0 h1">New File Restore</span>
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
        const totalSteps = 2;

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
            nextButton.disabled = (step === 1 && !selectedAgent) || (step === 2 && !selectedSnapshot);
            
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

        // Format bytes to human readable size
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Handle next button click
        document.getElementById('nextButton').addEventListener('click', async () => {
            if (currentStep === 1) {
                await loadSnapshots(selectedAgent);
                currentStep = 2;
                showStep(2);
            } else if (currentStep === 2) {
                // Create file restore
                try {
                    const response = await fetch('/mobile/mobileSlideApi.php?action=createFileRestore', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            agent_id: selectedAgent,
                            snapshot_id: selectedSnapshot
                        })
                    });

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to create file restore');
                    }

                    // Redirect to the file browser
                    window.location.href = `file-restore-browse.php?id=${data.data.file_restore_id}`;
                } catch (error) {
                    console.error('Error creating file restore:', error);
                    alert('Failed to create file restore: ' + error.message);
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
                
                // Update agent info
                const selectedOption = agentSelect.options[agentSelect.selectedIndex];
                if (selectedOption) {
                    const hostname = selectedOption.dataset.hostname;
                    const device = selectedOption.dataset.device;
                    document.getElementById('agentInfo').innerHTML = `
                        <i class="bi bi-laptop me-1"></i>${hostname}<br>
                        <i class="bi bi-hdd me-1"></i>${device}
                    `;
                }
                
                // Load snapshots for this agent
                await loadSnapshots(prefilledAgentId);
                
                // Auto-select snapshot if provided
                if (prefilledSnapshotId) {
                    const snapshotSelect = document.getElementById('snapshotSelect');
                    snapshotSelect.value = prefilledSnapshotId;
                    selectedSnapshot = prefilledSnapshotId;
                    
                    // Update snapshot info
                    const selectedSnapshotOption = snapshotSelect.options[snapshotSelect.selectedIndex];
                    if (selectedSnapshotOption) {
                        const time = selectedSnapshotOption.dataset.time;
                        document.getElementById('snapshotInfo').innerHTML = `
                            <i class="bi bi-clock me-1"></i>${formatDate(time)}
                        `;
                    }
                    
                    // Enable next button and auto-advance to final step
                    document.getElementById('nextButton').disabled = false;
                    document.getElementById('nextButton').textContent = 'Create File Restore';
                    document.getElementById('nextButton').innerHTML = 'Create File Restore<i class="bi bi-check-lg ms-2"></i>';
                    currentStep = 2;
                    showStep(2);
                }
            }
            
            updateProgress();
        }
        
        init();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 