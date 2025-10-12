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
    <title>Browse Files - Slide Mobile</title>
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
        }

        .content-area {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Adjust for safe area */
        }

        .file-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom)); /* Space for bottom nav */
        }

        .file-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
            cursor: pointer;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-info {
            flex-grow: 1;
            min-width: 0; /* Allow text truncation */
        }

        .file-name {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-details {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
        }

        .download-button {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin-left: 1rem;
            min-width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .download-button i {
            font-size: 1rem;
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            padding: 1rem;
        }

        .loading .loading-spinner {
            display: flex;
        }

        .current-path {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            overflow-x: auto;
            white-space: nowrap;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <button onclick="window.location.href='restores.php'" class="btn btn-link text-white p-0 me-3">
                <i class="bi bi-arrow-left"></i>
            </button>
            <span class="navbar-brand mb-0 h1">Browse Files</span>
        </div>
    </header>

    <!-- Current Path -->
    <div id="currentPath" class="current-path d-none"></div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="file-list" id="fileList">
            <!-- Files will be loaded here -->
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script>
        let isLoading = false;
        const restoreId = new URLSearchParams(window.location.search).get('id');
        let currentPath = '';

        // Format file size for display
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Format date for display
        function formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleString();
        }

        function navigateToFolder(path) {
            currentPath = path;
            loadFiles();
        }

        // Handle file/folder download
        function handleDownload(event, button) {
            event.stopPropagation(); // Prevent folder navigation when clicking download
            
            const itemData = JSON.parse(button.dataset.item);
            
            if (itemData.type === 'directory') {
                // For directories, we'll need to implement folder download logic
                alert('Folder download coming soon');
                return;
            }

            // For files, use the first available download URI
            if (itemData.download_uris && itemData.download_uris.length > 0) {
                const downloadUri = itemData.download_uris[0].uri;
                window.open(downloadUri, '_blank');
            }
        }

        async function loadFiles() {
            if (isLoading) return;
            isLoading = true;

            document.querySelector('.loading-spinner').style.display = 'flex';
            const container = document.getElementById('fileList');
            const pathDisplay = document.getElementById('currentPath');
            container.innerHTML = '';

            try {
                // Validate file restore ID format
                if (!restoreId || !restoreId.match(/^fr_[a-z0-9]{12}$/)) {
                    throw new Error('Invalid file restore ID format');
                }

                const response = await fetch(`/mobile/mobileSlideApi.php?action=browseFileRestore&id=${restoreId}&path=${encodeURIComponent(currentPath)}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Failed to load files');
                }

                document.querySelector('.loading-spinner').style.display = 'none';
                pathDisplay.textContent = currentPath || '/';
                pathDisplay.classList.remove('d-none');

                // Add parent directory link if not at root
                if (currentPath) {
                    const parentPath = currentPath.split('/').slice(0, -1).join('/');
                    const parentEl = document.createElement('div');
                    parentEl.className = 'file-item d-flex align-items-center px-3';
                    parentEl.innerHTML = `
                        <div class="file-info">
                            <h6 class="file-name">
                                <i class="bi bi-arrow-up me-2"></i>
                                Parent Directory
                            </h6>
                        </div>
                    `;
                    parentEl.onclick = () => navigateToFolder(parentPath);
                    container.appendChild(parentEl);
                }

                // Sort directories first, then files
                const sortedItems = [...data.data].sort((a, b) => {
                    if (a.type === b.type) return a.name.localeCompare(b.name);
                    return a.type === 'directory' ? -1 : 1;
                });

                sortedItems.forEach(item => {
                    const itemEl = document.createElement('div');
                    itemEl.className = 'file-item d-flex align-items-center px-3';
                    
                    const isDirectory = item.type === 'directory';
                    const iconClass = isDirectory ? 'bi-folder-fill' : 'bi-file-text';
                    
                    itemEl.innerHTML = `
                        <div class="file-info">
                            <h6 class="file-name">
                                <i class="bi ${iconClass} me-2"></i>
                                ${item.name}
                            </h6>
                            <div class="file-details">
                                ${isDirectory ? '' : formatFileSize(item.size)}
                                ${!isDirectory && item.size && item.modified_at ? ' • ' : ''}
                                ${item.modified_at ? formatDate(item.modified_at) : ''}
                            </div>
                        </div>
                        <button class="btn btn-outline-primary download-button" onclick="handleDownload(event, this)" data-item='${JSON.stringify(item)}'>
                            <i class="bi bi-download"></i>
                        </button>
                    `;

                    if (isDirectory) {
                        const newPath = currentPath ? `${currentPath}/${item.name}` : item.name;
                        itemEl.onclick = () => navigateToFolder(newPath);
                    }

                    container.appendChild(itemEl);
                });

            } catch (error) {
                console.error('Error loading files:', error);
                container.innerHTML = `
                    <div class="alert alert-danger m-3">
                        Failed to load files: ${error.message}
                    </div>
                `;
            } finally {
                isLoading = false;
            }
        }

        // Initial load
        loadFiles();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>