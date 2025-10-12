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
    <title>Snapshots - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .snapshot-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }

        .snapshot-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            margin-bottom: 0.5rem;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .snapshot-item:last-child {
            border-bottom: none;
        }

        .snapshot-item {
            cursor: pointer;
            transition: all 0.2s ease-out;
        }

        .snapshot-item:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }

        .snapshot-header {
            display: flex;
            align-items: center;
        }

        .snapshot-info {
            flex-grow: 1;
            min-width: 0;
        }

        .snapshot-name {
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .snapshot-device {
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.875rem;
        }

        .snapshot-thumbnail {
            width: 60px;
            height: 45px;
            border-radius: 4px;
            object-fit: cover;
            background-color: rgba(255, 255, 255, 0.1);
            margin-left: 1rem;
            flex-shrink: 0;
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
            <span class="navbar-brand mb-0 h1">Snapshots</span>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <select class="form-select form-select-sm" id="agentFilter">
            <option value="">All Agents</option>
        </select>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="snapshot-list" id="snapshotList">
            <!-- Snapshots will be loaded here -->
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
        let currentOffset = 0;
        let isLoading = false;
        let hasMore = true;
        const LIMIT = 30;
        let allSnapshots = [];
        let selectedAgent = '';
        let isInitialLoad = true;

        // Get agent_id from URL if present
        const urlParams = new URLSearchParams(window.location.search);
        const filterAgentId = urlParams.get('agent_id');

        // Update the agent filter dropdown
        function updateAgentFilter(snapshots) {
            const agentFilter = document.getElementById('agentFilter');
            const agents = new Set();
            
            snapshots.forEach(snapshot => {
                const agentName = snapshot.agent_display_name || snapshot.agent_hostname || 'Unknown Agent';
                agents.add(agentName);
            });

            // Sort agents alphabetically
            const sortedAgents = Array.from(agents).sort();
            
            // Clear existing options except "All Agents"
            while (agentFilter.options.length > 1) {
                agentFilter.remove(1);
            }

            // Add agent options
            sortedAgents.forEach(agent => {
                const option = new Option(agent, agent);
                agentFilter.add(option);
            });

            // If we have a filtered agent_id, select the matching agent
            if (filterAgentId) {
                const matchingSnapshot = snapshots.find(s => s.agent_id === filterAgentId);
                if (matchingSnapshot) {
                    const agentName = matchingSnapshot.agent_display_name || matchingSnapshot.agent_hostname;
                    agentFilter.value = agentName;
                    selectedAgent = agentName;
                }
            }
        }

        // Filter and display snapshots
        function displaySnapshots(snapshots) {
            const container = document.getElementById('snapshotList');
            
            // Add initial-load class only on first load
            if (isInitialLoad) {
                container.classList.add('initial-load');
                isInitialLoad = false;
                // Remove the class after animations complete
                setTimeout(() => {
                    container.classList.remove('initial-load');
                }, 500);
            }
            
            container.innerHTML = ''; // Clear existing snapshots

            snapshots.forEach(snapshot => {
                const agentName = snapshot.agent_display_name || snapshot.agent_hostname || 'Unknown Agent';
                const deviceName = snapshot.device_display_name || snapshot.device_hostname || 'Unknown Device';
                
                // Skip if an agent is selected and doesn't match
                if (selectedAgent && agentName !== selectedAgent) {
                    return;
                }

                // Skip if we're filtering by agent_id and it doesn't match
                if (filterAgentId && snapshot.agent_id !== filterAgentId) {
                    return;
                }
                
                let dateTimeStr = 'Unknown Date';
                if (snapshot.backup_started_at) {
                    try {
                        const date = new Date(snapshot.backup_started_at);
                        if (!isNaN(date)) {
                            dateTimeStr = date.toLocaleString('en-US', {
                                month: 'numeric',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            });
                        }
                    } catch (e) {
                        console.error('Error formatting date:', e);
                    }
                }
                
                const snapshotEl = document.createElement('div');
                snapshotEl.className = 'snapshot-item';
                snapshotEl.onclick = () => showSnapshotDetails(snapshot.snapshot_id);
                snapshotEl.innerHTML = `
                    <div class="snapshot-header">
                        <div class="snapshot-info">
                            <h6 class="snapshot-name">${dateTimeStr} - ${agentName}</h6>
                            <div class="snapshot-device">
                                <i class="bi bi-hdd"></i> ${deviceName}
                                ${snapshot.verify_boot_status ? `<span class="status-badge ${snapshot.verify_boot_status === 'success' ? 'success' : snapshot.verify_boot_status === 'warning' ? 'warning' : snapshot.verify_boot_status === 'error' ? 'danger' : 'secondary'} ms-2">${snapshot.verify_boot_status}</span>` : ''}
                            </div>
                        </div>
                        ${snapshot.verify_boot_screenshot_url ? 
                            `<img src="${snapshot.verify_boot_screenshot_url}" class="snapshot-thumbnail" alt="Screenshot">` :
                            `<div class="snapshot-thumbnail d-flex align-items-center justify-content-center"><i class="bi bi-image text-muted"></i></div>`
                        }
                    </div>
                `;
                container.appendChild(snapshotEl);
            });
        }

        async function loadSnapshots() {
            if (isLoading || !hasMore) return;
            
            isLoading = true;
            document.querySelector('.loading-spinner').style.display = 'flex';

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getSnapshots&offset=${currentOffset}&limit=${LIMIT}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load snapshots');
                }

                const snapshots = data.data;
                hasMore = snapshots.length === LIMIT;
                currentOffset += snapshots.length;

                // Add new snapshots to our full list
                allSnapshots = allSnapshots.concat(snapshots);
                
                // Update agent filter with all known agents
                updateAgentFilter(allSnapshots);
                
                // Display filtered snapshots
                displaySnapshots(allSnapshots);
            } catch (error) {
                console.error('Error loading snapshots:', error);
            } finally {
                isLoading = false;
                document.querySelector('.loading-spinner').style.display = 'none';
            }
        }

        // Show snapshot details
        function showSnapshotDetails(snapshotId) {
            window.location.href = `snapshot-details.php?id=${snapshotId}`;
        }

        // Handle agent filter changes
        document.getElementById('agentFilter').addEventListener('change', (e) => {
            selectedAgent = e.target.value;
            displaySnapshots(allSnapshots);
        });

        // Initial load
        loadSnapshots();

        // Infinite scroll
        window.addEventListener('scroll', () => {
            if ((window.innerHeight + window.scrollY) >= document.documentElement.scrollHeight - 100) {
                loadSnapshots();
            }
        });
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 