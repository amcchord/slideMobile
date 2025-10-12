<!DOCTYPE html>
<?php
require_once '../include/getApiKey.php';

$error_messages = [
    'invalid_key' => 'The API key provided is not valid. Please check the key and try again.',
    'cookie_error' => 'Unable to save the API key. Please check your browser settings.',
    'missing_key' => 'Please provide an API key.',
    'system_error' => 'A system error occurred while processing your request.'
];

$error = isset($_GET['error']) ? $_GET['error'] : null;
$error_message = isset($error_messages[$error]) ? $error_messages[$error] : null;
$detailed_message = isset($_GET['message']) ? $_GET['message'] : null;
?>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Enter API Key - Slide Mobile</title>
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
            <a href="key-management.php" class="btn btn-link text-white">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="navbar-brand mb-0 h1">Enter API Key</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1 container pt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <?php if ($error_message): ?>
                <div class="alert alert-danger mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                    <?php if (isset($_GET['code'])): ?>
                        <br><small class="text-muted">Error code: <?php echo htmlspecialchars($_GET['code']); ?></small>
                    <?php endif; ?>
                    <?php if ($detailed_message): ?>
                        <br><small class="text-muted">Details: <?php echo htmlspecialchars($detailed_message); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="card bg-dark border-secondary">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Enter Your API Key</h5>
                        <form action="setKeyCookie.php" method="post">
                            <div class="mb-4">
                                <label for="api_key" class="form-label">API Key</label>
                                <textarea class="form-control bg-dark text-white border-secondary" 
                                    id="api_key" 
                                    name="api_key" 
                                    rows="3" 
                                    placeholder="Paste your API key here"
                                    required></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-key-fill me-2"></i>Save API Key
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="mt-4 text-center text-muted">
                    <p class="mb-2">Don't have an API key?</p>
                    <p class="small">You can generate one from the desktop version of Slide in Settings → API Keys</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html> 