<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-hospital"></i>
            <span>HOSPITAL</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="../../index.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../hcm/index.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Core HCM</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../payroll/index.php" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payroll Management</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../compensation/index.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Compensation Planning</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../analytics/index.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>HR Analytics</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../hmo/index.php" class="nav-link">
                    <i class="fas fa-heartbeat"></i>
                    <span>HMO & Benefits</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../reports/generate_report.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
                <div class="user-role"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'] ?? 'user')); ?></div>
            </div>
        </div>
        <a href="../../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
