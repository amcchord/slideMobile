<!DOCTYPE html>
<?php
require_once '../include/getApiKey.php';
$hasKey = hasApiKey();
?>
<html lang="en" data-bs-theme="dark">
<head>
    <title>API Key Management - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        header.navbar {
            padding-top: env(safe-area-inset-top);
            background-color: var(--bs-dark) !important;
        }

        body {
            background-color: var(--bs-dark);
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }

        .content-area {
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <a href="index.php" class="btn btn-link text-white">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="navbar-brand mb-0 h1">API Key Management</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1 container pt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <?php if ($hasKey): ?>
                    <div class="card bg-dark border-secondary">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-4">Current API Key Status</h5>
                            <p class="text-success mb-4">
                                <i class="bi bi-check-circle-fill me-2"></i>API Key is set and active
                            </p>
                            <form action="setKeyCookie.php" method="post">
                                <input type="hidden" name="delete_key" value="1">
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-trash me-2"></i>Remove API Key
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card bg-dark border-secondary mb-4">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-4">Set Up API Key</h5>
                            <p class="text-muted mb-4">Choose how you want to set up your API key:</p>
                            <div class="d-grid gap-3">
                                <a href="manual-key-entry.php" class="btn btn-primary">
                                    <i class="bi bi-key-fill me-2"></i>Enter API Key Manually
                                </a>
                                <a href="scan.php" class="btn btn-primary">
                                    <i class="bi bi-qr-code-scan me-2"></i>Scan QR Code
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html> 