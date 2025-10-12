<?php
require_once 'include/getApiKey.php';
if (!hasApiKey()) {
    header('Location: index.php');
    exit;
}

$snapshotId = isset($_GET['id']) ? $_GET['id'] : '';
if (!$snapshotId) {
    header('Location: snapshots.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Snapshot Details - Slide Mobile</title>
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
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }

        .screenshot-container {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .screenshot-large {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .restore-options {
            margin-top: 1.5rem;
        }

        .restore-option-btn {
            text-align: left;
            padding: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.2s ease-out;
        }

        .restore-option-btn:hover {
            border-color: #5fa3ff;
            background-color: rgba(13, 110, 253, 0.1);
            transform: translateX(4px);
        }

        .restore-option-icon {
            font-size: 2rem;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(13, 110, 253, 0.2);
            border-radius: 8px;
            color: #5fa3ff;
            flex-shrink: 0;
        }

        .restore-option-content {
            flex: 1;
        }

        .restore-option-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #ffffff;
        }

        .restore-option-description {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.75);
            margin: 0;
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
<body>
    <div class="page-container d-flex flex-column min-vh-100">
        <!-- Header -->
        <header class="navbar bg-dark border-bottom">
            <div class="container-fluid">
                <button class="btn btn-link text-white" onclick="goBack()">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <span class="navbar-brand mb-0 h1">Snapshot Details</span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow-1 main-content">
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div id="snapshotDetails">
                <!-- Snapshot details will be loaded here -->
            </div>
        </main>

        <!-- Bottom Navigation -->
        <?php include 'include/bottomNav.php'; ?>
    </div>

    <script>
        let isLoading = false;
        const snapshotId = '<?php echo htmlspecialchars($snapshotId); ?>';

        // Show page with animation
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelector('.page-container').classList.add('active');
            }, 100);
        });

        function goBack() {
            document.querySelector('.page-container').classList.remove('active');
            setTimeout(() => {
                window.location.href = 'snapshots.php';
            }, 300);
        }

        async function loadSnapshotDetails() {
            if (isLoading) return;
            isLoading = true;

            const container = document.getElementById('snapshotDetails');
            document.querySelector('.loading-spinner').style.display = 'flex';

            try {
                console.log('Loading snapshot details for ID:', snapshotId);
                
                let snapshot = null;
                
                // First, try to get snapshot data from sessionStorage
                const cachedSnapshot = sessionStorage.getItem('currentSnapshot');
                if (cachedSnapshot) {
                    try {
                        const parsedSnapshot = JSON.parse(cachedSnapshot);
                        if (parsedSnapshot.snapshot_id === snapshotId || String(parsedSnapshot.snapshot_id) === String(snapshotId)) {
                            console.log('Using cached snapshot data from sessionStorage');
                            snapshot = parsedSnapshot;
                            // Clear the cached data after using it
                            sessionStorage.removeItem('currentSnapshot');
                        }
                    } catch (e) {
                        console.warn('Failed to parse cached snapshot data:', e);
                        sessionStorage.removeItem('currentSnapshot');
                    }
                }
                
                // If not found in cache, fetch from API
                if (!snapshot) {
                    console.log('Fetching snapshot from API (limit 50)');
                    const response = await fetch(`/mobile/mobileSlideApi.php?action=getSnapshots&limit=50`);
                    
                    if (!response.ok) {
                        console.error('API response not OK:', response.status, response.statusText);
                        throw new Error(`API request failed: ${response.status} ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    console.log('API response:', data);

                    if (!data.success) {
                        console.error('API returned error:', data.message);
                        throw new Error(data.message || 'Failed to load snapshot');
                    }

                    if (!data.data || !Array.isArray(data.data)) {
                        console.error('Invalid data format:', data);
                        throw new Error('Invalid data format received from API');
                    }

                    console.log('Looking for snapshot ID:', snapshotId, 'among', data.data.length, 'snapshots');
                    
                    // Find the specific snapshot - try both strict and loose comparison
                    snapshot = data.data.find(s => s.snapshot_id === snapshotId);
                    if (!snapshot) {
                        // Try converting to string for comparison
                        snapshot = data.data.find(s => String(s.snapshot_id) === String(snapshotId));
                    }

                    if (!snapshot) {
                        console.error('Snapshot not found in recent 50 snapshots. Looking for:', snapshotId);
                        throw new Error('Snapshot not found in recent snapshots. Please navigate from the snapshots list.');
                    }
                }
                
                console.log('Found snapshot:', snapshot);

                // Get agent name from snapshot data (already included in the API response)
                const agentName = snapshot.agent_display_name || snapshot.agent_hostname || 'Unknown Agent';

                const locations = snapshot.locations || [];
                const location = locations.length > 0 ? locations[0] : null;
                const deviceId = location ? location.device_id : null;

                const backupDate = snapshot.backup_ended_at ? new Date(snapshot.backup_ended_at) : null;
                const startDate = snapshot.backup_started_at ? new Date(snapshot.backup_started_at) : null;
                const duration = (backupDate && startDate) ? Math.floor((backupDate - startDate) / 1000 / 60) : null;

                container.innerHTML = `
                    ${snapshot.verify_boot_screenshot_url ? `
                        <div class="screenshot-container">
                            <img src="${snapshot.verify_boot_screenshot_url}" class="screenshot-large" alt="Boot Screenshot">
                            ${snapshot.verify_boot_status ? `
                                <div class="mt-2">
                                    <span class="status-badge ${snapshot.verify_boot_status === 'success' ? 'success' : snapshot.verify_boot_status === 'warning' ? 'warning' : snapshot.verify_boot_status === 'error' ? 'danger' : 'secondary'}">
                                        Boot Verification: ${snapshot.verify_boot_status}
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}
                    
                    <div class="info-card">
                        <div class="card-label">Agent</div>
                        <div class="card-value">${agentName}</div>
                    </div>

                    ${backupDate ? `
                        <div class="info-card">
                            <div class="card-label">Backup Completed</div>
                            <div class="card-value">${backupDate.toLocaleString()}</div>
                        </div>
                    ` : ''}

                    ${startDate ? `
                        <div class="info-card">
                            <div class="card-label">Backup Started</div>
                            <div class="card-value">${startDate.toLocaleString()}</div>
                        </div>
                    ` : ''}

                    ${duration !== null ? `
                        <div class="info-card">
                            <div class="card-label">Duration</div>
                            <div class="card-value">${duration} minutes</div>
                        </div>
                    ` : ''}

                    ${snapshot.verify_fs_status ? `
                        <div class="info-card">
                            <div class="card-label">Filesystem Verification</div>
                            <div class="card-value">
                                <span class="status-badge ${snapshot.verify_fs_status === 'success' ? 'success' : snapshot.verify_fs_status === 'warning' ? 'warning' : snapshot.verify_fs_status === 'error' ? 'danger' : 'secondary'}">
                                    ${snapshot.verify_fs_status}
                                </span>
                            </div>
                        </div>
                    ` : ''}

                    ${locations.length > 0 ? `
                        <div class="info-card">
                            <div class="card-label">Locations</div>
                            <div class="card-value">
                                ${locations.map(loc => `
                                    <span class="status-badge ${loc.type === 'local' ? 'info' : 'secondary'} me-1">
                                        <i class="bi bi-${loc.type === 'local' ? 'hdd' : 'cloud'}"></i> ${loc.type}
                                    </span>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}

                    <div class="restore-options">
                        <h5 class="mb-3" style="color: rgba(255, 255, 255, 0.85); font-weight: 600;">Start Restore</h5>
                        
                        <button class="btn btn-outline-primary restore-option-btn w-100" onclick="startRestore('file', '${snapshot.snapshot_id}', '${snapshot.agent_id}', ${deviceId ? `'${deviceId}'` : 'null'})">
                            <div class="restore-option-icon">
                                <i class="bi bi-file-earmark"></i>
                            </div>
                            <div class="restore-option-content">
                                <div class="restore-option-title">File Restore</div>
                                <div class="restore-option-description">Browse and download individual files and folders from this backup</div>
                            </div>
                        </button>

                        <button class="btn btn-outline-primary restore-option-btn w-100" onclick="startRestore('image', '${snapshot.snapshot_id}', '${snapshot.agent_id}', ${deviceId ? `'${deviceId}'` : 'null'})">
                            <div class="restore-option-icon">
                                <i class="bi bi-hdd"></i>
                            </div>
                            <div class="restore-option-content">
                                <div class="restore-option-title">Image Export</div>
                                <div class="restore-option-description">Export full disk images as VHDX, VMDK, QCOW2, or RAW format</div>
                            </div>
                        </button>

                        <button class="btn btn-outline-primary restore-option-btn w-100" onclick="startRestore('vm', '${snapshot.snapshot_id}', '${snapshot.agent_id}', ${deviceId ? `'${deviceId}'` : 'null'})">
                            <div class="restore-option-icon">
                                <i class="bi bi-play-circle"></i>
                            </div>
                            <div class="restore-option-content">
                                <div class="restore-option-title">Virtual Machine</div>
                                <div class="restore-option-description">Boot this backup as a VM for testing or disaster recovery</div>
                            </div>
                        </button>
                    </div>
                `;

            } catch (error) {
                console.error('Error loading snapshot details:', error);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Failed to load snapshot details</strong><br>
                        ${error.message}<br>
                        <small class="mt-2 d-block">Check the browser console for more details.</small>
                    </div>
                `;
            } finally {
                isLoading = false;
                document.querySelector('.loading-spinner').style.display = 'none';
            }
        }

        function startRestore(type, snapshotId, agentId, deviceId) {
            let url = '';
            if (type === 'file') {
                url = `new-file-restore.php?snapshot_id=${snapshotId}&agent_id=${agentId}`;
            } else if (type === 'image') {
                url = `new-image-restore.php?snapshot_id=${snapshotId}&agent_id=${agentId}`;
            } else if (type === 'vm') {
                url = `new-vm-restore.php?snapshot_id=${snapshotId}&agent_id=${agentId}`;
            }
            
            if (deviceId) {
                url += `&device_id=${deviceId}`;
            }
            
            window.location.href = url;
        }

        // Initial load
        loadSnapshotDetails();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

