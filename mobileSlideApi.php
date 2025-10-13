<?php
require_once 'include/getApiKey.php';

/**
 * Log slow API queries to /tmp/slow_api_queries.log
 * 
 * @param string $url The API endpoint URL
 * @param float $durationMs Duration in milliseconds
 * @param string $apiKey The API key (will log last 6 chars only)
 * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
 */
function logSlowApiQuery($url, $durationMs, $apiKey, $method = 'GET') {
    if ($durationMs <= 100) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $apiKeySuffix = substr($apiKey, -6);
    $logEntry = sprintf(
        "[%s] %dms %s %s API_KEY: ...%s\n",
        $timestamp,
        round($durationMs),
        $method,
        $url,
        $apiKeySuffix
    );
    
    file_put_contents('/tmp/slow_api_queries.log', $logEntry, FILE_APPEND);
}

/**
 * Make a Slide API call with timing and logging
 * 
 * @param string $url The API endpoint URL
 * @param string $apiKey The API key for authorization
 * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
 * @param array|null $data Request body data (for POST/PUT)
 * @param array $additionalHeaders Additional headers beyond Authorization
 * @return array ['response' => string, 'httpCode' => int]
 */
function makeSlideApiCall($url, $apiKey, $method = 'GET', $data = null, $additionalHeaders = []) {
    $ch = curl_init();
    
    $headers = array_merge([
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ], $additionalHeaders);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else {
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
    }
    
    if ($data !== null) {
        if (!in_array('Content-Type: application/json', $headers)) {
            $headers[] = 'Content-Type: application/json';
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    
    $durationMs = ($endTime - $startTime) * 1000;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    logSlowApiQuery($url, $durationMs, $apiKey, $method);
    
    return [
        'response' => $response,
        'httpCode' => $httpCode
    ];
}

// Handle different API actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getSnapshots':
        handleGetSnapshots();
        break;
    case 'getAgents':
        handleGetAgents();
        break;
    case 'getAgent':
        handleGetAgent();
        break;
    case 'startBackup':
        handleStartBackup();
        break;
    case 'resolveAlert':
        handleResolveAlert();
        break;
    case 'getDevices':
        handleGetDevices();
        break;
    case 'getDevice':
        handleGetDevice();
        break;
    case 'getFileRestores':
        handleGetFileRestores();
        break;
    case 'getImageExports':
        handleGetImageExports();
        break;
    case 'getVirtualMachines':
        handleGetVirtualMachines();
        break;
    case 'updateVirt':
        handleUpdateVirtualMachine();
        break;
    case 'browseFileRestore':
        handleBrowseFileRestore();
        break;
    case 'deleteRestore':
        handleDeleteRestore();
        break;
    case 'createFileRestore':
        handleCreateFileRestore();
        break;
    case 'createVirtualMachine':
        handleCreateVirtualMachine();
        break;
    case 'getAlerts':
        handleGetAlerts();
        break;
    case 'getNetworks':
        handleGetNetworks();
        break;
    case 'createNetwork':
        handleCreateNetwork();
        break;
    case 'deleteNetwork':
        handleDeleteNetwork();
        break;
    case 'updateNetwork':
        handleUpdateNetwork();
        break;
    case 'getNetwork':
        handleGetNetwork();
        break;
    case 'pairAgent':
        handlePairAgent();
        break;
    case 'createAgentForPairing':
        handleCreateAgentForPairing();
        break;
    case 'updateAlert':
        handleUpdateAlert();
        break;
    case 'getClients':
        handleGetClients();
        break;
    case 'createIpsec':
        handleCreateIpsec();
        break;
    case 'updateIpsec':
        handleUpdateIpsec();
        break;
    case 'deleteIpsec':
        handleDeleteIpsec();
        break;
    case 'createPortForward':
        handleCreatePortForward();
        break;
    case 'updatePortForward':
        handleUpdatePortForward();
        break;
    case 'deletePortForward':
        handleDeletePortForward();
        break;
    case 'createWgPeer':
        handleCreateWgPeer();
        break;
    case 'updateWgPeer':
        handleUpdateWgPeer();
        break;
    case 'deleteWgPeer':
        handleDeleteWgPeer();
        break;
    case 'getAccounts':
        handleGetAccounts();
        break;
    case 'getAccount':
        handleGetAccount();
        break;
    case 'updateAccount':
        handleUpdateAccount();
        break;
    case 'getUsers':
        handleGetUsers();
        break;
    case 'getUser':
        handleGetUser();
        break;
    case 'getAudits':
        handleGetAudits();
        break;
    case 'getAudit':
        handleGetAudit();
        break;
    case 'getAuditActions':
        handleGetAuditActions();
        break;
    case 'getAuditResourceTypes':
        handleGetAuditResourceTypes();
        break;
    case 'rebootDevice':
        handleRebootDevice();
        break;
    case 'powerOffDevice':
        handlePowerOffDevice();
        break;
    case 'getBackups':
        handleGetBackups();
        break;
    case 'getBackup':
        handleGetBackup();
        break;
    case 'createClient':
        handleCreateClient();
        break;
    case 'updateClient':
        handleUpdateClient();
        break;
    case 'deleteClient':
        handleDeleteClient();
        break;
    case 'getClient':
        handleGetClient();
        break;
    case 'getSlowQueries':
        handleGetSlowQueries();
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

function handleGetSnapshots() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
    $agentId = isset($_GET['agent_id']) ? $_GET['agent_id'] : '';

    // Construct API URL with query parameters
    $queryParams = [
        'offset=' . $offset,
        'limit=' . $limit,
        'sort_by=backup_end_time',
        'sort_asc=false'
    ];
    
    if ($agentId) {
        $queryParams[] = 'agent_id=' . urlencode($agentId);
    }

    // Fetch snapshots
    $result = makeSlideApiCall("https://api.slide.tech/v1/snapshot?" . implode('&', $queryParams), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];
    
    if ($httpCode !== 200 || !$response) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch snapshots'
        ]);
        return;
    }
    
    $snapshotsData = json_decode($response, true);
    if (!isset($snapshotsData['data']) || !is_array($snapshotsData['data'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid snapshot data format'
        ]);
        return;
    }

    // Collect all agent IDs and device IDs
    $agentIds = array_unique(array_map(function($snapshot) {
        return $snapshot['agent_id'];
    }, $snapshotsData['data']));

    $deviceIds = array_unique(array_map(function($snapshot) {
        return $snapshot['locations'][0]['device_id'];
    }, $snapshotsData['data']));

    // Fetch agents
    $agents = [];
    if (!empty($agentIds)) {
        $result = makeSlideApiCall("https://api.slide.tech/v1/agent?limit=50", $apiKey);
        $response = $result['response'];
        if ($response) {
            $agentsData = json_decode($response, true);
            if (isset($agentsData['data'])) {
                foreach ($agentsData['data'] as $agent) {
                    $agents[$agent['agent_id']] = $agent;
                }
            }
        }
    }

    // Fetch devices
    $devices = [];
    if (!empty($deviceIds)) {
        $result = makeSlideApiCall("https://api.slide.tech/v1/device?limit=50", $apiKey);
        $response = $result['response'];
        if ($response) {
            $devicesData = json_decode($response, true);
            if (isset($devicesData['data'])) {
                foreach ($devicesData['data'] as $device) {
                    $devices[$device['device_id']] = $device;
                }
            }
        }
    }

    // Enrich snapshot data with agent and device info
    foreach ($snapshotsData['data'] as &$snapshot) {
        $agentId = $snapshot['agent_id'];
        $deviceId = $snapshot['locations'][0]['device_id'];

        // Add agent info
        if (isset($agents[$agentId])) {
            $agent = $agents[$agentId];
            $snapshot['agent_display_name'] = $agent['display_name'];
            $snapshot['agent_hostname'] = $agent['hostname'];
        }

        // Add device info
        if (isset($devices[$deviceId])) {
            $device = $devices[$deviceId];
            $snapshot['device_display_name'] = $device['display_name'];
            $snapshot['device_hostname'] = $device['hostname'];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $snapshotsData['data']
    ]);
}

