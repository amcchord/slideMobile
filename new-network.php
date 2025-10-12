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
    <title>New Network - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .content-area {
            padding-bottom: 100px; /* Space for bottom nav */
        }
        .form-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .network-type-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .network-type-option {
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .network-type-option:hover {
            border-color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), 0.1);
        }
        .network-type-option.selected {
            border-color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), 0.2);
        }
        .network-type-option i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        .config-section {
            display: none;
        }
        .config-section.active {
            display: block;
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
        .switch-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .ip-range-inputs {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 10px;
            align-items: center;
        }
        .error-message {
            color: var(--bs-danger);
            font-size: 0.875rem;
            margin-top: 5px;
        }
        /* Make placeholder text fainter */
        .form-control::placeholder,
        .form-select::placeholder {
            color: rgba(255, 255, 255, 0.3);
            opacity: 1; /* Firefox */
        }
        .form-control::-webkit-input-placeholder,
        .form-select::-webkit-input-placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        .form-control::-moz-placeholder,
        .form-select::-moz-placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        .form-control:-ms-input-placeholder,
        .form-select:-ms-input-placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        /* Smooth transition for auto-fill animation */
        #dhcpRangeStart, #dhcpRangeEnd {
            transition: background-color 0.3s ease;
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
            <span class="navbar-brand mb-0 h1">New Network</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1 content-area">
        <div class="container py-4">
            <form id="networkForm">
                <!-- Network Type Selection -->
                <div class="form-section">
                    <h5>Network Type</h5>
                    <div class="network-type-selector">
                        <div class="network-type-option" data-type="standard">
                            <i class="bi bi-router"></i>
                            <h6>Standard Network</h6>
                            <small class="text-muted">Isolated network with routing and optional internet access</small>
                        </div>
                        <div class="network-type-option" data-type="bridge-lan">
                            <i class="bi bi-diagram-3"></i>
                            <h6>Bridge to LAN</h6>
                            <small class="text-muted">Direct connection to your existing LAN network</small>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="form-section">
                    <h5>Basic Information</h5>
                    <div class="mb-3">
                        <label for="networkName" class="form-label">Network Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="networkName" maxlength="128">
                        <div class="form-text">A descriptive name for this network</div>
                    </div>
                    <div class="mb-3">
                        <label for="networkComments" class="form-label">Comments</label>
                        <textarea class="form-control" id="networkComments" rows="2" maxlength="1024"></textarea>
                        <div class="form-text">Optional notes about this network</div>
                    </div>
                    <div class="mb-3">
                        <label for="clientId" class="form-label">Client</label>
                        <select class="form-select" id="clientId">
                            <option value="">No client assigned</option>
                        </select>
                        <div class="form-text">Optionally assign this network to a client</div>
                    </div>
                </div>

                <!-- Standard Network Configuration -->
                <div class="form-section config-section" id="standardConfig">
                    <h5>Network Configuration</h5>
                    
                    <!-- Router Configuration -->
                    <div class="mb-4">
                        <label for="routerPrefix" class="form-label">Router IP/Subnet <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="routerPrefix" placeholder="e.g., 10.0.0.1/24">
                        <div class="form-text">The router IP address and subnet mask (CIDR notation)</div>
                        <div class="error-message" id="routerPrefixError"></div>
                    </div>

                    <!-- DHCP Configuration -->
                    <div class="switch-container">
                        <label class="form-label mb-0">Enable DHCP</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="dhcpEnabled" checked>
                        </div>
                    </div>
                    <div id="dhcpConfig" class="mb-4">
                        <label class="form-label">DHCP Range</label>
                        <div class="ip-range-inputs">
                            <input type="text" class="form-control" id="dhcpRangeStart" placeholder="e.g., 10.0.0.100">
                            <span>to</span>
                            <input type="text" class="form-control" id="dhcpRangeEnd" placeholder="e.g., 10.0.0.200">
                        </div>
                        <div class="form-text">IP address range for DHCP clients (auto-filled based on router IP)</div>
                        <div class="error-message" id="dhcpRangeError"></div>
                    </div>

                    <!-- Internet Access -->
                    <div class="switch-container">
                        <label class="form-label mb-0">Enable Internet Access</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="internetEnabled" checked>
                        </div>
                    </div>

                    <!-- DNS Servers -->
                    <div class="mb-4">
                        <label for="nameservers" class="form-label">DNS Servers</label>
                        <input type="text" class="form-control" id="nameservers" placeholder="e.g., 1.1.1.1, 1.0.0.1" value="1.1.1.1, 1.0.0.1">
                        <div class="form-text">Comma-separated list of DNS servers</div>
                        <div class="error-message" id="nameserversError"></div>
                    </div>

                    <!-- WireGuard Configuration -->
                    <div class="switch-container">
                        <label class="form-label mb-0">Enable WireGuard VPN</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="wgEnabled">
                        </div>
                    </div>
                    <div id="wgConfig" class="mb-4" style="display: none;">
                        <label for="wgPrefix" class="form-label">WireGuard Subnet</label>
                        <input type="text" class="form-control" id="wgPrefix" placeholder="e.g., 192.168.178.1/24">
                        <div class="form-text">The WireGuard VPN subnet (CIDR notation)</div>
                        <div class="error-message" id="wgPrefixError"></div>
                    </div>
                </div>

                <!-- Bridge Network Configuration -->
                <div class="form-section config-section" id="bridgeConfig">
                    <h5>Bridge Configuration</h5>
                    <div class="mb-3">
                        <label for="bridgeDeviceId" class="form-label">Bridge Device <span class="text-danger">*</span></label>
                        <select class="form-select" id="bridgeDeviceId">
                            <option value="">Select a device...</option>
                        </select>
                        <div class="form-text">Select the device that will bridge this network to your LAN</div>
                        <div class="spinner-border spinner-border-sm ms-2" id="deviceLoadingSpinner" style="display: none;"></div>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Bridge networks connect directly to your existing LAN. VMs on this network will receive IP addresses from your existing DHCP server.
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid gap-2 mb-4">
                    <button type="submit" class="btn btn-primary" id="createButton">
                        <i class="bi bi-plus-circle me-2"></i>Create Network
                    </button>
                    <a href="restores.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="text-light">Creating network...</div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedNetworkType = null;

        // Network type selection
        document.querySelectorAll('.network-type-option').forEach(option => {
            option.addEventListener('click', () => {
                // Update selection
                document.querySelectorAll('.network-type-option').forEach(o => o.classList.remove('selected'));
                option.classList.add('selected');
                
                selectedNetworkType = option.dataset.type;
                
                // Show/hide relevant configuration sections
                document.querySelectorAll('.config-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                if (selectedNetworkType === 'standard') {
                    document.getElementById('standardConfig').classList.add('active');
                } else if (selectedNetworkType === 'bridge-lan') {
                    document.getElementById('bridgeConfig').classList.add('active');
                    loadDevices();
                }
            });
        });

        // DHCP toggle
        document.getElementById('dhcpEnabled').addEventListener('change', (e) => {
            document.getElementById('dhcpConfig').style.display = e.target.checked ? 'block' : 'none';
        });

        // WireGuard toggle
        document.getElementById('wgEnabled').addEventListener('change', (e) => {
            document.getElementById('wgConfig').style.display = e.target.checked ? 'block' : 'none';
        });

        // Load devices for bridge configuration
        async function loadDevices() {
            const spinner = document.getElementById('deviceLoadingSpinner');
            const select = document.getElementById('bridgeDeviceId');
            
            spinner.style.display = 'inline-block';
            select.disabled = true;
            
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getDevices');
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load devices');
                }

                select.innerHTML = '<option value="">Select a device...</option>';
                data.data.forEach(device => {
                    const option = document.createElement('option');
                    option.value = device.device_id;
                    option.textContent = `${device.display_name || device.hostname} (${device.serial_number})`;
                    select.appendChild(option);
                });

            } catch (error) {
                console.error('Error loading devices:', error);
                select.innerHTML = '<option value="">Failed to load devices</option>';
            } finally {
                spinner.style.display = 'none';
                select.disabled = false;
            }
        }

        // Load clients
        async function loadClients() {
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getClients');
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    const select = document.getElementById('clientId');
                    data.data.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.client_id;
                        option.textContent = client.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading clients:', error);
            }
        }

        // Validate IP address
        function isValidIP(ip) {
            const parts = ip.split('.');
            if (parts.length !== 4) return false;
            return parts.every(part => {
                const num = parseInt(part);
                return !isNaN(num) && num >= 0 && num <= 255;
            });
        }

        // Validate CIDR notation
        function isValidCIDR(cidr) {
            const parts = cidr.split('/');
            if (parts.length !== 2) return false;
            const ip = parts[0];
            const mask = parseInt(parts[1]);
            return isValidIP(ip) && !isNaN(mask) && mask >= 0 && mask <= 32;
        }

        // Calculate suggested DHCP range based on router IP
        function calculateDHCPRange(routerCIDR) {
            if (!isValidCIDR(routerCIDR)) return null;
            
            const [routerIP, maskBits] = routerCIDR.split('/');
            const ipParts = routerIP.split('.').map(Number);
            
            // For typical /24 networks, suggest .100 to .200
            if (maskBits === '24') {
                const baseIP = ipParts.slice(0, 3).join('.');
                return {
                    start: `${baseIP}.100`,
                    end: `${baseIP}.200`
                };
            }
            
            // For /16 networks, suggest x.x.1.100 to x.x.1.200
            if (maskBits === '16') {
                const baseIP = ipParts.slice(0, 2).join('.');
                return {
                    start: `${baseIP}.1.100`,
                    end: `${baseIP}.1.200`
                };
            }
            
            // For other networks, try to suggest a reasonable range
            // This is a simplified approach - in production you'd want more sophisticated subnet calculations
            if (parseInt(maskBits) >= 24) {
                // Small subnet - suggest range starting from .10
                const baseIP = ipParts.slice(0, 3).join('.');
                const lastOctet = ipParts[3];
                const start = Math.max(lastOctet + 10, 10);
                const end = Math.min(start + 50, 254);
                return {
                    start: `${baseIP}.${start}`,
                    end: `${baseIP}.${end}`
                };
            }
            
            return null;
        }

        // Auto-fill DHCP range when router prefix changes
        document.getElementById('routerPrefix').addEventListener('blur', function() {
            const routerPrefix = this.value.trim();
            if (!routerPrefix) return;
            
            // Only auto-fill if DHCP is enabled and fields are empty
            if (document.getElementById('dhcpEnabled').checked) {
                const dhcpStart = document.getElementById('dhcpRangeStart');
                const dhcpEnd = document.getElementById('dhcpRangeEnd');
                
                // Only auto-fill if both fields are empty
                if (!dhcpStart.value && !dhcpEnd.value) {
                    const suggestedRange = calculateDHCPRange(routerPrefix);
                    if (suggestedRange) {
                        dhcpStart.value = suggestedRange.start;
                        dhcpEnd.value = suggestedRange.end;
                        
                        // Add a subtle animation to show the fields were auto-filled
                        dhcpStart.style.backgroundColor = 'rgba(var(--bs-success-rgb), 0.2)';
                        dhcpEnd.style.backgroundColor = 'rgba(var(--bs-success-rgb), 0.2)';
                        setTimeout(() => {
                            dhcpStart.style.backgroundColor = '';
                            dhcpEnd.style.backgroundColor = '';
                        }, 1000);
                    }
                }
            }
        });

        // Form validation
        function validateForm() {
            let isValid = true;
            
            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            
            if (!selectedNetworkType) {
                alert('Please select a network type');
                return false;
            }
            
            const networkName = document.getElementById('networkName').value.trim();
            if (!networkName) {
                alert('Please enter a network name');
                return false;
            }
            
            if (selectedNetworkType === 'standard') {
                // Validate router prefix
                const routerPrefix = document.getElementById('routerPrefix').value.trim();
                if (!routerPrefix || !isValidCIDR(routerPrefix)) {
                    document.getElementById('routerPrefixError').textContent = 'Please enter a valid IP address with subnet mask (e.g., 10.0.0.1/24)';
                    isValid = false;
                }
                
                // Validate DHCP range if enabled
                if (document.getElementById('dhcpEnabled').checked) {
                    const dhcpStart = document.getElementById('dhcpRangeStart').value.trim();
                    const dhcpEnd = document.getElementById('dhcpRangeEnd').value.trim();
                    
                    if (!dhcpStart || !isValidIP(dhcpStart) || !dhcpEnd || !isValidIP(dhcpEnd)) {
                        document.getElementById('dhcpRangeError').textContent = 'Please enter valid IP addresses for DHCP range';
                        isValid = false;
                    }
                }
                
                // Validate nameservers
                const nameservers = document.getElementById('nameservers').value.trim();
                if (nameservers) {
                    const servers = nameservers.split(',').map(s => s.trim());
                    if (!servers.every(isValidIP)) {
                        document.getElementById('nameserversError').textContent = 'Please enter valid IP addresses separated by commas';
                        isValid = false;
                    }
                }
                
                // Validate WireGuard prefix if enabled
                if (document.getElementById('wgEnabled').checked) {
                    const wgPrefix = document.getElementById('wgPrefix').value.trim();
                    if (!wgPrefix || !isValidCIDR(wgPrefix)) {
                        document.getElementById('wgPrefixError').textContent = 'Please enter a valid IP address with subnet mask (e.g., 192.168.178.1/24)';
                        isValid = false;
                    }
                }
            } else if (selectedNetworkType === 'bridge-lan') {
                // Validate bridge device selection
                const bridgeDeviceId = document.getElementById('bridgeDeviceId').value;
                if (!bridgeDeviceId) {
                    alert('Please select a device for bridging');
                    return false;
                }
            }
            
            return isValid;
        }

        // Form submission
        document.getElementById('networkForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('show');
            
            try {
                const payload = {
                    name: document.getElementById('networkName').value.trim(),
                    type: selectedNetworkType,
                    comments: document.getElementById('networkComments').value.trim()
                };
                
                const clientId = document.getElementById('clientId').value;
                if (clientId) {
                    payload.client_id = clientId;
                }
                
                if (selectedNetworkType === 'standard') {
                    payload.router_prefix = document.getElementById('routerPrefix').value.trim();
                    payload.dhcp = document.getElementById('dhcpEnabled').checked;
                    
                    if (payload.dhcp) {
                        payload.dhcp_range_start = document.getElementById('dhcpRangeStart').value.trim();
                        payload.dhcp_range_end = document.getElementById('dhcpRangeEnd').value.trim();
                    }
                    
                    payload.internet = document.getElementById('internetEnabled').checked;
                    
                    const nameservers = document.getElementById('nameservers').value.trim();
                    if (nameservers) {
                        payload.nameservers = nameservers.split(',').map(s => s.trim());
                    }
                    
                    payload.wg = document.getElementById('wgEnabled').checked;
                    if (payload.wg) {
                        payload.wg_prefix = document.getElementById('wgPrefix').value.trim();
                    }
                } else if (selectedNetworkType === 'bridge-lan') {
                    payload.bridge_device_id = document.getElementById('bridgeDeviceId').value;
                }
                
                const response = await fetch('/mobile/mobileSlideApi.php?action=createNetwork', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to create network');
                }
                
                // Redirect to network details page
                window.location.href = `network-details.php?id=${data.data.network_id}`;
                
            } catch (error) {
                console.error('Error creating network:', error);
                alert('Failed to create network: ' + error.message);
            } finally {
                loadingOverlay.classList.remove('show');
            }
        });

        // Load clients on page load
        loadClients();
    </script>
</body>
</html> 