<!DOCTYPE html>
<?php
require_once 'include/getApiKey.php';
if (!hasApiKey()) {
    header('Location: index.php');
    exit;
}
?>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Backups - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .backup-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }

        .backup-filters {
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .backup-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .backup-item:last-child {
            border-bottom: none;
        }

        .backup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .backup-agent {
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
        }

        .backup-time {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 0.5rem;
        }

        .backup-duration {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.75);
        }

        .backup-error {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 3px solid #dc3545;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            padding: 1rem;
        }

        .loading .loading-spinner {
            display: flex;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Backups</span>
        </div>
    </header>

    <!-- Filters -->
    <div class="backup-filters">
        <select class="form-select form-select-sm" id="agentFilter" onchange="loadBackups()">
            <option value="">All Agents</option>
        </select>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="backup-list" id="backupList">
            <!-- Backups will be loaded here -->
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
    <script>
        let isLoading = false;
        let agents = [];

        const statusClasses = {
            'succeeded': 'success',
            'failed': 'danger',
            'canceled': 'secondary',
            'started': 'info',
            'pending': 'info',
            'transferring': 'info',
            'finalizing': 'info'
        };

        async function loadAgents() {
            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getAgents&limit=100');
                const data = await response.json();

                if (data.success && data.data) {
                    agents = data.data;
                    const agentFilter = document.getElementById('agentFilter');
                    
                    data.data.forEach(agent => {
                        const option = document.createElement('option');
                        option.value = agent.agent_id;
                        option.textContent = agent.display_name || agent.hostname;
                        agentFilter.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading agents:', error);
            }
        }

        async function loadBackups() {
            if (isLoading) return;
            isLoading = true;

            const backupList = document.getElementById('backupList');
            backupList.classList.add('loading');

            const agentFilter = document.getElementById('agentFilter').value;
            let url = '/mobile/mobileSlideApi.php?action=getBackups&limit=50';
            if (agentFilter) url += `&agent_id=${encodeURIComponent(agentFilter)}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                backupList.classList.remove('loading');

                if (data.success && data.data && data.data.length > 0) {
                    displayBackups(data.data);
                } else {
                    backupList.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-database"></i>
                            <div>No backups found</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading backups:', error);
                backupList.classList.remove('loading');
                backupList.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>Failed to load backups</div>
                    </div>
                `;
            } finally {
                isLoading = false;
            }
        }

        function displayBackups(backups) {
            const backupList = document.getElementById('backupList');
            backupList.innerHTML = backups.map(backup => {
                const agent = agents.find(a => a.agent_id === backup.agent_id);
                const agentName = agent ? (agent.display_name || agent.hostname) : backup.agent_id;
                const statusClass = statusClasses[backup.status] || 'secondary';
                const duration = calculateDuration(backup.started_at, backup.ended_at);
                
                return `
                    <div class="backup-item">
                        <div class="backup-header">
                            <div class="backup-agent">${escapeHtml(agentName)}</div>
                            <span class="status-badge ${statusClass}">${escapeHtml(backup.status)}</span>
                        </div>
                        <div class="backup-time">
                            <i class="bi bi-clock"></i> Started: ${formatDate(backup.started_at)}
                            ${backup.ended_at ? `<br><i class="bi bi-clock-fill"></i> Ended: ${formatDate(backup.ended_at)}` : ''}
                        </div>
                        ${duration ? `<div class="backup-duration">Duration: ${duration}</div>` : ''}
                        ${backup.snapshot_id ? `
                            <div class="backup-duration">
                                <i class="bi bi-check-circle"></i> Snapshot: ${backup.snapshot_id}
                            </div>
                        ` : ''}
                        ${backup.error_message ? `
                            <div class="backup-error">
                                <strong>Error:</strong> ${escapeHtml(backup.error_message)}
                                ${backup.error_code ? `<br><small>Code: ${backup.error_code}</small>` : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        }

        function calculateDuration(started, ended) {
            if (!started || !ended) return null;
            
            const start = new Date(started);
            const end = new Date(ended);
            const diff = end - start;
            
            const hours = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            
            if (hours > 0) {
                return `${hours}h ${minutes}m ${seconds}s`;
            }
            if (minutes > 0) {
                return `${minutes}m ${seconds}s`;
            }
            return `${seconds}s`;
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString();
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', async () => {
            await loadAgents();
            loadBackups();
        });
    </script>
</body>
</html>