function handleGetAgents() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Fetch agents
    $result = makeSlideApiCall("https://api.slide.tech/v1/agent?limit=50", $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];
    
    if ($httpCode !== 200 || !$response) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch agents'
        ]);
        return;
    }
    
    $agentsData = json_decode($response, true);
    if (!isset($agentsData['data'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid agent data format'
        ]);
        return;
    }

    // Collect all device IDs
    $deviceIds = array_unique(array_map(function($agent) {
        return $agent['device_id'];
    }, $agentsData['data']));

    // Fetch devices
    $devices = [];
    if (!empty($deviceIds)) {
        $result = makeSlideApiCall("https://api.slide.tech/v1/device?limit=50", $apiKey);
        $deviceResponse = $result['response'];

        if ($deviceResponse) {
            $devicesData = json_decode($deviceResponse, true);
            if (isset($devicesData['data'])) {
                foreach ($devicesData['data'] as $device) {
                    $devices[$device['device_id']] = $device;
                }
            }
        }
    }

    // Add device info to each agent
    foreach ($agentsData['data'] as &$agent) {
        if (isset($agent['device_id']) && isset($devices[$agent['device_id']])) {
            $agent['device'] = $devices[$agent['device_id']];
        }
    }

    // Fetch alerts for all agents
    $result = makeSlideApiCall("https://api.slide.tech/v1/alert?resolved=false", $apiKey);
    $alertsResponse = $result['response'];
    
    $alertsByAgent = [];
    if ($alertsResponse) {
        $alertsData = json_decode($alertsResponse, true);
        if (isset($alertsData['data'])) {
            foreach ($alertsData['data'] as $alert) {
                if (isset($alert['agent_id'])) {
                    if (!isset($alertsByAgent[$alert['agent_id']])) {
                        $alertsByAgent[$alert['agent_id']] = [];
                    }
                    $alertsByAgent[$alert['agent_id']][] = $alert;
                }
            }
        }
    }

    // Fetch active backups
    $result = makeSlideApiCall("https://api.slide.tech/v1/backup?limit=50", $apiKey);
    $backupsResponse = $result['response'];
    
    $activeBackupsByAgent = [];
    if ($backupsResponse) {
        $backupsData = json_decode($backupsResponse, true);
        if (isset($backupsData['data'])) {
            foreach ($backupsData['data'] as $backup) {
                if (!isset($backup['ended_at']) && isset($backup['agent_id'])) {
                    $activeBackupsByAgent[$backup['agent_id']] = $backup;
                }
            }
        }
    }

    // Add alerts and active backups to agent data
    foreach ($agentsData['data'] as &$agent) {
        $agent['alerts'] = $alertsByAgent[$agent['agent_id']] ?? [];
        $agent['active_backup'] = $activeBackupsByAgent[$agent['agent_id']] ?? null;

        // Calculate backup status for active backups
        if ($agent['active_backup'] && !isset($agent['active_backup']['ended_at'])) {
            // Calculate elapsed time since backup started
            $startTime = strtotime($agent['active_backup']['started_at']);
            $elapsedSeconds = time() - $startTime;
            $hours = floor($elapsedSeconds / 3600);
            $minutes = floor(($elapsedSeconds % 3600) / 60);
            $seconds = $elapsedSeconds % 60;
            if ($hours > 0) {
                $agent['active_backup']['backup-status'] = sprintf("Active: %02d:%02d:%02d", $hours, $minutes, $seconds);
            } else {
                $agent['active_backup']['backup-status'] = sprintf("Active: %02d:%02d", $minutes, $seconds);
            }
            if ($agent['active_backup']['status'] === 'failed') {
                $agent['active_backup']['backup-status'] = 'Last Backup Failed';
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $agentsData['data']
    ]);
}

