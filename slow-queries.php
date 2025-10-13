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
    <title>Slow Queries - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .query-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }

        .filter-bar {
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filter-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .filter-row input {
            flex: 1;
        }

        .query-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
        }

        .query-item:last-child {
            border-bottom: none;
        }

        .query-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .query-time {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .query-duration {
            font-weight: 700;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .query-duration.low {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .query-duration.medium {
            background-color: rgba(255, 152, 0, 0.2);
            color: #ff9800;
        }

        .query-duration.high {
            background-color: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .query-method {
            display: inline-block;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background-color: rgba(13, 110, 253, 0.2);
            color: #5fa3ff;
            margin-right: 0.5rem;
        }

        .query-url {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
            word-break: break-all;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            padding: 2rem;
        }

        .loading .loading-spinner {
            display: flex;
        }

        .stats-bar {
            padding: 0.75rem 1rem;
            background-color: rgba(13, 110, 253, 0.1);
            border-bottom: 1px solid rgba(13, 110, 253, 0.2);
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .btn-filter {
            white-space: nowrap;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-speedometer2"></i> Slow Queries
            </span>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-row">
            <input type="date" class="form-control form-control-sm" id="dateFrom" placeholder="From">
            <input type="date" class="form-control form-control-sm" id="dateTo" placeholder="To">
            <button class="btn btn-sm btn-primary btn-filter" onclick="loadQueries()">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar" id="statsBar" style="display: none;">
        <i class="bi bi-info-circle"></i> Showing <span id="queryCount">0</span> slow queries (>100ms)
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="query-list" id="queryList">
            <!-- Queries will be loaded here -->
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

        async function loadQueries() {
            if (isLoading) return;
            isLoading = true;
            
            const container = document.getElementById('queryList');
            const statsBar = document.getElementById('statsBar');
            const loadingSpinner = document.querySelector('.loading-spinner');
            
            loadingSpinner.style.display = 'flex';

            try {
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;
                
                let url = '/mobile/mobileSlideApi.php?action=getSlowQueries';
                if (dateFrom) {
                    url += '&date_from=' + encodeURIComponent(dateFrom);
                }
                if (dateTo) {
                    url += '&date_to=' + encodeURIComponent(dateTo);
                }
                
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load slow queries');
                }

                container.innerHTML = '';

                if (data.data.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-speedometer2"></i>
                            <h5>No Slow Queries</h5>
                            <p>No API queries over 100ms found for your API key.</p>
                        </div>
                    `;
                    statsBar.style.display = 'none';
                } else {
                    // Show stats
                    document.getElementById('queryCount').textContent = data.total;
                    statsBar.style.display = 'block';

                    // Display queries
                    data.data.forEach(query => {
                        const queryEl = document.createElement('div');
                        queryEl.className = 'query-item';

                        // Extract just the endpoint path from full URL
                        const urlObj = new URL(query.url);
                        const endpoint = urlObj.pathname + (urlObj.search ? urlObj.search : '');

                        queryEl.innerHTML = `
                            <div class="query-header">
                                <div class="query-time">
                                    <i class="bi bi-clock"></i> ${formatTimestamp(query.timestamp)}
                                </div>
                                <div class="query-duration ${query.severity}">
                                    ${query.duration}ms
                                </div>
                            </div>
                            <div>
                                <span class="query-method">${query.method}</span>
                                <span class="query-url">${endpoint}</span>
                            </div>
                        `;

                        container.appendChild(queryEl);
                    });
                }
            } catch (error) {
                console.error('Error loading slow queries:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle text-danger"></i>
                        <h5>Error</h5>
                        <p>${error.message}</p>
                    </div>
                `;
                statsBar.style.display = 'none';
            } finally {
                isLoading = false;
                loadingSpinner.style.display = 'none';
            }
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const queryDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            
            const timeStr = date.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: false
            });
            
            if (queryDate.getTime() === today.getTime()) {
                return 'Today ' + timeStr;
            }
            
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            if (queryDate.getTime() === yesterday.getTime()) {
                return 'Yesterday ' + timeStr;
            }
            
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric' 
            }) + ' ' + timeStr;
        }

        // Set default date range to last 7 days
        function setDefaultDateRange() {
            const today = new Date();
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            
            document.getElementById('dateTo').value = today.toISOString().split('T')[0];
            document.getElementById('dateFrom').value = weekAgo.toISOString().split('T')[0];
        }

        // Initialize
        setDefaultDateRange();
        loadQueries();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

