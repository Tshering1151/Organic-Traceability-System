<?php
session_start();


// Check if user is logged in and is an aggregator
if (!isset($_SESSION['aggre_id']) || $_SESSION['user_type'] !== 'aggregator') {
    header("Location: ../login.php");
    exit;
}


require_once __DIR__ . '/../database/db.php';


// Initialize
$farmer = null;
$products = [];
$message = "";
$success_message = "";


$aggregator_name = $_SESSION['user_name'];
$aggregator_id = $_SESSION['aggre_id'];
$aggregator_cid = $_SESSION['user_cid'];


// Get aggregator's profile image from aggregators table
$profile_stmt = $conn->prepare("SELECT profile_picture FROM aggre_tbl WHERE aggre_id = ?");
$profile_stmt->bind_param("i", $aggregator_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$aggregator_profile = $profile_result->fetch_assoc();
$profile_stmt->close();


$profile_picture = $aggregator_profile['profile_picture'] ?? null;
$profile_image_path = null;


// Check if profile picture exists
if ($profile_picture) {
    $profile_image_path = "/Test/Aggregator/uploads/profiles/" . $profile_picture;
    // Check if file actually exists on server
    $server_path = __DIR__ . "/uploads/profiles/" . $profile_picture;
    if (!file_exists($server_path)) {
        $profile_image_path = null;
    }
}


// Handle farmer search
if (isset($_POST['find_farmer'])) {
    $cid = trim($_POST['cid']);


    if (strlen($cid) == 11) {
        // Fetch farmer using correct table and column names
        $stmt = $conn->prepare("SELECT id, name, cid, dzongkhag, gewog FROM farmers WHERE cid = ?");
        $stmt->bind_param("s", $cid);
        $stmt->execute();
        $farmerResult = $stmt->get_result();


        if ($farmerResult->num_rows > 0) {
            $farmer = $farmerResult->fetch_assoc();


            // Fetch farmer's products using correct table and column names
            $stmtProd = $conn->prepare("SELECT product_id, product_name, harvest_date, farm_location, product_image
                                        FROM products WHERE farmer_id = ?");
            $stmtProd->bind_param("i", $farmer['id']);
            $stmtProd->execute();
            $products = $stmtProd->get_result()->fetch_all(MYSQLI_ASSOC);


        } else {
            $message = "No farmer found with this CID.";
        }
    } else {
        $message = "Please enter a valid 11-digit CID.";
    }
}


// Handle product collection
if (isset($_POST['collect_products'])) {
    if (isset($_POST['selected_products']) && !empty($_POST['selected_products'])) {
        $target_market = $_POST['target_market'];
        $selected_products = $_POST['selected_products'];
        $quantities = $_POST['quantities'];
       
        // Create aggregator_products table if not exists (using correct structure from first code)
        $create_table_sql = "CREATE TABLE IF NOT EXISTS aggregator_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aggregator_id INT NOT NULL,
            product_id VARCHAR(50) UNIQUE NOT NULL,
            farmer_id INT NOT NULL,
            farmer_name VARCHAR(255) NOT NULL,
            farmer_cid VARCHAR(11) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity DECIMAL(10,2) NOT NULL,
            unit VARCHAR(50) NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            market_name VARCHAR(255) NOT NULL,
            harvest_date DATE,
            farm_location VARCHAR(255),
            status VARCHAR(20) DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        mysqli_query($conn, $create_table_sql);
       
        $collection_date = date('Y-m-d H:i:s');
        $collected_count = 0;
       
        foreach ($selected_products as $product_id) {
            $quantity = isset($quantities[$product_id]) ? $quantities[$product_id] : 1;
           
            // Get product details for aggregated product creation
            $product_stmt = $conn->prepare("SELECT product_name, harvest_date, farm_location FROM products WHERE id = ?");
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            $product_data = $product_result->fetch_assoc();
           
            if ($product_data) {
                // Generate unique aggregated product ID
                $aggregated_product_id = 'AGG_' . $aggregator_id . '_' . time() . '_' . rand(1000, 9999);
               
                // Insert into aggregator_products table with correct structure
                $insert_stmt = $conn->prepare("INSERT INTO aggregator_products
                    (aggregator_id, product_id, farmer_id, farmer_name, farmer_cid,
                     product_name, quantity, unit, unit_price, market_name, harvest_date, farm_location, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())");
               
                // Set default values for missing fields
                $unit = 'kg';
                $unit_price = 0.00;
               
                $insert_stmt->bind_param("ississsddsss",
                    $aggregator_id,
                    $aggregated_product_id,
                    $farmer['id'],
                    $farmer['name'],
                    $farmer['cid'],
                    $product_data['product_name'],
                    $quantity,
                    $unit,
                    $unit_price,
                    $target_market,
                    $product_data['harvest_date'],
                    $product_data['farm_location']
                );
               
                if ($insert_stmt->execute()) {
                    $collected_count++;
                }
            }
        }
       
        if ($collected_count > 0) {
            $success_message = "Successfully collected $collected_count product(s) to your collection!";
        } else {
            $message = "Failed to collect products. Please try again.";
        }
    } else {
        $message = "Please select at least one product to collect.";
    }
}


// Get aggregator's complete information
$stmt = $conn->prepare("SELECT name, cid, dzongkhag, gewog, business_name, business_license, contact_phone, created_at FROM aggregators WHERE id = ?");
$stmt->bind_param("i", $aggregator_id);
$stmt->execute();
$result = $stmt->get_result();
$aggregator_details = $result->fetch_assoc();
$stmt->close();


// Fetch aggregator's products
$aggregator_products = [];
$table_exists_query = $conn->query("SHOW TABLES LIKE 'aggregator_products'");
if ($table_exists_query->num_rows > 0) {
    $products_query = $conn->prepare("SELECT * FROM aggregator_products WHERE aggregator_id = ? ORDER BY created_at DESC");
    if ($products_query) {
        $products_query->bind_param("i", $aggregator_id);
        $products_query->execute();
        $result = $products_query->get_result();
        if ($result) {
            $aggregator_products = $result->fetch_all(MYSQLI_ASSOC);
        }
        $products_query->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggregator Dashboard - OrganicTrace</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fbf6 0%, #e8f5e8 100%);
            min-height: 100vh;
        }
       
        .navbar {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c59 100%);
            padding: 15px 0;
        }
       
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
       
        /* Profile Dropdown Styles */
        .profile-dropdown {
            position: relative;
        
        }
        .profile-trigger {
            background: none;
            border: none;
            display: flex;
            align-items: center;
            cursor: pointer;
        }


        /* .profile-trigger:hover {
            background: rgba(255, 255, 255, 0.2);
        } */


        .profile-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            margin-right: 8px;
        }


        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .profile-avatar-text {
            font-weight: bold;
            color: #555;
        }


        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }


        .profile-name {
            font-weight: bold;
            margin: 0;
            line-height: 1.2;
        }


        .profile-role {
            font-size: 11px;
            opacity: 0.8;
            margin: 0;
            line-height: 1.2;
        }


        .dropdown-arrow {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }


        .profile-dropdown.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 10px 0;
            min-width: 180px;
            z-index: 1000;
        }


        .profile-dropdown.active .dropdown-menu {
            display: block;
        }

        .dropdown-header {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
        }


        .dropdown-avatar {
            width: 50px;
            height: 50px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: white;
            margin: 0 auto 10px;
            overflow: hidden;
            border: 3px solid #f0f0f0;
        }


        .dropdown-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }


        .dropdown-avatar-text {
            color: white;
        }


        .dropdown-name {
            font-weight: bold;
            color: #333;
            margin: 0 0 5px 0;
            font-size: 14px;
        }


        .dropdown-role {
            color: #666;
            font-size: 12px;
            margin: 0;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
        }


        .dropdown-item:hover {
            background: #f5f5f5;
           
        }


        .dropdown-item i {
            width: 16px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }


        .dropdown-item span {
            color: #333;
            font-weight: 500;
        }


        .dropdown-divider {
            height: 1px;
            background: #f0f0f0;
            margin: 5px 0;
        }


        .logout-btn { 
            color: #f44336 !important; 
        }


        .logout-btn:hover {
            background: #ffebee !important;
        }


        .logout-btn i {
            color: #f44336 !important;
        }


        .dashboard-header {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            padding: 25px;
        }
       
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
       
        .search-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid #4CAF50;
            display: none; /* Initially hidden */
        }
       
        .farmer-info-card {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
       
        .product-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
       
        .product-card:hover {
            border-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }
       
        .product-card.selected {
            border-color: #4CAF50;
            background: #f0f9f0;
        }
       
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
       
        .btn-custom {
            background: #4CAF50;
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
       
        .btn-custom:hover {
            background: #45a049;
            color: white;
            transform: translateY(-1px);
        }
       
        .btn-outline-custom {
            border: 2px solid #4CAF50;
            color: #4CAF50;
            background: transparent;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
       
        .btn-outline-custom:hover {
            background: #4CAF50;
            color: white;
        }
       
        .collection-form {
            background: #e8f5e8;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
            display: none;
        }
       
        .form-control:focus, .form-select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
       
        .stat-card {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
       
        .stat-card .stat-icon {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 15px;
        }
       
        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2d5016;
        }
       
        .stat-card .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
       
        .quantity-input {
            width: 100px;
            display: inline-block;
        }


        .products-table {
            margin-top: 30px;
        }


        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }


        .table thead th {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 15px;
        }


        .table tbody td {
            padding: 12px 15px;
            border-color: #f0f0f0;
        }


        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .profile-trigger {
                padding: 8px 12px;
                border-radius: 20px;
            }
            
            .profile-avatar {
                width: 32px;
                height: 32px;
                font-size: 14px;
                border: 2px solid rgba(255, 255, 255, 0.3);
            }
            
            .profile-info {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .profile-name {
                font-size: 14px;
                font-weight: 600;
            }
            
            .profile-role {
                font-size: 11px;
                opacity: 0.9;
            }
            
            .dropdown-menu {
                right: 0;
                min-width: 220px;
                margin-top: 8px;
            }
            
            .dropdown-header {
                padding: 20px 15px;
            }
            
            .dropdown-avatar {
                width: 60px;
                height: 60px;
                margin-bottom: 12px;
            }
            
            .dropdown-name {
                font-size: 16px;
                margin-bottom: 4px;
            }
            
            .dropdown-role {
                font-size: 13px;
            }
            
            .dropdown-item {
                padding: 15px 20px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-leaf me-2"></i>OrganicTrace - Aggregator
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <div class="profile-dropdown" id="profileDropdown">
                            <button class="profile-trigger" onclick="toggleDropdown()">
                                <div class="profile-avatar">
                                    <?php if ($profile_image_path): ?>
                                        <img src="<?= $profile_image_path ?>" alt="<?= htmlspecialchars($aggregator_name) ?>">
                                    <?php else: ?>
                                        <span class="profile-avatar-text"><?= strtoupper(substr($aggregator_name, 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="profile-info">
                                    <div class="profile-name"><?= htmlspecialchars($aggregator_name) ?></div>
                                    <div class="profile-role">Aggregator</div>
                                </div>
                                <i class="fas fa-chevron-down dropdown-arrow"></i>
                            </button>
                            
                            <div class="dropdown-menu">
                                <div class="dropdown-header px-3 pb-2 border-bottom">
                                    <div class="dropdown-avatar mb-2">
                                        <?php if ($profile_image_path): ?>
                                            <img src="<?= $profile_image_path ?>" alt="<?= htmlspecialchars($aggregator_name) ?>">
                                        <?php else: ?>
                                            <span class="dropdown-avatar-text"><?= strtoupper(substr($aggregator_name, 0, 1)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dropdown-name fw-bold"><?= htmlspecialchars($aggregator_name) ?></div>
                                    <div class="dropdown-role text-muted">Aggregator</div>
                                </div>
                                
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i>
                                    <span>Profile</span>
                                </a>
                                
                                <div class="dropdown-divider"></div>
                                
                                <a href="../logout.php" class="dropdown-item logout-btn text-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>



    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Welcome Header -->
        <div class="dashboard-header">
            <h1 class="mb-3">
                <i class="fas fa-warehouse text-success me-3"></i>
                Welcome, <?= htmlspecialchars($aggregator_name) ?>
            </h1>
            <p class="lead mb-2">Aggregator Dashboard - Manage your collection and distribution operations</p>
        </div>


        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <i class="fas fa-boxes stat-icon"></i>
                    <div class="stat-number"><?= count($aggregator_products) ?></div>
                    <div class="stat-label">Products Collected</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <i class="fas fa-users stat-icon"></i>
                    <div class="stat-number">0</div>
                    <div class="stat-label">Connected Farmers</div>
                </div>
            </div>
        </div>


        <!-- Main Actions -->
        <div class="main-card">
            <!-- Add Product Button -->
            <div class="text-center mb-4">
                <button type="button" class="btn btn-custom btn-lg" id="add_product_btn">
                    <i class="fas fa-plus-circle me-2"></i>Add Product from Farmer
                </button>
            </div>


            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>


            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>


            <!-- Farmer Search Form (Initially Hidden) -->
            <div class="search-section" id="search_section">
                <h4 class="text-success mb-3">
                    <i class="fas fa-search me-2"></i>Find Farmer
                </h4>
                <form method="POST" id="farmer_search_form">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label for="cid" class="form-label">Farmer CID</label>
                            <input type="text" name="cid" id="cid" class="form-control"
                                   placeholder="Enter 11-digit Farmer CID" maxlength="11"
                                   value="<?= htmlspecialchars($_POST['cid'] ?? '') ?>" required>
                            <div class="form-text">Enter the farmer's 11-digit CID number</div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="find_farmer" class="btn btn-custom w-100">
                                <i class="fas fa-search me-2"></i>Find Farmer
                            </button>
                        </div>
                    </div>
                </form>
            </div>


            <?php if ($farmer): ?>
                <!-- Farmer Information -->
                <div class="farmer-info-card">
                    <h4><i class="fas fa-user-check me-2"></i>Farmer Found</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Name:</strong> <?= htmlspecialchars($farmer['name']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>CID:</strong> <?= htmlspecialchars($farmer['cid']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Location:</strong> <?= htmlspecialchars(($farmer['dzongkhag'] ?? 'N/A') . ', ' . ($farmer['gewog'] ?? 'N/A')); ?></p>
                        </div>
                    </div>
                </div>


                <!-- Available Products -->
                <div class="products-section">
                    <h4 class="text-success mb-3">
                        <i class="fas fa-boxes me-2"></i>Available Products
                    </h4>
                    <?php if (!empty($products)): ?>
                        <form method="POST" id="collect_products_form">
                            <input type="hidden" name="farmer_id" value="<?= $farmer['id']; ?>">
                           
                            <!-- Product Selection -->
                            <div class="row">
                                <?php foreach ($products as $prod): ?>
                                    <div class="col-md-6">
                                        <div class="product-card" data-product-id="<?= $prod['id'] ?>">
                                            <div class="row align-items-center">
                                                <div class="col-3">
                                                    <?php if (!empty($prod['product_image'])): ?>
                                                        <img src="../Farmer/uploads/products/<?= htmlspecialchars($prod['product_image']); ?>"
                                                             alt="Product Image" class="product-image"
                                                             onerror="this.src='../assets/images/no-image.png'">
                                                    <?php else: ?>
                                                        <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-seedling text-success" style="font-size: 2rem;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-9">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input product-checkbox" type="checkbox"
                                                               name="selected_products[]" value="<?= $prod['id'] ?>"
                                                               id="product_<?= $prod['id'] ?>">
                                                        <label class="form-check-label fw-bold" for="product_<?= $prod['id'] ?>">
                                                            <?= htmlspecialchars($prod['product_name']); ?>
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block">
                                                        <strong>Harvest Date:</strong> <?= htmlspecialchars($prod['harvest_date'] ?? 'N/A'); ?>
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <strong>Location:</strong> <?= htmlspecialchars($prod['farm_location'] ?? 'N/A'); ?>
                                                    </small>
                                                    <div class="mt-2">
                                                        <label class="form-label small">Quantity (kg):</label>
                                                        <input type="number" class="form-control quantity-input"
                                                               name="quantities[<?= $prod['id'] ?>]"
                                                               step="0.01" min="0.01" value="1.00" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>


                            <!-- Collection Form -->
                            <div class="collection-form" id="collection_form">
                                <h5 class="text-success mb-3">
                                    <i class="fas fa-plus-circle me-2"></i>Collection Details
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="target_market" class="form-label">Target Market</label>
                                        <select name="target_market" id="target_market" class="form-select" required>
                                            <option value="">-- Select Market --</option>
                                            <option value="Thimphu Weekend Market">Thimphu Weekend Market</option>
                                            <option value="Paro Market">Paro Market</option>
                                            <option value="Punakha Market">Punakha Market</option>
                                            <option value="Wangdue Market">Wangdue Market</option>
                                            <option value="Bumthang Market">Bumthang Market</option>
                                            <option value="Organic Stores">Organic Stores</option>
                                            <option value="Restaurants">Restaurants</option>
                                            <option value="Processing Units">Processing Units</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="submit" name="collect_products" class="btn btn-custom w-100">
                                            <i class="fas fa-plus me-2"></i>Collect Selected Products
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No products found for this farmer.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>


        <!-- My Products Section -->
        <?php if (!empty($aggregator_products)): ?>
        <div class="main-card">
            <h3 class="text-success mb-4">
                <i class="fas fa-warehouse me-2"></i>My Collected Products (<?= count($aggregator_products) ?>)
            </h3>
           
            <div class="products-table">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Farmer</th>
                            <th>Market</th>
                            <th>Date Added</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aggregator_products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr($product['product_id'], 0, 15)) . '...' ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td><?= htmlspecialchars($product['quantity']) . ' ' . htmlspecialchars($product['unit']) ?></td>
                            <td><?= htmlspecialchars($product['farmer_name']) ?></td>
                            <td><?= htmlspecialchars($product['market_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($product['created_at'])) ?></td>
                            <td>
                                <span class="badge bg-success"><?= htmlspecialchars(ucfirst($product['status'])) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
   
    <script>
        // Profile dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }


        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });


        // Close dropdown on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const dropdown = document.getElementById('profileDropdown');
                dropdown.classList.remove('active');
            }
        });


        $(document).ready(function() {
            // Show/Hide Add Product Form
            $('#add_product_btn').on('click', function() {
                const searchSection = $('#search_section');
                if (searchSection.is(':visible')) {
                    searchSection.hide();
                    $(this).html('<i class="fas fa-plus-circle me-2"></i>Add Product from Farmer');
                } else {
                    searchSection.show();
                    $(this).html('<i class="fas fa-times me-2"></i>Cancel');
                }
            });


            // Format CID input to only allow digits
            $('#cid').on('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 11) {
                    value = value.slice(0, 11);
                }
                this.value = value;
            });


            // Product selection handling
            $('.product-checkbox').on('change', function() {
                const productCard = $(this).closest('.product-card');
                const quantityInput = productCard.find('input[type="number"]');
               
                if ($(this).is(':checked')) {
                    productCard.addClass('selected');
                    quantityInput.prop('disabled', false).focus();
                } else {
                    productCard.removeClass('selected');
                    quantityInput.prop('disabled', true);
                }
               
                // Show/hide collection form based on selections
                updateCollectionForm();
            });


            // Update collection form visibility
            function updateCollectionForm() {
                const selectedCount = $('.product-checkbox:checked').length;
                if (selectedCount > 0) {
                    $('#collection_form').show();
                } else {
                    $('#collection_form').hide();
                }
            }


            // Product card click to toggle selection
            $('.product-card').on('click', function(e) {
                if (!$(e.target).is('input, label')) {
                    const checkbox = $(this).find('.product-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });


            // Form validation
            $('#collect_products_form').on('submit', function(e) {
                const selectedProducts = $('.product-checkbox:checked').length;
                const targetMarket = $('#target_market').val();
               
                if (selectedProducts === 0) {
                    e.preventDefault();
                    alert('Please select at least one product to collect.');
                    return false;
                }
               
                if (!targetMarket) {
                    e.preventDefault();
                    alert('Please select a target market.');
                    return false;
                }
               
                // Validate quantities
                let validQuantities = true;
                $('.product-checkbox:checked').each(function() {
                    const productId = $(this).val();
                    const quantity = parseFloat($(`input[name="quantities[${productId}]"]`).val());
                    if (!quantity || quantity <= 0) {
                        validQuantities = false;
                        return false;
                    }
                });
               
                if (!validQuantities) {
                    e.preventDefault();
                    alert('Please enter valid quantities for all selected products.');
                    return false;
                }
            });


            // Auto-hide alerts after 5 seconds
            $('.alert-success').delay(5000).fadeOut();
        });
    </script>
</body>
</html>