function handleGetAgent() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Agent ID is required'
        ]);
        return;
    }

    $agentId = $_GET['id'];

    // Fetch agent details
    $result = makeSlideApiCall("https://api.slide.tech/v1/agent/" . $agentId, $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];
    
    if ($httpCode !== 200 || !$response) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch agent details'
        ]);
        return;
    }
    
    $agentData = json_decode($response, true);
    if (!$agentData) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid agent data format'
        ]);
        return;
    }

    // Get alerts for this agent
    $result = makeSlideApiCall("https://api.slide.tech/v1/alert?agent_id=" . $agentId . "&resolved=false", $apiKey);
    $alertsResponse = $result['response'];
    
    $agentData['alerts'] = [];
    if ($alertsResponse) {
        $alertsData = json_decode($alertsResponse, true);
        if (isset($alertsData['data'])) {
            $agentData['alerts'] = array_filter($alertsData['data'], function($alert) {
                return isset($alert['resolved']) && $alert['resolved'] === false;
            });
        }
    }

    // Get active backup for this agent
    $result = makeSlideApiCall("https://api.slide.tech/v1/backup?agent_id=" . $agentId . "&limit=1", $apiKey);
    $backupResponse = $result['response'];
    
    $agentData['active_backup'] = null;
    if ($backupResponse) {
        $backupData = json_decode($backupResponse, true);
        if (isset($backupData['data']) && !empty($backupData['data'])) {
            $latestBackup = $backupData['data'][0];
            if (!isset($latestBackup['ended_at'])) {
                $agentData['active_backup'] = $latestBackup;
                // Calculate elapsed time since backup started
                $startTime = strtotime($latestBackup['started_at']);
                $elapsedSeconds = time() - $startTime;
                $hours = floor($elapsedSeconds / 3600);
                $minutes = floor(($elapsedSeconds % 3600) / 60);
                $seconds = $elapsedSeconds % 60;
                if ($hours > 0) {
                    $agentData['active_backup']['backup-status'] = sprintf("Active: %02d:%02d:%02d", $hours, $minutes, $seconds);
                } else {
                    $agentData['active_backup']['backup-status'] = sprintf("Active: %02d:%02d", $minutes, $seconds);
                }

                if ($latestBackup['status'] === 'failed') {
                    $agentData['active_backup']['backup-status'] = 'Last Backup Failed';
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $agentData
    ]);
}

function handleStartBackup() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get the agent ID from the request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['agent_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Agent ID is required'
        ]);
        return;
    }

    // Start the backup
    $result = makeSlideApiCall("https://api.slide.tech/v1/backup", $apiKey, 'POST', ['agent_id' => $data['agent_id']]);
    $response = $result['response'];
    $httpCode = $result['httpCode'];
    
    if ($httpCode !== 200 && $httpCode !== 201 && $httpCode !== 202) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to start backup'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Backup started successfully'
    ]);
}

function handleResolveAlert() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get the alert ID from the request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['alert_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Alert ID is required'
        ]);
        return;
    }

    // Resolve the alert
    $result = makeSlideApiCall("https://api.slide.tech/v1/alert/" . $data['alert_id'], $apiKey, 'PATCH', ['resolved' => true]);
    $response = $result['response'];
    $httpCode = $result['httpCode'];
    
    if ($httpCode !== 200 && $httpCode !== 201 && $httpCode !== 204) {
        error_log("Failed to resolve alert. Status: " . $httpCode . ", Response: " . $response);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to resolve alert'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Alert resolved successfully'
    ]);
}

