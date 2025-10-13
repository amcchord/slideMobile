<?php
require_once '../include/getApiKey.php';
if (hasApiKey()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code - Slide Mobile</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="../fonts/dattoDin/dattoDin.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        #video-container {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            position: relative;
        }

        #qr-video {
            width: 100%;
            border-radius: 8px;
        }

        .scan-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid var(--bs-primary);
            border-radius: 8px;
            pointer-events: none;
        }

        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: var(--bs-primary);
            animation: scan 2s linear infinite;
        }

        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <a href="index.php" class="btn btn-outline-light">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="navbar-brand mb-0 h1">Scan QR Code</span>
            <div style="width: 40px"></div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-center p-3">
        <div id="video-container" class="mb-3">
            <video id="qr-video" playsinline></video>
            <div class="scan-overlay">
                <div class="scan-line"></div>
            </div>
        </div>
        <div id="error-message" class="alert alert-danger d-none"></div>
        <p class="text-center text-muted">Point your camera at the QR code generated on the desktop site</p>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const video = document.getElementById('qr-video');
            const errorMessage = document.getElementById('error-message');
            let scanning = true;

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: "environment" }
                });
                video.srcObject = stream;
                await video.play();

                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                async function scan() {
                    if (!scanning) return;

                    if (video.readyState === video.HAVE_ENOUGH_DATA) {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);

                        const code = jsQR(imageData.data, imageData.width, imageData.height);
                        if (code) {
                            scanning = false;
                            // Stop the camera stream
                            stream.getTracks().forEach(track => track.stop());
                            
                            // Create and submit form to setKeyCookie.php for validation and encryption
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'setKeyCookie.php';
                            
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'api_key';
                            input.value = code.data;
                            
                            form.appendChild(input);
                            document.body.appendChild(form);
                            
                            // Show loading state
                            errorMessage.textContent = 'Validating API key...';
                            errorMessage.classList.remove('alert-danger');
                            errorMessage.classList.add('alert-info');
                            errorMessage.classList.remove('d-none');
                            
                            form.submit();
                        }
                    }
                    requestAnimationFrame(scan);
                }

                scan();
            } catch (error) {
                errorMessage.textContent = 'Unable to access camera. Please make sure you have granted camera permissions.';
                errorMessage.classList.remove('d-none');
            }
        });
    </script>
</body>
</html> 