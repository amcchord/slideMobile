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
    <title>Audit Logs - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .audit-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }

        .audit-filters {
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .audit-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: background-color 0.2s ease-out;
        }

        .audit-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .audit-item:last-child {
            border-bottom: none;
        }

        .audit-timeline-marker {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #0d6efd;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .audit-action {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #ffffff;
        }

        .audit-description {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }

        .audit-meta {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
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
            <span class="navbar-brand mb-0 h1">Audit Logs</span>
        </div>
    </header>

    <!-- Filters -->
    <div class="audit-filters">
        <div class="row g-2">
            <div class="col-6">
                <select class="form-select form-select-sm" id="actionFilter" onchange="loadAudits()">
                    <option value="">All Actions</option>
                </select>
            </div>
            <div class="col-6">
                <select class="form-select form-select-sm" id="resourceFilter" onchange="loadAudits()">
                    <option value="">All Resources</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="audit-list" id="auditList">
            <!-- Audit logs will be loaded here -->
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </main>

    <!-- Audit Details Modal -->
    <div class="modal fade" id="auditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title">Audit Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="auditModalBody">
                    <!-- Audit details will be loaded here -->
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
    <script>
        let isLoading = false;
        let auditModalInstance = null;

        async function loadFilters() {
            try {
                const [actionsResponse, resourcesResponse] = await Promise.all([
                    fetch('/mobile/mobileSlideApi.php?action=getAuditActions'),
                    fetch('/mobile/mobileSlideApi.php?action=getAuditResourceTypes')
                ]);

                const actionsData = await actionsResponse.json();
                const resourcesData = await resourcesResponse.json();

                if (actionsData.success && actionsData.data) {
                    const actionFilter = document.getElementById('actionFilter');
                    actionsData.data.forEach(action => {
                        const option = document.createElement('option');
                        option.value = action.name;
                        option.textContent = action.description || action.name;
                        actionFilter.appendChild(option);
                    });
                }

                if (resourcesData.success && resourcesData.data) {
                    const resourceFilter = document.getElementById('resourceFilter');
                    resourcesData.data.forEach(resource => {
                        const option = document.createElement('option');
                        option.value = resource.name;
                        option.textContent = resource.name;
                        resourceFilter.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading filters:', error);
            }
        }

        async function loadAudits() {
            if (isLoading) return;
            isLoading = true;

            const auditList = document.getElementById('auditList');
            auditList.classList.add('loading');

            const actionFilter = document.getElementById('actionFilter').value;
            const resourceFilter = document.getElementById('resourceFilter').value;

            let url = '/mobile/mobileSlideApi.php?action=getAudits&limit=50';
            if (actionFilter) url += `&audit_action_name=${encodeURIComponent(actionFilter)}`;
            if (resourceFilter) url += `&audit_resource_type_name=${encodeURIComponent(resourceFilter)}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                auditList.classList.remove('loading');

                if (data.success && data.data && data.data.length > 0) {
                    displayAudits(data.data);
                } else {
                    auditList.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-file-text"></i>
                            <div>No audit logs found</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading audits:', error);
                auditList.classList.remove('loading');
                auditList.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>Failed to load audit logs</div>
                    </div>
                `;
            } finally {
                isLoading = false;
            }
        }

        function displayAudits(audits) {
            const auditList = document.getElementById('auditList');
            auditList.innerHTML = audits.map(audit => `
                <div class="audit-item" onclick="showAuditDetails('${audit.audit_id}')">
                    <div class="audit-action">
                        <span class="audit-timeline-marker"></span>
                        ${escapeHtml(audit.action)}
                    </div>
                    <div class="audit-description">${escapeHtml(audit.description)}</div>
                    <div class="audit-meta">
                        <span><i class="bi bi-folder"></i> ${escapeHtml(audit.resource_type)}</span>
                        <span><i class="bi bi-clock"></i> ${formatDate(audit.audit_time)}</span>
                        ${audit.user_id ? `<span><i class="bi bi-person"></i> User ${audit.user_id}</span>` : ''}
                        ${audit.system ? '<span class="status-badge secondary">System</span>' : ''}
                    </div>
                </div>
            `).join('');
        }

        async function showAuditDetails(auditId) {
            if (!auditModalInstance) {
                auditModalInstance = new bootstrap.Modal(document.getElementById('auditModal'));
            }

            const modalBody = document.getElementById('auditModalBody');
            modalBody.innerHTML = `
                <div class="text-center p-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            auditModalInstance.show();

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getAudit&audit_id=${encodeURIComponent(auditId)}`);
                const data = await response.json();

                if (data.success && data.data) {
                    const audit = data.data;
                    modalBody.innerHTML = `
                        <div class="info-card">
                            <div class="card-label">Action</div>
                            <div class="card-value">${escapeHtml(audit.action)}</div>
                        </div>
                        <div class="info-card">
                            <div class="card-label">Description</div>
                            <div class="card-value">${escapeHtml(audit.description)}</div>
                        </div>
                        <div class="info-card">
                            <div class="card-label">Resource Type</div>
                            <div class="card-value">${escapeHtml(audit.resource_type)}</div>
                        </div>
                        <div class="info-card">
                            <div class="card-label">Resource ID</div>
                            <div class="card-value">${escapeHtml(audit.resource_id)}</div>
                        </div>
                        <div class="info-card">
                            <div class="card-label">Time</div>
                            <div class="card-value">${formatDate(audit.audit_time)}</div>
                        </div>
                        <div class="info-card">
                            <div class="card-label">Source</div>
                            <div class="card-value">${escapeHtml(audit.source)}</div>
                        </div>
                        ${audit.user_id ? `
                            <div class="info-card">
                                <div class="card-label">User ID</div>
                                <div class="card-value">${escapeHtml(audit.user_id)}</div>
                            </div>
                        ` : ''}
                        ${audit.client_id ? `
                            <div class="info-card">
                                <div class="card-label">Client ID</div>
                                <div class="card-value">${escapeHtml(audit.client_id)}</div>
                            </div>
                        ` : ''}
                        <div class="info-card">
                            <div class="card-label">Action Fields</div>
                            <div class="card-value"><pre style="color: #ffffff; font-size: 0.875rem;">${escapeHtml(audit.action_fields_json || 'N/A')}</pre></div>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = '<div class="text-center text-danger">Failed to load audit details</div>';
                }
            } catch (error) {
                console.error('Error loading audit details:', error);
                modalBody.innerHTML = '<div class="text-center text-danger">Failed to load audit details</div>';
            }
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
        document.addEventListener('DOMContentLoaded', () => {
            loadFilters();
            loadAudits();
        });
    </script>
</body>
</html>