function handleGetDevices() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No API key available']);
        return;
    }

    $result = makeSlideApiCall("https://api.slide.tech/v1/device", $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch devices']);
        return;
    }

    $devices = json_decode($response, true);
    if (!$devices || !isset($devices['data'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid response from API']);
        return;
    }

    // Fetch agents for all devices
    $result = makeSlideApiCall("https://api.slide.tech/v1/agent", $apiKey);
    $agentsResponse = $result['response'];

    // Log the agents response for debugging
    error_log("Agents response: " . $agentsResponse);

    $agentsByDevice = [];
    if ($agentsResponse) {
        $agentsData = json_decode($agentsResponse, true);
        if (isset($agentsData['data'])) {
            foreach ($agentsData['data'] as $agent) {
                // Check if the agent has a device_id
                if (isset($agent['device_id'])) {
                    $deviceId = $agent['device_id'];
                    if (!isset($agentsByDevice[$deviceId])) {
                        $agentsByDevice[$deviceId] = [];
                    }
                    $agentsByDevice[$deviceId][] = $agent;
                }
            }
        }
    }

    // Fetch unresolved alerts for all devices
    $result = makeSlideApiCall("https://api.slide.tech/v1/alert?status=unresolved", $apiKey);
    $alertsResponse = $result['response'];

    $alertsByDevice = [];
    if ($alertsResponse) {
        $alertsData = json_decode($alertsResponse, true);
        if (isset($alertsData['data'])) {
            foreach ($alertsData['data'] as $alert) {
                if (isset($alert['device_id'])) {
                    $alertsByDevice[$alert['device_id']][] = $alert;
                }
            }
        }
    }

    // Add alerts and agents to each device
    foreach ($devices['data'] as &$device) {
        $device['alerts'] = $alertsByDevice[$device['device_id']] ?? [];
        $device['agents'] = $agentsByDevice[$device['device_id']] ?? [];
        // Log the agent count for each device
        error_log("Device {$device['device_id']} has " . count($device['agents']) . " agents");
    }

    echo json_encode(['success' => true, 'data' => $devices['data']]);
}

function handleGetDevice() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No API key available']);
        return;
    }

    $deviceId = $_GET['id'] ?? null;
    if (!$deviceId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Device ID is required']);
        return;
    }

    $result = makeSlideApiCall("https://api.slide.tech/v1/device/" . $deviceId, $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    // Log the response for debugging
    error_log("Device API response: " . $response);
    error_log("HTTP Code: " . $httpCode);

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch device details']);
        return;
    }

    $device = json_decode($response, true);
    if (!$device) {
        error_log("Failed to decode JSON response");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON response from API']);
        return;
    }

    // Log the decoded response
    error_log("Decoded device data: " . print_r($device, true));

    // The API returns the device directly, not wrapped in a data object
    $deviceData = $device;

    // Fetch agents for this device
    $result = makeSlideApiCall("https://api.slide.tech/v1/agent", $apiKey);
    $agentsResponse = $result['response'];

    $deviceData['agents'] = [];
    if ($agentsResponse) {
        $agentsData = json_decode($agentsResponse, true);
        if (isset($agentsData['data'])) {
            foreach ($agentsData['data'] as $agent) {
                if (isset($agent['device_id']) && $agent['device_id'] === $deviceId) {
                    $deviceData['agents'][] = $agent;
                }
            }
        }
    }

    // Log the agent count for debugging
    error_log("Device details: Device {$deviceId} has " . count($deviceData['agents']) . " agents");

    // Fetch unresolved alerts for this device
    $result = makeSlideApiCall("https://api.slide.tech/v1/alert?status=unresolved&device_id=" . $deviceId, $apiKey);
    $alertsResponse = $result['response'];

    $deviceData['alerts'] = [];
    if ($alertsResponse) {
        $alertsData = json_decode($alertsResponse, true);
        if (isset($alertsData['data'])) {
            $deviceData['alerts'] = $alertsData['data'];
        }
    }

    echo json_encode(['success' => true, 'data' => $deviceData]);
}

function handleGetFileRestores() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No API key available']);
        return;
    }

    // Fetch file restores
    $result = makeSlideApiCall("https://api.slide.tech/v1/restore/file", $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch file restores']);
        return;
    }

    $restores = json_decode($response, true);
    if (!$restores || !isset($restores['data'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid response from API']);
        return;
    }

    // Collect agent IDs
    $agentIds = array_unique(array_map(function($restore) {
        return $restore['agent_id'];
    }, $restores['data']));

    // Fetch agents to get their names
    $agents = [];
    if (!empty($agentIds)) {
        $result = makeSlideApiCall("https://api.slide.tech/v1/agent", $apiKey);
        $agentsResponse = $result['response'];

        if ($agentsResponse) {
            $agentsData = json_decode($agentsResponse, true);
            if (isset($agentsData['data'])) {
                foreach ($agentsData['data'] as $agent) {
                    $agents[$agent['agent_id']] = $agent;
                }
            }
        }
    }

    // Add agent info to restores and ensure file_restore_id is present
    foreach ($restores['data'] as &$restore) {
        // Ensure file_restore_id is set correctly
        if (isset($restore['file_restore_id'])) {
            $restore['file_restore_id'] = $restore['file_restore_id'];
        }
        
        if (isset($agents[$restore['agent_id']])) {
            $agent = $agents[$restore['agent_id']];
            $restore['agent_display_name'] = $agent['display_name'];
            $restore['agent_hostname'] = $agent['hostname'];
        }
    }

    echo json_encode(['success' => true, 'data' => $restores['data']]);
}

function handleGetImageExports() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No API key available']);
        return;
    }

    // Fetch image exports
    $result = makeSlideApiCall("https://api.slide.tech/v1/restore/image", $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch image exports']);
        return;
    }

    $exports = json_decode($response, true);
    if (!$exports || !isset($exports['data'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid response from API']);
        return;
    }

    // Collect agent IDs
    $agentIds = array_unique(array_map(function($export) {
        return $export['agent_id'];
    }, $exports['data']));

    // Fetch agents to get their names
    $agents = [];
    if (!empty($agentIds)) {
        $result = makeSlideApiCall("https://api.slide.tech/v1/agent", $apiKey);
        $agentsResponse = $result['response'];

        if ($agentsResponse) {
            $agentsData = json_decode($agentsResponse, true);
            if (isset($agentsData['data'])) {
                foreach ($agentsData['data'] as $agent) {
                    $agents[$agent['agent_id']] = $agent;
                }
            }
        }
    }

    // Add agent info to exports
    foreach ($exports['data'] as &$export) {
        if (isset($agents[$export['agent_id']])) {
            $agent = $agents[$export['agent_id']];
            $export['agent_display_name'] = $agent['display_name'];
            $export['agent_hostname'] = $agent['hostname'];
        }
    }

    echo json_encode(['success' => true, 'data' => $exports['data']]);
}

function handleGetVirtualMachines() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No API key available']);
        return;
    }

    // Fetch virtual machines
    $result = makeSlideApiCall("https://api.slide.tech/v1/restore/virt", $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch virtual machines']);
        return;
    }

    $vms = json_decode($response, true);
    if (!$vms || !isset($vms['data'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid response from API']);
        return;
    }

    // Collect agent IDs
    $agentIds = array_unique(array_map(function($vm) {
        return $vm['agent_id'];
    }, $vms['data']));

    // Fetch agents to get their names
    $agents = [];
    if (!empty($agentIds)) {
        $result = makeSlideApiCall("https://api.slide.tech/v1/agent", $apiKey);
        $agentsResponse = $result['response'];

        if ($agentsResponse) {
            $agentsData = json_decode($agentsResponse, true);
            if (isset($agentsData['data'])) {
                foreach ($agentsData['data'] as $agent) {
                    $agents[$agent['agent_id']] = $agent;
                }
            }
        }
    }

    // Add agent info to VMs
    foreach ($vms['data'] as &$vm) {
        if (isset($agents[$vm['agent_id']])) {
            $agent = $agents[$vm['agent_id']];
            $vm['agent_display_name'] = $agent['display_name'];
            $vm['agent_hostname'] = $agent['hostname'];
        }
    }

    echo json_encode(['success' => true, 'data' => $vms['data']]);
}

function handleBrowseFileRestore() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $id = $_GET['id'] ?? '';
    $path = $_GET['path'] ?? '';
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    // Log the incoming request
    error_log("Browsing file restore with ID: " . $id . ", path: " . $path);

    // Validate file restore ID format
    if (!preg_match('/^fr_[a-z0-9]{12}$/', $id)) {
        error_log("Invalid file restore ID format: " . $id);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file restore ID format'
        ]);
        return;
    }

    // Construct the URL with proper query parameters
    $queryParams = [];
    if ($path !== '') {
        $queryParams[] = 'path=' . urlencode($path);
    }
    $queryParams[] = 'offset=' . $offset;
    $queryParams[] = 'limit=' . $limit;
    
    $url = "https://api.slide.tech/v1/restore/file/{$id}/browse";
    if (!empty($queryParams)) {
        $url .= '?' . implode('&', $queryParams);
    }
    
    error_log("Making API request to: " . $url);

    $result = makeSlideApiCall($url, $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];
    $error = '';

    // Log the API response
    error_log("API response code: " . $httpCode);
    if ($error) {
        error_log("Curl error: " . $error);
    }
    error_log("API response: " . $response);

    if ($httpCode === 404) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'File restore not found'
        ]);
        return;
    }

    if ($httpCode !== 200 || !$response) {
        http_response_code($httpCode ?: 500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to browse file restore'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    if (!$responseData || !isset($responseData['data'])) {
        error_log("Invalid response data: " . print_r($responseData, true));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response from API'
        ]);
        return;
    }

    // Transform the response to match our frontend expectations
    $files = array_map(function($item) {
        return [
            'name' => $item['name'],
            'path' => $item['path'],
            'type' => $item['type'] === 'dir' ? 'directory' : 'file',
            'size' => $item['size'] ?? 0,
            'modified_at' => $item['modified_at'] ?? null,
            'download_uris' => $item['download_uris'] ?? []
        ];
    }, $responseData['data']);

    // Include pagination info if present
    $result = [
        'success' => true,
        'data' => $files
    ];
    
    if (isset($responseData['pagination'])) {
        $result['pagination'] = $responseData['pagination'];
    }

    echo json_encode($result);
}

