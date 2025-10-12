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
    <title>Users - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .user-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }

        .user-section {
            margin-bottom: 1.5rem;
        }

        .user-section-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.75);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.5rem 1rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            display: flex;
            align-items: center;
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.25rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .user-info {
            flex-grow: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #ffffff;
        }

        .user-email {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.75);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            <span class="navbar-brand mb-0 h1">Users</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="user-list" id="userList">
            <!-- Users will be loaded here -->
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

        const roleNames = {
            'r_account_owner': { name: 'Owner', class: 'owner' },
            'r_account_admin': { name: 'Admin', class: 'admin' },
            'r_account_bc_admin': { name: 'BC Admin', class: 'admin' },
            'r_account_bc_tech': { name: 'Tech', class: 'tech' },
            'r_account_readonly': { name: 'Read Only', class: 'readonly' }
        };

        async function loadUsers() {
            if (isLoading) return;
            isLoading = true;

            const userList = document.getElementById('userList');
            userList.classList.add('loading');

            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getUsers');
                const data = await response.json();

                userList.classList.remove('loading');

                if (data.success && data.data && data.data.length > 0) {
                    displayUsers(data.data);
                } else {
                    userList.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-people"></i>
                            <div>No users found</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading users:', error);
                userList.classList.remove('loading');
                userList.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>Failed to load users</div>
                    </div>
                `;
            } finally {
                isLoading = false;
            }
        }

        function displayUsers(users) {
            // Group users by role
            const grouped = {};
            users.forEach(user => {
                const roleId = user.role_id || 'r_account_readonly';
                if (!grouped[roleId]) {
                    grouped[roleId] = [];
                }
                grouped[roleId].push(user);
            });

            // Display in order of role importance
            const roleOrder = ['r_account_owner', 'r_account_admin', 'r_account_bc_admin', 'r_account_bc_tech', 'r_account_readonly'];
            
            const userList = document.getElementById('userList');
            userList.innerHTML = roleOrder.map(roleId => {
                if (!grouped[roleId] || grouped[roleId].length === 0) return '';
                
                const roleInfo = roleNames[roleId] || { name: 'User', class: 'secondary' };
                
                return `
                    <div class="user-section">
                        <div class="user-section-title">${roleInfo.name}s</div>
                        ${grouped[roleId].map(user => `
                            <div class="user-item">
                                <div class="user-avatar">${getInitials(user.display_name || user.first_name + ' ' + user.last_name)}</div>
                                <div class="user-info">
                                    <div class="user-name">
                                        ${escapeHtml(user.display_name || user.first_name + ' ' + user.last_name)}
                                        <span class="role-badge ${roleInfo.class}">${roleInfo.name}</span>
                                    </div>
                                    <div class="user-email">${escapeHtml(user.email)}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }).join('');
        }

        function getInitials(name) {
            if (!name) return '?';
            const parts = name.trim().split(' ');
            if (parts.length >= 2) {
                return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load users when page loads
        document.addEventListener('DOMContentLoaded', loadUsers);
    </script>
</body>
</html>

