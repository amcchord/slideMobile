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
    <title>Restores - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .restore-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }
        
        .network-list {
            padding: 0.5rem;
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav + safe area */
            min-height: calc(100vh - 120px); /* Ensure full height scrolling */
        }

        .restore-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
        }

        .restore-item:last-child {
            border-bottom: none;
        }
        
        .network-item {
            padding: 1rem;
        }

        .restore-info, .network-info {
            flex-grow: 1;
            min-width: 0; /* Allow text truncation */
        }

        .restore-name, .network-name {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .restore-details, .network-details {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
        }

        .restore-expires {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
            text-align: right;
            white-space: nowrap;
            margin-left: 1rem;
        }

        .delete-button {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin-left: 1rem;
            min-width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .delete-button i {
            font-size: 1rem;
            color: var(--bs-danger);
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            padding: 1rem;
        }

        .loading .loading-spinner {
            display: flex;
        }

        .nav-tabs {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .nav-tabs .nav-link {
            color: rgba(255, 255, 255, 0.6);
            border: none;
            padding: 0.75rem 1.5rem;
        }

        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: transparent;
            border-bottom: 2px solid var(--bs-primary);
        }

        .network-type-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .connection-info {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        
        .network-status-indicators {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.25rem 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.25rem;
            font-size: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
        }
        
        .status-badge i {
            font-size: 0.875rem;
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-left: auto;
            margin-right: 60px;
            align-items: flex-end;
        }
        
        .quick-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            white-space: nowrap;
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .quick-actions .btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .network-item {
            transition: all 0.2s ease;
            position: relative;
            border-radius: 8px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid transparent;
            -webkit-tap-highlight-color: rgba(var(--bs-primary-rgb), 0.1);
        }
        
        .network-item:last-child {
            margin-bottom: 20px; /* Extra space for last item */
        }
        
        .network-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }
        
        .network-item:hover .network-name {
            color: var(--bs-primary);
        }
        
        .vm-count-badge {
            background: rgba(var(--bs-primary-rgb), 0.15);
            color: var(--bs-primary);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-color: rgba(var(--bs-primary-rgb), 0.3);
        }
        
        .internet-badge {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-internet-badge {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.5);
        }
        
        .click-indicator {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            font-size: 1.25rem;
            transition: all 0.2s ease;
            pointer-events: none;
        }
        
        .network-item:hover .click-indicator {
            color: var(--bs-primary);
            transform: translateY(-50%) translateX(3px);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Restores</span>
            <a href="new-restore.php" class="btn btn-primary btn-sm" id="headerAction">
                <i class="bi bi-plus-lg me-1"></i>
                New Restore
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1" style="overflow-y: auto;">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs px-3" id="restoreTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="restores-tab" data-bs-toggle="tab" data-bs-target="#restores" type="button" role="tab" aria-controls="restores" aria-selected="true">
                    Restores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="networks-tab" data-bs-toggle="tab" data-bs-target="#networks" type="button" role="tab" aria-controls="networks" aria-selected="false">
                    Networks
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="restoreTabContent">
            <!-- Restores Tab -->
            <div class="tab-pane fade show active" id="restores" role="tabpanel" aria-labelledby="restores-tab">
                <div class="restore-list" id="restoreList">
                    <!-- Restores will be loaded here -->
                </div>
            </div>

            <!-- Networks Tab -->
            <div class="tab-pane fade" id="networks" role="tabpanel" aria-labelledby="networks-tab">
                <div class="network-list" id="networkList">
                    <!-- Networks will be loaded here -->
                </div>
            </div>
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
        let currentTab = 'restores';
        let isInitialLoad = true;

        // Update header action button based on tab
        document.getElementById('restores-tab').addEventListener('shown.bs.tab', function() {
            currentTab = 'restores';
            const headerAction = document.getElementById('headerAction');
            headerAction.href = 'new-restore.php';
            headerAction.innerHTML = '<i class="bi bi-plus-lg me-1"></i>New Restore';
            loadRestores();
        });

        document.getElementById('networks-tab').addEventListener('shown.bs.tab', function() {
            currentTab = 'networks';
            const headerAction = document.getElementById('headerAction');
            headerAction.href = 'new-network.php';
            headerAction.innerHTML = '<i class="bi bi-plus-lg me-1"></i>New Network';
            loadNetworks();
        });

        // Format date for display
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            return new Date(dateStr).toLocaleString();
        }

        // Format relative time for expiry
        function formatExpiry(expiryDate) {
            if (!expiryDate) return '';
            
            const now = new Date();
            const expiry = new Date(expiryDate);
            const diffMs = expiry - now;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);

            if (diffMins < 0) {
                return 'Expired';
            } else if (diffMins < 60) {
                return `Expires in ${diffMins}m`;
            } else if (diffHours < 24) {
                return `Expires in ${diffHours}h`;
            } else {
                return `Expires in ${diffDays}d`;
            }
        }

        // Handle restore deletion
        window.handleDeleteRestore = async function(event, type, id) {
            event.stopPropagation(); // Prevent navigation for file restores
            
            if (!confirm('Are you sure you want to delete this restore?')) {
                return;
            }

            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=deleteRestore', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: type,
                        id: id
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to delete restore');
                }

                // Refresh the list after successful deletion
                loadRestores();
            } catch (error) {
                console.error('Error deleting restore:', error);
                alert('Failed to delete restore: ' + error.message);
            }
        }

        // Handle network deletion
        window.handleDeleteNetwork = async function(event, networkId) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            if (!confirm('Are you sure you want to delete this network? All connected VMs will lose network access.')) {
                return;
            }

            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=deleteNetwork', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        network_id: networkId
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to delete network');
                }

                // Refresh the list after successful deletion
                loadNetworks();
            } catch (error) {
                console.error('Error deleting network:', error);
                alert('Failed to delete network: ' + error.message);
            }
        }

        // Navigate to network details
        window.viewNetworkDetails = function(networkId) {
            console.log('Navigating to network:', networkId);
            window.location.href = `network-details.php?id=${networkId}`;
        }
        
        // Handle network item click
        function handleNetworkClick(event, networkId) {
            // Don't navigate if clicking on buttons or delete button
            if (event.target.closest('.delete-button')) return;
            if (event.target.closest('.quick-actions')) return;
            
            event.preventDefault();
            event.stopPropagation();
            console.log('Network clicked:', networkId);
            window.viewNetworkDetails(networkId);
        }

        // Load all restores
        async function loadRestores(isBackgroundUpdate = false) {
            if (isLoading || currentTab !== 'restores') return;
            isLoading = true;

            // Only show loading spinner for initial load, not background updates
            if (!isBackgroundUpdate) {
                document.querySelector('.loading-spinner').style.display = 'flex';
                document.getElementById('restoreList').innerHTML = '';
            }

            try {
                // Fetch all three types of restores in parallel
                const [fileResponse, imageResponse, virtResponse] = await Promise.all([
                    fetch('/mobile/mobileSlideApi.php?action=getFileRestores'),
                    fetch('/mobile/mobileSlideApi.php?action=getImageExports'),
                    fetch('/mobile/mobileSlideApi.php?action=getVirtualMachines')
                ]);

                const [fileData, imageData, virtData] = await Promise.all([
                    fileResponse.json(),
                    imageResponse.json(),
                    virtResponse.json()
                ]);

                if (!fileData.success || !imageData.success || !virtData.success) {
                    throw new Error('Failed to load one or more restore types');
                }

                // Combine and sort all restores by creation date
                const allRestores = [
                    ...(fileData.data || []).map(r => ({ ...r, type: 'file' })),
                    ...(imageData.data || []).map(r => ({ ...r, type: 'image' })),
                    ...(virtData.data || []).map(r => ({ ...r, type: 'vm' }))
                ].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                const container = document.getElementById('restoreList');
                
                // Add initial-load class only on first load
                if (isInitialLoad && currentTab === 'restores') {
                    container.classList.add('initial-load');
                    isInitialLoad = false;
                    // Remove the class after animations complete
                    setTimeout(() => {
                        container.classList.remove('initial-load');
                    }, 500);
                }
                
                if (allRestores.length === 0) {
                    // Only update empty state if not a background update or if container is empty
                    if (!isBackgroundUpdate || !container.children.length) {
                        container.innerHTML = `
                            <div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 60vh;">
                                <p class="text-muted mb-4">No active restores</p>
                                <a href="new-restore.php" class="btn btn-outline-primary btn-lg">
                                    <i class="bi bi-plus-lg me-2"></i>
                                    Create New Restore
                                </a>
                            </div>
                        `;
                    }
                    return;
                }

                // Create the new content
                const newContent = document.createElement('div');
                allRestores.forEach(restore => {
                    const restoreEl = document.createElement('div');
                    restoreEl.className = 'restore-item d-flex align-items-start px-3';
                    
                    let clickAttr = '';
                    if (restore.type === 'file') {
                        clickAttr = `style="cursor: pointer" onclick="window.location.href='file-restore-browse.php?id=${restore.file_restore_id}'"`;
                    } else if (restore.type === 'vm') {
                        clickAttr = `style="cursor: pointer" onclick="window.location.href='manage-vm.php?id=${restore.virt_id}'"`;
                    }

                    const typeIcon = {
                        'file': 'bi-folder',
                        'image': 'bi-hdd',
                        'vm': 'bi-display'
                    }[restore.type];

                    restoreEl.innerHTML = `
                        <div class="restore-info" ${clickAttr}>
                            <h6 class="restore-name">
                                <i class="bi ${typeIcon} me-2"></i>
                                ${restore.agent_display_name || restore.agent_hostname || 'Unknown Agent'}
                            </h6>
                            <div class="restore-details">
                                Created ${formatDate(restore.created_at)}
                            </div>
                        </div>
                        ${restore.expires_at ? `
                            <div class="restore-expires">
                                ${formatExpiry(restore.expires_at)}
                            </div>
                        ` : ''}
                        <button class="btn btn-link delete-button" onclick="handleDeleteRestore(event, '${restore.type}', '${restore.type === 'file' ? restore.file_restore_id : restore.type === 'image' ? restore.image_export_id : restore.virt_id}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                    newContent.appendChild(restoreEl);
                });

                // Replace content if different
                if (!isBackgroundUpdate || container.innerHTML !== newContent.innerHTML) {
                    container.innerHTML = newContent.innerHTML;
                }

            } catch (error) {
                console.error('Error loading restores:', error);
                if (!isBackgroundUpdate) {
                    document.getElementById('restoreList').innerHTML = `
                        <div class="text-center text-danger p-4">
                            Failed to load restores: ${error.message}
                        </div>
                    `;
                }
            } finally {
                document.querySelector('.loading-spinner').style.display = 'none';
                isLoading = false;
            }
        }

        // Load all networks
        async function loadNetworks(isBackgroundUpdate = false) {
            if (isLoading || currentTab !== 'networks') return;
            isLoading = true;

            if (!isBackgroundUpdate) {
                document.querySelector('.loading-spinner').style.display = 'flex';
                document.getElementById('networkList').innerHTML = '';
            }

            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getNetworks');
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load networks');
                }

                const networks = data.data || [];
                const container = document.getElementById('networkList');
                
                // Add initial-load class only on first load
                if (isInitialLoad && currentTab === 'networks') {
                    container.classList.add('initial-load');
                    isInitialLoad = false;
                    // Remove the class after animations complete
                    setTimeout(() => {
                        container.classList.remove('initial-load');
                    }, 500);
                }
                
                if (networks.length === 0) {
                    if (!isBackgroundUpdate || !container.children.length) {
                        container.innerHTML = `
                            <div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 60vh;">
                                <i class="bi bi-diagram-3-fill text-muted" style="font-size: 4rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                                <h5 class="text-muted mb-2">No Networks Yet</h5>
                                <p class="text-muted mb-4">Create a network to connect your virtual machines</p>
                                <a href="new-network.php" class="btn btn-primary btn-lg">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Create Your First Network
                                </a>
                            </div>
                        `;
                    }
                    return;
                }

                // Create the new content
                const newContent = document.createElement('div');
                networks.forEach(network => {
                    const networkEl = document.createElement('div');
                    networkEl.className = 'network-item px-3';
                    networkEl.style.cursor = 'pointer';
                    networkEl.style.touchAction = 'manipulation';
                    networkEl.setAttribute('data-network-id', network.network_id);

                    const typeIcon = network.type === 'bridge-lan' ? 'bi-diagram-3' : 'bi-router';
                    const typeBadge = network.type === 'bridge-lan' ? 'Bridge to LAN' : 'Standard';
                    const connectedVMs = network.connected_virt_ids?.length || 0;

                    // Build status indicators
                    let statusIndicators = '';
                    if (network.type === 'standard') {
                        const indicators = [];
                        
                        // VM count
                        if (connectedVMs > 0) {
                            indicators.push(`<span class="status-badge vm-count-badge">
                                <i class="bi bi-display"></i>${connectedVMs} VM${connectedVMs !== 1 ? 's' : ''}
                            </span>`);
                        }
                        
                        // Internet status
                        if (network.internet) {
                            indicators.push(`<span class="status-badge internet-badge">
                                <i class="bi bi-globe"></i>Internet
                            </span>`);
                        } else {
                            indicators.push(`<span class="status-badge no-internet-badge">
                                <i class="bi bi-slash-circle"></i>No Internet
                            </span>`);
                        }
                        
                        // DHCP
                        if (network.dhcp) {
                            indicators.push(`<span class="status-badge">
                                <i class="bi bi-router"></i>DHCP
                            </span>`);
                        }
                        
                        // WireGuard
                        if (network.wg) {
                            const peerCount = network.wg_peers?.length || 0;
                            indicators.push(`<span class="status-badge">
                                <i class="bi bi-shield-lock"></i>WG: ${peerCount}
                            </span>`);
                        }
                        
                        // IPsec
                        if (network.ipsec_conns?.length > 0) {
                            indicators.push(`<span class="status-badge">
                                <i class="bi bi-lock"></i>IPsec: ${network.ipsec_conns.length}
                            </span>`);
                        }
                        
                        // Port forwards
                        if (network.port_forwards?.length > 0) {
                            indicators.push(`<span class="status-badge">
                                <i class="bi bi-arrow-right-circle"></i>Ports: ${network.port_forwards.length}
                            </span>`);
                        }
                        
                        statusIndicators = `<div class="network-status-indicators">${indicators.join('')}</div>`;
                    } else {
                        // Bridge network
                        const indicators = [];
                        
                        if (connectedVMs > 0) {
                            indicators.push(`<span class="status-badge vm-count-badge">
                                <i class="bi bi-display"></i>${connectedVMs} VM${connectedVMs !== 1 ? 's' : ''}
                            </span>`);
                        }
                        
                        indicators.push(`<span class="status-badge">
                            <i class="bi bi-diagram-3"></i>Bridged to ${network.bridge_device_id || 'Unknown'}
                        </span>`);
                        
                        statusIndicators = `<div class="network-status-indicators">${indicators.join('')}</div>`;
                    }

                    // Quick actions removed per user request
                    let quickActions = '';

                    networkEl.innerHTML = `
                        <div class="d-flex align-items-start network-inner">
                            <div class="network-info flex-grow-1">
                                <h6 class="network-name mb-1">
                                    <i class="bi ${typeIcon} me-2"></i>
                                    ${network.name}
                                </h6>
                                <div class="network-details">
                                    <span class="badge network-type-badge bg-secondary">${typeBadge}</span>
                                    ${network.client_id ? `<span class="text-muted ms-2" style="font-size: 0.875rem;">Client assigned</span>` : ''}
                                </div>
                                ${statusIndicators}
                                ${network.type === 'standard' && network.router_prefix ? 
                                    `<div class="text-muted mt-2" style="font-size: 0.875rem;">
                                        <i class="bi bi-diagram-2 me-1"></i>${network.router_prefix}
                                    </div>` : ''
                                }
                            </div>
                            ${quickActions}
                            <button class="btn btn-link delete-button ms-2" data-network-id="${network.network_id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <i class="bi bi-chevron-right click-indicator"></i>
                    `;
                    newContent.appendChild(networkEl);
                });

                if (!isBackgroundUpdate || container.innerHTML !== newContent.innerHTML) {
                    container.innerHTML = newContent.innerHTML;
                }

            } catch (error) {
                console.error('Error loading networks:', error);
                if (!isBackgroundUpdate) {
                    document.getElementById('networkList').innerHTML = `
                        <div class="text-center text-danger p-4">
                            Failed to load networks: ${error.message}
                        </div>
                    `;
                }
            } finally {
                document.querySelector('.loading-spinner').style.display = 'none';
                isLoading = false;
            }
        }

        // Start periodic background updates
        function startBackgroundUpdates() {
            // Initial load based on current tab
            if (currentTab === 'restores') {
                loadRestores(false);
            } else {
                loadNetworks(false);
            }
            
            // Set up periodic background updates
            refreshInterval = setInterval(() => {
                if (currentTab === 'restores') {
                    loadRestores(true);
                } else {
                    loadNetworks(true);
                }
            }, 5000); // Update every 5 seconds
        }

        // Start updates when page loads
        startBackgroundUpdates();

        // Clean up interval when page is unloaded
        window.addEventListener('unload', () => {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
        
        // Add event delegation for network items
        const networkListElement = document.getElementById('networkList');
        
        networkListElement.addEventListener('click', function(event) {
            // Handle delete button clicks
            const deleteBtn = event.target.closest('.delete-button');
            if (deleteBtn) {
                event.preventDefault();
                event.stopPropagation();
                const networkId = deleteBtn.getAttribute('data-network-id');
                if (networkId) {
                    window.handleDeleteNetwork(event, networkId);
                }
                return;
            }
            
            // Handle network item clicks
            const networkItem = event.target.closest('.network-item');
            if (!networkItem) return;
            
            // Don't navigate if clicking on buttons
            if (event.target.closest('.quick-actions')) return;
            
            const networkId = networkItem.getAttribute('data-network-id');
            if (networkId) {
                console.log('Network item clicked:', networkId);
                window.viewNetworkDetails(networkId);
            }
        });
        
        // Quick add WireGuard peer
        async function quickAddWgPeer(networkId, networkName) {
            const peerName = prompt(`Enter a name for the new WireGuard peer on "${networkName}":`);
            if (!peerName) return;
            
            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=createWgPeer&networkId=${networkId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        peer_name: peerName
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to create WireGuard peer');
                }
                
                alert('WireGuard peer created successfully! View network details to see the configuration.');
                loadNetworks();
            } catch (error) {
                console.error('Error creating WireGuard peer:', error);
                alert('Failed to create WireGuard peer: ' + error.message);
            }
        }
        
        // Quick add port forward
        async function quickAddPortForward(networkId, networkName) {
            const dest = prompt(`Enter destination for port forward on "${networkName}" (e.g., 10.0.0.100:80):`);
            if (!dest) return;
            
            const proto = confirm('Use TCP protocol? (Cancel for UDP)') ? 'tcp' : 'udp';
            
            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=createPortForward&networkId=${networkId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        proto: proto,
                        dest: dest
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to create port forward');
                }
                
                alert('Port forward created successfully!');
                loadNetworks();
            } catch (error) {
                console.error('Error creating port forward:', error);
                alert('Failed to create port forward: ' + error.message);
            }
        }
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 