function handleDeleteRestore() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['type']) || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Type and ID are required'
        ]);
        return;
    }

    // Validate ID format based on type
    $idPattern = '';
    $endpoint = '';
    switch ($data['type']) {
        case 'file':
            $idPattern = '/^fr_[a-z0-9]{12}$/';
            $endpoint = "restore/file/{$data['id']}";
            break;
        case 'image':
            $idPattern = '/^ie_[a-z0-9]{12}$/';
            $endpoint = "restore/image/{$data['id']}";
            break;
        case 'vm':
            $idPattern = '/^virt_[a-z0-9]{12}$/';
            $endpoint = "restore/virt/{$data['id']}";
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid restore type'
            ]);
            return;
    }

    if (!preg_match($idPattern, $data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid ID format'
        ]);
        return;
    }

    // Make API request to delete the restore
    $result = makeSlideApiCall("https://api.slide.tech/v1/" . $endpoint, $apiKey, 'DELETE');
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode === 404) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Restore not found'
        ]);
        return;
    }

    if ($httpCode !== 204) {
        http_response_code($httpCode ?: 500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete restore'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Restore deleted successfully'
    ]);
}

function handleCreateFileRestore() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['snapshot_id']) || !isset($data['agent_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Snapshot ID and agent ID are required'
        ]);
        return;
    }

    // Get device ID for the agent
    $result = makeSlideApiCall("https://api.slide.tech/v1/agent/" . $data['agent_id'], $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200 || !$response) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch agent details'
        ]);
        return;
    }

    $agentData = json_decode($response, true);
    if (!isset($agentData['device_id'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get device ID from agent'
        ]);
        return;
    }

    // Create file restore
    $result = makeSlideApiCall("https://api.slide.tech/v1/restore/file", $apiKey, 'POST', [
        'snapshot_id' => $data['snapshot_id'],
        'device_id' => $agentData['device_id']
    ]);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create file restore'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleCreateVirtualMachine() {
    require_once 'include/getApiKey.php';
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['agent_id']) || !isset($data['snapshot_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        return;
    }
    
    // Get device ID for the agent
    $result = makeSlideApiCall("https://api.slide.tech/v1/agent/" . urlencode($data['agent_id']), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];
    
    if ($httpCode !== 200 || !$response) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch agent details'
        ]);
        return;
    }
    
    $agentData = json_decode($response, true);
    $deviceId = $agentData['device_id'];
    
    // Create virtual machine restore
    $requestBody = [
        'snapshot_id' => $data['snapshot_id'],
        'agent_id' => $data['agent_id'],
        'device_id' => $deviceId,
        'cpu_count' => $data['cpu_count'],
        'memory_in_mb' => $data['memory_in_mb'],
        'disk_bus' => $data['disk_bus'],
        'network_model' => $data['network_model'],
        'network_type' => $data['network_type']
    ];
    
    // Add network_source if provided (for network-id type)
    if (isset($data['network_source'])) {
        $requestBody['network_source'] = $data['network_source'];
    }
    
    $result = makeSlideApiCall("https://api.slide.tech/v1/restore/virt", $apiKey, 'POST', $requestBody);
    $response = $result['response'];
    $httpCode = $result['httpCode'];
    
    if ($httpCode !== 201 || !$response) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create virtual machine'
        ]);
        return;
    }
    
    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleGetAlerts() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Fetch alerts
    $result = makeSlideApiCall("https://api.slide.tech/v1/alert?resolved=false", $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch alerts'
        ]);
        return;
    }

    $alertsData = json_decode($response, true);
    if (!$alertsData || !isset($alertsData['data'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response from API'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'data' => $alertsData['data']
    ]);
}

