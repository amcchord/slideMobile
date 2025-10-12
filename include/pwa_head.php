<?php
// Common PWA head elements
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#212529">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Slide">
    <meta name="description" content="Mobile interface for Slide backup management">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/mobile/pwa/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/mobile/pwa/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/mobile/pwa/icons/icon-384x384.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/mobile/pwa/icons/icon-384x384.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/mobile/pwa/manifest.json">
    
    <!-- Common Stylesheets -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="/fonts/dattoDin/dattoDin.css" rel="stylesheet">
    <link href="/mobile/css/style.css" rel="stylesheet">
    
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/mobile/pwa/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(err => {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script> 