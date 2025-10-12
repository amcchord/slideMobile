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
    <title>Device Details - Slide Mobile</title>
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
                <span class="navbar-brand mb-0 h1">Device Details</span>
            </div>
        </header>

        <!-- Action Buttons -->
        <div class="bg-dark border-bottom">
            <div class="container-fluid d-flex gap-2 p-2" id="actionButtons">
                <button class="btn btn-primary flex-grow-1" onclick="viewAgents()">
                    <i class="bi bi-hdd-network me-2"></i>View Agents
                </button>
            </div>
            <div class="container-fluid d-flex gap-2 p-2 pt-0">
                <button class="btn btn-outline-warning flex-grow-1" onclick="rebootDevice()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Reboot
                </button>
                <button class="btn btn-outline-danger flex-grow-1" onclick="powerOffDevice()">
                    <i class="bi bi-power me-2"></i>Power Off
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

            <div id="deviceDetails">
                <!-- Device details will be loaded here -->
            </div>
        </main>

        <!-- Bottom Navigation -->
        <?php include 'include/bottomNav.php'; ?>
    </div>

    <script>
        let isLoading = false;
        let currentDeviceId = null;
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
                window.location.href = 'devices.php';
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

        function viewAgents() {
            if (!currentDeviceId) return;
            window.location.href = `agents.php?device_id=${currentDeviceId}`;
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            return new Date(dateStr).toLocaleString();
        }

        async function updateBackupStatus() {
            if (!currentDeviceId || isLoading) return;

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getDevice&id=${currentDeviceId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load device details');
                }

                const device = data.data;
                
                // Update action buttons
                updateActionButtons(device);

                // Update backup section if it exists
                const backupSection = document.querySelector('.details-section:nth-child(2)');
                if (backupSection && backupSection.querySelector('h6').textContent === 'Active Backup') {
                    if (!device.active_backup) {
                        backupSection.remove();
                    }
                } else if (device.active_backup) {
                    // Create new backup section if it doesn't exist
                    const newBackupSection = document.createElement('div');
                    newBackupSection.className = 'details-section';
                    newBackupSection.innerHTML = `
                        <h6>Active Backup</h6>
                        <div class="mb-2">
                            <small class="text-muted">${device.active_backup['backup-status'] || device.active_backup.status.charAt(0).toUpperCase() + device.active_backup.status.slice(1)}</small>
                            <div class="details-value">${formatDate(device.active_backup.started_at)}</div>
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

        async function loadDeviceDetails() {
            const urlParams = new URLSearchParams(window.location.search);
            const deviceId = urlParams.get('id');
            
            if (!deviceId) {
                goBack();
                return;
            }

            currentDeviceId = deviceId;

            if (isLoading) return;
            isLoading = true;
            
            document.querySelector('.loading-spinner').style.display = 'flex';
            const container = document.getElementById('deviceDetails');
            container.innerHTML = '';

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getDevice&id=${deviceId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load device details');
                }

                const device = data.data;
                
                // Create sections for different types of information
                const sections = [
                    {
                        title: 'Basic Information',
                        items: [
                            ...(device.display_name ? [{ label: 'Display Name', value: device.display_name }] : []),
                            { label: 'Hostname', value: device.hostname },
                            { label: 'Device ID', value: device.device_id },
                            { label: 'Serial Number', value: device.serial_number || 'N/A' },
                            { label: 'Agents', value: `${device.agents?.length || 0} agent${device.agents?.length !== 1 ? 's' : ''}` }
                        ]
                    },
                    {
                        title: 'Hardware Information',
                        items: [
                            { label: 'Model', value: device.hardware_model_name || 'N/A' },
                            { label: 'Storage', value: device.storage_total_bytes ? 
                                `${formatBytes(device.storage_used_bytes)} used of ${formatBytes(device.storage_total_bytes)}` : 
                                'N/A' 
                            },
                            { label: 'Image Version', value: device.image_version || 'N/A' },
                            { label: 'Package Version', value: device.package_version || 'N/A' }
                        ]
                    },
                    {
                        title: 'Service Information',
                        items: [
                            { label: 'Service Model', value: device.service_model_name || 'N/A' },
                            { label: 'Short Name', value: device.service_model_name_short || 'N/A' },
                            { label: 'Status', value: device.service_status ? 
                                device.service_status.charAt(0).toUpperCase() + device.service_status.slice(1) : 
                                'N/A'
                            }
                        ]
                    },
                    {
                        title: 'Network Information',
                        items: [
                            { label: 'Public IP', value: device.public_ip_address || 'N/A' },
                            { label: 'Local IPs', value: (device.ip_addresses || []).join(', ') || 'N/A' },
                            { label: 'MAC Address', value: device.addresses?.[0]?.mac || 'N/A' }
                        ]
                    },
                    {
                        title: 'Status',
                        items: [
                            { label: 'Last Seen', value: formatDate(device.last_seen_at) },
                            { label: 'Booted At', value: formatDate(device.booted_at) }
                        ]
                    }
                ];

                // Add formatBytes function
                function formatBytes(bytes, decimals = 2) {
                    if (!bytes) return 'N/A';
                    
                    const k = 1024;
                    const dm = decimals < 0 ? 0 : decimals;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

                    const i = Math.floor(Math.log(bytes) / Math.log(k));

                    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
                }

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
                console.error('Error loading device details:', error);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load device details. Please try again.
                    </div>
                `;
            } finally {
                isLoading = false;
                document.querySelector('.loading-spinner').style.display = 'none';
            }
        }

        // Update action buttons based on backup status
        function updateActionButtons(device) {
            // No longer needed since we only have one static button
        }

        // Device power controls
        async function rebootDevice() {
            if (!currentDeviceId) return;

            if (!confirm('Are you sure you want to reboot this device? This will temporarily interrupt all services.')) {
                return;
            }

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=rebootDevice&device_id=${encodeURIComponent(currentDeviceId)}`, {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    alert('Device reboot initiated successfully');
                    loadDeviceDetails();
                } else {
                    throw new Error(data.message || 'Failed to reboot device');
                }
            } catch (error) {
                console.error('Error rebooting device:', error);
                alert('Failed to reboot device: ' + error.message);
            }
        }

        async function powerOffDevice() {
            if (!currentDeviceId) return;

            if (!confirm('Are you sure you want to power off this device? This will stop all services and you will need physical access to turn it back on.')) {
                return;
            }

            // Double confirmation for power off
            if (!confirm('FINAL WARNING: This will completely power off the device. Are you absolutely sure?')) {
                return;
            }

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=powerOffDevice&device_id=${encodeURIComponent(currentDeviceId)}`, {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    alert('Device power off initiated successfully');
                    loadDeviceDetails();
                } else {
                    throw new Error(data.message || 'Failed to power off device');
                }
            } catch (error) {
                console.error('Error powering off device:', error);
                alert('Failed to power off device: ' + error.message);
            }
        }

        // Initial load
        loadDeviceDetails();
        startRefresh();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 