function handleUpdateVirtualMachine() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Virtual machine ID is required'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['state'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'State is required'
        ]);
        return;
    }

    // Validate state
    $validStates = ['running', 'stopped', 'paused'];
    if (!in_array($data['state'], $validStates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid state. Must be one of: ' . implode(', ', $validStates)
        ]);
        return;
    }

    // Update virtual machine state
    $result = makeSlideApiCall("https://api.slide.tech/v1/restore/virt/" . urlencode($_GET['id']), $apiKey, 'PATCH', ['state' => $data['state']]);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode === 404) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Virtual machine not found'
        ]);
        return;
    }

    if ($httpCode !== 200 && $httpCode !== 202) {
        http_response_code($httpCode ?: 500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update virtual machine state'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
} 

function handleGetNetworks() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Fetch networks
    $result = makeSlideApiCall("https://api.slide.tech/v1/network?limit=50", $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch networks'
        ]);
        return;
    }

    $networksData = json_decode($response, true);
    if (!$networksData || !isset($networksData['data'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response from API'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'data' => $networksData['data']
    ]);
}

function handleGetNetwork() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID is required'
        ]);
        return;
    }

    $networkId = $_GET['id'];

    // Fetch network details
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($networkId), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode === 404) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Network not found'
        ]);
        return;
    }

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch network details'
        ]);
        return;
    }

    $networkData = json_decode($response, true);
    if (!$networkData) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response from API'
        ]);
        return;
    }

    // If the network has connected VMs, fetch their details
    if (!empty($networkData['connected_virt_ids'])) {
        $result = makeSlideApiCall("https://api.slide.tech/v1/restore/virt", $apiKey);
        $vmsResponse = $result['response'];

        $networkData['connected_vms'] = [];
        if ($vmsResponse) {
            $vmsData = json_decode($vmsResponse, true);
            if (isset($vmsData['data'])) {
                foreach ($vmsData['data'] as $vm) {
                    if (in_array($vm['virt_id'], $networkData['connected_virt_ids'])) {
                        $networkData['connected_vms'][] = $vm;
                    }
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $networkData
    ]);
}

function handleCreateNetwork() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['name']) || !isset($data['type'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Name and type are required'
        ]);
        return;
    }

    // Create network
    $result = makeSlideApiCall("https://api.slide.tech/v1/network", $apiKey, 'POST', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create network'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleDeleteNetwork() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['network_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID is required'
        ]);
        return;
    }

    // Delete network
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($data['network_id']), $apiKey, 'DELETE');
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode === 404) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Network not found'
        ]);
        return;
    }

    if ($httpCode !== 204) {
        http_response_code($httpCode ?: 500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete network'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Network deleted successfully'
    ]);
}

function handleUpdateNetwork() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID is required'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Update network
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($_GET['id']), $apiKey, 'PATCH', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode === 404) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Network not found'
        ]);
        return;
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        http_response_code($httpCode ?: 500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update network'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handlePairAgent() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['device_id']) || !isset($data['pair_code'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Device ID and pair code are required'
        ]);
        return;
    }

    // Pair agent
    $result = makeSlideApiCall("https://api.slide.tech/v1/agent/pair", $apiKey, 'POST', [
        'device_id' => $data['device_id'],
        'pair_code' => $data['pair_code']
    ]);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        
        // Try to parse error message from response
        $errorData = json_decode($response, true);
        $errorMessage = 'Failed to pair agent';
        if (isset($errorData['message'])) {
            $errorMessage = $errorData['message'];
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleCreateAgentForPairing() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['device_id']) || !isset($data['display_name'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Device ID and display name are required'
        ]);
        return;
    }

    // Create agent for pairing
    $result = makeSlideApiCall("https://api.slide.tech/v1/agent", $apiKey, 'POST', [
        'device_id' => $data['device_id'],
        'display_name' => $data['display_name']
    ]);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create agent for pairing'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleUpdateAlert() {
    // NOTE: As of January 2025, the Slide API endpoint PATCH /v1/alert/{alert_id}
    // is returning 500 Internal Server Error when attempting to update alerts.
    // This appears to be a server-side issue with the Slide API.
    // The implementation below is correct according to their API documentation.
    
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['alert_id']) || !isset($data['resolved'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Alert ID and resolved status are required'
        ]);
        return;
    }

    // Update alert
    $result = makeSlideApiCall("https://api.slide.tech/v1/alert/" . urlencode($data['alert_id']), $apiKey, 'PATCH', [
        'resolved' => $data['resolved']
    ]);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    // Parse the response data to get error details
    $responseData = json_decode($response, true);

    if ($httpCode === 404) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Alert not found'
        ]);
        return;
    }

    if ($httpCode === 500) {
        http_response_code(500);
        $errorMessage = 'Internal server error';
        if (isset($responseData['message'])) {
            $errorMessage = $responseData['message'];
        }
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        return;
    }

    if ($httpCode !== 200) {
        http_response_code($httpCode ?: 500);
        $errorMessage = 'Failed to update alert';
        if (isset($responseData['message'])) {
            $errorMessage = $responseData['message'];
        } elseif (isset($responseData['details']) && is_array($responseData['details']) && !empty($responseData['details'])) {
            $errorMessage = $responseData['details'][0];
        }
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
} 

