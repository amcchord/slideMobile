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
    <title>Clients - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .client-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }

        .client-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .client-item:last-child {
            border-bottom: none;
        }

        .client-info {
            flex-grow: 1;
            min-width: 0;
            cursor: pointer;
        }

        .client-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #ffffff;
        }

        .client-comments {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.75);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .client-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: 1rem;
        }

        .fab-button {
            position: fixed;
            bottom: calc(80px + env(safe-area-inset-bottom));
            right: max(1rem, env(safe-area-inset-right));
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease-out;
            z-index: 100;
        }

        .fab-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.5);
        }

        .fab-button:active {
            transform: scale(0.95);
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
            <span class="navbar-brand mb-0 h1">Clients</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="client-list" id="clientList">
            <!-- Clients will be loaded here -->
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </main>

    <!-- Floating Action Button -->
    <button class="fab-button" onclick="showCreateClientModal()">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- Client Modal (Create/Edit) -->
    <div class="modal fade" id="clientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="clientModalTitle">Create Client</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="clientForm">
                        <input type="hidden" id="clientId" value="">
                        <div class="mb-3">
                            <label for="clientName" class="form-label">Client Name</label>
                            <input type="text" class="form-control" id="clientName" required maxlength="128">
                        </div>
                        <div class="mb-3">
                            <label for="clientComments" class="form-label">Comments</label>
                            <textarea class="form-control" id="clientComments" rows="3" maxlength="1024"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveClient()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title">Delete Client</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this client?</p>
                    <p class="text-warning"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
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
        let clientModalInstance = null;
        let deleteModalInstance = null;
        let clientToDelete = null;

        async function loadClients() {
            if (isLoading) return;
            isLoading = true;

            const clientList = document.getElementById('clientList');
            clientList.classList.add('loading');

            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getClients');
                const data = await response.json();

                clientList.classList.remove('loading');

                if (data.success && data.data && data.data.length > 0) {
                    displayClients(data.data);
                } else {
                    clientList.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-briefcase"></i>
                            <div>No clients found</div>
                            <div class="mt-2"><small>Click the + button to add a client</small></div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading clients:', error);
                clientList.classList.remove('loading');
                clientList.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>Failed to load clients</div>
                    </div>
                `;
            } finally {
                isLoading = false;
            }
        }

        function displayClients(clients) {
            const clientList = document.getElementById('clientList');
            clientList.innerHTML = clients.map(client => `
                <div class="client-item">
                    <div class="client-info" onclick="showEditClientModal('${client.client_id}', '${escapeHtml(client.name)}', '${escapeHtml(client.comments || '')}')">
                        <div class="client-name">${escapeHtml(client.name)}</div>
                        ${client.comments ? `<div class="client-comments">${escapeHtml(client.comments)}</div>` : ''}
                    </div>
                    <div class="client-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="showEditClientModal('${client.client_id}', '${escapeHtml(client.name)}', '${escapeHtml(client.comments || '')}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="showDeleteModal('${client.client_id}', '${escapeHtml(client.name)}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function showCreateClientModal() {
            document.getElementById('clientModalTitle').textContent = 'Create Client';
            document.getElementById('clientId').value = '';
            document.getElementById('clientName').value = '';
            document.getElementById('clientComments').value = '';
            
            if (!clientModalInstance) {
                clientModalInstance = new bootstrap.Modal(document.getElementById('clientModal'));
            }
            clientModalInstance.show();
        }

        function showEditClientModal(clientId, name, comments) {
            document.getElementById('clientModalTitle').textContent = 'Edit Client';
            document.getElementById('clientId').value = clientId;
            document.getElementById('clientName').value = name;
            document.getElementById('clientComments').value = comments;
            
            if (!clientModalInstance) {
                clientModalInstance = new bootstrap.Modal(document.getElementById('clientModal'));
            }
            clientModalInstance.show();
        }

        async function saveClient() {
            const clientId = document.getElementById('clientId').value;
            const name = document.getElementById('clientName').value.trim();
            const comments = document.getElementById('clientComments').value.trim();

            if (!name) {
                alert('Please enter a client name');
                return;
            }

            const data = { name };
            if (comments) {
                data.comments = comments;
            }

            try {
                const isEdit = !!clientId;
                const url = isEdit 
                    ? `/mobile/mobileSlideApi.php?action=updateClient&client_id=${encodeURIComponent(clientId)}`
                    : '/mobile/mobileSlideApi.php?action=createClient';

                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    if (clientModalInstance) {
                        clientModalInstance.hide();
                    }
                    await loadClients();
                } else {
                    alert('Failed to save client');
                }
            } catch (error) {
                console.error('Error saving client:', error);
                alert('Failed to save client');
            }
        }

        function showDeleteModal(clientId, name) {
            clientToDelete = clientId;
            if (!deleteModalInstance) {
                deleteModalInstance = new bootstrap.Modal(document.getElementById('deleteModal'));
            }
            deleteModalInstance.show();
        }

        async function confirmDelete() {
            if (!clientToDelete) return;

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=deleteClient&client_id=${encodeURIComponent(clientToDelete)}`, {
                    method: 'POST'
                });

                const result = await response.json();

                if (result.success) {
                    if (deleteModalInstance) {
                        deleteModalInstance.hide();
                    }
                    clientToDelete = null;
                    await loadClients();
                } else {
                    alert('Failed to delete client');
                }
            } catch (error) {
                console.error('Error deleting client:', error);
                alert('Failed to delete client');
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/'/g, '&#39;');
        }

        // Load clients when page loads
        document.addEventListener('DOMContentLoaded', loadClients);
    </script>
</body>
</html>

