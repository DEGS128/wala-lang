<header class="header">
    <div class="header-left">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <span class="breadcrumb-item">HOSPITAL</span>
            <?php
            // Get current page info for breadcrumb
            $current_page = basename($_SERVER['PHP_SELF'], '.php');
            $module_path = '';
            
            // Determine module path for breadcrumb
            if (strpos($_SERVER['PHP_SELF'], '/hcm/') !== false) {
                $module_path = 'Core HCM';
            } elseif (strpos($_SERVER['PHP_SELF'], '/payroll/') !== false) {
                $module_path = 'Payroll Management';
            } elseif (strpos($_SERVER['PHP_SELF'], '/compensation/') !== false) {
                $module_path = 'Compensation Planning';
            } elseif (strpos($_SERVER['PHP_SELF'], '/analytics/') !== false) {
                $module_path = 'HR Analytics';
            } elseif (strpos($_SERVER['PHP_SELF'], '/hmo/') !== false) {
                $module_path = 'HMO & Benefits';
            } elseif (strpos($_SERVER['PHP_SELF'], '/reports/') !== false) {
                $module_path = 'Reports';
            }
            
            if ($module_path) {
                echo '<i class="fas fa-chevron-right"></i>';
                echo '<span class="breadcrumb-item">' . $module_path . '</span>';
            }
            
            // Add current page to breadcrumb if it's not index
            if ($current_page !== 'index') {
                echo '<i class="fas fa-chevron-right"></i>';
                echo '<span class="breadcrumb-item">' . ucwords(str_replace('_', ' ', $current_page)) . '</span>';
            }
            ?>
        </div>
    </div>
    
    <div class="header-right">
        <div class="header-actions">
            <div class="search-box">
                <input type="text" placeholder="Search..." id="globalSearch">
                <button type="button" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="notifications">
                <button class="notification-btn" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button class="mark-all-read">Mark all as read</button>
                    </div>
                    <div class="notification-list">
                        <div class="notification-item unread">
                            <div class="notification-icon">
                                <i class="fas fa-user-plus text-success"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">New Employee Added</div>
                                <div class="notification-text">John Doe has been added to the system</div>
                                <div class="notification-time">2 hours ago</div>
                            </div>
                        </div>
                        <div class="notification-item unread">
                            <div class="notification-icon">
                                <i class="fas fa-money-bill-wave text-info"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Payroll Processed</div>
                                <div class="notification-text">Monthly payroll has been processed successfully</div>
                                <div class="notification-time">1 day ago</div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-calendar-check text-warning"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Attendance Reminder</div>
                                <div class="notification-text">Please submit attendance records by end of day</div>
                                <div class="notification-time">2 days ago</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="user-menu">
                <button class="user-menu-btn" id="userMenuBtn">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-header">
                        <div class="user-info">
                            <div class="user-avatar-large">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
                                <div class="user-role"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'] ?? 'user')); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="user-dropdown-menu">
                        <a href="../../profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                        <a href="../../settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../../logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
