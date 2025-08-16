<?php
// session_start();
// Check if user is logged in and is an aggregator
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'aggregator') {
//     header("Location: login.php");
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggregator Dashboard - Organic Traceability System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
            color: #28a745 !important;
        }
        .card {
            transition: transform 0.2s ease-in-out;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-buy-product {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 12px 30px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .btn-buy-product:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .footer {
            background-color: #343a40;
            color: white;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-success">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-leaf me-2"></i>
                    Organic Traceability System
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-dashboard me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box me-1"></i>My Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart me-1"></i>Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="suppliers.php">
                                <i class="fas fa-users me-1"></i>Suppliers
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username'] ?? 'Aggregator'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container my-5">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h1 class="display-4 text-success mb-3">
                            <i class="fas fa-seedling me-3"></i>
                            Welcome to Aggregator Dashboard
                        </h1>
                        <p class="lead text-muted mb-4">
                            Manage your organic product sourcing and traceability with ease
                        </p>
                        <a href="product.php" class="btn btn-success btn-buy-product btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>
                            Buy Products
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-2x mb-3"></i>
                        <h4>Total Products</h4>
                        <h2>156</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-3"></i>
                        <h4>Active Orders</h4>
                        <h2>23</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-3"></i>
                        <h4>Suppliers</h4>
                        <h2>45</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-certificate fa-2x mb-3"></i>
                        <h4>Certified Organic</h4>
                        <h2>89%</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3">Quick Actions</h3>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-plus-circle fa-3x text-success mb-3"></i>
                        <h5>Add New Product</h5>
                        <p class="text-muted">Source new organic products from suppliers</p>
                        <a href="add_product.php" class="btn btn-outline-success">Add Product</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-search fa-3x text-info mb-3"></i>
                        <h5>Track Products</h5>
                        <p class="text-muted">Monitor product traceability and origins</p>
                        <a href="track.php" class="btn btn-outline-info">Track Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-3x text-warning mb-3"></i>
                        <h5>View Reports</h5>
                        <p class="text-muted">Analyze sales and inventory reports</p>
                        <a href="reports.php" class="btn btn-outline-warning">View Reports</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Recent Activities
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-shopping-cart text-success me-2"></i>
                                    New order received for Organic Tomatoes
                                </div>
                                <small class="text-muted">2 hours ago</small>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-truck text-info me-2"></i>
                                    Shipment delivered from Green Valley Farm
                                </div>
                                <small class="text-muted">4 hours ago</small>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-certificate text-warning me-2"></i>
                                    Organic certification updated for Product #1234
                                </div>
                                <small class="text-muted">1 day ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>
                        <i class="fas fa-leaf me-2"></i>
                        Organic Traceability System
                    </h5>
                    <p class="text-muted">
                        Ensuring transparency and quality in organic food supply chain.
                    </p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-light">About Us</a></li>
                        <li><a href="contact.php" class="text-light">Contact</a></li>
                        <li><a href="help.php" class="text-light">Help Center</a></li>
                        <li><a href="privacy.php" class="text-light">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Contact Info</h6>
                    <p class="text-muted mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        info@organictraceability.com
                    </p>
                    <p class="text-muted mb-1">
                        <i class="fas fa-phone me-2"></i>
                        +1 (555) 123-4567
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        123 Green Street, Eco City
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> Organic Traceability System. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Add some interactive functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            // Add click tracking for buy product button
            const buyButton = document.querySelector('.btn-buy-product');
            if (buyButton) {
                buyButton.addEventListener('click', function(e) {
                    // You can add analytics tracking here
                    console.log('Buy Product button clicked - redirecting to product.php');
                });
            }
        });
    </script>
</body>
</html>