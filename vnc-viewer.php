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
    <title>VNC Viewer - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            background: #000;
        }
        .header {
            flex: 0 0 auto;
        }
        #screen {
            flex: 1;
            background: #000;
            overflow: hidden;
        }
        .loading-container {
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 1000;
        }
        .loading-container .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        .loading-text {
            margin-top: 1rem;
            color: #fff;
        }
        .connection-error {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        .connection-status {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
            margin-right: 1rem;
        }
        .connection-status.connected {
            color: var(--bs-success);
        }
        .connection-status.disconnected {
            color: var(--bs-danger);
        }
        .connection-status i {
            margin-right: 0.25rem;
        }
        /* Add new styles for the keyboard input */
        #keyboard-input {
            position: fixed;
            top: -100px;
            opacity: 0;
            height: 0;
            width: 0;
        }
        /* Add styles for keyboard visibility */
        body.keyboard-visible #screen {
            margin-bottom: 40vh; /* Adjust this value based on keyboard height */
        }
        #screen {
            transition: margin-bottom 0.3s ease;
        }
    </style>
    <script type="module">
        import RFB from './vendor/novnc/core/rfb.js';
        
        let rfb;
        const urlParams = new URLSearchParams(window.location.search);
        const virtId = urlParams.get('id');
        const wsUri = urlParams.get('ws');
        const password = urlParams.get('password');

        if (!virtId || !wsUri || !password) {
            window.location.href = 'restores.php';
        }

        function updateState(text, isConnected = false) {
            const status = document.querySelector('.connection-status');
            status.textContent = text;
            status.className = 'connection-status ' + (isConnected ? 'connected' : 'disconnected');
        }

        function showError(message) {
            document.querySelector('.loading-container').style.display = 'none';
            document.querySelector('.connection-error').style.display = 'block';
            document.getElementById('screen').style.display = 'none';
            document.querySelector('.connection-error p').textContent = message;
            updateState('Disconnected');
        }

        function hideError() {
            document.querySelector('.loading-container').style.display = 'flex';
            document.querySelector('.connection-error').style.display = 'none';
            document.getElementById('screen').style.display = 'block';
            updateState('Connecting...');
        }

        function connectedToServer(e) {
            console.log('Connected to server');
            document.querySelector('.loading-container').style.display = 'none';
            updateState('Connected', true);
        }

        function disconnectedFromServer(e) {
            if (e.detail.clean) {
                showError('Disconnected from server');
            } else {
                showError('Something went wrong, connection is closed');
            }
        }

        function sendCtrlAltDel() {
            if (rfb) {
                rfb.sendCtrlAltDel();
                return false;
            }
        }

        function connect() {
            hideError();
            updateState('Connecting...');

            // Clean up existing connection if any
            if (rfb) {
                rfb.disconnect();
                rfb = null;
            }

            try {
                rfb = new RFB(document.getElementById('screen'), wsUri, {
                    credentials: { password }
                });
                
                rfb.addEventListener("connect", connectedToServer);
                rfb.addEventListener("disconnect", disconnectedFromServer);
                rfb.addEventListener("credentialsrequired", () => {
                    console.log('Sending credentials');
                    rfb.sendCredentials({ password });
                });
                
                rfb.scaleViewport = true;
                rfb.viewOnly = false;

            } catch (err) {
                console.error('Failed to create RFB client:', err);
                showError('Failed to create VNC connection: ' + err.message);
            }
        }

        // Make functions globally available
        window.connect = connect;
        window.sendCtrlAltDel = sendCtrlAltDel;

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && rfb) {
                rfb.disconnect();
            } else if (!document.hidden && !rfb) {
                connect();
            }
        });

        // Handle fullscreen
        window.toggleFullscreen = function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        };

        // Update keyboard functions
        window.toggleKeyboard = function() {
            const input = document.getElementById('keyboard-input');
            input.style.opacity = '0';
            input.focus();
        };

        // Prevent keyboard from hiding when clicking the VNC screen
        document.getElementById('screen').addEventListener('mousedown', (e) => {
            const input = document.getElementById('keyboard-input');
            if (document.body.classList.contains('keyboard-visible')) {
                e.preventDefault();
                // Refocus the input after a short delay
                setTimeout(() => {
                    input.focus();
                }, 10);
            }
        });

        // Handle input blur to hide keyboard only when clicking outside VNC
        document.getElementById('keyboard-input').addEventListener('blur', (e) => {
            // Check if the click was inside the VNC screen
            const screen = document.getElementById('screen');
            const relatedTarget = e.relatedTarget || document.activeElement;
            
            if (!screen.contains(relatedTarget)) {
                document.body.classList.remove('keyboard-visible');
            } else {
                // Refocus the input if clicking inside VNC screen
                setTimeout(() => {
                    e.target.focus();
                }, 10);
            }
        });

        // Add keyboard visibility detection
        const originalHeight = window.visualViewport.height;
        window.visualViewport.addEventListener('resize', () => {
            const currentHeight = window.visualViewport.height;
            if (currentHeight < originalHeight) {
                // Keyboard is shown
                document.body.classList.add('keyboard-visible');
                // Scroll to ensure the focused element is visible
                setTimeout(() => {
                    window.scrollTo(0, 0);
                }, 100);
            } else {
                // Keyboard is hidden
                document.body.classList.remove('keyboard-visible');
            }
        });

        // Handle input events
        document.getElementById('keyboard-input').addEventListener('keydown', (e) => {
            if (rfb) {
                let keysym;
                
                // Handle special keys
                switch (e.key) {
                    case 'Enter':
                        keysym = 0xFF0D;  // Return key
                        break;
                    case 'Backspace':
                        keysym = 0xFF08;  // Backspace key
                        break;
                    case 'Delete':
                        keysym = 0xFFFF;  // Delete key
                        break;
                    case 'Tab':
                        keysym = 0xFF09;  // Tab key
                        break;
                    case 'Escape':
                        keysym = 0xFF1B;  // Escape key
                        break;
                    case 'ArrowLeft':
                        keysym = 0xFF51;  // Left arrow
                        break;
                    case 'ArrowUp':
                        keysym = 0xFF52;  // Up arrow
                        break;
                    case 'ArrowRight':
                        keysym = 0xFF53;  // Right arrow
                        break;
                    case 'ArrowDown':
                        keysym = 0xFF54;  // Down arrow
                        break;
                    default:
                        // For regular characters, use their character code
                        keysym = e.key.length === 1 ? e.key.charCodeAt(0) : 0;
                }

                if (keysym) {
                    rfb.sendKey(keysym);
                    e.preventDefault();
                }
            }
        });

        // Clear the input periodically to ensure it doesn't get too long
        setInterval(() => {
            document.getElementById('keyboard-input').value = '';
        }, 100);

        // Initial connection
        connect();
    </script>
