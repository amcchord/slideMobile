<!-- Bottom Navigation -->
<div class="navbar fixed-bottom bg-dark border-top">
    <div class="container-fluid d-flex justify-content-around">
        <a href="/mobile/devices.php" class="nav-link text-center" data-page="devices">
            <i class="bi bi-hdd-stack"></i>
            <small class="d-block">Devices</small>
        </a>
        <a href="/mobile/agents.php" class="nav-link text-center" data-page="agents">
            <i class="bi bi-laptop"></i>
            <small class="d-block">Agents</small>
        </a>
        <a href="/mobile/snapshots.php" class="nav-link text-center" data-page="snapshots">
            <i class="bi bi-clock-history"></i>
            <small class="d-block">Snapshots</small>
        </a>
        <a href="/mobile/alerts.php" class="nav-link text-center" data-page="alerts">
            <i class="bi bi-bell"></i>
            <small class="d-block">Alerts</small>
        </a>
        <a href="/mobile/more.php" class="nav-link text-center" data-page="more">
            <i class="bi bi-three-dots"></i>
            <small class="d-block">More</small>
        </a>
    </div>
</div>

<script>
// Enhanced navigation with transitions
(function() {
    // Get current page from URL
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop().replace('.php', '');
    
    // Mark current page as active
    const navLinks = document.querySelectorAll('.navbar.fixed-bottom .nav-link');
    navLinks.forEach(link => {
        const page = link.getAttribute('data-page');
        if (page === currentPage) {
            link.classList.add('active');
        }
    });
    
    // Handle navigation clicks
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Don't navigate if already on the same page
            if (this.classList.contains('active')) {
                return;
            }
            
            const targetUrl = this.getAttribute('href');
            const icon = this.querySelector('i');
            const originalClass = icon.className;
            
            // Change icon to spinner
            icon.className = 'bi bi-arrow-repeat spin';
            
            // Add slide-out class to main content
            const main = document.querySelector('main');
            if (main) {
                main.style.animation = 'slideOutToLeft 0.3s ease-out forwards';
            }
            
            // Navigate after animation
            setTimeout(() => {
                window.location.href = targetUrl;
            }, 200);
        });
    });
    
    // Add slide animations for navigation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideOutToLeft {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(-100%);
                opacity: 0;
            }
        }
        
        .navbar.fixed-bottom .nav-link {
            transition: all 0.2s ease-out;
        }
        
        .navbar.fixed-bottom .nav-link.active {
            transform: scale(1.1);
        }
        
        .navbar.fixed-bottom .nav-link:active {
            transform: scale(0.95);
        }
    `;
    document.head.appendChild(style);
})();
</script> 