function handleGetClients() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    // Fetch clients
    $result = makeSlideApiCall("https://api.slide.tech/v1/client?limit=50", $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch clients'
        ]);
        return;
    }

    $clientsData = json_decode($response, true);
    if (!$clientsData || !isset($clientsData['data'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response from API'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'data' => $clientsData['data']
    ]);
} 

// IPsec handlers
function handleCreateIpsec() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID is required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Create IPsec connection
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($networkId) . "/ipsec", $apiKey, 'POST', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create IPsec connection'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleUpdateIpsec() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId']) || !isset($_GET['ipsecId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID and IPsec ID are required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $ipsecId = $_GET['ipsecId'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Update IPsec connection
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($networkId) . "/ipsec/" . urlencode($ipsecId), $apiKey, 'PATCH', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update IPsec connection'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleDeleteIpsec() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId']) || !isset($_GET['ipsecId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID and IPsec ID are required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $ipsecId = $_GET['ipsecId'];

    // Delete IPsec connection
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($networkId) . "/ipsec/" . urlencode($ipsecId), $apiKey, 'DELETE');
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 204) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete IPsec connection'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'IPsec connection deleted successfully'
    ]);
}

// Port forward handlers
function handleCreatePortForward() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID is required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Create port forward
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($networkId) . "/port-forward", $apiKey, 'POST', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create port forward'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleUpdatePortForward() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId']) || !isset($_GET['portForwardId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID and port forward ID are required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $portForwardId = $_GET['portForwardId'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Update port forward
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($networkId) . "/port-forward/" . urlencode($portForwardId), $apiKey, 'PATCH', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201 && $httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update port forward'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleDeletePortForward() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId']) || !isset($_GET['portForwardId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID and port forward ID are required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $portForwardId = $_GET['portForwardId'];

    // Delete port forward
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/" . urlencode($networkId) . "/port-forward/" . urlencode($portForwardId), $apiKey, 'DELETE');
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 204) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete port forward'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Port forward deleted successfully'
    ]);
}

// WireGuard peer handlers
function handleCreateWgPeer() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID is required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Create WireGuard peer
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/", $apiKey, 'POST', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create WireGuard peer'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleUpdateWgPeer() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId']) || !isset($_GET['wgPeerId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID and WireGuard peer ID are required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $wgPeerId = $_GET['wgPeerId'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Update WireGuard peer
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/", $apiKey, 'PATCH', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201 && $httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update WireGuard peer'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleDeleteWgPeer() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['networkId']) || !isset($_GET['wgPeerId'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Network ID and WireGuard peer ID are required'
        ]);
        return;
    }

    $networkId = $_GET['networkId'];
    $wgPeerId = $_GET['wgPeerId'];

    // Delete WireGuard peer
    $result = makeSlideApiCall("https://api.slide.tech/v1/network/", $apiKey, 'DELETE');
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 204) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete WireGuard peer'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'WireGuard peer deleted successfully'
    ]);
}

// Accounts handlers
function handleGetAccounts() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    $queryParams = [
        'offset=' . $offset,
        'limit=' . $limit,
        'sort_by=name',
        'sort_asc=true'
    ];

    $ch = curl_init();
    $result = makeSlideApiCall("https://api.slide.tech/v1/account?" . implode('&', $queryParams), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch accounts'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData['data'] ?? [],
        'pagination' => $responseData['pagination'] ?? []
    ]);
}

function handleGetAccount() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['account_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Account ID is required'
        ]);
        return;
    }

    $accountId = $_GET['account_id'];

    $ch = curl_init();
    $result = makeSlideApiCall("https://api.slide.tech/v1/account/" . urlencode($accountId), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch account'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleUpdateAccount() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['account_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Account ID is required'
        ]);
        return;
    }

    $accountId = $_GET['account_id'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $result = makeSlideApiCall("https://api.slide.tech/v1/account/", $apiKey, 'PATCH', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update account'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

// Users handlers
function handleGetUsers() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    $queryParams = [
        'offset=' . $offset,
        'limit=' . $limit,
        'sort_by=id',
        'sort_asc=true'
    ];

    $ch = curl_init();
    $result = makeSlideApiCall("https://api.slide.tech/v1/user?" . implode('&', $queryParams), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch users'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData['data'] ?? [],
        'pagination' => $responseData['pagination'] ?? []
    ]);
}

function handleGetUser() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        return;
    }

    $userId = $_GET['user_id'];

    $ch = curl_init();
    $result = makeSlideApiCall("https://api.slide.tech/v1/user/" . urlencode($userId), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch user'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

// Audit handlers
function handleGetAudits() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    $queryParams = [
        'offset=' . $offset,
        'limit=' . $limit,
        'sort_by=audit_time',
        'sort_asc=false'
    ];

    if (isset($_GET['audit_action_name']) && $_GET['audit_action_name']) {
        $queryParams[] = 'audit_action_name=' . urlencode($_GET['audit_action_name']);
    }

    if (isset($_GET['audit_resource_type_name']) && $_GET['audit_resource_type_name']) {
        $queryParams[] = 'audit_resource_type_name=' . urlencode($_GET['audit_resource_type_name']);
    }

    $ch = curl_init();
    $result = makeSlideApiCall("https://api.slide.tech/v1/audit?" . implode('&', $queryParams), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch audits'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData['data'] ?? [],
        'pagination' => $responseData['pagination'] ?? []
    ]);
}