</head>
<body>
    <!-- Header -->
    <header class="navbar bg-dark border-bottom header">
        <div class="container-fluid">
            <a href="#" onclick="history.back()" class="btn btn-link text-light">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="navbar-brand mb-0 h1">VNC Viewer</span>
            <div class="d-flex align-items-center">
                <span class="connection-status">Initializing...</span>
                <button class="btn btn-outline-light me-2" onclick="sendCtrlAltDel()">
                    <i class="bi bi-keyboard"></i>
                    Ctrl+Alt+Del
                </button>
                <button class="btn btn-outline-light me-2" onclick="toggleKeyboard()">
                    <i class="bi bi-keyboard-fill"></i>
                </button>
                <button class="btn btn-outline-light" onclick="toggleFullscreen()">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Add keyboard input element -->
    <input type="text" id="keyboard-input" autocomplete="off" />

    <!-- Loading Overlay -->
    <div class="loading-container">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Connecting...</span>
        </div>
        <div class="loading-text">Connecting to virtual machine...</div>
    </div>

    <!-- Connection Error -->
    <div class="connection-error">
        <h4 class="text-danger mb-3">Connection Failed</h4>
        <p class="text-muted">Unable to connect to the virtual machine. Please check if the VM is running.</p>
        <button class="btn btn-primary" onclick="connect()">Try Again</button>
    </div>

    <!-- VNC Screen -->
    <div id="screen"></div>
</body>
</html> 