<!DOCTYPE html>
<?php
require_once 'include/getApiKey.php';
$hasKey = hasApiKey();

if (!$hasKey) {
    header('Location: key-management.php');
    exit;
}
?>
<html lang="en" data-bs-theme="dark">
<head>
    <title>More - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .more-list {
            padding: 1rem;
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }

        .more-section {
            margin-bottom: 2rem;
        }

        .more-section-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
            padding-left: 0.5rem;
        }

        .more-item {
            background-color: rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #ffffff;
            transition: all 0.2s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .more-item:hover {
            background-color: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateX(4px);
        }

        .more-item-icon {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(13, 110, 253, 0.25);
            border-radius: 8px;
            margin-right: 1rem;
            color: #5fa3ff;
        }

        .more-item-content {
            flex: 1;
        }

        .more-item-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            color: #ffffff;
        }

        .more-item-description {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.75);
            margin: 0;
        }

        .more-item-arrow {
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.25rem;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">More</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="more-list">
            <!-- Overview Section -->
            <div class="more-section">
                <div class="more-section-title">Overview</div>
                
                <a href="/mobile/index.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-house"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Dashboard</div>
                        <div class="more-item-description">View system overview</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>
            </div>

            <!-- Management Section -->
            <div class="more-section">
                <div class="more-section-title">Management</div>
                
                <a href="/mobile/accounts.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Accounts</div>
                        <div class="more-item-description">View and manage accounts</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>

                <a href="/mobile/users.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Users</div>
                        <div class="more-item-description">View team members and roles</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>

                <a href="/mobile/clients.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Clients</div>
                        <div class="more-item-description">Manage client organizations</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>
            </div>

            <!-- Operations Section -->
            <div class="more-section">
                <div class="more-section-title">Operations</div>
                
                <a href="/mobile/backups.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-database"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Backups</div>
                        <div class="more-item-description">View all backup attempts</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>

                <a href="/mobile/restores.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-arrow-clockwise"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Restores</div>
                        <div class="more-item-description">Manage file and VM restores</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>
            </div>

            <!-- Networking Section -->
            <div class="more-section">
                <div class="more-section-title">Networking</div>
                
                <a href="/mobile/networks.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Networks</div>
                        <div class="more-item-description">Disaster recovery networks</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>
            </div>

            <!-- Compliance Section -->
            <div class="more-section">
                <div class="more-section-title">Compliance</div>
                
                <a href="/mobile/audits.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Audit Logs</div>
                        <div class="more-item-description">View system activity logs</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>
            </div>

            <!-- Diagnostics Section -->
            <div class="more-section">
                <div class="more-section-title">Diagnostics</div>
                
                <a href="/mobile/slow-queries.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">Slow Queries</div>
                        <div class="more-item-description">View API performance metrics</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>
            </div>

            <!-- Settings Section -->
            <div class="more-section">
                <div class="more-section-title">Settings</div>
                
                <a href="/mobile/key-management.php" class="more-item">
                    <div class="more-item-icon">
                        <i class="bi bi-key"></i>
                    </div>
                    <div class="more-item-content">
                        <div class="more-item-title">API Key</div>
                        <div class="more-item-description">Manage your API token</div>
                    </div>
                    <i class="bi bi-chevron-right more-item-arrow"></i>
                </a>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>

