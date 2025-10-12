<?php
session_start();

// If already authenticated, redirect to index
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('../include/getApiKey.php');
    
    // If we can get a valid API key, consider the user authenticated
    $apiKey = getApiKey();
    if ($apiKey) {
        $_SESSION['authenticated'] = true;
        header('Location: ' . ($_SESSION['redirect_after_login'] ?? 'index.php'));
        unset($_SESSION['redirect_after_login']);
        exit;
    } else {
        $error = 'Authentication failed. Please try again.';
    }
}

// Store the current page as redirect target after login
if (isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Login - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        /* Add padding for the top safe area */
        header.navbar {
            padding-top: env(safe-area-inset-top);
            background-color: var(--bs-dark) !important; /* Ensure dark background extends into safe area */
        }

        /* Ensure the body background extends into safe areas */
        body {
            background-color: var(--bs-dark);
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
            padding-top: env(safe-area-inset-top);
        }

        .content-area {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Adjust for safe area */
        }
        .login-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1 class="h4 mb-4 text-center">Login Required</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login with Slide</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 