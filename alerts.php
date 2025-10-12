<!DOCTYPE html>
<?php
require_once 'include/getApiKey.php';
$hasKey = hasApiKey();

if (!$hasKey) {
    header('Location: key-management.php');
    exit;
}

function formatAlertFields($alertType, $fields) {
    if (empty($fields)) return '';
    
    // Decode JSON if it's a string
    if (is_string($fields)) {
        $fields = json_decode($fields, true);
    }
    
    if (!is_array($fields)) return htmlspecialchars($fields);
    
    $output = [];
    
    switch ($alertType) {
        case 'device_out_of_date':
            if (isset($fields['device'])) {
                $device = $fields['device'];
                if (isset($device['storage_used'], $device['storage_total'])) {
                    $usedGB = round($device['storage_used'] / (1024 * 1024 * 1024), 1);
                    $totalGB = round($device['storage_total'] / (1024 * 1024 * 1024), 1);
                    $usedPercent = round(($device['storage_used'] / $device['storage_total']) * 100, 1);
                    $output[] = "Storage: {$usedGB}GB used of {$totalGB}GB ({$usedPercent}%)";
                }
                if (isset($device['storage_health'])) {
                    $output[] = "Storage Health: " . ucfirst($device['storage_health']);
                }
            }
            break;
            
        case 'device_storage_space_low':
        case 'device_storage_space_critical':
            if (isset($fields['available_bytes'], $fields['total_bytes'])) {
                $availableGB = round($fields['available_bytes'] / (1024 * 1024 * 1024), 1);
                $totalGB = round($fields['total_bytes'] / (1024 * 1024 * 1024), 1);
                $usedPercent = round((1 - ($fields['available_bytes'] / $fields['total_bytes'])) * 100, 1);
                $output[] = "Available Storage: {$availableGB}GB of {$totalGB}GB";
                $output[] = "Used: {$usedPercent}%";
            }
            break;
            
        case 'device_not_checking_in':
        case 'agent_not_checking_in':
            if (isset($fields['last_seen'])) {
                $lastSeen = is_array($fields['last_seen']) ? 
                    $fields['last_seen']['seconds'] : strtotime($fields['last_seen']);
                $output[] = "Last seen: " . date('M j, Y g:i A', $lastSeen);
            }
            break;
            
        case 'agent_backup_failed':
            if (isset($fields['error'])) {
                $output[] = "Error: " . $fields['error'];
            }
            if (isset($fields['backup_started_at'])) {
                $output[] = "Backup started: " . date('M j, Y g:i A', strtotime($fields['backup_started_at']));
            }
            break;
            
        default:
            // For any other types, just show key-value pairs
            foreach ($fields as $key => $value) {
                if (is_array($value)) continue; // Skip nested arrays
                $key = str_replace('_', ' ', $key);
                $output[] = ucwords($key) . ": " . $value;
            }
    }
    
    return implode("<br>", $output);
}

// Get alerts from the API
require_once 'include/encryption.php';
$apiKey = getApiKey();

// First fetch alerts
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.slide.tech/v1/alert?sort_by=created&sort_asc=false");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$alerts = [];
$error = null;

