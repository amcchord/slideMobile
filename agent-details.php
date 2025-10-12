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
    <title>Agent Details - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .page-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--bs-body-bg);
            transform: translateX(100%);
            transition: transform 0.3s ease-out;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .page-container.active {
            transform: translateX(0);
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav + safe area */
        }

        .details-section {
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .details-section h6 {
            color: rgba(255, 255, 255, 0.75);
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .details-value {
            word-break: break-word;
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            padding: 1rem;
        }

        .loading .loading-spinner {
            display: flex;
        }

        #restoreModal .btn {
            text-align: left;
            padding: 1rem;
        }

        #restoreModal .btn small {
            display: block;
            opacity: 0.85;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="page-container d-flex flex-column min-vh-100">
        <!-- Header -->
        <header class="navbar bg-dark border-bottom">
            <div class="container-fluid">
                <button class="btn btn-link text-white" onclick="goBack()">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <span class="navbar-brand mb-0 h1">Agent Details</span>
            </div>
        </header>

        <!-- Action Buttons -->
        <div class="bg-dark border-bottom">
            <div class="container-fluid d-flex gap-2 p-2">
                <button class="btn btn-primary flex-grow-1" onclick="backupNow()">
                    <i class="bi bi-play-fill me-2"></i>Backup Now
                </button>
                <button class="btn btn-outline-primary flex-grow-1" onclick="showRestoreModal()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Restore
                </button>
                <button class="btn btn-outline-primary flex-grow-1" onclick="viewSnapshots()">
                    <i class="bi bi-camera me-2"></i>Snapshots
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-grow-1 main-content">
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div id="agentDetails">
                <!-- Agent details will be loaded here -->
            </div>
        </main>

        <!-- Bottom Navigation -->
        <?php include 'include/bottomNav.php'; ?>
    </div>

    <!-- Restore Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title">Start Restore</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="snapshotSelect" class="form-label">Select Snapshot</label>
                        <select class="form-select" id="snapshotSelect">
                            <option value="">Loading snapshots...</option>
                        </select>
                        <small class="text-muted">Choose the snapshot to restore from</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Restore Type</label>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="startRestoreType('file')">
                                <i class="bi bi-file-earmark me-2"></i>File Restore
                                <div><small>Browse and download individual files</small></div>
                            </button>
                            <button class="btn btn-outline-primary" onclick="startRestoreType('image')">
                                <i class="bi bi-hdd me-2"></i>Image Export
                                <div><small>Export disk images (VHDX, VMDK, etc.)</small></div>
                            </button>
                            <button class="btn btn-outline-primary" onclick="startRestoreType('vm')">
                                <i class="bi bi-play-circle me-2"></i>Virtual Machine
                                <div><small>Boot as a VM for testing or disaster recovery</small></div>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let isLoading = false;
        let currentAgentId = null;
        let refreshInterval;

        // Show page with animation
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelector('.page-container').classList.add('active');
            }, 100);
        });

        function goBack() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            document.querySelector('.page-container').classList.remove('active');
            setTimeout(() => {
                window.location.href = 'agents.php';
            }, 300);
        }

        // Start periodic refresh
        function startRefresh() {
            // Clear any existing interval
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            // Set new interval for backup status only
            refreshInterval = setInterval(updateBackupStatus, 5000);
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

        async function backupNow() {
            if (!currentAgentId) {
                alert('Cannot start backup: No agent ID available');
                return;
            }

            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=startBackup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ agent_id: currentAgentId })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start backup');
                }

                // Update the button immediately to show spinning gear
                const actionButtons = document.querySelector('.container-fluid.d-flex.gap-2');
                if (actionButtons) {
                    const backupButton = `<button class="btn btn-success flex-grow-1">
                        <i class="bi bi-gear-fill spin"></i> Started
                    </button>`;
                    actionButtons.innerHTML = `
                        ${backupButton}
                        <button class="btn btn-outline-primary flex-grow-1" onclick="viewSnapshots()">
                            <i class="bi bi-camera me-2"></i>View Snapshots
                        </button>
                    `;
                }

                // Create backup section immediately
                const newBackupSection = document.createElement('div');
                newBackupSection.className = 'details-section';
                newBackupSection.innerHTML = `
                    <h6>Active Backup</h6>
                    <div class="mb-2">
                        <small class="text-muted">Started</small>
                        <div class="details-value">${formatDate(new Date())}</div>
                    </div>
                `;
                
                // Insert after basic information if not already present
                const existingBackupSection = document.querySelector('.details-section:nth-child(2)');
                if (existingBackupSection && existingBackupSection.querySelector('h6').textContent === 'Active Backup') {
                    existingBackupSection.replaceWith(newBackupSection);
                } else {
                    const basicInfoSection = document.querySelector('.details-section');
                    if (basicInfoSection) {
                        basicInfoSection.after(newBackupSection);
                    }
                }
            } catch (error) {
                console.error('Error starting backup:', error);
                alert('Failed to start backup: ' + error.message);
            }
        }

        // Restore modal functions
        let restoreModalInstance = null;
        let agentSnapshots = [];
        
        async function showRestoreModal() {
            if (!currentAgentId) return;
            
            if (!restoreModalInstance) {
                restoreModalInstance = new bootstrap.Modal(document.getElementById('restoreModal'));
            }
            
            // Show modal
            restoreModalInstance.show();
            
            // Load snapshots for this agent
            const snapshotSelect = document.getElementById('snapshotSelect');
            snapshotSelect.innerHTML = '<option value="">Loading snapshots...</option>';
            snapshotSelect.disabled = true;
            
            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getSnapshots&agent_id=${currentAgentId}&limit=50`);
                const data = await response.json();
                
                if (data.success && data.data && data.data.length > 0) {
                    agentSnapshots = data.data;
                    snapshotSelect.innerHTML = '<option value="">Select a snapshot...</option>';
                    
                    data.data.forEach(snapshot => {
                        const option = document.createElement('option');
                        option.value = snapshot.snapshot_id;
                        option.dataset.deviceId = snapshot.locations && snapshot.locations[0] ? snapshot.locations[0].device_id : '';
                        
                        const date = new Date(snapshot.backup_ended_at);
                        const dateStr = date.toLocaleString();
                        const status = snapshot.verify_boot_status ? ` [${snapshot.verify_boot_status}]` : '';
                        option.textContent = `${dateStr}${status}`;
                        
                        snapshotSelect.appendChild(option);
                    });
                    
                    snapshotSelect.disabled = false;
                    
                    // Auto-select the first (most recent) snapshot
                    if (snapshotSelect.options.length > 1) {
                        snapshotSelect.selectedIndex = 1;
                    }
                } else {
                    snapshotSelect.innerHTML = '<option value="">No snapshots available</option>';
                }
            } catch (error) {
                console.error('Error loading snapshots:', error);
                snapshotSelect.innerHTML = '<option value="">Failed to load snapshots</option>';
            }
        }
        
        function startRestoreType(type) {
            const snapshotSelect = document.getElementById('snapshotSelect');
            const snapshotId = snapshotSelect.value;
            
            if (!snapshotId) {
                alert('Please select a snapshot first');
                return;
            }
            
            const selectedOption = snapshotSelect.options[snapshotSelect.selectedIndex];
            const deviceId = selectedOption.dataset.deviceId;
            
            let url = '';
            if (type === 'file') {
                url = `new-file-restore.php?snapshot_id=${snapshotId}&agent_id=${currentAgentId}`;
            } else if (type === 'image') {
                url = `new-image-restore.php?snapshot_id=${snapshotId}&agent_id=${currentAgentId}`;
            } else if (type === 'vm') {
                url = `new-vm-restore.php?snapshot_id=${snapshotId}&agent_id=${currentAgentId}`;
            }
            
            if (deviceId) {
                url += `&device_id=${deviceId}`;
            }
            
            window.location.href = url;
        }

        function viewSnapshots() {
            if (!currentAgentId) return;
            window.location.href = `snapshots.php?agent_id=${currentAgentId}`;
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            return new Date(dateStr).toLocaleString();
        }

        async function updateBackupStatus() {
            if (!currentAgentId || isLoading) return;

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getAgent&id=${currentAgentId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load agent details');
                }

                const agent = data.data;
                
                // Update action buttons
                updateActionButtons(agent);

                // Update backup section if it exists
                const backupSection = document.querySelector('.details-section:nth-child(2)');
                if (backupSection && backupSection.querySelector('h6').textContent === 'Active Backup') {
                    if (!agent.active_backup) {
                        backupSection.remove();
                    }
                } else if (agent.active_backup) {
                    // Create new backup section if it doesn't exist
                    const newBackupSection = document.createElement('div');
                    newBackupSection.className = 'details-section';
                    newBackupSection.innerHTML = `
                        <h6>Active Backup</h6>
                        <div class="mb-2">
                            <small class="text-muted">${agent.active_backup['backup-status'] || agent.active_backup.status.charAt(0).toUpperCase() + agent.active_backup.status.slice(1)}</small>
                            <div class="details-value">${formatDate(agent.active_backup.started_at)}</div>
                        </div>
                    `;
                    
                    // Insert after basic information
                    const basicInfoSection = document.querySelector('.details-section');
                    if (basicInfoSection) {
                        basicInfoSection.after(newBackupSection);
                    }
                }
            } catch (error) {
                console.error('Error updating backup status:', error);
            }
        }

        async function loadAgentDetails() {
            const urlParams = new URLSearchParams(window.location.search);
            const agentId = urlParams.get('id');
            
            if (!agentId) {
                goBack();
                return;
            }

            currentAgentId = agentId;

            if (isLoading) return;
            isLoading = true;
            
            document.querySelector('.loading-spinner').style.display = 'flex';
            const container = document.getElementById('agentDetails');
            container.innerHTML = '';

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getAgent&id=${agentId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load agent details');
                }

                const agent = data.data;
                
                // Update action buttons first
                updateActionButtons(agent);

                // Create sections for different types of information
                const sections = [
                    {
                        title: 'Basic Information',
                        items: [
                            ...(agent.display_name ? [{ label: 'Display Name', value: agent.display_name }] : []),
                            { label: 'Hostname', value: agent.hostname },
                            { label: 'Agent ID', value: agent.agent_id },
                            { label: 'Agent Version', value: agent.agent_version }
                        ]
                    },
                    ...(agent.active_backup ? [{
                        title: 'Active Backup',
                        items: [
                            { 
                                label: agent.active_backup.status.charAt(0).toUpperCase() + agent.active_backup.status.slice(1), 
                                value: formatDate(agent.active_backup.started_at)
                            }
                        ]
                    }] : []),
                    {
                        title: 'Status',
                        items: [
                            { label: 'Last Seen', value: formatDate(agent.last_seen_at) },
                            { label: 'Booted At', value: formatDate(agent.booted_at) }
                        ]
                    },
                    {
                        title: 'Network Information',
                        items: [
                            { label: 'Public IP', value: agent.public_ip_address || 'N/A' },
                            { label: 'Local IPs', value: (agent.ip_addresses || []).join(', ') || 'N/A' },
                            { label: 'MAC Address', value: agent.addresses?.[0]?.mac || 'N/A' }
                        ]
                    },
                    {
                        title: 'System Information',
                        items: [
                            { label: 'Platform', value: agent.platform },
                            { label: 'OS', value: `${agent.os} ${agent.os_version}` },
                            { label: 'Manufacturer', value: agent.manufacturer },
                            { label: 'Firmware Type', value: agent.firmware_type }
                        ]
                    }
                ];

                // Render sections
                sections.forEach(section => {
                    const sectionEl = document.createElement('div');
                    sectionEl.className = 'details-section';
                    
                    let sectionHtml = `<h6>${section.title}</h6>`;
                    section.items.forEach(item => {
                        sectionHtml += `
                            <div class="mb-2">
                                <small class="text-muted">${item.label}</small>
                                <div class="details-value ${item.className || ''}">${item.value}</div>
                            </div>
                        `;
                    });
                    
                    sectionEl.innerHTML = sectionHtml;
                    container.appendChild(sectionEl);
                });

            } catch (error) {
                console.error('Error loading agent details:', error);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load agent details. Please try again.
                    </div>
                `;
            } finally {
                isLoading = false;
                document.querySelector('.loading-spinner').style.display = 'none';
            }
        }

        // Update action buttons based on backup status
        function updateActionButtons(agent) {
            const actionButtons = document.querySelector('.container-fluid.d-flex.gap-2');
            if (!actionButtons) return;

            const backupButton = agent.active_backup ? 
                `<button class="btn ${agent.active_backup.status === 'failed' ? 'btn-warning' : 'btn-success'} flex-grow-1">
                    ${agent.active_backup.status === 'started' ? 
                                '<i class="bi bi-gear-fill spin"></i>' : 
                                '<i class="bi bi-exclamation-triangle"></i>'
                    } ${agent.active_backup['backup-status'] || agent.active_backup.status.charAt(0).toUpperCase() + agent.active_backup.status.slice(1)}
                </button>` :
                `<button class="btn btn-primary flex-grow-1" onclick="backupNow()">
                    <i class="bi bi-play-fill me-2"></i>Backup Now
                </button>`;

            actionButtons.innerHTML = `
                ${backupButton}
                <button class="btn btn-outline-primary flex-grow-1" onclick="showRestoreModal()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Restore
                </button>
                <button class="btn btn-outline-primary flex-grow-1" onclick="viewSnapshots()">
                    <i class="bi bi-camera me-2"></i>Snapshots
                </button>
            `;
        }

        // Initial load
        loadAgentDetails();
        startRefresh();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 