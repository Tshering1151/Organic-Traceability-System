<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper {
            display: flex;
            flex: 1;
        }
        .header {
            background: linear-gradient(135deg, #63992eff 0%, #25642fff 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }
        .header-img {
            max-width: 80px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .dashboard-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .welcome-section {
            background: linear-gradient(135deg, #63992eff 0%, #25642fff 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c5530 0%, #1a3d1e 100%);
            color: white;
            min-height: 100%;
            padding: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.1);
            color: #90EE90;
            padding-left: 2rem;
        }
        
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.2);
            color: #90EE90;
            border-left: 4px solid #90EE90;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
            overflow-y: auto;
        }
        
        /* Mobile Toggle */
        .sidebar-toggle {
            display: none;
            background: #25642f;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                z-index: 1000;
                height: 100vh;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <!-- Top Gradient Banner -->
        <div class="header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <!-- Left Image -->
                    <div class="col-md-2 text-start">
                        <img src="../images/header-left.jpg" alt="Header Left Image" class="img-fluid header-img">
                    </div>
                    
                    <!-- Center Title -->
                    <div class="col-md-8 text-center">
                        <h1 class="mb-0"><i class="bi bi-speedometer2"></i> Admin Panel</h1>
                        <large>Department of Agricultural Marketing & Cooperatives</large>
                    </div>
                    
                    <!-- Right Image -->
                    <div class="col-md-2 text-end">
                        <img src="../images/header-right.jpg" alt="Header Right Image" class="img-fluid header-img" style="max-width: 80px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                    </div>
                </div>
            </div>
        </div>

        <!-- Yellow Bar -->
        <div style="background: linear-gradient(90deg, orange, yellow); color: black; padding: 8px 0; border-bottom: 1px solid #ccc;">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <!-- Admin Username -->
                    <div class="col-md-6 text-start">
                        <a href="manage_users.php" style="color: black; text-decoration: none;">
                            <i class="bi bi-person-circle"></i> 
                            <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                        </a>
                    </div>

                    <!-- Logout Button -->
                    <div class="col-md-6 text-end">
                        <a href="logout.php" style="color: black; text-decoration: none;">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Wrapper with Sidebar and Content -->
    <div class="main-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h5><i class="bi bi-leaf"></i> Organic Trace</h5>
                <small>Management System</small>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="bi bi-house-door"></i>
                        Home
                    </a>
                </li>
                <li>
                    <a href="add_organic_product.php">
                        <i class="bi bi-plus-circle"></i>
                        Add Organic Product
                    </a>
                </li>
                <li>
                    <a href="add_organic_details.php">
                        <i class="bi bi-info-circle"></i>
                        Add Organic Details
                    </a>
                </li>
                <li>
                    <a href="manage_users.php">
                        <i class="bi bi-people"></i>
                        Manage Users
                    </a>
                </li>
                <li>
                    <a href="view_products.php">
                        <i class="bi bi-eye"></i>
                        View Products
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="bi bi-gear"></i>
                        Settings
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Mobile Toggle Button -->
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i> Menu
            </button>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2 class="mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
                <p class="mb-0">Manage your organic products and application from this dashboard</p>
            </div>

            <!-- Dashboard Content -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <i class="bi bi-plus-circle-fill text-success" style="font-size: 2rem;"></i>
                            <h5 class="card-title mt-3">Add Organic Product</h5>
                            <p class="card-text">Add new organic products to the system</p>
                            <a href="add_organic_product.php" class="btn btn-success">Add Product</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <i class="bi bi-info-circle-fill text-primary" style="font-size: 2rem;"></i>
                            <h5 class="card-title mt-3">Organic Details</h5>
                            <p class="card-text">Manage organic product details</p>
                            <a href="add_organic_details.php" class="btn btn-primary">Manage Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <i class="bi bi-people-fill text-warning" style="font-size: 2rem;"></i>
                            <h5 class="card-title mt-3">Users</h5>
                            <p class="card-text">Manage system users</p>
                            <a href="manage_users.php" class="btn btn-warning">View Users</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Row -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <i class="bi bi-box-seam text-success" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Total Products</h6>
                            <h4 class="text-success">125</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <i class="bi bi-check-circle text-primary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Verified</h6>
                            <h4 class="text-primary">89</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Pending</h6>
                            <h4 class="text-warning">36</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <i class="bi bi-people text-info" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Active Users</h6>
                            <h4 class="text-info">12</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="bi bi-activity"></i> Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">You are successfully logged in as an admin.</p>
                            <p class="text-muted">Last login: <?php echo date('F j, Y, g:i a'); ?></p>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i>
                                    Use the sidebar to navigate between different sections of the admin panel.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <!-- Center Content -->
                <div class="col-12 text-center">
                    <h5 class="mb-3">Admin Dashboard</h5>
                    <p class="mb-2">© <?php echo date('Y'); ?> DAMC. All rights reserved.</p>
                    <p class="mb-0">
                        <a href="#" class="text-white me-3"><i class="bi bi-envelope"></i> Contact</a>
                        <a href="#" class="text-white me-3"><i class="bi bi-telephone"></i> Support</a>
                        <a href="#" class="text-white"><i class="bi bi-info-circle"></i> About</a>
                    </p>
                </div>
            </div>
            
            <!-- Bottom Footer -->
            <hr class="my-3 border-light">
            <div class="row">
                <div class="col-12 text-center">
                    <small class="text-light">
                        Designed by RIM Interns | 2025
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>