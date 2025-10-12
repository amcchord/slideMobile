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
    <title>New Restore - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .content-area {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }

        .restore-type-card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .restore-type-card:hover {
            transform: translateY(-2px);
        }
        .restore-type-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
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
            <span class="navbar-brand mb-0 h1">New Restore</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container py-4">
            <h5 class="mb-4">Select Restore Type</h5>
            
            <div class="row g-4">
                <!-- File Restore -->
                <div class="col-12 col-md-4">
                    <div class="card restore-type-card h-100" onclick="location.href='new-file-restore.php'">
                        <div class="card-body text-center">
                            <i class="bi bi-folder restore-type-icon"></i>
                            <h5 class="card-title">File Restore</h5>
                            <p class="card-text text-muted">
                                Browse and restore individual files and folders from your backups
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Image Export -->
                <!-- <div class="col-12 col-md-4">
                    <div class="card restore-type-card h-100" onclick="location.href='new-image-restore.php'">
                        <div class="card-body text-center">
                            <i class="bi bi-hdd restore-type-icon"></i>
                            <h5 class="card-title">Image Export</h5>
                            <p class="card-text text-muted">
                                Export a complete system image from a backup point
                            </p>
                        </div>
                    </div>
                </div> -->

                <!-- Virtual Machine -->
                <div class="col-12 col-md-4">
                    <div class="card restore-type-card h-100" onclick="location.href='new-vm-restore.php'">
                        <div class="card-body text-center">
                            <i class="bi bi-display restore-type-icon"></i>
                            <h5 class="card-title">Virtual Machine</h5>
                            <p class="card-text text-muted">
                                Create a new virtual machine from a backup point
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 