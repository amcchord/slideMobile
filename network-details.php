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
    <title>Network Details - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .content-area {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }
        .info-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 0.875rem;
            color: var(--bs-secondary);
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 500;
        }
        .resource-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .resource-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            position: relative;
        }
        .resource-item h6 {
            margin-bottom: 10px;
        }
        .resource-details {
            font-size: 0.875rem;
            color: var(--bs-secondary);
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-overlay.show {
            display: flex;
        }
        .vm-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .vm-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        .vm-status {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .vm-status.running {
            background: var(--bs-success);
        }
        .vm-status.stopped {
            background: var(--bs-danger);
        }
        .vm-status.paused {
            background: var(--bs-warning);
        }
        .action-buttons {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 5px;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .edit-form {
            display: none;
        }
        .edit-form.active {
            display: block;
        }
        .view-mode {
            display: block;
        }
        .view-mode.editing {
            display: none;
        }
        .wg-config-display {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 0.875rem;
            margin-top: 10px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .copy-button {
            position: absolute;
            top: 5px;
            right: 5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .config-container {
            position: relative;
        }
        .public-key-display {
            cursor: pointer;
            text-decoration: underline;
            text-decoration-style: dotted;
            color: var(--bs-primary);
            display: inline-block;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 576px) {
            .public-key-display {
                max-width: 120px;
            }
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .modal-form .form-label {
            font-weight: 500;
        }
        .network-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .network-item {
            display: flex;
            align-items: center;
            padding: 5px 0;
        }
        .network-item i {
            margin-right: 8px;
            color: var(--bs-secondary);
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
            <span class="navbar-brand mb-0 h1" id="networkTitle">Network Details</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container py-4">
            <!-- Loading State -->
            <div class="text-center py-5" id="loadingState">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading network details...</p>
            </div>

            <!-- Error State -->
            <div class="alert alert-danger" id="errorState" style="display: none;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <span id="errorMessage">Failed to load network details</span>
            </div>

            <!-- Network Details -->
            <div id="networkContent" style="display: none;">
                <!-- Basic Information -->
                <div class="info-section">
                    <div class="section-header">
                        <h5>Network Information</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="toggleEditMode()">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                    </div>
                    
                    <!-- View Mode -->
                    <div class="view-mode" id="viewMode">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Network ID</span>
                                <span class="info-value" id="networkId"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Type</span>
                                <span class="info-value" id="networkType"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Client</span>
                                <span class="info-value" id="networkClient">No client assigned</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Comments</span>
                                <span class="info-value" id="networkComments">No comments</span>
                            </div>
                        </div>
                        
                        <!-- Standard Network Specific Info -->
                        <div id="standardNetworkInfo" style="display: none;">
                            <hr class="my-3">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Router IP/Subnet</span>
                                    <span class="info-value" id="routerPrefix"></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">DHCP</span>
                                    <span class="info-value" id="dhcpStatus"></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">DHCP Range</span>
                                    <span class="info-value" id="dhcpRange">N/A</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Internet Access</span>
                                    <span class="info-value" id="internetStatus"></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">DNS Servers</span>
                                    <span class="info-value" id="nameservers"></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">WireGuard</span>
                                    <span class="info-value" id="wgStatus"></span>
                                </div>
                            </div>
                            <div id="wgInfo" style="display: none;">
                                <div class="info-grid mt-3">
                                    <div class="info-item">
                                        <span class="info-label">WireGuard Subnet</span>
                                        <span class="info-value" id="wgPrefix"></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">WireGuard Public Key</span>
                                        <span class="info-value">
                                            <span id="wgPublicKey" class="public-key-display" onclick="showPublicKeyModal()" title="Click to view full key"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bridge Network Specific Info -->
                        <div id="bridgeNetworkInfo" style="display: none;">
                            <hr class="my-3">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Bridge Device</span>
                                    <span class="info-value" id="bridgeDevice"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div class="edit-form" id="editForm">
                        <form id="networkEditForm">
                            <div class="mb-3">
                                <label for="editName" class="form-label">Network Name</label>
                                <input type="text" class="form-control" id="editName" maxlength="128">
                            </div>
                            <div class="mb-3">
                                <label for="editComments" class="form-label">Comments</label>
                                <textarea class="form-control" id="editComments" rows="2" maxlength="1024"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editClient" class="form-label">Client</label>
                                <select class="form-select" id="editClient">
                                    <option value="">No client assigned</option>
                                </select>
                            </div>
                            
                            <!-- Standard Network Edit Fields -->
                            <div id="standardEditFields" style="display: none;">
                                <hr>
                                <div class="mb-3">
                                    <label for="editRouterPrefix" class="form-label">Router IP/Subnet</label>
                                    <input type="text" class="form-control" id="editRouterPrefix" placeholder="10.0.0.1/24">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="editDhcp">
                                        <label class="form-check-label" for="editDhcp">Enable DHCP</label>
                                    </div>
                                </div>
                                <div id="editDhcpRange" style="display: none;">
                                    <label class="form-label">DHCP Range</label>
                                    <div class="row g-2 mb-3">
                                        <div class="col">
                                            <input type="text" class="form-control" id="editDhcpStart" placeholder="Start IP">
                                        </div>
                                        <div class="col-auto align-self-center">to</div>
                                        <div class="col">
                                            <input type="text" class="form-control" id="editDhcpEnd" placeholder="End IP">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="editInternet">
                                        <label class="form-check-label" for="editInternet">Enable Internet Access</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="editNameservers" class="form-label">DNS Servers</label>
                                    <input type="text" class="form-control" id="editNameservers" placeholder="1.1.1.1, 1.0.0.1">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="editWg">
                                        <label class="form-check-label" for="editWg">Enable WireGuard</label>
                                    </div>
                                </div>
                                <div id="editWgPrefix" style="display: none;" class="mb-3">
                                    <label for="editWgPrefixInput" class="form-label">WireGuard Subnet</label>
                                    <input type="text" class="form-control" id="editWgPrefixInput" placeholder="192.168.178.1/24">
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <button type="button" class="btn btn-secondary" onclick="toggleEditMode()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Connected VMs -->
                <div class="info-section">
                    <h5>Connected Virtual Machines</h5>
                    <div id="vmList">
                        <p class="text-muted">No virtual machines connected to this network.</p>
                    </div>
                </div>

                <!-- IPsec Connections (Standard Networks Only) -->
                <div class="info-section" id="ipsecSection" style="display: none;">
                    <div class="section-header">
                        <h5>IPsec Connections</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="showIpsecModal()">
                            <i class="bi bi-plus-circle me-1"></i>Add
                        </button>
                    </div>
                    <div id="ipsecList">
                        <p class="text-muted">No IPsec connections configured.</p>
                    </div>
                </div>

                <!-- Port Forwards (Standard Networks Only) -->
                <div class="info-section" id="portForwardSection" style="display: none;">
                    <div class="section-header">
                        <h5>Port Forwards</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="showPortForwardModal()">
                            <i class="bi bi-plus-circle me-1"></i>Add
                        </button>
                    </div>
                    <div id="portForwardList">
                        <p class="text-muted">No port forwards configured.</p>
                    </div>
                </div>

                <!-- WireGuard Peers (Standard Networks Only) -->
                <div class="info-section" id="wgPeerSection" style="display: none;">
                    <div class="section-header">
                        <h5>WireGuard Peers</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="showWgPeerModal()">
                            <i class="bi bi-plus-circle me-1"></i>Add
                        </button>
                    </div>
                    <div id="wgPeerList">
                        <p class="text-muted">No WireGuard peers configured.</p>
                    </div>
                </div>

                <!-- Delete Network Button -->
                <div class="d-grid gap-2 mt-4">
                    <button class="btn btn-danger" onclick="deleteNetwork()">
                        <i class="bi bi-trash me-2"></i>Delete Network
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="text-light" id="loadingText">Processing...</div>
        </div>
    </div>

    <!-- IPsec Modal -->
    <div class="modal fade" id="ipsecModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ipsecModalTitle">Add IPsec Connection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="ipsecForm">
                        <input type="hidden" id="ipsecId">
                        <div class="mb-3">
                            <label for="ipsecName" class="form-label">Connection Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ipsecName" required>
                        </div>
                        <div class="mb-3">
                            <label for="ipsecRemoteAddrs" class="form-label">Remote IP Addresses <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ipsecRemoteAddrs" placeholder="1.2.3.4, 5.6.7.8" required>
                            <div class="form-text">Comma-separated list of public IP addresses</div>
                        </div>
                        <div class="mb-3">
                            <label for="ipsecRemoteNetworks" class="form-label">Remote Networks <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ipsecRemoteNetworks" placeholder="10.0.0.0/24, 10.10.0.0/24" required>
                            <div class="form-text">Comma-separated list of networks in CIDR notation</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveIpsec()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Port Forward Modal -->
    <div class="modal fade" id="portForwardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="portForwardModalTitle">Add Port Forward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="portForwardForm">
                        <input type="hidden" id="portForwardId">
                        <div class="mb-3">
                            <label for="portForwardProto" class="form-label">Protocol <span class="text-danger">*</span></label>
                            <select class="form-select" id="portForwardProto" required>
                                <option value="tcp">TCP</option>
                                <option value="udp">UDP</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="portForwardDest" class="form-label">Destination <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="portForwardDest" placeholder="10.0.0.100:80" required>
                            <div class="form-text">Internal IP address and port (e.g., 10.0.0.100:80)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePortForward()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- WireGuard Public Key Modal -->
    <div class="modal fade" id="wgPublicKeyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">WireGuard Public Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This is the network's WireGuard public key. Peers will need this key to connect to the network:</p>
                    <div class="config-container">
                        <button class="btn btn-sm btn-outline-secondary copy-button" onclick="copyPublicKey()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                        <div class="wg-config-display" id="publicKeyDisplay" style="word-break: break-all;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- WireGuard Peer Modal -->
    <div class="modal fade" id="wgPeerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="wgPeerModalTitle">Add WireGuard Peer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="wgPeerForm">
                        <input type="hidden" id="wgPeerId">
                        <div class="mb-3">
                            <label for="wgPeerName" class="form-label">Peer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="wgPeerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="wgPeerRemoteNetworks" class="form-label">Remote Networks</label>
                            <input type="text" class="form-control" id="wgPeerRemoteNetworks" placeholder="10.80.0.0/24, 10.80.1.0/24">
                            <div class="form-text">Comma-separated list of networks this peer can access (optional)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveWgPeer()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        let networkData = null;
        let clients = [];
        const networkId = new URLSearchParams(window.location.search).get('id');

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            if (!networkId) {
                showError('No network ID specified');
                return;
            }
            loadNetworkDetails();
            loadClients();
        });

        // Load network details
        async function loadNetworkDetails() {
            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getNetwork&id=${networkId}`);
                const data = await response.json();

                if (!data.success) {
                    showError(data.message || 'Failed to load network details');
                    return;
                }

                networkData = data.data;
                displayNetworkDetails();

                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('networkContent').style.display = 'block';

            } catch (error) {
                console.error('Error loading network details:', error);
                showError('Failed to load network details: ' + error.message);
            }
        }

        // Display network details
        function displayNetworkDetails() {
            // Update page title
            document.getElementById('networkTitle').textContent = networkData.name;
            
            // Basic info
            document.getElementById('networkId').textContent = networkData.network_id;
            document.getElementById('networkType').textContent = networkData.type === 'bridge-lan' ? 'Bridge to LAN' : 'Standard';
            document.getElementById('networkComments').textContent = networkData.comments || 'No comments';
            
            // Show type-specific info
            if (networkData.type === 'standard') {
                displayStandardNetworkInfo();
                document.getElementById('standardNetworkInfo').style.display = 'block';
                document.getElementById('bridgeNetworkInfo').style.display = 'none';
                
                // Show standard network sections
                document.getElementById('ipsecSection').style.display = 'block';
                document.getElementById('portForwardSection').style.display = 'block';
                if (networkData.wg) {
                    document.getElementById('wgPeerSection').style.display = 'block';
                }
                
                // Display sub-resources
                displayIpsecConnections();
                displayPortForwards();
                if (networkData.wg) {
                    displayWgPeers();
                }
            } else {
                displayBridgeNetworkInfo();
                document.getElementById('standardNetworkInfo').style.display = 'none';
                document.getElementById('bridgeNetworkInfo').style.display = 'block';
                
                // Hide standard network sections
                document.getElementById('ipsecSection').style.display = 'none';
                document.getElementById('portForwardSection').style.display = 'none';
                document.getElementById('wgPeerSection').style.display = 'none';
            }
            
            // Display connected VMs
            displayConnectedVMs();
        }

        // Display standard network info
        function displayStandardNetworkInfo() {
            document.getElementById('routerPrefix').textContent = networkData.router_prefix || 'Not configured';
            document.getElementById('dhcpStatus').innerHTML = networkData.dhcp ? 
                '<span class="badge bg-success">Enabled</span>' : 
                '<span class="badge bg-secondary">Disabled</span>';
            
            if (networkData.dhcp && networkData.dhcp_range_start && networkData.dhcp_range_end) {
                document.getElementById('dhcpRange').textContent = `${networkData.dhcp_range_start} - ${networkData.dhcp_range_end}`;
            } else {
                document.getElementById('dhcpRange').textContent = 'N/A';
            }
            
            document.getElementById('internetStatus').innerHTML = networkData.internet ? 
                '<span class="badge bg-success">Enabled</span>' : 
                '<span class="badge bg-secondary">Disabled</span>';
            
            document.getElementById('nameservers').textContent = networkData.nameservers?.join(', ') || 'Not configured';
            
            document.getElementById('wgStatus').innerHTML = networkData.wg ? 
                '<span class="badge bg-success">Enabled</span>' : 
                '<span class="badge bg-secondary">Disabled</span>';
            
            if (networkData.wg) {
                document.getElementById('wgInfo').style.display = 'block';
                document.getElementById('wgPrefix').textContent = networkData.wg_prefix || 'Not configured';
                document.getElementById('wgPublicKey').textContent = networkData.wg_public_key || 'Not available';
            }
        }

        // Display bridge network info
        function displayBridgeNetworkInfo() {
            document.getElementById('bridgeDevice').textContent = networkData.bridge_device_id || 'Not configured';
        }

        // Display connected VMs
        function displayConnectedVMs() {
            const vmList = document.getElementById('vmList');
            
            if (!networkData.connected_vms || networkData.connected_vms.length === 0) {
                vmList.innerHTML = '<p class="text-muted">No virtual machines connected to this network.</p>';
                return;
            }
            
            vmList.innerHTML = networkData.connected_vms.map(vm => `
                <div class="vm-item" onclick="window.location.href='manage-vm.php?id=${vm.virt_id}'">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">
                                <span class="vm-status ${vm.state}"></span>
                                ${vm.agent_display_name || vm.agent_hostname || 'Unknown VM'}
                            </h6>
                            <div class="resource-details">
                                <div>State: ${vm.state}</div>
                                <div>CPU: ${vm.cpu_count} cores, RAM: ${vm.memory_in_mb} MB</div>
                                <div>Created: ${new Date(vm.created_at).toLocaleString()}</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </div>
            `).join('');
        }

        // Display IPsec connections
        function displayIpsecConnections() {
            const ipsecList = document.getElementById('ipsecList');
            
            if (!networkData.ipsec_conns || networkData.ipsec_conns.length === 0) {
                ipsecList.innerHTML = '<p class="text-muted">No IPsec connections configured.</p>';
                return;
            }
            
            ipsecList.innerHTML = '<ul class="resource-list">' + 
                networkData.ipsec_conns.map(conn => `
                    <li class="resource-item">
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-outline-primary" onclick="editIpsec('${conn.ipsec_id}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteIpsec('${conn.ipsec_id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <h6>${conn.name}</h6>
                        <div class="resource-details">
                            <div><strong>Local ID:</strong> ${conn.local_id}</div>
                            <div><strong>Remote ID:</strong> ${conn.remote_id}</div>
                            <div><strong>PSK:</strong> <code>${conn.psk}</code></div>
                            <div class="mt-2">
                                <strong>Local Networks:</strong>
                                <ul class="network-list mt-1">
                                    ${conn.local_networks.map(net => `<li><i class="bi bi-diagram-2"></i>${net}</li>`).join('')}
                                </ul>
                            </div>
                            <div class="mt-2">
                                <strong>Remote Networks:</strong>
                                <ul class="network-list mt-1">
                                    ${conn.remote_networks.map(net => `<li><i class="bi bi-diagram-2"></i>${net}</li>`).join('')}
                                </ul>
                            </div>
                            <div class="mt-2">
                                <strong>Gateway IPs:</strong> ${conn.local_addrs.join(', ')}
                            </div>
                            <div><strong>Remote IPs:</strong> ${conn.remote_addrs.join(', ')}</div>
                        </div>
                    </li>
                `).join('') + '</ul>';
        }

        // Display port forwards
        function displayPortForwards() {
            const portForwardList = document.getElementById('portForwardList');
            
            if (!networkData.port_forwards || networkData.port_forwards.length === 0) {
                portForwardList.innerHTML = '<p class="text-muted">No port forwards configured.</p>';
                return;
            }
            
            portForwardList.innerHTML = '<ul class="resource-list">' + 
                networkData.port_forwards.map(pf => `
                    <li class="resource-item">
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-outline-primary" onclick="editPortForward('${pf.port_forward_id}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deletePortForward('${pf.port_forward_id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <h6>${pf.proto.toUpperCase()} Port ${pf.port}</h6>
                        <div class="resource-details">
                            <div><strong>Public Endpoint:</strong> <code>${pf.endpoint || 'Pending...'}</code></div>
                            <div><strong>Destination:</strong> ${pf.dest}</div>
                        </div>
                    </li>
                `).join('') + '</ul>';
        }

        // Display WireGuard peers
        function displayWgPeers() {
            const wgPeerList = document.getElementById('wgPeerList');
            
            if (!networkData.wg_peers || networkData.wg_peers.length === 0) {
                wgPeerList.innerHTML = '<p class="text-muted">No WireGuard peers configured.</p>';
                return;
            }
            
            wgPeerList.innerHTML = '<ul class="resource-list">' + 
                networkData.wg_peers.map(peer => `
                    <li class="resource-item">
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-outline-primary" onclick="editWgPeer('${peer.wg_peer_id}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteWgPeer('${peer.wg_peer_id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="showWgConfig('${peer.wg_peer_id}')">
                                <i class="bi bi-file-earmark-text"></i>
                            </button>
                        </div>
                        <h6>${peer.peer_name}</h6>
                        <div class="resource-details">
                            <div><strong>WireGuard Address:</strong> ${peer.wg_address}</div>
                            <div><strong>Endpoint:</strong> ${peer.wg_endpoint}</div>
                            ${peer.remote_networks && peer.remote_networks.length > 0 ? `
                                <div class="mt-2">
                                    <strong>Remote Networks:</strong>
                                    <ul class="network-list mt-1">
                                        ${peer.remote_networks.map(net => `<li><i class="bi bi-diagram-2"></i>${net}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    </li>
                `).join('') + '</ul>';
        }

        // Toggle edit mode
        function toggleEditMode() {
            const viewMode = document.getElementById('viewMode');
            const editForm = document.getElementById('editForm');
            
            if (editForm.classList.contains('active')) {
                // Cancel edit
                viewMode.classList.remove('editing');
                editForm.classList.remove('active');
            } else {
                // Enter edit mode
                viewMode.classList.add('editing');
                editForm.classList.add('active');
                populateEditForm();
            }
        }

        // Populate edit form
        function populateEditForm() {
            document.getElementById('editName').value = networkData.name;
            document.getElementById('editComments').value = networkData.comments || '';
            document.getElementById('editClient').value = networkData.client_id || '';
            
            if (networkData.type === 'standard') {
                document.getElementById('standardEditFields').style.display = 'block';
                document.getElementById('editRouterPrefix').value = networkData.router_prefix || '';
                document.getElementById('editDhcp').checked = networkData.dhcp;
                document.getElementById('editDhcpStart').value = networkData.dhcp_range_start || '';
                document.getElementById('editDhcpEnd').value = networkData.dhcp_range_end || '';
                document.getElementById('editInternet').checked = networkData.internet;
                document.getElementById('editNameservers').value = networkData.nameservers?.join(', ') || '';
                document.getElementById('editWg').checked = networkData.wg;
                document.getElementById('editWgPrefixInput').value = networkData.wg_prefix || '';
                
                // Show/hide DHCP range
                document.getElementById('editDhcpRange').style.display = networkData.dhcp ? 'block' : 'none';
                document.getElementById('editDhcp').addEventListener('change', (e) => {
                    document.getElementById('editDhcpRange').style.display = e.target.checked ? 'block' : 'none';
                });
                
                // Show/hide WireGuard prefix
                document.getElementById('editWgPrefix').style.display = networkData.wg ? 'block' : 'none';
                document.getElementById('editWg').addEventListener('change', (e) => {
                    document.getElementById('editWgPrefix').style.display = e.target.checked ? 'block' : 'none';
                });
            } else {
                document.getElementById('standardEditFields').style.display = 'none';
            }
        }

        // Load clients for dropdown
        async function loadClients() {
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getClients');
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    clients = data.data;
                    const select = document.getElementById('editClient');
                    data.data.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.client_id;
                        option.textContent = client.name;
                        select.appendChild(option);
                    });
                    
                    // Update client display if assigned
                    if (networkData && networkData.client_id) {
                        const client = clients.find(c => c.client_id === networkData.client_id);
                        if (client) {
                            document.getElementById('networkClient').textContent = client.name;
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading clients:', error);
            }
        }

        // Save network changes
        document.getElementById('networkEditForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            document.getElementById('loadingText').textContent = 'Saving changes...';
            
            try {
                const payload = {
                    name: document.getElementById('editName').value.trim(),
                    comments: document.getElementById('editComments').value.trim(),
                    client_id: document.getElementById('editClient').value
                };
                
                if (networkData.type === 'standard') {
                    payload.router_prefix = document.getElementById('editRouterPrefix').value.trim();
                    payload.dhcp = document.getElementById('editDhcp').checked;
                    
                    if (payload.dhcp) {
                        payload.dhcp_range_start = document.getElementById('editDhcpStart').value.trim();
                        payload.dhcp_range_end = document.getElementById('editDhcpEnd').value.trim();
                    }
                    
                    payload.internet = document.getElementById('editInternet').checked;
                    
                    const nameservers = document.getElementById('editNameservers').value.trim();
                    if (nameservers) {
                        payload.nameservers = nameservers.split(',').map(s => s.trim());
                    }
                    
                    payload.wg = document.getElementById('editWg').checked;
                    if (payload.wg) {
                        payload.wg_prefix = document.getElementById('editWgPrefixInput').value.trim();
                    }
                }
                
                const response = await fetch(`/mobile/mobileSlideApi.php?action=updateNetwork&id=${networkId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to update network');
                }
                
                // Reload network details
                await loadNetworkDetails();
                toggleEditMode();
                
            } catch (error) {
                console.error('Error updating network:', error);
                alert('Failed to update network: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        });

        // Delete network
        async function deleteNetwork() {
            if (!confirm('Are you sure you want to delete this network? All connected VMs will lose network access.')) {
                return;
            }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            document.getElementById('loadingText').textContent = 'Deleting network...';
            
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
                
                window.location.href = 'restores.php';
                
            } catch (error) {
                console.error('Error deleting network:', error);
                alert('Failed to delete network: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        }

        // IPsec functions
        function showIpsecModal() {
            document.getElementById('ipsecModalTitle').textContent = 'Add IPsec Connection';
            document.getElementById('ipsecForm').reset();
            document.getElementById('ipsecId').value = '';
            new bootstrap.Modal(document.getElementById('ipsecModal')).show();
        }

        function editIpsec(ipsecId) {
            const conn = networkData.ipsec_conns.find(c => c.ipsec_id === ipsecId);
            if (!conn) return;
            
            document.getElementById('ipsecModalTitle').textContent = 'Edit IPsec Connection';
            document.getElementById('ipsecId').value = ipsecId;
            document.getElementById('ipsecName').value = conn.name;
            document.getElementById('ipsecRemoteAddrs').value = conn.remote_addrs.join(', ');
            document.getElementById('ipsecRemoteNetworks').value = conn.remote_networks.join(', ');
            
            new bootstrap.Modal(document.getElementById('ipsecModal')).show();
        }

        async function saveIpsec() {
            const ipsecId = document.getElementById('ipsecId').value;
            const isEdit = !!ipsecId;
            
            const payload = {
                name: document.getElementById('ipsecName').value.trim(),
                remote_addrs: document.getElementById('ipsecRemoteAddrs').value.split(',').map(s => s.trim()).filter(s => s),
                remote_networks: document.getElementById('ipsecRemoteNetworks').value.split(',').map(s => s.trim()).filter(s => s)
            };
            
            if (!payload.name || payload.remote_addrs.length === 0 || payload.remote_networks.length === 0) {
                alert('Please fill in all required fields');
                return;
            }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            document.getElementById('loadingText').textContent = isEdit ? 'Updating IPsec connection...' : 'Creating IPsec connection...';
            
            try {
                const url = isEdit ? 
                    `/mobile/mobileSlideApi.php?action=updateIpsec&networkId=${networkId}&ipsecId=${ipsecId}` :
                    `/mobile/mobileSlideApi.php?action=createIpsec&networkId=${networkId}`;
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save IPsec connection');
                }
                
                bootstrap.Modal.getInstance(document.getElementById('ipsecModal')).hide();
                await loadNetworkDetails();
                
            } catch (error) {
                console.error('Error saving IPsec connection:', error);
                alert('Failed to save IPsec connection: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        }

        async function deleteIpsec(ipsecId) {
            if (!confirm('Are you sure you want to delete this IPsec connection?')) {
                return;
            }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            document.getElementById('loadingText').textContent = 'Deleting IPsec connection...';
            
            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=deleteIpsec&networkId=${networkId}&ipsecId=${ipsecId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to delete IPsec connection');
                }
                
                await loadNetworkDetails();
                
            } catch (error) {
                console.error('Error deleting IPsec connection:', error);
                alert('Failed to delete IPsec connection: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        }

        // Port forward functions
        function showPortForwardModal() {
            document.getElementById('portForwardModalTitle').textContent = 'Add Port Forward';
            document.getElementById('portForwardForm').reset();
            document.getElementById('portForwardId').value = '';
            new bootstrap.Modal(document.getElementById('portForwardModal')).show();
        }

        function editPortForward(portForwardId) {
            const pf = networkData.port_forwards.find(p => p.port_forward_id === portForwardId);
            if (!pf) return;
            
            document.getElementById('portForwardModalTitle').textContent = 'Edit Port Forward';
            document.getElementById('portForwardId').value = portForwardId;
            document.getElementById('portForwardProto').value = pf.proto;
            document.getElementById('portForwardDest').value = pf.dest;
            
            new bootstrap.Modal(document.getElementById('portForwardModal')).show();
        }

        async function savePortForward() {
            const portForwardId = document.getElementById('portForwardId').value;
            const isEdit = !!portForwardId;
            
            const payload = {
                proto: document.getElementById('portForwardProto').value,
                dest: document.getElementById('portForwardDest').value.trim()
            };
            
            if (!payload.dest) {
                alert('Please enter a destination');
                return;
            }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            document.getElementById('loadingText').textContent = isEdit ? 'Updating port forward...' : 'Creating port forward...';
            
            try {
                const url = isEdit ? 
                    `/mobile/mobileSlideApi.php?action=updatePortForward&networkId=${networkId}&portForwardId=${portForwardId}` :
                    `/mobile/mobileSlideApi.php?action=createPortForward&networkId=${networkId}`;
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save port forward');
                }
                
                bootstrap.Modal.getInstance(document.getElementById('portForwardModal')).hide();
                await loadNetworkDetails();
                
            } catch (error) {
                console.error('Error saving port forward:', error);
                alert('Failed to save port forward: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        }

        async function deletePortForward(portForwardId) {
            if (!confirm('Are you sure you want to delete this port forward?')) {
                return;
            }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            document.getElementById('loadingText').textContent = 'Deleting port forward...';
            
            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=deletePortForward&networkId=${networkId}&portForwardId=${portForwardId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to delete port forward');
                }
                
                await loadNetworkDetails();
                
            } catch (error) {
                console.error('Error deleting port forward:', error);
                alert('Failed to delete port forward: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        }

        // WireGuard peer functions
        function showWgPeerModal() {
            document.getElementById('wgPeerModalTitle').textContent = 'Add WireGuard Peer';
            document.getElementById('wgPeerForm').reset();
            document.getElementById('wgPeerId').value = '';
            new bootstrap.Modal(document.getElementById('wgPeerModal')).show();
        }

        function editWgPeer(wgPeerId) {
            const peer = networkData.wg_peers.find(p => p.wg_peer_id === wgPeerId);
            if (!peer) return;
            
            document.getElementById('wgPeerModalTitle').textContent = 'Edit WireGuard Peer';
            document.getElementById('wgPeerId').value = wgPeerId;
            document.getElementById('wgPeerName').value = peer.peer_name;
            document.getElementById('wgPeerRemoteNetworks').value = peer.remote_networks?.join(', ') || '';
            
            new bootstrap.Modal(document.getElementById('wgPeerModal')).show();
        }

        async function saveWgPeer() {
            const wgPeerId = document.getElementById('wgPeerId').value;
            const isEdit = !!wgPeerId;
            
            const payload = {
                peer_name: document.getElementById('wgPeerName').value.trim()
            };
            
            const remoteNetworks = document.getElementById('wgPeerRemoteNetworks').value.trim();
            if (remoteNetworks) {
                payload.remote_networks = remoteNetworks.split(',').map(s => s.trim()).filter(s => s);
            }
            
            if (!payload.peer_name) {
                alert('Please enter a peer name');
                return;
            }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            document.getElementById('loadingText').textContent = isEdit ? 'Updating WireGuard peer...' : 'Creating WireGuard peer...';
            
            try {
                const url = isEdit ? 
                    `/mobile/mobileSlideApi.php?action=updateWgPeer&networkId=${networkId}&wgPeerId=${wgPeerId}` :
                    `/mobile/mobileSlideApi.php?action=createWgPeer&networkId=${networkId}`;
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save WireGuard peer');
                }
                
                bootstrap.Modal.getInstance(document.getElementById('wgPeerModal')).hide();
                await loadNetworkDetails();
                
            } catch (error) {
                console.error('Error saving WireGuard peer:', error);
                alert('Failed to save WireGuard peer: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        }

        async function deleteWgPeer(wgPeerId) {
            if (!confirm('Are you sure you want to delete this WireGuard peer?')) {
                return;
            }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            document.getElementById('loadingText').textContent = 'Deleting WireGuard peer...';
            
            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=deleteWgPeer&networkId=${networkId}&wgPeerId=${wgPeerId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to delete WireGuard peer');
                }
                
                await loadNetworkDetails();
                
            } catch (error) {
                console.error('Error deleting WireGuard peer:', error);
                alert('Failed to delete WireGuard peer: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        }

        // Show WireGuard configuration
        function showWgConfig(wgPeerId) {
            const peer = networkData.wg_peers.find(p => p.wg_peer_id === wgPeerId);
            if (!peer) return;
            
            const config = generateWgConfig(peer);
            
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">WireGuard Configuration - ${peer.peer_name}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Save this configuration as a .conf file and import it into your WireGuard client:</p>
                            <div class="config-container">
                                <button class="btn btn-sm btn-outline-secondary copy-button" onclick="copyToClipboard('${btoa(config)}')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                                <div class="wg-config-display">${config}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }

        // Generate WireGuard config
        function generateWgConfig(peer) {
            const endpoint = peer.wg_endpoint.split(':');
            const serverHost = endpoint[0];
            const serverPort = endpoint[1] || '51820';
            
            let config = `[Interface]
PrivateKey = ${peer.wg_private_key}
Address = ${peer.wg_address}/32

[Peer]
PublicKey = ${networkData.wg_public_key}
Endpoint = ${serverHost}:${serverPort}
AllowedIPs = ${networkData.router_prefix}`;
            
            if (peer.remote_networks && peer.remote_networks.length > 0) {
                config += `, ${peer.remote_networks.join(', ')}`;
            }
            
            config += '\nPersistentKeepalive = 25';
            
            return config;
        }

        // Copy to clipboard
        function copyToClipboard(base64Config) {
            const config = atob(base64Config);
            navigator.clipboard.writeText(config).then(() => {
                alert('Configuration copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy to clipboard');
            });
        }

        // Show public key modal
        function showPublicKeyModal() {
            if (!networkData.wg_public_key) {
                alert('WireGuard public key not available');
                return;
            }
            
            document.getElementById('publicKeyDisplay').textContent = networkData.wg_public_key;
            new bootstrap.Modal(document.getElementById('wgPublicKeyModal')).show();
        }

        // Copy public key
        function copyPublicKey() {
            if (!networkData.wg_public_key) return;
            
            navigator.clipboard.writeText(networkData.wg_public_key).then(() => {
                alert('Public key copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy to clipboard');
            });
        }

        // Show error
        function showError(message) {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('errorState').style.display = 'block';
            document.getElementById('errorMessage').textContent = message;
        }
    </script>
</body>
</html> 