if ($httpCode === 200 && $response) {
    $alertsData = json_decode($response, true);
    $alerts = $alertsData['data'] ?? [];
    
    // Collect unique device IDs and agent IDs
    $deviceIds = array_unique(array_column($alerts, 'device_id'));
    $agentIds = array_filter(array_unique(array_column($alerts, 'agent_id')));
    
    // Fetch devices info
    $devices = [];
    if (!empty($deviceIds)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.slide.tech/v1/device");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $devicesData = json_decode($response, true);
            foreach ($devicesData['data'] ?? [] as $device) {
                $devices[$device['device_id']] = $device;
            }
        }
    }
    
    // Fetch agents info
    $agents = [];
    if (!empty($agentIds)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.slide.tech/v1/agent");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $agentsData = json_decode($response, true);
            foreach ($agentsData['data'] ?? [] as $agent) {
                $agents[$agent['agent_id']] = $agent;
            }
        }
    }
    
    // Enhance alerts with device and agent names
    foreach ($alerts as &$alert) {
        if (!isset($alert['device_id'])) {
            continue;
        }

        $device = $devices[$alert['device_id']] ?? null;
        $agent = $agents[$alert['agent_id']] ?? null;
        
        if ($device) {
            if (!empty(trim($device['display_name'] ?? ''))) {
                $alert['device_name'] = $device['display_name'];
            } elseif (isset($device['hostname'])) {
                $alert['device_name'] = $device['hostname'];
            } else {
                $alert['device_name'] = $alert['device_id'];
            }
        } else {
            $alert['device_name'] = $alert['device_id'];
        }
        
        if ($agent) {
            if (!empty(trim($agent['display_name'] ?? ''))) {
                $alert['agent_name'] = $agent['display_name'];
            } elseif (isset($agent['hostname'])) {
                $alert['agent_name'] = $agent['hostname'];
            } else {
                $alert['agent_name'] = $alert['agent_id'];
            }
        }
    }
    
    // Sort alerts: unresolved first (newest to oldest), then resolved (newest to oldest)
    usort($alerts, function($a, $b) {
        // If one is resolved and the other isn't, unresolved comes first
        if ($a['resolved'] !== $b['resolved']) {
            return $a['resolved'] ? 1 : -1;
        }
        // If both are resolved or both unresolved, sort by created_at (newest first)
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
} else {
    $error = "Failed to fetch alerts (HTTP $httpCode)";
}
?>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Alerts - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .alert-card {
            border-left: 4px solid;
            margin-bottom: 1rem;
            position: relative;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }

        .alert-card .card-body {
            padding-bottom: 1rem;
        }

        .alert-card.critical {
            border-left-color: var(--bs-danger);
        }

        .alert-card.warning {
            border-left-color: var(--bs-warning);
        }

        .alert-card.info {
            border-left-color: var(--bs-info);
        }

        .alert-card.resolved {
            opacity: 0.6;
        }

        .alert-card.swiping {
            transform: translateX(var(--swipe-distance));
        }

        .resolve-button {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .alert-actions {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 0.5rem;
        }

        .swipe-action-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--bs-success);
            opacity: 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 1rem;
            color: white;
            font-weight: bold;
            transition: opacity 0.3s ease-out;
            z-index: -1;
            border-radius: inherit;
        }

        .swipe-action-bg.active {
            opacity: 0.9;
        }

        .swipe-action-bg.unresolve {
            background-color: var(--bs-warning);
            justify-content: flex-start;
            padding-left: 1rem;
            padding-right: 0;
        }

        /* Toggle for showing resolved alerts */
        .show-resolved-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            margin: 1rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .resolved-section {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .resolved-header {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            padding: 0 1rem;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Alerts</span>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm flex-grow-1" id="statusFilter" onchange="filterAlerts()">
                <option value="all">All Alerts</option>
                <option value="unresolved" selected>Unresolved Only</option>
                <option value="resolved">Resolved Only</option>
            </select>
            <button class="btn btn-sm btn-success" onclick="resolveAllVisible()" id="resolveAllBtn">
                <i class="bi bi-check-all"></i> Resolve All
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1 container-fluid py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <?php 
                $unresolvedAlerts = array_filter($alerts, function($alert) { return !$alert['resolved']; });
                $resolvedAlerts = array_filter($alerts, function($alert) { return $alert['resolved']; });
            ?>
            
            <?php if (empty($unresolvedAlerts)): ?>
                <div class="text-center mt-4">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No Active Alerts</h4>
                    <p class="text-muted">Everything is running smoothly</p>
                </div>
            <?php else: ?>
                <div class="alert-list initial-load" id="unresolvedAlerts">
                    <?php foreach ($unresolvedAlerts as $alert): ?>
                        <div class="card alert-card <?php echo strtolower($alert['alert_type']); ?>" data-alert-id="<?php echo $alert['alert_id']; ?>">
                            <div class="swipe-action-bg">
                                <i class="bi bi-check-lg me-2"></i> RESOLVE
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title mb-1">
                                        <?php 
                                        $title = isset($alert['agent_name']) ? $alert['agent_name'] : $alert['device_name'];
                                        echo htmlspecialchars($title); 
                                        ?>
                                    </h5>
                                    <span class="badge <?php 
                                        switch ($alert['alert_type']) {
                                            case 'device_not_checking_in':
                                            case 'device_storage_space_critical':
                                            case 'agent_not_checking_in':
                                            case 'agent_backup_failed':
                                                echo 'bg-danger';
                                                break;
                                            case 'device_storage_space_low':
                                            case 'device_out_of_date':
                                                echo 'bg-warning';
                                                break;
                                            default:
                                                echo 'bg-info';
                                        }
                                    ?>">
                                        <?php 
                                        $alertType = str_replace('_', ' ', $alert['alert_type']);
                                        echo htmlspecialchars(ucwords($alertType)); 
                                        ?>
                                    </span>
                                </div>
                                <p class="card-text text-muted small mb-2">
                                    <i class="bi bi-clock"></i> 
                                    <?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                                </p>
                                <?php if (isset($alert['alert_fields'])): ?>
                                    <p class="card-text mb-2">
                                        <?php echo formatAlertFields($alert['alert_type'], $alert['alert_fields']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="card-text mb-0">
                                    <small class="text-muted">
                                        <i class="bi bi-hdd"></i> <?php echo htmlspecialchars($alert['device_name']); ?>
                                        <?php if (isset($alert['agent_name']) && $alert['agent_name'] !== $alert['device_name']): ?>
                                            <br><i class="bi bi-laptop"></i> <?php echo htmlspecialchars($alert['agent_name']); ?>
                                        <?php endif; ?>
                                    </small>
                                </p>
                                <div class="alert-actions">
                                    <button class="btn btn-success btn-sm resolve-button flex-grow-1" onclick="toggleResolve('<?php echo $alert['alert_id']; ?>', true)">
                                        <i class="bi bi-check-lg"></i> Resolve
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($resolvedAlerts)): ?>
                <div class="show-resolved-toggle" onclick="toggleResolvedSection()">
                    <i class="bi bi-chevron-down me-2" id="resolvedToggleIcon"></i>
                    Show Resolved Alerts (<?php echo count($resolvedAlerts); ?>)
                </div>
                
                <div class="resolved-section" id="resolvedSection" style="display: none;">
                    <div class="resolved-header">Resolved Alerts</div>
                    <div class="alert-list" id="resolvedAlerts">
                        <?php foreach ($resolvedAlerts as $alert): ?>
                            <div class="card alert-card resolved <?php echo strtolower($alert['alert_type']); ?>" data-alert-id="<?php echo $alert['alert_id']; ?>">
                                <div class="swipe-action-bg unresolve">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i> UNRESOLVE
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title mb-1">
                                            <?php 
                                            $title = isset($alert['agent_name']) ? $alert['agent_name'] : $alert['device_name'];
                                            echo htmlspecialchars($title); 
                                            ?>
                                        </h5>
                                        <span class="badge bg-secondary">
                                            <?php 
                                            $alertType = str_replace('_', ' ', $alert['alert_type']);
                                            echo htmlspecialchars(ucwords($alertType)); 
                                            ?>
                                        </span>
                                    </div>
                                    <p class="card-text text-muted small mb-2">
                                        <i class="bi bi-clock"></i> 
                                        <?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                                        <?php if (isset($alert['resolved_at'])): ?>
                                            <br><i class="bi bi-check-circle"></i> 
                                            Resolved: <?php echo date('M j, Y g:i A', strtotime($alert['resolved_at'])); ?>
                                            <?php if (isset($alert['resolved_by'])): ?>
                                                by <?php echo htmlspecialchars($alert['resolved_by']); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (isset($alert['alert_fields'])): ?>
                                        <p class="card-text mb-2">
                                            <?php echo formatAlertFields($alert['alert_type'], $alert['alert_fields']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="card-text mb-0">
                                        <small class="text-muted">
                                            <i class="bi bi-hdd"></i> <?php echo htmlspecialchars($alert['device_name']); ?>
                                            <?php if (isset($alert['agent_name']) && $alert['agent_name'] !== $alert['device_name']): ?>
                                                <br><i class="bi bi-laptop"></i> <?php echo htmlspecialchars($alert['agent_name']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                    <div class="alert-actions">
                                        <button class="btn btn-outline-warning btn-sm resolve-button flex-grow-1" onclick="toggleResolve('<?php echo $alert['alert_id']; ?>', false)">
                                            <i class="bi bi-arrow-counterclockwise"></i> Unresolve
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'include/bottomNav.php'; ?>

    <!-- Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Remove initial-load class after animations complete
        document.addEventListener('DOMContentLoaded', function() {
            const alertList = document.getElementById('unresolvedAlerts');
            if (alertList && alertList.classList.contains('initial-load')) {
                setTimeout(() => {
                    alertList.classList.remove('initial-load');
                }, 500);
            }
        });

        // Toggle resolved section visibility
        function toggleResolvedSection() {
            const section = document.getElementById('resolvedSection');
            const icon = document.getElementById('resolvedToggleIcon');
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                icon.className = 'bi bi-chevron-up me-2';
            } else {
                section.style.display = 'none';
                icon.className = 'bi bi-chevron-down me-2';
            }
        }

        // Filter alerts by status
        function filterAlerts() {
            const filter = document.getElementById('statusFilter').value;
            const allCards = document.querySelectorAll('.alert-card');
            
            allCards.forEach(card => {
                const isResolved = card.classList.contains('resolved');
                
                if (filter === 'all') {
                    card.style.display = '';
                } else if (filter === 'unresolved') {
                    card.style.display = isResolved ? 'none' : '';
                } else if (filter === 'resolved') {
                    card.style.display = isResolved ? '' : 'none';
                }
            });
            
            updateResolveAllButton();
        }

        // Update the "Resolve All" button visibility
        function updateResolveAllButton() {
            const filter = document.getElementById('statusFilter').value;
            const resolveAllBtn = document.getElementById('resolveAllBtn');
            const unresolvedCards = Array.from(document.querySelectorAll('.alert-card:not(.resolved)')).filter(card => card.style.display !== 'none');
            
            if (filter === 'unresolved' || filter === 'all') {
                resolveAllBtn.style.display = unresolvedCards.length > 0 ? '' : 'none';
            } else {
                resolveAllBtn.style.display = 'none';
            }
        }

        // Resolve all visible unresolved alerts
        async function resolveAllVisible() {
            const unresolvedCards = Array.from(document.querySelectorAll('.alert-card:not(.resolved)')).filter(card => card.style.display !== 'none');
            
            if (unresolvedCards.length === 0) {
                return;
            }

            if (!confirm(`Are you sure you want to resolve all ${unresolvedCards.length} visible alert(s)?`)) {
                return;
            }

            const resolveAllBtn = document.getElementById('resolveAllBtn');
            resolveAllBtn.disabled = true;
            resolveAllBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Resolving...';

            let resolved = 0;
            let failed = 0;

            for (const card of unresolvedCards) {
                const alertId = card.dataset.alertId;
                try {
                    const response = await fetch('/mobile/mobileSlideApi.php?action=updateAlert', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            alert_id: alertId,
                            resolved: true
                        })
                    });

                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        resolved++;
                        card.style.opacity = '0.5';
                    } else {
                        failed++;
                    }
                } catch (error) {
                    console.error('Error resolving alert:', error);
                    failed++;
                }
            }

            if (failed > 0) {
                alert(`Resolved ${resolved} alert(s). ${failed} failed.`);
            }
            
            location.reload();
        }

        // Toggle alert resolution status
        async function toggleResolve(alertId, resolve) {
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=updateAlert', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        alert_id: alertId,
                        resolved: resolve
                    })
                });

                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || `Failed to update alert (HTTP ${response.status})`);
                }

                // Reload the page to reflect changes
                location.reload();
            } catch (error) {
                console.error('Error updating alert:', error);
                alert('Failed to update alert: ' + error.message);
            }
        }

        // Add swipe gesture support for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        let currentCard = null;

        document.querySelectorAll('.alert-card').forEach(card => {
            let isSwipeActive = false;
            
            card.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
                currentCard = this;
                isSwipeActive = false;
            });

            card.addEventListener('touchmove', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                const diff = touchEndX - touchStartX;
                const threshold = 50;
                
                if (Math.abs(diff) > threshold) {
                    e.preventDefault(); // Prevent scrolling when swiping
                    
                    const isResolved = this.classList.contains('resolved');
                    const swipeBg = this.querySelector('.swipe-action-bg');
                    
                    // Swipe right to resolve (unresolved alerts) or left to unresolve (resolved alerts)
                    if ((!isResolved && diff > threshold) || (isResolved && diff < -threshold)) {
                        this.style.setProperty('--swipe-distance', `${diff}px`);
                        this.classList.add('swiping');
                        swipeBg.classList.add('active');
                        isSwipeActive = true;
                    } else {
                        this.style.setProperty('--swipe-distance', '0px');
                        this.classList.remove('swiping');
                        swipeBg.classList.remove('active');
                        isSwipeActive = false;
                    }
                }
            });

            card.addEventListener('touchend', function(e) {
                const diff = touchEndX - touchStartX;
                const threshold = 100;
                const isResolved = this.classList.contains('resolved');
                const alertId = this.dataset.alertId;
                
                this.style.setProperty('--swipe-distance', '0px');
                this.classList.remove('swiping');
                this.querySelector('.swipe-action-bg').classList.remove('active');
                
                // Complete the action if swiped far enough
                if (isSwipeActive && Math.abs(diff) > threshold) {
                    if (!isResolved && diff > 0) {
                        toggleResolve(alertId, true);
                    } else if (isResolved && diff < 0) {
                        toggleResolve(alertId, false);
                    }
                }
                
                touchStartX = 0;
                touchEndX = 0;
                currentCard = null;
            });
        });

        // Initialize filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            filterAlerts();
            updateResolveAllButton();
        });
    </script>
</body>
</html> 