<!DOCTYPE html>
<?php
require_once 'include/getApiKey.php';
if (!hasApiKey()) {
    header('Location: index.php');
    exit;
}
?>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Accounts - Slide Mobile</title>
    <?php include 'include/pwa_head.php'; ?>
    <style>
        .account-list {
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }

        .account-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease-out;
        }

        .account-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .account-item:last-child {
            border-bottom: none;
        }

        .account-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #ffffff;
        }

        .account-contact {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 0.25rem;
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            padding: 1rem;
        }

        .loading .loading-spinner {
            display: flex;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="navbar bg-dark border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Accounts</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="account-list" id="accountList">
            <!-- Accounts will be loaded here -->
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </main>

    <!-- Account Details Modal -->
    <div class="modal fade" id="accountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="accountModalTitle">Account Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="accountModalBody">
                    <!-- Account details will be loaded here -->
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <?php include 'include/bottomNav.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
    <script>
        let isLoading = false;

        async function loadAccounts() {
            if (isLoading) return;
            isLoading = true;

            const accountList = document.getElementById('accountList');
            accountList.classList.add('loading');

            try {
                const response = await fetch('/mobile/mobileSlideApi.php?action=getAccounts');
                const data = await response.json();

                accountList.classList.remove('loading');

                if (data.success && data.data && data.data.length > 0) {
                    displayAccounts(data.data);
                } else {
                    accountList.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-building"></i>
                            <div>No accounts found</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading accounts:', error);
                accountList.classList.remove('loading');
                accountList.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>Failed to load accounts</div>
                    </div>
                `;
            } finally {
                isLoading = false;
            }
        }

        function displayAccounts(accounts) {
            const accountList = document.getElementById('accountList');
            accountList.innerHTML = accounts.map(account => `
                <div class="account-item" onclick="showAccountDetails('${account.account_id}')">
                    <div class="account-name">${escapeHtml(account.account_name)}</div>
                    <div class="account-contact">
                        <i class="bi bi-person"></i> ${escapeHtml(account.primary_contact)}
                    </div>
                    <div class="account-contact">
                        <i class="bi bi-envelope"></i> ${escapeHtml(account.primary_email)}
                    </div>
                </div>
            `).join('');
        }

        async function showAccountDetails(accountId) {
            const modal = new bootstrap.Modal(document.getElementById('accountModal'));
            const modalBody = document.getElementById('accountModalBody');
            
            modalBody.innerHTML = `
                <div class="text-center p-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            modal.show();

            try {
                const response = await fetch(`/mobile/mobileSlideApi.php?action=getAccount&account_id=${encodeURIComponent(accountId)}`);
                const data = await response.json();

                if (data.success && data.data) {
                    const account = data.data;
                    modalBody.innerHTML = `
                        <div class="info-card">
                            <div class="card-label">Account Name</div>
                            <div class="card-value">${escapeHtml(account.account_name)}</div>
                        </div>
                        <div class="info-card">
                            <div class="card-label">Primary Contact</div>
                            <div class="card-value">${escapeHtml(account.primary_contact)}</div>
                        </div>
                        <div class="info-card">
                            <div class="card-label">Primary Email</div>
                            <div class="card-value">${escapeHtml(account.primary_email)}</div>
                        </div>
                        <div class="info-card">
                            <div class="card-label">Primary Phone</div>
                            <div class="card-value">${escapeHtml(account.primary_phone)}</div>
                        </div>
                        ${account.alert_emails && account.alert_emails.length > 0 ? `
                            <div class="info-card">
                                <div class="card-label">Alert Emails</div>
                                <div class="card-value">${account.alert_emails.map(email => escapeHtml(email)).join('<br>')}</div>
                            </div>
                        ` : ''}
                        ${account.billing_address ? `
                            <div class="info-card">
                                <div class="card-label">Billing Address</div>
                                <div class="card-value">
                                    ${escapeHtml(account.billing_address.Line1)}<br>
                                    ${account.billing_address.Line2 ? escapeHtml(account.billing_address.Line2) + '<br>' : ''}
                                    ${escapeHtml(account.billing_address.City)}, ${escapeHtml(account.billing_address.State)} ${escapeHtml(account.billing_address.PostalCode)}<br>
                                    ${escapeHtml(account.billing_address.Country)}
                                </div>
                            </div>
                        ` : ''}
                    `;
                } else {
                    modalBody.innerHTML = '<div class="text-center text-danger">Failed to load account details</div>';
                }
            } catch (error) {
                console.error('Error loading account details:', error);
                modalBody.innerHTML = '<div class="text-center text-danger">Failed to load account details</div>';
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load accounts when page loads
        document.addEventListener('DOMContentLoaded', loadAccounts);
    </script>
</body>
</html>