function handleGetAudit() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['audit_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Audit ID is required'
        ]);
        return;
    }

    $auditId = $_GET['audit_id'];

    $result = makeSlideApiCall("https://api.slide.tech/v1/audit/" . urlencode($auditId), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch audit'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleGetAuditActions() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $queryParams = [
        'offset=0',
        'limit=50',
        'sort_by=name',
        'sort_asc=true'
    ];

    $result = makeSlideApiCall("https://api.slide.tech/v1/audit/action?" . implode('&', $queryParams), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch audit actions'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData['data'] ?? []
    ]);
}

function handleGetAuditResourceTypes() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $queryParams = [
        'offset=0',
        'limit=50',
        'sort_by=name',
        'sort_asc=true'
    ];

    $result = makeSlideApiCall("https://api.slide.tech/v1/audit/resource?" . implode('&', $queryParams), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch audit resource types'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData['data'] ?? []
    ]);
}

// Device power handlers
function handleRebootDevice() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['device_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Device ID is required'
        ]);
        return;
    }

    $deviceId = $_GET['device_id'];

    $result = makeSlideApiCall("https://api.slide.tech/v1/device/", $apiKey, 'POST');
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 202) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reboot device'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData,
        'message' => 'Device reboot initiated'
    ]);
}

function handlePowerOffDevice() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['device_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Device ID is required'
        ]);
        return;
    }

    $deviceId = $_GET['device_id'];

    $result = makeSlideApiCall("https://api.slide.tech/v1/device/", $apiKey, 'POST');
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 202) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to power off device'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData,
        'message' => 'Device power off initiated'
    ]);
}

// Backups handlers
function handleGetBackups() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    $queryParams = [
        'offset=' . $offset,
        'limit=' . $limit,
        'sort_by=start_time',
        'sort_asc=false'
    ];

    if (isset($_GET['agent_id']) && $_GET['agent_id']) {
        $queryParams[] = 'agent_id=' . urlencode($_GET['agent_id']);
    }

    if (isset($_GET['device_id']) && $_GET['device_id']) {
        $queryParams[] = 'device_id=' . urlencode($_GET['device_id']);
    }

    if (isset($_GET['snapshot_id']) && $_GET['snapshot_id']) {
        $queryParams[] = 'snapshot_id=' . urlencode($_GET['snapshot_id']);
    }

    $result = makeSlideApiCall("https://api.slide.tech/v1/backup?" . implode('&', $queryParams), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch backups'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData['data'] ?? [],
        'pagination' => $responseData['pagination'] ?? []
    ]);
}

function handleGetBackup() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['backup_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Backup ID is required'
        ]);
        return;
    }

    $backupId = $_GET['backup_id'];

    $result = makeSlideApiCall("https://api.slide.tech/v1/backup/" . urlencode($backupId), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch backup'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

// Enhanced Client handlers
function handleCreateClient() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $result = makeSlideApiCall("https://api.slide.tech/v1/client", $apiKey, 'POST', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 201) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create client'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleGetClient() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['client_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Client ID is required'
        ]);
        return;
    }

    $clientId = $_GET['client_id'];

    $result = makeSlideApiCall("https://api.slide.tech/v1/client/" . urlencode($clientId), $apiKey);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch client'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleUpdateClient() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['client_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Client ID is required'
        ]);
        return;
    }

    $clientId = $_GET['client_id'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $result = makeSlideApiCall("https://api.slide.tech/v1/client/", $apiKey, 'PATCH', $data);
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update client'
        ]);
        return;
    }

    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
}

function handleDeleteClient() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    if (!isset($_GET['client_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Client ID is required'
        ]);
        return;
    }

    $clientId = $_GET['client_id'];

    $result = makeSlideApiCall("https://api.slide.tech/v1/client/", $apiKey, 'DELETE');
    $response = $result['response'];
    $httpCode = $result['httpCode'];

    if ($httpCode !== 204) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete client'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Client deleted successfully'
    ]);
}

function handleGetSlowQueries() {
    $apiKey = getApiKey();
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'API key not found'
        ]);
        return;
    }

    $logFile = '/tmp/slow_api_queries.log';
    
    // Check if log file exists
    if (!file_exists($logFile)) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        return;
    }

    // Get the last 6 characters of the current API key for filtering
    $apiKeySuffix = substr($apiKey, -6);
    
    // Get date range filters if provided
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    
    // Read the log file
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to read log file'
        ]);
        return;
    }
    
    // Parse and filter log entries
    $entries = [];
    $maxEntries = 500; // Limit to prevent performance issues
    
    // Process lines in reverse order (newest first)
    $lines = array_reverse($lines);
    
    foreach ($lines as $line) {
        if (count($entries) >= $maxEntries) {
            break;
        }
        
        // Parse log line format: [2025-01-15 10:30:45] 250ms GET https://api.slide.tech/v1/device API_KEY: ...abc123
        if (preg_match('/^\[([\d\-: ]+)\]\s+(\d+)ms\s+(\w+)\s+(.*?)\s+API_KEY:\s+\.\.\.(\w+)$/', $line, $matches)) {
            $timestamp = $matches[1];
            $duration = intval($matches[2]);
            $method = $matches[3];
            $url = $matches[4];
            $loggedApiKeySuffix = $matches[5];
            
            // Security check: only show queries matching this user's API key
            if ($loggedApiKeySuffix !== $apiKeySuffix) {
                continue;
            }
            
            // Date range filtering
            if ($dateFrom && $timestamp < $dateFrom) {
                continue;
            }
            if ($dateTo && $timestamp > $dateTo . ' 23:59:59') {
                continue;
            }
            
            // Determine severity level
            $severity = 'low';
            if ($duration >= 1000) {
                $severity = 'high';
            } else {
                if ($duration >= 500) {
                    $severity = 'medium';
                }
            }
            
            $entries[] = [
                'timestamp' => $timestamp,
                'duration' => $duration,
                'method' => $method,
                'url' => $url,
                'severity' => $severity
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $entries,
        'total' => count($entries)
    ]);
} 